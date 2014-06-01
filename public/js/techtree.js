$(document).ready(function(){

    function makeSVG(tag, attribs, value)
    {
        if (attribs === null) {
            attribs = {};
        }

        var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
        for (var k in attribs) {
            el.setAttribute(k, attribs[k]);
        }

        if (value) {
            value = document.createTextNode(value);
            el.appendChild(value);
        }
        return el;
    }

    techtree = {

        init: function()
        {
            /* after init: moving technology divs to correct spots */
            $('.techdata').each(function(index) {
                var key = $(this).attr('id');
                var html = $(this).html();
                key = key.replace('techsource','grid');
                if ($("#"+key)){
                    $("#"+key).html(html);
                }
            });

            /* draw svg lines for requirements between techs. Have to be redrawn when
             * screen size is changing..
             */
            techtree.draw_requirements();
            $(window).resize(function() {
                techtree.draw_requirements();
            });
        },

         /**
          *
          * @param object src A jquery object which serves as source point for the requirment line
          * @param object trgt A jquery object which serves as target point for the requirment line
          * @param numeric count Required tech level to fullfill the requirement
          * @param bool fullfilled OPTIONAL Is requirement fullfilled? (default = false)
          */
         draw_requirement: function(src, trgt, type, required_tech_count, tech_count)
         {
             fullfilled = false;
             if (parseInt(tech_count) >= parseInt(required_tech_count)) {
                 fullfilled = true;
             }

             var srcid = src.parent().attr('id').replace('grid-','');
             var trgtid = trgt.parent().attr('id').replace('grid-','');
             var srcpos = src.parent().position();
             var trgtpos = trgt.parent().position();

             source_grid_xy  = srcid.split('-');
             target_grid_xy  = trgtid.split('-');

             source_y = parseInt(source_grid_xy[0]);
             source_x = parseInt(source_grid_xy[1]);
             source_left = srcpos.left;
             source_top  = srcpos.top;
             source_width  = src.outerWidth();
             source_height = src.outerHeight();

             target_y = parseInt(target_grid_xy[0]);
             target_x = parseInt(target_grid_xy[1]);
             target_left = trgtpos.left;
             target_top  = trgtpos.top;
             target_width  = trgt.outerWidth();
             target_height = trgt.outerHeight();

             ya = source_top + source_height;
             yb = source_top + source_height + 20;
             yc = target_top - target_height;
             yd = target_top - 20;
             ye = target_top;

             xa = source_left + Math.round(source_width/2);
             xe = target_left + Math.round(target_width/2);

             unique_positioner = Math.abs(source_y-1)*6 + source_x + source_y + target_x*3 + target_y*3;

             xb = xc = target_left + target_width  + unique_positioner;

             switch (type) {
                 case 'building': stroke_color = '#99d';
                                  break;
                 case 'research': stroke_color = '#9d9';
                                  break;
                 case 'ship': stroke_color = '#cc7';
                              break;
                 default: stroke_color = '#666';
                          break;
             }

             var group = makeSVG('g', {title: src.attr('id') + ' to ' + trgt.attr('id')});
             xd = xe = target_left + unique_positioner;

             if (source_x == target_x && source_y == target_y-1) {
                 xe = target_left + Math.round(target_width/2);
                 var params = {
                         x1:xe,
                         y1:ya,
                         x2:xe,
                         y2:ye,
                         stroke: stroke_color,
                         'stroke-width': '3px',
                         'fill-opacity':'0',
                         'class':type
                 };
                 if (fullfilled == false) {
                     params['stroke-dasharray'] = '5 5';
                     params['stroke-width'] = '2px';
                 }
                 group.appendChild(makeSVG('line', params));
             } else {
                 d1 = xa + ',' + ya;
                 d2 = xe + ',' + yb;
                 d3 = xe + ',' + ye;
                 d = d1 + ' ' + d2 + ' ' + d3;
                 var params = {
                     points: d,
                     stroke: stroke_color,
                     'stroke-width': '3px',
                     'fill-opacity':'0',
                     'class':type
                 };
                 if (fullfilled == false) {
                     params['stroke-dasharray'] = '5 5';
                     params['stroke-width'] = '2px';
                 }
                 group.appendChild(makeSVG('polyline', params));
             }

             d1 = String(xe-4) + ',' + String(ye-8);
             d2 = String(xe)   + ',' + String(ye);
             d3 = String(xe+4) + ',' + String(ye-8);
             d = d1 + ' ' + d2 + ' ' + d3;

             group.appendChild(makeSVG('polyline', {points: d, fill: stroke_color, 'class':type}));

             text = makeSVG('text', {x:xe-12, y:ye-8,'font-size': '12px', fill: '#666', 'class':type}, required_tech_count);
             group.appendChild(text);

             document.getElementById('grid-svg').appendChild(group);
         },
         draw_requirements: function() {
             setTimeout(function() {
                 $('svg *').remove();
                 /* Take requirements data to draw the lines into techtree */
                 $('.requirementsdata').each(function() {
                     if ($(this).hasClass( 'building' )) {
                         from_class = 'building';
                         to_class = 'building';
                     } else if ($(this).hasClass( 'research' )) {
                         from_class = 'building';
                         to_class = 'research';
                     } else if ($(this).hasClass( 'ship' )) {
                         from_class = 'research';
                         to_class = 'ship';
                     } else {
                         from_class = 'building';
                         to_class = 'personell';
                     }
                     data = $(this).html().trim().split('-');
                     techId = parseInt(data[0]);
                     requiredTechId = parseInt(data[1]);
                     req_count = parseInt(data[2]);
                     count = parseInt(data[3]);
                     domSourceElem = $('#'+from_class+'-' + requiredTechId);
                     domTargetElem = $('#'+to_class+'-' + techId);
                     if (domSourceElem && domTargetElem) {
                         techtree.draw_requirement(domSourceElem, domTargetElem, to_class, req_count, count);
                     }
                 });
             }, 100);
         },

         /* little helper to reset colors in action points bar */
        reset_colors_for_bar_buttons: function() {
            $('.techModal .progress a.progress-bar i').parent().css({
                'background-image': 'none',
                'background-color': '#eee'
            });
        },
        /**
         *
         */
        refresh_resource_bar: function() {
            var url = '/resources/json/reloadresourcebar';
            $('#resource-bar').load(url, null, function() {
                console.log("resource bar loaded :D");
            });
        }
    };

    $('.techModal').on('shown.bs.modal', function () {
        console.log("techModal loaded :D");
        techtree.reset_colors_for_bar_buttons();
        techtree.refresh_resource_bar();
    });

    /** levelup action points */
    /** update action points only visually as a preview */
    $(document).on('mouseover', '#techModal .progress.ap_spend a.progress-bar', function(e) {
        $(this).prevAll('.progress-bar-info').removeClass('progress-bar-info').addClass('progress-bar-success');
        $(this).removeClass('progress-bar-info').addClass('progress-bar-success');
        $(this).nextAll('.progress-bar-success').removeClass('progress-bar-success').addClass('progress-bar-info');
        techtree.reset_colors_for_bar_buttons();
    });
    /** remove the 'preview' action points */
    $(document).on('mouseout', '#techModal .progress.ap_spend', function(e) {
        $('#techModal .progress a.progress-bar-success').removeClass('progress-bar-success').addClass('progress-bar-info');
        techtree.reset_colors_for_bar_buttons();
    });

    /** status points */
    /** update action points only visually as a preview */
    $(document).on('mouseover', '.techModal .progress.status_points a.bar', function(e) {
        if ($(this).hasClass('progress-bar-info')) {
            $(this).prevAll('.progress-bar-info').removeClass('progress-bar-info').addClass('progress-bar-warning');
            $(this).prevAll('.progress-bar-info').text('');
            $(this).removeClass('progress-bar-info').addClass('progress-bar-warning');
            $(this).text('test');
            $(this).nextAll('.progress-bar-info').text('');
            $(this).nextAll('.progress-bar-warning').removeClass('progress-bar-warning').addClass('progress-bar-info');
        } else {
            $(this).prevAll('.progress-bar-danger').removeClass('progress-bar-danger').addClass('progress-bar-warning');
            $(this).text('');
            $(this).removeClass('progress-bar-danger').addClass('progress-bar-warning');
            $(this).nextAll('.progress-bar-warning').removeClass('progress-bar-warning').addClass('progress-bar-danger');
        }
        techtree.reset_colors_for_bar_buttons();
    });
    /** remove the 'preview' action points */
    $(document).on('mouseout', '.techModal .progress.status_points', function(e) {
        $('.techModal .progress a.progress-bar-danger').removeClass('progress-bar-danger').addClass('progress-bar-warning');
        techtree.reset_colors_for_bar_buttons();
    });

});

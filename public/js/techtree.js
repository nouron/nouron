$(document).ready(function(){
    techtree = {
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
             var srcpos = src.position();
             var trgtpos = trgt.position();
             
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
                         class_ = 'building';
                     } else if ($(this).hasClass( 'research' )) {
                         class_ = 'research';
                     } else if ($(this).hasClass( 'ship' )) {
                         class_ = 'ship';
                     } else {
                         class_ = 'advisor';
                     }
                     data = $(this).html().trim().split('-');
                     techId = parseInt(data[0]);
                     requiredTechId = parseInt(data[1]);
                     req_count = parseInt(data[2]);
                     count = parseInt(data[3]);
                     domSourceElem = $('#tech-' + requiredTechId);
                     domTargetElem = $('#tech-' + techId);
                     if (domSourceElem && domTargetElem) {
                         techtree.draw_requirement(domSourceElem, domTargetElem, class_, req_count, count);
                     }
                 });
             }, 100);
         },
        
         /* little helper to reset colors in action points bar */
        reset_colors_for_bar_buttons: function() {
            $('#techModal .progress a.bar i').parent().css({
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
    
    /* ajax load modal content */
    $('#visualTechtree a').live('click', function(e) {
        e.preventDefault();
        if (!$(this).hasClass('disabled')) {
            tmp = $(this).attr('id').split('|');
            techId = tmp[0].replace('tech-','');
            var url = '/techtree/tech/'+techId;
            if ( tmp.length > 1) {
                var order = tmp[1].split('-');
                url = url + '/' + order[0];
                if (order.length > 1) {
                    url = url + '/' + order[1];
                }
            }
            $('#techModal').load(url, null, function() {
                console.log("techModal loaded :D");
                techtree.reset_colors_for_bar_buttons();
                techtree.refresh_resource_bar();
            });
        }
    });
    
    /* click and drop solution for repositioning techs (only for admin use, temporary solution) */
    $('.grid-cell').click(function(e){
        if ($(this).html()=='') {
            techId = $('#storage-tech-id').html();
            domId = $(this).attr('id').split('-');
            console.log(domId);
            row = domId[1];
            column = domId[2];
            $.getJSON(
                '/techtree/tech/'+techId+'/reposition/'+row+'/'+column,
                function(data) {
                    console.log($('#storage').html());
                    $(this).html($('#storage').html());
                },
                function(data) {
                }
            );
        }
    });
    
    /** levelup action points */
    /** update action points only visually as a preview */
    $('#techModal .progress.ap_spend a.bar').live('mouseover', function(e) {
        $(this).prevAll('.bar-info').removeClass('bar-info').addClass('bar-success');
        $(this).removeClass('bar-info').addClass('bar-success');
        $(this).nextAll('.bar-success').removeClass('bar-success').addClass('bar-info');
        techtree.reset_colors_for_bar_buttons();
    });
    /** remove the 'preview' action points */
    $('#techModal .progress.ap_spend').live('mouseout', function(e) {
        $('#techModal .progress a.bar-success').removeClass('bar-success').addClass('bar-info');
        techtree.reset_colors_for_bar_buttons();
    });
    
    /** status points */
    /** update action points only visually as a preview */
    $('#techModal .progress.status_points a.bar').live('mouseover', function(e) {
        if ($(this).hasClass('bar-info')) {
            $(this).prevAll('.bar-info').removeClass('bar-info').addClass('bar-warning');
            $(this).prevAll('.bar-info').text('');
            $(this).removeClass('bar-info').addClass('bar-warning');
            $(this).text('test');
            $(this).nextAll('.bar-info').text('');
            $(this).nextAll('.bar-warning').removeClass('bar-warning').addClass('bar-info');
        } else {
            $(this).prevAll('.bar-danger').removeClass('bar-danger').addClass('bar-warning');
            $(this).text('');
            $(this).removeClass('bar-danger').addClass('bar-warning');
            $(this).nextAll('.bar-warning').removeClass('bar-warning').addClass('bar-danger');
        }
        techtree.reset_colors_for_bar_buttons();
    });
    /** remove the 'preview' action points */
    $('#techModal .progress.status_points').live('mouseout', function(e) {
        $('#techModal .progress a.bar-danger').removeClass('bar-danger').addClass('bar-warning');
        techtree.reset_colors_for_bar_buttons();
    });
    
});
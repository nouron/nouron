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

        refresh_resource_bar: function() {
            var url = '/resources/json/reloadresourcebar';
            $('#resource-bar').load(url, null, function() {
                console.log("resource bar loaded");
            });
        },

        /**
         * Load modal content from server via AJAX.
         * @param {Element} modalEl  The .techModal DOM element
         * @param {string}  url      URL to fetch (e.g. /techtree/building/25)
         * @param {Function} done    Optional callback after content is injected
         */
        loadModalContent: function(modalEl, url, done) {
            $(modalEl).find('.modal-content').html(
                '<div class="modal-body text-center py-4">' +
                '<div class="spinner-border text-secondary" role="status">' +
                '<span class="visually-hidden">Laden...</span></div></div>'
            );
            $.ajax({
                url: url,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(html) {
                    $(modalEl).find('.modal-dialog').replaceWith(html);
                    /* re-init tooltips inside the freshly loaded modal */
                    $(modalEl).find('[data-bs-toggle="tooltip"]').each(function() {
                        new bootstrap.Tooltip(this);
                    });
                    if (typeof done === 'function') { done(); }
                },
                error: function() {
                    $(modalEl).find('.modal-content').html(
                        '<div class="modal-header"><h5 class="modal-title">Fehler</h5>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
                        '<div class="modal-body">Inhalt konnte nicht geladen werden.</div>'
                    );
                }
            });
        }
    };

    /* ------------------------------------------------------------------ */
    /* Load modal content from the tech button's href when modal opens     */
    /* ------------------------------------------------------------------ */
    $(document).on('show.bs.modal', '.techModal', function(event) {
        var trigger = event.relatedTarget;
        if (!trigger) { return; }
        var url = $(trigger).attr('href');
        if (!url || url === '#') { return; }
        techtree.loadModalContent(this, url);
    });

    /* After modal is shown: refresh resource bar */
    $(document).on('shown.bs.modal', '.techModal', function() {
        techtree.reset_colors_for_bar_buttons();
        techtree.refresh_resource_bar();
    });

    /* ------------------------------------------------------------------ */
    /* Action buttons inside modals: levelup / leveldown / add-AP / repair */
    /* Button id format:  "{type}-{techId}|{order}[-{ap}]"                */
    /* e.g. "building-25|add-3"  "building-25|levelup"                    */
    /* ------------------------------------------------------------------ */
    $(document).on('click', '.techModal [id*="|"]', function(e) {
        e.preventDefault();
        var raw = this.id;                            // e.g. "building-25|add-3"
        var halves = raw.split('|');                  // ["building-25", "add-3"]
        var typeid  = halves[0].split('-');            // ["building", "25"]
        var type    = typeid[0];
        var techId  = typeid[1];
        var orderparts = halves[1].split('-');         // ["add","3"] or ["levelup"]
        var order   = orderparts[0];
        var ap      = orderparts[1] || '';

        var url = '/techtree/' + type + '/' + techId + '/' + order + (ap ? '/' + ap : '');
        var modalEl = $(this).closest('.techModal')[0];

        techtree.loadModalContent(modalEl, url, function() {
            techtree.refresh_resource_bar();
        });
    });

    /* ------------------------------------------------------------------ */
    /* Hover preview: AP spend bar                                         */
    /* ------------------------------------------------------------------ */
    $(document).on('mouseover', '.techModal .progress.ap_spend a.progress-bar', function(e) {
        $(this).prevAll('a.bg-info').removeClass('bg-info').addClass('bg-success');
        $(this).removeClass('bg-info').addClass('bg-success');
        $(this).nextAll('a.bg-success').removeClass('bg-success').addClass('bg-info');
        techtree.reset_colors_for_bar_buttons();
    });
    $(document).on('mouseout', '.techModal .progress.ap_spend', function(e) {
        $(this).find('a.progress-bar.bg-success').removeClass('bg-success').addClass('bg-info');
        techtree.reset_colors_for_bar_buttons();
    });

    /* ------------------------------------------------------------------ */
    /* Hover preview: status points / repair bar                           */
    /* ------------------------------------------------------------------ */
    $(document).on('mouseover', '.techModal .progress.status_points a.progress-bar', function(e) {
        if ($(this).hasClass('bg-danger')) {
            /* hovering a damaged slot — preview repairing up to here */
            $(this).prevAll('a.bg-danger').removeClass('bg-danger').addClass('bg-warning');
            $(this).removeClass('bg-danger').addClass('bg-warning');
            $(this).nextAll('a.bg-warning').not('span').removeClass('bg-warning').addClass('bg-danger');
        } else if ($(this).hasClass('bg-warning')) {
            /* hovering a healthy slot — preview removing status down to here */
            $(this).nextAll('a.bg-warning').removeClass('bg-warning').addClass('bg-danger');
        }
        techtree.reset_colors_for_bar_buttons();
    });
    $(document).on('mouseout', '.techModal .progress.status_points', function(e) {
        /* restore: turn all preview-warning anchors back to danger */
        $(this).find('a.progress-bar.bg-warning').removeClass('bg-warning').addClass('bg-danger');
        techtree.reset_colors_for_bar_buttons();
    });

});

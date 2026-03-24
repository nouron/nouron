$(document).ready(function(){

    var COLORS = {
        building:  '#99d',
        research:  '#9d9',
        ship:      '#cc7',
        personell: '#aaa',
    };

    techtree = {

        // Leader Line instances keyed by target type, for toggle support
        lines: { building: [], research: [], ship: [], personell: [] },

        init: function() {
            // Move each .techdata span's content into the matching grid cell
            $('.techdata').each(function() {
                var key = $(this).attr('id').replace('techsource', 'grid');
                var cell = $('#' + key);
                if (cell.length) {
                    cell.html($(this).html());
                }
            });

            techtree.draw_requirements();

            // Leader Line repositions automatically on scroll; reposition on resize
            $(window).on('resize', function() {
                ['building', 'research', 'ship', 'personell'].forEach(function(type) {
                    techtree.lines[type].forEach(function(l) { l.position(); });
                });
            });
        },

        draw_requirements: function() {
            // Remove any existing lines first
            ['building', 'research', 'ship', 'personell'].forEach(function(type) {
                techtree.lines[type].forEach(function(l) { try { l.remove(); } catch(e) {} });
                techtree.lines[type] = [];
            });

            $('.requirementsdata').each(function() {
                var fromClass, toClass;
                if      ($(this).hasClass('building'))  { fromClass = 'building';  toClass = 'building'; }
                else if ($(this).hasClass('research'))  { fromClass = 'building';  toClass = 'research'; }
                else if ($(this).hasClass('ship'))      { fromClass = 'research';  toClass = 'ship'; }
                else                                    { fromClass = 'building';  toClass = 'personell'; }

                var parts        = $(this).text().trim().split('-');
                var techId       = parseInt(parts[0]);
                var reqTechId    = parseInt(parts[1]);
                var reqLevel     = parseInt(parts[2]);
                var currentLevel = parseInt(parts[3]);
                var fulfilled    = currentLevel >= reqLevel;

                var srcEl  = document.getElementById(fromClass + '-' + reqTechId);
                var trgtEl = document.getElementById(toClass   + '-' + techId);
                if (!srcEl || !trgtEl) { return; }

                var options = {
                    color:          COLORS[toClass],
                    size:           fulfilled ? 2.5 : 1.5,
                    path:           'fluid',
                    startSocket:    'bottom',
                    endSocket:      'top',
                    endPlug:        'arrow3',
                    endPlugSize:    1.5,
                    startSocketGravity: 40,
                    endSocketGravity:   40,
                };

                if (!fulfilled) {
                    options.dash = { len: 6, gap: 4 };
                    options.color = COLORS[toClass].replace(')', ', 0.55)').replace('rgb', 'rgba');
                }

                if (reqLevel > 1) {
                    options.middleLabel = LeaderLine.captionLabel('Lv' + reqLevel, {
                        color: '#999',
                        fontSize: '10px',
                    });
                }

                try {
                    var line = new LeaderLine(srcEl, trgtEl, options);
                    techtree.lines[toClass].push(line);
                } catch (e) {
                    console.warn('LeaderLine:', e);
                }
            });
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
    /* ------------------------------------------------------------------ */
    $(document).on('click', '.techModal [id*="|"]', function(e) {
        e.preventDefault();
        var raw        = this.id;
        var halves     = raw.split('|');
        var typeid     = halves[0].split('-');
        var type       = typeid[0];
        var techId     = typeid[1];
        var orderparts = halves[1].split('-');
        var order      = orderparts[0];
        var ap         = orderparts[1] || '';

        var url     = '/techtree/' + type + '/' + techId + '/' + order + (ap ? '/' + ap : '');
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
            $(this).prevAll('a.bg-danger').removeClass('bg-danger').addClass('bg-warning');
            $(this).removeClass('bg-danger').addClass('bg-warning');
            $(this).nextAll('a.bg-warning').not('span').removeClass('bg-warning').addClass('bg-danger');
        } else if ($(this).hasClass('bg-warning')) {
            $(this).nextAll('a.bg-warning').removeClass('bg-warning').addClass('bg-danger');
        }
        techtree.reset_colors_for_bar_buttons();
    });
    $(document).on('mouseout', '.techModal .progress.status_points', function(e) {
        $(this).find('a.progress-bar.bg-warning').removeClass('bg-warning').addClass('bg-danger');
        techtree.reset_colors_for_bar_buttons();
    });

});

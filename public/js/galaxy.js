// Run once the DOM is ready
$(document).ready(function () {

    var offset = parseInt( $('#system #data_offset').text() );
    var scale = parseInt( $('#system #data_scale').text() );
    var range = parseInt( $('#system #data_range').text() );

    var xMin = parseInt( $('#system #data_xMin').text() );
    var xMax = parseInt( $('#system #data_xMax').text() );
    var yMin = parseInt( $('#system #data_yMin').text() );
    var yMax = parseInt( $('#system #data_yMax').text() );

    freeze = 0;

    function toggleSelect(e) {

        x = xMin + Math.round((e.pageX - $('#system #systemLayer').offset().left - offset) / scale);
        y = yMin + Math.round((e.pageY - $('#system #systemLayer').offset().top - offset) / scale);

        left = offset + ((x-range/2) % (range)) * scale;
        top_ = offset + ((y-range/2) % (range)) * scale;

        field_selector = $('#system #systemLayer #field_selector');
        oldleft = field_selector.left;
        oldtop = field_selector.top;

        if (oldleft != left || oldtop != top_) {
            field_selector.remove();
            $('#system #systemLayer').append('<div id="field_selector" style="top:'+top_+'px; left:'+left+'px; width:'+scale+'px; height:'+scale+'px;"><!-- --></div>');
            $('.parallax-layer').trigger('freeze');
        } else {
            field_selector.remove();
            $('.parallax-layer').trigger('unfreeze');
        }

        return '['+x+','+y+',0]';

//        if ( $('#system #systemLayer #field_selector') )
//        if (freeze == 1) {
//            freeze = 0;
//            $('.parallax-layer').trigger('unfreeze');
//        } else {
//            freeze = 1;
//            $('.parallax-layer').trigger('freeze');
//        }
    }

    /* Klick auf Koloniespot */
    $("#system #systemLayer .spots li").click(function(e) {
        e.preventDefault();
        id = $(this).attr('id');
        cid = parseInt(id.slice(4)); // cut 'cid-'
        $('#system .colonyInfos').hide();

        if (cid >= 0) {
            $('#system #cid-'+cid+'-info').show();
            coords  = $('#system #cid-'+cid+'-info li.coords').text();
        } else {
            // wenn keine Kolonien auf Planet vorhanden, nehme Koordinaten des Planeten
            x = xMin + Math.round(($(this).parent().prev().offset().left - $('#system #systemLayer').offset().left - offset) / scale);
            y = yMin + Math.round(($(this).parent().prev().offset().top - $('#system #systemLayer').offset().top - offset) / scale);
            coords = '['+x+','+y+',0]';
        }

        // set coords into hidden form field
        $('#system form[name=fleetActions] input[name=coords]').val(coords);

        if ( cid >= 0 ) {
            $('#system #colonyInfos').show();
        } else {
            $('#system #colonyInfos').hide();
        }

        if ( $('#system form[name=fleetActions] input[name=fleetId]').val() > 0 ) {
            $('#system #fleetActions').show();
        } else {
            $('#system #fleetActions').hide();
        }

        toggleSelect(e);

        return false; // this line makes it possible to click anywhere else to unselect the object
    });

    /* Klick irgendwo ins System */
    $("#system #systemLayer").click(function(e) {

        $('#system #fleetActions').hide();
        // function makes it possible to click anywhere else to unselect the object
        $('.colonyInfos').hide();
        $('#fleetActions').hide();
        $('#system #systemLayer img').removeClass('active'); // remove 'active' from all objects
        $('#system #systemLayer ul.spots li').removeClass('active'); // remove 'active' from all spots
        $('#system #systemLayer ul.spots').hide();

        coords = toggleSelect(e);

        $('#system form[name=fleetActions] input[name=coords]').val(coords);

        fleetId = $('#system form[name=fleetActions] input[name=fleetId]').val();
        if (fleetId != '' && fleetId>0) {
            $('#system #fleetActions').show();
        }
    });

    $("#system #systemLayer img").mouseenter(function(e) {
        id = $(this).attr('id');
        $('#'+ id + '-spots').show();
    });

    $("#system #systemLayer .tooltip_trigger").mouseleave(function(e) {
        if ( $(this).prev().attr('class') !== 'active' ) {
            $(this).children('ul.spots').hide();
        }
    });

    // show list of colony spots when hovering a system object
    $("#system #systemLayer ul.spots").mouseenter(function(e) {
        $('.tooltip_box').hide(); // hide tooltip-box
        $(this).show();
    });

    $("#system #systemLayer ul.spots li").click(function(e) {
        $('#system #systemLayer img').removeClass('active'); // remove 'active' from all objects
        $('#system #systemLayer ul.spots li').removeClass('active'); // remove 'active' from all spots
        $(this).parent().prev().addClass('active'); // set current img object 'active'
        $(this).addClass('active'); // set current spot 'active'
    });

    // select fleet:
    $("#system ul.fleetList li").click(function(e) {
        currentFleet = $(this);
        $("#system ul.fleetList li").removeClass('active');
        currentFleet.addClass('active');

        if (!$(this).parent().hasClass('foreignFleetList')) {
            // nur für eigene Flotten!!
            $('#system form[name=fleetActions] input[name=fleetId]').val( currentFleet.attr('id').slice(4) ); // cut'fid-'

            coords = $('#system form[name=fleetActions] input[name=coords]').val();
            if (coords != '') {
                $('#system #fleetActions').show();
            }
        }
    });


    $("#system ul.foreignFleetList li").click(function(e) {
        $('#system #fleetActions').hide();
    });

    $("#system #toggleFleetsLayer").click(function(e) {
        $('#system #fleetsLayer').fadeToggle('slow');
    });

    $("#system #toggleGridLayer").click(function(e) {
        $('#system #gridLayer').fadeToggle('slow');
    });

    $("#system #toggleSystemLayer").click(function(e) {
        $('#system #systemLayer').fadeToggle('slow');
    });

    $("form#fleetActions button").click(function(e){

        $("form#fleetActions button").disable();
        $.post(
            "/galaxy/system/addFleetOrder",
            $("form#fleetActions").serialize(),
            function(data) {

                //$("form#fleetActions button").disable('false');

                //data contains the JSON object
                if (data.error) {
                    $('form[name=fleetActions] div.error').text(data.error);
                    $('form[name=fleetActions] div.error').show();
                } else {
                    $('form[name=fleetActions] div.error').hide();
                }

            },
            "html"
        );
        return false;
    });

    /**
     *
     */
    function _get_point(x,y)
    {
        var offset = parseInt($('#data_offset').text());
        var range  = parseInt($('#data_range').text());
        var scale  = parseInt($('#data_scale').text());
        var m = range;
        var r = Math.round(m/2);
        var left = Math.round((( x + r) % m) * scale) + offset + Math.round(scale/2);
        var top  = Math.round((( y + r) % m) * scale) + offset + Math.round(scale/2);
        return [left, top];
    }

    /**
     * @param array from
     * @param array to
     */
    function _draw_line(from, to) {
        var group = makeSVG('g');
        group.appendChild(makeSVG('line', {x1:from[0], y1:from[1], x2:to[0], y2:to[1], stroke: 'white'}));
        document.getElementById('fleetsLayer-svg').appendChild(group);
    }

    /**
     *
     */
    function draw_fleet_order_waypoint(fleetorder)
    {
        var coords = jQuery.parseJSON(fleetorder.text());
        var point = _get_point(coords[0],coords[1]);

        var group = makeSVG('g', {title: coords});
        group.appendChild(makeSVG('circle', {cx: point[0], cy:point[1], r:2, fill: 'white'}));
        document.getElementById('fleetsLayer-svg').appendChild(group);

        text = makeSVG('text', {x:point[0]+5, y:point[1]+5});
        text.appendChild(makeSVG('tspan', {'font-family':'Verdana', 'font-size': '10px', stroke: '#999'}, fleetorder.text()));
        group.appendChild(text);

    }

    /**
     *
     */
    function draw_fleet_order_path(ordersForFleet) {
        for (var tick in ordersForFleet) {

            var coords = ordersForFleet[tick];
            console.log(coords);
            nexttick = parseInt(tick) + 1;

            if (nexttick in ordersForFleet) {
                var nextcoords = ordersForFleet[nexttick];
                var point = _get_point(coords[0],coords[1]);
                var nextpoint = _get_point(nextcoords[0],nextcoords[1], 'test');
                _draw_line(point, nextpoint);
            }

        }
    }

    var fleetorders = {}
    $('#fleetsLayer .fleetorder').each(function( index ) {
        var key = $(this).attr('id').split('-');
        var fleetId = parseInt(key[0]);
        var tick = parseInt(key[1]);
        if (!(fleetId in fleetorders)){
            fleetorders[fleetId] = {}
        }
        var coords = jQuery.parseJSON($(this).text());
        if ( (xMin <= coords[0]) && (coords[0] <= xMax) &&
             (yMin <= coords[1]) && (coords[1] <= yMax)) {
            fleetorders[fleetId][tick] = coords;
            draw_fleet_order_waypoint($(this));
        }
    });

    for (var i in fleetorders) {
        console.log(fleetorders[i]);
        draw_fleet_order_path(fleetorders[i]);
    }


});

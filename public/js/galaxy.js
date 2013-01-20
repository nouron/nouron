// Run once the DOM is ready
$(document).ready(function () {
    //jQuery('#parallax').parallax();
    
    // Declare parallax on layers
    jQuery('.parallax-layer').parallax({
        mouseport: "#parallax"
    });
    
    offset = parseInt( $('#system #data_offset').text() );
    scale = parseInt( $('#system #data_scale').text() );
    range = parseInt( $('#system #data_range').text() );
    
    xMin = parseInt( $('#system #data_xMin').text() );
    yMin = parseInt( $('#system #data_yMin').text() );
    
    freeze = 0;
    
    function toggleSelect() {
        $('#system #systemLayer #field_selector').remove();
        if (freeze == 1) {
            freeze = 0;
            $('.parallax-layer').trigger('unfreeze');
        } else {
            freeze = 1;
            $('.parallax-layer').trigger('freeze');
        }
    }
    
    /* Klick auf Koloniespot */
    $("#system #systemLayer img + ul.spots li").click(function(e) {
        e.preventDefault();
        id = $(this).attr('id').slice(4); // cut 'cid-'
        $('#system .colonyInfos').hide();
        
        if (id > 0) {
            $('#system #cid-'+id+'-info').show();
            coords  = $('#system #cid-'+id+'-info li.coords').text();
            // set coords into hidden form field
            $('#system form[name=fleetActions] input[name=coords]').val(coords);
        } else {
            // wenn keine Kolonien auf Planet vorhanden, nehme Koordinaten des Planeten    
            
            x = xMin + Math.round(($(this).parent().prev().offset().left - $('#system #systemLayer').offset().left - offset) / scale);
            y = yMin + Math.round(($(this).parent().prev().offset().top - $('#system #systemLayer').offset().top - offset) / scale);
            coords = '['+x+','+y+',0]';
            $('#system form[name=fleetActions] input[name=coords]').val(coords);
        }
        
        if ( $('#system form[name=fleetActions] input[name=fleetId]').val() > 0 ) {
            $('#system #fleetActions').show();
        }
        
        toggleSelect();
        
        return false; // this line makes it possible to click anywhere else to unselect the object
    });
    
    /* Klick irgendwo ins System */
    $("#system #systemLayer").click(function(e) {
        
        toggleSelect();
            
        $('#system #fleetActions').hide();
        // function makes it possible to click anywhere else to unselect the object
        $('.colonyInfos').hide();
        $('#fleetActions').hide();
        $('#system #systemLayer img').removeClass('active'); // remove 'active' from all objects
        $('#system #systemLayer ul.spots li').removeClass('active'); // remove 'active' from all spots
        $('#system #systemLayer ul.spots').hide();
        
        x = xMin + Math.round((e.pageX - $('#system #systemLayer').offset().left - offset) / scale);
        y = yMin + Math.round((e.pageY - $('#system #systemLayer').offset().top - offset) / scale);
        
        console.log($('#system #systemLayer').offset().left + ", " + $('#system #systemLayer').offset().top);
        console.log(offset + " + (" + x +" %"+range+"*"+scale);
        
        left = offset + (x % range) * scale;
        top_ = offset + (y % range) * scale;
        
        $('#system #systemLayer #field_selector').remove();
        $('#system #systemLayer').append('<div id="field_selector" style="top:'+top_+'px; left:'+left+'px; width:'+scale+'px; height:'+scale+'px;"><!-- --></div>')
        
        coords  = '['+x+','+y+',0]';
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
                    console.log(data);
                }
                
            },
            "html"
        );
        return false;
    });
});
/**
 * 
 */
 function makeSVG(tag, attribs, value)
 {
     if (attribs == null) {
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

$(document).ready(function(){

    // Techtree (Show/Hide Technologies)
    $('#toggleBuildings').toggle(function() {
        $('.building').fadeOut('slow');
        $('.line_building').fadeOut('slow');
    }, function() {
        $('.building').fadeIn('slow');
        $('.line_building').fadeIn('slow');
    });
    
    $('#toggleResearches').toggle(function() {
        $('.research').fadeOut('slow');
        $('.line_research').fadeOut('slow');
        $('.category_ships').fadeOut('slow');
    }, function() {
        $('.research').fadeIn('slow');
        $('.line_research').fadeIn('slow');
    });
    
    $('#toggleShips').toggle(function() {
        $('.ship').fadeOut('slow');
        $('.line_ship').fadeOut('slow');
        $('.category_ships').fadeOut('slow');
    }, function() {
        $('.ship').fadeIn('slow');
        $('.line_ship').fadeIn('slow');
        $('.category_ships').fadeIn('slow');
    });
    
    $('#toggleAdvisors').toggle(function() {
        $('.advisor').fadeOut('slow');
        $('.line_advisor').fadeOut('slow');
        $('.category_crew').fadeOut('slow');
    }, function() {
        $('.advisor').fadeIn('slow');
        $('.line_advisor').fadeIn('slow');
        $('.category_crew').fadeIn('slow');
    });
    
    $('#toggleFullTechtree').toggle(function(e) {
        e.preventDefault();
        $('#techtree .na').fadeIn('slow');
    }, function(e) {
        e.preventDefault();
        $('#techtree .na').fadeOut('slow');
    });

//     /**
//      * 
//      * @param object src A jquery object which serves as source point for the requirment line
//      * @param object trgt A jquery object which serves as target point for the requirment line
//      * @param numeric count Required tech level to fullfill the requirement
//      * @param bool fullfilled OPTIONAL Is requirement fullfilled? (default = false)
//      */
//     function draw_requirement(src, trgt, required_tech_count, tech_count)
//     {
//         fullfilled = false;
//         if (tech_count && tech_count >= required_tech_count) {
//             fullfilled = true;
//         }
//       
//         var srcid = src.parent().attr('id').replace('tech-','');
//         var trgtid = trgt.parent().attr('id').replace('tech-','');
//       
//         source_grid_xyz  = srcid.split('-');
//         target_grid_xyz = trgtid.split('-');
//       
//         if (source_grid_xyz[0] != target_grid_xyz[0]) {
//             /*console.log('Requirements between techs in different stages can not be drawn! (from '+srcid+' to '+trgtid+')');*/
//             return false;
//         }
//       
//         var srcpos = src.position();
//         var trgtpos = trgt.position();
//       
//         if (srcpos.left <= trgtpos.left) {
//             left = Math.round(srcpos.left + src.width());
//             right = Math.round(trgtpos.left);
//         } else {
//             left = Math.round(trgtpos.left + trgt.width());
//             right = Math.round(srcpos.left);
//         }
//
//         xa = left;
//         xd = right;
//         
//         grid_x_diff = Math.abs(source_grid_xyz[2] - target_grid_xyz[2]);
//         grid_x_mod = Math.abs(source_grid_xyz[1] - target_grid_xyz[1]); /* Modifier depend on grid_y value!! */
//         
//         if (grid_x_diff <=1 ) {
//             /* there is only small space between two columns */
//             xm = xb = xc = Math.round( (right + left) / 2 ) - 3 * grid_x_diff;
//         } else {
//             xm = xb = xc = Math.round( (right + left) / 2 ) - 12 * grid_x_mod;
//         }
//
//         padding = 5; // 4px padding +1px border
//         if (srcpos.top > trgtpos.top) {
//             ya = yb = Math.round( (srcpos.top  + ((src.height() + 2*padding)  / 2) - 3*(source_grid_xyz[1] - target_grid_xyz[1])) );
//             yc = yd = Math.round( (trgtpos.top + ((trgt.height() + 2*padding) / 2) + 4*(source_grid_xyz[1] - target_grid_xyz[1])) );
//         } else {
//             yc = yd = Math.round( (trgtpos.top  + ((src.height() + 2*padding)  / 2) - 3*(target_grid_xyz[1] - source_grid_xyz[1])) );
//             ya = yb = Math.round( (srcpos.top + ((trgt.height() + 2*padding) / 2) + 4*(target_grid_xyz[1] - source_grid_xyz[1])) );
//         }
//
//         ym = Math.round( (yb+yc) / 2 );
//       
//         stroke_color = fullfilled ? '#666' : '#aaa';
//       
//         var group = makeSVG('g', {title: src.attr('id') + ' to ' + trgt.attr('id')});
//         group.appendChild(makeSVG('line', {x1:xa, y1:ya, x2:xb, y2:yb, stroke: stroke_color}));
//         group.appendChild(makeSVG('line', {x1:xb, y1:yb, x2:xc, y2:yc, stroke: stroke_color}));
//         group.appendChild(makeSVG('line', {x1:xc, y1:yc, x2:xd, y2:yd, stroke: stroke_color}));
//         group.appendChild(makeSVG('rect', {x:xm-10, y:ym-10, height:'20', width:'20', fill: 'white'}));
//         text = makeSVG('text', {x:xm-5, y:ym+5});
//         text.appendChild(makeSVG('tspan', {'font-family':'Courier', 'font-size': '10pt', stroke: stroke_color}, count));
//         group.appendChild(text);
//
//         stage = source_grid_xyz[0];
//         document.getElementById('grid-svg').appendChild(group);
//     }
     
     /**
      * 
      * @param object src A jquery object which serves as source point for the requirment line
      * @param object trgt A jquery object which serves as target point for the requirment line
      * @param numeric count Required tech level to fullfill the requirement
      * @param bool fullfilled OPTIONAL Is requirement fullfilled? (default = false)
      */
     function draw_requirement(src, trgt, required_tech_count, tech_count)
     {
         fullfilled = false;
//         if (tech_count && tech_count >= required_tech_count) {
//             fullfilled = true;
//         }
       
         var srcid = src.parent().attr('id').replace('grid-','');
         var trgtid = trgt.parent().attr('id').replace('grid-','');
         var srcpos = src.position();
         var trgtpos = trgt.position();
         
         source_grid_xy  = srcid.split('-');
         target_grid_xy  = trgtid.split('-');
         
         source_y = source_grid_xy[0];
         source_x = source_grid_xy[1];
         source_left = srcpos.left;
         source_top = srcpos.top;
         source_width  = src.width();
         source_height = src.height();
         
         target_y = target_grid_xy[0];
         target_x = target_grid_xy[1];
         target_left = trgtpos.left;
         target_top = trgtpos.top;
         target_width  = trgt.width();
         target_height = trgt.height();
       
         ya = source_top + source_height + 10;
         yb = ya + 20;
         yc = target_top - target_height;
         yd = yc + 10;
         ye = target_top;

         xa = source_left + Math.round(source_width/2);
         xe = target_left + Math.round(target_width/2);
         xb = xc = xd = xe;
         if (source_y == target_y-1) {
             xb = xc = xd = xe;
         } else if (source_x < target_x) {
             xb = xc = target_left - 5;
         } else {
             xb = xc = target_left + target_width - 20;
         }
         
         stroke_color = fullfilled ? '#666' : '#aaa';
       
         var group = makeSVG('g', {title: src.attr('id') + ' to ' + trgt.attr('id')});

         diff_y = Math.abs(source_y - target_y)*3;
         diff_x = Math.abs(source_x - target_x)*5;
         if (source_x < target_x) {
             xa = xa + diff_x;
             xb = xc = xc - diff_x;
             xd = xe = xe - diff_x;
         } else if (source_x > target_x) {
             xa = xa - diff_x;
             xb = xc = xc + diff_x;
             xd = xe = xe + diff_x;
         }
         
         if (source_y < target_y && source_x == target_x) {
             xa = xa + diff_y;
             xb = xc = xc + diff_y;
             xd = xe = xe + diff_y;
         }
         
         group.appendChild(makeSVG('line', {x1:xa, y1:ya, x2:xb, y2:yb, stroke: stroke_color}));
         group.appendChild(makeSVG('line', {x1:xb, y1:yb, x2:xc, y2:yc, stroke: stroke_color}));
         group.appendChild(makeSVG('line', {x1:xc, y1:yc, x2:xd, y2:yd, stroke: stroke_color}));
         group.appendChild(makeSVG('line', {x1:xd, y1:yd, x2:xe, y2:ye, stroke: stroke_color}));
         
         d1 = String(xe-4) + ',' + String(ye-8);
         d2 = String(xe)   + ',' + String(ye);
         d3 = String(xe+4) + ',' + String(ye-8);
         d = d1 + ' ' + d2 + ' ' + d3;
         
         group.appendChild(makeSVG('polygon', {points: d, fill: stroke_color}));

         //group.appendChild(makeSVG('line', {x1:xc, y1:yc, x2:xd, y2:yd, stroke: stroke_color}));
         //group.appendChild(makeSVG('rect', {x:xm-10, y:ym-10, height:'20', width:'20', fill: 'white'}));
         //text = makeSVG('text', {x:xm-5, y:ym+5});
         //text.appendChild(makeSVG('tspan', {'font-family':'Courier', 'font-size': '10pt', stroke: stroke_color}, count));
         //group.appendChild(text);

         document.getElementById('grid-svg').appendChild(group);
     }

     function draw_requirements() {
         setTimeout(function() {
             $('svg.span12 *').remove();
             /* Take requirements data to draw the lines into techtree */
             $('.requirementsdata').each(function() {
                 data = $(this).html().trim().split('-');
                 techId = data[0];
                 requiredTechId = data[1];
                 count = data[2];
                 fullfilled = data[3];
                 domSourceElem = $('#tech-' + requiredTechId);
                 domTargetElem = $('#tech-' + techId);
                 if (domSourceElem && domTargetElem) {
                     draw_requirement(domSourceElem, domTargetElem, count, fullfilled);
                 }
             });
         }, 500);
     }
    
//    /* stop automatic sliding of carousel: */
//    $('#visualTechtree').carousel('pause');
    
    /* enable tooltips */
    $('[rel=tooltip]').tooltip();
    
    //$('.parallax-layer').parallax();

    /* moving technology divs to correct spots */
    $('.techdata').each(function(index) {
        var key = $(this).attr('id');
        var html = $(this).html();
        key = key.replace('techsource','grid');
        if ($("#"+key)){
            $("#"+key).html(html);
        } else {

        }
    });

    draw_requirements();
    
    $('#visualTechtree a.btn').click(function(e){
        techId = $(this).attr('id').replace('tech-','');
        if ($('#storage').html()=='') {
            $('#storage-tech-id').html(techId);
            $('#storage').html($(this).html());
            $(this).remove();
        } else {
            $('#storage-tech-id').html('');
            $('#storage').html('');
        }
//        $('#techModal').load('http://dev.nouron.de/techtree/json/getModalHtmlForTechnology/'+techId);
    });
    
    $('.grid-cell').click(function(e){
        if ($(this).html()=='') {
            techId = $('#storage-tech-id').html();
            domId = $(this).attr('id').split('-');
            console.log(domId);
            row = domId[1];
            column = domId[2];
            $.getJSON(
                '/techtree/technology/set/'+techId+'/'+row+'/'+column,
                function(data) {
                    console.log($('#storage').html());
                    $(this).html($('#storage').html());
                },
                function(data) {
                }
            );
        }
    });
    
    $('.modal-footer a').click(function(e) {
        e.preventDefault();
        $('#techModal').load('http://dev.nouron.de/techtree/json/getModalHtmlForTechnology/'+techId);
    });
    

});
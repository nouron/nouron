$(document).ready(function(){
     
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
         yb = source_top + source_height+10;
         yc = target_top - target_height;
         yd = target_top-20;
         ye = target_top;

         xa = source_left + Math.round(source_width/2);
         xe = target_left + Math.round(target_width/2);

         unique_positioner = (source_x*2+target_x*2+source_y+target_y);
         
         if (target_x <= source_x ) {
             xb = xc = target_left + target_width  + unique_positioner - 50;
         } else {
             xb = xc = target_left - unique_positioner  - 50;
         }

         
         stroke_color = fullfilled ? '#666' : '#aaa';
       
         var group = makeSVG('g', {title: src.attr('id') + ' to ' + trgt.attr('id')});
         
         x_modifier = Math.round(source_width / 6) - 3;
         //xa = xb = xc = source_left + target_x * x_modifier;
         xd = xe = target_left + source_x * x_modifier + unique_positioner;

         if (source_y == target_y-1) {
             if (source_x != target_x) {
                 group.appendChild(makeSVG('line', {x1:xa, y1:ya, x2:xe, y2:yb, stroke: stroke_color}));
                 group.appendChild(makeSVG('line', {x1:xe, y1:yb, x2:xe, y2:ye, stroke: stroke_color}));
             } else {
                 group.appendChild(makeSVG('line', {x1:xe, y1:ya, x2:xe, y2:ye, stroke: stroke_color}));
             }
         } else {
             group.appendChild(makeSVG('line', {x1:xa, y1:ya, x2:xb, y2:yb, stroke: stroke_color}));
             group.appendChild(makeSVG('line', {x1:xb, y1:yb, x2:xc, y2:yc, stroke: stroke_color}));
             group.appendChild(makeSVG('line', {x1:xc, y1:yc, x2:xd, y2:yd, stroke: stroke_color}));
             group.appendChild(makeSVG('line', {x1:xd, y1:yd, x2:xe, y2:ye, stroke: stroke_color}));
         }
         
         d1 = String(xe-4) + ',' + String(ye-8);
         d2 = String(xe)   + ',' + String(ye);
         d3 = String(xe+4) + ',' + String(ye-8);
         d = d1 + ' ' + d2 + ' ' + d3;
         
         group.appendChild(makeSVG('polygon', {points: d, fill: stroke_color}));

         text = makeSVG('text', {x:xe+5, y:ye-8});
         text.appendChild(makeSVG('tspan', {'font-family':'Sans-serif', 'font-size': '11px', stroke: stroke_color}, count));
         group.appendChild(text);

         test = makeSVG('rect', {x:0,y:0,'height':10, 'width':10, 'stroke':"black"});
         group.appendChild(test);
         test = makeSVG('rect', {x:300,y:0,'height':10, 'width':10, 'stroke':"black"});
         group.appendChild(test);
         test = makeSVG('rect', {x:600,y:0,'height':10, 'width':10, 'stroke':"black"});
         group.appendChild(test);
         test = makeSVG('rect', {x:900,y:0,'height':10, 'width':10, 'stroke':"black"});
         group.appendChild(test);
         
         document.getElementById('grid-svg').appendChild(group);
     }

     function draw_requirements() {
         setTimeout(function() {
             $('svg *').remove();
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
         }, 300);
     }
     
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
    $(window).resize(function() {
        draw_requirements();
    });
    
    $('#visualTechtree a.btn').click(function(e){
        e.preventDefault();
        techId = $(this).attr('id').replace('tech-','');
//        if ($('#storage').html()=='') {
//            $('#storage-tech-id').html(techId);
//            $('#storage').html($(this).html());
//            $(this).remove();
//        } else {
//            $('#storage-tech-id').html('');
//            $('#storage').html('');
//        }
        $('#techModal').load('/techtree/tech/'+techId, null, function(){console.log(":D");});
    });
    
    $('.modal-footer a').live('click', function(e) {
        e.preventDefault();
        $.getJSON(
            $(this).attr('href'),
            function(data) {
                console.log('ok');
            },
            function(data) {
                console.log('error');
            }
        );
    });
    
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
    

});
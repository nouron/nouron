$(document).ready(function(){
     
     /**
      * 
      * @param object src A jquery object which serves as source point for the requirment line
      * @param object trgt A jquery object which serves as target point for the requirment line
      * @param numeric count Required tech level to fullfill the requirement
      * @param bool fullfilled OPTIONAL Is requirement fullfilled? (default = false)
      */
     function draw_requirement(src, trgt, type, required_tech_count, tech_count)
     {
         fullfilled = false;
         if (tech_count && tech_count >= required_tech_count) {
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
         yb = source_top + source_height+10;
         yc = target_top - target_height;
         yd = target_top-20;
         ye = target_top;

         xa = source_left + Math.round(source_width/6) * target_x;
         xe = target_left + Math.round(target_width/2);

         unique_positioner = (source_x*2+target_x*2+source_y+target_y);
         
         if (target_x <= source_x ) {
             xb = xc = target_left + target_width  + unique_positioner - 50;
         } else {
             xb = xc = target_left - unique_positioner  - 50;
         }

         switch (type) {
             case 'building': stroke_color = fullfilled ? '#669' : '#99f';
                              break;
             case 'research': stroke_color = fullfilled ? '#696' : '#9f9';
                              break;
             case 'ship': stroke_color = fullfilled ? '#bb5' : '#dd7';
                          break;
             default: stroke_color = fullfilled ? '#666' : '#aaa';
                      break;
         }

         var group = makeSVG('g', {title: src.attr('id') + ' to ' + trgt.attr('id')});
         
         x_modifier = Math.round(source_width / 6) - 3;
         //xa = xb = xc = source_left + target_x * x_modifier;
         xd = xe = target_left + source_x * x_modifier + unique_positioner;

         if (source_y == target_y-1) {
             if (source_x != target_x) {
                 d1 = xa + ',' + ya;
                 d2 = xe + ',' + yb;
                 d3 = xe + ',' + ye;
                 d = d1 + ' ' + d2 + ' ' + d3;
                 group.appendChild(makeSVG('polyline', {points: d, stroke: stroke_color, 'fill-opacity':'0', 'class':type}));
             } else {
                 group.appendChild(makeSVG('line', {x1:xe, y1:ya, x2:xe, y2:ye, stroke: stroke_color, 'fill-opacity':'0', 'class':type}));
             }
         } else {
             d1 = xa + ',' + ya;
             d2 = xb + ',' + yb;
             d3 = xc + ',' + yc;
             d4 = xd + ',' + yd;
             d5 = xe + ',' + ye;
             d = d1 + ' ' + d2 + ' ' + d3 + ' ' + d4 + ' ' + d5;
             group.appendChild(makeSVG('polyline', {points: d, stroke: stroke_color, 'fill-opacity':'0', 'class':type}));
         }
         
         d1 = String(xe-4) + ',' + String(ye-8);
         d2 = String(xe)   + ',' + String(ye);
         d3 = String(xe+4) + ',' + String(ye-8);
         d = d1 + ' ' + d2 + ' ' + d3;
         
         group.appendChild(makeSVG('polyline', {points: d, fill: stroke_color, 'class':type}));

         text = makeSVG('text', {x:xe+5, y:ye-8,'font-size': '12px', fill: '#666', 'class':type}, required_tech_count);
         group.appendChild(text);

//         test = makeSVG('rect', {x:0,y:0,'height':10, 'width':10, 'stroke':"black"});
//         group.appendChild(test);
//         test = makeSVG('rect', {x:300,y:0,'height':10, 'width':10, 'stroke':"black"});
//         group.appendChild(test);
//         test = makeSVG('rect', {x:600,y:0,'height':10, 'width':10, 'stroke':"black"});
//         group.appendChild(test);
//         test = makeSVG('rect', {x:900,y:0,'height':10, 'width':10, 'stroke':"black"});
//         group.appendChild(test);
         
         document.getElementById('grid-svg').appendChild(group);
     }

     function draw_requirements() {
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
                 }
                 data = $(this).html().trim().split('-');
                 techId = data[0];
                 requiredTechId = data[1];
                 req_count = data[2];
                 count = data[3];
                 domSourceElem = $('#tech-' + requiredTechId);
                 domTargetElem = $('#tech-' + techId);
                 if (domSourceElem && domTargetElem) {
                     draw_requirement(domSourceElem, domTargetElem, class_, req_count, count);
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
        $('#techModal').load('/techtree/tech/'+techId, null, function(){console.log("techModal loaded :D");});
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
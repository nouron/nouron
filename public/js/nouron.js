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
    }, function() {
        $('.research').fadeIn('slow');
        $('.line_research').fadeIn('slow');
    });
    
    $('#toggleShips').toggle(function() {
        $('.ship').fadeOut('slow');
        $('.line_ship').fadeOut('slow');
    }, function() {
        $('.ship').fadeIn('slow');
        $('.line_ship').fadeIn('slow');
    });
    
    $('#toggleAdvisors').toggle(function() {
        $('.advisor').fadeOut('slow');
        $('.line_advisor').fadeOut('slow');
    }, function() {
        $('.advisor').fadeIn('slow');
        $('.line_advisor').fadeIn('slow');
    });
    
    $('#toggleFullTechtree').toggle(function(e) {
        e.preventDefault();
        $('#techtree .na').fadeIn('slow');
    }, function(e) {
        e.preventDefault();
        $('#techtree .na').fadeOut('slow');
    });
    
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
         if (tech_count && tech_count >= required_tech_count) {
             fullfilled = true;
         }
       
         var srcid = src.parent().attr('id').replace('tech-','');
         var trgtid = trgt.parent().attr('id').replace('tech-','');
       
         src_grid_xyz  = srcid.split('-');
         trgt_grid_xyz = trgtid.split('-');
       
         if (src_grid_xyz[0] != trgt_grid_xyz[0]) {
             /*console.log('Requirements between techs in different stages can not be drawn! (from '+srcid+' to '+trgtid+')');*/
             return false;
         }
       
         var srcpos = src.position();
         var trgtpos = trgt.position();
       
         if (srcpos.left <= trgtpos.left) {
             left = Math.round(srcpos.left + src.width());
             right = Math.round(trgtpos.left);
         } else {
             left = Math.round(trgtpos.left + trgt.width());
             right = Math.round(srcpos.left);
         }

         xa = left;
         xd = right;
         
         grid_x_diff = Math.abs(src_grid_xyz[2] - trgt_grid_xyz[2]);
         grid_x_mod = Math.abs(src_grid_xyz[1] - trgt_grid_xyz[1]); /* Modifier depend on grid_y value!! */
         
         if (grid_x_diff <=1 ) {
             /* there is only small space between two columns */
             xm = xb = xc = Math.round( (right + left) / 2 ) - 3 * grid_x_diff;
         } else {
             xm = xb = xc = Math.round( (right + left) / 2 ) - 12 * grid_x_mod;
         }
         
         padding = 5; // 4px padding +1px border
         ya = yb = Math.round( (srcpos.top  + ((src.height() + 2*padding)  / 2) - 3*(src_grid_xyz[1] - trgt_grid_xyz[1])) );
         yc = yd = Math.round( (trgtpos.top + ((trgt.height() + 2*padding) / 2) + 4*(src_grid_xyz[1] - trgt_grid_xyz[1])) );

         ym = Math.round( (yb+yc) / 2 );
       
         stroke_color = fullfilled ? '#666' : '#aaa';
       
         var group = makeSVG('g', {title: src.attr('id') + ' to ' + trgt.attr('id')});
         group.appendChild(makeSVG('line', {x1:xa, y1:ya, x2:xb, y2:yb, stroke: stroke_color}));
         group.appendChild(makeSVG('line', {x1:xb, y1:yb, x2:xc, y2:yc, stroke: stroke_color}));
         group.appendChild(makeSVG('line', {x1:xc, y1:yc, x2:xd, y2:yd, stroke: stroke_color}));
         group.appendChild(makeSVG('rect', {x:xm-10, y:ym-10, height:'20', width:'20', fill: 'white'}));
         text = makeSVG('text', {x:xm-5, y:ym+5});
         text.appendChild(makeSVG('tspan', {'font-family':'Courier', 'font-size': '10pt', stroke: stroke_color}, count));
         group.appendChild(text);

         stage = src_grid_xyz[0];
         document.getElementById('stage-'+stage+'-svg').appendChild(group);
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
    
    /* stop automatic sliding of carousel: */
    $('#visualTechtree').carousel('pause');
    
    /* enable tooltips */
    $('[rel=tooltip]').tooltip();
    
    //$('.parallax-layer').parallax();

    /* moving technology divs to correct spots */
    $('.techdata').each(function(index) {
        var key = $(this).attr('id');
        var html = $(this).html();
        key = key.replace('source','');
        $("#"+key).html(html);
    });

    $('.carousel-control.left').hide();
    
    $('.carousel-control.left').click(function(e){
        stage_id = $('#visualTechtree .item.active').attr('id');
        stage    = parseInt(stage_id.replace('stage-','')) - 1;
        $('.carousel-control.right').show();
        if ( stage < 1 ) {
            $(this).hide();
        }
        draw_requirements();
    });

    $('.carousel-control.right').click(function(e){
        stage_id = $('#visualTechtree .item.active').attr('id');
        stage    = parseInt(stage_id.replace('stage-','')) + 1;
        $('.carousel-control.left').show();
        if ( stage > 4 ) {
            $(this).hide();
        }
        draw_requirements();
    });

    $('#visualTechtree a.btn').click(function(e){
        techId = $(this).attr('id').replace('tech-','');
        $('#techModal').load('http://dev.nouron.de/techtree/json/getModalHtmlForTechnology/'+techId);
    });
  
    draw_requirements();
    
    
    $('.modal-footer a').click(function(e) {
        e.preventDefault();
        $('#techModal').load('http://dev.nouron.de/techtree/json/getModalHtmlForTechnology/'+techId);
    });
    
});
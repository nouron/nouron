$(document).ready(function(){
  $('#visualTechtree').carousel({
     interval:1500,
     pause: true
    });
  $('#visualTechtree').carousel('pause');
  $('[rel=tooltip]').tooltip();

  /** moving technology divs to correct spots */
  $('.techdata').each(function(index) {
      var key = $(this).attr('id');
      var html = $(this).html();
      key = key.replace('source','');
      $("#"+key).html(html);
  });
  
  
  $('.carousel-control.left').hide();
 
  $('.carousel-control.left').click(function(e){
    $(this).hide();
    $('.carousel-control.right').show();
  });

  $('.carousel-control.right').click(function(e){
    $(this).hide();
    $('.carousel-control.left').show();
  });

  $('#visualTechtree a').click(function(e){
      techId = $(this).attr('id').replace('tech-','');
      $('#techModal .modal-body .span2').load('http://dev.nouron.de/techtree/json/getCostsForTechnology/'+techId);
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
//  
//  w = $('#stage-0').width();
//  h = $('#stage-0').height();
//  
//  $('svg#stage-0-svg').attr('viewbox', '0 0 '+w+' '+h);
  
  /**
   * 
   * @param object src A jquery object which serves as source point for the requirment line
   * @param object trgt A jquery object which serves as target point for the requirment line
   * @param numeric count Required tech level to fullfill the requirement
   * @param bool fullfilled OPTIONAL Is requirement fullfilled? (default = false)
   */
  function draw_requirement(src, trgt, count, fullfilled)
  {
      if (fullfilled == null) {
          fullfilled = false;
      }
      
      var srcid = src.parent().attr('id').replace('tech-','');
      var trgtid = trgt.parent().attr('id').replace('tech-','');
            
      srcxyz  = srcid.split('-');
      trgtxyz = trgtid.split('-');
      
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
      xm = xb = xc = Math.round( (right + left) / 2 ) - 4*(srcxyz[1] - trgtxyz[1]);
      xd = right;
      
      ya = yb = Math.round(( (srcpos.top  + src.height())  / 2) - 4*(srcxyz[2] - trgtxyz[2])) * 1.75;
      yc = yd = Math.round(( (trgtpos.top + trgt.height()) / 2) - 4*(srcxyz[2] - trgtxyz[2])) * 1.75;
      ym = Math.round( (yb+yc) / 2 ) * 2;
      
      stroke_color = fullfilled ? '#222' : '#666';
      
      var group = makeSVG('g', {title: src.attr('id') + ' to ' + trgt.attr('id')});
      group.appendChild(makeSVG('line', {x1:xa, y1:ya, x2:xb, y2:yb, stroke: stroke_color}));
      group.appendChild(makeSVG('line', {x1:xb, y1:yb, x2:xc, y2:yc, stroke: stroke_color}));
      group.appendChild(makeSVG('line', {x1:xc, y1:yc, x2:xd, y2:yd, stroke: stroke_color}));
      group.appendChild(makeSVG('rect', {x:xm-10, y:ym-10, height:'30', width:'30', fill: 'white'}));
      text = makeSVG('text', {x:xm, y:ym+10});
      text.appendChild(makeSVG('tspan', {'font-family':'Verdana', 'font-size': '10pt', stroke: stroke_color}, count));
      group.appendChild(text);
      document.getElementById('stage-0-svg').appendChild(group);
  }
  
  /* draw some svg for debugging */
//  frame = $('#stage-0');
//  framepos = frame.position();
//  max_x = framepos.left + frame.width() - 10;
//  max_y = framepos.top + frame.height() - 10;
//  rect = makeSVG('rect', {x:max_x, y:max_y, height:'10', width:'10', fill: 'black'});
//
//  rect2 = makeSVG('rect', {x:0, y:0, height:'100%', width:'100%', stroke: 'black', 'fill-opacity':0});
//  document.getElementById('stage-0-svg').appendChild(rect);
//  document.getElementById('stage-0-svg').appendChild(rect2);
  /* Take requirements data to draw the lines into techtree */
  $('.requirementsdata').each(function() {
      data = $(this).html().trim().split('-');
      techId = data[0];
      requiredTechId = data[1];
      count = data[2];
      domSourceElem = $('#tech-' + requiredTechId);
      domTargetElem = $('#tech-' + techId);
      if (domSourceElem && domTargetElem) {
          draw_requirement(domSourceElem, domTargetElem, count);
      }
  });
});
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

    /* enable tooltips */
    $('[rel=tooltip]').tooltip();
    
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
});
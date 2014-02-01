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
    $('#toggleBuildings').on('click', function(e){
        e.preventDefault();
        if ($('.building').css('display') != 'none') {
            $('.building').fadeOut('slow');
            $('.line_building').fadeOut('slow');
        } else {
            $('.building').fadeIn('slow');
            $('.line_building').fadeIn('slow');
        }
    });

    $('#toggleResearches').on('click', function(e) {
        e.preventDefault();
        if ($('.research').css('display') != 'none') {
            $('.research').fadeOut('slow');
            $('.line_research').fadeOut('slow');
        } else {
            $('.research').fadeIn('slow');
            $('.line_research').fadeIn('slow');
        }
    });

    $('#toggleShips').on('click', function(e) {
        e.preventDefault();
        if ($('.ship').css('display') != 'none') {
            $('.ship').fadeOut('slow');
            $('.line_ship').fadeOut('slow');
            $('.category_ships').fadeOut('slow');
        } else {
            $('.ship').fadeIn('slow');
            $('.line_ship').fadeIn('slow');
            $('.category_ships').fadeIn('slow');
        }
    });

    $('#toggleAdvisors').on('click', function(e) {
        e.preventDefault();
        if ($('.personell').css('display') != 'none') {
            $('.personell').fadeOut('slow');
            $('.line_personell').fadeOut('slow');
            $('.category_crew').fadeOut('slow');
        } else {
            $('.personell').fadeIn('slow');
            $('.line_personell').fadeIn('slow');
            $('.category_crew').fadeIn('slow');
        }
    });

});

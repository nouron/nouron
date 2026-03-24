$(document).ready(function(){

    /* enable tooltips */
    $('[rel=tooltip]').tooltip();

    // Techtree (Show/Hide Technologies)
    $('#toggleBuildings').on('click', function(e){
        e.preventDefault();
        var visible = $('.building').is(':visible');
        if (visible) {
            $('.building').fadeOut('slow');
            techtree.lines.building.forEach(function(l) { l.hide('fade'); });
        } else {
            $('.building').fadeIn('slow');
            techtree.lines.building.forEach(function(l) { l.show('fade'); });
        }
    });

    $('#toggleResearches').on('click', function(e) {
        e.preventDefault();
        var visible = $('.research').is(':visible');
        if (visible) {
            $('.research').fadeOut('slow');
            techtree.lines.research.forEach(function(l) { l.hide('fade'); });
        } else {
            $('.research').fadeIn('slow');
            techtree.lines.research.forEach(function(l) { l.show('fade'); });
        }
    });

    $('#toggleShips').on('click', function(e) {
        e.preventDefault();
        var visible = $('.ship').is(':visible');
        if (visible) {
            $('.ship').fadeOut('slow');
            techtree.lines.ship.forEach(function(l) { l.hide('fade'); });
        } else {
            $('.ship').fadeIn('slow');
            techtree.lines.ship.forEach(function(l) { l.show('fade'); });
        }
    });

    $('#toggleAdvisors').on('click', function(e) {
        e.preventDefault();
        var visible = $('.personell').is(':visible');
        if (visible) {
            $('.personell').fadeOut('slow');
            techtree.lines.personell.forEach(function(l) { l.hide('fade'); });
        } else {
            $('.personell').fadeIn('slow');
            techtree.lines.personell.forEach(function(l) { l.show('fade'); });
        }
    });

});

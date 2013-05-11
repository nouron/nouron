// Run once the DOM is ready
$(document).ready(function () {
    
    $('.new-inbox-message').bind('fade-cycle', function() {
        $(this).fadeOut('slow', function() {
            $(this).fadeIn('slow', function() {
                $(this).trigger('fade-cycle');
            });
        });
    });
    $('.new-inbox-message').each(function(index, elem) {
        setTimeout(function() {
            $(elem).trigger('fade-cycle');
        }, index * 250);
    });    
    
    $(".message-options a.btn").click(function(e){
        e.preventDefault();
        button = $(this);
        button.siblings().addClass('disabled');
        var target = $(this).attr('href');
        var message_id_options = $(this).parent().attr('id');
        $.getJSON(
            target,
            function(data) {
                message_dom_id = message_id_options.replace('-options', '');
                if (data.result && (data.status == 'archived' || data.status == 'deleted')) {
                    $("#"+message_dom_id).fadeOut('fast');
                } else if (data.result) {
                    button.siblings().addClass('disabled');
                    span = '<span class="btn">'+button.html()+'</span>';
                    button.replaceWith( span );
                    $("#"+message_dom_id+" .message-options a.btn").last().removeClass('hidden disabled');
                    $("#"+message_dom_id+" .message-options a.btn").last().prev().removeClass('hidden disabled');
                }

                $("#flashMessages").append('<div class="alert alert-success">ok</div>');
            },
            function(data) {
                $("#flashMessages").append('<div class="alert alert-error">Ein Fehler ist aufgetreten</div>');
            }
        );
        return false;
    });
    
    
    
    
});
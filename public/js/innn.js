// Run once the DOM is ready
$(document).ready(function () {

    $(".message-options a.btn").click(function(e){
        e.preventDefault();

        $(this).addClass('disabled');
        $(this).siblings().addClass('disabled');
        
        var target = $(this).attr('href');
        var message_id_options = $(this).parent().attr('id');
        $.getJSON(
            target,
            function(data) {
                $(this).removeClass('disabled');
                message_dom_id = message_id_options.replace('-options', '');
                
                if (data.result && (data.status == 'archived' || data.status == 'deleted')) {
                    $("#"+message_dom_id).fadeOut('fast');
                }
            }
        );
        return false;
    });
});
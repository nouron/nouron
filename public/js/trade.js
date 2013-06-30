// Run once the DOM is ready
$(document).ready(function () {
    $(".modal form").live("submit", function(e){
        e.preventDefault();
        action = $(this).attr('action');
        $.post(
            action,
            $(this).serialize(),
            function(html) {
                if (html=='') {
                    $('.modal').modal({show:false});
                } else {
                    alert('test');
                    $('.modal').modal({show:true});
                    $('.modal-body').replaceWith(html);
                }
            },
            "html"
        );
    });
});
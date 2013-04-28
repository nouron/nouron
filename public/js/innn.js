// Run once the DOM is ready
$(document).ready(function () {

    $(".message-options a.btn").click(function(e){
        e.preventDefault();
        //$(".message-options a.btn").disable();
        
        var target = $(this).attr('href');
        $.post(
            target,
            {},
            function(data) {
                if (data.result) {
                    alert('ok');
                } else {
                    //$(".message-options a.btn").enable();
                }
                
            },
            "html"
        );
        return false;
    });
});
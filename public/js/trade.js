//// Run once the DOM is ready
//$(document).ready(function () {
function isPositiveNumber(str) {
    var n = ~~Number(str);
    return String(n) === str && n > 0;
}
trade = {
    addPlusMinusButtons: function(name) {
        input = $('input#'+name);
        input.parent().addClass('input-prepend input-append');
        input.before('<button id="'+name+'-minus-button" class="btn" type="button"><i class="icon-minus-sign"></i></button>');
        input.after('<button id="'+name+'-plus-button" class="btn" type="button"><i class="icon-plus-sign"></i></button>');
        input.live('keyup', function(e) {
            if ( !isPositiveNumber(input.attr('value')) ) {
                input.attr('value', 0);
            }
        });
        
        $('#'+name+'-plus-button').live('click', function(e) {
            value = parseInt($('#'+name).attr('value'));
            value = (!isNaN(value)) ? value : 0 ;
            $('#'+name).attr('value', value+1 );
        });
        $('#'+name+'-minus-button').live("click", function(e){
            value = parseInt($("#"+name).attr('value'));
            value = (!isNaN(value)) ? value : 0 ;
            if (value > 0) {
                $('#'+name).attr('value', value-1 );
            }
        });
    },        
    // Initialisierung
    init : function() {
        console.log('trade.init()');
        trade.addPlusMinusButtons('price');
        trade.addPlusMinusButtons('amount');
        
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
                        $('.modal').modal({show:true});
                        $('.modal-body').replaceWith(html);
                        trade.addPlusMinusButtons('price');
                        trade.addPlusMinusButtons('amount');
                    }
                },
                "html"
            );
        });
        
        /** click and confirm delete button => remove offer, update dom */
        $('.removeOfferButton').live('click', function(e){
            e.preventDefault();
            var href= $(this).attr('href');
            var id = $(this).parent().parent().attr('id');
            var offerType = $(this).hasClass('resource') ? 'resource' : 'technology';
            bootbox.confirm("Are you sure?", function(result) {
                if (result == true) {
                    data = id.split("-",3);
                    if (offerType == 'resource') {
                        data = {'colony_id': data[1], 'resource_id': data[2]}
                    } else {
                        data = {'colony_id': data[1], 'tech_id': data[2]}
                    }
                    $.post(
                        href,
                        data,
                        $("#"+id).remove()
                    );
                }
            }); 
        });
    }
};
//});
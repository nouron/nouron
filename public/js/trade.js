$(document).ready(function(){
    function isPositiveNumber(str) {
        var n = ~~Number(str);
        return String(n) === str && n > 0;
    }
    trade = {
        addOneUnit: function(name) {
            value = parseInt($('#'+name).attr('value'));
            value = (!isNaN(value)) ? value : 0 ;
            $('#'+name).attr('value', value+1 );
        },
        removeOneUnit: function(name) {
            value = parseInt($("#"+name).attr('value'));
            value = (!isNaN(value)) ? value : 0 ;
            if (value > 0) {
                $('#'+name).attr('value', value-1 );
            }
        },
        addPlusMinusButtons: function(name) {
            input = $('input#'+name);
            input.parent().addClass('input-group ').addClass('col-xs-6');
            input.before('<span id="'+name+'-minus-button" class="btn btn-default input-group-addon"><i class="glyphicon glyphicon-minus-sign"></i></span>');
            input.after('<span id="'+name+'-plus-button" class="btn btn-default input-group-addon"><i class="glyphicon glyphicon-plus-sign"></i></span>');
            $(document).on('keyup', 'input#'+name, function(e) {
                if ( !isPositiveNumber(input.attr('value')) ) {
                    input.attr('value', 0);
                }
            });

            var plusButtonTimeout, plusButton = $('#'+name+'-plus-button');
            plusButton.mousedown(function(){
                plusButtonTimeout = setInterval(function(){
                    trade.addOneUnit(name);
                }, 250);
                return false;
            })
            .click(function(){
                trade.addOneUnit(name);
                return false;
            });

            var minusButtonTimeout, minusButton = $('#'+name+'-minus-button');
            minusButton.mousedown(function(){
                minusButtonTimeout = setInterval(function(){
                    trade.removeOneUnit(name);
                }, 250);
                return false;
            })
            .click(function(){
                trade.removeOneUnit(name);
                return false;
            });

            $(document).mouseup(function(){
                clearInterval(plusButtonTimeout);
                clearInterval(minusButtonTimeout);
                return false;
            });
        },
        // Initialisierung
        init : function() {
            console.log('trade.init()');
            trade.addPlusMinusButtons('price');
            trade.addPlusMinusButtons('amount');

            $(document).on("submit", ".modal form", function(e){
                e.preventDefault();
                action = $(this).attr('action');
                $.post(
                    action,
                    $(this).serialize(),
                    function(html) {
                        if (html=='') {
                            $.bootstrapGrowl("Trade offer created/updated!", {
                                type: 'success',
                                align: 'center',
                                width: 'auto'
                            });

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
            $(document).on('click', '.removeOfferButton', function(e){
                e.preventDefault();
                var href= $(this).attr('href');
                var id = $(this).parent().parent().attr('id');
                var data = id.split("-",4);
                var offerType = data[1]
                bootbox.confirm("Are you sure?", function(result) {
                    if (result == true) {
                        if (offerType == 'resource') {
                            data = {'colony_id': data[2], 'resource_id': data[3]}
                        } else {
                            data = {'colony_id': data[2], 'research_id': data[3]}
                        }
                        $.post(
                            href,
                            data,
                            function(returnData) {
                                if (returnData.result == true) {
                                    $("#"+id).remove();
                                } else {
                                    console.log('removal failed');
                                    $.bootstrapGrowl("removal failed!", {
                                        type: 'error',
                                        align: 'center',
                                        width: 'auto'
                                    });
                                }
                            }
                        );
                    }
                });
            });
        }
    };
});

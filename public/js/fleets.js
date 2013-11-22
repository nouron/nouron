fleetlist = {
    init : function() {}
    

}

fleetconfig = {
    init : function() {
        var fleetId  = parseInt($("#fleet_id").html());
        var colonyId = parseInt($("#colony_id").html());
        
        $("#fleet .item div").live("mouseover", function(e){
            $(this).children(".btn").removeClass('disabled');
        });
        $("#fleet .item div").live("mouseleave", function(e){
            $(this).children(".btn").addClass('disabled');
        });

        /**
         * first get all available technologies
         */
        $.getJSON(
            "/techtree/json/getColonyTechnologies",
            function(items) {
                $.each(items, function(type, techs) {
                    for (var techId in techs) {
                        $('.'+type+'OnColony-'+techId).html(techs[techId].level);
                    }
                });
            });
        
        /**
         * count for each technology on fleet side the available amount and
         * update the values
         */
        $.getJSON(
            "/fleet/json/getFleetTechnologies/"+fleetId,
            function (items) {
                $.each(items, function(type, techs) {
                    for (var techId in techs) {
                        if (techs[techId].is_cargo) {
                            $('.'+type+'InFleetCargo-'+techId).html(techs[techId].count);
                        } else {
                            $('.'+type+'InFleet-'+techId).html(techs[techId].count);
                        }
                    }
                });
            }
        );
        
        /**
         * 
         */
        $.getJSON(
            "/resources/json/getColonyResources/"+colonyId,
            function (items) {
                $.each(items, function(resId, res) {
                    $('.resourceOnColony-'+resId).html(res.amount);
                });
            }
        );
        
        /**
         * count for each technology on fleet side the available amount and
         * update the values
         */
        $.getJSON(
            "/fleet/json/getFleetResources/"+fleetId,
            function (items) {
                $.each(items, function(resId, res) {
                    $('.resourceInFleetCargo-'+resId).html(res.amount);
                });
            }
        );

        /*
         * add to Fleet - Click-Actions:
         */
        $(".item .transfer").live('click', function(e) {
            e.preventDefault();
            $(".item .btn").addClass('disabled');
            amount = parseInt($(this).html());
            var item  = $(this).parent().parent();
            var cargo = $(this).hasClass('cargo');
            if (item.hasClass('ship')) {
                var itemId = item.attr('id').replace('ship-','');
                fleetconfig.addToFleet('ship', itemId, amount, cargo);
            } else if (item.hasClass('research')) {
                var itemId = item.attr('id').replace('research-','');
                fleetconfig.addToFleet('research', itemId, amount, cargo);
            } else if (item.hasClass('personell')) {
                var itemId = item.attr('id').replace('personell-','');
                fleetconfig.addToFleet('personell', itemId, amount, cargo);
            } else if (item.hasClass('resource')) {
                var itemId = item.attr('id').replace('resource-','');
                fleetconfig.addToFleet('resource', itemId, amount, true);
            }
            $(".item .btn").removeClass('disabled');
        });
    },

    /**
     * adds or removes the given amount to or from current fleet
     * 
     * @param integer tech  The technology id
     * @param integer amount  The amount of transferred techs (can be negative when removing techs)
     * @param boolean asCargo The tech is added to / removed from fleet cargo
     */
    addToFleet : function(itemType, itemId, amount, asCargo) {
        var fleetId = $("#fleet_id").html();
        
        $.post(
            "/fleet/json/addToFleet/"+fleetId,
            {
                'id' : fleetId,
                'itemType': itemType,
                'itemId' : itemId,
                'amount' : amount,
                'isCargo' : asCargo
            },
            function(data) {
                console.log(data);
                if (data.transferred > 0 && amount > 0) {
                    fleetconfig.updateAmounts(itemType, itemId, data.transferred, asCargo);
                }
                if (data.transferred > 0 && amount < 0) {
                    fleetconfig.updateAmounts(itemType, itemId, -data.transferred, asCargo);
                }
            },
            'json'
        );
    },

    /**
     * update the tech amounts ONLY visually
     */
    updateAmounts : function(itemType, itemId, delta, asCargo) {
        console.log(itemType+' '+itemId+' '+delta+' # ');

        if (itemId > 0 && !isNaN(delta) && delta != 0) {
            var selector = "."+itemType+"OnColony-"+itemId;
            var oldColoAmount = parseInt($(selector).html());
            if (oldColoAmount == NaN) {oldColoAmount=0;}
            $(selector).html(oldColoAmount - delta);
            
            if (asCargo == true) {
                selector = "."+itemType+"InFleetCargo-"+itemId;
            } else {
                selector = "."+itemType+"InFleet-"+itemId;
            }
            oldFleetAmount = parseInt($(selector).html());
            if (oldFleetAmount == NaN) {oldFleetAmount=0;}
            $(selector).html(oldFleetAmount + delta);
        }
    }
}
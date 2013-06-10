fleets = {

    // Initialisierung
    init : function() {

        var fleetId  = parseInt($("#fleet_id").html());
        var colonyId = parseInt($("#colony_id").html());
        
        $("#fleet .tech .btn").hide();
        $("#fleet .resource .btn").hide();
        
        $("#fleet .item div").live("mouseover", function(e){
            $(this).children(".btn").show();
        });
        $("#fleet .item div").live("mouseleave", function(e){
            $(this).children(".btn").hide();
        });
        /**
         * first get all available technologies
         */
        $.getJSON(
            "/techtree/json/getTechnologies",
            function(items) {
                $.each(items, function(techId, tech) {

                });
            });
        
        /**
         * count for each technology on colony side the available amount and
         * update the values
         */
        $.getJSON(
            "/techtree/json/getTechtree",
            function (items) {
                $.each(items, function(techId, tech) {
                    $('#techOnColony-'+techId).html(tech.level);
                });
            }
        );
        
        /**
         * count for each technology on fleet side the available amount and
         * update the values
         */
        $.getJSON(
            "/fleet/json/getFleetTechnologies/"+fleetId,
            function (items) {
                $.each(items, function(techId, tech) {
                    $('#techInFleet-'+techId).html(tech.count);
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
                    $('#resourceOnColony-'+resId).html(res.amount);
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
                    $('#resourceInFleetCargo-'+resId).html(res.amount);
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
            var item = $(this).parent().parent();
            if (item.hasClass('tech')) {
                cargo  = $(this).hasClass('cargo');
                var itemId = item.attr('id').replace('tech-','');
                fleets.addToFleet('tech', itemId, amount, cargo);
            } else if (item.hasClass('resource')) {
                var itemId = item.attr('id').replace('resource-','');
                fleets.addToFleet('resource', itemId, amount, true);
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
        fleetId = $("#fleet_id").html();
        
        $.post("/fleet/json/addToFleet/"+fleetId, {
            'id' : fleetId,
            'itemType': itemType,
            'itemId' : itemId,
            'amount' : amount,
            'isCargo' : asCargo
        }, function(data) {
            console.log(data);
            if (data.transferred > 0 && amount > 0) {
                fleets.updateAmounts(itemType, itemId, data.transferred, asCargo);
            }
            if (data.transferred > 0 && amount < 0) {
                fleets.updateAmounts(itemType, itemId, -data.transferred, asCargo);
            }            
        }, 'json');
    },

    /**
     * update the tech amounts visually
     */
    updateAmounts : function(itemType, itemId, delta, asCargo) {
        console.log(itemType+' '+itemId+' '+delta+' # ');

        if (itemId > 0 && !isNaN(delta) && delta != 0) {
            selector = "#"+itemType+"OnColony-"+itemId;
            oldColoAmount = parseInt($(selector).html());
            if (oldColoAmount == NaN) {oldColoAmount=0;}
            $(selector).html(oldColoAmount - delta);
            
            if (asCargo == true) {
                selector = "#"+itemType+"InFleetCargo-"+itemId;
            } else {
                selector = "#"+itemType+"InFleet-"+itemId;
            }
            oldFleetAmount = parseInt($(selector).html());
            if (oldFleetAmount == NaN) {oldFleetAmount=0;}
            $(selector).html(oldFleetAmount + delta);
        }
    }
}
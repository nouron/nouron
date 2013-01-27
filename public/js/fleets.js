fleets = {

    // Initialisierung
    init : function() {

        /**
         * first get all available technologies
         */
        $.getJSON(
            "/galaxy/fleet/getTechnologiesAsJson",
            function(items) {
                $.each(items, function(techId, tech) {
                    colonyItemHtml = '<li class="colo_item ' + tech.sType + ' roundedCorners" id="colo_' + tech.sType + '_' + techId + '">';
                    fleetItemHtml  = '<li class="fleet_item ' + tech.sType + ' roundedCorners" id="fleet_' + tech.sType + '_' + techId + '">';
                    html = '<a href="#" title="' + tech.sTranslated + '">'
                        + tech.sTranslated
                        + ' <span class="amount">0</span>'
                        + '<span class="id">' + techId + '</span>'
                        + '<span class="type">' + tech.sType + '</span>'
                        + '</a></li>';
                    
                    switch (tech.sType) {
                        case 'ship':
                            $("#colo_category_ships ul.list_ships").append(colonyItemHtml + html);
                            $("#fleet_category_ships ul.list_ships").append(fleetItemHtml + html);
                            break;
                        case 'advisor':
                            $("#colo_category_crew ul.list_crew").append(colonyItemHtml + html);
                            $("#fleet_category_crew ul.list_crew").append(fleetItemHtml + html);
                            // @todo list_passenger
                            break;
                        default:
                            $("#colo_category_cargo ul.list_cargo").append(colonyItemHtml + html);
                            $("#fleet_category_cargo ul.list_cargo").append(fleetItemHtml + html);
                            break;
                    }
                    
                    $('#colo_' + tech.sType + '_' + techId).hide();
                    $('#fleet_' + tech.sType + '_' + techId).hide();
                });
            });
        
        /**
         * count for each technology on colony side the available amount and
         * update the values
         */
        $.getJSON(
            "/galaxy/fleet/getTechtreeAsJson",
            function (items) {
                $.each(items, function(techId, tech) {
                    $('#colo_' + tech.sType + '_' + techId + ' .amount').html(tech.nCount);
                    count = $('#colo_' + tech.sType + '_' + techId + ' .amount').html();
                    if (count > 0) {
                        $('#colo_' + tech.sType + '_' + techId).show();
                    }
                });
            }
        );
        
        /**
         * count for each technology on fleet side the available amount and
         * update the values
         */
        $.getJSON(
            "/galaxy/fleet/getFleetTechnologiesAsJson",
            function (items) {
                $.each(items, function(techId, tech) {
                    $('#fleet_' + tech.sType + '_' + techId + ' .amount').html(tech.nCount);
                    count = $('#fleet_' + tech.sType + '_' + techId + ' .amount').html();
                    if (count > 0) {
                        $('#fleet_' + tech.sType + '_' + techId).show();
                    }
                });
            }
        );

        /**
         * clicks on technologies
         * - will select category
         * - will select side
         */
        $("#fleet_category_ships li").click(function(e) {
            e.preventDefault();
            fleets.selectCategory('.category_ships');
            fleets.selectSide('fleet');
        });
        $("#colo_category_ships li").click(function(e) {
            e.preventDefault();
            fleets.selectCategory('.category_ships');
            fleets.selectSide('colony');
        });
        $("#fleet_category_crew li").click(function(e) {
            e.preventDefault();
            fleets.selectCategory('.category_crew');
            fleets.selectSide('fleet');
        });
        $("#colo_category_crew li").click(function(e) {
            e.preventDefault();
            fleets.selectCategory('.category_crew');
            fleets.selectSide('colony');
        });
        $("#fleet_category_cargo li").click(function(e) {
            e.preventDefault();
            fleets.selectCategory('.category_cargo');
            fleets.selectSide('fleet');
        });
        $("#colo_category_cargo li").click(function(e) {
            e.preventDefault();
            fleets.selectCategory('.category_cargo');
            fleets.selectSide('colony');
        });

        $(".colo_item a").live('click', function(e) {
            e.preventDefault();
            $(".fleet_item").removeClass("active");
            $(".colo_item").removeClass("active");
            var html = $(this).html();
            $("#addToFleetLinks").show();
            $("#addToColonyLinks").hide();
            $("#addToFleetLinks p").html(html);
            var amount = $("#addToFleetLinks p span.amount").html();
            fleets.showhideTransferButtons(amount);
            var id = $("#addToFleetLinks p span.id").html();
            var type = $("#addToFleetLinks p span.type").html();
            $("#fleet_" + type + "_" + id).addClass("active");
            $("#colo_" + type + "_" + id).addClass("active");
        });

        $(".fleet_item a").live('click', function(e) {
            e.preventDefault();
            $(".fleet_item").removeClass("active");
            $(".colo_item").removeClass("active");
            var html = $(this).html();
            $("#addToFleetLinks").hide();
            $("#addToColonyLinks").show();
            $("#addToColonyLinks p").html(html);
            var amount = $("#addToColonyLinks p span.amount").html();
            fleets.showhideTransferButtons(amount);
            var id = $("#addToColonyLinks p span.id").html();
            var type = $("#addToColonyLinks p span.type").html();
            $("#fleet_" + type + "_" + id).addClass("active");
            $("#colo_" + type + "_" + id).addClass("active");
        });

        /*
         * add to Fleet - Click-Actions:
         */
        $("#add1").unbind("click");
        $("#add1").click(function(e) {
            e.preventDefault();
            techId = $("#addToFleetLinks p span.id").html();
            fleets.addToFleet(techId, 1, false);
        });
        $("#add5").unbind("click");
        $("#add5").click(function(e) {
            e.preventDefault();
            techId = $("#addToFleetLinks p span.id").html();
            fleets.addToFleet(techId, 5, false);
        });
        $("#add10").unbind("click");
        $("#add10").click(function(e) {
            e.preventDefault();
            techId = $("#addToFleetLinks p span.id").html();
            fleets.addToFleet(techId, 10, false);
        });

        /**
         * add to Fleet cargo - Click-Actions:
         */
        $("#addToCargo1").unbind("click");
        $("#addToCargo1").click(function(e) {
            e.preventDefault();
            techId = $("#addToFleetLinks p span.id").html();
            fleets.addToFleet(techId, 1, true);
        });
        $("#addToCargo5").unbind("click");
        $("#addToCargo5").click(function(e) {
            e.preventDefault();
            techId = $("#addToFleetLinks p span.id").html();
            fleets.addToFleet(techId, 5, true);
        });
        $("#addToCargo10").unbind("click");
        $("#addToCargo10").click(function(e) {
            e.preventDefault();
            techId = $("#addToFleetLinks p span.id").html();
            fleets.addToFleet(techId, 10, true);
        });

        /**
         * remove from fleet
         */
        $("#remove1").unbind("click");
        $("#remove1").click(function(e) {
            e.preventDefault();
            techId = $("#addToColonyLinks p span.id").html();
            fleets.addToFleet(techId, -1, false);
        });
        $("#remove5").unbind("click");
        $("#remove5").click(function(e) {
            e.preventDefault();
            techId = $("#addToColonyLinks p span.id").html();
            fleets.addToFleet(techId, -5, false);
        });
        $("#remove10").unbind("click");
        $("#remove10").click(function(e) {
            e.preventDefault();
            techId = $("#addToColonyLinks p span.id").html();
            fleets.addToFleet(techId, -10, false);
        });

    },
    /**
     * visually select active category
     */
    selectCategory : function(className) {
        $(".category_ships").removeClass("active");
        $(".category_crew").removeClass("active");
        $(".category_cargo").removeClass("active");

        $(className).addClass("active");
    },
    /**
     * visually select active side (fleet or colony)
     */
    selectSide : function(side) {
        if (side == 'fleet') {
            $("#addToColonyLinks").show();
            $("#addToFleetLinks").hide();
            $("#addToFleetCargoLinks").hide();
        } else {
            $("#addToFleetLinks").show();
            $("#addToFleetCargoLinks").show();
            $("#addToColonyLinks").hide();
        }
    },
    /**
     * adds or removes the given amount to or from current fleet
     * 
     * @param integer tech  The technology id
     * @param integer amount  The amount of transferred techs (can be negative when removing techs)
     * @param boolean asCargo The tech is added to / removed from fleet cargo
     */
    addToFleet : function(tech, amount, asCargo) {
        fleet = $("#fleet_id").html();

        $.post("/galaxy/fleet/addToFleet/", {
            'id' : fleet,
            'tech' : tech,
            'amount' : amount,
            'isCargo' : asCargo
        }, function(data) {
            console.log(data);
            if (data.transferred > 0 && amount > 0) {
                fleets.updateAmounts(tech, data.transferred);
            }
            if (data.transferred > 0 && amount < 0) {
                fleets.updateAmounts(tech, -data.transferred);
            }            
        }, 'json');
    },
    /**
     * hides the transfer buttons that are higher than the available tech amount
     * 
     * @param integer amount 
     */
    showhideTransferButtons : function(amount) {
        if (amount >= 1) {
            $("#add1").show();
            $("#remove1").show();
            $("#addToCargo1").show();
        } else {
            $("#add1").hide();
            $("#remove1").hide();
            $("#addToCargo1").hide();
        }

        if (amount >= 5) {
            $("#add5").show();
            $("#remove5").show();
            $("#addToCargo5").show();
        } else {
            $("#add5").hide();
            $("#remove5").hide();
            $("#addToCargo5").hide();
        }

        if (amount >= 10) {
            $("#add10").show();
            $("#remove10").show();
            $("#addToCargo10").show();
        } else {
            $("#add10").hide();
            $("#remove10").hide();
            $("#addToCargo10").hide();
        }
    },
    /**
     * update the tech amounts visually
     */
    updateAmounts : function(techId, delta) {
        
        console.log(techId+' '+delta+' # ');

        if (techId > 0 && !isNaN(delta) && delta != 0) {
            type = $("#addToFleetLinks p span.type").html();

            oldColoAmount = parseInt($(".colo_item.active span.amount").html());
            if (oldColoAmount == NaN) {oldColoAmount=0;}
            $(".colo_item.active span.amount").html(oldColoAmount - delta);

            oldFleetAmount = parseInt($(".fleet_item.active span.amount").html());
            if (oldFleetAmount == NaN) {oldFleetAmount=0;}
            $(".fleet_item.active span.amount").html(oldFleetAmount + delta);
            if (oldFleetAmount+delta > 0) { $(".fleet_item.active").show() }
        }
        
        $("#fleet li a span.amount").each(function(index) {
            // don't show anything with an amount of 0
            if ($(this).html() <= 0 || $(this).html() == 'undefined') {
                $(this).parent().parent().hide();
                if ($(this).parent().parent().hasClass('active')) {
                    $('#addToColonyLinks').hide();
                    $('#addToFleetLinks').hide();
                }
            } else {
                $(this).parent().parent().show();
            }
        });
    }
}
fleetlist = {
    init : function() {}
}

fleetconfig = {
    _qty: 1,
    _selected: null, // { type, id, cargo, name }

    init: function() {
        var fleetId  = parseInt($("#fleet_id").text());
        var colonyId = parseInt($("#colony_id").text());

        // Pre-fill all amount placeholders with 0 so items not returned by API show 0
        $('#fleetconfig span[class]').each(function() {
            if ($(this).text() === '…') $(this).text('0');
        });

        // Load colony technologies (ships, personell, research levels)
        $.getJSON("/techtree/json/getColonyTechnologies", function(items) {
            $.each(items, function(type, techs) {
                for (var techId in techs) {
                    $('.' + type + 'OnColony-' + techId).text(techs[techId].level);
                }
            });
        })
        .done(function() { console.info('Colony technologies loaded.'); })
        .fail(function() { console.error('Colony technologies failed.'); });

        // Load fleet technologies (ships, personell, research in fleet)
        $.getJSON("/fleet/json/getFleetTechnologies/" + fleetId, function(items) {
            $.each(items, function(type, techs) {
                for (var i in techs) {
                    if (techs[i].is_cargo == 1) {
                        $('.' + type + 'InFleetCargo-' + techs[i][type + '_id']).text(techs[i].count);
                    } else {
                        $('.' + type + 'InFleet-' + techs[i][type + '_id']).text(techs[i].count);
                    }
                }
            });
        })
        .done(function() { console.info('Fleet technologies loaded.'); })
        .fail(function() { console.error('Fleet technologies failed.'); });

        // Load colony resources
        if (colonyId) {
            $.getJSON("/resources/json/getColonyResources/" + colonyId, function(items) {
                $.each(items, function(resId, res) {
                    $('.resourceOnColony-' + resId).text(res.amount);
                });
            })
            .done(function() { console.info('Colony resources loaded.'); })
            .fail(function() { console.error('Colony resources failed.'); });
        }

        // Load fleet resources
        $.getJSON("/fleet/json/getFleetResources/" + fleetId, function(items) {
            $.each(items, function(resId, res) {
                $('.resourceInFleetCargo-' + resId).text(res.amount);
            });
        })
        .done(function() { console.info('Fleet resources loaded.'); })
        .fail(function() { console.error('Fleet resources failed.'); });

        // Quantity selector
        $(document).on('click', '.fc-qty-btn', function() {
            $('.fc-qty-btn').removeClass('active');
            $(this).addClass('active');
            fleetconfig._qty = parseInt($(this).data('qty'));
        });

        // Item row selection
        $(document).on('click', '.fc-item', function() {
            $('.fc-item').removeClass('selected');
            $(this).addClass('selected');
            fleetconfig._selected = {
                type:  $(this).data('type'),
                id:    $(this).data('id'),
                cargo: $(this).data('cargo'),
                name:  $(this).find('.fc-mid').text().trim()
            };
            $('#fc-label').text(fleetconfig._selected.name);
            $('#fc-to-colony').prop('disabled', false);
            $('#fc-to-fleet').prop('disabled', false);
        });

        // Transfer to fleet (+qty)
        $('#fc-to-fleet').on('click', function() {
            if (!fleetconfig._selected) return;
            fleetconfig.addToFleet(
                fleetconfig._selected.type,
                fleetconfig._selected.id,
                fleetconfig._qty,
                fleetconfig._selected.cargo == 1
            );
        });

        // Transfer to colony (-qty)
        $('#fc-to-colony').on('click', function() {
            if (!fleetconfig._selected) return;
            fleetconfig.addToFleet(
                fleetconfig._selected.type,
                fleetconfig._selected.id,
                -fleetconfig._qty,
                fleetconfig._selected.cargo == 1
            );
        });
    },

    addToFleet: function(itemType, itemId, amount, asCargo) {
        var fleetId = $("#fleet_id").text();
        console.log("fleet: " + fleetId, "itemType: " + itemType, "itemId: " + itemId, "amount: " + amount, "asCargo: " + asCargo);

        $('#fc-to-colony, #fc-to-fleet').prop('disabled', true);

        $.post(
            "/fleet/json/addToFleet/" + fleetId,
            {
                'id':       fleetId,
                'itemType': itemType,
                'itemId':   itemId,
                'amount':   amount,
                'isCargo':  asCargo ? 1 : 0
            },
            function(data) {
                console.info("addToFleet", data);
                if (data.transferred > 0 && amount > 0) {
                    fleetconfig.updateAmounts(itemType, itemId, data.transferred, asCargo);
                } else if (data.transferred > 0 && amount < 0) {
                    fleetconfig.updateAmounts(itemType, itemId, -data.transferred, asCargo);
                }
                $('#fc-to-colony, #fc-to-fleet').prop('disabled', false);
            },
            'json'
        )
        .fail(function() {
            console.error('Adding item to fleet failed.');
            $('#fc-to-colony, #fc-to-fleet').prop('disabled', false);
        });
    },

    updateAmounts: function(itemType, itemId, delta, asCargo) {
        console.info('updateAmounts()', 'type: ' + itemType + ' | itemId: ' + itemId + ' | delta: ' + delta);

        if (itemId > 0 && !isNaN(delta) && delta != 0) {
            var colSel = '.' + itemType + 'OnColony-' + itemId;
            var oldColo = parseInt($(colSel).text()) || 0;
            $(colSel).text(oldColo - delta);

            var fleetSel = asCargo
                ? '.' + itemType + 'InFleetCargo-' + itemId
                : '.' + itemType + 'InFleet-' + itemId;
            var oldFleet = parseInt($(fleetSel).text()) || 0;
            $(fleetSel).text(oldFleet + delta);
        }
    }
}

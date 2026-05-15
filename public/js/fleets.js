var fleetlist = { init: function () {} };

var fleetconfig = {
    _qty: 1,
    _selected: null,

    init: function () {
        var container  = document.getElementById('fleetconfig');
        var fleetIdEl  = document.getElementById('fleet_id');
        if (!container || !fleetIdEl) return;

        var fleetId  = parseInt(fleetIdEl.textContent, 10);
        var colonyEl = document.getElementById('colony_id');
        var colonyId = colonyEl ? parseInt(colonyEl.textContent, 10) : 0;

        // Pre-fill all '…' placeholders with 0
        container.querySelectorAll('span[class]').forEach(function (el) {
            if (el.textContent === '…') el.textContent = '0';
        });

        // Load colony technologies (ships, personell, research levels on colony)
        fetch('/techtree/json/getColonyTechnologies')
            .then(function (r) { return r.json(); })
            .then(function (items) {
                Object.entries(items).forEach(function (entry) {
                    var type = entry[0], techs = entry[1];
                    Object.values(techs).forEach(function (t) {
                        document.querySelectorAll('.' + type + 'OnColony-' + t.id).forEach(function (el) {
                            el.textContent = t.level;
                        });
                    });
                });
            })
            .catch(function () { console.error('Colony technologies failed.'); });

        // Load fleet technologies (ships, personell, research in fleet)
        fetch('/fleet/json/getFleetTechnologies/' + fleetId)
            .then(function (r) { return r.json(); })
            .then(function (items) {
                Object.entries(items).forEach(function (entry) {
                    var type = entry[0], techs = entry[1];
                    Object.values(techs).forEach(function (t) {
                        var domId = t[type + '_id'];
                        var sel = t.is_cargo == 1
                            ? '.' + type + 'InFleetCargo-' + domId
                            : '.' + type + 'InFleet-' + domId;
                        document.querySelectorAll(sel).forEach(function (el) {
                            el.textContent = t.count;
                        });
                    });
                });
            })
            .catch(function () { console.error('Fleet technologies failed.'); });

        // Load colony resources
        if (colonyId) {
            fetch('/resources/colony/' + colonyId)
                .then(function (r) { return r.json(); })
                .then(function (items) {
                    Object.entries(items).forEach(function (entry) {
                        var resId = entry[0], res = entry[1];
                        document.querySelectorAll('.resourceOnColony-' + resId).forEach(function (el) {
                            el.textContent = res.amount;
                        });
                    });
                })
                .catch(function () { console.error('Colony resources failed.'); });
        }

        // Load fleet resources
        fetch('/fleet/json/getFleetResources/' + fleetId)
            .then(function (r) { return r.json(); })
            .then(function (items) {
                Object.entries(items).forEach(function (entry) {
                    var resId = entry[0], res = entry[1];
                    document.querySelectorAll('.resourceInFleetCargo-' + resId).forEach(function (el) {
                        el.textContent = res.amount;
                    });
                });
            })
            .catch(function () { console.error('Fleet resources failed.'); });

        // Quantity selector + item row selection (single delegated listener)
        container.addEventListener('click', function (e) {
            var qtyBtn = e.target.closest('.fc-qty-btn');
            var item   = e.target.closest('.fc-item');

            if (qtyBtn) {
                container.querySelectorAll('.fc-qty-btn').forEach(function (b) {
                    b.classList.remove('active');
                });
                qtyBtn.classList.add('active');
                fleetconfig._qty = parseInt(qtyBtn.dataset.qty, 10);
                return;
            }

            if (item) {
                container.querySelectorAll('.fc-item').forEach(function (i) {
                    i.classList.remove('selected');
                });
                item.classList.add('selected');
                fleetconfig._selected = {
                    type:  item.dataset.type,
                    id:    item.dataset.id,
                    cargo: item.dataset.cargo,
                    name:  item.querySelector('.fc-mid').textContent.trim()
                };
                var label    = document.getElementById('fc-label');
                var toColony = document.getElementById('fc-to-colony');
                var toFleet  = document.getElementById('fc-to-fleet');
                if (label)    label.textContent = fleetconfig._selected.name;
                if (toColony) toColony.disabled = false;
                if (toFleet)  toFleet.disabled  = false;
            }
        });

        // Transfer buttons (only rendered when fleet is in colony orbit)
        var toFleetBtn  = document.getElementById('fc-to-fleet');
        var toColonyBtn = document.getElementById('fc-to-colony');
        if (toFleetBtn) {
            toFleetBtn.addEventListener('click', function () {
                if (!fleetconfig._selected) return;
                fleetconfig.addToFleet(
                    fleetconfig._selected.type,
                    fleetconfig._selected.id,
                    fleetconfig._qty,
                    fleetconfig._selected.cargo == 1
                );
            });
        }
        if (toColonyBtn) {
            toColonyBtn.addEventListener('click', function () {
                if (!fleetconfig._selected) return;
                fleetconfig.addToFleet(
                    fleetconfig._selected.type,
                    fleetconfig._selected.id,
                    -fleetconfig._qty,
                    fleetconfig._selected.cargo == 1
                );
            });
        }
    },

    addToFleet: function (itemType, itemId, amount, asCargo) {
        var fleetId   = document.getElementById('fleet_id').textContent.trim();
        var toColony  = document.getElementById('fc-to-colony');
        var toFleet   = document.getElementById('fc-to-fleet');
        var csrfMeta  = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        if (toColony) toColony.disabled = true;
        if (toFleet)  toFleet.disabled  = true;

        var params = new URLSearchParams({
            id:       fleetId,
            itemType: itemType,
            itemId:   itemId,
            amount:   amount,
            isCargo:  asCargo ? 1 : 0
        });

        fetch('/fleet/json/addToFleet/' + fleetId, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN':     csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params.toString()
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.transferred > 0 && amount > 0) {
                fleetconfig.updateAmounts(itemType, itemId, data.transferred, asCargo);
            } else if (data.transferred > 0 && amount < 0) {
                fleetconfig.updateAmounts(itemType, itemId, -data.transferred, asCargo);
            }
            if (toColony) toColony.disabled = false;
            if (toFleet)  toFleet.disabled  = false;
        })
        .catch(function () {
            console.error('Adding item to fleet failed.');
            if (toColony) toColony.disabled = false;
            if (toFleet)  toFleet.disabled  = false;
        });
    },

    updateAmounts: function (itemType, itemId, delta, asCargo) {
        if (!itemId || isNaN(delta) || delta === 0) return;

        document.querySelectorAll('.' + itemType + 'OnColony-' + itemId).forEach(function (el) {
            el.textContent = (parseInt(el.textContent, 10) || 0) - delta;
        });

        var fleetSel = asCargo
            ? '.' + itemType + 'InFleetCargo-' + itemId
            : '.' + itemType + 'InFleet-' + itemId;
        document.querySelectorAll(fleetSel).forEach(function (el) {
            el.textContent = (parseInt(el.textContent, 10) || 0) + delta;
        });
    }
};

$(document).ready(function () {

    // ── Galaxy Overview (/galaxy) ─────────────────────────────────────────
    if ($('#galaxy-overview').length > 0) {
        var el = document.getElementById('galaxy-overview');
        var allSystems = JSON.parse(el.dataset.systems || '[]');

        if (allSystems.length === 0) return;

        var xs = allSystems.map(function(s) { return +s.x; });
        var ys = allSystems.map(function(s) { return +s.y; });
        var pad = 400;
        var bounds = [
            [Math.min.apply(null, ys) - pad, Math.min.apply(null, xs) - pad],
            [Math.max.apply(null, ys) + pad, Math.max.apply(null, xs) + pad]
        ];

        var overview = L.map('galaxy-overview', {
            crs: L.CRS.Simple,
            attributionControl: false,
            zoomSnap: 0.5,
            minZoom: -3,
        });
        overview.fitBounds(bounds);

        // Starfield + nebulae as imageOverlay (pans with map)
        (function() {
            var W = 2048, H = 2048;
            var cvs = document.createElement('canvas');
            cvs.width = W; cvs.height = H;
            var ctx = cvs.getContext('2d');
            var minX = bounds[0][1], maxX = bounds[1][1];
            var minY = bounds[0][0], maxY = bounds[1][0];
            function gc(lat, lng) {
                return [(lng - minX) / (maxX - minX) * W,
                        (maxY - lat) / (maxY - minY) * H];
            }
            ctx.fillStyle = '#020810';
            ctx.fillRect(0, 0, W, H);
            var cx_ = (minX + maxX) / 2, cy_ = (minY + maxY) / 2;
            var spread = Math.max(maxX - minX, maxY - minY);
            var nebulae = [
                { lat: cy_ + spread*0.15, lng: cx_ + spread*0.20, r: W*0.14, color: [80,35,200] },
                { lat: cy_ - spread*0.20, lng: cx_ - spread*0.18, r: W*0.12, color: [20,70,190] },
                { lat: cy_ + spread*0.05, lng: cx_ - spread*0.28, r: W*0.10, color: [20,150,70] },
                { lat: cy_ - spread*0.10, lng: cx_ + spread*0.30, r: W*0.11, color: [180,60,20] },
            ];
            nebulae.forEach(function(n) {
                var p = gc(n.lat, n.lng);
                for (var pass = 0; pass < 4; pass++) {
                    var frac = pass / 3;
                    var alpha = 0.18 - frac * 0.15;
                    var scale = 0.3 + frac * 0.7;
                    var grd = ctx.createRadialGradient(p[0],p[1],0, p[0],p[1], n.r*scale);
                    grd.addColorStop(0, 'rgba('+n.color+','+alpha+')');
                    grd.addColorStop(1, 'rgba('+n.color+',0)');
                    ctx.fillStyle = grd;
                    ctx.beginPath(); ctx.arc(p[0],p[1], n.r*scale, 0, Math.PI*2); ctx.fill();
                }
            });
            var palettes = ['255,255,255','210,225,255','255,245,210'];
            for (var i = 0; i < 2500; i++) {
                var sx = Math.random()*W, sy = Math.random()*H;
                var rn = Math.random();
                var sr = rn < 0.73 ? 0.3 + Math.random()*0.4 : 0.7 + Math.random()*0.9;
                var a  = 0.2 + Math.random()*0.8;
                ctx.fillStyle = 'rgba('+palettes[Math.floor(Math.random()*3)]+','+a+')';
                ctx.beginPath(); ctx.arc(sx, sy, sr, 0, Math.PI*2); ctx.fill();
            }
            L.imageOverlay(cvs.toDataURL(), bounds, { opacity:1, zIndex:1, interactive:false }).addTo(overview);
        })();

        var STAR_COLORS = {
            'stellar_class_O': '#8899ff',
            'stellar_class_B': '#aabbff',
            'stellar_class_A': '#ccdeff',
            'stellar_class_F': '#ffeecc',
            'stellar_class_G': '#ffdd66',
            'stellar_class_K': '#ff9944',
            'stellar_class_M': '#ff5533',
        };

        // Auto jump lanes: connect each system to its 2 nearest neighbours
        var lanesAdded = {};
        allSystems.forEach(function(s) {
            var nearest = allSystems
                .filter(function(o) { return o.id !== s.id; })
                .map(function(o) {
                    var dx = o.x - s.x, dy = o.y - s.y;
                    return { sys: o, dist: Math.sqrt(dx*dx + dy*dy) };
                })
                .sort(function(a,b) { return a.dist - b.dist; })
                .slice(0, 2);
            nearest.forEach(function(n) {
                var key = [s.id, n.sys.id].sort().join('-');
                if (!lanesAdded[key]) {
                    lanesAdded[key] = true;
                    L.polyline([[+s.y, +s.x], [+n.sys.y, +n.sys.x]], {
                        color: '#1a3060', weight: 1.5, opacity: 0.55,
                        dashArray: '4 10', interactive: false,
                    }).addTo(overview);
                }
            });
        });

        // Coordinate grid
        (function() {
            var interval = 500;
            var minLat = bounds[0][0], maxLat = bounds[1][0];
            var minLng = bounds[0][1], maxLng = bounds[1][1];
            var ls = { color: 'rgba(80,140,220,0.12)', weight: 0.5, interactive: false };
            var lblCss = 'color:rgba(80,140,220,0.38);font:7px "Courier New",monospace;white-space:nowrap;';
            var startLat = Math.ceil(minLat / interval) * interval;
            var startLng = Math.ceil(minLng / interval) * interval;
            for (var lat = startLat; lat < maxLat; lat += interval) {
                L.polyline([[lat, minLng], [lat, maxLng]], ls).addTo(overview);
                L.marker([lat, minLng], { icon: L.divIcon({
                    html: '<span style="'+lblCss+'">'+lat+'</span>',
                    className: '', iconSize: [32, 8], iconAnchor: [-3, 4]
                }), interactive: false }).addTo(overview);
            }
            for (var lng = startLng; lng < maxLng; lng += interval) {
                L.polyline([[minLat, lng], [maxLat, lng]], ls).addTo(overview);
                L.marker([maxLat, lng], { icon: L.divIcon({
                    html: '<span style="'+lblCss+'">'+lng+'</span>',
                    className: '', iconSize: [32, 8], iconAnchor: [16, -2]
                }), interactive: false }).addTo(overview);
            }
        })();

        // System nodes
        allSystems.forEach(function(s) {
            var col    = STAR_COLORS[s['class']] || '#aaaaaa';
            var radius = Math.max(7, Math.min(13, (+s.size || 8) * 0.8));
            var latlng = [+s.y, +s.x];
            var node;

            if (s.icon_url) {
                var sz = Math.max(16, Math.min(28, (+s.size || 8) * 2));
                L.circleMarker(latlng, {
                    radius: sz/2 + 4, color: col, weight: 1,
                    opacity: 0.3, fillOpacity: 0, interactive: false,
                }).addTo(overview);
                node = L.marker(latlng, {
                    icon: L.icon({ iconUrl: '/img/' + s.icon_url, iconSize: [sz, sz], iconAnchor: [sz/2, sz/2] }),
                    className: 'galaxy-star',
                });
            } else {
                L.circleMarker(latlng, {
                    radius: radius + 5, color: col, weight: 1,
                    opacity: 0.25, fillOpacity: 0, interactive: false,
                }).addTo(overview);
                node = L.circleMarker(latlng, {
                    radius: radius, color: col, weight: 2,
                    fillColor: col, fillOpacity: 0.9, className: 'galaxy-star',
                });
            }

            node.bindTooltip(s.name.toUpperCase(), {
                permanent: true, direction: 'right',
                offset: [radius + 5, 0],
            })
            .on('click', function() { window.location.href = '/galaxy/' + s.id; })
            .addTo(overview);
        });
    }

    // ── System Detail (/galaxy/:id) ───────────────────────────────────────
    if ($('#galaxy-map').length > 0) {
        var mapEl      = document.getElementById('galaxy-map');
        var cx         = parseInt(mapEl.dataset.x || '0');
        var cy         = parseInt(mapEl.dataset.y || '0');
        var systemName = mapEl.dataset.systemName || '';
        var systemBg   = mapEl.dataset.bg || '';

        // Background
        if (systemBg) {
            mapEl.style.backgroundImage = 'url(' + systemBg + ')';
            mapEl.style.backgroundSize  = 'cover';
            mapEl.style.backgroundPosition = 'center';
        } else {
            (function() {
                var W = mapEl.offsetWidth || 1200, H = mapEl.offsetHeight || 800;
                var cvs = document.createElement('canvas');
                cvs.width = W; cvs.height = H;
                var ctx = cvs.getContext('2d');
                ctx.fillStyle = '#020810';
                ctx.fillRect(0, 0, W, H);
                var grd = ctx.createRadialGradient(W/2,H/2,0, W/2,H/2, W*0.35);
                grd.addColorStop(0, 'rgba(60,100,180,0.10)');
                grd.addColorStop(1, 'rgba(60,100,180,0)');
                ctx.fillStyle = grd; ctx.fillRect(0,0,W,H);
                var palettes = ['255,255,255','210,225,255','255,245,210'];
                for (var i = 0; i < 550; i++) {
                    var sx = Math.random()*W, sy = Math.random()*H;
                    var rn = Math.random();
                    var sr = rn < 0.75 ? 0.3+Math.random()*0.4 : 0.7+Math.random()*0.9;
                    var a  = 0.2+Math.random()*0.8;
                    ctx.fillStyle = 'rgba('+palettes[Math.floor(Math.random()*3)]+','+a+')';
                    ctx.beginPath(); ctx.arc(sx,sy,sr,0,Math.PI*2); ctx.fill();
                }
                mapEl.style.backgroundImage  = 'url('+cvs.toDataURL()+')';
                mapEl.style.backgroundSize   = 'cover';
            })();
        }

        var galaxyMap = L.map('galaxy-map', {
            crs: L.CRS.Simple,
            minZoom: 1, maxZoom: 4.5, zoomSnap: 0.5,
            attributionControl: false,
        });
        galaxyMap.setView([cy, cx], 4);

        // Central star
        L.circleMarker([cy, cx], {
            radius: 22, color: 'rgba(255,240,160,0.3)', weight: 1,
            fillColor: 'rgba(255,230,100,0.15)', fillOpacity: 1,
            interactive: false,
        }).addTo(galaxyMap);
        L.circleMarker([cy, cx], {
            radius: 14, color: '#fff5cc', weight: 2,
            fillColor: '#ffee66', fillOpacity: 0.92,
            className: 'system-star-outer', interactive: false,
        }).addTo(galaxyMap);
        L.circleMarker([cy, cx], {
            radius: 6, color: 'transparent', weight: 0,
            fillColor: '#ffffff', fillOpacity: 0.7, interactive: false,
        }).addTo(galaxyMap);

        // Star label
        var starLabelItem = (function() {
            var m = L.circleMarker([cy, cx], { radius: 1, opacity: 0, fillOpacity: 0, interactive: false })
                .bindTooltip(systemName, {
                    permanent: true, direction: 'right', offset: [18, 0],
                    className: 'star-label',
                }).addTo(galaxyMap);
            return { marker: m, interactive: false };
        })();

        // Coordinate grid
        (function() {
            var interval = 10;
            var range    = 150;
            var ls  = { color: 'rgba(80,140,220,0.14)', weight: 0.5, interactive: false };
            var lsM = { color: 'rgba(80,140,220,0.25)', weight: 0.5, interactive: false };
            var lblCss = 'color:rgba(80,140,220,0.35);font:7px "Courier New",monospace;white-space:nowrap;';
            var minC = -range, maxC = range;
            for (var v = minC; v <= maxC; v += interval) {
                var isMajor = (v % 50 === 0);
                L.polyline([[cy + v, cx + minC], [cy + v, cx + maxC]], isMajor ? lsM : ls).addTo(galaxyMap);
                L.polyline([[cy + minC, cx + v], [cy + maxC, cx + v]], isMajor ? lsM : ls).addTo(galaxyMap);
                if (isMajor && v !== 0) {
                    L.marker([cy + v, cx + minC], { icon: L.divIcon({
                        html: '<span style="'+lblCss+'">'+(cy+v)+'</span>',
                        className: '', iconSize: [32,8], iconAnchor: [-3,4]
                    }), interactive: false }).addTo(galaxyMap);
                    L.marker([cy + maxC, cx + v], { icon: L.divIcon({
                        html: '<span style="'+lblCss+'">'+(cx+v)+'</span>',
                        className: '', iconSize: [32,8], iconAnchor: [16,-2]
                    }), interactive: false }).addTo(galaxyMap);
                }
            }
        })();

        var layerMisc    = L.layerGroup().addTo(galaxyMap);
        var layerPlanets = L.layerGroup().addTo(galaxyMap);
        var layerFleets  = L.layerGroup().addTo(galaxyMap);

        var LABEL_ZOOM = 3;
        var labelItems = [starLabelItem];

        function updateLabels() {
            var show = galaxyMap.getZoom() >= LABEL_ZOOM;
            labelItems.forEach(function(item) {
                var m = item.marker;
                if (show) {
                    m.off('mouseover').off('mouseout');
                    m.openTooltip();
                } else {
                    m.closeTooltip();
                    if (item.interactive) {
                        m.on('mouseover', function() { m.openTooltip(); })
                         .on('mouseout',  function() { m.closeTooltip(); });
                    }
                }
            });
        }

        galaxyMap.on('zoomend', updateLabels);

        var lastLoadX = null, lastLoadY = null;
        var RELOAD_THRESHOLD = 25;

        function loadMapData(x, y) {
            fetch('/galaxy/json/getmapdata/' + x + '/' + y)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    layerMisc.clearLayers();
                    layerPlanets.clearLayers();
                    layerFleets.clearLayers();
                    labelItems = [starLabelItem];

                    var fieldGroups = {};
                    data.forEach(function(obj) {
                        if (obj.layer !== 0) return;
                        var title = obj.attribs.title || obj.attribs['class'] || '?';
                        if (!fieldGroups[title]) fieldGroups[title] = [];
                        fieldGroups[title].push(obj);
                    });

                    data.forEach(function(obj) {
                        var marker = renderObject(obj, obj.layer !== 0);
                        if (!marker) return;
                        if      (obj.layer === 0) layerMisc.addLayer(marker);
                        else if (obj.layer === 1) layerPlanets.addLayer(marker);
                        else if (obj.layer === 3) layerFleets.addLayer(marker);
                    });

                    Object.keys(fieldGroups).forEach(function(title) {
                        var group = fieldGroups[title];
                        var sumY = 0, sumX = 0;
                        group.forEach(function(obj) { sumY += obj.y; sumX += obj.x; });
                        var centY = sumY / group.length, centX = sumX / group.length;
                        var cm = L.circleMarker([centY, centX], {
                            radius: 0, opacity: 0, fillOpacity: 0, interactive: false,
                        }).addTo(layerMisc);
                        cm.bindTooltip(title, { permanent: true, direction: 'center', offset: [0, 0] });
                        labelItems.push({ marker: cm, interactive: false });
                    });

                    updateLabels();
                });
        }

        function renderObject(obj, showLabel) {
            var latlng = [obj.y, obj.x];
            var title  = obj.attribs.title || obj.attribs['class'] || '?';
            var size   = obj.layer === 0 ? 24 : 32;

            if (obj.attribs.image_url) {
                var icon = L.icon({
                    iconUrl: obj.attribs.image_url,
                    iconSize: [size, size], iconAnchor: [size/2, size/2],
                });
                var m = L.marker(latlng, { icon: icon });
                if (showLabel) {
                    m.bindTooltip(title, { permanent: true, direction: 'right', offset: [size/2 + 4, 0] });
                    labelItems.push({ marker: m, interactive: true });
                }
                return m;
            }
            if (obj.layer === 3) {
                return L.circleMarker(latlng, {
                    radius: 7, color: '#ffcc44', weight: 2,
                    fillColor: '#ffaa22', fillOpacity: 0.85,
                    className: 'fleet-marker',
                }).bindTooltip(title);
            }
            var cm = L.circleMarker(latlng, {
                radius: 7, color: '#4488cc', weight: 1.5,
                fillColor: '#224466', fillOpacity: 0.7,
            });
            if (showLabel) {
                cm.bindTooltip(title, { permanent: true, direction: 'right', offset: [11, 0] });
                labelItems.push({ marker: cm, interactive: true });
            }
            return cm;
        }

        galaxyMap.on('moveend', function() {
            var c = galaxyMap.getCenter();
            var x = Math.round(c.lng), y = Math.round(c.lat);
            if (lastLoadX === null ||
                Math.abs(x - lastLoadX) > RELOAD_THRESHOLD ||
                Math.abs(y - lastLoadY) > RELOAD_THRESHOLD) {
                lastLoadX = x; lastLoadY = y;
                loadMapData(x, y);
            }
        });

        // Toggle buttons
        $('#toggleGridLayer').on('click', function(e) {
            e.preventDefault();
            // grid is drawn directly on map; re-toggle via layerMisc visibility not applicable
            // just reload map data as a refresh
        });
        $('#toggleSystemLayer').on('click', function(e) {
            e.preventDefault();
            if (galaxyMap.hasLayer(layerPlanets)) {
                galaxyMap.removeLayer(layerPlanets);
            } else {
                galaxyMap.addLayer(layerPlanets);
            }
        });
        $('#toggleFleetsLayer').on('click', function(e) {
            e.preventDefault();
            if (galaxyMap.hasLayer(layerFleets)) {
                galaxyMap.removeLayer(layerFleets);
            } else {
                galaxyMap.addLayer(layerFleets);
            }
        });

        loadMapData(cx, cy);
    }

});

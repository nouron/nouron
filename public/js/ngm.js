$(document).ready( function() {

    function makeSVG(tag, attribs, value)
    {
        if (attribs === null) {
            attribs = {};
        }

        var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
        for (var k in attribs) {
            el.setAttribute(k, attribs[k]);
        }

        if (value) {
            value = document.createTextNode(value);
            el.appendChild(value);
        }
        return el;
    }

    function sprintf(format, etc) {
        var arg = arguments;
        var i = 1;
        return format.replace(/%((%)|s)/g, function (m) { return m[2] || arg[i++]; });
    }

    ngm = {
        /* defaults */
        systemsize: 50, // units
        fieldsize: 10, // pixel
        width: 700,
        height: 700,

        /**
         * calculate and (re)positioning the field selector
         *
         * @param e  mouseclick event
         * @return coords
         */
        toggleSelect: function (e) {
            range = ngm.range;
            scale = ngm.scale;
            xMin = Math.floor(ngm.coordx - ngm.range/2);
            yMin = Math.floor(ngm.coordy - ngm.range/2);

            map_offset_left = Math.floor($(ngm.selector).offset().left);
            map_offset_top  = Math.floor($(ngm.selector).offset().top);
            center_svg_left = Math.floor($(ngm.selector + " .grid-svg:nth(4)").offset().left);
            center_svg_top  = Math.floor($(ngm.selector + " .grid-svg:nth(4)").offset().top);

            x = xMin + Math.floor((e.pageX - center_svg_left) / scale);
            y = yMin + Math.floor((e.pageY - center_svg_top) / scale);

            left = center_svg_left + (x - xMin) * scale - map_offset_left;
            top_ = center_svg_top  + (y - yMin) * scale - map_offset_top;

            $(ngm.selector + ' #field-selector').remove();
            $(ngm.selector).append('<div id="field-selector" style="top:'+top_+'px; left:'+left+'px; width:'+scale+'px; height:'+scale+'px;"><!-- --></div>');

            return '['+x+','+y+',0]';
        },

        addObjectToSVGDom: function() {
            var tpl = '<svg class="grid-svg %s" width="%spx" height="%spx" style="margin-left: %spx; margin-top: %spx;"></svg>';

        },

        /**
         * create a new svg dom element and position it in the right direction
         *
         * @param direction north|east|south|west
         */
        createGridSVGDom: function(direction) {
            var tpl = '<svg class="grid-svg %s" width="%spx" height="%spx" style="margin-left: %spx; margin-top: %spx;"></svg>';
            var width  = ngm.range * ngm.scale;
            var height = ngm.range * ngm.scale;

            var halfwidth  = Math.floor(width/2);
            var halfheight = Math.floor(height/2);

            switch (direction) {
                case 'north-east':
                    class_ = 'grid-north grid-east';
                    margin_left = halfwidth;
                    margin_top = -height-halfheight;
                    break;
                case 'east':
                    class_ = 'grid-east';
                    margin_left = halfwidth;
                    margin_top = -halfheight;
                    break;
                case 'south-east':
                    class_ = 'grid-south grid-east';
                    margin_left = halfwidth;
                    margin_top = halfheight;
                    break;
                case 'north-west':
                    class_ = 'grid-north grid-west';
                    margin_left = -width-halfwidth;
                    margin_top  = -height-halfheight;
                    break;
                case 'west':
                    class_ = 'grid-west';
                    margin_left = -width-halfwidth;
                    margin_top  = -halfwidth;
                    break;
                case 'south-west':
                    class_ = 'grid-south grid-west';
                    margin_left = -width-halfwidth;
                    margin_top  = halfheight;
                    break;
                case 'north':
                    class_ = 'grid-north';
                    margin_left = -halfwidth;
                    margin_top  = -height-halfheight;
                    break;
                case 'south':
                    class_ = 'grid-south';
                    margin_left = -halfwidth;
                    margin_top  = halfheight;
                    break;
                case 'center':
                    class_ = '';
                    margin_left = -halfwidth;
                    margint_top = -halfheight;
                    break;
                default:
                    throw "invalid direction";
            }

            //console.log(class_, width, height, margin_left, margin_top);
            return sprintf(tpl, class_, width, height, margin_left, margin_top);
        },
        setNewCenterCoords: function(coordx, coordy) {
            // TODO: complete reload of all SVG maps with new coords as center
        },

        loadMapDataInArea: function(xymin, xymax) {
            // currently use of dummy data (only works in firefox!)
            // TODO: make this work with real source data!
            var data = (function () {
                var json = null;
                $.ajax({
                    jsonp: 'jsonp_callback',
                    'async': false,
                    'global': false,
                    'url': sprintf(ngm.dataSourceUri, xymin, xymax),
                    'dataType': "json",
                    'success': function (data) {
                        json = data;
                    }
                });
                return json;
            })();

            return data.filter(function(el){
                return (el.y >= xymin[1] && el.y < xymax[1] &&
                        el.x >= xymin[0] && el.x < xymax[0]);
            });
        },

        /**
         * load data for one svg map:
         *
         *        -25
         *         ^
         * -25 <- x,y -> +25
         *         v
         *        +25
         *
         * load data for 9 svg maps:
         *
         *        -75
         *         ^
         * -75 <- x,y -> +75
         *         v
         *        +75
         *
         * @param {number} coordx
         * @param {number} coordy
         * @return array
         */
        loadMapDataByCoords: function(coordx, coordy, initfullmap) {
            if (initfullmap === true) {
                data = ngm.loadMapDataInArea([coordx-ngm.range*1.5, coordy-ngm.range*1.5], [coordx+ngm.range*1.5,coordy+ngm.range*1.5]);
            } else {
                data = ngm.loadMapDataInArea([coordx-ngm.range*0.5, coordy-ngm.range*0.5], [coordx+ngm.range*0.5,coordy+ngm.range*0.5]);
            }
            return data;
        },
        /**
         * initialize whole map for first time
         * create nine separate svg dom elements which are filled with loaded data
         *
         * @param config: array of configuration options
         */
        init: function(config) {
            ngm.dataSourceUri = config.dataSourceUri;
            ngm.mode = config.mode;
            ngm.selector = config.selector;
            ngm.width  = config.width;
            ngm.height = config.height;
            ngm.coordx = config.center[0];
            ngm.coordy = config.center[1];
            ngm.scale = parseInt(config.scale);
            ngm.range = parseInt(config.range);
            ngm.layers = config.layers;
            var map = $(ngm.selector);

            map.attr('style', 'width:'+ngm.width+';height:'+ngm.height+';position:absolute');

            data_north_west = ngm.loadMapDataByCoords(ngm.coordx-ngm.range, ngm.coordy-ngm.range);
            data_north      = ngm.loadMapDataByCoords(ngm.coordx, ngm.coordy-ngm.range);
            data_north_east = ngm.loadMapDataByCoords(ngm.coordx+ngm.range, ngm.coordy-ngm.range);

            map.append(ngm.createGridSVGDom('north-west'));
            ngm.fillWithContent('north-west', data_north_west);
            map.append(ngm.createGridSVGDom('north'));
            ngm.fillWithContent('north', data_north);
            map.append(ngm.createGridSVGDom('north-east'));
            ngm.fillWithContent('north-east', data_north_east);

            data_west   = ngm.loadMapDataByCoords(ngm.coordx-ngm.range, ngm.coordy);
            data_center = ngm.loadMapDataByCoords(ngm.coordx, ngm.coordy);
            data_east   = ngm.loadMapDataByCoords(ngm.coordx+ngm.range, ngm.coordy);

            map.append(ngm.createGridSVGDom('west'));
            ngm.fillWithContent('west', data_west);
            map.append(ngm.createGridSVGDom('center'));
            ngm.fillWithContent('center', data_center);
            map.append(ngm.createGridSVGDom('east'));
            ngm.fillWithContent('east', data_east);

            data_south_west = ngm.loadMapDataByCoords(ngm.coordx-ngm.range, ngm.coordy+ngm.range);
            data_south      = ngm.loadMapDataByCoords(ngm.coordx, ngm.coordy+ngm.range);
            data_south_east = ngm.loadMapDataByCoords(ngm.coordx+ngm.range, ngm.coordy+ngm.range);

            map.append(ngm.createGridSVGDom('south-west'));
            ngm.fillWithContent('south-west', data_south_west);
            map.append(ngm.createGridSVGDom('south'));
            ngm.fillWithContent('south', data_south);
            map.append(ngm.createGridSVGDom('south-east'));
            ngm.fillWithContent('south-east', data_south_east);

            map.append('<div id="push-north" class="grid-push grid-push-north">&#x25B2;</div>');
            map.append('<div id="push-west" class="grid-push grid-push-west">&#x25C0;</div>');
            map.append('<div id="push-east" class="grid-push grid-push-east">&#x25B6;</div>');
            map.append('<div id="push-south" class="grid-push grid-push-south">&#x25BC;</div>');

            $(ngm.selector + ' .grid-push').on('click', function(e) {
                e.preventDefault();
                var id = $(this).attr('id');
                console.log(id);
                switch (id) {
                    case 'push-east':
                        $(ngm.selector + ' .grid-svg').each(function(){
                            left = parseInt($(this).css('margin-left').replace('px', ''));
                        });
                        ngm.addSystems('east');
                        break;
                    case 'push-west':
                        $(ngm.selector + ' .grid-svg').each(function(){
                            left = parseInt($(this).css('margin-left').replace('px', ''));
                        });
                        ngm.addSystems('west');
                        break;
                    case 'push-north':
                        $(ngm.selector + ' .grid-svg').each(function(){
                            top = parseInt($(this).css('margin-top').replace('px', ''));
                        });
                        ngm.addSystems('north');
                        break;
                    case 'push-south':
                        $(ngm.selector + ' .grid-svg').each(function(){
                            top = parseInt($(this).css('margin-top').replace('px', ''));
                        });
                        ngm.addSystems('south');
                        break;
                    default:
                        throw "invalid push direction";
                }
            });

            $(".grid-svg").on('click', function(e) {
                coords = ngm.toggleSelect(e);
                console.log(coords);
            });
        },
        /**
         *
         * @param direction north|east|south|west
         * @param data
         */
        fillWithContent: function(direction, data) {

            switch (direction) {
                case 'north-east':
                    targetsvg = $('.grid-north.grid-east');
                    break;
                case 'east':
                    targetsvg = $('.grid-east').not('.grid-north')
                                               .not('.grid-south');
                    break;
                case 'south-east':
                    targetsvg = $('.grid-south.grid-east');
                    break;
                case 'north-west':
                    targetsvg = $('.grid-north.grid-west');
                    break;
                case 'west':
                    targetsvg = $('.grid-west').not('.grid-north')
                                               .not('.grid-south');
                    break;
                case 'south-west':
                    targetsvg = $('.grid-south.grid-west');
                    break;
                case 'north':
                    targetsvg = $('.grid-north').not('.grid-west')
                                                .not('.grid-east');
                    break;
                case 'south':
                    targetsvg = $('.grid-south').not('.grid-west')
                                                    .not('.grid-east');
                    break;
                case 'center':
                    targetsvg = $('.grid-svg').not('.grid-north')
                                              .not('.grid-east')
                                              .not('.grid-south')
                                              .not('.grid-west');
                    break;
                default:
                    throw "invalid direction given";
            }

            ngm.drawGrid(targetsvg[0]);
            var test = ngm.layers;

            for (i=0; i<ngm.layers.length; i++) {
                var objects = data.filter(function(elem){return elem.layer==i;});
                ngm.drawLayerObjects(targetsvg[0], ngm.layers[i], objects);
            }
        },
        /**
         * draw basic grid for one svg dom element
         *
         * @param targetDomElement  svg dom element to draw inside
         */
        drawGrid: function(targetDomElement) {
            max = Math.floor(ngm.range/10) * ngm.scale;

            // horizontal lines
            var group = makeSVG('g', {'class': 'ngm-grid-layer'});
            for (var i=0; i<10; i++) {
                group.appendChild(makeSVG('line', {
                    x1: 0,
                    y1: i*max,
                    x2: ngm.range*ngm.scale,
                    y2: i*max,
                    stroke: '#222222',
                    'stroke-width': '1px',
                    'fill-opacity':'0'
                }));
            }

            // vertical lines
            for (var j=0; j<10; j++) {
                group.appendChild(makeSVG('line', {
                    x1: j*max,
                    y1: 0,
                    x2: j*max,
                    y2: ngm.range*ngm.scale,
                    stroke: '#222222',
                    'stroke-width': '1px',
                    'fill-opacity':'0'
                }));
            }

            targetDomElement.appendChild(group);

        },
        /**
         *
         * @param targetDomElement
         * @param layerConfig
         * @param layerObjects
         */
        drawLayerObjects: function(targetDomElement, layerConfig, layerObjects){
            var group = makeSVG('g', {'class': layerConfig.class});
            if (layerConfig.objectDefaultShape == 'circle') {
                for (var i=0; i<layerObjects.length; i++) {
                    var r = Math.floor(ngm.scale/2);
                    attribs = layerObjects[i].attribs;
                    group.appendChild(makeSVG('circle', {
                        'title': attribs.title+'('+layerObjects[i].x+','+layerObjects[i].y+')',
                        'cx': parseInt(layerObjects[i].x) % ngm.range*ngm.scale+r,
                        'cy': parseInt(layerObjects[i].y) % ngm.range*ngm.scale+r,
                        'r':  r,
                        'fill': 'transparent',
                        'stroke-width': '1',
                        'stroke': "#999",
                        'class': attribs.class,
                        'data-x': layerObjects[i].x,
                        'data-y': layerObjects[i].y
                    }));
                }
            } else {
                for (var j=0; j<layerObjects.length; j++) {
                    attribs = layerObjects[j].attribs;
                    group.appendChild(makeSVG('rect', {
                        'title': attribs.title,
                        'x': parseInt(layerObjects[j].x) % ngm.range*ngm.scale,
                        'y': parseInt(layerObjects[j].y) % ngm.range*ngm.scale,
                        'width': ngm.scale,
                        'height': ngm.scale,
                        'fill': 'grey',
                        'class': attribs.class,
                        'data-x': layerObjects[j].x,
                        'data-y': layerObjects[j].y
                    }));
                }
            }
            targetDomElement.appendChild(group);
        },
        /**
         * this will load and add new systems (3 at a time) for the given direction
         *
         * @param direction north|east|south|west
         */
        addSystems: function(direction) {
            var map = $(ngm.selector);
            var delta = ngm.range;
            if (direction == 'east') {
                $('.grid-svg').animate({marginLeft: "-="+delta*ngm.scale+'px'}, 500);
                $(ngm.selector+' #field-selector').animate({marginLeft: "-="+delta*ngm.scale+'px'}, 500);
                ngm.coordx = ngm.coordx+delta;
                console.log('new center xy:', ngm.coordx, ngm.coordy);

                setTimeout(function() {
                    // delete west
                    $('.grid-west').remove();
                    // center -> west
                    elements = document.querySelectorAll('.grid-svg:not(.grid-west):not(.grid-east)');
                    for (i=0; i<elements.length; i++) {
                        oldclass = elements[i].getAttribute('class');
                        newclass = oldclass + ' grid-west';
                        elements[i].setAttribute('class', newclass);
                    }
                    // east -> center
                    elements = document.querySelectorAll('.grid-east');
                    for (i=0; i<elements.length; i++) {
                        oldclass = elements[i].getAttribute('class');
                        newclass = oldclass.replace('grid-east', '')
                                           .replace('  ', ' ');
                        elements[i].setAttribute('class', newclass);
                    }
                    // create new
                    data_north_east = ngm.loadMapDataByCoords(ngm.coordx+delta, ngm.coordy-delta);
                    data_east       = ngm.loadMapDataByCoords(ngm.coordx+delta, ngm.coordy);
                    data_south_east = ngm.loadMapDataByCoords(ngm.coordx+delta, ngm.coordy+delta);

                    map.append(ngm.createGridSVGDom('north-east'));
                    ngm.fillWithContent('north-east', data_north_east);
                    map.append(ngm.createGridSVGDom('east'));
                    ngm.fillWithContent('east', data_east);
                    map.append(ngm.createGridSVGDom('south-east'));
                    ngm.fillWithContent('south-east', data_south_east);

                }, 500);

            } else if (direction == 'west') {

                $('.grid-svg').animate({marginLeft: "+="+delta*ngm.scale+'px'}, 500);
                $(ngm.selector+' #field-selector').animate({marginLeft: "+="+delta*ngm.scale+'px'}, 500);
                ngm.coordx = ngm.coordx-delta;
                console.log('new center xy:', ngm.coordx, ngm.coordy);
                setTimeout(function() {
                    // delete east
                    $('.grid-east').remove();
                    // center -> east
                    elements = document.querySelectorAll('.grid-svg:not(.grid-west):not(.grid-east)');
                    for (i=0; i<elements.length; i++) {
                        oldclass = elements[i].getAttribute('class');
                        newclass = oldclass + ' grid-east';
                        elements[i].setAttribute('class', newclass);
                    }
                    // west -> center
                    elements = document.querySelectorAll('.grid-west');
                    for (i=0; i<elements.length; i++) {
                        oldclass = elements[i].getAttribute('class');
                        newclass = oldclass.replace('grid-west', '')
                                           .replace('  ', ' ');
                        elements[i].setAttribute('class', newclass);
                    }

                    // create new
                    data_north_west = ngm.loadMapDataByCoords(ngm.coordx-delta, ngm.coordy-delta);
                    data_west       = ngm.loadMapDataByCoords(ngm.coordx-delta, ngm.coordy);
                    data_south_west = ngm.loadMapDataByCoords(ngm.coordx-delta, ngm.coordy+delta);

                    map.append(ngm.createGridSVGDom('north-west'));
                    ngm.fillWithContent('north-west', data_north_west);
                    map.append(ngm.createGridSVGDom('west'));
                    ngm.fillWithContent('west', data_west);
                    map.append(ngm.createGridSVGDom('south-west'));
                    ngm.fillWithContent('south-west', data_south_west);

                }, 500);

            } else if (direction == 'north') {

                $('.grid-svg').animate({marginTop: "+="+delta*ngm.scale+'px'}, 500);
                $(ngm.selector+' #field-selector').animate({marginTop: "+="+delta*ngm.scale+'px'}, 500);
                ngm.coordy = ngm.coordy-delta;
                console.log('new center xy:', ngm.coordx, ngm.coordy);
                setTimeout(function() {
                    // delete south
                    $('.grid-south').remove();

                    // center -> south
                    elements = document.querySelectorAll('.grid-svg:not(.grid-north):not(.grid-south)');
                    for (i=0; i<elements.length; i++) {
                        oldclass = elements[i].getAttribute('class');
                        newclass = oldclass + ' grid-south';
                        elements[i].setAttribute('class', newclass);
                    }
                    // north -> center
                    elements = document.querySelectorAll('.grid-north');
                    for (i=0; i<elements.length; i++) {
                        oldclass = elements[i].getAttribute('class');
                        newclass = oldclass.replace('grid-north', '')
                                           .replace('  ', ' ');
                        elements[i].setAttribute('class', newclass);
                    }

                    // create new
                    data_north_west = ngm.loadMapDataByCoords(ngm.coordx-delta, ngm.coordy-delta);
                    data_north      = ngm.loadMapDataByCoords(ngm.coordx, ngm.coordy-delta);
                    data_north_east = ngm.loadMapDataByCoords(ngm.coordx+delta, ngm.coordy-delta);

                    map.append(ngm.createGridSVGDom('north-west'));
                    ngm.fillWithContent('north-west', data_north_west);
                    map.append(ngm.createGridSVGDom('north'));
                    ngm.fillWithContent('north', data_north);
                    map.append(ngm.createGridSVGDom('north-east'));
                    ngm.fillWithContent('north-east', data_north_east);
                }, 500);

            } else if (direction == 'south') {

                $('.grid-svg').animate({marginTop: "-="+delta*ngm.scale+'px'}, 500);
                $(ngm.selector+' #field-selector').animate({marginTop: "-="+delta*ngm.scale+'px'}, 500);
                ngm.coordy = ngm.coordy+delta;
                console.log('new center xy:', ngm.coordx, ngm.coordy);
                setTimeout(function() {
                    // delete north
                    $('.grid-north').remove();

                    // center -> north
                    elements = document.querySelectorAll('.grid-svg:not(.grid-north):not(.grid-south)');
                    for (i=0; i<elements.length; i++) {
                        oldclass = elements[i].getAttribute('class');
                        newclass = oldclass + ' grid-north';
                        elements[i].setAttribute('class', newclass);
                    }
                    // south -> center
                    elements = document.querySelectorAll('.grid-south');
                    for (i=0; i<elements.length; i++) {
                        oldclass = elements[i].getAttribute('class');
                        newclass = oldclass.replace('grid-south', '')
                                               .replace('  ', ' ');
                        elements[i].setAttribute('class', newclass);
                    }

                    // create new
                    data_south_west = ngm.loadMapDataByCoords(ngm.coordx-delta, ngm.coordy+delta);
                    data_south      = ngm.loadMapDataByCoords(ngm.coordx, ngm.coordy+delta);
                    data_south_east = ngm.loadMapDataByCoords(ngm.coordx+delta, ngm.coordy+delta);

                    map.append(ngm.createGridSVGDom('south-west'));
                    ngm.fillWithContent('south-west', data_south_west);
                    map.append(ngm.createGridSVGDom('south'));
                    ngm.fillWithContent('south', data_south);
                    map.append(ngm.createGridSVGDom('south-east'));
                    ngm.fillWithContent('south-east', data_south_east);
                }, 500);
            }
        },
        removeSystem: function(direction) {

        },

        exportMap: function() {
            var objects_data = [];
            $('.ngm svg g').children().each(function(i, item){
                var layer_class = $(this).parent().attr('class');
                if (this.tagName == 'rect' || this.tagName == 'circle') {
                    objects_data.push({
                        'attr': {
                            'class' : $(this).attr('class'),
                            'title' : $(this).attr('title')
                        },
                        'x' : $(this).data('x'),
                        'y' : $(this).data('y')
                    });
                }
            });
            return objects_data;
        }
    };

});

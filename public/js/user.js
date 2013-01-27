$(document).ready(function () {
    max = $('#maxDiplomats').text();
    // einen diplomat zum kontakt hinzufügen
    $('.diplomats .addDiplomat').click(function(e){
        e.preventDefault();
        $('.diplomats a').hide();
        parent = $(this).parent();
        icons = parent.children('img');
        url = $(this).attr('href');
        $.post(url, null, function(data) {
            icons.last().clone().prependTo(parent);
            $('.diplomats a').show();
            count = parseInt( $('#usedDiplomats').text() ) + 1;
            console.log(count);
            $('#usedDiplomats').text(count);
            if (count >= max) {
                $('.diplomats .addDiplomat').hide()
            }
        }, 'json');
    });

    // einen diplomat vom kontakt entfernen
    $('.diplomats .removeDiplomat').click(function(e){
        e.preventDefault();
        $('.diplomats a').hide();
        removeIcon = $(this);
        parent = $(this).parent();
        icons = parent.children('img');
        url = $(this).attr('href');
        $.post(url, null, function(data) {
            icons.last().remove();
            $('.diplomats a').show();
            if (icons.length - 1 <= 1) {
                removeIcon.hide();
            }
            count = parseInt( $('#usedDiplomats').text() ) - 1;
            console.log(count);
            $('#usedDiplomats').text(count);
        }, 'json');

    });

    // wenn ein oder weniger Diplomaten zugewiesen verberge removeIcon
    $('.diplomats').each(function(index) {
        count = $(this).children('img').length;
        if (count <= 1) {
            $(this).children('.removeDiplomat').hide();
        }
    });
    
    // wenn alle Diplomaten verbraucht sind können keine mehr hinzugefügt werden

    count = $('.diplomats img').length;
    if (count >= max) {
        $('.diplomats .addDiplomat').hide()
    }
    
    $('.removeContact').click(function(e){
        e.preventDefault();
        url = $(this).attr('href');
        $.post(url, null, function(data) {
            /*count = parseInt( $('#usedDiplomats').text() ) - 1;
            console.log(count);
            $('#usedDiplomats').text(count);*/
            $(this).parent().parent().hide();
        }, 'json');
        
    })
    
    // spider chart
    
    function showSpiderChart() {
        
        var labels = $("#spider-chart-1-data tr th");
        var values = $("#spider-chart-1-data tr td");
        
        var options = {
                series: {
                    spider: {
                        active: true,
                        connection: { width: 1 },
                        legs: {
                            font: "11px Verdana",
                            data: [{label: labels[0].innerHTML}, {label: labels[1].innerHTML}, {label: labels[2].innerHTML}, {label: labels[3].innerHTML}],
                            legScaleMax: 1,
                            legScaleMin: 0.8,
                            legStartAngle: -90
                        }, spiderSize: 0.9,
                        scaleMode: "others"
                    }
                }, grid: {
                    hoverable: false,
                    clickable: false,
                    tickColor: "rgba(0,0,0,0.2)",
                    mode: "spider"
                }
            };
        
        data = [{
            label: "",
            data: [[0, values[0].innerHTML], [1, values[1].innerHTML], [2, values[2].innerHTML], [3, values[3].innerHTML]],
            spider: {
                show: true,
                lineWidth: 0
            }
        }];
        
        $.plot($("#spider-chart-1"), data, options);
        
        var labels = $("#spider-chart-2-data tr th");
        var values = $("#spider-chart-2-data tr td");
        
        var options = {
            series: {
                spider: {
                    active: true,
                    connection: { width: 1 },
                    legs: {
                        font: "11px Verdana",
                        data: [{label: labels[0].innerHTML}, {label: labels[1].innerHTML}, {label: labels[2].innerHTML}, {label: labels[3].innerHTML}, {label: labels[4].innerHTML}],
                        legScaleMax: 1,
                        legScaleMin: 0.8,
                        legStartAngle: -90
                    }, spiderSize: 0.9,
                    scaleMode: "others"
                }
            }, grid: {
                hoverable: false,
                clickable: false,
                tickColor: "rgba(0,0,0,0.2)",
                mode: "spider"
            }
        };

        data = [{
            label: "",
            data:   [[0, values[0].innerHTML], [1, values[1].innerHTML], [2, values[2].innerHTML], [3, values[3].innerHTML], [4, values[4].innerHTML]],
            spider: {
                show: true,
                lineWidth: 0
            }
        }];


        $.plot($("#spider-chart-2"), data, options);
    }
    
    if ( $("#spider-chart-1").length != 0 ) {
        showSpiderChart();
    }
});
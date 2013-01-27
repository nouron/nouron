/**
 * Nouron - A free Space Opera MMO Browsergame Copyright (c) 2008-2009, Mario
 * Gehnke LICENSE This source file is licensed under the Creative Commons BY NC
 * SA License For more info see
 * {@link http://creativecommons.org/licenses/by-nc-sa/3.0/de/}
 * 
 * @author Mario Gehnke {@link mailto:mario.gehnke@gmx.de}
 * @copyright Copyright (c) 2008-2009, Mario Gehnke
 * @license Creative Commons BY NC SA
 *          {@link http://creativecommons.org/licenses/by-nc-sa/3.0/de/}
 */

$(function() {
    $("input").blur(function() {
        var formElementId = $(this).attr('id');
        doValidation(formElementId);
    });
});

function doValidation(id) {
    var url = '/public/auth/validationform'
    var data = {}
    $("input").each(function() {
        data[$(this).attr('name')] = $(this).val();
    });
    $.post(url, data, function(resp) {
        $("#" + id).parent().find('.errors').remove();
        $("#" + id).parent().append(getErrorHtml(resp[id], id))
    }, 'json');
}

function getErrorHtml(formErrors, id) {
    var o = '<ul id="errors_' + id + '" class="errors">';
    for (errorKey in formErrors) {
        o += '<li>' + formErrors[errorKey] + '</li>';
    }

    o += '</ul>';
    return o;
}
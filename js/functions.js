redcapChartField.looseJsonParse = function(ob) {
    return Function('"use strict"; return (' + ob + ')')();
}

redcapChartField.getChartParams = function(settings) {
    var params = {};
    $.each(settings, function(key, value) {
        if (redcapChartField.configFields[key].type === 'json') {
            value = redcapChartField.looseJsonParse(value);
        }

        params[key.substr(6)] = value;
    });

    return params;
}

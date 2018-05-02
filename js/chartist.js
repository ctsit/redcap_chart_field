$(document).ready(function() {
    $.each(redcapChartField.fields, function(fieldName, settings) {
        var params = redcapChartField.getChartParams(settings);
        $('#' + fieldName + '-tr').html('<td colspan="' + redcapChartField.colspan + '"><div id="' + fieldName + '-chart"></div></td>');
        Chartist[settings.chart_type]('#' + fieldName + '-chart', params.data, params.options, params.responsive_options);
    });
});

$(document).ready(function() {
    $.each(redcapChartField.fields, function(fieldName, settings) {
        var params = redcapChartField.getChartParams(settings);
        var $chart = $('<canvas id="' + fieldName + '-chart"></canvas>');
        var ctx = $chart[0].getContext('2d');

        if (params.width) {
            ctx.canvas.width = params.width;
        }

        if (params.height) {
            ctx.canvas.height = params.height;
        }

        $('#' + fieldName + '-tr').html($chart);
        $chart.wrap('<td colspan="' + redcapChartField.colspan + '"></td>');

        var chart = new Chart(ctx, params);
    });
});

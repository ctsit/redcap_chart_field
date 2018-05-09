$(document).ready(function() {
    $.each(redcapChartField.fields, function(fieldName, settings) {
        var params = redcapChartField.getChartParams(settings);
        var $chart = $('<canvas id="' + fieldName + '-chart"></canvas>');
        var ctx = $chart[0].getContext('2d');

        var wrapperClasses = 'chartjs-wrapper';
        if (params.width) {
            ctx.canvas.width = params.width;
        }
        else {
            wrapperClasses += ' chartjs-wrapper-full-width';
        }

        if (params.height) {
            ctx.canvas.height = params.height;
        }

        $('#' + fieldName + '-tr').html($chart);

        // Wrapping chart with a "table-layout: fixed" table element to make
        // chart responsive.
        $chart.wrap('<td colspan="' + redcapChartField.colspan + '"><table class="' + wrapperClasses + '"></table></td>');

        var chart = new Chart(ctx, params);
    });
});

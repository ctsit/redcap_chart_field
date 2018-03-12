$(document).ready(function() {
    $.each(redcapChartField.fields, function(fieldName, settings) {
        $('#' + fieldName + '-tr').append('<td colspan="2"><canvas id="' + fieldName + '-chart"></canvas></td>');
        var chart = new Chart(fieldName + '-chart', redcapChartField.getChartParams(settings));
    });
})

$(document).ready(function() {
    var fieldsVisited = {};
    var chartFieldsBranchingLogic = function() {
        var fieldType = $fieldSelector.val();

        if (fieldType === 'chart') {
            $('.chart-property').show();
            $('#div_field_annotation').hide();
        }
        else {
            $misc.val(miscContents);
            $('.chart-property').hide();

            if (fieldType !== 'section_header') {
                $('#div_field_annotation').show();
            }
        }
    }

    // Adding extra config fields.
    var $label = $('#quesTextDiv > table > tbody > tr > td').first().children('div').first();
    $label.after(redcapCharts.onlineDesignerContents);

    // Adding chart option.
    var $fieldSelector = $('select[name="field_type"]');
    $fieldSelector.append('<option value="chart">Chart</option>');

    // Showing or hiding chart fields based on field type selection.
    $fieldSelector.change(chartFieldsBranchingLogic);
    $('#div_add_field').on('dialogopen', function() {
        chartFieldsBranchingLogic();

        // Skip if this is not a chart field.
        if ($fieldSelector.val() !== 'chart') {
            return;
        }

        // Skip if this form has been visited already.
        var fieldName = $('input[name="field_name"]').val();
        if (typeof fieldsVisited[fieldName] !== 'undefined') {
            return;
        }

        fieldsVisited[fieldName] = true;
        $('[name="field_annotation"]').val('');

        // Setting up default values.
        if (typeof redcapCharts.fields[fieldName] !== 'undefined') {
            $.each(redcapCharts.fields[fieldName], function(key, value) {
                $target = $('[name="' + key + '"');

                switch (redcapCharts.configFields[key]) {
                    case 'json':
                        $target.val(JSON.stringify(value, null, 4));
                        break;
                    case 'select':
                        $target.children('option[value="' + value +'"]').prop('selected', true);
                        break;
                }
            });
        }
    });

    // Validating JSON fields.
    $('.json-field').change(function() {
        try {
            var ob = JSON.parse($(this).val());
            var value = JSON.stringify(ob, null, 4);
        }
        catch (err) {
            var value = '';
        }

        $(this).val(value);
    });
});

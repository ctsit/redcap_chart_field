$(document).ready(function() {
    var fieldsVisited = {};
    var chartFieldsBranchingLogic = function() {
        var fieldType = $fieldSelector.val();

        if (fieldType === 'chart') {
            $('.chart-property').show();
            $('#div_field_annotation').hide();
        }
        else {
            $('.chart-property').hide();

            if (fieldType !== 'section_header') {
                $('#div_field_annotation').show();
            }
        }
    }

    // Adding extra config fields.
    var $label = $('#quesTextDiv > table > tbody > tr > td').first().children('div').first();
    $label.after(redcapChartField.onlineDesignerContents);

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

        $('[name="field_annotation"]').val('');

        // Setting up default values.
        if (typeof redcapChartField.fields[fieldName] !== 'undefined') {
            $.each(redcapChartField.fields[fieldName], function(key, value) {
                $target = $('[name="' + key + '"');

                switch (redcapChartField.configFields[key].type) {
                    case 'json':
                        $target.val(value);
                        break;
                    case 'select':
                        $target.children('option[value="' + value +'"]').prop('selected', true);
                        break;
                }
            });
        }

        $('.ui-button-text').each(function() {
            if ($(this).text() !== 'Save') {
                return;
            }

            // TODO: add validation.

            $(this).parent().click(function() {
                if ($fieldSelector.val() === 'chart') {
                    fieldsVisited[fieldName] = true;
                }
            });

            return false;
        });
    });

    // Validating JSON fields.
    $('.json-field').change(function() {
        try {
            redcapChartField.looseJsonParse($(this).val());
        }
        catch (err) {
            $(this).val('');
        }
    });
});

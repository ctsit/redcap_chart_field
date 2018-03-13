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
        // Branching logic on chart fields.
        chartFieldsBranchingLogic();

        $('.piping-helper a').click(function() {
            // Opening piping helper modal.
            pipingExplanation();
            return false;
        });

        // Adding validation on submit.
        var buttons = $(this).dialog('option', 'buttons');
        $.each(buttons, function(i, button) {
            if (button.text !== 'Save') {
                return;
            }

            var callback = button.click;
            buttons[i].click = function() {
                if ($fieldSelector.val() !== 'chart') {
                    callback();
                }

                var success = true;
                $('.chart-property-input').each(function() {
                    if (!redcapChartField.validateChartPropertyInput(this)) {
                        success = false;
                        return false;
                    }
                });

                if (!success) {
                    return false;
                }

                fieldsVisited[fieldName] = true;
                callback();
            }

            return false;
        });

        $(this).dialog('option', 'buttons', buttons);

        // Skip if this is not a chart field.
        if ($fieldSelector.val() !== 'chart') {
            return;
        }

        // Making sure misc field is empty.
        $('[name="field_annotation"]').val('');

        // Skip if this form has been visited already.
        var fieldName = $('input[name="field_name"]').val();
        if (typeof fieldsVisited[fieldName] !== 'undefined') {
            return;
        }

        // Setting up default values.
        if (typeof redcapChartField.fields[fieldName] !== 'undefined') {
            $.each(redcapChartField.fields[fieldName], function(key, value) {
                $target = $('[name="' + key + '"');

                switch (redcapChartField.configFields[key].type) {
                    case 'int':
                    case 'json':
                    case 'array':
                        $target.val(value);
                        break;
                    case 'select':
                        $target.children('option[value="' + value +'"]').prop('selected', true);
                        break;
                }
            });
        }
    });
});

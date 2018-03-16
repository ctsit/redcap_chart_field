$(document).ready(function() {
    var fieldsVisited = {};
    var chartFieldsBranchingLogic = function() {
        var fieldType = $fieldSelector.val();

        if (fieldType === 'chart') {
            // Showing chart fields.
            $('.chart-property').show();

            // Hiding misc and label fields.
            $('#div_field_annotation').hide();
            $label.hide();
        }
        else {
            // Hiding chart fields.
            $('.chart-property').hide();

            // Showing misc and label fields.
            if (fieldType !== 'section_header') {
                $('#div_field_annotation').show();
                $label.show();
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
                    return;
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

        var fieldName = $('input[name="field_name"]').val();
        if (!fieldName) {
            // Handling the case when the user opens a new field dialog just
            // after adding/editing a chart field.

            // Reseting chart fields values.
            $('.chart-property-input').each(function() {
                if ($(this).is('select')) {
                    $(this).prop('selectedIndex', 0);
                }
                else {
                    $(this).val('');
                }
            });

            // Unselecting chart field option.
            $fieldSelector.prop('selectedIndex', 0);
            $fieldSelector.change();

            return;
        }

        // Skip if this form has been visited already.
        if (typeof fieldsVisited[fieldName] !== 'undefined') {
            return;
        }

        // Setting up default values.
        if (typeof redcapChartField.fields[fieldName] !== 'undefined') {
            $.each(redcapChartField.fields[fieldName], function(key, value) {
                $target = $('[name="' + key + '"');

                switch (redcapChartField.configFields[key].type) {
                    case 'select':
                        $target.children('option[value="' + value +'"]').prop('selected', true);
                        break;
                    default:
                        $target.val(value);
                }
            });
        }
    });
});

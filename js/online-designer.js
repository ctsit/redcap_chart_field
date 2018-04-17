$(document).ready(function() {
    var fieldsVisited = {};

    // Adding chart config fields to markup.
    $('#quesTextDiv > table > tbody > tr > td:first > div:first').append(redcapChartField.onlineDesignerContents.left);
    $('#quesTextDiv > table > tbody > tr > td:last').append(redcapChartField.onlineDesignerContents.right);

    var $fieldSelector = $('select[name="field_type"]');
    var $chartFlag = $('input[name="is_chart"]');

    // Showing or hiding chart fields based on field type selection and the
    // chart flag.
    $fieldSelector.change(redcapChartField.doBranching);
    $chartFlag.change(redcapChartField.doBranching);

    $('#div_add_field').on('dialogopen', function() {
        var fieldName = $('input[name="field_name"]').val();

        if (fieldName && typeof redcapChartField.fields[fieldName] !== 'undefined') {
            // Turning chart flag on.
            $chartFlag.prop('checked', true);

            // Making sure misc field is empty.
            $('[name="field_annotation"]').val('');

            if (typeof fieldsVisited[fieldName] === 'undefined') {
                // Setting up default values.
                $.each(redcapChartField.fields[fieldName], function(key, value) {
                    $target = $('[name="' + key + '"');

                    if (redcapChartField.configFields[key].type === 'select') {
                        $target.children('option[value="' + value +'"]').prop('selected', true);
                    }
                    else {
                        $target.val(value);
                    }
                });
            }
        }
        else {
            // Making sure all chart fields are blank if the current form is not
            // a chart field.
            $chartFlag.prop('checked', false);
            $('.chart-property-input').val('');
        }

        // Branching logic on chart fields.
        redcapChartField.doBranching();

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
                // Updating field name value.
                fieldName = $('input[name="field_name"]').val();

                if ($fieldSelector.val() !== 'descriptive' || !$chartFlag.is(':checked')) {
                    callback();
                    delete redcapChartField.fields[fieldName];
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

                callback();

                fieldsVisited[fieldName] = true;
                if (typeof redcapChartField.fields[fieldName] === 'undefined') {
                    redcapChartField.fields[fieldName] = true;
                }
            }

            return false;
        });

        $(this).dialog('option', 'buttons', buttons);
    });

    // Handling chart field copy.
    var copyingChartField = false;
    var copyFieldDoOld = copyFieldDo;

    copyFieldDo = function(fieldName) {
        if (typeof redcapChartField.fields[fieldName] !== 'undefined') {
            copyingChartField = fieldName;
        }

        copyFieldDoOld(fieldName);
    }

    $(document).ajaxComplete(function(event, xhr, settings) {
        if (!copyingChartField || settings.url.indexOf(app_path_webroot + 'Design/copy_field.php') !== 0) {
            return;
        }

        redcapChartField.fields[xhr.responseText] = redcapChartField.fields[copyingChartField];
        copyingChartField = false;
    });
});

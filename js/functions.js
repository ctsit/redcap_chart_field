/**
 * Decodes a JSON string with more flexible constraints.
 */
redcapChartField.looseJsonParse = function(ob) {
    return Function('"use strict"; return (' + ob + ')')();
};

/**
 * Gets and formats charts parameters from the given settings array.
 */
redcapChartField.getChartParams = function(settings) {
    var params = {};
    $.each(settings, function(key, value) {
        var type = redcapChartField.configFields[key].type;

        if (type === 'json' || type === 'array') {
            value = redcapChartField.looseJsonParse(value);
        }

        params[key.substr(6)] = value;
    });

    return params;
};

/**
 * Checks whether the given string represents a JS object.
 */
redcapChartField.isObjectStr = function(obStr) {
    try {
        var ob = redcapChartField.looseJsonParse(obStr);
        return ob && typeof ob === 'object' && ob.constructor === Object;
    }
    catch (err) {
        return false;
    }
};

/**
 * Checks whether the given string represents a JS array.
 */
redcapChartField.isArrayStr = function(arrStr) {
    try {
        var ob = redcapChartField.looseJsonParse(arrStr);
        return ob && Array.isArray(ob);
    }
    catch (err) {
        return false;
    }
};

/**
 * Validates the given configuration form input and throws an alert in case of
 * error.
 */
redcapChartField.validateChartPropertyInput = function(ob) {
    var config = redcapChartField.configFields[ob.name];
    var valid = true;
    var msg = 'This field is required.';

    if (ob.value === '') {
        valid = typeof config.required === 'undefined' || !config.required;
    }
    else {
        switch (config.type) {
            case 'array':
                msg = 'This is not a valid JS array.';
                valid = redcapChartField.isArrayStr(ob.value);
                break;

            case 'json':
                msg = 'This is not a valid JS object.';
                valid = redcapChartField.isObjectStr(ob.value);
                break;

            case 'int':
                return redcap_validate(ob, '', '', 'soft_typed', 'integer', 1);
        }
    }

    if (valid) {
        ob.style.fontWeight = 'normal';
        ob.style.backgroundColor = '#fff';
    }
    else {
        simpleDialog(msg, null, null, null, '$(\'[name="' + ob.name + '"]\').focus();');
        ob.style.fontWeight = 'bold';
        ob.style.backgroundColor = '#ffb7be';
    }

    return valid;
};

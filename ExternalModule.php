<?php
/**
 * @file
 * Provides ExternalModule class for REDCap Chart Field module.
 */

namespace REDCapChartField\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Piping;
use RCView;

define('CHARTJS_VERSION', '2.7.2');
define('CHARTIST_VERSION', '0.11.0');

/**
 * ExternalModule class for REDCap Chart Field module.
 */
class ExternalModule extends AbstractExternalModule {

    protected $lib;
    protected $cssFiles;
    protected $jsFiles = array('js/functions.js');
    protected $jsSettings = array(
        'fields' => array(),
    );

    /**
     * @inheritdoc
     */
    function redcap_every_page_before_render($project_id) {
        if (!$this->setConfigFieldsInfo($project_id)) {
            return;
        }

        global $Proj;
        foreach ($Proj->metadata as $field => $info) {
            if ($info['element_type'] == 'descriptive' && ($settings = json_decode($info['misc'], true)) && isset($settings['chart_type'])) {
                // Transfering chart metadata from misc field to JS settings array.
                $this->jsSettings['fields'][$field] = $settings;
            }
        }

        if (PAGE == 'Design/edit_field.php' && isset($_POST['field_type']) && $_POST['field_type'] == 'descriptive' && !empty($_POST['is_chart'])) {
            $misc = array();
            foreach (array_keys($this->jsSettings['configFields']) as $field) {
                if (!empty($_POST[$field])) {
                    $misc[$field] = $_POST[$field];
                }
            }

            // Using misc field to store chart metadata.
            $_POST['field_annotation'] = json_encode($misc, JSON_HEX_QUOT);
        }
    }

    /**
     * @inheritdoc
     */
    function redcap_every_page_top($project_id) {
        if (PAGE == 'Design/online_designer.php' && !empty($this->lib) && !empty($_GET['page'])) {
            $this->buildConfigFormFields();
        }
    }

    /**
     * @inheritdoc
     */
    function redcap_data_entry_form_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $repeat_instance = 1) {
        $this->loadCharts($instrument, $record, $event_id, $repeat_instance);
    }

    /**
     * @inheritdoc
     */
    function redcap_survey_page_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $survey_hash, $response_id = null, $repeat_instance = 1) {
        $this->loadCharts($instrument, $record, $event_id, $repeat_instance, true);
    }

    /**
     * Load charts for the current page.
     */
    function loadCharts($instrument, $record = null, $event_id, $instance, $is_survey = false) {
        if (empty($this->lib)) {
            return;
        }

        global $Proj;

        if (!$record) {
            $record = $_GET['id'];
        }

        if ($Proj->project['double_data_entry']) {
            global $user_rights;
            if (!empty($user_rights['double_data'])) {
                $record .= '--' . $user_rights['double_data'];
            }
        }

        // Getting the list of charts for the current form.
        $filtered = array();
        foreach ($this->jsSettings['fields'] as $field_name => $config) {
            if ($Proj->metadata[$field_name]['form_name'] != $instrument) {
                continue;
            }

            foreach ($this->jsSettings['configFields'] as $key => $info) {
                if (!empty($info['piping']) && !empty($config[$key])) {
                    // Applying Piping on chart data.
                    $config[$key] = $this->__piping($config[$key], $record, $event_id, $instrument, $instance);
                }
            }

            $filtered[$field_name] = $config;
        }

        if (!$this->jsSettings['fields'] = $filtered) {
            return;
        }

        $this->jsSettings['colspan'] = $is_survey ? 3 : 2;
        $this->loadChartsLib();
        $this->loadPageResources();
    }

    /**
     * Load charts library on the current page.
     */
    function loadChartsLib() {
        switch ($this->lib) {
            case 'chartjs':
                $this->includeJs('//cdnjs.cloudflare.com/ajax/libs/Chart.js/' . CHARTJS_VERSION . '/Chart.min.js');
                $this->cssFiles[] = 'css/' . $this->lib . '.css';
                break;

            case 'chartist':
                $this->includeJs('//cdn.jsdelivr.net/npm/chartist@' . CHARTIST_VERSION . '/dist/chartist.min.js');
                $this->includeCss('//cdn.jsdelivr.net/npm/chartist@' . CHARTIST_VERSION . '/dist/chartist.min.css');
                break;
        }

        $this->jsFiles[] = 'js/' . $this->lib . '.js';
    }

    /**
     * Set configuration fields according to the chosen library.
     */
    function setConfigFieldsInfo($project_id) {
        if (!$project_id || !($lib = $this->getProjectSetting('chart_lib', $project_id)) || !($lib_link = $this->getChartLibraryLink($lib))) {
            return false;
        }

        $mod_link = $this->getModuleLink();

        $helper = 'JS objects only. Check out ' . $mod_link . ' documentation to know how to fill out chart configuration fields.';
        $helper .= ' Check also ' . $lib_link . ' oficial website for documentation and examples.';

        $config = array(
            'chart_type' => array(
                'type' => 'select',
                'label' => 'Chart type',
                'required' => true,
            ),
            'chart_data' => array(
                'type' => 'json',
                'label' => 'Chart data',
                'required' => true,
                'helper' => $helper,
                'piping' => true,
            ),
            'chart_options' => array(
                'type' => 'json',
                'label' => 'Chart options',
                'helper' => $helper,
                'piping' => true,
                'is_additional_config' => true,
            ),
        );

        switch ($lib) {
            case 'chartjs':
                foreach (array('chart_width' => 'Canvas width', 'chart_height' => 'Canvas height') as $key => $label) {
                    $config[$key] = array(
                        'type' => 'int',
                        'label' => $label,
                        'is_additional_config' => true,
                    );
                }

                $config['chart_type']['choices'] = array(
                    'line' => 'Line',
                    'bar' => 'Bar',
                    'horizontalBar' => 'Horizontal bar',
                    'radar' => 'Radar',
                    'pie' => 'Pie',
                    'doughnut' => 'Doughnut',
                    'polarArea' => 'Polar area',
                    'bubble' => 'Bubble',
                    'scatter' => 'Scatter',
                );

                break;

            case 'chartist':
                $config['chart_responsive_options'] = array(
                    'type' => 'array',
                    'label' => 'Chart responsive options',
                    'helper' => str_replace('objects', 'arrays', $helper),
                    'piping' => true,
                    'is_additional_config' => true,
                );

                $config['chart_type']['choices'] = array(
                    'Bar' => 'Bar',
                    'Line' => 'Line',
                    'Pie' => 'Pie',
                );

                break;
        }

        $this->lib = $lib;
        $this->jsSettings['configFields'] = $config;

        return true;
    }

    /**
     * Gets a labeled link to module's GitHub page.
     */
    function getModuleLink() {
        $config = $this->getConfig();
        return RCView::a(array('href' => 'https://github.com/ctsit/redcap_chart_field', 'target' => '_blank'), RCView::b($config['name']));
    }

    /**
     * Gets a labeled link to the given library's official website.
     */
    function getChartLibraryLink($lib) {
        $config = $this->getConfig();
        foreach ($config['project-settings'] as $setting) {
            if ($setting['key'] != 'chart_lib') {
                continue;
            }

            foreach ($setting['choices'] as $option) {
                if ($option['value'] == $lib) {
                    return $option['name'];
                }
            }

            break;
        }

        return false;
    }

    /**
     * Builds configuration fields for online designer.
     */
    protected function buildConfigFormFields() {
        global $lang;

        $field = RCView::checkbox(array(
            'name' => 'is_chart',
            'class' => 'x-form-field chart-config-input',
        ));

        $output = array(
            'left' => RCView::div(array('id' => 'chart-flag', 'class' => 'chart-config'), RCView::b('Is chart') . ' ' . $field),
            'right' => '',
        );

        $default_classes = 'x-form-field chart-property-input chart-config-input';

        foreach ($this->jsSettings['configFields'] as $name => $info) {
            switch ($info['type']) {
                case 'select':
                    $field = RCView::select(array(
                        'name' => $name,
                        'class' => $default_classes . ' x-form-text',
                    ), array('' => '--- Select ---') + $info['choices']);
                    break;

                case 'json':
                case 'array':
                    $field = RCView::textarea(array(
                        'name' => $name,
                        'class' => $default_classes . ' x-form-textarea',
                    ));
                    break;

                case 'int':
                    $field = RCView::text(array(
                        'name' => $name,
                        'class' => $default_classes . ' x-form-text',
                    ));
                    break;
            }

            if (!empty($info['helper'])) {
                $field .= RCView::div(array('class' => 'chart-config-helper'), $info['helper']);
            }

            $label = RCView::div(array('class' => 'chart-config-label'), RCView::b($info['label']));
            $field = RCView::div(array('class' => 'chart-config chart-property'), RCView::div(array('class' => 'clearfix'), $label) . $field);
            $position = empty($info['is_additional_config']) ? 'left' : 'right';

            $output[$position] .= $field;
        }

        foreach ($output as $position => $column) {
            $output[$position] = RCView::div(array('class' => 'chart-container chart-container-' . $position), $column);
        }

        $this->jsSettings['onlineDesignerContents'] = $output;
        $this->jsFiles[] = 'js/online-designer.js';
        $this->cssFiles[] = 'css/online-designer.css';

        $this->loadPageResources();
    }

    /**
     * Loads queued scripts, CSS and JS settings for the current page.
     */
    protected function loadPageResources() {
        $this->setJsSettings();
        $this->loadScripts();
        $this->loadStyles();
    }

    /**
     * Includes local CSS files.
     */
    protected function loadStyles() {
        foreach ($this->cssFiles as $path) {
            $this->includeCss($this->getUrl($path));
        }

        $this->cssFiles = array();
    }

    /**
     * Includes local JS files.
     */
    protected function loadScripts() {
        foreach ($this->jsFiles as $path) {
            $this->includeJs($this->getUrl($path));
        }

        $this->jsFiles = array();
    }

    /**
     * Sets JS settings.
     */
    protected function setJsSettings() {
        echo '<script>redcapChartField = ' . json_encode($this->jsSettings) . ';</script>';
    }

    /**
     * Include a CSS file.
     */
    protected function includeCss($path) {
        echo '<link rel="stylesheet" href="' . $path . '">';
    }

    /**
     * Include a JS file.
     */
    protected function includeJs($path) {
        echo '<script src="' . $path . '"></script>';
    }

    /**
     * Auxiliar function to prevent piping errors on nested brackets.
     */
    protected function __piping($str, $record, $event_id, $instrument, $instance) {
        if (preg_match_all('/(["\'])(.*\].*)?\1/', $str, $matches)) {
            foreach ($matches[2] as $result) {
                $piped = Piping::replaceVariablesInLabel($result, $record, $event_id, $instance, array(), true, null, false, '', 1, false, false, $instrument);
                $str = str_replace($result, $piped, $str);
            }
        }

        return $str;
    }
}

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

    protected $cssFiles;
    protected $jsFiles = array('js/functions.js');
    protected $jsSettings = array(
        'fields' => array(),
    );

    /**
     * @inheritdoc
     */
    function redcap_every_page_before_render($project_id) {
        if (!$project_id) {
            return;
        }

        $this->jsSettings['configFields'] = $this->getConfigFieldsInfo($project_id);

        if (PAGE === 'Design/edit_field.php' && isset($_POST['field_type']) && $_POST['field_type'] == 'chart') {
            $misc = array();
            foreach (array_keys($this->jsSettings['configFields']) as $field) {
                if (!empty($_POST[$field])) {
                    $misc[$field] = $_POST[$field];
                }
            }

            $_POST['field_annotation'] = json_encode($misc);
        }

        global $Proj;
        foreach ($Proj->metadata as $field => $info) {
            if ($info['element_type'] != 'chart') {
                continue;
            }

            $this->jsSettings['fields'][$field] = json_decode($Proj->metadata[$field]['misc'], true);
            $Proj->metadata[$field]['misc'] = '';
        }
    }

    /**
     * @inheritdoc
     */
    function redcap_every_page_top($project_id) {
        if (PAGE == 'Design/online_designer.php') {
            $this->buildConfigFormFields();
        }
    }

    /**
     * @inheritdoc
     */
    function redcap_data_entry_form_top($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $repeat_instance = 1) {
        global $double_data_entry, $user_rights;
        $entry_num = $double_data_entry && $user_rights['double_data'] ? '--' . $user_rights['double_data'] : '';

        $this->loadCharts($instrument, $record . $entry_num, $event_id, $repeat_instance);
    }

    /**
     * @inheritdoc
     */
    function redcap_survey_page_top($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1) {
        $this->loadCharts($instrument, $record, $event_id, $repeat_instance);
    }

    /**
     * Load charts for the current page.
     */
    function loadCharts($instrument, $record, $event_id, $instance) {
        global $Proj;

        $filtered = array();
        foreach ($this->jsSettings['fields'] as $field_name => $config) {
            if ($Proj->metadata[$field_name]['form_name'] != $instrument) {
                continue;
            }

            foreach ($this->jsSettings['configFields'] as $key => $info) {
                if (!empty($info['piping'])) {
                    $config[$key] = $this->__piping($config[$key], $record, $event_id, $instance);
                }
            }

            $filtered[$field_name] = $config;
        }

        if (!$this->jsSettings['fields'] = $filtered) {
            return;
        }

        $this->loadChartsLib();
        $this->loadPageResources();
    }

    /**
     * Load charts library on the current page.
     */
    function loadChartsLib() {
        $lib = $this->getProjectSetting('chart_lib', $project_id);

        switch ($lib) {
            case 'chartjs':
                $this->includeJs('//cdnjs.cloudflare.com/ajax/libs/Chart.js/' . CHARTJS_VERSION . '/Chart.min.js');
                break;

            case 'chartist':
                $this->includeJs('//cdn.jsdelivr.net/npm/chartist@' . CHARTIST_VERSION . '/dist/chartist.min.js');
                $this->includeCss('//cdn.jsdelivr.net/npm/chartist@' . CHARTIST_VERSION . '/dist/chartist.min.css');
                break;
        }

        $this->jsFiles[] = 'js/' . $lib . '.js';
    }

    /**
     * Get configuration fields according to the chosen library.
     */
    function getConfigFieldsInfo($project_id) {
        $lib = $this->getProjectSetting('chart_lib', $project_id);
        $mod_link = $this->getModuleLink();
        $lib_link = $this->getChartLibraryLink($lib);

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
            ),
        );

        switch ($lib) {
            case 'chartjs':
                $config['chart_width'] = array('type' => 'int', 'label' => 'Canvas width');
                $config['chart_height'] = array('type' => 'int', 'label' => 'Canvas height');
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
                );

                $config['chart_type']['choices'] = array(
                    'Bar' => 'Bar',
                    'Line' => 'Line',
                    'Pie' => 'Pie',
                );

                break;
        }

        return $config;
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
    function getChartLibraryLink($lib = null, $project_id = null) {
        if (!$lib) {
            $lib = $this->getProjectSetting('chart_lib', $project_id);
        }

        $config = $this->getConfig();
        foreach ($config['project-settings'] as $setting) {
            if ($setting['key'] == 'chart_lib') {
                break;
            }
        }

        foreach ($setting['choices'] as $option) {
            if ($option['value'] == $lib) {
                return $option['name'];
            }
        }

        return false;
    }

    /**
     * Builds configuration fields for online designer.
     */
    protected function buildConfigFormFields() {
        global $lang;

        $piping = RCView::img(array('src' => APP_PATH_IMAGES . 'pipe_small.gif')) . ' ';
        $piping .= RCView::a(array('href' => '#'), $lang['design_456']);
        $piping = RCView::div(array('class' => 'piping-helper'), $piping);

        $output = '';
        foreach ($this->jsSettings['configFields'] as $name => $info) {
            switch ($info['type']) {
                case 'select':
                    $field = RCView::select(array(
                        'name' => $name,
                        'class' => 'x-form-text x-form-field chart-property-input',
                    ), $info['choices']);
                    break;

                case 'json':
                case 'array':
                    $field = RCView::textarea(array(
                        'name' => $name,
                        'class' => 'x-form-textarea x-form-field chart-property-input',
                    ));
                    break;

                case 'int':
                    $field = RCView::text(array(
                        'name' => $name,
                        'class' => 'x-form-text x-form-field chart-property-input',
                    ));
                    break;
            }

            if (!empty($info['helper'])) {
                $field .= RCView::div(array('class' => 'chart-property-helper'), $info['helper']);
            }

            $label = RCView::div(array('class' => 'chart-property-label'), RCView::b($info['label']));
            if (!empty($info['piping'])) {
                $label .= $piping;
            }

            $output .= RCView::div(array('class' => 'chart-property'), RCView::div(array('class' => 'clearfix'), $label) . $field);
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
    protected function __piping($str, $record, $event_id, $instance) {
        $tmp = ' [!!!TEMP!!!]';

        $str = str_replace(']', ']' . $tmp, $str);
        $str = Piping::replaceVariablesInLabel($str, $record, $event_id, $instance, array(), true, null, false);
        return str_replace($tmp, '', $str);
    }
}

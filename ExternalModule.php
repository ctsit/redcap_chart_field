<?php
/**
 * @file
 * Provides ExternalModule class for REDCap Charts module.
 */

namespace REDCapCharts\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Piping;
use RCView;

/**
 * ExternalModule class for REDCap Charts module.
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

        $this->jsSettings['configFields'] = $this->getConfigFields($project_id);

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
            $this->buildChartConfigFields();
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
                if ($info['type'] == 'json') {
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
        switch ($this->getProjectSetting('chart_lib', $project_id)) {
            case 'chartjs':
                $this->includeJs('//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js');
                $this->jsFiles[] = 'js/chartjs.js';
                break;

            case 'chartist':
                $this->includeJs('//cdn.jsdelivr.net/npm/chartist@0.11.0/dist/chartist.min.js');
                $this->includeCss('//cdn.jsdelivr.net/npm/chartist@0.11.0/dist/chartist.min.css');
                $this->jsFiles[] = 'js/chartist.js';
                break;
        }
    }

    /**
     * Get configuration fields according to the chosen library.
     */
    function getConfigFields($project_id) {
        $config = array(
            'chart_type' => array('type' => 'select', 'label' => 'Chart type'),
            'chart_data' => array('type' => 'json', 'label' => 'Chart data'),
            'chart_options' => array('type' => 'json', 'label' => 'Chart options'),
        );

        switch ($this->getProjectSetting('chart_lib', $project_id)) {
            // TODO: add width and height fields.
            case 'chartjs':
                $config['chart_type']['choices'] = array(
                    'line' => 'Line',
                    'bar' => 'Bar',
                    'radar' => 'Radar',
                    'pie' => 'Pie',
                    'doughnut' => 'Doughnut',
                    'polarArea' => 'Polar area',
                    'bubble' => 'Bubble',
                    'scatter' => 'Scatter',
                );

                break;

            case 'chartist':
                $config['chart_responsive_options'] =  array('type' => 'json', 'label' => 'Chart responsive options');
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
     * Builds configuration fields for online designer..
     */
    protected function buildChartConfigFields() {
        $output = '';

        foreach ($this->jsSettings['configFields'] as $name => $info) {
            switch ($info['type']) {
                case 'select':
                    $field = RCView::select(array('name' => $name), $this->jsSettings['configFields'][$name]['choices']);
                    break;

                case 'json':
                    $field = RCView::textarea(array(
                        'name' => $name,
                        'class' => 'x-form-textarea x-form-field json-field',
                    ));
                    break;
            }

            $output .= RCView::div(array('class' => 'chart-property'), RCView::div(array(), RCView::b($info['label'])) . $field);
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

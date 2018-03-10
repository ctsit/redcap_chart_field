<?php
/**
 * @file
 * Provides ExternalModule class for REDCap Chart Field module.
 */

namespace REDCapChartField\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use RCView;

/**
 * ExternalModule class for REDCap Chart Field module.
 */
class ExternalModule extends AbstractExternalModule {

    protected $cssFiles;
    protected $jsFiles;
    protected $settings = array(
        'fields' => array(),
        'configFields' => array(
            'chart_type' => 'select',
            'chart_data' => 'json',
            'chart_options' => 'json',
         ),
    );

    /**
     * @inheritdoc
     */
    function redcap_every_page_before_render($project_id) {
        if (!$project_id) {
            return;
        }

        if (PAGE === 'Design/edit_field.php' && isset($_POST['field_type']) && $_POST['field_type'] == 'chart') {
            $misc = array();
            foreach ($this->settings['configFields'] as $field => $type) {
                if (!empty($_POST[$field])) {
                    $misc[$field] = $type == 'json' ? json_decode($_POST[$field]) : $_POST[$field];
                }
            }

            $_POST['field_annotation'] = json_encode($misc);
        }

        global $Proj;
        foreach ($Proj->metadata as $field => $info) {
            if ($info['element_type'] != 'chart') {
                continue;
            }

            $Proj->metadata[$field]['chart_settings'] = $this->settings['fields'][$field] = json_decode($Proj->metadata[$field]['misc'], true);
            $Proj->metadata[$field]['misc'] = '';
        }
    }

    /**
     * @inheritdoc
     */
    function redcap_every_page_top($project_id) {
        if (!$project_id) {
            return;
        }

        $this->jsFiles[] = 'js/online-designer.js';

        $this->includeJs('//cdn.jsdelivr.net/chartist.js/latest/chartist.min.js');
        $this->includeCss('//cdn.jsdelivr.net/chartist.js/latest/chartist.min.css');

        $this->buildChartConfigFields();

        $this->setJsSettings();
        $this->loadScripts();
        $this->loadStyles();
    }

    protected function buildChartConfigFields() {
        $output = RCView::div(array('class' => 'chart-property'), RCView::div(array(), RCView::b('Chart type')) . RCView::select(array('name' => 'chart_type'), array(
            'bar' => 'Bar',
            'line' => 'Line',
        )));

        $json_fields = array(
            'chart_data' => 'Chart data',
            'chart_options' => 'Chart options',
        );

        foreach ($json_fields as $name => $label) {
            $output .= RCView::div(array('class' => 'chart-property'), RCView::div(array(), RCView::b($label)) . RCView::textarea(array(
                'name' => $name,
                'class' => 'x-form-textarea x-form-field json-field',
            )));
        }

        $this->settings['onlineDesignerContents'] = $output;
        $this->cssFiles[] = 'css/online-designer.css';
    }

    /**
     * Includes local CSS files.
     */
    protected function loadStyles() {
        foreach ($this->cssFiles as $path) {
            $this->includeCss($this->getUrl($path));
        }
    }

    /**
     * Includes local JS files.
     */
    protected function loadScripts() {
        foreach ($this->jsFiles as $path) {
            $this->includeJs($this->getUrl($path));
        }
    }

    /**
     * Sets JS settings.
     */
    protected function setJsSettings() {
        echo '<script>redcapChartField = ' . json_encode($this->settings) . ';</script>';
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
}

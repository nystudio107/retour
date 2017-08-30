<?php
/**
 * Retour plugin for Craft CMS
 *
 * Retour Widget
 *
 * @author    nystudio107
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class RetourWidget extends BaseWidget
{
    /**
     * @return mixed
     */
    public function getName()
    {
        return craft()->retour->getPluginName() . " " . Craft::t("Stats");
    }

    /**
     * @return mixed
     */
    public function getBodyHtml()
    {
        // Include our Javascript & CSS
        craft()->templates->includeCssResource('retour/css/widgets/RetourWidget.css');
        craft()->templates->includeJsResource('retour/js/widgets/RetourWidget.js');

        // Variables to pass down to our rendered template
        $variables = array();
        $variables['settings'] = $this->getSettings();

        return craft()->templates->render('retour/widgets/RetourWidget_Body', $variables);
    }

    /**
     * @return string
     */
    public function getIconPath()
    {
        return craft()->path->getPluginsPath() . 'retour/resources/icon.svg';
    }

    /**
     * @return mixed
     */
    public function getSettingsHtml()
    {
        // Variables to pass down to our rendered template
        $variables = array();
        $variables['settings'] = $this->getSettings();

        return craft()->templates->render('retour/widgets/RetourWidget_Settings', $variables);
    }

    /**
     * @param mixed $settings The Widget's settings
     *
     * @return mixed
     */
    public function prepSettings($settings)
    {
        // Modify $settings here...
        return $settings;
    }

    /**
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'numberOfDays' => array(AttributeType::String, 'label' => 'Some Setting', 'default' => '3'),
        );
    }
}

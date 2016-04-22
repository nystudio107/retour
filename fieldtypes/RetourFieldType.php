<?php
/**
 * Retour plugin for Craft CMS
 *
 * Retour FieldType
 *
 * @author    Andrew Welch
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class RetourFieldType extends BaseFieldType
{
    /**
     * Returns the name of the fieldtype.
     *
     * @return mixed
     */
    public function getName()
    {
        return Craft::t('Retour Redirect');
    }

    /**
     * Returns the content attribute config.
     *
     * @return mixed
     */
    public function defineContentAttribute()
    {
        return AttributeType::Mixed;
    }

    /**
     * Returns the field's input HTML.
     *
     * @param string $name
     * @param mixed  $value
     * @return string
     */
    public function getInputHtml($name, $value)
    {

        $id = craft()->templates->formatInputId($name);
        $namespacedId = craft()->templates->namespaceInputId($id);

        // Include our Javascript & CSS
        craft()->templates->includeCssResource('retour/css/fields/RetourFieldType.css');
        craft()->templates->includeJsResource('retour/js/fields/RetourFieldType.js');

/* -- Variables to pass down to our field.js */

        $jsonVars = array(
            'id' => $id,
            'name' => $name,
            'namespace' => $namespacedId,
            'prefix' => craft()->templates->namespaceInputId(""),
            );

        $jsonVars = json_encode($jsonVars);
        craft()->templates->includeJs("$('#{$namespacedId}').RetourFieldType(" . $jsonVars . ");");

/* -- Get the list of matches */

        $matchList = array(
            'exactmatch' => 'Exact Match',
            'regexmatch' => 'RegEx Match',
            );

/* -- Variables to pass down to our rendered template */

        $variables = array(
            'id' => $id,
            'name' => $name,
            'namespaceId' => $namespacedId,
            'matchList' => $matchList,
            'values' => $value
            );

        return craft()->templates->render('retour/fields/RetourFieldType.twig', $variables);
    }

    /**
     * Define our FieldType's settings
     * @return none
     */
    protected function defineSettings()
    {
        return array(
            'defaultRedirectSrcUrl' => array(AttributeType::String, 'default' => ''),
            'defaultRedirectMatchType' => array(AttributeType::String, 'default' => 'match'),
            'redirectChangeable' => array(AttributeType::Bool, 'default' => 1),
        );
    }

    /**
     * Render the field settings
     * @return none
     */
    public function getSettingsHtml()
    {
        craft()->templates->includeCssResource('retour/css/fields/RetourFieldTypeSettings.css');
        craft()->templates->includeJsResource('retour/js/fields/RetourFieldTypeSettings.js');

/* -- Get the list of matches */

        $matchList = array(
            'exactmatch' => 'Exact Match',
            'regexmatch' => 'RegEx Match',
            );

        return craft()->templates->render('retour/fields/RetourFieldType_Settings', array(
            'matchList'     => $matchList,
            'settings'      => $this->getSettings()
        ));
   }

    /**
     * Returns the input value as it should be saved to the database.
     *
     * @param mixed $value
     * @return mixed
     */
    public function prepValueFromPost($value)
    {
        $result = null;

        if (empty($value))
        {
            $result = $this->prepValue($value);
        }
        else
        {
            $result = new Retour_RedirectsFieldModel($value);
        }
        return $result;
    }

    /**
     * Prepares the field's value for use.
     *
     * @param mixed $value
     * @return mixed
     */
    public function prepValue($value)
    {
        if (!$value)
        {
            $value = new Retour_RedirectsFieldModel();

            $value->defaultRedirectSrcUrl = $this->getSettings()->defaultRedirectSrcUrl;
            $value->defaultRedirectMatchType = $this->getSettings()->defaultRedirectMatchType;
            $value->redirectChangeable = $this->getSettings()->redirectChangeable;
        }

        return $value;
    }

}
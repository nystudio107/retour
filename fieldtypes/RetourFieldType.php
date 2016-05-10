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
    } /* -- getName */

    /**
     * Returns the content attribute config.
     *
     * @return mixed
     */
    public function defineContentAttribute()
    {
        return false;
    } /* -- defineContentAttribute */

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

        $matchList = craft()->retour->getMatchesList();

/* -- Variables to pass down to our rendered template */

        $variables = array(
            'id' => $id,
            'name' => $name,
            'namespaceId' => $namespacedId,
            'matchList' => $matchList,
            'element' => $this->element,
            'field' => $this->model,
            'values' => $value
            );

        return craft()->templates->render('retour/fields/RetourFieldType.twig', $variables);
    } /* -- getInputHtml */

    /**
     * Define our FieldType's settings
     * @return none
     */
    protected function defineSettings()
    {
        return array(
            'defaultRedirectSrcUrl' => array(AttributeType::String, 'default' => ''),
            'defaultRedirectMatchType' => array(AttributeType::String, 'default' => 'match'),
            'defaultRedirectHttpCode' => array(AttributeType::Number, 'default' => 301),
            'redirectChangeable' => array(AttributeType::Bool, 'default' => 1),
        );
    } /* -- getSettingsHtml */

    /**
     * Render the field settings
     * @return none
     */
    public function getSettingsHtml()
    {

/* -- Get the list of matches */

        $matchList = craft()->retour->getMatchesList();

        return craft()->templates->render('retour/fields/RetourFieldType_Settings', array(
            'matchList'     => $matchList,
            'settings'      => $this->getSettings()
        ));
   } /* -- getSettingsHtml */

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
        $result->redirectDestUrl = $this->element->url;
        $result->locale = $this->element->locale;
        if ($result->redirectMatchType == "exactmatch")
            $result->redirectSrcUrl = '/' . ltrim($result->redirectSrcUrl, '/');

/* -- Restore the default fields we don't let the user edit */

        $oldRecord = craft()->retour->getRedirectByElementId($this->element->id, $this->element->locale);

        if ($oldRecord)
        {
            $result->hitCount = $oldRecord->hitCount;
            $result->hitLastTime = $oldRecord->hitLastTime;
        }

        try
        {
            $result->redirectSrcUrlParsed = craft()->templates->renderObjectTemplate($result->redirectSrcUrl, $this->element);
        }
        catch (Exception $e)
        {
            RetourPlugin::log("Template error in the `redirectSrcUrl` field.", LogLevel::Info, true);
        }
        $error = craft()->cache->flush();
        RetourPlugin::log("Cache flushed: " . print_r($error, true), LogLevel::Info, false);

        return $result;
    } /* -- prepValueFromPost */

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
            $result = craft()->retour->getRedirectByElementId($this->element->id, $this->element->locale);
            if ($result)
                $value->setAttributes($result->getAttributes(), false);
            else
            {
                $value->redirectSrcUrl = $this->getSettings()->defaultRedirectSrcUrl;
                $value->redirectMatchType = $this->getSettings()->defaultRedirectMatchType;
                $value->redirectHttpCode = $this->getSettings()->defaultRedirectHttpCode;
            }
       }

        $value->redirectChangeable = $this->getSettings()->redirectChangeable;

        return $value;
    } /* -- prepValue */

    /**
     * @inheritDoc IFieldType::onAfterElementSave()
     *
     * @return null
     */
    public function onAfterElementSave()
    {
        $element = $this->element;
        $field = $this->model;
        $result = $element->getContent()->getAttribute($field->handle);

        $result->associatedElementId = $element->id;
        craft()->retour->saveRedirect($result);

        // $this->prepValueFromPost(null);
    }

}

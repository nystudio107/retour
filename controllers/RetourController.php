<?php
/**
 * Retour plugin for Craft CMS
 *
 * Retour Controller
 *
 * @author    Andrew Welch
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class RetourController extends BaseController
{

/**
 * @param  array  $variables
 */
    public function actionEditRedirect(array $variables = array())
    {

/* -- Give us something to edit */

        $redirectModel = new Retour_RedirectsModel();
        $redirectId = 0;
        if (!empty($variables['redirectId']))
        {
            $redirectId = $variables['redirectId'];
            $record = craft()->retour->getRedirectById($redirectId);
            if ($record)
                $redirectModel->setAttributes($record->getAttributes(), false);
        }

/* -- Get the list of matches */

        $matchList = craft()->retour->getMatchesList();

/* -- Display the edit template */

        $this->renderTemplate('retour/_edit', array(
           'values' => $redirectModel,
           'matchList' => $matchList,
           'redirectId' => $redirectId
        ));
    } /* -- actionEditRedirect */

/**
 * @param  array  $variables
 */
    public function actionSaveRedirect(array $variables = array())
    {
        $this->requirePostRequest();

        $redirectId = craft()->request->getPost('redirectId');

        if ($redirectId)
            $record = craft()->retour->getRedirectById($redirectId);
        else
            $record = new Retour_RedirectsRecord;

/* -- Set the record attributes, defaulting to the existing values for whatever is missing from the post data */

        $record->locale = craft()->language;
        $record->redirectSrcUrl = craft()->request->getPost('redirectSrcUrl', $record->redirectSrcUrl);
        $record->redirectSrcUrlParsed = $record->redirectSrcUrl;
        $record->redirectMatchType = craft()->request->getPost('redirectMatchType', $record->redirectMatchType);
        $record->redirectDestUrl = craft()->request->getPost('redirectDestUrl', $record->redirectDestUrl);
        $record->redirectHttpCode = craft()->request->getPost('redirectHttpCode', $record->redirectHttpCode);
        $record->hitLastTime = DateTimeHelper::currentUTCDateTime();
        $record->associatedEntryId = 0;

        if ($record->save())
        {
            craft()->userSession->setNotice(Craft::t('Retour Redirect saved.'));
            $this->redirectToPostedUrl($record);
        }
        else
        {
            $error = $record->getErrors();
            RetourPlugin::log(print_r($error, true), LogLevel::Info, false);
            craft()->userSession->setError(Craft::t('Couldn’t save Retour Redirect.'));

/* -- Send the Meta back to the template */

            craft()->urlManager->setRouteVariables(array(
                'values' => $record
            ));
        }
    } /* -- actionSaveRedirect */

/**
 */
    public function actionEditSettings()
    {
        $retourPlugin = craft()->plugins->getPlugin('retour');
        $settings = $retourPlugin->getSettings();

        $this->renderTemplate('retour/settings', array(
           'settings' => $settings
        ));
    } /* -- actionEditSettings */

}
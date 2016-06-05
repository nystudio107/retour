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
            $record = new Retour_StaticRedirectsRecord;

/* -- Set the record attributes, defaulting to the existing values for whatever is missing from the post data */

        $record->locale = craft()->language;
        $record->redirectMatchType = craft()->request->getPost('redirectMatchType', $record->redirectMatchType);
        $record->redirectSrcUrl = craft()->request->getPost('redirectSrcUrl', $record->redirectSrcUrl);
        if (($record->redirectMatchType == "exactmatch") && ($record->redirectSrcUrl !=""))
            $record->redirectSrcUrl = '/' . ltrim($record->redirectSrcUrl, '/');
        $record->redirectSrcUrlParsed = $record->redirectSrcUrl;
        $record->redirectDestUrl = craft()->request->getPost('redirectDestUrl', $record->redirectDestUrl);
        $record->redirectHttpCode = craft()->request->getPost('redirectHttpCode', $record->redirectHttpCode);
        $record->hitLastTime = DateTimeHelper::currentUTCDateTime();
        $record->associatedElementId = 0;

        $result = craft()->retour->saveStaticRedirect($record);
        if ($result === "" || $result === -1)
        {
            $this->redirectToPostedUrl($record);
        }
        else
        {

/* -- Send the record back to the template */

            craft()->urlManager->setRouteVariables(array(
                'values' => $record
            ));
        }
    } /* -- actionSaveRedirect */

/**
 * @param  array  $variables
 */
    public function actionDeleteRedirect()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        $affectedRows = craft()->db->createCommand()->delete('retour_static_redirects', array(
            'id' => $id
        ));

        RetourPlugin::log("Deleted Redirected: " . $id, LogLevel::Info, false);
        $error = craft()->cache->flush();
        RetourPlugin::log("Cache flushed: " . print_r($error, true), LogLevel::Info, false);

        $this->returnJson(array('success' => true));
    } /* -- actionDeleteRedirect */

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

/**
 */
    public function actionClearStatistics()
    {

        $error = craft()->retour->clearStatistics();
        RetourPlugin::log("Statistics cleared: " . print_r($error, true), LogLevel::Info, false);

        $error = craft()->cache->flush();
        RetourPlugin::log("Cache flushed: " . print_r($error, true), LogLevel::Info, false);

        craft()->userSession->setNotice(Craft::t('Statistics Cleared.'));
        craft()->request->redirect('statistics');
    } /* -- actionClearStatistics */

}
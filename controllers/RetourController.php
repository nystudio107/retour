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
    public function actionImportHtaccess(array $variables = array())
    {
        $file = \CUploadedFile::getInstanceByName('file');
        if (!is_null($file))
        {
            $filename = $file->getTempName();
            $handle = @fopen($filename, "r");
            if ($handle)
            {
                $skippingRule = false;
                while (($line = fgets($handle)) !== false)
                {
                    $redirectType = "";
                    RetourPlugin::log("parsing line: " . $line, LogLevel::Info, false);
                    $line = ltrim($line);
                    $line = preg_replace('/\s+/', ' ', $line);
                    $redirectParts = explode(" ", $line);
                    RetourPlugin::log("line parts: " . print_r($redirectParts, true), LogLevel::Info, false);
                    array_shift($redirectParts);

                    if ((!empty($redirectParts[0])) && (!empty($redirectParts[1])) && (!empty($redirectParts[2])))
                    {
                        if (strpos($line, 'RedirectMatch') === 0)
                        {
                            $redirectType = "regexmatch";
                            $srcUrl = $redirectParts[1];
                            $destUrl = $redirectParts[2];
                            $redirectCode = $redirectParts[0];
                        }
                        else if (strpos($line, 'Redirect') === 0)
                        {
                            $redirectType = "exactmatch";
                            $srcUrl = $redirectParts[1];
                            $destUrl = $redirectParts[2];
                            $redirectCode = $redirectParts[0];
                        }
                    }

/* -- We should just ignore RewriteRule's completely 
                    if (strpos($line, 'RewriteRule') === 0)
                    {
                        $srcUrl = $redirectParts[0];
                        $destUrl = $redirectParts[1];
                        $redirectCode = $redirectParts[2];
                        $pos = strpos($redirectCode, 'R=');
                        if ($pos !== false)
                        {
                            $redirectType = "regexmatch";
                            $redirectCode = substr($redirectCode, $pos + 2, 3);
                        }
                    }

                    if (strpos($line, 'RewriteCond') === 0)
                        $skippingRule = true;

                    if (strpos($line, 'RewriteEngine') === 0)
                        $skippingRule = false;
*/
                    if (($redirectType != "") && (!$skippingRule))
                    {

                        $record = new Retour_StaticRedirectsRecord;

                        $record->locale = craft()->language;
                        $record->redirectMatchType = $redirectType;
                        $record->redirectSrcUrl = $srcUrl;
                        if (($record->redirectMatchType == "exactmatch") && ($record->redirectSrcUrl !=""))
                            $record->redirectSrcUrl = '/' . ltrim($record->redirectSrcUrl, '/');
                        $record->redirectSrcUrlParsed = $record->redirectSrcUrl;
                        $record->redirectDestUrl = $destUrl;
                        $record->redirectHttpCode = $redirectCode;
                        $record->hitLastTime = DateTimeHelper::currentUTCDateTime();
                        $record->associatedElementId = 0;

                        $result = craft()->retour->saveStaticRedirect($record);

                    }
                }
                if (!feof($handle))
                    craft()->userSession->setError(Craft::t('Error: unexpected fgets() fail.'));
                fclose($handle);
            }
        }
        else
            craft()->userSession->setError(Craft::t('Please upload a file.'));

    } /* -- actionImportHtaccess */

/**
 * @param  array  $variables
 */
    public function actionEditRedirect(array $variables = array())
    {

/* -- Give us something to edit */

        $redirectModel = new Retour_RedirectsModel();
        $redirectModel->redirectSrcUrl = craft()->request->getParam('defaultRedirectSrcUrl');
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
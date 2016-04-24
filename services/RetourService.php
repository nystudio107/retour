<?php
/**
 * Retour plugin for Craft CMS
 *
 * Retour Service
 *
 * @author    Andrew Welch
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class RetourService extends BaseApplicationComponent
{

    protected $cachedRecords = null;

/**
 * @return Array All of the redirects
 */
    public function getAllRedirects()
    {

/* -- Cache it in our class; no need to fetch it more than once */

        if (isset($this->cachedRecords))
            return $this->cachedRecords;

        $result = Retour_RedirectsRecord::model()->findAll();

        $this->cachedRecords = $result;

        return $result;
    } /* -- getAllRedirects */

    public function findRedirectMatch($url)
    {
        $result = null;
        $redirects = $this->getAllRedirects();
        foreach ($redirects as $redirect)
        {
            switch ($redirect->redirectMatchType)
            {

/* -- Do a straight up match */

                case "exactmatch":
                    if (strcasecmp($redirect->redirectSrcUrlParsed, $url) === 0)
                    {
                        $result = $redirect;
                        $error = $this->incrementRedirectHitCount($redirect);
                        RetourPlugin::log(print_r($error, true), LogLevel::Info, false);
                        return $result;
                    }
                    break;

/* -- Do a regex match */

                case "regexmatch":
                    $matchRegEx = "`" . $redirect->redirectSrcUrlParsed . "`";
                    if (preg_match($matchRegEx, $url) === 1)
                    {
                        $result = $redirect;
                        $error = $this->incrementRedirectHitCount($redirect);
                        RetourPlugin::log(print_r($error, true), LogLevel::Info, false);
                        return $result;
                    }
                    break;

/* -- Otherwise try to look up a plugin's method by and call it for the match */

                default:
                    break;
            }
        }
        return $result;
    } /* -- */

/**
 * @param  Retour_RedirectsModel The redirect to create
 */
    public function incrementRedirectHitCount($redirectsModel)
    {
        if (isset($redirectsModel))
        {
            $redirectsModel->hitCount = $redirectsModel->hitCount + 1;
            $redirectsModel->hitLastTime = DateTimeHelper::currentTimeForDb();
            return $redirectsModel->save();
        }
    } /* -- incrementRedirectHitCount */

/**
 * @param  int $entryId The associated entryID
 * @param  string $locale  The locale
 * @return Mixed The resulting Redirect
 */
    public function getRedirectByEntryId($entryId, $locale)
    {
        $result = Retour_RedirectsRecord::model()->findByAttributes(array('associatedEntryId' => $entryId, 'locale' => $locale));
        return $result;
    } /* -- getRedirectByEntryId */

/**
 * @param  Retour_RedirectsModel The redirect to save
 */
    public function saveRedirect($redirectsModel)
    {
        if (isset($redirectsModel))
        {
            $result = $this->getRedirectByEntryId($redirectsModel->associatedEntryId, $redirectsModel->locale);
            if ($result)
            {
                $result->setAttributes($redirectsModel->getAttributes(), false);
                $error = $result->save();
            }
            else
                $error = $this->createRedirect($redirectsModel);
            RetourPlugin::log(print_r($error, true), LogLevel::Info, false);
        }
    } /* -- saveRedirect */

/**
 * @param  int $entryId The associated entryID
 * @param  string $locale  The locale
 */
    public function deleteRedirectByEntryId($entryId, $locale)
    {
        $result = $this->getRedirectByEntryId($entryId, $locale);
        if ($result)
        {
            $result->delete();
        }
    } /* -- deleteRedirectByEntryId */

/**
 * @param  Retour_RedirectsModel The redirect to create
 */
    public function createRedirect($redirectsModel)
    {
        if (isset($redirectsModel))
        {
            $result = new Retour_RedirectsRecord;
            $result->setAttributes($redirectsModel->getAttributes(), false);
            return $result->save();
        }
    } /* -- createRedirect */

}
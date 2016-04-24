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

    protected $cachedRedirects = null;
    protected $cachedEntryRedirects = null;
    protected $cachedStaticRedirects = null;

/**
 * @return Array All of the redirects
 */
    public function getAllRedirects()
    {

/* -- Cache it in our class; no need to fetch it more than once */

        if (isset($this->cachedRedirects))
            return $this->cachedRedirects;

        $result = Retour_RedirectsRecord::model()->findAll();

        $this->cachedRedirects = $result;

        return $result;
    } /* -- getAllRedirects */

/**
 * @return Array All of the entry redirects
 */
    public function getAllEntryRedirects()
    {

/* -- Cache it in our class; no need to fetch it more than once */

        if (isset($this->cachedEntryRedirects))
            return $this->cachedEntryRedirects;

        $result = Retour_RedirectsRecord::model()->findAllByAttributes(array(), 'associatedEntryId <> 0');

        $this->cachedEntryRedirects = $result;

        return $result;
    } /* -- getAllEntryRedirects */

/**
 * @return Array All of the static redirects
 */
    public function getAllStaticRedirects()
    {

/* -- Cache it in our class; no need to fetch it more than once */

        if (isset($this->cachedStaticRedirects))
            return $this->cachedStaticRedirects;

        $result = Retour_RedirectsRecord::model()->findAllByAttributes(array('associatedEntryId' => 0));

        $this->cachedStaticRedirects = $result;

        return $result;
    } /* -- getAllStaticRedirects */

    public function findRedirectMatch($url)
    {
        $redirects = $this->getAllRedirects();
        foreach ($redirects as $redirect)
        {
            switch ($redirect->redirectMatchType)
            {

/* -- Do a straight up match */

                case "exactmatch":
                    if (strcasecmp($redirect->redirectSrcUrlParsed, $url) === 0)
                    {
                        $error = $this->incrementRedirectHitCount($redirect);
                        RetourPlugin::log($redirect->redirectMatchType . " result: " . print_r($error, true), LogLevel::Info, false);
                        return $redirect;
                    }
                    break;

/* -- Do a regex match */

                case "regexmatch":
                    $matchRegEx = "`" . $redirect->redirectSrcUrlParsed . "`i";
                    if (preg_match($matchRegEx, $url) === 1)
                    {
                        $error = $this->incrementRedirectHitCount($redirect);
                        RetourPlugin::log($redirect->redirectMatchType . " result: " . print_r($error, true), LogLevel::Info, false);
                        return $redirect;
                    }
                    break;

/* -- Otherwise try to look up a plugin's method by and call it for the match */

                default:
                    $plugin = craft()->plugins->getPlugin($redirect->redirectMatchType);
                    if ($plugin)
                    {
                        if (method_exists($plugin, "retourMatch"))
                        {
                            $args = array(
                                'redirectModel' => &$redirect,
                                );
                            $result = call_user_func_array(array($plugin, "retourMatch"), $args);
                            if ($result)
                            {
                                $error = $this->incrementRedirectHitCount($redirect);
                                RetourPlugin::log($redirect->redirectMatchType . " result: " . print_r($error, true), LogLevel::Info, false);
                                return $redirect;
                            }
                        }
                    }
                    break;
            }
        }
        return null;
    } /* -- findRedirectMatch */

/**
 * @param  Retour_RedirectsModel The redirect to create
 */
    public function incrementRedirectHitCount(&$redirectsModel)
    {
        if (isset($redirectsModel))
        {
            $redirectsModel->hitCount = $redirectsModel->hitCount + 1;
            $redirectsModel->hitLastTime = DateTimeHelper::currentUTCDateTime();
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
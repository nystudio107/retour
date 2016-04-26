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

    protected $cachedEntryRedirects = null;
    protected $cachedStaticRedirects = null;
    protected $cachedStatistics = null;

/**
 * @return Array All of the entry redirects
 */
    public function getAllEntryRedirects()
    {

/* -- Cache it in our class; no need to fetch it more than once */

        if (isset($this->cachedEntryRedirects))
            return $this->cachedEntryRedirects;

        $result = craft()->db->createCommand()
            ->select('*')
            ->from('retour_redirects')
            ->order('hitCount DESC')
            ->queryAll();

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

        $result = craft()->db->createCommand()
            ->select('*')
            ->from('retour_static_redirects')
            ->order('hitCount DESC')
            ->queryAll();

        $this->cachedStaticRedirects = $result;

        return $result;
    } /* -- getAllStaticRedirects */

/**
 * @return Array All of the statistics
 */
    public function getAllStatistics()
    {

/* -- Cache it in our class; no need to fetch it more than once */

        if (isset($this->cachedStatistics))
            return $this->cachedStatistics;

        $result = craft()->db->createCommand()
            ->select('*')
            ->from('retour_stats')
            ->order('hitCount DESC')
            ->queryAll();

        $this->cachedStatistics = $result;

        return $result;
    } /* -- getAllStatistics */

/**
 * @param  string $url the url to match
 * @return mixed      the redirect array
 */
    public function findRedirectMatch($url)
    {
        $result = null;

/* -- Check the cache first */

        $redirect = $this->getRedirectFromCache($url);
        if ($redirect)
        {
            $error = $this->incrementRedirectHitCount($redirect);
            $this->saveRedirectToCache($url, $redirect);
            RetourPlugin::log("[cached] " . $redirect['redirectMatchType'] . " result: " . print_r($error, true), LogLevel::Info, false);
            return $redirect;
        }

/* -- Look up the entry redirects first */

        $redirects = null;
        $redirects = $this->getAllEntryRedirects();
        $result = $this->lookupRedirect($url, $redirects);
        if ($result)
            return $result;

/* -- Look up the static redirects next */

        $redirects = null;
        $redirects = $this->getAllStaticRedirects();
        $result = $this->lookupRedirect($url, $redirects);
        if ($result)
            return $result;

        return $result;
    } /* -- findRedirectMatch */

/**
 * @param  string $url the url to match
 * @param  mixed $redirects an array of redirects to look through
 * @return mixed      the redirect array
 */
    public function lookupRedirect($url, $redirects)
    {
        $result = null;
        foreach ($redirects as $redirect)
        {
            switch ($redirect['redirectMatchType'])
            {

/* -- Do a straight up match */

                case "exactmatch":
                    if (strcasecmp($redirect['redirectSrcUrlParsed'], $url) === 0)
                    {
                        $error = $this->incrementRedirectHitCount($redirect);
                        RetourPlugin::log($redirect['redirectMatchType'] . " result: " . print_r($error, true), LogLevel::Info, false);
                        $this->saveRedirectToCache($url, $redirect);
                        return $redirect;
                    }
                    break;

/* -- Do a regex match */

                case "regexmatch":
                    $matchRegEx = "`" . $redirect['redirectSrcUrlParsed'] . "`i";
                    if (preg_match($matchRegEx, $url) === 1)
                    {
                        $error = $this->incrementRedirectHitCount($redirect);
                        RetourPlugin::log($redirect['redirectMatchType'] . " result: " . print_r($error, true), LogLevel::Info, false);

/* -- If we're not associated with an EntryID, handle capture group replacement */

                        if ($redirect['associatedEntryId'] == 0)
                        {
                            $redirect['redirectDestUrl'] = preg_replace($matchRegEx, $redirect['redirectDestUrl'], $url);
                            $redirect = $value;
                        }
                        $this->saveRedirectToCache($url, $redirect);
                        return $redirect;
                    }
                    break;

/* -- Otherwise try to look up a plugin's method by and call it for the match */

                default:
                    $plugin = craft()->plugins->getPlugin($redirect['redirectMatchType']);
                    if ($plugin)
                    {
                        if (method_exists($plugin, "retourMatch"))
                        {
                            $args = array(
                                'redirect' => &$redirect,
                                );
                            $result = call_user_func_array(array($plugin, "retourMatch"), $args);
                            if ($result)
                            {
                                $error = $this->incrementRedirectHitCount($redirect);
                                RetourPlugin::log($redirect['redirectMatchType'] . " result: " . print_r($error, true), LogLevel::Info, false);
                                $this->saveRedirectToCache($url, $redirect);
                                return $redirect;
                            }
                        }
                    }
                    break;
            }
        }
        return $result;
    } /* -- lookupRedirect */

/**
 * @param  Retour_RedirectsModel The redirect to create
 */
    public function incrementRedirectHitCount(&$redirect)
    {
        if (isset($redirect))
        {
            $redirect['hitCount'] = $redirect['hitCount'] + 1;
            $redirect['hitLastTime'] = DateTimeHelper::currentTimeForDb();

            if ($redirect['associatedEntryId'])
                $table = 'retour_redirects';
            else
                $table= 'retour_static_redirects';
            $result = craft()->db->createCommand()
                ->update($table, array(
                    'hitCount' =>  $redirect['hitCount'],
                    'hitLastTime' =>  $redirect['hitLastTime'],
                    ), 'id=:id', array(':id' => $redirect['id']));
        }
    } /* -- incrementRedirectHitCount */

/**
 * @param  $url The 404 url
 */
    public function incrementStatistics($url)
    {

/* -- See if a stats record exists already */

        $result = craft()->db->createCommand()
            ->select('*')
            ->from('retour_stats')
            ->where('redirectSrcUrl =' . craft()->db->quoteValue($url))
            ->queryAll();

        if (empty($result))
        {
            $stats = new Retour_StatsRecord;
            $stats->redirectSrcUrl = $url;
            $stats->hitCount = 1;
            $stats->hitLastTime = DateTimeHelper::currentUTCDateTime();
            $stats->save();
        }
        else
        {

/* -- Update the stats table */

            foreach ($result as $stat)
            {
                $stat['hitCount'] = $stat['hitCount'] + 1;
                $stat['hitLastTime'] = DateTimeHelper::currentTimeForDb();

                $result = craft()->db->createCommand()
                    ->update('retour_stats', array(
                        'hitCount' =>  $stat['hitCount'],
                        'hitLastTime' =>  $stat['hitLastTime'],
                        ), 'id=:id', array(':id' => $stat['id']));
            }
        }
    } /* -- incrementStatistics */

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
 * @param  int $id The redirect's id
 * @param  string $locale  The locale
 * @return Mixed The resulting Redirect
 */
    public function getRedirectById($id)
    {
        $result = Retour_StaticRedirectsRecord::model()->findByAttributes(array('id' => $id));
        return $result;
    } /* -- getRedirectById */

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

/**
 * @param  string $urld The input URL
 * @param  mixed $redirect The redirect
 */
    public function saveRedirectToCache($url, $redirect)
    {
        $cacheKey = "retour_cache_" . md5($url);
        $error = craft()->cache->set($cacheKey, $redirect, null);
        RetourPlugin::log("Cached Redirect saved: " . print_r($error, true), LogLevel::Info, false);
    } /* -- saveRedirectToCache */

/**
 * @param  string $urld The input URL
 * @return mixed The redirect
 */
    public function getRedirectFromCache($url)
    {
        $cacheKey = "retour_cache_" . md5($url);
        $result = craft()->cache->get($cacheKey);
        RetourPlugin::log("Cached Redirect hit: " . print_r($result, true), LogLevel::Info, false);
        return $result;
    } /* -- getRedirectFromCache */

/**
 * @return  mixed Returns the list of matching schemes
 */
    public function getMatchesList()
    {
        $result = array(
            'exactmatch' => Craft::t('Exact Match'),
            'regexmatch' => Craft::t('RegEx Match'),
            );

/* -- Add any plugins that offer the retourMatch() method */

        foreach (craft()->plugins->getPlugins() as $plugin)
        {
            if (method_exists($plugin, "retourMatch"))
            {
                $result[$plugin->getClassHandle()] = $plugin->getName() . Craft::t(" Match");
            }
        }

        return $result;
    } /* -- getMatchesList */

}
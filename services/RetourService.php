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
     * @return array All of the statistics
     */
    public function getAllStatistics()
    {

        // Cache it in our class; no need to fetch it more than once
        if (isset($this->cachedStatistics)) {
            return $this->cachedStatistics;
        }

        $result = craft()->db->createCommand()
            ->select('*')
            ->from('retour_stats')
            ->order('hitCount DESC')
            ->limit(craft()->config->get("statsDisplayLimit", "retour"))
            ->queryAll();

        $this->cachedStatistics = $result;

        return $result;
    }

    /**
     * @param int $days The number of days to get
     * @param int $handled
     *
     * @return array Recent statistics
     */
    public function getRecentStatistics($days = 1, $handled = 0)
    {

        $handled = (int)$handled;

        if (!$handled) {
            $handled = 0;
        }
        $result = craft()->db->createCommand()
            ->select('*')
            ->from('retour_stats')
            ->where("hitLastTime >= ( CURDATE() - INTERVAL '$days' DAY )")
            ->andWhere('handledByRetour =' . $handled)
            ->order('hitLastTime DESC')
            ->queryAll();

        return $result;
    }

    /**
     */
    public function clearStatistics()
    {
        $result = craft()->db->createCommand()
            ->truncateTable('retour_stats');

        return $result;
    }

    /**
     * @param  string $url the url to match
     *
     * @return mixed      the redirect array
     */
    public function findRedirectMatch($url)
    {
        $result = null;

        // Check the cache first
        $redirect = $this->getRedirectFromCache($url);
        if ($redirect) {
            $error = $this->incrementRedirectHitCount($redirect);
            $this->saveRedirectToCache($url, $redirect);
            RetourPlugin::log("[cached] " . $redirect['redirectMatchType'] . " result: " . print_r($error, true), LogLevel::Info, false);

            return $redirect;
        }

        // Look up the entry redirects first
        $redirects = null;
        $redirects = $this->getAllEntryRedirects();
        $result = $this->lookupRedirect($url, $redirects);
        if ($result) {
            return $result;
        }

        // Look up the static redirects next
        $redirects = null;
        $redirects = $this->getAllStaticRedirects();
        $result = $this->lookupRedirect($url, $redirects);
        if ($result) {
            return $result;
        }

        return $result;
    }

    /**
     * @param $url
     *
     * @return mixed The redirect
     */
    public function getRedirectFromCache($url)
    {
        $cacheKey = "retour_cache_" . md5($url);
        $result = craft()->cache->get($cacheKey);
        RetourPlugin::log("Cached Redirect hit: " . print_r($result, true), LogLevel::Info, false);

        return $result;
    }

    /**
     * @param  Retour_RedirectsModel The redirect to create
     */
    public function incrementRedirectHitCount(&$redirect)
    {
        if (isset($redirect)) {
            $redirect['hitCount'] = $redirect['hitCount'] + 1;
            $redirect['hitLastTime'] = DateTimeHelper::currentTimeForDb();

            if ($redirect['associatedElementId']) {
                $table = 'retour_redirects';
            } else {
                $table = 'retour_static_redirects';
            }
            $result = craft()->db->createCommand()
                ->update($table, array(
                    'hitCount'    => $redirect['hitCount'],
                    'hitLastTime' => $redirect['hitLastTime'],
                ), 'id=:id', array(':id' => $redirect['id']));
        }
    }

    /**
     * @param  string $url     The input URL
     * @param  mixed  $redirect The redirect
     */
    public function saveRedirectToCache($url, $redirect)
    {
        $cacheKey = "retour_cache_" . md5($url);
        $error = craft()->cache->set($cacheKey, $redirect, 0);
        RetourPlugin::log("Cached Redirect saved: " . print_r($error, true), LogLevel::Info, false);
    }

    /**
     * @return Array All of the entry redirects
     */
    public function getAllEntryRedirects($limit = null)
    {

        // Cache it in our class; no need to fetch it more than once
        if (isset($this->cachedEntryRedirects)) {
            return $this->cachedEntryRedirects;
        }

        $result = craft()->db->createCommand()
            ->select('*')
            ->from('retour_redirects')
            ->order('hitCount DESC');

        if ($limit) {
            $result = $result->limit($limit);
        } else {
            $this->cachedEntryRedirects = $result;
        }

        return $result->queryAll();
    }

    /**
     * @param  string $url       the url to match
     * @param  mixed  $redirects an array of redirects to look through
     *
     * @return mixed      the redirect array
     */
    public function lookupRedirect($url, $redirects)
    {
        $result = null;
        foreach ($redirects as $redirect) {
            $redirectMatchType = isset($redirect['redirectMatchType']) ? $redirect['redirectMatchType'] : null;
            switch ($redirectMatchType) {
                // Do a straight up match
                case "exactmatch":
                    if (strcasecmp($redirect['redirectSrcUrlParsed'], $url) === 0) {
                        $error = $this->incrementRedirectHitCount($redirect);
                        RetourPlugin::log($redirectMatchType . " result: " . print_r($error, true), LogLevel::Info, false);
                        $this->saveRedirectToCache($url, $redirect);

                        return $redirect;
                    }
                    break;

                // Do a regex match
                case "regexmatch":
                    $matchRegEx = "`" . $redirect['redirectSrcUrlParsed'] . "`i";
                    if (preg_match($matchRegEx, $url) === 1) {
                        $error = $this->incrementRedirectHitCount($redirect);
                        RetourPlugin::log($redirectMatchType . " result: " . print_r($error, true), LogLevel::Info, false);

                        // If we're not associated with an EntryID, handle capture group replacement
                        if ($redirect['associatedElementId'] == 0) {
                            $redirect['redirectDestUrl'] = preg_replace($matchRegEx, $redirect['redirectDestUrl'], $url);
                        }
                        $this->saveRedirectToCache($url, $redirect);

                        return $redirect;
                    }
                    break;

                // Otherwise try to look up a plugin's method by and call it for the match
                default:
                    $plugin = $redirectMatchType ? craft()->plugins->getPlugin($redirectMatchType) : null;
                    if ($plugin) {
                        if (method_exists($plugin, "retourMatch")) {
                            $args = array(
                                array(
                                    'redirect' => &$redirect,
                                ),
                            );
                            $result = call_user_func_array(array($plugin, "retourMatch"), $args);
                            if ($result) {
                                $error = $this->incrementRedirectHitCount($redirect);
                                RetourPlugin::log($redirectMatchType . " result: " . print_r($error, true), LogLevel::Info, false);
                                $this->saveRedirectToCache($url, $redirect);

                                return $redirect;
                            }
                        }
                    }
                    break;
            }
        }
        RetourPlugin::log("Not handled: " . $url, LogLevel::Info, false);

        return $result;
    }

    /**
     * @return array All of the static redirects
     */
    public function getAllStaticRedirects($limit = null)
    {

        // Cache it in our class; no need to fetch it more than once
        if (isset($this->cachedStaticRedirects)) {
            return $this->cachedStaticRedirects;
        }

        $result = craft()->db->createCommand()
            ->select('*')
            ->from('retour_static_redirects')
            ->order('hitCount DESC');

        if ($limit) {
            $result = $result->limit($limit);
        } else {
            $this->cachedStaticRedirects = $result;
        }

        return $result->queryAll();
    }

    /**
     * @param $record
     *
     * @return bool|int|string
     */
    public function saveStaticRedirect($record)
    {
        $error = "";

        if (isset($record)) {
            if (($record->redirectSrcUrl == "") || ($record->redirectDestUrl == "")) {
                $id = $record->id;
                $affectedRows = craft()->db->createCommand()->delete('retour_static_redirects', array(
                    'id' => $id,
                ));

                RetourPlugin::log("Deleted Redirected: " . $id, LogLevel::Info, false);
                $error = craft()->cache->flush();
                RetourPlugin::log("Cache flushed: " . print_r($error, true), LogLevel::Info, false);
                $error = -1;
            } else {
                if ($record->save()) {
                    $error = craft()->cache->flush();
                    RetourPlugin::log("Cache flushed: " . print_r($error, true), LogLevel::Info, false);
                    craft()->userSession->setNotice(Craft::t('Retour Redirect saved.'));
                    $error = "";

                    // To prevent redirect loops, see if any static redirects have our destUrl as their srcUrl
                    $redir = $this->getRedirectByRedirectSrcUrl($record->redirectDestUrl, $record->locale);
                    if ($redir) {
                        $id = $redir->id;
                        $affectedRows = craft()->db->createCommand()->delete('retour_static_redirects', array(
                            'id' => $id,
                        ));
                    }
                } else {
                    $error = $record->getErrors();
                    RetourPlugin::log(print_r($error, true), LogLevel::Info, false);
                    craft()->userSession->setError(Craft::t('Couldn’t save Retour Redirect.'));
                }
            }
        }

        return $error;
    }

    /**
     * @param  string $srcUrl the redirect's redirectSrcUrl
     * @param  string $locale The locale
     *
     * @return Mixed The resulting Redirect
     */
    public function getRedirectByRedirectSrcUrl($srcUrl, $locale)
    {
        $result = Retour_RedirectsRecord::model()->findByAttributes(array('redirectSrcUrlParsed' => $srcUrl, 'locale' => $locale));

        return $result;
    }

    /**
     * @param      $url The 404 url
     * @param bool $handled
     */
    public function incrementStatistics($url, $handled = false)
    {

        $handled = (int)$handled;
        $url = substr($url, 0, 255);
        $referrer = craft()->request->getUrlReferrer();
        if (is_null($referrer)) {
            $referrer = "";
        }

        // Strip the query string if `stripQueryStringFromStats` is set
        if (craft()->config->get("stripQueryStringFromStats", "retour")) {
            $url = UrlHelper::stripQueryString($url);
        }

        // Make sure the referrerUrl does not exceed the max length of its table column.
        $attrConfigs = Retour_RedirectsRecord::model()->getAttributeConfigs();
        $maxLength = isset($attrConfigs['referrerUrl']['maxLength']) ? $attrConfigs['referrerUrl']['maxLength'] : 255;
        $trimMarker = '...';
        $referrer = mb_strimwidth($referrer, 0, ($maxLength-strlen($trimMarker)), $trimMarker, craft()->charset);

        // See if a stats record exists already
        $result = craft()->db->createCommand()
            ->select('*')
            ->from('retour_stats')
            ->where('redirectSrcUrl =' . craft()->db->quoteValue($url))
            ->queryAll();

        if (empty($result)) {
            $stats = new Retour_StatsRecord;
            $stats->redirectSrcUrl = $url;
            $stats->referrerUrl = $referrer;
            $stats->hitCount = 1;
            $stats->hitLastTime = DateTimeHelper::currentUTCDateTime();
            $stats->handledByRetour = $handled;
            $stats->save();
        } else {
            // Update the stats table
            foreach ($result as $stat) {
                $stat['hitCount'] = $stat['hitCount'] + 1;
                $stat['hitLastTime'] = DateTimeHelper::currentTimeForDb();
                $stat['referrerUrl'] = $referrer;

                $result = craft()->db->createCommand()
                    ->update('retour_stats', array(
                        'hitCount'        => $stat['hitCount'],
                        'hitLastTime'     => $stat['hitLastTime'],
                        'handledByRetour' => $handled,
                        'referrerUrl'     => $stat['referrerUrl'],
                    ), 'id=:id', array(':id' => $stat['id']));
            }
        }

        // After incrementing a statistic, trim the retour_stats db table
        $this->trimStatistics();
    }

    /**
     * Trim the retour_stats db table based on the statsStoredLimit config.php
     * setting
     *
     * @return void
     */
    public function trimStatistics()
    {
        $affectedRows = 0;
        $table = craft()->db->addTablePrefix('retour_stats');
        $quotedTable = craft()->db->quoteTableName($table);
        $limit = craft()->config->get("statsStoredLimit", "retour");

        // As per https://stackoverflow.com/questions/578867/sql-query-delete-all-records-from-the-table-except-latest-n
        if (!empty($limit) && $limit) {
            $affectedRows = craft()->db->createCommand("
            DELETE FROM $quotedTable
            WHERE id NOT IN (
              SELECT id
              FROM (
                SELECT id
                FROM $quotedTable
                ORDER BY hitLastTime DESC
                LIMIT $limit
              ) foo
            )
        ")->execute();
            RetourPlugin::log("Trimmed " . $affectedRows . " from retour_stats table", LogLevel::Info, false);
        }
    }

    /**
     * @param  int $id The redirect's id
     *
     * @return Mixed The resulting Redirect
     */
    public function getRedirectById($id)
    {
        $result = Retour_StaticRedirectsRecord::model()->findByAttributes(array('id' => $id));

        return $result;
    }

    /**
     * @param  Retour_RedirectsModel The redirect to save
     */
    public function saveRedirect($redirectsModel)
    {
        if (isset($redirectsModel)) {
            $result = $this->getRedirectByElementId($redirectsModel->associatedElementId, $redirectsModel->locale);
            if ($result) {
                $result->setAttributes($redirectsModel->getAttributes(), false);
                $error = $result->save();
            } else {
                $error = $this->createRedirect($redirectsModel);
            }
            RetourPlugin::log(print_r($error, true), LogLevel::Info, false);
        }
    }

    /**
     * @param  int    $elementId The associated elementId
     * @param  string $locale    The locale
     *
     * @return Mixed The resulting Redirect
     */
    public function getRedirectByElementId($elementId, $locale)
    {
        $result = Retour_RedirectsRecord::model()->findByAttributes(array('associatedElementId' => $elementId, 'locale' => $locale));

        return $result;
    }

    /**
     * @param  Retour_RedirectsModel The redirect to create
     */
    public function createRedirect($redirectsModel)
    {
        if (isset($redirectsModel)) {
            // Don't try to create a redirect if one already exists for the redirectSrcUrlParsed, or if empty
            if ($redirectsModel->redirectSrcUrlParsed && !$this->getRedirectByRedirectSrcUrl($redirectsModel->redirectSrcUrlParsed, $redirectsModel->locale)) {
                $result = new Retour_RedirectsRecord;
                $result->setAttributes($redirectsModel->getAttributes(), false);
                $result->save();
                $error = $result->getErrors();
                RetourPlugin::log(print_r($error, true), LogLevel::Info, false);
            }
        }
    }

    /**
     * @param  int    $elementId The associated elementId
     * @param  string $locale    The locale
     */
    public function deleteRedirectByElementId($elementId, $locale)
    {
        $result = $this->getRedirectByElementId($elementId, $locale);
        if ($result) {
            $result->delete();
        }
    }

    /**
     * Returns a list of localized URIs for the passed in element
     *
     * @param null $element
     *
     * @return array an array of paths
     */
    public function getLocalizedUris($element = null)
    {
        $localizedUris = array();
        if ($element) {
            if (craft()->isLocalized()) {
                $unsortedLocalizedUris = array();
                $_rows = craft()->db->createCommand()
                    ->select('locale')
                    ->addSelect('uri')
                    ->from('elements_i18n')
                    ->where(array('elementId' => $element->id, 'enabled' => 1))
                    ->queryAll();

                foreach ($_rows as $row) {
                    $path = ($row['uri'] == '__home__') ? '' : $row['uri'];
                    $url = UrlHelper::getSiteUrl($path, null, null, $row['locale']);
                    if (craft()->config->get('addTrailingSlashesToUrls')) {
                        $url = rtrim($url, '/') . '/';
                    }
                    $unsortedLocalizedUrls[$row['locale']] = $url;
                }

                $locales = craft()->i18n->getSiteLocales();
                foreach ($locales as $locale) {
                    $localeId = $locale->getId();
                    if (isset($unsortedLocalizedUris[$localeId])) {
                        $urlParts = parse_url($unsortedLocalizedUris[$localeId]);

                        array_push($localizedUris, "/" . $urlParts['path']);
                    }
                }
            } else {
                array_push($localizedUris, "/" . $element->uri);
            }
        }

        return $localizedUris;
    }

    /**
     * @return  string The name of the plugin
     */
    public function getPluginName()
    {
        $retourPlugin = craft()->plugins->getPlugin('retour');
        $result = $retourPlugin->getName();

        return $result;
    }

    /**
     * @return  mixed Returns the list of matching schemes
     */
    public function getMatchesList()
    {
        $result = array(
            'exactmatch' => Craft::t('Exact Match'),
            'regexmatch' => Craft::t('RegEx Match'),
        );

        // Add any plugins that offer the retourMatch() method
        foreach (craft()->plugins->getPlugins() as $plugin) {
            if (method_exists($plugin, "retourMatch")) {
                $result[$plugin->getClassHandle()] = $plugin->getName() . Craft::t(" Match");
            }
        }

        return $result;
    }
}

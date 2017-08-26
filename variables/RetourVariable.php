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

class RetourVariable
{

    /**
     * @param null $limit
     *
     * @return mixed
     */
    public function getEntryRedirects($limit = null)
    {
        return craft()->retour->getAllEntryRedirects($limit);
    }

    /**
     * @param null $limit
     *
     * @return mixed
     */
    public function getStaticRedirects($limit = null)
    {
        return craft()->retour->getAllStaticRedirects($limit);
    }

    /**
     * @return mixed
     */
    public function getStatistics()
    {
        return craft()->retour->getAllStatistics();
    }

    /**
     * @param $days
     * @param $handled
     *
     * @return mixed
     */
    public function getRecentStatistics($days, $handled)
    {
        return craft()->retour->getRecentStatistics($days, $handled);
    }

    /**
     * @return mixed
     */
    public function getMatchesList()
    {
        return craft()->retour->getMatchesList();
    }

    /**
     * @return mixed
     */
    public function getPluginName()
    {
        return craft()->retour->getPluginName();
    }

    /**
     * @return int
     */
    public function getHttpStatus()
    {
        return http_response_code();
    }

}

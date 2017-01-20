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

    function getEntryRedirects($limit = null)
    {
        return craft()->retour->getAllEntryRedirects($limit);
    } /* -- getEntryRedirects */

    function getStaticRedirects($limit = null)
    {
        return craft()->retour->getAllStaticRedirects($limit);
    } /* -- getStaticRedirects */

    function getStatistics()
    {
        return craft()->retour->getAllStatistics();
    } /* -- getStatistics */

    function getRecentStatistics($days, $handled)
    {
        return craft()->retour->getRecentStatistics($days, $handled);
    } /* -- getRecentStatistics */

    function getMatchesList()
    {
        return craft()->retour->getMatchesList();
    } /* -- getMatchesList */

    function getPluginName()
    {
        return craft()->retour->getPluginName();
    } /* -- getPluginName */

    public function getHttpStatus()
    {
        return http_response_code();
    }

}

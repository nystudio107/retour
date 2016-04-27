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

    function getEntryRedirects()
    {
        return craft()->retour->getAllEntryRedirects();
    } /* -- getEntryRedirects */

    function getStaticRedirects()
    {
        return craft()->retour->getAllStaticRedirects();
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
<?php
/**
 * Retour Configuration
 *
 * Completely optional configuration settings for Retour if you want to customize some
 * of its more esoteric behavior, or just want specific control over things.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'retour.php' and make
 * your changes there.
 */

return array(

/**
 * Controls whether Retour automatically creates static redirects when an entry's URI changes.
 */
    "createUriChangeRedirects" => true,
    "staticRedirectDisplayLimit" => 100,
    "dynamicRedirectDisplayLimit" => 100,
);

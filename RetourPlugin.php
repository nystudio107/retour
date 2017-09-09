<?php
/**
 * Retour plugin for Craft CMS
 *
 * Redirect URLs to retain your website's SEO gravitas when migrating a website
 * or restructuring it.
 *
 * @author    Andrew Welch
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class RetourPlugin extends BasePlugin
{

    protected $originalUris = array();

    /**
     * @return mixed
     */
    public function init()
    {

        // Listen for exceptions
        craft()->onException = function (\CExceptionEvent $event) {
            if ((($event->exception instanceof \CHttpException) && ($event->exception->statusCode == 404)) ||
                (($event->exception->getPrevious() instanceof \CHttpException) && ($event->exception->getPrevious()->statusCode == 404))) {
                RetourPlugin::log("A 404 exception occurred", LogLevel::Info, false);
                if (craft()->request->isSiteRequest() && !craft()->request->isLivePreview()) {
                    // See if we should redirect
                    $url = urldecode(craft()->request->getRequestUri());
                    // Strip the query string if `alwaysStripQueryString` is set
                    if (craft()->config->get("alwaysStripQueryString", "retour")) {
                        $url = UrlHelper::stripQueryString($url);
                    }
                    $noQueryUrl = UrlHelper::stripQueryString($url);
                    RetourPlugin::log("404 URL: " . $url, LogLevel::Info, false);

                    // Redirect if we find a match, otherwise let Craft handle it
                    $redirect = craft()->retour->findRedirectMatch($url);

                    if (isset($redirect)) {
                        craft()->retour->incrementStatistics($url, true);
                        $event->handled = true;
                        RetourPlugin::log("Redirecting " . $url . " to " . $redirect['redirectDestUrl'], LogLevel::Info, false);
                        craft()->request->redirect($redirect['redirectDestUrl'], true, $redirect['redirectHttpCode']);
                    } else {
                        // Now try it without the query string, too, otherwise let Craft handle it
                        $redirect = craft()->retour->findRedirectMatch($noQueryUrl);

                        if (isset($redirect)) {
                            craft()->retour->incrementStatistics($url, true);
                            $event->handled = true;
                            RetourPlugin::log("Redirecting " . $url . " to " . $redirect['redirectDestUrl'], LogLevel::Info, false);
                            craft()->request->redirect($redirect['redirectDestUrl'], true, $redirect['redirectHttpCode']);
                        } else {
                            craft()->retour->incrementStatistics($url, false);
                        }
                    }
                }
            }
        };

        // Listen for structure changes so we can regenerated our FieldType's URLs
        craft()->on('structures.onMoveElement', function (Event $e) {
            $element = $e->params['element'];
            $elemType = $element->getElementType();
            if ($element) {
                if ($elemType == ElementType::Entry) {
                    // Check the field layout, so that we only do this for FieldLayouts that have our Retour fieldtype
                    $fieldLayouts = $element->fieldLayout->getFields();
                    foreach ($fieldLayouts as $fieldLayout) {
                        $field = craft()->fields->getFieldById($fieldLayout->fieldId);
                        if ($field->type == "Retour") {
                            craft()->elements->saveElement($element);
                            RetourPlugin::log("Resaved moved structure element", LogLevel::Info, false);
                            break;
                        }
                    }
                }
            }
        });

        // Listen for entries whose slug changes
        craft()->on('entries.onBeforeSaveEntry', function (Event $e) {
            $this->originalUris = array();
            if (!$e->params['isNewEntry'] && craft()->config->get("createUriChangeRedirects", "retour")) {
                $entry = $e->params['entry'];

                $thisSection = $entry->getSection();
                if ($thisSection->hasUrls) {
                    $this->originalUris = craft()->retour->getLocalizedUris($entry);
                }
            }
        });

        craft()->on('entries.onSaveEntry', function (Event $e) {
            if (!$e->params['isNewEntry'] && craft()->config->get("createUriChangeRedirects", "retour")) {
                $entry = $e->params['entry'];
                $newUris = craft()->retour->getLocalizedUris($entry);

                foreach ($newUris as $newUri) {
                    $oldUri = current($this->originalUris);
                    next($this->originalUris);
                    if ((strcmp($oldUri, $newUri) != 0) && ($oldUri != "")) {
                        $record = new Retour_StaticRedirectsRecord;

                        if (craft()->config->get('addTrailingSlashesToUrls')) {
                            $oldUri = rtrim($oldUri, '/') . '/';
                            $newUri = rtrim($newUri, '/') . '/';
                        }

                        // Set the record attributes for our new auto-redirect
                        $record->locale = $entry->locale;
                        $record->redirectMatchType = 'exactmatch';
                        $record->redirectSrcUrl = $oldUri;
                        if (($record->redirectMatchType == "exactmatch") && ($record->redirectSrcUrl != "")) {
                            $record->redirectSrcUrl = '/' . ltrim($record->redirectSrcUrl, '/');
                        }
                        $record->redirectSrcUrlParsed = $record->redirectSrcUrl;
                        $record->redirectDestUrl = $newUri;
                        if (($record->redirectMatchType == "exactmatch") && ($record->redirectDestUrl != "")) {
                            $record->redirectDestUrl = '/' . ltrim($record->redirectDestUrl, '/');
                        }
                        $record->redirectHttpCode = '301';
                        $record->hitLastTime = DateTimeHelper::currentUTCDateTime();
                        $record->associatedElementId = 0;

                        $result = craft()->retour->saveStaticRedirect($record);
                    }
                }
            }
        });
    }

    /**
     * Returns the user-facing name.
     *
     * @return mixed
     */
    public function getName()
    {
        $pluginNameOverride = $this->getSettings()->getAttribute('pluginNameOverride');

        return empty($pluginNameOverride) ? Craft::t('Retour') : $pluginNameOverride;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return Craft::t("Intelligently redirect legacy URLs, so that you don't lose SEO value when rebuilding & restructuring a website.");
    }

    /**
     * @return string
     */
    public function getDocumentationUrl()
    {
        return 'https://github.com/nystudio107/retour/blob/master/README.md';
    }

    /**
     * @return string
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/nystudio107/retour/master/releases.json';
    }

    /**
     * Returns the version number.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.0.22';
    }

    /**
     * @return string
     */
    public function getSchemaVersion()
    {
        return '1.0.5';
    }

    /**
     * @return string
     */
    public function getDeveloper()
    {
        return 'nystudio107';
    }

    /**
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'http://nystudio107.com';
    }

    /**
     * @return bool
     */
    public function hasCpSection()
    {
        return true;
    }

    public function registerCpRoutes()
    {
        return array(
            'retour/settings'                 => array('action' => 'retour/editSettings'),
            'retour/clearStats'               => array('action' => 'retour/clearStatistics'),
            'retour/new'                      => array('action' => 'retour/editRedirect'),
            'retour/edit/(?P<redirectId>\d+)' => array('action' => 'retour/editRedirect'),
            'retour/htaccess'                 => array('action' => 'retour/importHtaccess'),
        );
    }

    /**
     */
    public function onBeforeInstall()
    {
    }

    /**
     */
    public function onAfterInstall()
    {
        // Show our "Welcome to Retour" message
        if (!craft()->isConsole()) {
            craft()->request->redirect(UrlHelper::getCpUrl('retour/welcome'));
        }
    }

    /**
     */
    public function onBeforeUninstall()
    {
    }

    /**
     */
    public function onAfterUninstall()
    {
    }

    /**
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'pluginNameOverride' => AttributeType::String,
        );
    }
}

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

    public function actionEditSettings()
    {
        $retourPlugin = craft()->plugins->getPlugin('retour');
        $settings = $retourPlugin->getSettings();

        $this->renderTemplate('retour/settings', array(
           'settings' => $settings
        ));
    }

}
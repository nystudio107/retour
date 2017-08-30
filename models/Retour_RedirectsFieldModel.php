<?php
/**
 * Retour plugin for Craft CMS
 *
 * Retour_RedirectsField Model
 *
 * @author    Andrew Welch
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class Retour_RedirectsFieldModel extends Retour_RedirectsModel
{
    /**
     * Defines this model's attributes.
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), array(
            'redirectChangeable' => array(AttributeType::Bool, 'default' => 1),
        ));
    }
}

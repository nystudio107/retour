<?php
/**
 * Retour plugin for Craft CMS
 *
 * Retour_Redirects Model
 *
 * @author    Andrew Welch
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class Retour_RedirectsModel extends BaseModel
{
    /**
     * Defines this model's attributes.
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), array(
            'redirectSrcUrl'       => array(AttributeType::String, 'default' => ''),
            'redirectSrcUrlParsed' => array(AttributeType::String, 'default' => ''),
            'redirectMatchType'    => array(AttributeType::String, 'default' => 'match'),
            'redirectDestUrl'      => array(AttributeType::String, 'default' => ''),
            'redirectHttpCode'     => array(AttributeType::Number, 'default' => 301),
            'hitCount'             => array(AttributeType::Number, 'default' => 0),
            'hitLastTime'          => array(AttributeType::DateTime, 'default' => DateTimeHelper::currentTimeForDb()),
            'locale'               => array(AttributeType::String, 'default' => ''),
            'associatedElementId'  => array(AttributeType::Number, 'default' => 0),
        ));
    }
}

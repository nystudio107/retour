<?php
/**
 * Retour plugin for Craft CMS
 *
 * Retour_Stats Model
 *
 * --snip--
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a
 * model.
 *
 * https://craftcms.com/docs/plugins/models
 * --snip--
 *
 * @author    Andrew Welch
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class Retour_StatsModel extends BaseModel
{
    /**
     * Defines this model's attributes.
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), array(
            'redirectSrcUrl'  => array(AttributeType::String, 'default' => ''),
            'referrerUrl'     => array(AttributeType::String, 'default' => '', 'maxLength' => 2000),),
            'hitCount'        => array(AttributeType::Number, 'default' => 0),
            'hitLastTime'     => array(AttributeType::DateTime, 'default' => DateTimeHelper::currentTimeForDb()),
            'handledByRetour' => array(AttributeType::Bool, 'default' => false),
        ));
    }
}

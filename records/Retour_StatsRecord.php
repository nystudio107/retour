<?php
/**
 * Retour plugin for Craft CMS
 *
 * Retour_Stats Record
 *
 * @author    Andrew Welch
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class Retour_StatsRecord extends BaseRecord
{
    /**
     * @return string
     */
    public function getTableName()
    {
        return 'retour_stats';
    }

    /**
     * @access protected
     * @return array
     */
   protected function defineAttributes()
    {
        return array(
            'redirectSrcUrl'        => array(AttributeType::String, 'default' => ''),
            'referrerUrl'           => array(AttributeType::String, 'default' => ''),
            'hitCount'              => array(AttributeType::Number, 'default' => 0),
            'hitLastTime'           => array(AttributeType::DateTime, 'default' => DateTimeHelper::currentTimeForDb() ),
            'handledByRetour'       => array(AttributeType::Bool, 'default' => false ),
        );
    }

    /**
     * @return array
     */
    public function defineIndexes()
    {
        return array(
            array('columns' => array('hitCount', 'id')),
            array('columns' => array('redirectSrcUrl'), 'unique' => true)
        );
    }

    /**
     * @return array
     */
    public function defineRelations()
    {
        return array(
        );
    }
}
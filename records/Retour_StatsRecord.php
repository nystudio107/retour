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
     * Returns the name of the database table the model is associated with (sans table prefix). By convention,
     * tables created by plugins should be prefixed with the plugin name and an underscore.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'retour_stats';
    }

    /**
     * Returns an array of attributes which map back to columns in the database table.
     *
     * @access protected
     * @return array
     */
   protected function defineAttributes()
    {
        return array(
            'redirectSrcUrl'        => array(AttributeType::String, 'default' => ''),
            'hitCount'              => array(AttributeType::Number, 'default' => 0),
            'hitLastTime'           => array(AttributeType::DateTime, 'default' => DateTimeHelper::currentTimeForDb() ),
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
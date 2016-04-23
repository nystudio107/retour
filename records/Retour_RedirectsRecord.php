<?php
/**
 * Retour plugin for Craft CMS
 *
 * Retour_Redirects Record
 *
 * @author    Andrew Welch
 * @copyright Copyright (c) 2016 nystudio107
 * @link      http://nystudio107.com
 * @package   Retour
 * @since     1.0.0
 */

namespace Craft;

class Retour_RedirectsRecord extends BaseRecord
{
    /**
     * Returns the name of the database table the model is associated with (sans table prefix). By convention,
     * tables created by plugins should be prefixed with the plugin name and an underscore.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'retour_redirects';
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
            'redirectSrcUrl'    => array(AttributeType::String, 'default' => ''),
            'redirectMatchType' => array(AttributeType::String, 'default' => 'match'),
            'redirectDestUrl'   => array(AttributeType::String, 'default' => ''),
            /* defined in defineRelations()
            'associatedEntryId'   => array(AttributeType::Number, 'default' => 0),
            'locale'    => array(AttributeType::Locale, 'required' => true, 'primaryKey' => true),
            */
        );
    }

    /**
     * Define fields that should be indexed
     * @return array
     */

    public function defineIndexes()
    {
        return array(
            array('columns' => array('locale', 'associatedEntryId')),
            array('columns' => array('redirectSrcUrl'), 'unique' => true)
        );
    }

    /**
     * If your record should have any relationships with other tables, you can specify them with the
     * defineRelations() function
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'associatedEntry'   => array(static::BELONGS_TO, 'EntryRecord', 'required' => true, 'onDelete' => static::CASCADE),
            'locale'            => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE)
        );
    }
}
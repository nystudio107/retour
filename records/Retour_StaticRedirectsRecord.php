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

class Retour_StaticRedirectsRecord extends BaseRecord
{
    /**
     * @return string
     */
    public function getTableName()
    {
        return 'retour_static_redirects';
    }

    /**
     * @access protected
     * @return array
     */
   protected function defineAttributes()
    {
        return array(
            'redirectSrcUrl'        => array(AttributeType::String, 'default' => ''),
            'redirectSrcUrlParsed'  => array(AttributeType::String, 'default' => ''),
            'redirectMatchType'     => array(AttributeType::String, 'default' => 'match'),
            'redirectDestUrl'       => array(AttributeType::String, 'default' => ''),
            'redirectHttpCode'      => array(AttributeType::Number, 'default' => 301),
            'hitCount'              => array(AttributeType::Number, 'default' => 0),
            'hitLastTime'           => array(AttributeType::DateTime, 'default' => DateTimeHelper::currentTimeForDb() ),
            'locale'                => array(AttributeType::Locale, 'required' => true),
            'associatedElementId'     => array(AttributeType::Number, 'default' => 0),
        );
    }

    /**
     * @return array
     */

    public function defineIndexes()
    {
        return array(
            array('columns' => array('locale', 'id')),
            array('columns' => array('redirectSrcUrlParsed'), 'unique' => true)
        );
    }

}
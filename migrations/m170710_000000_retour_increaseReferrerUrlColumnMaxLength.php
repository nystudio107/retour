<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170710_000000_retour_increaseReferrerUrlColumnMaxLength extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {

        $this->alterColumn('retour_stats', 'referrerUrl', array(ColumnType::Varchar, 'maxLength' => 2000));

        RetourPlugin::log('The max length of column referrerUrl has been increased to 2000 ', LogLevel::Info, true);

        return true;
    }
}

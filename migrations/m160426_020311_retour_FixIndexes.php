<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160426_020311_retour_FixIndexes extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{

        craft()->db->createCommand()->dropIndex('retour_redirects', 'redirectSrcUrl', true);
        craft()->db->createCommand()->createIndex('retour_redirects', 'redirectSrcUrlParsed', true);

        craft()->db->createCommand()->dropIndex('retour_static_redirects', 'redirectSrcUrl', true);
        craft()->db->createCommand()->createIndex('retour_static_redirects', 'redirectSrcUrlParsed', true);

        RetourPlugin::log("Updated Indexes for retour_redirects & retour_static_redirects", LogLevel::Info, true);

		return true;
	}
}

<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160514_000000_retour_convertToElementId extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {

/* -- Rename the column in  the retour_static_redirects column */

        MigrationHelper::renameColumn('retour_static_redirects', 'associatedEntryId', 'associatedElementId');

/* -- Rename the column in  the retour_redirects column */

        MigrationHelper::renameColumn('retour_redirects', 'associatedEntryId', 'associatedElementId');

/* -- Drop the old fk */

        MigrationHelper::dropForeignKeyIfExists('retour_redirects', array('associatedElementId'));

/* -- Add the new foreign key */

        $this->addForeignKey('retour_redirects', 'associatedElementId', 'elements', 'id', 'CASCADE', 'CASCADE');

        // return true and let craft know its done
        return true;
    }

}

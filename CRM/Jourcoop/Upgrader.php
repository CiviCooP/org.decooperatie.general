<?php

/**
 * Class CRM_Jourcoop_Upgrader.
 * A collection of upgrade steps. For now just implements install(),
 * might add support for updating to new versions later.
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 */
class CRM_Jourcoop_Upgrader extends CRM_Jourcoop_Upgrader_Base
{

    /**
     * Load JSON config files on first install.
     */
    public function install()
    {
        \CRM_Jourcoop_ConfigLoader::run();
    }

    /**
     * Run one off members migration script.
     * Hmm, no, let's do that using the API...
     *   public function upgrade_2016091300() {
     *    $mm = CRM_Jourcoop_Membership_Migrate::getInstance();
     *    $mm->migrateTemporaryMembershipData();
     *   }
     */

}

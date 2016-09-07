<?php
/**
 * Class CRM_Jourcoop_Upgrader.
 * A collection of upgrade steps. For now just implements install(),
 * might add support for updating to new versions later.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 */

class CRM_Jourcoop_Upgrader extends CRM_Jourcoop_Upgrader_Base {

  /**
   * Load JSON config files on install.
   */
  public function install() {
    \CRM_Jourcoop_ConfigLoader::run();
  }

  /**
   * Run members migration script (20160815)
   * Set to a new / higher id to execute on staging environment!
   */
  public function upgrade_20160815() {

      $mm = CRM_Jourcoop_Membership_Migrate::getInstance();
      $mm->migration_20160815();
      return true;
  }

}

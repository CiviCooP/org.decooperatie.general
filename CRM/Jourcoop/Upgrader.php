<?php

/**
 * Class CRM_Jourcoop_Upgrader.
 * A collection of upgrade steps. For now just implements install(),
 * might add support for updating to new versions later.
 */
class CRM_Jourcoop_Upgrader extends CRM_Jourcoop_Upgrader_Base {

  /**
   * Load JSON config files on install.
   */
  public function install() {
    \CRM_Jourcoop_ConfigLoader::run();
  }

}

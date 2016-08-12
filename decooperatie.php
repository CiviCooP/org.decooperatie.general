<?php
/**
 * Generic extension for De Cooperatie (org.decooperatie.general).
 * For information about this extension, see README.md and info.xml.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 * @link https://github.com/civicoop/org.civicoop.configitems
 */

require_once 'decooperatie.civix.php';

/**
 * Implements hook_civicrm_config().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function general_civicrm_config(&$config) {
  _general_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 * @param $files array(string)
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function general_civicrm_xmlMenu(&$files) {
  _general_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function general_civicrm_install() {
  _general_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function general_civicrm_uninstall() {
  _general_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function general_civicrm_enable() {
  _general_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function general_civicrm_disable() {
  _general_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 * @return mixed Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending) for 'enqueue', returns void
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function general_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _general_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function general_civicrm_managed(&$entities) {
  _general_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 * Generate a list of case-types
 * Note: This hook only runs in CiviCRM 4.4+.
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function general_civicrm_caseTypes(&$caseTypes) {
  _general_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 * Generate a list of Angular modules.
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function general_civicrm_angularModules(&$angularModules) {
_general_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function general_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _general_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

<?php
/**
 * Class CRM_Jourcoop_CiviApi.
 * Some quick & dirty copy/paste API related functions. Sorry.
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 */

require_once CIVICRM_PLUGIN_DIR . 'civicrm/api/class.api.php';

class CRM_Jourcoop_CiviApi extends \civicrm_api3 {

  /**
   * @var array $cache Temp class cache array
   */
  private $cache = [];

  /**
   * Call the CiviCRM API class.
   * Apparently, the call() method was set as private recently... so we're imitating it now.
   * @param string $entity Entity
   * @param string $action Action
   * @param mixed $params Parameters
   * @return \StdClass|array API result
   */
  public function api($entity, $action, $params) {
    $ret = $this->{$entity}->{$action}($params);
    return $this->result();
  }

  /**
   * Function to get custom field ids and cache them
   * (Currently only caches single items - caching groups may be more efficient when used often)
   * @param string $groupName Group Name
   * @param string $fieldName Field Name
   * @param bool $prependCustom Prepend 'custom_' to the return value
   * @return string|int|null Custom Field ID (or string containing 'custom_<id>')
   */
  public function getCustomFieldId($groupName, $fieldName, $prependCustom = FALSE) {
    try {
      if (!isset($this->cache['cfid'][$groupName][$fieldName])) {
        $this->cache['cfid'][$groupName][$fieldName] = $this->api('CustomField', 'getvalue', [
          'custom_group_id' => $groupName,
          'name'            => $fieldName,
          'return'          => 'id',
        ]);
      }
      return ($prependCustom ? 'custom_' : '') . $this->cache['cfid'][$groupName][$fieldName];
    } catch (\CiviCRM_API3_Exception $e) {
      return NULL;
    }
  }

  /**
   * Function to get a contact group id and cache it
   * @param string $groupName Group Name
   * @return int|null Contact Group ID
   */
  public function getContactGroupId($groupName) {
    try {
      if (!isset($this->cache['contactgid'][$groupName])) {
        $this->cache['contactgid'][$groupName] = $this->api('Group', 'getvalue', [
          'name'   => $groupName,
          'return' => 'id',
        ]);
      }
      return $this->cache['contactgid'][$groupName];
    } catch (\CiviCRM_API3_Exception $e) {
      return NULL;
    }
  }

  /**
   * Function to get an option group id and cache it
   * @param string $groupName Group Name
   * @return int|null Option Group ID
   */
  public function getOptionGroupId($groupName) {
    try {
      if (!isset($this->cache['ogid'][$groupName])) {
        $this->cache['ogid'][$groupName] = $this->api('OptionGroup', 'getvalue', [
          'name'   => $groupName,
          'return' => 'id',
        ]);
      }
      return $this->cache['ogid'][$groupName];
    } catch (\CiviCRM_API3_Exception $e) {
      return NULL;
    }
  }

  /**
   * Function to get an option value (value, not id!) and cache it
   * @param string $groupName Group Name
   * @param string $valueName Value Name
   * @return string|null Option Value
   */
  public function getOptionValue($groupName, $valueName) {
    try {
      if (!isset($this->cache['ogid'][$groupName][$valueName])) {
        $this->cache['ogid'][$groupName][$valueName] = $this->api('OptionValue', 'getvalue', [
          'option_group_id' => $groupName,
          'name'            => $valueName,
          'return'          => 'value',
        ]);
      }
      return $this->cache['ogid'][$groupName][$valueName];
    } catch (\CiviCRM_API3_Exception $e) {
      return NULL;
    }
  }

  /**
   * Function to get an array of membership types
   * @return \stdClass[] Membership Types
   */
  public function getMembershipTypes() {
    try {
      $mtypes = $this->api('MembershipType', 'get', ['is_active' => 1]);
      $ret = [];

      foreach ($mtypes->values as $mtype) {
        $ret[$mtype->id] = $mtype;
      }
      return $ret;
    } catch(\CiviCRM_API3_Exception $e) {
      return NULL;
    }
  }

}
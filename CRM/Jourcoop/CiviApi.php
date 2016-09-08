<?php
/**
 * Class CRM_Jourcoop_CiviApi.
 * Some quick & dirty copy/paste API related functions. Sorry.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 */

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
  public function api($entity, $action, $params)
  {
    $ret = $this->{$entity}->{$action}($params);
    return $this->result();
  }

  /**
   * Function to get custom field ids and cache them
   * @param string $groupName Group Name
   * @param string $fieldName Field Name
   * @param bool $addCustom Prepend 'custom_' to the return value
   * @return string Custom Field ID (or string containing 'custom_<id>')
   */
  public function getCustomFieldId($groupName, $fieldName, $addCustom = false) {
    if (!isset($this->cache['cfid'][$groupName][$fieldName])) {
      $this->cache['cfid'][$groupName][$fieldName] = civicrm_api3('CustomField', 'getvalue', [
        'custom_group_id' => $groupName,
        'name'            => $fieldName,
        'return'          => 'id',
      ]);
    }
    return ($addCustom ? 'custom_' : '') . $this->cache['cfid'][$groupName][$fieldName];
  }
}
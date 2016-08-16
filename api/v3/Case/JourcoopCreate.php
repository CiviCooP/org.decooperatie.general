<?php
/**
 * Case.JourcoopCreate API method.
 * Create a new job / project in the CiviCase backend.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 */

/**
 * @param array $params Parameters
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_Case_JourcoopCreate($params) {

    return civicrm_api3_create_success(true, $params, 'Case', 'JourcoopCreate');
}

/**
 * @param array $params Info about parameters this API call supports
 */
function _civicrm_api3_Case_JourcoopCreate_spec(&$params) { }
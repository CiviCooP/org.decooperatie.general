<?php
/**
 * Membership.JourcoopRenew API method.
 * Checks if there are any active memberships that should be renewed for another month,
 * updates them if necessary, and generates the correct contribution entries.
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
function civicrm_api3_Membership_JourcoopRenew($params) {

    try {
        $renew = \CRM_Jourcoop_Membership_Renew::getInstance();
        $count = $renew->renewMemberships();
    } catch(\CRM_Jourcoop_Exception $e) {
        return civicrm_api3_create_error('Membership renewal error: ' . $e->getMessage() . '.');
    }

    return civicrm_api3_create_success("{$count} memberships renewed.", $params, 'Membership', 'JourcoopRenew');
}

/**
 * @param array $params Info about parameters this API call supports
 */
function _civicrm_api3_Membership_JourcoopRenew_spec(&$params) { }
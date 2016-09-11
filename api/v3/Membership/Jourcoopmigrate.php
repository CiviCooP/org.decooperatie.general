<?php
/**
 * Membership.JourcoopMigrate API method.
 * One time migration of memberships from temporary data structure.
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
function civicrm_api3_membership_jourcoopmigrate($params) {

    try {
        $mm = \CRM_Jourcoop_Membership_Migrate::getInstance();
        $count = $mm->migration_20160901();
    } catch(\CRM_Jourcoop_Exception $e) {
        return civicrm_api3_create_error('Membership migration error: ' . $e->getMessage() . '.');
    }

    return civicrm_api3_create_success("{$count} memberships migrated.", $params, 'Membership', 'JourcoopMigrate');
}

/**
 * @param array $params Info about parameters this API call supports
 */
function _civicrm_api3_membership_jourcoopmigrate_spec(&$params) { }
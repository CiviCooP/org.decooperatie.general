<?php
/**
 * Activity.JourcoopCreate API method.
 * This method allows adding multiple activities of the same type to a case,
 * without the previous ones being delinked in civicrm_case_activity and/or set as is_current_revision = 0.
 * TODO: Decide if this is a great hack or not intended to be allowed for a reason.
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
 * @throws \CiviCRM_API3_Exception API_Exception
 */
function civicrm_api3_activity_jourcoopcreate($params) {

    // Get and unset case ID
    $caseId = $params['case_id'];
    unset($params['case_id']);

    // activity_type_id string nor activity_name seem to work, looking up activity type id manually
    if(!empty($params['activity_type_id']) && !is_numeric($params['activity_type_id'])) {
        $params['activity_type_id'] = \CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', $params['activity_type_id']);
    }

    // Create activity as usual
    $activityResult = civicrm_api3_activity_create($params);
    if(empty($activityResult['id'])) {
        throw new \CiviCRM_API3_Exception('Could not create activity (this exception should never be reached', 500);
    }

        // Link up case and activity ourself
        $caseActivity = new \CRM_Case_DAO_CaseActivity;
        $caseActivity->case_id = $caseId;
        $caseActivity->activity_id = $activityResult['id'];
        $caseActivity->save();
        $error_msg = $caseActivity->_lastError;
        $caseActivity->free();

        if($error_msg) {
            throw new \CiviCRM_API3_Exception('An error occurred while linking case and activity: ' . $error_msg, 500);
        }

    return civicrm_api3_create_success($activityResult, $params, 'Activity', 'JourcoopCreate');
}

/**
 * @param array $params Info about parameters this API call supports
 */
function _civicrm_api3_activity_jourcoopcreate_spec(&$params)
{
    _civicrm_api3_activity_create_spec($params);
}
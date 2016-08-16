<?php

/**
 * Class CRM_Jourcoop_Membership_Renew.
 * Checks if there are any active memberships that should be renewed for another month,
 * updates them if necessary, and generates the correct contribution entries.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 */
class CRM_Jourcoop_Membership_Renew {

    /**
     * @var static $instance Instance
     */
    protected static $instance;

    /**
     * @return static Get Instance
     */
    public static function getInstance() {
        if(empty(static::$instance)) {
            static::$instance = new self;
        }
        return static::$instance;
    }

    /**
     * Renew all active memberships!
     * @return int Number of memberships renewed
     * @throws CRM_Jourcoop_Exception Thrown if I get a panic attack
     */
    public function renewMemberships() {

        // Check all active memberships that have an end date before 'now + 1 day'. That means all memberships
        // will be renewed on the last day of each month. And if the script didn't run it will still fix it later.
        $chkEnddateBefore = new \DateTime("now +1 day");

        $memberships = civicrm_api3('Memberships', 'get', [
           'status_id' => ['New', 'Current', 'Grace'],
            'end_date' => $chkEnddateBefore->format('Ymd'),
            'options' => ['limit' => 0],
        ]);

        // End date will be last day of next month if renewing within last 2 days of the month, this month otherwise
        $renewcount = 0;
        $newEnddate = new \DateTime("last day of this month");
        if($newEnddate->diff($chkEnddateBefore)->days < 2) {
            $newEnddate = new \DateTime("last day of next month");
        }

        // Walk all memberships and try to renew them (inspired by nl.sp.renewmembership)
        if(count($memberships) > 0) {
            foreach($memberships as $membership) {

                $transaction = new \CRM_Core_Transaction;

                // WORK IN PROGRESS !!!!!!!!11
                // https://github.com/SPnl/nl.sp.renewmembership/blob/master/CRM/Renewmembership/Renew.php#L54

                // TODO Even kijken wat er gebeurt als ik gewoon deze method aanroep:
                // TODO CRM_Member_BAO_Membership::renewMembership($membership['contact_id'], $membership['type_id'], false, true, null, null, 1, $membership['id'], 0, null, null, true, null, []);

                /* // Set new membership end date
                civicrm_api3('Membership', 'create', [
                    'id' => $membership['id'],
                    'end_date' => $newEnddate->format('Ymd'),
                ]);
                */

                // Get last contribution? Ja, MembershipPayment/Contributiontransact/etc
                // Hee, er is een nieuwe Payment API voor een bestaande contributie in 4.7.

                // Contribution.create
                // MembershipPayment.create

                // Membership.create (=update einddatum)
                // https://github.com/SPnl/nl.sp.renewmembership/blob/master/CRM/Renewmembership/Renew.php#L54

                $transaction->commit();
                $renewcount++;
            }
        }


        return $renewcount;
    }

    /**
     * Get the last contribution's payment information
     */
/*
    protected static function getRenewalPayment($contributionId) {
        if (!$contributionId) {
            return false;
        }

        try {
            $contribution = civicrm_api3('Contribution', 'getsingle', array('id' => $contributionId));
            $sql = "SELECT honor_contact_id, honor_type_id FROM civicrm_contribution WHERE id = %1";
            $dao = CRM_Core_DAO::executeQuery($sql, array( 1 => array($contribution['id'], 'Integer')));
            if ($dao->fetch() && $dao->honor_contact_id) {
                $contribution['honor_contact_id'] = $dao->honor_contact_id;
                $contribution['honor_type_id'] = $dao->honor_type_id;
            }
        } catch (Exception $ex) {
            return false;
        }

        $receiveDate = new DateTime();
        $contribution['receive_date'] = $receiveDate->format('YmdHis');
        $contribution['contribution_status_id'] = 2;//pending
        $instrument_id = self::getPaymenyInstrument($contribution);
        unset($contribution['payment_instrument']);
        unset($contribution['instrument_id']);
        if ($instrument_id) {
            $contribution['contribution_payment_instrument_id'] = $instrument_id;
        }
        unset($contribution['contribution_id']);
        unset($contribution['invoice_id']);
        unset($contribution['id']);
        return $contribution;
    }

    protected static function getPaymenyInstrument($contribution) {
        if (empty($contribution['instrument_id'])) {
            return false;
        }

        $instrument_id = CRM_Core_OptionGroup::getValue('payment_instrument', $contribution['instrument_id'], 'id', 'Integer');
        if (empty($instrument_id)) {
            return false;
        }
        return $instrument_id;
    }

}
*/
}
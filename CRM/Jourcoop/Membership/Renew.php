<?php

/**
 * Class CRM_Jourcoop_Membership_Renew.
 * Checks if there are any active memberships that should be renewed for another month,
 * updates them if necessary, and generates the correct contribution entries.
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
    if (empty(static::$instance)) {
      static::$instance = new self;
    }
    return static::$instance;
  }

  /**
   * Renew all active memberships! And/or create Contributions that do not exist yet.
   * @return array Array with status information
   * @throws CRM_Jourcoop_Exception Thrown if I get a panic attack
   */
  public function renewMemberships() {

    /*
     * Even hardop nadenken (KL 20161003): eigenlijk hebben we twee aparte stappen, omdat we ook met
     * terugwerkende kracht bijdragen moeten aanmaken, en omdat een nieuw lidmaatschap al gelijk een
     * einddatum krijgt van de vólgende maand terwijl er dan nog geen bijdrage is.
     * 1. Als een lidmaatschap binnen en dag of twee verloopt of al verlopen is en geen speciale
     *    status heeft: verlengen met een maand.
     * 2. Als een lidmaatschap op New of Current staat en er is geen contributiepost voor de maand
     *    waarin de einddatum ligt: maak een bijdrage aan voor die maand én indien nodig voor alle
     *    maanden vanaf de begindatum.
     */

    // Hardcoded parameters for De Cooperatie
    $noContributionsBefore = new \DateTime('2016-09-01');
    $paymentMethodName = 'Handled by Exact';
    $retIncludeSkipped = FALSE; // For debugging

    $return = ['memberships' => [], 'contributions' => []]; // Used to return status array
    $cvapi = new \CRM_Jourcoop_CiviApi;

    // STEP 1: RENEW MEMBERSHIPS
    // End date before 'now + 1 day' -> memberships renewed on or after the last day of each month

    $chkEnddateBefore = new \DateTime("now +1 day");
    $newEndDate = new \DateTime("last day of next month");

    $memberships = $cvapi->api('Membership', 'get', [
      'status_id' => ['IN' => ['New', 'Current', 'Grace']],
      'end_date'  => ['<=' => $chkEnddateBefore->format('Ymd')],
      'options'   => ['limit' => 0],
    ]);

    if ($memberships->count > 0) {
      foreach ($memberships->values as $membership) {

        // Update end date if it's expiring soon and if it isn't later than the default
        if ($membership->end_date < $newEndDate) {
          $ret = $cvapi->api('Membership', 'update', [
            'id'       => $membership->id,
            'end_date' => $newEndDate->format('Ymd'),
          ]);
          if ($ret->is_error) {
            $return['memberships'][$membership->id] = "ERROR (" . $ret->error_message . ")";
          } else {
            $return['memberships'][$membership->id] = "OK (new end date: " . $newEndDate->format('Y-m-d') . ")";
          }
        } elseif ($retIncludeSkipped) {
          $return['memberships'][$membership->id] = "SKIP (end date unchanged: {$membership->end_date})";
        }
      }

      // Recalculate membership statuses before moving on to contributions
      $cvapi->api('Job', 'process_membership', []);
    }

    // STEP 2: CREATE CONTRIBUTIONS
    // I've considered ContributionRecur - but not currently needed and more work for admins - so
    // we're simply checking if active memberships have a contribution for the last month.
    // (Rewrite with more efficient queries once we hit 10,000 members or so...)

    $paymentMethodId = $cvapi->getOptionValue('payment_instrument', $paymentMethodName);
    $membershipTypes = $cvapi->getMembershipTypes();

    $memberships = $cvapi->api('Membership', 'get', [
      'status_id'                       => ['IN' => ['New', 'Current']],
      'options'                         => ['limit' => 0],
      'api.MembershipPayment.getsingle' => [
        'options' => ['limit' => 1, 'sort' => 'contribution_id DESC'],
      ],
    ]);

    // Walk memberships and try to create contributions
    if ($memberships->count > 0) {
      foreach ($memberships->values as $membership) {

        $createUntilDate = new \DateTime($membership->end_date);
        $mpayment = $membership->{'api.MembershipPayment.getsingle'};

        $mtype = $membershipTypes[$membership->membership_type_id];
        if ($mtype->minimum_fee == 0) {
          if ($retIncludeSkipped) {
            $return['contributions'][$membership->id] = "SKIP (contribution is 0.00)";
          }
          continue;
        }

        if (isset($mpayment->contribution_id)) {
          // Check the date of this contact's last contribution, if it exists
          // (assuming a contribution for a given month has a receive date in that month)
          $contribution = $cvapi->api('Contribution', 'getsingle', ['id' => $mpayment->contribution_id]);
          if ($contribution->is_error) {
            $return['contributions'][$membership->id] = "ERROR (contribution {$mpayment->contribution_id} not found, where have my foreign keys gone?)";
            continue;
          } else {
            $lastContribDate = new \DateTime($contribution->receive_date);
            $lastContribDate->modify('last day of this month')->setTime(23, 59, 59);
            if ($lastContribDate >= $createUntilDate) {
              if ($retIncludeSkipped) {
                $return['contributions'][$membership->id] = "OK (last contribution date " . $lastContribDate->format('Y-m-d') . ")";
              }
              continue;
            }
          }
        }

        // Default if no contribution found: start from the join date
        if (!isset($createFromDate)) {
          $createFromDate = new \DateTime($membership->join_date);
          if ($createFromDate < $noContributionsBefore) {
            $createFromDate = $noContributionsBefore;
          }
        }
        if ($createFromDate->format('j') != 1) {
          $createFromDate->modify('first day of next month');
        }

        // Try to create contributions every first of the month between the calculated dates
        /** @var \DateTime[] $createDates * */
        $createDates = new \DatePeriod($createFromDate, new \DateInterval('P1M'), $createUntilDate);
        if (iterator_count($createDates) > 0) {
          $cresultmsg = "cid {$membership->contact_id}, from {$createFromDate->format('d-m-Y')} up to {$createUntilDate->format('d-m-Y')}, details: ";

          foreach ($createDates as $cdate) {

            $contribution = $cvapi->api('Contribution', 'create', [
              'contact_id'             => $membership->contact_id,
              'payment_instrument_id'  => $paymentMethodId,
              'total_amount'           => $mtype->minimum_fee,
              'financial_type_id'      => $mtype->financial_type_id,
              'receive_date'           => $cdate->format('Ymd'),
              'contribution_status_id' => 'Completed', // Required to create trxns to export
              'note'                   => 'Contributie De Cooperatie ' . $cdate->format('m/Y'),
              'source'                 => 'Auto (' . date('d-m-Y') . ')',
            ]);
            if ($contribution->is_error) {
              $return['contributions'][$membership->id] = "ERROR (" . $contribution->eror_message . " at date " . ($cdate ? $cdate->format('Y-m-d') : "unknown") . ", " . $cresultmsg . ")";
            } else {
              $mpayment = $cvapi->api('MembershipPayment', 'create', [
                'membership_id'   => $membership->id,
                'contribution_id' => $contribution->id,
              ]);
              $cresultmsg .= $cdate->format('Y-m-d') . ' CONT OK, MP ' . ($mpayment->is_error ? 'ERROR' : 'OK') . '. ';
            }
          }
          $return['contributions'][$membership->id] = "OK (" . $cresultmsg . ")";
        } elseif ($retIncludeSkipped) {
          $return['contributions'][$membership->id] = "SKIP (nothing to do)";
        }

      }
    }

    // That's all!
    return array_merge([
      'count' => [
        'memberships'   => count($return['memberships']),
        'contributions' => count($return['contributions']),
      ],
    ], $return);
  }

}
<?php

/**
 * Class CRM_Jourcoop_Membership_Migrate. One-off membership migration script(s).
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 */
class CRM_Jourcoop_Membership_Migrate {

  /**
   * @var static $instance Instance
   */
  protected static $instance;

  /**
   * @return static Get instance
   */
  public static function getInstance() {
    if (empty(static::$instance)) {
      static::$instance = new self;
    }
    return static::$instance;
  }

  /**
   * Migratie 2016-09-01: Vanuit tijdelijke velden de juiste lidmaatschappen aanmaken
   * en definitieve velden vullen. Eenmalig uit te voeren, daarna tijdelijke velden verwijderen.
   * @throws CRM_Jourcoop_Exception Thrown if Kevin screws up
   */
  public function migration_20160901() {

    $cvapi = new CRM_Jourcoop_CiviApi;

    // Get/set settings and field ids
    $groupNames = [
      'leads'   => 'Ge_mporteerde_leads_7', // Will get pending membership
      'members' => ['Geimporteerde_inschrijvingen_4'], // Will get active membership
      'new' => ['Nieuwe_inschrijvingen_website_3'], // Will get active membership
    ];
    $tempFields = [
      'NVJ_Member'   => $cvapi->getCustomFieldId('Leden_Tijdelijk', 'Lid_NVJ', TRUE),
      'NVJ_Since'    => $cvapi->getCustomFieldId('Leden_Tijdelijk', 'Lid_NVJ_sinds', TRUE),
      'IBAN'         => $cvapi->getCustomFieldId('Leden_Tijdelijk', 'Rekeningnummer_IBAN', TRUE),
      'Aanmelddatum' => $cvapi->getCustomFieldId('Leden_Tijdelijk', 'Aanmelddatum', TRUE),
      // 'Incasso' => $cvapi->getCustomFieldId('Leden_Tijdelijk', 'Akkoord_Incasso', TRUE),
      // 'Lid_Coop' => $cvapi->getCustomFieldId('Leden_Tijdelijk', 'Lid_Cooperatie', TRUE),
      'Werkplek'     => $cvapi->getCustomFieldId('Leden_Tijdelijk', 'Werkplekvoorkeur', TRUE),
    ];
    $newFields = [
      'NVJ_Member' => $cvapi->getCustomFieldId('Administrative_Data', 'NVJ_Member', TRUE),
      'NVJ_Since'  => $cvapi->getCustomFieldId('Administrative_Data', 'NVJ_Member_Since', TRUE),
      'IBAN'       => $cvapi->getCustomFieldId('Administrative_Data', 'Bank_Account_IBAN', TRUE),
      'Werkplek'   => $cvapi->getCustomFieldId('Member_Profile', 'Werkplekvoorkeur', TRUE),
      'Werkervaring' => $cvapi->getCustomFieldId('Member_Profile', 'Werkervaring', TRUE),
    ];
    $websiteTypeMapping = [
      'website' => 'Main',
      'facebook_link' => 'Facebook',
      'twitter_profiel' => 'Twitter',
      'linkedin_profiel' => 'LinkedIn',
      'instagram_profiel' => 'Instagram',
    ];
    $contactApiParams = [
      'contact_type'       => 'Individual',
      'options'            => ['limit' => 9999],
      'api.Membership.getsingle' => [],
      'api.UFMatch.getsingle' => [],
      'return'             => array_merge(array_values($tempFields), ['id', 'display_name']),
    ];

    // You never know...
    if (!function_exists('get_userdata') || !function_exists('get_user_meta')) {
      throw new CRM_Jourcoop_Exception('WordPress user functions do not exist - are we running on WP?');
    }

    // Get contacts
    $cmembers = $cvapi->api('Contact', 'get', array_merge($contactApiParams, ['group' => $groupNames['members']]));
    $cleads = $cvapi->api('Contact', 'get', array_merge($contactApiParams, ['group' => $groupNames['leads']]));
    $cnew = $cvapi->api('Contact', 'get', array_merge($contactApiParams, ['group' => $groupNames['leads']]));
    foreach ($cmembers->values as &$m) { $m->action = 'active'; $m->source = 'Geïmporteerde inschrijvingen'; }
    foreach ($cleads->values as &$l) { $l->action = 'lead'; $l->source = 'Geïmporteerde leads'; }
    foreach($cnew->values as &$n) { $n->action = 'new'; $n->source = 'Via website zomer 2016'; }
    $contacts = array_merge($cmembers->values, $cleads->values, $cnew->values);

    // Walk contacts array
    foreach ($contacts as $c) {

      // Fetch Wordpress metadata, too
      $wpmeta = [];
      if(empty($c->{'api.UFMatch.getsingle'}->is_error) && isset($c->{'api.UFMatch.getsingle'}->uf_id)) {
        $uf_id = $c->{'api.UFMatch.getsingle'}->uf_id;
        $wpmeta = array_map(function ($a) { return $a[0]; }, get_user_meta($uf_id));
        $wpuserdata = get_userdata($uf_id);
        $wpmeta['website'] = $wpuserdata->user_url;
      }

      // Update new contact data fields
      $isNvjMember = &$c->{$tempFields['Lid_NVJ']};
      $cparams = [
        'contact_id'             => $c->contact_id,
        'job_title' => (!empty($wpmeta['functie']) ? $wpmeta['functie'] : ''),
        $newFields['Werkervaring'] => (!empty($wpmeta['description']) ? $wpmeta['description'] : ''),
        $newFields['NVJ_Member'] => $isNvjMember,
        $newFields['NVJ_Since']  => $c->{$tempFields['Lid_NVJ_sinds']},
        $newFields['IBAN']       => $c->{$tempFields['IBAN']},
        $newFields['Werkplek'] => $c->{$tempFields['Werkplek']},
      ];
      $cret = $cvapi->api('Contact', 'create', $cparams);
      if($cret->is_error) {
        throw new \CRM_Jourcoop_Exception('Could not update contact (' . $cret->error_message . ')! We tried these parameters: ' . print_r($cparams, true));
      }

      // Add a new membership if none exists yet
      if(empty($c->{'api.Membership.getsingle'}->is_error) && $c->{'api.Membership.getsingle'}->count > 0) {
        continue;
      }
      $mparams = [
        'contact_id' => $c->contact_id,
        'membership_type_id' => ($isNvjMember ? 'Lid_NVJ' : 'Lid'),
        'join_date' => ($c->{$tempFields['Aanmelddatum']}),
        'start_date' => '2016-09-01 00:00:00',
        'num_terms' => 1,
        'source' => $c->source,
      ];
      $mret = $cvapi->api('Membership', 'create', $mparams);
      if($mret->is_error) {
        throw new \CRM_Jourcoop_Exception('Could not create membership for contact (' . $mret->error_message . ')! We tried these parameters: ' . print_r($mparams, true));
      }

      // Add website and social profiles to CiviCRM (and clean up in / make available to WP afterwards?)
      foreach($websiteTypeMapping as $wpMetaName => $civiWebsiteType) {
        if(!empty($wpmeta[$wpMetaName])) {
          $cvapi->api('Website', 'create', [
            'contact_id' => $c->contact_id,
            'website_type_id' => $civiWebsiteType,
            'url' => $wpmeta[$wpMetaName],
          ]);
        }
      }

      // That should do the trick! (Next steps: testing, backing up live and running it there)
    }

    return TRUE;
  }

}
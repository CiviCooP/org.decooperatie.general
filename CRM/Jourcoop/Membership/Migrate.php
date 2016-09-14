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
     * Migratie september: Vanuit tijdelijke velden de juiste lidmaatschappen aanmaken
     * en definitieve velden vullen. Eenmalig uit te voeren, daarna tijdelijke velden verwijderen.
     * @throws CRM_Jourcoop_Exception Thrown if Kevin screws up
     */
    public function migrateTemporaryMembershipData() {

        $cvapi = new CRM_Jourcoop_CiviApi;

        // Get/set settings and field ids
        // ?? Regression? Searching by group name does not work anymore
        $groupNames = [
            'leads'   => $cvapi->getContactGroupId('Ge_mporteerde_leads_7'), // Will get pending membership
            'members' => $cvapi->getContactGroupId('Geimporteerde_inschrijvingen_4'), // Will get active membership
            'new' => $cvapi->getContactGroupId('Nieuwe_inschrijvingen_website_3'), // Will get active membership
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
            'NVJ_Member' => $cvapi->getCustomFieldId('Jourcoop_Administrative_Data', 'NVJ_Member', TRUE),
            'NVJ_Since'  => $cvapi->getCustomFieldId('Jourcoop_Administrative_Data', 'NVJ_Member_Since', TRUE),
            'IBAN'       => $cvapi->getCustomFieldId('Jourcoop_Administrative_Data', 'Bank_Account_IBAN', TRUE),
            'Werkplek'   => $cvapi->getCustomFieldId('Jourcoop_Member_Profile', 'Werkplekvoorkeur', TRUE),
            'Werkervaring' => $cvapi->getCustomFieldId('Jourcoop_Member_Profile', 'Werkervaring', TRUE),
            'Importnotities' => $cvapi->getCustomFieldId('Jourcoop_Member_Profile', 'Import_Notes', TRUE),
        ];
        $websiteTypeMapping = [
            'work' => 'Work',
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
        $count = 0;

        // You never know...
        if (!function_exists('get_userdata') || !function_exists('get_user_meta')) {
            throw new CRM_Jourcoop_Exception('WordPress user functions do not exist - are we running on WP?');
        }

        // Get contacts
        $cmembers = $cvapi->api('Contact', 'get', array_merge($contactApiParams, ['group' => $groupNames['members']]));
        $cleads = $cvapi->api('Contact', 'get', array_merge($contactApiParams, ['group' => $groupNames['leads']]));
        $cnew = $cvapi->api('Contact', 'get', array_merge($contactApiParams, ['group' => $groupNames['new']]));
        if($cmembers->is_error || $cleads->is_error || $cnew->is_error) {
            throw new CRM_Jourcoop_Exception('Could not fetch members (Contact.get API error).');
        }

        foreach ($cmembers->values as &$m) { $m->action = 'active'; $m->source = 'Geimporteerde inschrijving'; }
        foreach ($cleads->values as &$l) { $l->action = 'lead'; $l->source = 'Geimporteerde lead'; }
        foreach($cnew->values as &$n) { $n->action = 'new'; $n->source = 'Online zomer 2016'; }
        $contacts = array_merge($cmembers->values, $cleads->values, $cnew->values);

        // Walk contacts array
        foreach ($contacts as $c) {
            $this->debug("START: Migrating contact {$c->contact_id} ({$c->display_name}), type: {$c->action}.");

            // Fetch Wordpress metadata, too
            $wpmeta = [];
            if(empty($c->{'api.UFMatch.getsingle'}->is_error) && isset($c->{'api.UFMatch.getsingle'}->uf_id)) {
                $uf_id = $c->{'api.UFMatch.getsingle'}->uf_id;
                $wpmeta = array_map(function ($a) { return $a[0]; }, get_user_meta($uf_id));
                $wpuserdata = get_userdata($uf_id);
                $wpmeta['work'] = $wpuserdata->user_url;
            }
            $this->debug("Migrating contact {$c->contact_id} ({$c->display_name}");

            // Update new contact data fields
            $isNvjMember = empty($c->{$tempFields['NVJ_Member']}) ? 0 : ($c->{$tempFields['NVJ_Member']});
            $wplocaties = []; // Hoofdpijndossier
            if(!empty($c->{$tempFields['Werkplek']})) {
                foreach (['Amsterdam', 'Den Haag', 'Rotterdam', 'Hilversum'] as $wplocatie) {
                    if(strpos($c->{$tempFields['Werkplek']}, $wplocatie) !== false) {
                        $wplocaties[] = strtolower(preg_replace('/[^a-zA-Z]+/', '', $wplocatie));
                    }
                }
            }
            $cparams = [
                'contact_id'             => $c->contact_id,
                'job_title' => (!empty($wpmeta['functie']) ? $wpmeta['functie'] : ''),
                $newFields['Werkervaring'] => (!empty($wpmeta['description']) ? $wpmeta['description'] : ''),
                $newFields['NVJ_Member'] => $isNvjMember,
                $newFields['NVJ_Since']  => (!empty($c->{$tempFields['NVJ_Since']}) ? date('Ymd',strtotime($c->{$tempFields['NVJ_Since']})) : ''),
                $newFields['IBAN']       => (!empty($c->{$tempFields['IBAN']}) ? $c->{$tempFields['IBAN']} : ''),
                $newFields['Werkplek'] => $wplocaties,
            ];
            $this->debug("Calling Contact.create with params: " . print_r($cparams, true));
            $cret = $cvapi->api('Contact', 'create', $cparams);
            if($cret->is_error) {
                throw new \CRM_Jourcoop_Exception('Could not update contact (' . $cret->error_message . ')! We tried these parameters: ' . print_r($cparams, true));
            }

            // Add a new membership if none exists yet
            if(!empty($c->{'api.Membership.getsingle'}->is_error) && $c->{'api.Membership.getsingle'}->count > 0) {
                $this->debug("A membership already exists for contact (" . print_r($c->{'api.Membership.getsingle'}, true));
                continue;
            }
            $mparams = [
                'contact_id' => $c->contact_id,
                'membership_type_id' => ($isNvjMember ? 'Lid (NVJ)' : 'Lid'),
                'join_date' => (!empty(($c->{$tempFields['Aanmelddatum']})) ? (date('Ymd',strtotime($c->{$tempFields['Aanmelddatum']}))) : null),
                'start_date' => '20160901',
                // 'num_terms' => 1,
                'source' => $c->source,
            ];
            if($c->action == 'lead') {
                $mparams['status_id'] = 'Pending';
                $mparams['is_override'] = 1;
            }
            $this->debug("Calling Membership.create with params: " . print_r($mparams, true));
            $mret = $cvapi->api('Membership', 'create', $mparams);
            if($mret->is_error) {
                throw new \CRM_Jourcoop_Exception('Could not create membership for contact (' . $mret->error_message . ')! We tried these parameters: ' . print_r($mparams, true));
            }

            print_r($wpmeta);
            // Add website and social profiles to CiviCRM (and clean up in / make available to WP afterwards?)
            foreach($websiteTypeMapping as $wpMetaName => $civiWebsiteType) {
                if(!empty($wpmeta[$wpMetaName])) {
                    $wparams = [
                        'contact_id' => $c->contact_id,
                        'website_type_id' => $civiWebsiteType,
                        'url' => $wpmeta[$wpMetaName],
                        'is_primary' => ($wpMetaName == 'work'),
                    ];
                    $this->debug("Calling Website.create with params: " . print_r($wparams, true));
                    $cvapi->api('Website', 'create', $wparams);
                }
            }

            // That should do the trick!
            $count++;
            $this->debug("END: Migrated contact $count of " . count($contacts) . ".");
        }

        return $count;
    }

    private function debug($msg) {
        echo $msg . "\n";
    }

}
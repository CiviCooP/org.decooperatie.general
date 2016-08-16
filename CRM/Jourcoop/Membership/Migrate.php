<?php

/**
 * Class CRM_Jourcoop_Membership_Migrate. One-off membership migration script(s).
 *
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
     * @return static Get Instance
     */
    public static function getInstance() {
        if(empty(static::$instance)) {
            static::$instance = new self;
        }
        return static::$instance;
    }

    /**
     * Migratie 2016-08-15: Vanuit tijdelijke velden
     */
    public function migration_20160815() {

        CRM_Core_Session::setStatus('Yeah, well, maybe I didn\'t feel like finishing this upgrade script just yet.');
        return false;

        /**
         * Velden onder Leden_Tijdelijk (3):
         * Lid_NVJ (boolean)
         * Aanmelddatum (date)
         * Akkoord_Incasso (boolean)
         * Rekeningnummer_IBAN (string)
         * Werkplekvoorkeur (text)
         * Lid_Cooperatie (boolean)
         * Lid_NVJ_sinds (date)
         *
         *
         * Nieuwe velden onder Administrative_Data (10):
         * NVJ_Member (boolean)
         * NVJ_Member_Since (date)
         * Bank_Account_IBAN (text)
         *
         */
    }

}
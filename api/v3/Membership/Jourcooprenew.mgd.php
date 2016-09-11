<?php
/**
 * This file adds a cron job object that will be automatically managed by CiviCRM (hook_civicrm_managed).
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 */

return [
        [
            'name'   => 'Cron:Membership.JourcoopRenew',
            'entity' => 'Job',
            'params' =>
                [
                    'version'       => 3,
                    'name'          => 'Call Membership.JourcoopRenew API',
                    'description'   => 'Updates active memberships that should be renewed for another month.',
                    'run_frequency' => 'Daily',
                    'api_entity'    => 'Membership',
                    'api_action'    => 'Jourcooprenew',
                ],
        ],
];
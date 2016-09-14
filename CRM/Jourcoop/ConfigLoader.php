<?php

/**
 * Class CRM_Jourcoop_ConfigLoader.
 * This class loads important entity types from JSON files using the org.civicoop.configitems extension.
 *
 * @author Kevin Levie <kevin.levie@civicoop.org>
 * @package org.decooperatie.general
 * @license AGPL-3.0
 */
class CRM_Jourcoop_ConfigLoader
{

    /**
     * Call CiviConfig Loader for JSON files in /json/configitems/, if we haven't already.
     * Does not catch loader exceptions: instead, just make sure your JSON syntax is correct.
     */
    public static function run()
    {
        // Check if this function has already run (note: this is >= 4.7 only syntax!)
        $configLoaded = \Civi::settings()->get('org.decooperatie.general.configLoaded');

        if (!isset($configLoaded) || $configLoaded == false) {

            $jsonPath = realpath(__DIR__ . '/../../json/configitems/');

            if (!$jsonPath) {
                throw new \CRM_Jourcoop_Exception('Cannot load JSON config items: directory not found.');
            }

            // Call loader
            $loader = new \CRM_Civiconfig_Loader;
            $result = $loader->updateConfigurationFromJson($jsonPath);

            // Set configLoaded = true, and show status message with result
            \Civi::settings()->set('org.decooperatie.general.configLoaded', true);

            \CRM_Core_Session::setStatus("Imported Jourcoop JSON config files. This is the output we got from the Civiconfig Loader:\n\n" . nl2br(print_r($result, true)) . "\n");
        }
    }

}
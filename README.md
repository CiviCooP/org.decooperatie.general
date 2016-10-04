org.decooperatie.general
========================

CiviCRM extension with functionality and settings specifically for [DeCooperatie.org](https://decooperatie.org).  
This extension currently contains:

* Important CiviCRM configuration, stored in JSON files that can be loaded by calling the `Civiconfig.load_json` API method (requires the [org.civicoop.configitems](https://github.com/civicoop/org.civicoop.configitems) extension)
* Two custom API methods and tasks: `Membership.JourcoopMigrate` is a one-off migration script; `Membership.JourcoopRenew` is used as a cron task to automatically renew memberships and generate contributions every month.


org.decooperatie.general
========================

CiviCRM extension that contains general settings and functions written
specifically for [DeCooperatie.org](https://decooperatie.org).

It currently contains important CiviCRM configuration (stored in JSON files and loaded using [org.civicoop.configitems](https://github.com/civicoop/org.civicoop.configitems), which must be installed), and custom API methods and tasks specifically written for De Cooperatie. 


------------------------

Note to self, call to import JSON data in the staging environment:

```php
$result = civicrm_api3('Civiconfig', 'load_json', [
  'path' => "/srv/users/serverpilot/apps/cooperatie-staging/public/wp-content/uploads/civicrm/ext/org.decooperatie.general/json/configitems",
]);
```

org.decooperatie.general
========================

CiviCRM extension that contains general settings and functions written
specifically for [DeCooperatie.org](https://decooperatie.org).

This extension requires the [org.civicoop.configitems](https://github.com/civicoop/org.civicoop.configitems)
extension (and probably many more).

------------------------

Call to import JSON data in the staging environment:

```php
$result = civicrm_api3('Civiconfig', 'load_json', [
  'path' => "/srv/users/serverpilot/apps/cooperatie-staging/public/wp-content/uploads/civicrm/ext/org.decooperatie.general/json/configitems",
]);
```

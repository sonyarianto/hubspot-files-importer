# hubspot-files-importer
Doing folders and files import? Maybe our script can help your task.

## PHP
Setup (using Composer)

```
composer require "hubspot/hubspot-php"
```

Quickstart

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/HubSpotFilesImporter.php';

$hubspot = new HubSpotFileDiscovery("PUT_YOUR_HUBSPOT_TOKEN_HERE");

// quick samples of available methods

echo json_encode($hubspot->getAllFolders()); // get all folders
echo json_encode($hubspot->getAllFiles()); // get all files
echo json_encode($hubspot->getAllNodes()); // get all data in array of nodes (folders and files combined)
echo json_encode($hubspot->getTreeNodes()); // get all data in array of nodes respecting tree format (folders and files combined) 
echo json_encode($hubspot->getBreadcrumbs($hubspot->getAllNodes(), 'NODE_ID_HERE')); // get breadcrumbs for particular node id

// import all folder structure and all files to local disk targeting particular local folder

$hubspot->createDiskStructureFromTree($hubspot->getTreeNodes(), __DIR__ . '/files');
```

## License
MIT

Maintained by Sony Arianto Kurniawan <<sony@sony-ak.com>> and contributors.

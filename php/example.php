<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/HubSpotFilesImporter.php';

$hubspot = new HubSpotFilesImporter('YOUR_HUBSPOT_TOKEN_HERE');

echo json_encode($hubspot->getAllFolders());
<?php
require 'vendor/autoload.php';

use Achachi\GDrive;

list($drive, $token) = GDrive::getInstance();

$authUrl = $drive->getAuthUrl();
echo '<a href="', htmlentities($authUrl, ENT_QUOTES), '">Connect to GDrive</a><br>';

if (!$token) {
    return;
}

echo '<pre>';
echo "[connected]\n";

echo sprintf('<a href="%s" download="%s">%s</a>', 'SCEP.pdf', 'SCEP.pdf', 'SCEP.pdf'),"\n";
foreach($drive->listInPath('kindle') as $file) {
    echo sprintf('<a href="%s" download="%s">%s</a>', 'download.php?id=' . $file->id, $file->name, $file->name),"\n";
}


<?php
require 'vendor/autoload.php';

use Achachi\GDrive;

function loadJson($path)
{
    if (!file_exists($path)) {
        return false;
    }
    return json_decode(file_get_contents($path), true);
}

$path = getenv('token.json');
if (isset($_REQUEST['code'])) {
    $drive = new GDrive(getenv('host.url') . "/index.php", false);
    $token = $drive->fetchAccessToken($_REQUEST['code']);
    file_put_contents($path, json_encode($token));
    header('Location: ' . getenv('host.url') . "/index.php");
    return;
} else {
    $token = loadJson($path);
    $drive = new GDrive(getenv('host.url') . "/index.php", $token);
}

$authUrl = $drive->getAuthUrl();
echo '<a href="', htmlentities($authUrl, ENT_QUOTES), '">Connect to GDrive</a><br>';

if (!$token) {
    return;
}

echo '<pre>';
echo "[connected]\n";

foreach($drive->listInPath('kindle') as $file) {
    echo sprintf('<a href="%s" download="%s">%s</a>', $drive->downloadLink($file), $file->name, $file->name),"\n";
}


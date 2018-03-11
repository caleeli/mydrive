<?php
require 'vendor/autoload.php';

use Achachi\GDrive;

if (empty($_REQUEST['id'])) {
    return;
} else {
    $id = $_REQUEST['id'];
}

list($drive, $token) = GDrive::getInstance();

$file = $drive->service->files->get($id);
header(sprintf('Content-Disposition: attachment; filename="%s"', $file->name));
echo $drive->getContent($file);

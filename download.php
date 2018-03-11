<?php
require 'vendor/autoload.php';

use Achachi\GDrive;

if (empty($_REQUEST['id'])) {
    return;
} else {
    $id = $_REQUEST['id'];
}

list($drive, $token) = GDrive::getInstance();
set_time_limit(0);

$file = $drive->service->files->get($id);
if (substr($file->name, -5) === '.mobi') {
    $mime = 'application/x-mobipocket-ebook';
} else {
    $mime = 'application/force-download';
}
header('Content-Type: ' . $mime);
header(sprintf('Content-Disposition: attachment; filename="%s"', $file->name));
echo $drive->getContent($file);

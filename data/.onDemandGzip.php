<?php

if (!isset($_GET['file'])) die('Usage: request filename.csv.gz -- e.g. bitfinex-tick.csv.gz');
$file = basename($_GET['file']);

if (!file_exists($file)) exit;
if ($file === '.htaccess' || $file === '.onDemandGzip.php') exit;

$cmd = 'gzip -k -q -c ' . escapeshellarg($file);
header("Content-Type: application/gzip");
header('Content-Disposition: attachment; filename="' . $file . '.gz"');
passthru($cmd);

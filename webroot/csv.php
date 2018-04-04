<?php

$dir=dirname(__FILE__)."/"."csv"."/";
$filename=$_GET["filename"];
$path=$dir.$filename;
if(!file_exists($path)) exit;

$csv=file_get_contents($path);
unlink($path);
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename={$filename}"); 
header("Content-Type:text/csv;charset=Shift-Jis"); 
//header("Content-Type:text/csv;charset=UTF-8"); 
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".strlen($csv));
echo $csv;
exit;


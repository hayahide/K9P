<?php

function getFileName(){

	$scriptname=$_SERVER["SCRIPT_NAME"];
	$pathinfo=pathinfo($scriptname);
	$filename=$pathinfo["filename"];
	return $filename;
}

function css($filename){

	header('Content-Type: text/css; charset=utf-8');
	require_once(dirname(dirname(__FILE__))."/"."{$filename}.css");
}

?>

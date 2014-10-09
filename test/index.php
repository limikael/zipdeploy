<?php

	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	require_once __DIR__."/../src/ZipDeploy.php";

	$zipDeploy=new ZipDeploy();
	$zipDeploy->createTarget("test");
	$zipDeploy->dispatch();

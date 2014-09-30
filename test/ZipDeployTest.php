<?php

	require_once __DIR__."/../src/ZipDeploy.php";

	class ZipDeployTest extends \PHPUnit_Framework_TestCase {

		function testCreateTarget() {
			$z=new ZipDeploy();

			$z->createTarget("testtarget")->
				setKey("hello")->
				setZipDir("doc");
		}

		function testExtract() {
			$z=new ZipDeploy();

			$z->setTempDir("tmp");

			$z->createTarget("test")->
				setKey("hello")->
				setZipDir("doc")->
				setTargetDir(__DIR__."/testing");

			$_REQUEST["target"]="test";
			$_REQUEST["key"]="hello";

			$z->setInputFileName(__DIR__."/doc.zip");
			$z->dispatch();
		}

		function testPutFile() {
			$z=new ZipDeploy();

			if (file_exists("helloworld.zip"))
				unlink("helloworld.zip");

			$_REQUEST["putfile"]="helloworld.zip";

			$z->setInputFileName(__DIR__."/doc.zip");
			$z->setPutFileEnabled();
			$z->dispatch();

			$this->assertTrue(file_exists("helloworld.zip"));

			if (file_exists("helloworld.zip"))
				unlink("helloworld.zip");
		}

		/**
		 * @expectedException Exception
		 */
		function testDispatchWrongKey() {
			$z=new ZipDeploy();

			$z->createTarget("test")->
				setKey("hello")->
				setZipDir("doc");

			$_REQUEST["target"]="test";
			$_REQUEST["key"]="wrong";

			$z->dispatch();
		}
	}

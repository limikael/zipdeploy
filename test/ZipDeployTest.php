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
			if (file_exists(__DIR__."/testing"))
				ZipDeploy::delTree(__DIR__."/testing");

			$z=new ZipDeploy();

			$z->setTempDir("tmp");

			$target=$z->createTarget("test")->
				setKey("hello")->
				setZipDir("doc")->
				setTargetDir(__DIR__."/testing");

			$_REQUEST["target"]="test";
			$_REQUEST["key"]="hello";

			$z->setInputFileName(__DIR__."/doc.zip");
			$z->dispatch();

			$this->assertTrue(file_exists(__DIR__."/testing"));
			$this->assertTrue(file_exists(__DIR__."/testing/hello.txt"));

			file_put_contents(__DIR__."/testing/testkeep", "hello");
			$z->dispatch();
			$this->assertFalse(file_exists(__DIR__."/testing/testkeep"));

			$target->addKeep("testkeep");

			file_put_contents(__DIR__."/testing/testkeep", "hello");
			$z->dispatch();
			$this->assertTrue(file_exists(__DIR__."/testing/testkeep"));

			if (file_exists(__DIR__."/testing"))
				ZipDeploy::delTree(__DIR__."/testing");
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

		function testSyncDir() {
			if (file_exists(__DIR__."/synctest_copy"))
				ZipDeploy::delTree(__DIR__."/synctest_copy");

			$dirSync=new DirSync(__DIR__."/synctest",__DIR__."/synctest_copy");
			$dirSync->sync();

			$this->assertTrue(file_exists(__DIR__."/synctest_copy"));
			$this->assertTrue(file_exists(__DIR__."/synctest_copy/file.txt"));
			$this->assertTrue(file_exists(__DIR__."/synctest_copy/another_file.txt"));

			file_put_contents(__DIR__."/synctest_copy/removeme.txt", "dummy");
			file_put_contents(__DIR__."/synctest_copy/some_dir/removeme_too.txt", "dummy");
			file_put_contents(__DIR__."/synctest_copy/some_dir/keepme.txt", "dummy");

			$dirSync=new DirSync(__DIR__."/synctest",__DIR__."/synctest_copy");
			$dirSync->addKeep("some_dir/keepme.txt");
			$dirSync->sync();

			$this->assertFalse(file_exists(__DIR__."/synctest_copy/removeme.txt"));
			$this->assertFalse(file_exists(__DIR__."/synctest_copy/some_dir/removeme_too.txt"));
			$this->assertTrue(file_exists(__DIR__."/synctest_copy/some_dir/keepme.txt"));

			if (file_exists(__DIR__."/synctest_copy"))
				ZipDeploy::delTree(__DIR__."/synctest_copy");
		}
	}

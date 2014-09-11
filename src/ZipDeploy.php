<?php

	/**
	 * A deploy target.
	 */
	class ZipDeployTarget {
		private $key;
		private $zipDir;
		private $targetDir;

		/**
		 * Set key.
		 */
		public function setKey($value) {
			$this->key=$value;
			$this->targetDir=$value;
			$this->getZipDir="";
			return $this;
		}

		/**
		 * Set zip dir.
		 */
		public function setZipDir($value) {
			$this->zipDir=$value;
			return $this;
		}

		/**
		 * Authenticate.
		 */
		public function authenicate() {
			if ($this->key)
				if ($this->key!=$_REQUEST["key"])
					throw new Exception("Wrong key");
		}

		/**
		 * Set target dir.
		 */
		public function setTargetDir($value) {
			$this->targetDir=$value;
		}

		/**
		 * Get target dir.
		 */
		public function getTargetDir() {
			return $this->targetDir;
		}

		/**
		 * Get dir inside zip.
		 */
		public function getZipDir() {
			return $this->zipDir;
		}
	}

	/**
	 * Deploy stuff to speficied dir.
	 */
	class ZipDeploy {

		private $targetsByName;
		private $inputFileName;

		/**
		 * Construct.
		 */
		public function ZipDeploy() {
			$this->targetsByName=array();
			$this->inputFileName="php://input";
			$this->tempDir=tempnam(sys_get_temp_dir(),"zip");
		}

		/**
		 * Set temp dir.
		 */
		public function setTempDir($path) {
			$this->tempDir=$path;
		}

		/**
		 * Create a target.
		 */
		public function createTarget($name) {
			$target=new ZipDeployTarget($name);
			$this->targetsByName[$name]=$target;

			return $target;
		}

		/**
		 * Set input file name. For testing.
		 */
		public function setInputFileName($name) {
			$this->inputFileName=$name;
		}

		/**
		 * Dispatch.
		 */
		public function dispatch() {
			$targetName=$_REQUEST["target"];
			$target=$this->targetsByName[$targetName];

			if (!$target)
				throw new Exception("No such target: ".$targetName);

			$target->authenicate();

			$zip=new ZipArchive();
			$zip->open($this->inputFileName);

			if (file_exists($target->getTargetDir())) {
				if (!self::delTree($target->getTargetDir()))
					throw new Error("Unable to remove old contents");
			}

			if (file_exists($this->tempDir))
				self::delTree($this->tempDir);

			$zip->extractTo($this->tempDir);
			$zip->close();

			rename($this->tempDir."/".$target->getZipDir(),$target->getTargetDir());

			if (file_exists($this->tempDir))
				self::delTree($this->tempDir);
		}

		/**
		 * Remove a directory including all contents.
		 */
		private static function delTree($dir) {
			if (is_file($dir))
				return unlink($dir);

			$files = array_diff(scandir($dir), array('.','..')); 

			foreach ($files as $file) { 
				if (is_dir("$dir/$file"))
					self::delTree("$dir/$file");

				else
					unlink("$dir/$file"); 
			} 

			return rmdir($dir); 
		}
	}
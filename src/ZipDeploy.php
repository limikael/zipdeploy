<?php

	/**
	 * A deploy target.
	 */
	class ZipDeployTarget {
		private $key;
		private $zipDir;
		private $targetDir;
		private $name;

		public function ZipDeployTarget($name) {
			$this->name=$name;
			$this->targetDir=$name;
			$this->zipDir="";
		}

		/**
		 * Set key.
		 */
		public function setKey($value) {
			$this->key=$value;
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
		private $putFileEnabled;

		/**
		 * Construct.
		 */
		public function ZipDeploy() {
			$this->targetsByName=array();
			$this->inputFileName="php://input";
			$this->tempDir=tempnam(sys_get_temp_dir(),"zip");
			$this->tmpZipFileName="__uploaded.zip";
			$this->putFileEnabled=FALSE;
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
		 * Set put file enabled.
		 */
		public function setPutFileEnabled($value=TRUE) {
			$this->putFileEnabled=$value;
		}

		/**
		 * Dispatch.
		 */
		public function dispatch() {
			if (isset($_REQUEST["target"]))
				$this->dispatchTarget();

			if (isset($_REQUEST["putfile"]) && $this->putFileEnabled)
				$this->dispatchPutFile();
		}

		/**
		 * Dispatch put file.
		 */
		private function dispatchPutFile() {
			$content=file_get_contents($this->inputFileName);

			$putRes=file_put_contents($_REQUEST["putfile"], $content);
			if (!$putRes)
				throw new Exception("unable to copy ".print_r(error_get_last(),TRUE));

			echo "OK";
		}

		/**
		 * Dispatch target.
		 */
		private function dispatchTarget() {
			$targetName=$_REQUEST["target"];
			$target=$this->targetsByName[$targetName];

			if (!$target)
				throw new Exception("No such target: ".$targetName);

			$target->authenicate();

			$content=file_get_contents($this->inputFileName);
			//echo "here, size: ".strlen($content);

			if (!$content || !strlen($content))
            	throw new Exception("No POST input.");

			$putRes=file_put_contents($this->tmpZipFileName,$content);
			if (!$putRes)
				throw new Exception("unable to copy ".print_r(error_get_last(),TRUE));

			/*$copyRes=copy($this->inputFileName,$this->tmpZipFileName);
			if ($copyRes!==TRUE)
				throw new Exception("unable to copy ".print_r(error_get_last(),TRUE));*/

			$zip=new ZipArchive();
			$openRes=$zip->open($this->tmpZipFileName);

			if ($openRes!==TRUE)
				throw new Exception("open failed: ".$openRes);

			if (file_exists($target->getTargetDir())) {
				if (!self::delTree($target->getTargetDir()))
					throw new Error("Unable to remove old contents");
			}

			if (file_exists($this->tempDir))
				self::delTree($this->tempDir);

			$extractRes=$zip->extractTo($this->tempDir);
			if (!$extractRes)
				throw new Exception("unable to extract ".print_r(error_get_last(),TRUE));

			$zip->close();

			rename($this->tempDir."/".$target->getZipDir(),$target->getTargetDir());

			if (file_exists($this->tempDir))
				self::delTree($this->tempDir);

			self::delTree($this->tmpZipFileName);

			echo "OK";
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
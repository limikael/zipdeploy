<?php

	/**
	 * Directory sync.
	 */
	class DirSync {
		private $from;
		private $to;
		private $keep;

		/**
		 * Construct.
		 */
		public function DirSync($from, $to) {
			$this->from=$from;
			$this->to=$to;
			$this->keep=array();
		}

		/**
		 * Add a file to keep.
		 */
		public function addKeep($name) {
			$this->keep[]=trim($name,"/");
		}

		/**
		 * Should this file be kept?
		 */
		private function isKeep($name) {
			$name=trim($name,"/");

			//echo "should keep $name\n";

			foreach ($this->keep as $k)
				if ($k==$name)
					return TRUE;

			return FALSE;
		}

		/**
		 * Perform sync.
		 */
		public function sync($subdir="") {
			$targetPath=$this->to."/".$subdir;

			if (!file_exists($targetPath)) {
				$res=mkdir($targetPath);
				if (!$res)
					throw new Exception("Unable to create: ".$targetPath);
			}

			if (!is_dir($targetPath))
				throw new Exception("Incomming directory, but there is a file there: ".$targetPath);

			$files = array_diff(scandir($this->from."/".$subdir), array('.','..')); 

			foreach ($files as $file) { 
				if (is_dir($this->from."/".$subdir."/".$file)) {
					$this->sync($subdir."/".$file);
				}

				else {
					$res=copy($this->from."/".$subdir."/".$file,$this->to."/".$subdir."/".$file);
					if (!$res)
						throw new Exception("Unable to copy: ".$this->from."/".$subdir."/".$file);
				}
			} 

			$existingsourcefiles = array_diff(scandir($this->from."/".$subdir), array('.','..')); 
			$existingdestfiles = array_diff(scandir($this->to."/".$subdir), array('.','..')); 

			$removefiles=array_diff($existingdestfiles,$existingsourcefiles);

			//print_r($removefiles);

			foreach ($removefiles as $removefile) { 
				if (!$this->isKeep($subdir."/".$removefile) && !$this->isKeep($subdir))
					ZipDeploy::delTree($this->to."/".$subdir."/".$removefile);
			}
		}
	}

	/**
	 * A deploy target.
	 */
	class ZipDeployTarget {
		private $key;
		private $zipDir;
		private $targetDir;
		private $name;
		private $keeps;

		public function ZipDeployTarget($name) {
			$this->name=$name;
			$this->targetDir=$name;
			$this->zipDir="";
			$this->keeps=array();
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
			return $this;
		}

		/**
		 * Add a file to keep.
		 */
		public function addKeep($keep) {
			$this->keeps[]=$keep;
			return $this;
		}

		/**
		 * Get files to keep for this target.
		 */
		public function getKeeps() {
			return $this->keeps;
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

			if (sizeof($_FILES)) {
				$keys=array_keys($_FILES);
				$key=$keys[0];
				$content=file_get_contents($_FILES[$key]["tmp_name"]);
			}

			else
				$content=file_get_contents($this->inputFileName);

			if (!$content || !strlen($content))
            	throw new Exception("No POST input.");

			$putRes=file_put_contents($this->tmpZipFileName,$content);
			if (!$putRes)
				throw new Exception("unable to input file: ".print_r(error_get_last(),TRUE));

			$zip=new ZipArchive();
			$openRes=$zip->open($this->tmpZipFileName);

			if ($openRes!==TRUE)
				throw new Exception("open failed: ".$openRes);

			if (file_exists($this->tempDir))
				self::delTree($this->tempDir);

			$extractRes=$zip->extractTo($this->tempDir);
			if (!$extractRes)
				throw new Exception("unable to extract ".print_r(error_get_last(),TRUE));

			$zip->close();

			$sync=new DirSync($this->tempDir."/".$target->getZipDir(),$target->getTargetDir());

			foreach ($target->getKeeps() as $keep)
				$sync->addKeep($keep);

			//$sync->addKeep("hello");
			$sync->sync();

			if (file_exists($this->tempDir))
				self::delTree($this->tempDir);

			self::delTree($this->tmpZipFileName);

			echo "OK";
		}

		/**
		 * Remove a directory including all contents.
		 */
		public static function delTree($dir) {
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
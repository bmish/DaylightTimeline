<?php
class CamImage {
	private $filename;
	private $date;
	
	function __construct($filename) {
		$this->filename = $filename;
		
		$this->date = "";
		if (!empty($this->filename)) {
			$this->date = filemtime($this->getPath());
		}
	}
	
	public function getFilename() {
		return $this->filename;
	}
	
	public function getDate() {
		return $this->date;
	}
	
	public function getDisplayDate() {
		global $DISPLAY_DATE_FORMAT;
		
		if (!empty($this->date)) {
			return date($DISPLAY_DATE_FORMAT, $this->date);
		}
		
		return "";
	}
	
	public function getPath() {
		global $SNAPSHOT_DIR_NAME;
		return $SNAPSHOT_DIR_NAME."/".$this->filename;
	}
}
?>
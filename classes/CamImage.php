<?php
class CamImage {
	private $filename;
	private $image;
	private $date;
	private $height;
	private $width;
	private $averagePixelColors;
	
	function __construct($filename) {
		$this->filename = $filename;
		
		$this->image = imagecreatefromjpeg($this->getPath());
		if (!$this->image) {
			return;
		}
		
		$this->height = imagesy($this->image);
		$this->width = imagesx($this->image);
		
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
	
	public function calculateAveragePixelColors() {
		$pixelCount = $this->width * $this->height;
		if ($pixelCount == 0) {
			return;
		}
		
		$pixelSumRed = 0;
		$pixelSumGreen = 0;
		$pixelSumBlue = 0;
		
		for ($x = 0; $x < $this->width; $x++) {
			for ($y = 0; $y < $this->height; $y++) {
				$rgb = imagecolorat($this->image, $x, $y);
				$colors = imagecolorsforindex($this->image, $rgb);
				
				$pixelSumRed += $colors["red"];
				$pixelSumGreen += $colors["green"];
				$pixelSumBlue += $colors["blue"];
			}
			
		}
		
		$this->averagePixelColors = array();
		$this->averagePixelColors["red"] = round($pixelSumRed / $pixelCount);
		$this->averagePixelColors["green"] = round($pixelSumGreen / $pixelCount);
		$this->averagePixelColors["blue"] = round($pixelSumBlue / $pixelCount);
	}
	
	public function getAveragePixelColors() {
		if (!$this->averagePixelColor) {
			$this->calculateAveragePixelColors();
		}
		
		return $this->averagePixelColors;
	}
}
?>
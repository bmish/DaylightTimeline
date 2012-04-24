<?php
class CamImage {
	private $filename;
	private $date;
	private $averagePixelColors;
	private $isProcessed;
	
	private function __construct($filename, $isProcessed = false) {
		$this->filename = $filename;
		$this->isProcessed = $isProcessed;
		
		$this->date = 0;
		$this->averagePixelColors = null;
	}
	
	public static function fromFile($filename) {
		$instance = new self($filename, false);
		
		$instance->date = "";
		if (!empty($instance->filename)) {
			$instance->date = filemtime($instance->getPath());
		}
		
		return $instance;
	}
	
	public static function fromRow($row) {
		$instance = new self($row["filename"], true);
		
		$instance->date = strtotime($row["uploadedAt"]);
		$instance->averagePixelColors = Util::hexToRGB($row["averagePixelColorHex"]);
		
		return $instance;
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
	
	public function getDateTime() {
		return date("Y-m-d H:i:s", $this->getDate());
	}
	
	public function getPath() {
		global $SNAPSHOT_UNPROCESSED_DIR_NAME, $SNAPSHOT_PROCESSED_DIR_NAME;
		
		if ($this->isProcessed) {
			return $SNAPSHOT_PROCESSED_DIR_NAME."/".$this->filename;
		} else {
			return $SNAPSHOT_UNPROCESSED_DIR_NAME."/".$this->filename;
		}
	}

	private function setupImageAndCalculateAveragePixelColors() {
		// Load image.
		$image = imagecreatefromjpeg($this->getPath());
		if (!$image) {
			return;
		}
		
		// Calculate dimensions.
		$height = imagesy($image);
		$width = imagesx($image);
		if (!$height || !$width) {
			return;
		}
		
		// Calculate average pixel colors.
		$this->averagePixelColors = $this->calculateAveragePixelColors($image, $width, $height);
		
		// Free image memory.
		imagedestroy($image);	
	}
	
	private function calculateAveragePixelColors($image, $width, $height) {
		// Resize image to one average pixel using resampling (around 40 times faster than looping through each pixel and nearly as accurate).
		// http://stackoverflow.com/questions/6962814/average-of-rgb-color-of-image
		$tmp_img = ImageCreateTrueColor(1,1);
		ImageCopyResampled($tmp_img,$image,0,0,0,0,1,1,$width,$height); // or ImageCopyResized
		
		// The remaining pixel contains the average color of the image.
		$rgb = ImageColorAt($tmp_img,0,0);
		$colors = imagecolorsforindex($tmp_img, $rgb);
		
		// Free temporary image memory.
		imagedestroy($tmp_img);

		return $colors;
	}
	
	private function addToDB() {
		mysql_query("INSERT INTO camImages (filename, uploadedAt, averagePixelColorHex) VALUES ('".$this->filename."','".$this->getDateTime()."','".$this->getAveragePixelColorHex()."')");
	}
	
	private static function createNecessaryDirectoriesIfNotExist() {
		global $SNAPSHOT_DIR_NAME, $SNAPSHOT_UNPROCESSED_DIR_NAME, $SNAPSHOT_PROCESSED_DIR_NAME;
		
		if (!file_exists($SNAPSHOT_DIR_NAME) ) {
			mkdir($SNAPSHOT_DIR_NAME, 0777);
		}
		if (!file_exists($SNAPSHOT_UNPROCESSED_DIR_NAME) ) {
			mkdir($SNAPSHOT_UNPROCESSED_DIR_NAME, 0777);
		}
		if (!file_exists($SNAPSHOT_PROCESSED_DIR_NAME) ) {
			mkdir($SNAPSHOT_PROCESSED_DIR_NAME, 0777);
		}
	}
	
	public static function processNewCamImages() {
		// Create necessary directories.
		CamImage::createNecessaryDirectoriesIfNotExist();
		
		// Get unprocessed cam images.
		$camImages = CamImage::getUnprocessedCamImages();
		$camImageCount = count($camImages);
		
		// Store image information in database.
		foreach ($camImages as $key => $camImage) {
			$camImage->addToDB();
			
			$camImage->moveToProcessedDirectory();
			
			unset($camImages[$key]); // Don't need this object anymore.
		}
		
		return $camImageCount;
	}
	
	private function moveToProcessedDirectory() {
		$oldPath = $this->getPath();
		$this->isProcessed = true;
		$newPath = $this->getPath();
		
		rename($oldPath, $newPath);
	}
	
	private static function getUnprocessedCamImages() {
		global $SNAPSHOT_UNPROCESSED_DIR_NAME;
		
		if (!is_dir($SNAPSHOT_UNPROCESSED_DIR_NAME)) {
			return array();
		}
		
		// Don't fail on recoverable corrupt jpeg files thrown by imagecreatefromjpeg().
		ini_set("gd.jpeg_ignore_warning", 1);
		
		// Create cam images from each file.
		$camImages = array();
		if ($handle = opendir($SNAPSHOT_UNPROCESSED_DIR_NAME)) {
		    while (false !== ($entry = readdir($handle))) {
				if (Util::isImage($SNAPSHOT_UNPROCESSED_DIR_NAME."/".$entry)) {
					$camImages[] = CamImage::fromFile($entry);
				}
		    }

		    closedir($handle);
		}
		
		return $camImages;
	}
	
	private function getAveragePixelColors() {
		if (!$this->averagePixelColors) {
			$this->setupImageAndCalculateAveragePixelColors();
		}
		
		return $this->averagePixelColors;
	}
	
	public function getAveragePixelColorHex() {
		$averagePixelColors = $this->getAveragePixelColors();
		
		$hex = Util::rgb2hex(array_values($averagePixelColors));
		
		return Util::hexToString($hex);
	}
	
	public static function getCamImages($count, $centerDate, $timeDirection) {
		if ($timeDirection == TimeDirection::Post) {
			$uploadedAtComparator = ">";
			$order = "ASC";
		} else {
			$uploadedAtComparator = "<";
			$order = "DESC";
		}
		
		$query = "SELECT * FROM camImages WHERE uploadedAt ".$uploadedAtComparator." '".date("Y-m-d H:i:s",$centerDate)."' ORDER BY uploadedAt ".$order." LIMIT ".$count;
		$result = mysql_query($query);
		if (!$result || mysql_num_rows($result) == 0) {
			return null;
		}
		
		$camImages = array();
		while ($row = mysql_fetch_array($result)) {
			$camImages[] = CamImage::fromRow($row);
		}
		
		return ($count == 1) ? $camImages[0] : $camImages;
	}
	
	public function jsonSerialize() {
		$arr = array();
		$arr["filename"] = $this->filename;
		$arr["date"] = $this->getDateTime();
		$arr["averagePixelColorHex"] = $this->getAveragePixelColorHex();
		
		return $arr;
	}
	
	public static function getJSONObjectOfCamImages($count, $centerDate, $timeDirection) {
		$camImages = CamImage::getCamImages($count, $centerDate, $timeDirection);
		
		if ($timeDirection == TimeDirection::Now) { // Don't need an array if only getting the current cam image.
			return $camImages->jsonSerialize();
		}
		
		$jsonArray = Util::jsonSerializeArray($camImages);
		
		return $jsonArray;
	}
}
?>
<?php
class CamImage {
	private $filename;
	private $date;
	private $height;
	private $width;
	private $averagePixelColors;
	private $isProcessed;
	
	private function __construct($filename, $isProcessed = false) {
		$this->filename = $filename;
		$this->isProcessed = $isProcessed;
		
		$this->date = 0;
		$this->height = 0;
		$this->width = 0;
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
		$instance->averagePixelColors = CamImage::hexToRGB($row["averagePixelColorHex"]);
		
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

	private function calculateAveragePixelColors() {
		// Load image.
		$image = imagecreatefromjpeg($this->getPath());
		if (!$image) {
			return;
		}
		
		// Calculate dimensions.
		$this->height = imagesy($image);
		$this->width = imagesx($image);
		$pixelCount = $this->width * $this->height;
		if ($pixelCount == 0) {
			return;
		}
		
		// Sum pixel colors.
		$pixelSumRed = 0;
		$pixelSumGreen = 0;
		$pixelSumBlue = 0;
		for ($x = 0; $x < $this->width; $x++) {
			for ($y = 0; $y < $this->height; $y++) {
				$rgb = imagecolorat($image, $x, $y);
				$colors = imagecolorsforindex($image, $rgb);
				
				$pixelSumRed += $colors["red"];
				$pixelSumGreen += $colors["green"];
				$pixelSumBlue += $colors["blue"];
			}
		}
		
		// Free image memory.
		imagedestroy($image);
		
		// Calculate pixel color averages.
		$this->averagePixelColors = array();
		$this->averagePixelColors["red"] = round($pixelSumRed / $pixelCount);
		$this->averagePixelColors["green"] = round($pixelSumGreen / $pixelCount);
		$this->averagePixelColors["blue"] = round($pixelSumBlue / $pixelCount);	
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
		
		// Store image information in database.
		foreach ($camImages as $camImage) {
			$camImage->addToDB();
			
			$camImage->moveToProcessedDirectory();
		}
		
		return count($camImages);
	}
	
	private function addToDB() {
		mysql_query("INSERT INTO camImages (filename, uploadedAt, averagePixelColorHex) VALUES ('".$this->filename."','".$this->getDateTime()."','".$this->getAveragePixelColorHex()."')");
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
				if (CamImage::isImage($SNAPSHOT_UNPROCESSED_DIR_NAME."/".$entry)) {
					$camImages[] = CamImage::fromFile($entry);
				}
		    }

		    closedir($handle);
		}
		
		return $camImages;
	}
	
	private function getAveragePixelColors() {
		if (!$this->averagePixelColors) {
			$this->calculateAveragePixelColors();
		}
		
		return $this->averagePixelColors;
	}
	
	public function getAveragePixelColorHex() {
		$averagePixelColors = $this->getAveragePixelColors();
		
		$hex = CamImage::rgb2hex(array_values($averagePixelColors));
		
		return CamImage::hexToString($hex);
	}
	
	// http://www.php.net/manual/en/function.dechex.php#39755
	private static function rgb2hex($rgb){
	    if(!is_array($rgb) || count($rgb) != 3){
	        echo "Argument must be an array with 3 integer elements";
	        return false;
	    }
	    for($i=0;$i<count($rgb);$i++){
	        if(strlen($hex[$i] = dechex($rgb[$i])) == 1){
	            $hex[$i] = "0".$hex[$i];
	        }
	    }
	    return $hex;
	}
	
	/**
	 * http://www.php.net/manual/en/function.hexdec.php#99478
	 * Convert a hexadecimal color code to its RGB equivalent
	 *
	 * @param string $hexStr (hexadecimal color value)
	 * @param boolean $returnAsString (if set true, returns the value separated by the separator character. Otherwise returns associative array)
	 * @param string $seperator (to separate RGB values. Applicable only if second parameter is true.)
	 * @return array or string (depending on second parameter. Returns False if invalid hex color value)
	 */                                                                                                 
	private function hexToRGB($hexStr, $returnAsString = false, $seperator = ',') {
	    $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
	    $rgbArray = array();
	    if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
	        $colorVal = hexdec($hexStr);
	        $rgbArray[0] = 0xFF & ($colorVal >> 0x10);
	        $rgbArray[1] = 0xFF & ($colorVal >> 0x8);
	        $rgbArray[2] = 0xFF & $colorVal;
	    } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
	        $rgbArray[0] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
	        $rgbArray[1] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
	        $rgbArray[2] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
	    } else {
	        return false; //Invalid hex color code
	    }
	    return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
	}
	
	private static function hexToString($hex) {
		$str = "";
		for($i=0;$i<count($hex);$i++){
			$str .= $hex[$i];
		}
		
		return $str;
	}
	
	private static function isImage($filePath) {
		return getimagesize($filePath) !== false;
	}
	
	public static function getNewestCamImages($count = 1) {
		$result = mysql_query("SELECT * FROM camImages ORDER BY uploadedAt DESC LIMIT ".$count);
		if (!$result || mysql_num_rows($result) == 0) {
			return null;
		}
		
		$camImages = array();
		while ($row = mysql_fetch_array($result)) {
			$camImages[] = CamImage::fromRow($row);
		}
		
		return ($count == 1) ? $camImages[0] : $camImages;
	}
	
	private static function jsonSerializeArray($array) {
		$ret = array();

		for ($i = 0; $i < count($array); $i++) {
			$ret[] = $array[$i]->jsonSerialize();
		}

		return $ret;
	}
	
	public function jsonSerialize() {
		$arr = array();
		$arr["filename"] = $this->filename;
		$arr["date"] = $this->getDateTime();
		$arr["averagePixelColorHex"] = $this->getAveragePixelColorHex();
		
		return $arr;
	}
	
	public static function getJSONObjectOfNewestCamImages($count = 1) {
		$newestCamImages = CamImage::getNewestCamImages($count);
		$jsonArray = CamImage::jsonSerializeArray($newestCamImages);
		
		return $jsonArray;
	}
	
	public static function outputArrayInJSON($json) {
		header('Content-type: application/json');
		echo json_encode($json);
	}
	
	public static function calculateLoadingDuration($timeStart) {
		return round(microtime(true) - $timeStart, 2);
	}
}
?>
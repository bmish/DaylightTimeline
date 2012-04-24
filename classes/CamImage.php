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

	private function setupImageAndCalculateAveragePixelColors($averagingMethod, $averagingFactor) {
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
		$this->averagePixelColors = $this->calculateAveragePixelColors($image, $width, $height, $averagingMethod, $averagingFactor);
		
		// Free image memory.
		imagedestroy($image);	
	}
	
	private function calculateAveragePixelColors($image, $width, $height, $averagingMethod, $averagingFactor) {
		$pixelSums = array();
		$pixelSums["red"] = 0;
		$pixelSums["green"] = 0;
		$pixelSums["blue"] = 0;
		$pixelCount = $width * $height;
		$pixelSumCount = 0;
		
		if ($averagingMethod == 1) {
			$pixelSumCount = floor($width / $averagingFactor) * floor($height / $averagingFactor);
			for ($x = 0; $x < $width; $x+=$averagingFactor) {
				for ($y = 0; $y < $height; $y+=$averagingFactor) {
					$rgb = imagecolorat($image, $x, $y);
					$colors = imagecolorsforindex($image, $rgb);

					$pixelSums["red"] += $colors["red"];
					$pixelSums["green"] += $colors["green"];
					$pixelSums["blue"] += $colors["blue"];
				}
			}
		} elseif($averagingMethod == 2) {
			$pixelSumCount = floor($pixelCount / $averagingFactor);
			for($ind = 0; $ind < $pixelCount; $ind+=$averagingFactor) {
				$x = $ind % $width;
				$y = floor($ind / $width);//echo '('.$x.','.$y.')';

				$rgb = imagecolorat($image, $x, $y);
				$colors = imagecolorsforindex($image, $rgb);

				$pixelSums["red"] += $colors["red"];
				$pixelSums["green"] += $colors["green"];
				$pixelSums["blue"] += $colors["blue"];
			} 
		} elseif ($averagingMethod == 3) {
			$pixelSumCount = $averagingFactor;
			for($i = 0; $i < $averagingFactor; $i++) {
				$x = rand(0, $width);
				$y = rand(0, $height);
				
				$rgb = imagecolorat($image, $x, $y);
				$colors = imagecolorsforindex($image, $rgb);

				$pixelSums["red"] += $colors["red"];
				$pixelSums["green"] += $colors["green"];
				$pixelSums["blue"] += $colors["blue"];
			}
		} elseif ($averagingMethod == 4) {
			$tmp_img = ImageCreateTrueColor(1,1);
			ImageCopyResampled($tmp_img,$image,0,0,0,0,1,1,$width,$height); // or ImageCopyResized
			$rgb = ImageColorAt($tmp_img,0,0);
			$colors = imagecolorsforindex($tmp_img, $rgb);
			
			$pixelSumCount = 1;
			$pixelSums["red"] += $colors["red"];
			$pixelSums["green"] += $colors["green"];
			$pixelSums["blue"] += $colors["blue"];
		}
		
		// Calculate pixel color averages.
		$averagePixelColors = array();
		$averagePixelColors["red"] = round($pixelSums["red"] / $pixelSumCount);
		$averagePixelColors["green"] = round($pixelSums["green"] / $pixelSumCount);
		$averagePixelColors["blue"] = round($pixelSums["blue"] / $pixelSumCount);
		
		return $averagePixelColors;
	}
	
	private function runAveragingTest($averagingMethod, $averagingFactor) {
		$timeStart = microtime(true);
		
		$this->setupImageAndCalculateAveragePixelColors($averagingMethod, $averagingFactor);
		$averagePixelColorHex = $this->getAveragePixelColorHex();
		
		echo '<p><font color="#'.$averagePixelColorHex.'">#'.$averagePixelColorHex.': '.CamImage::calculateLoadingDuration($timeStart, 4).' seconds using ('.$averagingMethod.', '.$averagingFactor.')</font></p>';
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
	
	public static function runAveragingTests() {
		$camImages = CamImage::getUnprocessedCamImages();
		if (count($camImages) == 0) {
			echo 'Need at least one unprocessed image to test with.';
			return;
		}
		$camImage = $camImages[0];
		
		$camImage->runAveragingTest(1, 1);
		$camImage->runAveragingTest(2, 1);
		$camImage->runAveragingTest(3, 20000);
		$camImage->runAveragingTest(4, -1);
		
		$camImage->runAveragingTest(1, 10);
		$camImage->runAveragingTest(2, 10);
		$camImage->runAveragingTest(3, 10000);
		
		$camImage->runAveragingTest(1, 20);
		$camImage->runAveragingTest(2, 20);
		$camImage->runAveragingTest(3, 8000);
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
				if (CamImage::isImage($SNAPSHOT_UNPROCESSED_DIR_NAME."/".$entry)) {
					$camImages[] = CamImage::fromFile($entry);
				}
		    }

		    closedir($handle);
		}
		
		return $camImages;
	}
	
	private function getAveragePixelColors() {
		global $AVERAGING_METHOD, $AVERAGING_FACTOR;
		
		if (!$this->averagePixelColors) {
			$this->setupImageAndCalculateAveragePixelColors($AVERAGING_METHOD, $AVERAGING_FACTOR);
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
	
	public static function getJSONObjectOfCamImages($count, $centerDate, $timeDirection) {
		$camImages = CamImage::getCamImages($count, $centerDate, $timeDirection);
		
		if ($timeDirection == TimeDirection::Now) { // Don't need an array if only getting the current cam image.
			return $camImages->jsonSerialize();
		}
		
		$jsonArray = CamImage::jsonSerializeArray($camImages);
		
		return $jsonArray;
	}
	
	public static function outputArrayInJSON($json) {
		header('Content-type: application/json');
		echo json_encode($json);
	}
	
	public static function calculateLoadingDuration($timeStart, $decimals = 2) {
		return round(microtime(true) - $timeStart, $decimals);
	}
}
?>
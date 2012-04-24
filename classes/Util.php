<?php
class Util {
	// http://www.php.net/manual/en/function.dechex.php#39755
	public static function rgb2hex($rgb){
	    if(!is_array($rgb) || count($rgb) < 3){
	        echo "rgb2hex(): Argument must be an array with 3 or 4 integer elements.";
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
	public static function hexToRGB($hexStr, $returnAsString = false, $seperator = ',') {
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
	
	public static function hexToString($hex) {
		$str = "";
		for($i=0;$i<count($hex);$i++){
			$str .= $hex[$i];
		}
		
		return $str;
	}
	
	public static function isImage($filePath) {
		return getimagesize($filePath) !== false;
	}
	
	public static function jsonSerializeArray($array) {
		$ret = array();

		for ($i = 0; $i < count($array); $i++) {
			$ret[] = $array[$i]->jsonSerialize();
		}

		return $ret;
	}
	
	public static function outputArrayInJSON($json) {
		header('Content-type: application/json');
		echo json_encode($json);
	}
	
	public static function calculateLoadingDuration($timeStart, $decimals = 2) {
		return round(microtime(true) - $timeStart, $decimals);
	}
	
	public static function dateToDate($date) {
		return date("Y-m-d", $date);
	}
	
	public static function dateToDateTime($date) {
		return date("Y-m-d H:i:s", $date);
	}
	
	public static function stringToDateTime($dateString) {
		return Util::dateToDateTime(strtotime($dateString));
	}
	
	public static function stringToDate($dateString) {
		return Util::dateToDate(strtotime($dateString));
	}
}
?>
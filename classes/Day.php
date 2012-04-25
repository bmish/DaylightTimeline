<?php
class Day {
	private $date;
	private $sunriseTime;
	private $sunsetTime;
	private $averageDaylightPixelColorHex;
	
	private function __construct($date) {
		$this->date = $date;
		$this->sunriseTime = null;
		$this->sunsetTime = null;
		$this->averageDaylightPixelColorHex = null;
	}
	
	private static function fromSunMovements($date, $sunriseDateTime, $sunsetDateTime) {
		$instance = new self($date);
		
		$instance->sunriseTime = $sunriseDateTime;
		$instance->sunsetTime = $sunsetDateTime;
		$instance->averageDaylightPixelColorHex = $this->calculateAverageDaylightPixelColorHex();
		
		$instance->saveToDB();
		
		return $instance;
	}
	
	private static function fromRow($row) {
		$instance = new self($row["date"]);
		
		$instance->sunriseTime = $row["sunriseTime"];
		$instance->sunsetTime = $row["sunsetTime"];
		$instance->averageDaylightPixelColorHex = $row["averageDaylightPixelColorHex"];
		
		return $instance;
	}
	
	private function foundCamImages() {
		return $this->averageDaylightPixelColorHex != null;
	}
	
	public static function processAll($year, $month) {
		// Retrieve the sunrise and sunset for each day.
		$sunMovements = Day::getSunMovements($year, $month);

		// Calculate the average color of each day and store this data in the database.
		$processedCount = 0;
		foreach ($sunMovements as $date => $sunMovement) {
			$day = Day::fromSunMovements($date, $sunMovement["sunriseDateTime"], $sunMovement["sunsetDateTime"]);
			
			if ($day->foundCamImages()) {
				$processedCount++;
			}
			
			unset($day);
		}
		
		return $processedCount;
	}
	
	private function saveToDB() {
		if (!$this->averageDaylightPixelColorHex) { // Don't record a day if we couldn't compute the average color for it.
			return false;
		}
		
		mysql_query("INSERT INTO days (date, sunriseTime, sunsetTime, averageDaylightPixelColorHex) VALUES ('".$this->date."','".$this->sunriseTime."','".$this->sunsetTime."', '".$this->averageDaylightPixelColorHex."')");
		
		return true;
	}
	
	private static function getSunMovements($year, $month) {
		$sunMovements = array();
		
		$url = "http://www.sunrisesunset.com/calendar.asp?comb_city_info=Champaign%2C%20Illinois;88.265;40.113;-6;1&month=".$month."&year=".$year;
		$calendarPage = file_get_contents($url);
		
		$pattern = '/<font size=3 face=\"Times\">([0-9]{1,2})<\/font><br><font size=1 face=\"Arial, Helvetica\"><br>Sunrise: ([0-9amp:]+)<br>Sunset: ([0-9amp:]+)<br><\/td>/';
		preg_match_all($pattern, $calendarPage, $matches);
		
		for ($i = 0; $i < count($matches[0]); $i++) {
			$dayNumber = $matches[1][$i];
			$sunriseTime = $matches[2][$i];
			$sunsetTime = $matches[3][$i];
			
			$date = Util::stringToDate($year.'-'.$month.'-'.$dayNumber);
			
			$sunriseDateString = $date.' '.$sunriseTime;
			$sunsetDateString = $date.' '.$sunsetTime;

			$sunriseDateTime = Util::stringToDateTime($sunriseDateString);
			$sunsetDateTime = Util::stringToDateTime($sunsetDateString);
			
			$sunMovements[$date] = array();
			$sunMovements[$date]["sunriseDateTime"] = $sunriseDateTime;
			$sunMovements[$date]["sunsetDateTime"] = $sunsetDateTime;
		}
		
		return $sunMovements;
	}
	
	private static function calculateAverageDaylightPixelColorHex($sunriseDateTime, $sunsetDateTime) {
		$query = "SELECT averagePixelColorHex FROM camImages WHERE uploadedAt > '$sunriseDateTime' AND uploadedAt < '$sunsetDateTime'";
		$result = mysql_query($query);
		if (!$result || ($dayCount = mysql_num_rows($result)) == 0) {
			return null;
		}
		
		$pixelSums = array();
		$pixelSums["red"] = 0;
		$pixelSums["green"] = 0;
		$pixelSums["blue"] = 0;
		while ($row = mysql_fetch_array($result)) {
			$rgb = Util::hexToRGB($row["averagePixelColorHex"]);
			
			$pixelSums["red"] += $rgb[0];
			$pixelSums["green"] += $rgb[1];
			$pixelSums["blue"] += $rgb[2];
		}
		
		$averagePixelColors["red"] = round($pixelSums["red"] / $dayCount);
		$averagePixelColors["green"] = round($pixelSums["green"] / $dayCount);
		$averagePixelColors["blue"] = round($pixelSums["blue"] / $dayCount);

		return Util::hexToString(Util::rgb2Hex(array_values($averagePixelColors)));
	}
	
	private static function getDays() {
		$query = "SELECT * FROM days ORDER BY date";
		$result = mysql_query($query);
		if (!$result || mysql_num_rows($result) == 0) {
			return array();
		}
		
		$days = array();
		while ($row = mysql_fetch_array($result)) {
			$day = Day::fromRow($row);
			
			$days[] = $day;
		}
		
		return $days;
	}
	
	public function jsonSerialize() {
		$arr = array();
		$arr["date"] = $this->date;
		$arr["sunriseTime"] = $this->sunriseTime;
		$arr["sunsetTime"] = $this->sunsetTime;
		$arr["averageDaylightPixelColorHex"] = $this->averageDaylightPixelColorHex;
		
		return $arr;
	}
	
	public static function getJSONObjectOfDays() {
		$days = Day::getDays();

		$jsonArray = Util::jsonSerializeArray($days);
		
		return $jsonArray;
	}
}
?>
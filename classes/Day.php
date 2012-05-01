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
		$instance->averageDaylightPixelColorHex = $instance->calculateAverageDaylightPixelColorHex();
		
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
	
	private static function fromDate($date) {
		$query = "SELECT * FROM days WHERE date = '".date("Y-m-d", $date)."' LIMIT 1";
		$result = mysql_query($query);
		if (!$result || mysql_num_rows($result) == 0) {
			return null;
		}
		
		$row = mysql_fetch_array($result);
		return Day::fromRow($row);
	}
	
	private function foundCamImages() {
		return $this->averageDaylightPixelColorHex != "";
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
		mysql_query("INSERT INTO days (date, sunriseTime, sunsetTime, averageDaylightPixelColorHex) VALUES ('".$this->date."','".$this->sunriseTime."','".$this->sunsetTime."', '".$this->averageDaylightPixelColorHex."')");
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
	
	private function calculateAverageDaylightPixelColorHex() {
		$query = "SELECT averagePixelColorHex FROM camImages WHERE uploadedAt > '".$this->sunriseDateTime."' AND uploadedAt < '".$this->sunsetDateTime."";
		$result = mysql_query($query);
		if (!$result || ($dayCount = mysql_num_rows($result)) == 0) {
			return "";
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
		
		$seenDayWithCamImages = false; // Ignore days until we get to first day with cam images.
		$days = array();
		while ($row = mysql_fetch_array($result)) {
			$day = Day::fromRow($row);
			
			if ($seenDayWithCamImages || $day->foundCamImages()) {
				$days[] = $day;
				$seenDayWithCamImages = true;
			}
		}
		
		// Remove days after the last day with cam images.
		while (!$days[count($days) - 1]->foundCamImages()) {
			unset($days[count($days) - 1]);
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
	
	public static function getJSONObjectOfDay($date) {
		if (!$date) {
			$date = CamImage::getLastCamImageDate();
		}
		
		$day = Day::fromDate($date);
		if (!$day) {
			return null;
		}
		
		return $day->jsonSerialize();
	}
}
?>
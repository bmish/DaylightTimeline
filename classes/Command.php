<?php
class Command {
	public static function processImages() {
		global $MEMORY_LIMIT_FOR_PROCESSING;
		
		// Start timing.
		$timeStart = microtime(true);

		// Remove script execution time limit and increase memory limit.
		set_time_limit(0);
		ini_set('memory_limit', $MEMORY_LIMIT_FOR_PROCESSING);

		// Process new cam images.
		$processedCount = CamImage::processNewCamImages();

		// Build JSON object.
		$obj = array();
		$obj["processedCount"] = $processedCount;
		$obj["duration"] = Util::calculateLoadingDuration($timeStart);

		// Output JSON.
		Util::outputArrayInJSON($obj);

		exit;
	}
	
	public static function processDays() {
		// Start timing.
		$timeStart = microtime(true);

		// Choose what year and month to process.
		$dateToUse = time();
		if (!empty($_GET["date"])) {
			$dateToUse = strtotime($_GET["date"]);
		}
		$year = date("Y", $dateToUse);
		$month = date("n", $dateToUse);

		// Process days.
		$processedCount = Day::processAll($year, $month);

		// Build JSON object.
		$obj = array();
		$obj["processedCount"] = $processedCount;
		$obj["duration"] = Util::calculateLoadingDuration($timeStart);

		// Output JSON.
		Util::outputArrayInJSON($obj);

		exit;
	}
	
	public static function jsonDay($date) {
		// Start timing.
		$timeStart = microtime(true);

		// Build JSON object.
		$obj = array();
		$obj["day"] = Day::getJSONObjectOfDay(strtotime($_GET["date"]));
		$obj["camImages"] = CamImage::getJSONObjectOfCamImages(strtotime($_GET["date"]));
		$obj["duration"] = Util::calculateLoadingDuration($timeStart);

		// Output JSON.
		Util::outputArrayInJSON($obj);

		exit;
	}
	
	public static function jsonDays() {
		// Start timing.
		$timeStart = microtime(true);

		// Build JSON object.
		$obj = array();
		$obj["days"] = Day::getJSONObjectOfDays();
		$obj["duration"] = Util::calculateLoadingDuration($timeStart);

		// Output JSON.
		Util::outputArrayInJSON($obj);

		exit;
	}
}
?>
<?php
require_once("config/config.php");
require_once("classes/CamImage.php");
require_once("classes/DB.php");
require_once("classes/enums.php");

// Connect to database.
DB::connect();

// Center date?
$centerDate = strtotime($_GET["center"]);
if (!$centerDate) {
	$centerDate = time();
}

// Run scripts?
if ($_GET["process"] == "true") {
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
	$obj["duration"] = CamImage::calculateLoadingDuration($timeStart);
		
	// Output JSON.
	CamImage::outputArrayInJSON($obj);
	
	exit;
} elseif ($_GET["json"] == "true") {
	// Start timing.
	$timeStart = microtime(true);
	
	// Build JSON object.
	$obj = array();
	$obj["centerCamImage"] = CamImage::getJSONObjectOfCamImages(1, $centerDate, TimeDirection::Now);
	$obj["pastCamImages"] = CamImage::getJSONObjectOfCamImages($IMAGES_PER_CANVAS, $centerDate, TimeDirection::Past);
	$obj["postCamImages"] = CamImage::getJSONObjectOfCamImages($IMAGES_PER_CANVAS, $centerDate, TimeDirection::Post);
	$obj["duration"] = CamImage::calculateLoadingDuration($timeStart);
		
	// Output JSON.
	CamImage::outputArrayInJSON($obj);
	
	exit;
} elseif ($_GET["averagingTest"] == "true") {
	CamImage::runAveragingTests();
	
	exit;
}

// Get newest cam image to display.
$newestCamImage = CamImage::getCamImages(1, $centerDate, TimeDirection::Now);
if ($newestCamImage == null) {
	echo 'No cam images found in database.';
	exit;
}

// Close database connection.
DB::close();
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo $DISPLAY_CAM_TITLE; ?></title>
<link href="css/style.css" rel="stylesheet" type="text/css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="js/fns.js"></script>
</head>
<body>
<div id="wrapper">
	<div id="header">
		<div id="pageTitle"><?php echo date("F j, Y", $newestCamImage->getDate()); ?></div>
		<div id="pageSubtitle"><?php echo $DISPLAY_CAM_LOCATION_NAME; ?></div>
	</div>
	<div id="daylightRow">
		<canvas id="pastDaylight" width="400" height="480">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
		<div id="camImageDiv">
			<div id="camImageHeader" class="camImageHeaderCorner"><?php echo date("g:i a", $newestCamImage->getDate()); ?></div>
			<img id="camImage" onmousedown="return false" src="<?php echo $newestCamImage->getPath(); ?>" alt="Latest cam image" title="Latest cam image" />
		</div>
		<canvas id="postDaylight" width="400" height="480">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
	</div>
	<div id="sliderDiv"><input type="range" id="slider" min="0" max="100" value="100" onchange="rangeUpdated(this.value)" /></div>
</div>
<script>init()</script>
</body>
</html>

<?php
require_once("config/config.php");
require_once("classes/CamImage.php");
require_once("classes/DB.php");

// Connect to database.
DB::connect();

// Center date?
$centerDate = strtotime($_GET["date"]);
if (!$centerDate) {
	$centerDate = time();
}

// Run scripts?
if ($_GET["process"] == "true") {
	// Start timing.
	$timeStart = microtime(true);
	
	// Remove script execution time limit.
	set_time_limit(0);
	
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
		<div id="pageSubtitle"><a target="_blank" href="http://maps.google.com/?q=<?php echo urlencode($DISPLAY_CAM_LOCATION_ADDR); ?>"><?php echo $DISPLAY_CAM_LOCATION_NAME; ?></a></div>
	</div>
	<div id="daylightRow">
		<canvas id="pastDaylight" width="400" height="480">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
		<div id="camImageDiv">
			<div id="camImageHeader" class="camImageHeaderCorner"><?php echo date("g:i a", $newestCamImage->getDate()); ?></div>
			<img id="camImage" onmousedown="return false" src="<?php echo $newestCamImage->getPath(); ?>" alt="Latest cam image" title="Latest cam image" />
		</div>
		<canvas id="postDaylight" width="400" height="480">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
	</div>
</div>
<script>init()</script>
</body>
</html>

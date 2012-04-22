<?php
require_once("config/config.php");
require_once("classes/CamImage.php");
require_once("classes/DB.php");

// Connect to database.
DB::connect();

// Run scripts?
if ($_GET["process"] == "true") {
	// Start timing.
	$timeStart = microtime(true);
	
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
	$obj["pastCamImages"] = CamImage::getJSONObjectOfNewestCamImages($IMAGES_PER_CANVAS);
	$obj["duration"] = CamImage::calculateLoadingDuration($timeStart);
		
	// Output JSON.
	CamImage::outputArrayInJSON($obj);
	
	exit;
}

// Get newest cam image to display.
$newestCamImage = CamImage::getNewestCamImages(1);
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
<script src="js/jquery-1.7.1.js"></script>
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
		<img id="camImage" src="<?php echo $newestCamImage->getPath(); ?>" alt="Latest cam image" title="Latest cam image" />
		<canvas id="postDaylight" width="400" height="480">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
	</div>
	<div id="pageDescription">
		<span class="fieldHeader">Time taken:</span> <?php echo $newestCamImage->getDisplayDate(); ?><br />
		<span class="fieldHeader">Update interval:</span> <?php echo $DISPLAY_CAM_UPDATE_INTERVAL; ?> (requires manual refresh)<br />
		<span class="fieldHeader">Floor/Facing:</span> <?php echo $DISPLAY_CAM_BUILDING_FLOOR; ?>/<?php echo $DISPLAY_CAM_FACING_DIRECTION; ?><br />
	</div>
</div>
</body>
</html>

<?php
require_once("config/config.php");
require_once("classes/CamImage.php");
require_once("classes/Command.php");
require_once("classes/Day.php");
require_once("classes/DB.php");
require_once("classes/enums.php");
require_once("classes/Util.php");

// Connect to database.
DB::connect();

// Center date?
$centerDate = strtotime($_GET["center"]);
if (!$centerDate) {
	$centerDate = time();
}

// Run scripts?
if ($_GET["process"] == "true") {
	Command::process();
} elseif ($_GET["json"] == "true") {
	Command::json($centerDate);
} elseif ($_GET["processDays"] == "true") {
	Command::processDays();
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
<script src="js/jquery-dateFormat/jquery.dateFormat-1.0.js"></script>
</head>
<body>
<div id="wrapper">
	<div id="header">
		<div id="pageTitle"><?php echo date("F j, Y", $newestCamImage->getDate()); ?></div>
		<div id="pageSubtitle"><?php echo $DISPLAY_CAM_LOCATION_NAME; ?></div>
	</div>
	<div id="daylightRow">
		<canvas id="pastDaylight" width="<?php echo $CANVAS_DAYLIGHT_WIDTH; ?>" height="<?php echo $CAM_IMAGE_HEIGHT; ?>">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
		<div id="camImageDiv">
			<div id="camImageHeader" class="camImageHeaderCorner"><?php echo date("g:i a", $newestCamImage->getDate()); ?></div>
			<img id="camImage" onmousedown="return false" src="<?php echo $newestCamImage->getPath(); ?>" alt="Latest cam image" title="Latest cam image" width="<?php echo $CAM_IMAGE_WIDTH; ?>" height="<?php echo $CAM_IMAGE_HEIGHT; ?>" />
		</div>
		<canvas id="postDaylight" width="<?php echo $CANVAS_DAYLIGHT_WIDTH; ?>" height="<?php echo $CAM_IMAGE_HEIGHT; ?>">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
	</div>
	<div id="historyRow">
		<canvas id="canvasHistory" width="<?php echo $CANVAS_HISTORY_WIDTH; ?>" height="<?php echo $CANVAS_HISTORY_HEIGHT; ?>">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
	</div>
	<div id="sliderDiv"><input type="range" id="slider" min="<?php echo $SLIDER_MIN; ?>" max="<?php echo $SLIDER_MAX; ?>" value="<?php echo $SLIDER_VALUE; ?>" step="<?php echo $SLIDER_STEP; ?>" onchange="rangeUpdated(this.value)" /></div>
</div>
<script>init()</script>
</body>
</html>

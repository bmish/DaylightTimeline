<?php
require_once("config/config.php");
require_once("classes/CamImage.php");
require_once("classes/Command.php");
require_once("classes/Day.php");
require_once("classes/DB.php");
require_once("classes/Util.php");

// Connect to database.
DB::connect();

// Run scripts?
if ($_GET["processImages"] == "true") {
	Command::processImages();
} elseif ($_GET["processDays"] == "true") {
	Command::processDays();
} elseif ($_GET["jsonDay"] == "true") {
	Command::jsonDay($date);
} elseif ($_GET["jsonDays"] == "true") {
	Command::jsonDays();
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
<script src="js/Cache.js"></script>
<script src="js/Date.js"></script>
<script src="js/Util.js"></script>
<script src="js/Visualization.js"></script>
<script src="js/jquery-dateFormat/jquery.dateFormat-1.0.js"></script>
</head>
<body>
<div id="wrapper">
	<div id="labelRow">
		<canvas id="canvasLabel" width="<?php echo $CANVAS_DAYLIGHT_WIDTH; ?>" height="<?php echo $CANVAS_LABEL_HEIGHT; ?>">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
	</div>
	<div id="daylightRow">
		<div id="canvasAndHeaderContainer">
			<div id="header">
				<div id="pageTitle"></div>
				<div id="pageSubtitle"><?php echo $DISPLAY_CAM_LOCATION_NAME; ?></div>
			</div>
			<canvas id="canvasDaylight" width="<?php echo $CANVAS_DAYLIGHT_WIDTH; ?>" height="<?php echo $CAM_IMAGE_HEIGHT; ?>">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
		</div>
	</div>
	<div id="historyRow">
		<input type="range" id="slider" min="<?php echo $SLIDER_MIN; ?>" max="<?php echo $SLIDER_MAX; ?>" value="<?php echo $SLIDER_VALUE; ?>" step="<?php echo $SLIDER_STEP; ?>" onchange="rangeUpdated(this.value)" />
		<canvas id="canvasHistory" width="<?php echo $CANVAS_HISTORY_WIDTH; ?>" height="<?php echo $CANVAS_HISTORY_HEIGHT; ?>">This text is displayed if your browser does not support HTML5 Canvas.</canvas>
	</div>
	<div id="camImageHoverBox">
		<div id="camImageHeader"></div>
		<img id="camImage" />
	</div>
</div>
</body>
</html>

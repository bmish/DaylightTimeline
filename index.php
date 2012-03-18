<?php
require_once("fns/config.php");
require_once("fns/fns.php");
require_once("fns/CamImage.php");

$camImages = getCamImages();
$newestCamImage = getNewestCamImageInArray($camImages);
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
		<div id="pageTitle"><?php echo $DISPLAY_CAM_TITLE; ?></div>
	</div>
	<div id="page">
		<div id="body">
			<img src="<?php echo $newestCamImage->getPath(); ?>" alt="Latest cam image" title="Latest cam image" />
			<div id="pageDescription">
				<span class="fieldHeader">Time taken:</span> <?php echo $newestCamImage->getDisplayDate(); ?><br />
				<span class="fieldHeader">Update interval:</span> <?php echo $DISPLAY_CAM_UPDATE_INTERVAL; ?> (requires manual refresh)<br />
				<span class="fieldHeader">Facing:</span> <?php echo $DISPLAY_CAM_FACING_DIRECTION; ?><br />
				<span class="fieldHeader">Floor:</span> <?php echo $DISPLAY_CAM_BUILDING_FLOOR; ?><br />
				<span class="fieldHeader">Location:</span> <a target="_blank" href="http://maps.google.com/?q=<?php echo urlencode($DISPLAY_CAM_LOCATION_ADDR); ?>"><?php echo $DISPLAY_CAM_LOCATION_NAME; ?></a></div>
		</div>
	</div>
</div>
</body>
</html>

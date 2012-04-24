<?php
// Timezone:
date_default_timezone_set('America/Chicago');

// Database:
$dbHost = '127.0.0.1';
$dbUser = '';
$dbPass = '';
$dbName = 'DaylightTimeline';

// Cam details:
$DISPLAY_CAM_TITLE = "My Cam";
$DISPLAY_CAM_LOCATION_NAME = "Champaign";
$DISPLAY_CAM_LOCATION_ADDR = "Champaign, IL";
$DISPLAY_CAM_UPDATE_INTERVAL = "30 seconds";
$DISPLAY_CAM_FACING_DIRECTION = "North";
$DISPLAY_CAM_BUILDING_FLOOR = "1";

// Cam image size:
$CAM_IMAGE_WIDTH = 640;
$CAM_IMAGE_HEIGHT = 480;

// Canvas sizes:
$CANVAS_DAYLIGHT_WIDTH = 400;
$CANVAS_HISTORY_WIDTH = $CANVAS_DAYLIGHT_WIDTH * 2 + $CAM_IMAGE_WIDTH;
$CANVAS_HISTORY_HEIGHT = 100;

// HTML slider values:
$SLIDER_MIN = 0;
$SLIDER_MAX = 100;
$SLIDER_VALUE = 100;
$SLIDER_STEP = 1;

// Algorithm details:
$IMAGES_PER_CANVAS = $CANVAS_DAYLIGHT_WIDTH;
$MEMORY_LIMIT_FOR_PROCESSING = '512M';

// Directory paths:
$SNAPSHOT_DIR_NAME = "snapshots";
$SNAPSHOT_UNPROCESSED_DIR_NAME = "snapshots/unprocessed";
$SNAPSHOT_PROCESSED_DIR_NAME = "snapshots/processed";
?>
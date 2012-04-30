// Config variables:
var SECONDS_PER_IMAGE_INDEX = 60;
var SKIP_IMAGE_AMOUNT = 2;
var SNAPSHOT_PROCESSED_DIR_NAME = "snapshots/processed/";

// Global variables:
var jsonDay;
var jsonDays;

var camImageElement;
var pageTitleElement;
var canvasDaylightElement;
var canvasHistoryElement;
var camImageElement;

var dayCanvasMap;

var MS_PER_SECOND = 1000;
var SECONDS_PER_DAY = 60 * 60 * 24;
var MS_PER_DAY = SECONDS_PER_DAY * MS_PER_SECOND;

var camImageHoverBoxOffsetX = 10;
var camImageHoverBoxOffsetY = 20;

function receivedJSONDay(data) {
	jsonDay = data;
	
	updatePageTitle();
	
	dayCanvasMap = mapCanvasToDayUsingTimeBuckets();
	drawDaylight(jsonDay.camImages);
	setupDaylightCanvasHoverEvent();
}

function receivedJSONDays(data) {
	jsonDays = data;
	
	updateSliderRange();
	drawHistory(jsonDays.days);
}

// http://cssglobe.com/post/1695/easiest-tooltip-and-image-preview-using-jquery
function setupDaylightCanvasHoverEvent() {
	$("#canvasDaylight").hover(function (e) { $("#camImageHoverBox").show(); }, function () { $("#camImageHoverBox").hide(); });
	$("#canvasDaylight").mousemove(function (e) {
		$("#camImageHoverBox")
			.css("top",(e.pageY - camImageHoverBoxOffsetX) + "px")
			.css("left",(e.pageX + camImageHoverBoxOffsetY) + "px");
		
		var mapIndex = getMapIndexFromMouseX(e.pageX);	
		updateCamImage(mapIndex);
	});
}

function getMapIndexFromMouseX(mouseX) {
	return mouseX - getCanvasDaylightElement().offsetLeft;
}

function updateCamImage(mapIndex) {
	if (!dayCanvasMap[mapIndex]) {
		$("#camImageHoverBox").hide();
		return;
	} else {
		$("#camImageHoverBox").show();
	}
	
	var im = new Image();
	im.src = SNAPSHOT_PROCESSED_DIR_NAME + dayCanvasMap[mapIndex].filename;
	getCamImageElement().src = im.src;
}

function updateSliderRange() {
	document.getElementById("slider").max = jsonDays.days.length - 1;
}

function updatePageTitle() {
	if (jsonDay.camImages.length == 0) {
		getPageTitleElement().innerText = "No data for this day";
		return;
	} 
	
	getPageTitleElement().innerText = $.format.date(jsonDay.camImages[0].date, "MMMM d, yyyy");
}

// Stretch all the images to fill the canvas completely.
function mapCanvasToDayUsingStretchFill() {
	var imageCount = jsonDay.camImages.length;
	var mapSize = getCanvasDaylightElement().width;
	if (imageCount == 0 || mapSize == 0) {
		return new Array();
	}

	var map = new Array(mapSize);
	var mapIndex = 0;
	
	for (var imageIndex = 0; imageIndex < imageCount; imageIndex+=SKIP_IMAGE_AMOUNT) {
		map[mapIndex++] = jsonDay.camImages[Math.floor(imageIndex)];
	}
	
	return map;
}

// Plot the images in the correct canvas location based on time.
function mapCanvasToDayUsingTimeBuckets() {
	var imageCount = jsonDay.camImages.length;
	var mapSize = getCanvasDaylightElement().width;
	if (imageCount == 0 || mapSize == 0) {
		return new Array();
	}
	
	var map = new Array(mapSize);
	var mapIndex = 0;
	var imageIndex = 0;
	var attemptCount = 1;
	var errorTolerance = 0;
	var prevDate = getDayDate(null);
	
	// Go through the images and store them in the correct places in the map.
	while (imageIndex < imageCount) {
		var rangeMinSeconds = getDaySeconds(prevDate);
		var rangeMaxSeconds = rangeMinSeconds + SECONDS_PER_IMAGE_INDEX * attemptCount + 1 + errorTolerance;
		var camImage = jsonDay.camImages[Math.floor(imageIndex)];
		var date = getDateFromString(camImage.date);
		
		// Are we at the correct position in the map to store the image?
		if (dateBetweenSecondsOfDay(date, rangeMinSeconds, rangeMaxSeconds)) { // Found correct time bucket for image.
			map[mapIndex++] = camImage;
			
			// Move to the next time bucket.
			prevDate = date;
			imageIndex += SKIP_IMAGE_AMOUNT;
			attemptCount = 1; 
		} else { // Image not in time bucket so expand the current time bucket.
			map[mapIndex++] = null;
			
			attemptCount++;
		}	
	}
	
	// Fill in tiny gaps using the previous image for approximation.
	for (var mapIndex = 1; mapIndex < mapSize - 1; mapIndex++) { // Ignore first and last space.
		if (!map[mapIndex] && map[mapIndex - 1] && map[mapIndex + 1]) {
			map[mapIndex] = map[mapIndex - 1];
		}
	}
	
	return map;
}

function drawDaylight(camImages) {
	// Get canvas.
	var c = getCanvasDaylightElement();
	var ctx = c.getContext("2d");
	
	// Decide how much width to give each image color.
	var rectWidth = 1;
	
	// Draw rectangle for each image color.
	ctx.clearRect(0, 0, c.width, c.height); // Clear canvas.
	for (var i = 0; i < dayCanvasMap.length; i++) {
		if (dayCanvasMap[i]) {
			ctx.fillStyle = "#" + dayCanvasMap[i].averagePixelColorHex;
			ctx.fillRect(i*rectWidth,0,rectWidth,c.height);
		}
	}
}

function drawHistory(days) {
	if (days.length == 0) {
		return;
	}
	
	// Get canvas.
	var c = getCanvasHistoryElement();
	var ctx = c.getContext("2d");
	
	// Decide how much width to give each day color.
	var rectWidth = Math.ceil(c.width / days.length);
	
	// Draw rectangle for each day color.
	for (var i = 0; i < days.length; i++) {
		if (days[i].averageDaylightPixelColorHex) {
			ctx.fillStyle = "#" + days[i].averageDaylightPixelColorHex;
			ctx.fillRect(i*rectWidth,0,rectWidth,c.height);
		}
	}
}

function daysSinceLastCamImage() {
	var dateStartString = jsonDays.days[jsonDays.days.length - 1].date;
	var dateStart = new Date(Date.parse(dateStartString));
	var today = getDayDate(null);
	
	return daysBetween(dateStart, today);
}

function rangeUpdated(newValue) {
	var daysAgo = daysSinceLastCamImage() + jsonDays.days.length - newValue - 1; 
	
	jQuery.getJSON("index.php?jsonDay=true&date=" + daysAgo + " days ago", receivedJSONDay);
}

function init() {
	jsonDay = null;
	jsonDays = null;
	
	camImageElement = null;
	pageTitleElement = null;
	canvasDaylightElement = null;
	canvasHistoryElement = null;
	
	var date = getParameterByName("date");
	
	jQuery.getJSON("index.php?jsonDays=true", receivedJSONDays);
	jQuery.getJSON("index.php?jsonDay=true&date=" + date, receivedJSONDay);
}

$(document).ready(function(){
	init();
});
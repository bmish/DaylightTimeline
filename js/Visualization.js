var SNAPSHOT_PROCESSED_DIR_NAME = "snapshots/processed/";

var jsonDay;
var jsonDays;

var camImageElement;
var pageTitleElement;
var canvasDaylightElement;
var canvasHistoryElement;

function receivedJSONDay(data) {
	jsonDay = data;
	
	updatePageTitle();
	drawDaylight(jsonDay.camImages);
}

function receivedJSONDays(data) {
	jsonDays = data;
	
	updateSliderRange();
	drawHistory(jsonDays.days);
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

function drawDaylight(camImages) {
	// Get canvas.
	var c = getCanvasDaylightElement();
	var ctx = c.getContext("2d");
	
	// Decide how much width to give each image color.
	var rectWidth = 1;
	
	// Draw rectangle for each image color.
	ctx.clearRect(0, 0, c.width, c.height); // Clear canvas.
	for (var i = 0; i < camImages.length; i+=2) {
		ctx.fillStyle = "#" + camImages[i].averagePixelColorHex;
		ctx.fillRect((i/2)*rectWidth,0,rectWidth,c.height);
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
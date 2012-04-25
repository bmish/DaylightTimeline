var SNAPSHOT_PROCESSED_DIR_NAME = "snapshots/processed/";

var jsonDay;
var jsonDays;
var camImageElement;
var pageTitleElement;
var canvasPastElement;
var canvasPostElement;
var canvasHistoryElement;

function receivedJSONDay(data) {
	jsonDay = data;
	
	updateCamImage();
	setCamImageFontColor();
	prepareCamImageEvents();
	drawDaylight(jsonDay.pastCamImages, true);
	drawDaylight(jsonDay.postCamImages, false);
}

function receivedJSONDays(data) {
	jsonDays = data;
	
	updateSliderRange();
	drawHistory(jsonDays.days);
}

function updateSliderRange() {
	document.getElementById("slider").max = jsonDays.days.length - 1;
}

function updateCamImage() {
	// Update cam image if necessary.
	if (getCamImageElement().src != SNAPSHOT_PROCESSED_DIR_NAME + jsonDay.centerCamImage.filename) {
		var im = new Image();
		im.src = SNAPSHOT_PROCESSED_DIR_NAME + jsonDay.centerCamImage.filename;
		getCamImageElement().src = im.src;
	}
	
	// Update page title.
	getPageTitleElement().innerText = $.format.date(jsonDay.centerCamImage.date, "MMMM d, yyyy");
}

function getCamImageElement() {
	if (!camImageElement) {
		camImageElement = document.getElementById("camImage");
	}
	
	return camImageElement;
}

function getPageTitleElement() {
	if (!pageTitleElement) {
		pageTitleElement = document.getElementById("pageTitle");
	}
	
	return pageTitleElement;
}

function getCanvasPastElement() {
	if (!canvasPastElement) {
		canvasPastElement = document.getElementById("canvasPast");
	}
	
	return canvasPastElement;
}

function getCanvasPostElement() {
	if (!canvasPostElement) {
		canvasPostElement = document.getElementById("canvasPost");
	}
	
	return canvasPostElement;
}

function getCanvasHistoryElement() {
	if (!canvasHistoryElement) {
		canvasHistoryElement = document.getElementById("canvasHistory");
	}
	
	return canvasHistoryElement;
}

function drawDaylight(camImages, drawPastDaylight) {
	// Get canvas.
	var c = drawPastDaylight ? getCanvasPastElement() : getCanvasPostElement();
	var ctx = c.getContext("2d");
	
	// Decide how much width to give each image color.
	var rectWidth = 1;
	
	// Draw rectangle for each image color.
	if (drawPastDaylight) {
		for (var i = 0; i < camImages.length; i++) {
			ctx.fillStyle = "#" + camImages[i].averagePixelColorHex;
			ctx.fillRect(c.width - i*rectWidth - 1,0,rectWidth,c.height);
		}
	} else {
		for (var i = 0; i < camImages.length; i++) {
			ctx.fillStyle = "#" + camImages[i].averagePixelColorHex;
			ctx.fillRect(i*rectWidth,0,rectWidth,c.height);
		}
	}
}

function daysSinceLastCamImage() {
	var dateStartString = jsonDays.days[jsonDays.days.length - 1].date;
	var dateStart = new Date(Date.parse(dateStartString));
	var today = new Date();
	
	// Both dates should have zeros for the time because we only want to count the number of days.
	today.setHours(0);
	today.setMinutes(0);
	today.setSeconds(0);
	today.setMilliseconds(0);
	
	return daysBetween(dateStart, today);
}

function daysBetween(dateStart, dateEnd) {
	var diff = dateEnd.getTime() - dateStart.getTime();
	
	var MILLISECONDS_IN_DAY = 1000 * 60 * 60 * 24;
	var dayCount = Math.floor(diff / MILLISECONDS_IN_DAY);
	
	return dayCount;
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

// http://wiki.vyre.com/index.php/JavaScript:_Opposite_colour
function decimalToHex(decimal) {
  var hex = decimal.toString(16);
  if (hex.length == 1) hex = '0' + hex;
  return hex;
}

function hexToDecimal(hex) {return parseInt(hex,16);}
 
function returnOpposite(colour) {
  return decimalToHex(255 - hexToDecimal(colour.substr(0,2))) 
    + decimalToHex(255 - hexToDecimal(colour.substr(2,2))) 
    + decimalToHex(255 -  hexToDecimal(colour.substr(4,2)));
}

function setCamImageFontColor() {
	$("#camImageHeader").css({"color": "#" + returnOpposite(jsonDay.centerCamImage.averagePixelColorHex)})
}

function maximizeTimeOnCamImage() {
	$('#camImage').mousemove(minimizeTimeOnCamImage);
	$("#camImageHeader").addClass("camImageHeaderFullSize");
}

function minimizeTimeOnCamImage() {
	$("#camImageHeader").removeClass("camImageHeaderFullSize");
}

function prepareCamImageEvents() {
	$('#camImage').mousedown(maximizeTimeOnCamImage);
	$('#camImage').mouseup(minimizeTimeOnCamImage);
}

function init() {
	jsonDay = null;
	jsonDays = null;
	camImageElement = null;
	pageTitleElement = null;
	canvasPastElement = null;
	canvasPostElement = null;
	
	var date = getParameterByName("center");
	
	jQuery.getJSON("index.php?jsonDays=true", receivedJSONDays);
	jQuery.getJSON("index.php?jsonDay=true&center=" + date, receivedJSONDay);
}

// http://stackoverflow.com/questions/901115/get-query-string-values-in-javascript
function getParameterByName(name)
{
  name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
  var regexS = "[\\?&]" + name + "=([^&#]*)";
  var regex = new RegExp(regexS);
  var results = regex.exec(window.location.search);
  if(results == null)
    return "";
  else
    return decodeURIComponent(results[1].replace(/\+/g, " "));
}

function rangeUpdated(newValue) {
	var daysAgo = daysSinceLastCamImage() + jsonDays.days.length - newValue - 1; 
	
	jQuery.getJSON("index.php?jsonDay=true&center=" + daysAgo + " days ago", receivedJSONDay);
}
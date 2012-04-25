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

function getCanvasDaylightElement() {
	if (!canvasDaylightElement) {
		canvasDaylightElement = document.getElementById("canvasDaylight");
	}
	
	return canvasDaylightElement;
}

function getCanvasHistoryElement() {
	if (!canvasHistoryElement) {
		canvasHistoryElement = document.getElementById("canvasHistory");
	}
	
	return canvasHistoryElement;
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
	
	jQuery.getJSON("index.php?jsonDay=true&date=" + daysAgo + " days ago", receivedJSONDay);
}
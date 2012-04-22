var json;

function receivedJSON(data) {
	json = data;
	
	setCamImageFontColor();
	prepareCamImageEvents();
	drawDaylight("pastDaylight",json.pastCamImages);
	drawDaylight("postDaylight",json.postCamImages);
}

function drawDaylight(canvasName, camImages) {
	// Get canvas.
	var c = document.getElementById(canvasName);
	var ctx = c.getContext("2d");
	
	// Decide how much width to give each image color.
	var rectWidth = 1;
	
	// Draw rectangle for each image color.
	if (canvasName == "pastDaylight") {
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
	$("#camImageHeader").css({"color": "#" + returnOpposite(json.centerCamImage.averagePixelColorHex)})
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
	var date = getParameterByName("date");
	
	jQuery.getJSON("index.php?json=true&date=" + date, receivedJSON);
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
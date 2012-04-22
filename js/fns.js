var json;

function receivedJSON(data) {
	json = data;
	
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
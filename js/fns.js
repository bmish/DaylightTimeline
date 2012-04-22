var json;

function receivedJSON(data) {
	json = data;
	
	drawPastDaylight();
}

function drawPastDaylight() {
	// Get canvas.
	var c = document.getElementById("pastDaylight");
	var ctx = c.getContext("2d");
	
	// Calculate how much width to give each image color.
	var rectWidth = Math.floor(c.width / json.pastCamImages.length);
	
	// Draw rectangle for each image color.
	for (var i = 0; i < json.pastCamImages.length; i++) {
		ctx.fillStyle = "#" + json.pastCamImages[i].averagePixelColorHex;
		ctx.fillRect(c.width - i*rectWidth,0,rectWidth,c.height);
	}
}

function init() {
	jQuery.getJSON("index.php?json=true", receivedJSON);
}
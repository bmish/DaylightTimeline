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
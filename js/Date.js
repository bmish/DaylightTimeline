function getDayDateFromString(dateString) {
	var date = new Date(Date.parse(dateString));
	return getDayDate(date);
}

function getDayDate(date) {
	if (!date) {
		date = new Date();
	}
	
	date.setHours(0);
	date.setMinutes(0);
	date.setSeconds(0);
	date.setMilliseconds(0);
	
	return date;
}

function daysBetween(dateStart, dateEnd) {
	var diff = dateEnd.getTime() - dateStart.getTime();
	
	var MILLISECONDS_IN_DAY = 1000 * 60 * 60 * 24;
	var dayCount = Math.floor(diff / MILLISECONDS_IN_DAY);
	
	return dayCount;
}

function millisecondsBetween(dateStart, dateEndString) {
	var dateEnd = new Date(Date.parse(dateEndString));
	var diff = dateEnd.getTime() - dateStart.getTime();
}
function getDateFromString(dateString) {
	return new Date(Date.parse(dateString));
}

function getDayDate(date) {
	var newDate = new Date();
	if (date) {
		newDate.setTime(date.getTime());
	}
	
	newDate.setHours(0);
	newDate.setMinutes(0);
	newDate.setSeconds(0);
	newDate.setMilliseconds(0);
	
	return newDate;
}

function daysBetween(dateStart, dateEnd) {
	var diff = dateEnd.getTime() - dateStart.getTime();
	var dayCount = Math.floor(diff / MS_PER_DAY);
	
	return dayCount;
}

function dateBetweenSecondsOfDay(dateExact, secondsStart, secondsEnd) {
	var dateDay = getDayDate(dateExact);
	
	var timeStart = dateDay.getTime() + secondsStart * MS_PER_SECOND;
	var timeEnd = dateDay.getTime() + secondsEnd * MS_PER_SECOND;
	var timeExact = dateExact.getTime();
	
	return timeExact >= timeStart && timeExact <= timeEnd;
}

function getDaySeconds(dateExact) {
	var dateDay = getDayDate(dateExact);
	var milliseconds = dateExact.getTime() - dateDay.getTime();

	return milliseconds / MS_PER_SECOND;
}
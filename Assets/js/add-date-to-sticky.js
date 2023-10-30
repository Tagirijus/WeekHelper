document.addEventListener('DOMContentLoaded', () => {

	var currentDateTimeBox = document.getElementById('currentDateTimeBox');
	if (currentDateTimeBox != null) {
		// first initial date
		currentDateTimeBox.innerHTML = getCurrentDateContent();
		// update every minute
		var currentDateUpdater = setInterval(function() {
			updateCurrentDate('currentDateTimeBox');
		}, 60000);
	}

});

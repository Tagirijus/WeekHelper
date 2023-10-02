document.addEventListener('DOMContentLoaded', () => {

	var headerTitle = document.getElementsByClassName('title');
	if (headerTitle != null) {
		// create initial date-span
		var currentDateHeader = document.createElement('span');
		currentDateHeader.id = 'currentDateHeader';
		currentDateHeader.innerHTML = getCurrentDateContent();

		// get actual header title and put currentDateHeader after it
		headerTitle = headerTitle[0];
		insertAfter(headerTitle, currentDateHeader);

		// update every minute
		var currentDateUpdater = setInterval(function() {
			updateCurrentDate('currentDateHeader');
		}, 60000);
	}

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

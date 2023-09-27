document.addEventListener('DOMContentLoaded', () => {

	var headerTitle = document.getElementsByClassName('title');
	if (headerTitle != null) {
		// create initial date-span
		var currentDate = document.createElement('span');
		currentDate.id = 'currentDate';
		currentDate.innerHTML = getCurrentDateContent();

		// get actual header title and put currentDate after it
		headerTitle = headerTitle[0];
		insertAfter(headerTitle, currentDate);

		// update every minute
		var currentDateUpdater = setInterval(function() {
			updateCurrentDate();
		}, 60000);
	}

});

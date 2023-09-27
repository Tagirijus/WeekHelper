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


Date.prototype.getWeek = function() {
	var onejan = new Date(this.getFullYear(),0,1);
	return Math.ceil((((this - onejan) / 86400000) + onejan.getDay()+1)/7);
}

function getCurrentDateContent() {
	var now = new Date();
	var dd = String(now.getDate()).padStart(2, '0');
	var mm = String(now.getMonth() + 1).padStart(2, '0'); // January is 0!
	var yyyy = now.getFullYear();
	var yy = yyyy.toString().slice(-2);
	var day = now.toLocaleDateString('de-DE', { weekday: 'short' })
	var HH = now.getHours();
	var MM = now.getMinutes().toString().padStart(2, '0');
	var WW = now.getWeek();
	return `${day}, ${dd}.${mm}.${yy} - ${HH}:${MM} - W${WW}`;
}

function insertAfter(referenceNode, newNode) {
	referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

function updateCurrentDate() {
	var currentDate = document.getElementById('currentDate');
	if (currentDate != null) {
		currentDate.innerHTML = getCurrentDateContent();
	}
}

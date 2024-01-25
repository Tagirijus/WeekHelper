var WEEKPATTERN = null;


Date.prototype.getWeek = function() {
	var firstOfJanuary = new Date(this.getFullYear(), 0, 1);
	return Math.ceil((((this - firstOfJanuary) / 86400000) + firstOfJanuary.getDay() - 1) / 7);
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

function updateCurrentDate(elemId) {
	var currentDate = document.getElementById(elemId);
	if (currentDate != null) {
		currentDate.innerHTML = getCurrentDateContent();
	}
}

function getWeeks() {
	var now = new Date();
	var next = new Date();
	next.setDate(next.getDate() + 7);
	var overnext = new Date();
	overnext.setDate(overnext.getDate() + 14);
	var over2next = new Date();
	over2next.setDate(over2next.getDate() + 21);
	var over3next = new Date();
	over3next.setDate(over3next.getDate() + 28);
	var over4next = new Date();
	over4next.setDate(over4next.getDate() + 35);

	var now_insert = formatWithWeekPattern(now);
	var next_insert = formatWithWeekPattern(next);
	var overnext_insert = formatWithWeekPattern(overnext);
	var over2next_insert = formatWithWeekPattern(over2next);
	var over3next_insert = formatWithWeekPattern(over3next);
	var over4next_insert = formatWithWeekPattern(over4next);

	var now_label = `+0 (this week): ${now_insert}`;
	var next_label = `+1 (next week): ${next_insert}`;
	var overnext_label = `+2 (in two weeks): ${overnext_insert}`;
	var over2next_label = `+3 (in three weeks): ${over2next_insert}`;
	var over3next_label = `+4 (in four weeks): ${over3next_insert}`;
	var over4next_label = `+5 (in five weeks): ${over4next_insert}`;

	return [
		{'label': now_label, 'insert': now_insert},
		{'label': next_label, 'insert': next_insert},
		{'label': overnext_label, 'insert': overnext_insert},
		{'label': over2next_label, 'insert': over2next_insert},
		{'label': over3next_label, 'insert': over3next_insert},
		{'label': over4next_label, 'insert': over4next_insert}
	];
}

function formatWithWeekPattern(date) {
	var out = WEEKPATTERN;
	out = out.replace('{YEAR}', date.getFullYear());
	out = out.replace('{YEAR_SHORT}', date.getFullYear().toString().slice(-2));
	out = out.replace('{WEEK}', date.getWeek());
	return out;
}

function getWeekPattern() {
	return new Promise((resolve) => {
		if (WEEKPATTERN == null) {
			fetch('/weekhelper/weekpattern').then(
				response => response.text()
			).then(
				result => {
					WEEKPATTERN = result;
					resolve(WEEKPATTERN);
				}
			);
		} else {
			resolve(WEEKPATTERN);
		}
	});
}

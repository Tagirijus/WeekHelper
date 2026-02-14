document.addEventListener('DOMContentLoaded', () => {

	const url = window.location.href;
	const is_automatic_plan_page = url.includes('/weekhelper/automaticplan');

	if (!is_automatic_plan_page) {
		fetch('/weekhelper/automaticplan?type=sticky').then(
			response => response.text()
		).then(
			result => {
				document.body.innerHTML += result;
				initAutomaticPlanControls();
			}
		);
	}

});

document.addEventListener('DOMContentLoaded', () => {

	var plan_btn = document.querySelectorAll('.plan-btn');
	Array.prototype.forEach.call(plan_btn, (el, i) => {

		el.addEventListener('click', (event) => {
			event.preventDefault();
			planToggleTab(el);
		});

	});

	var plan_sticky_container = document.getElementsByClassName('plan-sticky-container');
	if (plan_sticky_container != null) {
		plan_sticky_container[0].addEventListener('mouseleave', () => {
		    plan_sticky_container[0].scrollTop = 0;
		});
	}

});


function planToggleTab(el) {
	// cookie setting
	let selected = el.getAttribute('data-plan-secet-btn');
	createCookie('plan-tab-selected', selected, 365);

	// buttons toggle
	var plan_btn = document.querySelectorAll('.plan-btn');
	Array.prototype.forEach.call(plan_btn, (e, i) => {
		e.classList.remove('btn-blue');
	});
	el.classList.add('btn-blue');

	// tab toggle
	var plan_container = document.querySelectorAll('.plan-container');
	Array.prototype.forEach.call(plan_container, (e, i) => {
		let tab = e.getAttribute('data-plan-tab');
		if (tab == selected) {
			e.classList.remove('plan-hidden');
		} else {
			e.classList.add('plan-hidden');
		}
	});
}

function createCookie(name, value, days) {
    var expires;
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    }
    else {
        expires = "";
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

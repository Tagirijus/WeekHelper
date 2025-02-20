// Since I want to have my HoursView plugin use and show the
// actual remaining as soon as a subtask might be checked
// as done and the SubtaskStatusController only will reload
// the checkbox, I simply did the monkey patch to always
// reload the whole site on a click of the subtask status
// checkbox. It's not that great, but it is something ...
KB.on('subtasks.reloaded', function () {
	window.location.reload();
});


// also when clikcing on "start timer" for a subtask I want
// the whole site to refresh as well ... monkey patchissimo!
$(document).ready(function() {
    $('.js-subtask-toggle-timer').click(function() {
        // refresh page after short delay so that the
        // original action is being processed as well
        setTimeout(function() {
            window.location.reload();
        }, 100);
    });
});
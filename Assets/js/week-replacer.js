document.addEventListener('DOMContentLoaded', () => {

	KB.on('modal.afterRender', function () {
		$('#form-title').textcomplete([{
			match: /w(.*)$/,
			search: function (term, callback) {
				getWeekPattern().then(weekpattern => {
					var results = [];

					$.each(getWeeks(), function(basename, data) {
						if (data.label.indexOf(term) > -1) {
							results.push(data.label);
						}
					});

					callback(results);
				});
			},
			replace: function(choice) {
				var out = 'w';
				$.each(getWeeks(), function(basename, data) {
					if (data.label == choice) {
						out = data.insert;
					}
				});
				return out;
			},
			index: 1,
			maxCount: 10
		}]);
	});

});

var checkbox_insertions = ['- [ ] ', '[ ] '];


document.addEventListener('DOMContentLoaded', () => {

	$('textarea').textcomplete([{
		match: /\-(.*)$/,
		search: function (term, callback) {
			var results = [];

			$.each(checkbox_insertions, function(basename, data) {
				if (data.indexOf(term) > -1) {
					results.push(data);
				}
			});

			callback(results);
		},
		replace: function(choice) {
			return choice;
		},
		index: 1,
		maxCount: 10
	}]);

	KB.on('modal.afterRender', function () {
		$('textarea').textcomplete([{
			match: /\-(.*)$/,
			search: function (term, callback) {
				var results = [];

				$.each(checkbox_insertions, function(basename, data) {
					if (data.indexOf(term) > -1) {
						results.push(data);
					}
				});

				callback(results);
			},
			replace: function(choice) {
				return choice;
			},
			index: 1,
			maxCount: 10
		}]);
	});

});

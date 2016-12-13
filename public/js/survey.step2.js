var SurveyStep2 = (function() {
	var api = {};
	var nameBox = $("#name");
	var languageBox = $("#language");
	loaded = false;

	api.save = function (data) {
		data.name = nameBox.val();
		data.language = languageBox.val();
	};

	api.load = function (data) {
		nameBox.val(data.name);
		languageBox.val(data.language);
		Survey.setLang(data.language, false);
		nameBox.change();
		loaded = true;
	};

	$(document).ready(function() {
		var step2NextBtn = $("#step2NextBtn");

		nameBox.change(function(e) {
			var name = $(e.target);

			if (name.val() == "") {
				Helpers.markInvalid(name);
				step2NextBtn.prop("disabled", true);
			} else {
				step2NextBtn.prop("disabled", false);
				Helpers.markValid(name);
			}
		});

	    languageBox.change(function(e) {
	        Survey.setLang(e.target.value);
	    });

	    nameBox.change();

		if (!loaded) {
			languageBox.change();
		}
	});

	return api;
})();

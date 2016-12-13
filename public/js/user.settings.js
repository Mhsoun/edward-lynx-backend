var UserSettings = (function() {
    var api = {};

    //Switches the texts
    api.switchTexts = function (element, lang, type) {
        element.find(".lang").each(function(i, langElement) {
            langElement = $(langElement);

            if (langElement.hasClass('lang-' + lang)) {
                langElement.show();

                langElement.find(".type").each(function(i, typeElement) {
                    typeElement = $(typeElement);

                    if (typeElement.hasClass('type-' + type)) {
                        typeElement.show();
                    } else {
                        typeElement.hide();
                    }
                });
            } else {
                langElement.hide();
            }
        });
    };

    return api;
})();

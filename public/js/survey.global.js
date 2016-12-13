//Global survey functions & variables
var Survey = (function() {
    var api = {};
    var type = "";
    var lang = "";
    api.languageStrings = {};

    //The company id if the user is an admin
    var companyId = null;

     //Indicates if individual survey
    api.isIndividual = function() {
        return type == "individual";
    }

    //Indicates if group survey
    api.isGroup = function() {
        return type == "group";
    }

    //Indicates if progress survey
    api.isProgress = function() {
        return type == "progress";
    }

    //Indicates if LTT survey
    api.isLTT = function() {
        return type == "ltt";
    }

    //Indicates if normal survey
    api.isNormal = function() {
        return type == "normal";
    }

    //Returns the type id
    api.typeId = function() {
        switch (type) {
            case "individual":
                return 0;
            case "group":
                return 1;
            case "progress":
                return 2;
            case "normal":
                return 3;
            case "ltt":
                return 4;
        }

        return -1;
    }

    //Returns the type name for the given type
    function getTypeName(type) {
        switch (type) {
            case "individual":
                return api.languageStrings['surveys.individual'];
            case "group":
                return api.languageStrings['surveys.group'];
            case "progress":
                return api.languageStrings['surveys.progress'];
            case "normal":
                return api.languageStrings['surveys.normal'];
            case "ltt":
                return api.languageStrings['surveys.ltt'];
        }

        return "";
    }

    //Sets the survey type
    api.setType = function(newType, update) {
        if (type != newType) {
            type = newType;

            if (update == undefined || update) {
                var newEvent = new CustomEvent('changeType', { 'detail': { type: newType } });
                window.dispatchEvent(newEvent);
            }
        }
    }

    //Adds an event handler for when the survey type changes
    api.onChangeType = function(fn) {
        window.addEventListener("changeType", fn);
    }

    //Sets the company id if the user is an admin
    api.setCompanyId = function(newCompanyId) {
        companyId = newCompanyId;
    }

    //Returns the company id
    api.companyId = function() {
        return companyId;
    }

    //Sets the language
    api.setLang = function(newLang, update) {
        if (lang != newLang) {
            lang = newLang;

            if (update == undefined || update) {
                var newEvent = new CustomEvent('changeLanguage', { 'detail': { lang: newLang } });
                window.dispatchEvent(newEvent);
            }
        }
    }

    //Adds an event handler for when the language changes
    api.onChangeLang = function(fn) {
        window.addEventListener("changeLanguage", fn);
    }

    //Returns the language
    api.lang = function() {
        return lang;
    }

    //Submits the form
    api.submitForm = function() {
        if (confirm(api.languageStrings["survey.confirmCreation"])) {
            $("#createSurveyForm").submit();
            // localStorage.removeItem("survey_" + companyId);
        }
    }

    var navListItems = null;
    var allNextBtn = null;
    var doneSteps = {};

    //Updates the nav menu
    api.updateNavMeny = function() {
        //Remove event handlers from previous
        if (navListItems != null) {
            navListItems.off('click');
        }

        navListItems = $('div.setup-panel div a');
        allNextBtn = $('.nextBtn');

        var allWells = $('.setup-content');
        allWells.hide();

        navListItems.click(function (e) {
            e.preventDefault();
            var $target = $($(this).attr('href'));
            var $item = $(this);

            // if ($item.attr('disabled') != "disabled") {
                navListItems.removeClass('btn-primary').addClass('btn-default');
                $item.addClass('btn-primary');
                allWells.hide();
                $target.show();
                $target.find('input:eq(0)').focus();
            // }
        });

        allNextBtn.click(function() {
            var currentStep = $(this).closest(".setup-content");
            var currentStepBtn = currentStep.attr("id");
            var nextStepWizard = $('div.setup-panel div a[href="#' + currentStepBtn + '"]').parent().next().children("a");
            nextStepWizard.removeAttr('disabled').trigger('click');
            doneSteps[currentStepBtn] = true;
        });

        $('div.setup-panel div a.btn-primary').trigger('click');
    }

    function getSurveySteps() {
        return [SurveyStep1, SurveyStep2, SurveyStep3, SurveyStep4, SurveyStep5, SurveyStep6];
    }

    // window.onbeforeunload = function() {
    //     var savedSurvey = {};
    //
    //     getSurveySteps().forEach(function (step) {
    //         step.save(savedSurvey);
    //     });
    //
    //     savedSurvey.doneSteps = doneSteps;
    //     localStorage.setItem("survey_" + companyId, JSON.stringify(savedSurvey));
    // }

    //Loads the given survey
    function loadSurvey(savedSurvey) {
        getSurveySteps().forEach(function (step) {
            step.load(savedSurvey);
        });

        for (var id in savedSurvey.doneSteps) {
            var stepWizard = $('div.setup-panel div a[href="#' + id + '"]');
            stepWizard.removeAttr('disabled');
        }

        Survey.updateNavMeny();
    }

    $(document).ready(function () {
        // var getSurvey = localStorage.getItem("survey_" + companyId);
        //
        // if (getSurvey != null) {
        //     var savedSurvey = JSON.parse(getSurvey);
        //     loadSurvey(savedSurvey);
        // } else {
        //     Survey.updateNavMeny();
        // }

        updateLoadList();
    });

    //Saves the current project
    api.saveProject = function() {
        //Get the list
        var surveysList = JSON.parse(localStorage.getItem("surveys_" + companyId));

        if (surveysList == null) {
            surveysList = [];
        }

        //Create the save object
        var savedSurvey = {};
        getSurveySteps().forEach(function (step) {
            step.save(savedSurvey);
        });

        savedSurvey.doneSteps = doneSteps;
        savedSurvey.savedDate = new Date();

        if (savedSurvey.name != "") {
            surveysList.push(savedSurvey);

            //Save the list
            localStorage.setItem("surveys_" + companyId, JSON.stringify(surveysList));
            updateLoadList();
            $("#saveList").append(jQuery("<li />").text(savedSurvey.name + " " + api.languageStrings['surveys.projectSaved']));
        }
    }

    //Updates the list of surveys that can be laoded
    function updateLoadList() {
        var surveysList = JSON.parse(localStorage.getItem("surveys_" + companyId));

        if (surveysList != null) {
            var loadBox = $("#loadBox");
            loadBox.find(".loadSurvey").remove();

            surveysList.sort(function (x, y) { return x.savedDate > y.savedDate ? -1 : (x.savedDate < y.savedDate ? 1 : 0); }).forEach(function (survey) {
                var surveyBox = jQuery("<div class='loadSurvey' />");
                var formatedDate = moment(survey.savedDate).format('YYYY-MM-DD HH:mm:ss');

                surveyBox.append(jQuery("<b />").text(survey.name + " (" + getTypeName(survey.type) + ") - " + formatedDate));
                surveyBox.append("<br>");

                var loadButton = jQuery("<a class='textButton' />").text(api.languageStrings['buttons.load']);
                var deleteButton = jQuery("<a class='textButton' />").text(api.languageStrings['buttons.delete']);

                loadButton.click(function() {
                    loadSurvey(survey);
                });

                deleteButton.click(function() {
                    var index = surveysList.indexOf(survey);
                    if (index > -1) {
                        surveyBox.remove();
                        surveysList.splice(index, 1);
                        localStorage.setItem("surveys_" + companyId, JSON.stringify(surveysList));
                    }
                });

                surveyBox.append(loadButton);
                surveyBox.append("<br>");

                surveyBox.append(deleteButton);
                surveyBox.append("<br>");
                surveyBox.append("<br>");

                loadBox.append(surveyBox);
            });
        }
    }

    //Toggles the settings box
    api.toggleSettings = function() {
        $("#settingsBox").toggle();
    }

    return api;
})();

var SurveyStep7 = (function() {
    var api = {};

    var extraQuestionName = $("#extraQuestionName");
    var extraQuestionMandatory = $("#extraQuestionMandatory");

    var optionValuesBox = $("#optionValuesBox");
    var optionValues = $("#extraQuestionOptionValues");
    var newOptionValue = $("#newOptionValue");

    var hierarchyBox = $("#hierarchyBox");
    var hierarchyData = [];

    //Selects all the extra questions
    api.selectAllExtraQuestions = function() {
         Helpers.selectAllCheckboxes($("#extraQuestionsTable"), "extraQuestions[]");
    };

    $("input[name=extraQuestionType]:radio").change(function(e) {
        switch ($(e.target).val()) {
            case "options":
                optionValuesBox.show();
                hierarchyBox.hide();
                break;
            case "hierarchy":
                hierarchyBox.show();
                optionValuesBox.hide();
                break;
            default:
                optionValuesBox.hide();
                hierarchyBox.hide();
                break;
        }
    });
    $("input[name=extraQuestionType]:radio").change();

    $("#addOptionValue").click(function() {
    	var value = newOptionValue.val();

    	if (value != "") {
    		var newValue = jQuery("<div />");
    		var deleteButton = jQuery("<a class='textButton' style='margin-right: 5px'><span class='glyphicon glyphicon-trash' /></a>");
    		newValue.append(deleteButton)
    		newValue.append(jQuery("<span class='optionValue' />").text(value));

    		deleteButton.click(function() {
    			newValue.remove();
    		});

    		optionValues.append(newValue);
    		newOptionValue.val("");
    	}
    });

    //Adds the given extra question
    function addExtraQuestion(extraQuestion) {
        extraQuestion.companyId = Survey.companyId;
        $.ajax({
            url: "/survey/extra-questions",
            method: "post",
            data: extraQuestion,
            dataType: "json"
        }).done(function(data) {
            if (data.success) {
                extraQuestion.id = data.id;
                addExtraQuestionToUI(extraQuestion);
            }
        });
    }

    function addAllValues(data, parentValue, fn) {
        data.forEach(function (value) {
            var outputValue = "";
            if (parentValue != "") {
                outputValue += parentValue;
            }

            outputValue += value.value;

            fn(outputValue);
            addAllValues(value.children, outputValue + " > ", fn);
        });
    }

    //Adds the given extra question to the UI
    function addExtraQuestionToUI(extraQuestion) {
        var extraQuestionRow = jQuery("<tr />")
            .addClass('extraQuestion')
            .addClass('lang-' + Survey.lang());

        extraQuestionRow.append(jQuery("<td />")
            .append(jQuery("<input type='checkbox' name='extraQuestions[]'>")
                        .val(extraQuestion.id)
                        .attr("checked", "checked")));

        extraQuestionRow.append(jQuery("<td />").text(extraQuestion.name));

        var typeCol = null;
        switch (extraQuestion.type) {
            case "text":
                typeCol = jQuery("<span />").text(Survey.languageStrings["surveys.freeText"]);
                break;
            case "date":
                typeCol = jQuery("<span />").text(Survey.languageStrings["surveys.date"]);
                break;
            case "options":
                typeCol = jQuery("<select class='form-control' />");
                extraQuestion.values.forEach(function (value) {
                    typeCol.append(jQuery("<option />").text(value));
                });
                break;
            case "hierarchy":
                typeCol = jQuery("<select class='form-control' />");
                addAllValues(extraQuestion.values, "", function (value) {
                    typeCol.append(jQuery("<option />").text(value));
                });
                break;
        }

        extraQuestionRow.append(jQuery("<td />").append(typeCol));

        extraQuestionRow.append(jQuery("<td />").text(!extraQuestion.isOptional ?
            Survey.languageStrings['buttons.yes'] : Survey.languageStrings['buttons.no']));

        $("#extraQuestionsTable").append(extraQuestionRow);
    }

    $("#addExtraQuestionButton").click(function() {
    	var name = extraQuestionName.val();
    	var type = $("input[type=radio][name=extraQuestionType]:checked").val() || "";

    	if (name != "" && type != "") {
    		var values = [];

            switch (type) {
                case "options":
                    optionValues.find(".optionValue").each(function(i, e) {
                        values.push($(e).text());
                        $(e).parent().remove();
                    });
                    break;
                case "hierarchy":
                    $("#hierarchyView").html("");
                    values = hierarchyData;
                    hierarchyData = [];
                    addNewHierarchyLevel($("#hierarchyView"), 0, hierarchyData);
                    break;
            }

    		addExtraQuestion({
    			id: 0,
    			name: name,
    			type: type,
    			isOptional: !extraQuestionMandatory.is(":checked"),
    			values: values,
                lang: Survey.lang()
    		});

    		extraQuestionName.val("");
    	}
    });

    //Updates the selectable questions
    function updateSelectableQuestions() {
        Helpers.deselectAllCheckboxes($("#extraQuestionsTable"), "extraQuestions[]");
        $("#extraQuestionsTable .extraQuestion").each(function(i, element) {
            element = $(element);
            if (element.hasClass('lang-' + Survey.lang())) {
                element.show();
            } else {
                element.hide();
            }
        });
    }

    Survey.onChangeLang(function(e) {
        updateSelectableQuestions();
    });

    //Adds a new hierarchy level
    function addNewHierarchyLevel(element, level, data) {
        var newLevel = jQuery("<div />").css("margin-left", level * 20);

        var newValue = jQuery('<input type="text" class="form-control" style="max-width: 20em; margin-bottom: 5px; display: inline; margin-right: 7px">');
        var addNewValueButton = jQuery('<a class="textButton"><span class="glyphicon glyphicon-plus"></span></a>');

        newLevel.append(jQuery("<label />").text("New value"));
        newLevel.append("<br>");
        newLevel.append(newValue);
        newLevel.append(addNewValueButton);

        var newLevelValues = jQuery("<div />").css('margin-bottom', "10px");
        newLevel.append(newLevelValues);

        addNewValueButton.click(function() {
            var value = newValue.val();

        	if (value != "") {
        		var newValueBox = jQuery("<div />");

        		var deleteButton = jQuery("<a class='textButton' style='margin-right: 5px'><span class='glyphicon glyphicon-trash' /></a>");
        		newValueBox.append(deleteButton)

                var newLevelButton = jQuery("<a class='textButton' style='margin-right: 5px'><span class='glyphicon glyphicon-plus' /></a>");
        		newValueBox.append(newLevelButton)

        		newValueBox.append(jQuery("<span class='optionValue' />").text(value));

        		deleteButton.click(function() {
        			newValueBox.remove();
        		});

                var valueData = {
                    value: value,
                    children: []
                };

                data.push(valueData);

                newLevelButton.click(function() {
                    addNewHierarchyLevel(newValueBox, level + 1, valueData.children);
                });

        		newLevelValues.append(newValueBox);
        		newValue.val("");
        	}
        });

        element.append(newLevel);
    }

    addNewHierarchyLevel($("#hierarchyView"), 0, hierarchyData);

    return api;
})();

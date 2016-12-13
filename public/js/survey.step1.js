var SurveyStep1 = (function() {
    var api = {};
    var typeBox = $("input[name=type]:radio");
    var step1NextBtn = $("#step1NextBtn");
    step1NextBtn.prop("disabled", true);

    //Changes the type
    function changeType(newType) {
        var step1NextBtn = $("#step1NextBtn");
        var step6NextBtn = $("#step6NextBtn");
        var step7NextBtn = $("#step7NextBtn");
        var step7NavButton = $("#step7NavButton");
        var typeBox = $("input[name=type]:radio");
        step1NextBtn.prop("disabled", false);

        Survey.setType(newType);
        var isNormal = false;
        var isGroup = false;

        if (Survey.isIndividual()) {
            SurveyStep4.switchToIndividual();
            SurveyStep5.switchToIndividual();
            SurveyStep6.switchToIndividual();
        } else if (Survey.isGroup()) {
            SurveyStep4.switchToGroup();
            SurveyStep5.switchToGroup();
            SurveyStep6.switchToGroup();
            isGroup = true;
        } else if (Survey.isLTT()) {
            SurveyStep4.switchToGroup();
            SurveyStep5.switchToGroup();
            SurveyStep6.switchToGroup();
            isGroup = true;
        } else if (Survey.isProgress()) {
            SurveyStep4.switchToIndividual();
            SurveyStep5.switchToIndividual();
            SurveyStep6.switchToIndividual();
        } else if (Survey.isNormal()) {
            SurveyStep4.switchToNormal();
            SurveyStep5.switchToNormal();
            SurveyStep6.switchToNormal();
            isNormal = true;
        }

        //Update question
        if (isGroup) {
            SurveyStep3.switchToGroup();
        } else {
            SurveyStep3.switchToNoneGroup();
        }

        //Update nav menu
        step6NextBtn.off("click");
        step7NextBtn.off("click");

        if (isNormal) {
            step7NavButton.show();

            step6NextBtn.addClass("nextBtn");
            step6NextBtn.addClass("btn-primary");
            step6NextBtn.removeClass("btn-success");
            step6NextBtn.text(Survey.languageStrings['buttons.next']);

            step7NextBtn.click(function(e) {
                e.preventDefault();
                Survey.submitForm();
            })
        } else {
            step7NavButton.hide();

            step6NextBtn.removeClass("nextBtn");
            step6NextBtn.removeClass("btn-primary");
            step6NextBtn.addClass("btn-success");
            step6NextBtn.text(Survey.languageStrings['buttons.finish']);
            step6NextBtn.click(function(e) {
                e.preventDefault();
                Survey.submitForm();
            })
        }

        Survey.updateNavMeny();

        step1NextBtn.prop(
            "disabled",
            ($("input:radio[name='type']:checked").val() || "") == "");
    }

    $(document).ready(function() {
        typeBox.change(function(e) {
            changeType($("input:radio[name='type']:checked").val() || "individual");
        });
    })

    api.save = function (data) {
		data.type = $("input:radio[name='type']:checked").val();
	};

	api.load = function (data) {
        typeBox.each(function(i, type) {
            type = $(type);
            if (data.type == type.val()) {
                type.prop("checked", true);
            }
        });

        changeType(data.type);
	};

    return api;
})();

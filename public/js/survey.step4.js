var SurveyStep4 = (function() {
    var api = {};
    var defaultInformations = {};

    var step4NextBtn = $("#step4NextBtn");

    var startDate = $("#startDate");
    var endDate = $("#endDate");

    var description = $("#description");
    var individualInviteText = $("#individualInviteText");
    var individualInviteTextBox = $("#individualInviteTextBox");
    var thankYou = $("#thankYou");
    var questionInfo = $("#questionInfo");

    //Checks if the input data is valid
    function checkIfValid() {
        var isValid = true;

        var checkEmpty = function(element, mark) {
            if (mark === undefined) {
                mark = true;
            }

            if (element.val() == "") {
                isValid = false;

                if (mark) {
                    Helpers.markInvalid(element);
                }
            } else {
                if (mark) {
                    Helpers.markValid(element);
                }
            }
        }

        checkEmpty($("#startDate"));
        checkEmpty($("#endDate"));

        checkEmpty($("#description"));

        if (Survey.isIndividual() || Survey.isProgress()) {
            checkEmpty($("#individualInviteText"));
        }

        checkEmpty($("#thankYou"));

        step4NextBtn.prop("disabled", !isValid);
    }

    $('#startDatePicker').on("dp.change", function(e) {
        checkIfValid();
    });

    $('#endDatePicker').on("dp.change", function(e) {
        checkIfValid();

        var endDate = $("#endDate").val();
        $('#newRecipientEndDate').val(endDate);
        $('#newRecipientEndDateRecipients').val(endDate);
        $('#existingRecipientEndDate').val(endDate);
        $('#existingRecipientEndDateRecipients').val(endDate);
    });

    $(document).ready(function() {
        checkIfValid();
    });

    //Sets the default informations
    api.setDefaultInformations = function (newDefaultInformations) {
        defaultInformations = newDefaultInformations;
    }

    //Switches to an individual survey
    api.switchToIndividual = function() {
        individualInviteTextBox.show();
        checkIfValid();
    }

    //Switches to a group survey
    api.switchToGroup = function() {
        individualInviteTextBox.hide();
        checkIfValid();
    }

    //Switches to a normal survey
    api.switchToNormal = function() {
        individualInviteTextBox.hide();
        checkIfValid();
    }

    description.change(checkIfValid);
    individualInviteText.change(checkIfValid);
    thankYou.change(checkIfValid);

    //Updates the default informations
    function updateDefaultInformations() {
        var type = Survey.typeId();
        var lang = Survey.lang();

        if (type != -1 && lang != "") {
            var texts = defaultInformations[lang][type];
            description.val(texts[0]);

            if (Survey.isIndividual() || Survey.isProgress()) {
                individualInviteText.val(texts[1]);
                thankYou.val(texts[2]);
                questionInfo.val(texts[3]);
            } else {
                thankYou.val(texts[1]);
                questionInfo.val(texts[2]);
            }
        }
    }

    api.updateDefaultInformations = function () {
        updateDefaultInformations();
    };

    Survey.onChangeLang(function() {
        updateDefaultInformations();
    });

    Survey.onChangeType(function() {
        updateDefaultInformations();
    });

    api.save = function (data) {
        data.startDate = startDate.val();
        data.endDate = endDate.val();
        data.description = description.val();
        data.individualInviteText = individualInviteText.val();
        data.thankYou = thankYou.val();
        data.questionInfo = questionInfo.val();
    };

    api.load = function (data) {
        startDate.val(data.startDate);
        endDate.val(data.endDate);
        description.val(data.description);
        individualInviteText.val(data.individualInviteText);
        thankYou.val(data.thankYou);
        questionInfo.val(data.questionInfo);

        checkIfValid();
    };

    return api;
})();

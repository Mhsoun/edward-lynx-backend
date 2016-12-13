var SurveyStep5 = (function() {
    var api = {};
    var defaultEmails = {};

    var step5NextBtn = $("#step5NextBtn");
    var toEvaluateEmailBox = $("#toEvaluateEmailBox");
    var toEvaluateTeamEmailBox = $("#toEvaluateTeamBox");
    var candidateAnswerEmailBox = $("#candidateAnswerEmailBox");
    var userReportEmailBox = $("#userReportEmailBox");
    var inviteOthersReminderEmailBox = $("#inviteOthersReminderEmailBox");

    //Switches to the individual view
    api.switchToIndividual = function () {
        toEvaluateEmailBox.show();
        toEvaluateTeamEmailBox.hide();
        inviteOthersReminderEmailBox.show();

        if (Survey.isProgress()) {
            candidateAnswerEmailBox.hide();
            userReportEmailBox.show();
        } else if (Survey.isIndividual()) {
            candidateAnswerEmailBox.show();
            userReportEmailBox.hide();
        }
    }

    //Switches to the group view
    api.switchToGroup = function () {
        toEvaluateEmailBox.hide();
        toEvaluateTeamEmailBox.show();
        inviteOthersReminderEmailBox.hide();
    }

    //Switches to the normal view
    api.switchToNormal = function() {
        toEvaluateEmailBox.hide();
        toEvaluateTeamEmailBox.hide();
        inviteOthersReminderEmailBox.hide();
    }

    //Sets the default emails
    api.setDefaultEmails = function (newDefaultEmails) {
        defaultEmails = newDefaultEmails;
    }

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

        if (Survey.isIndividual()) {
            checkEmpty($("#toEvaluateInvitationSubject"));
            checkEmpty($("#toEvaluateInvitationText"));

            checkEmpty($("#candidateInvitationSubject"));
            checkEmpty($("#candidateInvitationText"));

            checkEmpty($("#inviteOthersReminderSubject"));
            checkEmpty($("#inviteOthersReminderText"));
        }

        if (Survey.isProgress()) {
            checkEmpty($("#toEvaluateInvitationSubject"));
            checkEmpty($("#toEvaluateInvitationText"));

            checkEmpty($("#userReportSubject"));
            checkEmpty($("#userReportText"));

            checkEmpty($("#inviteOthersReminderSubject"));
            checkEmpty($("#inviteOthersReminderText"));
        }

        if (Survey.isGroup() || Survey.isLTT()) {
            checkEmpty($("#toEvaluateTeamInvitationSubject"));
            checkEmpty($("#toEvaluateTeamInvitationText"));
        }

        checkEmpty($("#invitationSubject"));
        checkEmpty($("#invitationText"));
        checkEmpty($("#reminderSubject"));
        checkEmpty($("#reminderText"));

        step5NextBtn.prop("disabled", !isValid);
    }

    $(document).ready(function() {
        checkIfValid();
    });

    $("#toEvaluateInvitationSubject").change(checkIfValid);
    $("#toEvaluateInvitationText").change(checkIfValid);

    $("#candidateInvitationSubject").change(checkIfValid);
    $("#candidateInvitationText").change(checkIfValid);

    $("#userReportSubject").change(checkIfValid);
    $("#userReportText").change(checkIfValid);

    $("#toEvaluateTeamInvitationSubject").change(checkIfValid);
    $("#toEvaluateTeamInvitationText").change(checkIfValid);

    $("#invitationSubject").change(checkIfValid);
    $("#invitationText").change(checkIfValid);

    $("#reminderSubject").change(checkIfValid);
    $("#reminderText").change(checkIfValid);

    $("#inviteOthersReminderSubject").change(checkIfValid);
    $("#inviteOthersReminderText").change(checkIfValid);

    //Updates the default emails
    function updateDefaultEmails() {
        var type = Survey.typeId();
        var lang = Survey.lang();

        if (type != -1 && lang != "") {
            var emails = defaultEmails[lang][type];
            $("#invitationSubject").val(emails[0].subject);
            $("#invitationText").val(emails[0].message);

            $("#reminderSubject").val(emails[1].subject);
            $("#reminderText").val(emails[1].message);

            if (Survey.isIndividual()) {
                $("#toEvaluateInvitationSubject").val(emails[2].subject);
                $("#toEvaluateInvitationText").val(emails[2].message);

                $("#candidateInvitationSubject").val(emails[3].subject);
                $("#candidateInvitationText").val(emails[3].message);

                $("#inviteOthersReminderSubject").val(emails[4].subject);
                $("#inviteOthersReminderText").val(emails[4].message);
            }

            if (Survey.isProgress()) {
                $("#toEvaluateInvitationSubject").val(emails[2].subject);
                $("#toEvaluateInvitationText").val(emails[2].message);

                $("#userReportSubject").val(emails[3].subject);
                $("#userReportText").val(emails[3].message);

                $("#inviteOthersReminderSubject").val(emails[4].subject);
                $("#inviteOthersReminderText").val(emails[4].message);
            }

            if (Survey.isGroup() || Survey.isLTT()) {
                $("#toEvaluateTeamInvitationSubject").val(emails[2].subject);
                $("#toEvaluateTeamInvitationText").val(emails[2].message);
            }
        }
    }

    api.updateDefaultEmails = function () {
        updateDefaultEmails();
        checkIfValid();
    };

    Survey.onChangeLang(function() {
        updateDefaultEmails();
        checkIfValid();
    });

    Survey.onChangeType(function() {
        updateDefaultEmails();
        checkIfValid();
    });

    api.save = function (data) {
        data.invitationSubject = $("#invitationSubject").val();
        data.invitationText = $("#invitationText").val();
        data.reminderSubject = $("#reminderSubject").val();
        data.reminderText = $("#reminderText").val();
        data.candidateInvitationSubject = $("#candidateInvitationSubject").val();
        data.candidateInvitationText = $("#candidateInvitationText").val();
        data.userReportSubject = $("#userReportSubject").val();
        data.userReportText = $("#userReportText").val();
        data.toEvaluateInvitationSubject = $("#toEvaluateInvitationSubject").val();
        data.toEvaluateInvitationText = $("#toEvaluateInvitationText").val();
        data.toEvaluateTeamInvitationSubject = $("#toEvaluateTeamInvitationSubject").val();
        data.toEvaluateTeamInvitationText = $("#toEvaluateTeamInvitationText").val();
        data.inviteOthersReminderSubject = $("#inviteOthersReminderSubject").val();
        data.inviteOthersReminderText = $("#inviteOthersReminderText").val();
    };

    api.load = function (data) {
        $("#invitationSubject").val(data.invitationSubject);
        $("#invitationText").val(data.invitationText);
        $("#reminderSubject").val(data.reminderSubject);
        $("#reminderText").val(data.reminderText);
        $("#candidateInvitationSubject").val(data.candidateInvitationSubject);
        $("#candidateInvitationText").val(data.candidateInvitationText);
        $("#userReportSubject").val(data.userReportSubject);
        $("#userReportText").val(data.userReportText);
        $("#toEvaluateInvitationSubject").val(data.toEvaluateInvitationSubject);
        $("#toEvaluateInvitationText").val(data.toEvaluateInvitationText);
        $("#toEvaluateTeamInvitationSubject").val(data.toEvaluateTeamInvitationSubject);
        $("#toEvaluateTeamInvitationText").val(data.toEvaluateTeamInvitationText);
        $("#inviteOthersReminderSubject").val(data.inviteOthersReminderSubject);
        $("#inviteOthersReminderText").val(data.inviteOthersReminderText);
    };

    return api;
})();

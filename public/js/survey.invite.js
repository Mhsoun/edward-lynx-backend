var SurveyInvite = (function() {
	var api = {};
	api.languageStrings = {};
	api.roles = [];

	var surveyLink = "";
	var isAdmin = false;

	var inviteButton = $("#inviteButton");
	inviteButton.prop("disabled", true);

	//Sets the survey link
	api.setSurveyLink = function(newSurveyLink) {
		surveyLink = newSurveyLink;
	}

	api.setIsAdmin = function (newIsAdmin) {
		isAdmin = newIsAdmin;
	}

	//Returns the name of the given role
	function getRoleName(roleId) {
		for (var i in api.roles) {
			var role = api.roles[i];

			if (role.id == roleId) {
				return role.name;
			}
		}

		return "";
	}

	//Adds a new recipient
	function addRecipient(id, name, email, roleId) {
		var invitedPersonRow = jQuery("<tr class='recipientsRow' />")

		if (isAdmin) {
			invitedPersonRow.append(jQuery("<td />"));
		}

		invitedPersonRow.append(jQuery("<td />").text(name));
		invitedPersonRow.append(jQuery("<td />").text(email));
		invitedPersonRow.append(jQuery("<td />").text(getRoleName(roleId)));

		if (isAdmin) {
			var deleteButton = jQuery("<button class='btn btn-danger btn-xs'><span class='glyphicon glyphicon-trash'></span></button>")
			deleteButton.click(function() {
				deleteRecipientOnServer(id, function() {
					invitedPersonRow.remove();
				})
			});
			invitedPersonRow.append(jQuery("<td />").append(deleteButton));
		}
	
		$("#invitedList").append(invitedPersonRow);
	}

	//Adds a new recipient to the list of recipients
	api.addNew = function() {
		var recipientName = $("#recipientName");
		var recipientEmail = $("#recipientEmail");
		var recipientRole = $("#recipientRole");

		if (recipientName.val() != "" && recipientEmail.val() != "" && recipientRole.val() != "") {
			$.ajax({
				url: "/survey-invite/" + surveyLink + "/recipient",
				method: "post",
				data: {
					name: recipientName.val(),
					email: recipientEmail.val(),
					roleId: recipientRole.val()
				}
			}).done(function(data) {
				if (data.success) {
					addRecipient(data.id, recipientName.val(), recipientEmail.val(), recipientRole.val());
					recipientName.val("");
					recipientEmail.val("");
				} else {
					alert(data.message);
				}
			}).error(function(data) {
				alert(Helpers.flattenErrors(data.responseJSON));
			});
		}
	}

	//Deletes the recipient on the server
	function deleteRecipientOnServer(recipientId, doneFn) {
		$.ajax({
			url: "/survey-invite/" + surveyLink + "/recipient",
			method: "delete",
			data: {
				recipientId: recipientId
			}
		}).done(function(data) {
			if (data.success) {
				doneFn();
			}
		})
	}

	//Deletes the given recipient
	api.deleteRecipient = function(recipientId, recipientRow) {
		deleteRecipientOnServer(recipientId, function() {
			$(recipientRow).parent().parent().remove();
		})
	}
	return api;
})();

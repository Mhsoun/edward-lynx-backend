var SurveyStep6 = (function() {
    var api = {};

    var step6NextBtn = $("#step6NextBtn");
    step6NextBtn.prop("disabled", true);
    step6NextBtn.hide();

    var individualBox = $("#step3IndividualBox");
    var groupBox = $("#step3GroupBox");
    var normalBox = $("#step3NormalBox");
    var subcompanyBox = $("#step3Subcompany");

    api.existingRecipients = [];

    api.Individual = IndividualSurvey();
    api.Group = GroupSurvey();
    api.Normal = NormalSurvey();
    api.Subcompany = Subcompany();

    //Switches to the individual view
    api.switchToIndividual = function () {
        individualBox.show();
        groupBox.hide();
        normalBox.hide();
        subcompanyBox.hide();

        step6NextBtn.show();
        api.Individual.show();
    }

    //Switches to the group view
    api.switchToGroup = function () {
        individualBox.hide();
        normalBox.hide();

        step6NextBtn.hide();
        subcompanyBox.show();
    }

    //Switches to the normal view
    api.switchToNormal = function() {
        normalBox.show();
        groupBox.hide();
        individualBox.hide();
        subcompanyBox.hide();

        step6NextBtn.show();
    }

    //Adds an existing recipient
    api.addExistingRecipient = function(id, name, email, position) {
        api.existingRecipients[id] = {
            id: id,
            name: name,
            email: email,
            position: position
        };
    }

    api.load = function (data) {
        if (Survey.isIndividual()) {
            api.Individual.load(data);
        } else if (Survey.isGroup()) {
            api.Group.load(data);
        } else if (Survey.isNormal()) {
            api.Normal.load(data);
        }
    }

    api.save = function (data) {
        if (Survey.isIndividual()) {
            api.Individual.save(data);
        } else if (Survey.isGroup()) {
            api.Group.save(data);
        } else if (Survey.isNormal()) {
            api.Normal.save(data);
        }
    }

    return api;
})();

//Manages subcompanies
function Subcompany() {
    var api = {};
    api.selectedId = -1;

    //Selects the subcompany
    function selectSubcompany() {
        SurveyStep6.Group.show();
        $("#step3Subcompany").hide();
    }

    //Selects from an existing subcompany
    api.select = function() {
        var subcompany = $("#selectSubcompany").val();

        if (subcompany != "all") {
            api.selectedId = +subcompany;
        }

        selectSubcompany();
    }

    //Creates a new subcompany
    api.create = function() {
        var newSubcompanyName = $("#newSubcompanyName");

        if (newSubcompanyName.val() != "") {
            $.ajax({
                url: "/subcompany",
                method: "post",
                data: {
                    name: newSubcompanyName.val(),
                    companyId: Survey.companyId()
                }
            }).done(function(data) {
                if (data.success) {
                    newSubcompanyName.val("");
                    selectSubcompany();
                }
            })
        }
    }

    return api;
}

//The individual type
function IndividualSurvey() {
    var api = {};

    var existingRecipients = $("#existingRecipients");
    var addedExisting = false;

    var step6NextBtn = $("#step6NextBtn");
    step6NextBtn.prop("disabled", true);

    var newRecipientName = $("#newRecipientName");
    var newRecipientEmail = $("#newRecipientEmail");
    var newRecipientPosition = $("#newRecipientPosition");
    var newRecipientEndDate = $("#newRecipientEndDate");
    var newRecipientEndDateRecipients = $("#newRecipientEndDateRecipients");

    var existingRecipientEndDate = $("#existingRecipientEndDate");
    var existingRecipientEndDateRecipients = $("#existingRecipientEndDateRecipients");

    var candidatesTable = $("#candidatesTable");

    var newRecipientEndDateBox = $("#newRecipientEndDateBox");
    var existingRecipientEndDateBox = $("#existingRecipientEndDateBox");
    var endDateColumn = $("#endDateColumn");
    var endDateRecipientsColumn = $("#endDateRecipientsColumn");

    //Creates a recipient text box
    function createRecipientTextBox(boxName, boxValue, labelText, placeholderText, inputType) {
        inputType = inputType || "text";

        var container = jQuery("<div class='form-group' />");
        container.css("margin-right", "10px");

        var label = jQuery("<label />");
        label.attr("for", boxName);
        label.text(labelText);

        var textBox = jQuery("<input class='form-control'>")
            .attr("type", inputType);

        textBox.attr("name", boxName);
        textBox.val(boxValue);
        textBox.attr("placeholder", placeholderText);

        container.append(label);
        container.append(textBox);

        return container;
    }

    //Returns the end date
    function defaultEndDate() {
        return $("#endDate").val();
    }

    //Adds an existing recipient option
    function addExistingRecipientOption(id, name, email, position) {
        var recipientOption = jQuery("<option />");
        recipientOption.val(id);
        recipientOption.data("email", email);
        recipientOption.text(name + " (" + email + ")");
        existingRecipients.append(recipientOption);
    }

    //Adds a new recipient
    function addRecipient(name, email, position, endDate, endDateRecipients, id) {
        newRecipientName.val("");
        newRecipientEmail.val("");
        newRecipientPosition.val("");

        var candidateRow = jQuery("<tr class='candidateRow' />");
        candidateRow.append(jQuery("<td />").text(name));
        candidateRow.append(jQuery("<td />").text(email));
        candidateRow.append(jQuery("<td />").text(position));

        if (Survey.isProgress()) {
            candidateRow.append(jQuery("<td />").text(endDate));
            candidateRow.append(jQuery("<td />").text(endDateRecipients));
        }

        var deleteButton = jQuery("<a class='textButton'><span class='glyphicon glyphicon-trash' /></a>");
        deleteButton.click(function() {
            candidateRow.remove();
            checkIfCanContinue();

            if (id != undefined) {
                addExistingRecipientOption(id, name, email, position);
            }
        });
        candidateRow.append(jQuery("<td />").append(deleteButton));

        candidateRow.append(jQuery("<input type='hidden' name='candidateIds[]'>").val(id));
        candidateRow.append(jQuery("<input type='hidden' name='candidateNames[]'>").val(name));
        candidateRow.append(jQuery("<input type='hidden' name='candidateEmails[]'>").val(email));
        candidateRow.append(jQuery("<input type='hidden' name='candidatePositions[]'>").val(position));

        if (Survey.isProgress()) {
            candidateRow.append(jQuery("<input type='hidden' name='candidateEndDates[]'>").val(endDate));
            candidateRow.append(jQuery("<input type='hidden' name='candidateEndDatesRecipients[]'>").val(endDateRecipients));
        }

        candidatesTable.append(candidateRow);
        checkIfCanContinue();
    }

    //Shows the view
    api.show = function() {
        if (Survey.isProgress()) {
            newRecipientEndDateBox.show();
            existingRecipientEndDateBox.show();
            endDateColumn.show();
            endDateRecipientsColumn.show();
        } else {
            newRecipientEndDateBox.hide();
            existingRecipientEndDateBox.hide();
            endDateColumn.hide();
            endDateRecipientsColumn.hide();
        }

        $("#candidatesTable > tbody").html("");
    }

    //Adds an existing recipient to the list of recipients
    api.addExisting = function() {
        var existingRecipientId = existingRecipients.val();

        if (existingRecipientId == null) {
            return;
        }

        var existingRecipient = SurveyStep6.existingRecipients[existingRecipientId];
        var endDate = existingRecipientEndDate.val();
        var endDateRecipients = existingRecipientEndDateRecipients.val();

        if (!Survey.isProgress() || (Survey.isProgress() && endDate != "" && endDateRecipients != "")) {
            if (!hasAddedEmail(existingRecipient.email)) {
                addRecipient(
                    existingRecipient.name,
                    existingRecipient.email,
                    existingRecipient.position,
                    endDate,
                    endDateRecipients,
                    existingRecipient.id);

                existingRecipients.find("[value=\"" + existingRecipient.id + "\"]").remove();
            } else {
                alert(Survey.languageStrings["surveys.alreadyInvited"]);
            }
        }
    }

    //Indicates if the given email has been added
    function hasAddedEmail(email) {
        var added = false;

        $("[name=\"candidateEmails[]\"]").each(function(i, element) {
            if ($(element).val() == email) {
                added = true;
            }
        });

        return added;
    }

    //Adds a new recipient to the list of recipients
    api.addNew = function() {
        var newName = newRecipientName.val();
        var newEmail = newRecipientEmail.val();
        var newPosition = newRecipientPosition.val();
        var newEndDate = newRecipientEndDate.val();
        var newEndDateRecipients = newRecipientEndDateRecipients.val();

        if (newName != "" && newEmail != ""
           && (!Survey.isProgress() || (Survey.isProgress() && newEndDate != "" && newRecipientEndDateRecipients != ""))) {
            if (!Helpers.isValidEmail(newEmail)) {
                alert(Survey.languageStrings["validation.email"]);
                return;
            }

            if (hasAddedEmail(newEmail)) {
                alert(Survey.languageStrings["surveys.alreadyInvited"]);
                return;
            }

            addRecipient(newName, newEmail, newPosition, newEndDate, newEndDateRecipients);
        }
    }

    //Returns the number of added candidates
    function numCandidates() {
        return candidatesTable.find(".candidateRow").length;
    }

    //Checks if the user can continue
    function checkIfCanContinue() {
        if (Survey.isIndividual() || Survey.isProgress()) {
            step6NextBtn.prop("disabled", numCandidates() == 0);
        }
    }

    //Import
    $("#importCandidateCSVButton").click(function() {
        var file = $("#importCandidateFile")[0].files[0];
        var reader = new FileReader();
        reader.readAsText(file, "UTF-8");

        reader.onload = function(e) {
            var result = e.target.result;
            $.ajax({
                url: "/survey/import-recipients",
                method: "post",
                data: { csv: result }
            }).done(function(data) {
                if (data.success) {
                    for (var i in data.imported) {
                        var candidate = data.imported[i];

                        if (hasAddedEmail(candidate.email)) {
                            continue;
                        }

                        addRecipient(candidate.name, candidate.email, candidate.position, defaultEndDate(), defaultEndDate());
                    }
                }
            });
        };
    });

    api.save = function (data) {
        data.individual = {};
        data.individual.recipients = [];
        var ids = $("[name=\"candidateIds[]\"]");

        for (var i = 0; i < ids.length; i++) {
            data.individual.recipients.push($(ids[i]).val());
        }
    }

    api.load = function (data) {
        if (data.individual != undefined) {
            data.individual.recipients.forEach(function (id) {
                var existingRecipient = SurveyStep6.existingRecipients[id];
                if (existingRecipient != undefined) {
                    addRecipient(
                        existingRecipient.name,
                        existingRecipient.email,
                        existingRecipient.position,
                        defaultEndDate(),
                        defaultEndDate(),
                        id);
                }
            });
        }
    }

    return api;
}

//The group type
function GroupSurvey() {
    var api = {};

    api.roles = [];

    var currentGroup = null;
    var toEvaluateRoleId = 0;
    var groups = {};

    var step6NextBtn = $("#step6NextBtn");

    var selectGroup = $("#selectGroup");

    var targetGroupBox = $("#targetGroupBox");

    var selectGroupStep = $("#selectGroupStep");
    var selectRecipientsStep = $("#selectRecipientsStep");
    var selectRolesStep = $("#selectRolesStep");

    var selectedGroupNameHeader = $("#selectedGroupNameHeader");

    var editGroupBox = $("#editGroupBox");
    var editGroupName = $("#editGroupName");

    var selectRecipientsTable = $("#selectRecipientsTable");
    var selectNewGroupMemberBox = $("#selectNewGroupMemberBox");
    var selectNewGroupMember = $("#selectNewGroupMember");

    var selectRolesButton = $("#selectRolesButton");
    var selectGroupRoleHeader = $("#selectGroupRoleHeader");
    var selectGroupRole = $("#selectGroupRole");
    var doneSelectingRolesButton = $("#doneSelectingRolesButton");
    var selectRoleGroupButton = $("#selectRoleGroupButton");

    var groupResults = $("#groupResults");

    //Displays the group type
    api.show = function() {
        $("#step3GroupBox").show();
        showSelectGroup();
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

    //Adds a new group
    api.add = function(id, name, members) {
        var newGroup = {
            id: id,
            name: name,
            members: members,
            subcompanyId: -1
        };

        groups[id] = newGroup;
        return newGroup;
    }

    //Adds a new group in a subcompany
    api.addInSubcompany = function(id, name, members, subcompanyId, subcompanyName) {
        var newGroup = {
            id: id,
            name: name,
            members: members,
            subcompanyId: subcompanyId,
            subcompanyName: subcompanyName
        };

        groups[id] = newGroup;
        return newGroup;
    }

    //Updates the selectable groups
    function updateSelectableGroups() {
        selectGroup.find("option").each(function(i, element) {
            element = $(element);

            if (element.val() != "noSelect") {
                element.remove();
            }
        });

        var selectedSubcompany = SurveyStep6.Subcompany.selectedId;

        for (var id in groups) {
            var group = groups[id];

            if (selectedSubcompany == -1 || selectedSubcompany == group.subcompanyId) {
                var displayName = group.name;

                if (selectedSubcompany == -1 && group.subcompanyId != -1) {
                    displayName = group.subcompanyName + " > " + group.name;
                }

                selectGroup.append(jQuery("<option />").val(group.id).text(displayName));
            }
        }
    }

    //Shows the select group step
    function showSelectGroup() {
        updateSelectableGroups();
        selectGroupStep.show();
        selectRecipientsStep.hide();
        selectRolesStep.hide();
        targetGroupBox.hide();
        groupResults.hide();
    }

    //Shows the select recipients step
    function showSelectRecipients() {
        selectGroupStep.hide();
        selectRecipientsStep.show();
        selectRolesStep.hide();
        groupResults.hide();

        selectedGroupNameHeader.text(currentGroup.name);
        targetGroupBox.show();
        editGroupBox.hide();

        if (currentGroup.isSubgroup) {
            editGroupName.val(currentGroup.subName);
        } else {
            editGroupName.val(currentGroup.name);
        }
    }

    //Shows the select roles step
    function showSelectRolesStep() {
        selectGroupStep.hide();
        selectRecipientsStep.hide();
        selectRolesStep.show();
        doneSelectingRolesButton.hide();
        targetGroupBox.hide();
        groupResults.hide();

        updateSelectableRoles();
        selectGroupRoleHeader.text(Survey.languageStrings["surveys.roleToEvaluate"]);
    }

    //Shows the results step
    function showResultsStep() {
        selectGroupStep.hide();
        selectRecipientsStep.hide();
        selectRolesStep.hide();
        groupResults.show();

        step6NextBtn.show();
        step6NextBtn.prop("disabled", false);

        showResults();
    }

    //Updates the selectable recipients
    function updateSelectableRecipients() {
        selectNewGroupMember.find("option").remove();
        var added = false;

        SurveyStep6.existingRecipients.forEach(function(recipient) {
            var isUsed = currentGroup.members.some(function(member) {
                return member.id == recipient.id;
            });

            if (!isUsed) {
                selectNewGroupMember.append(jQuery("<option />")
                    .val(recipient.id)
                    .text(recipient.name + " (" + recipient.email + ")"));

                added = true;
            }
        });

        if (added) {
            selectNewGroupMemberBox.show();
        } else {
            selectNewGroupMemberBox.hide();
        }
    }

    //Updates the selected group
    function updateSelectedGroup() {
        updateGroupRecipients();
        updateSelectableRecipients();
        showSelectRecipients();
        selectGroup.val("noSelect");
    }

    //Selects an existing group
    api.select = function() {
        var selectedGroup = selectGroup.val();

        if (selectedGroup != "noSelect") {
            currentGroup = groups[selectedGroup];
            updateSelectedGroup();
        }
    }

    //Creates a new group
    api.create = function() {
        var newGroupName = $("#newGroupName");
        var groupName = newGroupName.val();

        if (groupName != "") {
            var doneFn = function(data) {
                newGroupName.val("");
                updateSelectedGroup();
            };

            var selectedSubcompany = SurveyStep6.Subcompany.selectedId;

            $.ajax({
                url: "/group",
                method: "post",
                data: {
                    name: groupName,
                    companyId: Survey.companyId(),
                    subcompanyId: selectedSubcompany != -1 ? selectedSubcompany : null
                }
            }).done(function(data) {
                if (data.success) {
                    if (selectedSubcompany != -1) {
                        currentGroup = api.addInSubcompany(
                            data.id,
                            data.name,
                            [],
                            selectedSubcompany,
                            data.subcompanyName);
                    } else {
                        currentGroup = api.add(data.id, data.name, []);
                    }

                    doneFn(data);
                }
            });
        }
    }

    //Creates a member row
    function createMemberRow(member) {
        var memberRow = jQuery("<tr />").prop("id", "group_member_" + member.id);

        var includeCheckbox = jQuery("<input type='checkbox' name='includedGroupMembers[]' />")
            .prop("checked", true)
            .val(member.id);

        memberRow.append(jQuery("<td />").append(includeCheckbox));
        includeCheckbox.trigger("onchange");

        memberRow.append(jQuery("<td />").text(member.name));
        memberRow.append(jQuery("<td />").text(member.email));
        memberRow.append(jQuery("<td />").text(member.position));

        var roleCol = jQuery("<td />");
        var roleSelect = jQuery("<select class='roleSelect form-control' />")
            .css('width', 'auto');

        for (var i in api.roles) {
            var role = api.roles[i];
            var roleOption = jQuery("<option />")
                .val(role.id)
                .text(role.name);

            if (role.id == member.roleId) {
                roleOption.attr("selected", "selected");
            }

            roleSelect.append(roleOption);
        }

        roleSelect.change(function() {
            var newRoleId = +roleSelect.val();

            $.ajax({
                url: "/group/" + currentGroup.id + "/member",
                method: "put",
                data: {
                    memberId: member.id,
                    roleId: newRoleId
                }
            }).done(function(data) {
                if (data.success) {
                    member.roleId = newRoleId;
                }
            });
        });

        roleCol.append(roleSelect);
        memberRow.append(roleCol);

        var deleteButton = jQuery("<a class='textButton'><span class='glyphicon glyphicon-trash' /></a>");
        deleteButton.click(function() {
            deleteMember(member, memberRow);
        });

        memberRow.append(jQuery("<td />").append(deleteButton));

        return memberRow;
    }

    //Updates the group recipients
    function updateGroupRecipients() {
        selectRecipientsTable.find("tr").each(function(i, element) {
            element = $(element);
            if (!element.hasClass("tableHeader")) {
                element.remove();
            }
        });

        for (var i in currentGroup.members) {
            var member = currentGroup.members[i];
            selectRecipientsTable.append(createMemberRow(member));
        }
    }

    //Adds the given member to the given group
    function addMember(group, id, name, email, roleId, position) {
        var member = {
            id: id,
            name: name,
            email: email,
            roleId: roleId,
            position: position,
            included: true,
        };

        group.members.push(member);
        selectRecipientsTable.append(createMemberRow(member));
        updateSelectableRecipients();
    }

    //Deletes the given member from the current group
    function deleteMember(member, memberRow) {
        $.ajax({
            url: "/group/" + currentGroup.id + "/member",
            method: "delete",
            data: {
                memberId: member.id
            }
        }).done(function(data) {
            if (data.success) {
                currentGroup.members.splice(currentGroup.members.indexOf(member), 1);
                memberRow.remove();
                updateSelectableRecipients();
            }
        });
    }

    //Creates a new member from an existing recipient
    api.createMemberFromExisting = function() {
        var memberId = selectNewGroupMember.val();

        if (memberId != "") {
            $.ajax({
                url: "/group/" + currentGroup.id + "/member",
                method: "post",
                data: {
                    recipientId: memberId,
                    companyId: Survey.companyId
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    var recipient = SurveyStep6.existingRecipients[memberId];
                    addMember(
                        currentGroup,
                        memberId,
                        recipient.name,
                        recipient.email,
                        data.roleId,
                        recipient.position);
                }
            });
        }
    }

    //Creates a new member
    api.createMember = function() {
        var newGroupMemberName = $("#newGroupMemberName");
        var newGroupMemberEmail = $("#newGroupMemberEmail");
        var newGroupMemberPosition = $("#newGroupMemberPosition");

        var name = newGroupMemberName.val();
        var email = newGroupMemberEmail.val();
        var position = newGroupMemberPosition.val();

        if (name != "" && email != "") {
            $.ajax({
                url: "/group/" + currentGroup.id + "/member",
                method: "post",
                data: {
                    name: name,
                    email: email,
                    position: position,
                    companyId: Survey.companyId
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    addMember(
                        currentGroup,
                        data.memberId,
                        name,
                        email,
                        data.roleId,
                        position);

                    newGroupMemberName.val("");
                    newGroupMemberEmail.val("");
                    newGroupMemberPosition.val("");
                } else {
                    alert(data.message);
                }
            }).error(function(data) {
                alert(Helpers.flattenErrors(data.responseJSON));
            });
        }
    }

    //Updates UI for the name of the given group
    function updateGroupName(group) {
        selectedGroupNameHeader.text(group.name);

        selectGroup.find("option").each(function(i, element) {
            element = $(element);

            if (element.val() == group.id) {
                element.text(group.name);
            }
        });
    }

    //Saves the changes made to the current group
    api.saveGroup = function() {
        var newName = editGroupName.val();

        if (newName != "") {
            $.ajax({
                url: "/group/" + currentGroup.id,
                method: "put",
                data: {
                    name: newName
                }
            }).done(function(data) {
                if (data.success) {
                    currentGroup.name = newName;
                    updateGroupName(currentGroup);
                    editGroupBox.hide();
                }
            });
        }
    }

    //Restores the current group
    api.restore = function() {
        if (currentGroup.isSubgroup) {
            editGroupName.val(currentGroup.subName)
        } else {
            editGroupName.val(currentGroup.name)
        }

        editGroupBox.hide();
    }

    //Toggles the edit for a group
    api.toggleEdit = function() {
        editGroupBox.toggle();
    }

    //Deletes the current group
    api.delete = function() {
        if (currentGroup != null) {
            var confirmDeletion = confirm(Survey.languageStrings[!currentGroup.isSubgroup ?
                "groups.deleteGroupConfirmation" : "groups.deleteSubgroupConfirmation"] + "?");

            if (confirmDeletion) {
                $.ajax({
                    url: "/group/" + currentGroup.id + "/delete",
                    method: "delete",
                    data: {}
                }).done(function(data) {
                    if (data.success) {
                        selectGroup.find("option").each(function(i, element) {
                            element = $(element);

                            if (element.val() == currentGroup.id) {
                                element.remove();
                            }
                        })

                        delete groups[currentGroup.id];
                        currentGroup = null;
                        showSelectGroup();
                    }
                })
            }
        }
    }

    //Updates the included members
    function updateIncludedMembers() {
        $("input[name='includedGroupMembers[]']").each(function(i, element) {
            element = $(element);
            currentGroup.members[i].included = element.prop("checked");
        });
    }

    //Returns the number of used roles
    function numUsedRoles() {
        var num = 0;
        var roles = {};

        currentGroup.members.forEach(function(member) {
            if (member.included) {
                if (roles[member.roleId] == undefined) {
                    roles[member.roleId] = true;
                    num++;
                }
            }
        });

        return num;
    }

    //Shows the select roles
    api.selectRoles = function() {
        updateIncludedMembers();

        if (numUsedRoles() >= 2) {
            $("#newGroupMemberName").val("");
            $("#newGroupMemberEmail").val("");
            $("#newGroupMemberPosition").val("");

            showSelectRolesStep();
        } else {
            alert(Survey.languageStrings["surveys.rolesToContinueText"]);
        }
    }

    //Updates the selectable roles
    function updateSelectableRoles() {
        selectGroupRole.find("option").remove();
        var added = false;
        api.roles.forEach(function(role) {
            var anyMembers = currentGroup.members.some(function(member) {
                return member.included && member.roleId == role.id;
            });

            if (anyMembers) {
                added = true;
                selectGroupRole.append(jQuery("<option />").val(role.id).text(role.name));
            }
        });

        if (added) {
            selectGroupRole.show();
        } else {
            selectGroupRole.hide();
        }
    }

    //Returns the role groups
    function getRoleGroups() {
        var roleGroups = [];

        currentGroup.members.forEach(function(member) {
            var roleId = member.roleId;
            var roleGroup = roleGroups[roleId];

            if (roleGroup == undefined) {
                roleGroup = {
                    name: getRoleName(roleId),
                    id: roleId,
                    members: []
                };

                roleGroups[roleId] = roleGroup;
            }

            roleGroup.members.push(member);
        });

        return Helpers.values(roleGroups).sort(function(x, y) {
            if (x.id == toEvaluateRoleId && y.id != toEvaluateRoleId) {
                return -1;
            } else if (y.id == toEvaluateRoleId && x.id != toEvaluateRoleId) {
                return 1;
            }

            return x.name.localeCompare(y.name);
        });
    }

    //Shows the result
    function showResults() {
        var groupResultsContent = groupResults.find("#groupResultsContent");
        groupResultsContent.html("");

        var roleGroups = getRoleGroups();

        groupResultsContent.append(jQuery("<input type='hidden' name='targetGroupId' />").val(currentGroup.id));
        groupResultsContent.append(jQuery("<input type='hidden' name='toEvaluateRole' />").val(toEvaluateRoleId));

        for (var step in roleGroups) {
            var roleGroup = roleGroups[step];
            var anyIncluded = roleGroup.members.some(function(member) {
                return member.included;
            });

            if (anyIncluded) {
                var roleName = roleGroup.name.replace("&gt;", ">");

                if (step == 0) {
                    roleName += " (" + Survey.languageStrings["surveys.toEvaluate"] + ")";
                }

                groupResultsContent.append(jQuery("<h3>").text(roleName));

                //Create the table for the group
                var groupTable = jQuery("<table class='table'/>");
                var colGroup = jQuery("<colgroup />");
                colGroup.append(jQuery("<col style='width: 33%' />"));
                colGroup.append(jQuery("<col style='width: 33%' />"));
                colGroup.append(jQuery("<col style='width: 33%' />"));
                groupTable.append(colGroup);

                var headerRowHead = jQuery("<thead />");
                var headerRow = jQuery("<tr />");
                headerRowHead.append(headerRow);

                headerRow.append(jQuery("<th>" + Survey.languageStrings["recipientName"] + "</th>"));
                headerRow.append(jQuery("<th>" + Survey.languageStrings["recipientEmail"] + "</th>"));
                headerRow.append(jQuery("<th>" + Survey.languageStrings["recipientPosition"] + "</th>"));

                groupTable.append(headerRowHead);

                //Now the members
                roleGroup.members.forEach(function(member) {
                    if (member.included) {
                        var memberRow = jQuery("<tr />");
                        memberRow.append(jQuery("<td />").text(member.name));
                        memberRow.append(jQuery("<td />").text(member.email));
                        memberRow.append(jQuery("<td />").text(member.position));
                        memberRow.append(jQuery("<input type='hidden' name='includedMembers[]'>").val(member.id));
                        groupTable.append(memberRow);
                    }
                });

                groupResultsContent.append(groupTable);
            }
        }
    }

    //Selects a group role
    api.selectGroupRole = function() {
        toEvaluateRoleId = +selectGroupRole.val();
        showResultsStep();
    }

    //Marks that the user is done selecting roles
    api.doneSelecting = function() {
        showResultsStep();
    }

    //Reselects the target group
    api.reselectGroup = function() {
        currentGroup = null;
        toEvaluateRoleId = 0;
        showSelectGroup();
    }

    //Reselects the roles
    api.reselectRoles = function() {
        toEvaluateRoleId = 0;
        showSelectRecipients();
        step6NextBtn.hide();
    }

    //Selects all the members
    api.selectAllMembers = function() {
        Helpers.selectAllCheckboxes(selectRecipientsTable, "includedGroupMembers[]");
    }

    //Import
    $("#importCSVButton").click(function() {
        var file = $("#importFile")[0].files[0];
        var reader = new FileReader();
        reader.readAsText(file, "UTF-8");

        reader.onload = function(e) {
            var result = e.target.result;
            $.ajax({
                url: "/group/" + currentGroup.id + "/import-members",
                method: "post",
                data: { csv: result }
            }).done(function(data) {
                if (data.success) {
                    for (var i in data.created) {
                        var member = data.created[i];
                        addMember(
                            currentGroup,
                            member.memberId,
                            member.name,
                            member.email,
                            member.roleId,
                            member.position);
                    }
                }
            });
        };
    });

    api.save = function (data) {
        if (currentGroup != null) {
            updateIncludedMembers();

            data.group = {
                id: currentGroup.id,
                subcompanyId: SurveyStep6.Subcompany.selectedId,
                includedMembers: {}
            };

            currentGroup.members.forEach(function (member) {
                if (member.included) {
                    data.group.includedMembers[member.id] = true;
                }
            });
        }
    }

    api.load = function (data) {
        if (data.group != undefined) {
            if (groups[data.group.id] != undefined) {
                currentGroup = groups[data.group.id];
                SurveyStep6.Subcompany.selectedId = data.group.subcompanyId;

                $("#step3GroupBox").show();
                $("#step3Subcompany").hide();
                updateSelectedGroup();

                currentGroup.members.forEach(function (member) {
                    if (data.group.includedMembers[member.id]) {
                        member.included = true;
                    } else {
                        member.included = false;
                    }

                    $("#group_member_" + member.id)
                        .find("input[name='includedGroupMembers[]']")
                        .prop("checked", member.included);
                });
            }
        }
    }

    return api;
}

//The normal type
function NormalSurvey() {
    var api = {};

    var existingRecipients = $("#normalExistingRecipients");
    var addedExisting = false;

    var step6NextBtn = $("#step6NextBtn");
    step6NextBtn.prop("disabled", true);

    var newRecipientName = $("#newNormalRecipientName");
    var newRecipientEmail = $("#newNormalRecipientEmail");
    var newRecipientPosition = $("#newNormalRecipientPosition");

    var candidatesTable = $("#normalParticipantsTable");

    //Creates a recipient text box
    function createRecipientTextBox(boxName, boxValue, labelText, placeholderText, inputType) {
        inputType = inputType || "text";

        var container = jQuery("<div class='form-group' />");
        container.css("margin-right", "10px");

        var label = jQuery("<label />");
        label.attr("for", boxName);
        label.text(labelText);

        var textBox = jQuery("<input class='form-control'>")
            .attr("type", inputType);

        textBox.attr("name", boxName);
        textBox.val(boxValue);
        textBox.attr("placeholder", placeholderText);

        container.append(label);
        container.append(textBox);

        return container;
    }

    //Adds an existing recipient option
    function addExistingRecipientOption(id, name, email, position) {
        var recipientOption = jQuery("<option />");
        recipientOption.val(id);
        recipientOption.data("email", email);
        recipientOption.text(name + " (" + email + ")");
        existingRecipients.append(recipientOption);
    }

    //Adds a new recipient
    function addRecipient(name, email, position, id) {
        newRecipientName.val("");
        newRecipientEmail.val("");
        newRecipientPosition.val("");

        var candidateRow = jQuery("<tr class='candidateRow' />");
        candidateRow.append(jQuery("<td />").text(name));
        candidateRow.append(jQuery("<td />").text(email));

        var deleteButton = jQuery("<a class='textButton'><span class='glyphicon glyphicon-trash' /></a");
        deleteButton.click(function() {
            candidateRow.remove();
            checkIfCanContinue();

            if (id != undefined) {
                addExistingRecipientOption(id, name, email, position);
            }
        });
        candidateRow.append(jQuery("<td />").append(deleteButton));

        candidateRow.append(jQuery("<input type='hidden' name='normalParticipantIds[]'>").val(id));
        candidateRow.append(jQuery("<input type='hidden' name='normalParticipantNames[]'>").val(name));
        candidateRow.append(jQuery("<input type='hidden' name='normalParticipantEmails[]'>").val(email));
        candidatesTable.append(candidateRow);
        checkIfCanContinue();
    }

    //Adds an existing recipient to the list of recipients
    api.addExisting = function() {
        var existingRecipientId = existingRecipients.val();

        if (existingRecipientId == null) {
            return;
        }

        var existingRecipient = SurveyStep6.existingRecipients[existingRecipientId];

        if (!hasAddedEmail(existingRecipient.email)) {
            addRecipient(existingRecipient.name, existingRecipient.email, existingRecipient.position, existingRecipient.id);
            existingRecipients.find("[value=\"" + existingRecipient.id + "\"]").remove();
        } else {
            alert(Survey.languageStrings["surveys.alreadyInvited"]);
        }
    }

    //Indicates if the given email has been added
    function hasAddedEmail(email) {
        var added = false;

        $("[name=\"normalParticipantEmails[]\"]").each(function(i, element) {
            if ($(element).val() == email) {
                added = true;
            }
        });

        return added;
    }

    //Adds a new recipient to the list of recipients
    api.addNew = function() {
        var newName = newRecipientName.val();
        var newEmail = newRecipientEmail.val();
        var newPosition = newRecipientPosition.val();

        if (newName != "" && newEmail != "") {
            if (!Helpers.isValidEmail(newEmail)) {
                alert(Survey.languageStrings["validation.email"]);
                return;
            }

            if (hasAddedEmail(newEmail)) {
                alert(Survey.languageStrings["surveys.alreadyInvited"]);
                return;
            }

            addRecipient(newName, newEmail, newPosition);
            newRecipientName.val("");
            newRecipientEmail.val("");
            newRecipientPosition.val("");
        }
    }

    //Returns the number of added participants
    function numParticipants() {
        return candidatesTable.find(".candidateRow").length;
    }

    //Checks if the user can continue
    function checkIfCanContinue() {
        if (Survey.isNormal()) {
            step6NextBtn.prop("disabled", numParticipants() == 0);
        }
    }

    //Import
    $("#importParticipantsCSVButton").click(function() {
        var file = $("#importNormalParticipantsFile")[0].files[0];
        var reader = new FileReader();
        reader.readAsText(file, "UTF-8");

        reader.onload = function(e) {
            var result = e.target.result;
            $.ajax({
                url: "/survey/import-recipients",
                method: "post",
                data: {
                    csv: result,
                    ignorePosition: true
                }
            }).done(function(data) {
                if (data.success) {
                    for (var i in data.imported) {
                        var participant = data.imported[i];

                        if (hasAddedEmail(participant.email)) {
                            continue;
                        }

                        addRecipient(participant.name, participant.email, participant.position);
                    }
                }
            });
        };
    });

    api.save = function (data) {
        data.normal = {};
        data.normal.recipients = [];
        var ids = $("[name=\"normalParticipantIds[]\"]");

        for (var i = 0; i < ids.length; i++) {
            data.normal.recipients.push($(ids[i]).val());
        }
    }

    api.load = function (data) {
        if (data.normal != undefined) {
            data.normal.recipients.forEach(function (id) {
                var existingRecipient = SurveyStep6.existingRecipients[id];
                if (existingRecipient != undefined) {
                    addRecipient(
                        existingRecipient.name,
                        existingRecipient.email,
                        existingRecipient.position,
                        id);
                }
            });
        }
    }

    return api;
}

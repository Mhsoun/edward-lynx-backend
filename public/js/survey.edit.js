var EditSurvey = (function() {
    var api = {};

    var groupId = 0;
    var ownerId = 0;

    api.roles = [];
    api.existingRecipients = [];

    var allRecipients = [];

    var existingRecipientId = $("#existingRecipientId");

    api.setSurvey = function(getOwnerId, getGroupId) {
        ownerId = getOwnerId;
        groupId = getGroupId;
    }

    //Adds an existing recipient
    api.addExistingRecipient = function(recipient) {
        api.existingRecipients.push(recipient);
        allRecipients[recipient.id] = recipient;
    }

    //Adds a group member
    api.addGroupMember = function(recipient) {
        allRecipients[recipient.id] = recipient;
    }

    //Updates the role for a recipient
    api.updateRole = function(e, recipientId) {
        var newRoleId = +$(e).val();

        $.ajax({
            url: "/group/" + groupId + "/member",
            method: "put",
            data: {
                memberId: recipientId,
                roleId: newRoleId
            }
        });
    }

    //Updates the selectable members
    function updateSelectableMembers() {
        existingRecipientId.find("option").remove();

        api.existingRecipients.forEach(function(recipient) {
            var newOption = jQuery("<option />");
            newOption.val(recipient.id);
            newOption.text(recipient.name + " (" + recipient.email + ")");
            existingRecipientId.append(newOption);
        });
    }

    //Creates a member row
    function createMemberRow(member) {
        var memberRow = jQuery("<tr />");

        var includeCheckbox = jQuery("<input type='checkbox' name='newParticipants[]' />")
            .prop("checked", true)
            .val(member.id);

        memberRow.append(jQuery("<td />").append(includeCheckbox));
        includeCheckbox.trigger("onchange");

        memberRow.append(jQuery("<td />").text(member.name));
        memberRow.append(jQuery("<td />").text(member.email));
        memberRow.append(jQuery("<td />").text(member.position));

        var roleCol = jQuery("<td />");
        var roleSelect = jQuery("<select class='form-control' style='max-width: 60%' />");

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
                url: "/group/" + groupId + "/member",
                method: "put",
                data: {
                    memberId: member.id,
                    roleId: newRoleId
                }
            });
        });

        roleCol.append(roleSelect);
        memberRow.append(roleCol);

        var deleteButton = jQuery("<a class='textButton'><span class='glyphicon glyphicon-trash' /></a>");
        deleteButton.click(function() {
            api.deleteMember(member.id, memberRow);
        });

        memberRow.append(jQuery("<td />").append(deleteButton));

        $("#selectRecipientsTable").append(memberRow);
    }

    //Adds a new member
    function addMember(member) {
        var recipient = {
            id: member.id,
            name: member.name,
            email: member.email,
            position: member.position,
        };

        if (allRecipients[recipient.id] == undefined) {
            allRecipients[recipient.id] = recipient;
        }

        createMemberRow(member);
        updateSelectableMembers();
    }

    //Creates a new member from an existing recipient
    api.createMemberFromExisting = function() {
        var memberId = existingRecipientId.val();

        if (memberId != "") {
            $.ajax({
                url: "/group/" + groupId + "/member",
                method: "post",
                data: {
                    recipientId: memberId,
                    companyId: ownerId
                },
                dataType: "json" 
            }).done(function(data) {
                if (data.success) {
                    var index = Helpers.indexOf(api.existingRecipients, function(recipient) {
                        return recipient.id == data.memberId;
                    });

                    if (index != -1) {
                        api.existingRecipients.splice(index, 1);
                    }

                    addMember({
                        id: data.memberId,
                        name: data.name,
                        email: data.email,
                        roleId: data.roleId,
                        position: data.position
                    });
                }
            });
        }
    }

    //Creates a new member
    api.createMember = function() {
        var nameBox = $("#name");
        var emailBox = $("#email");
        var positionBox = $("#position");

        var name = nameBox.val();
        var email = emailBox.val();
        var position = positionBox.val();

        if (name != "" && email != "") {
            $.ajax({
                url: "/group/" + groupId + "/member",
                method: "post",
                data: {
                    name: name,
                    email: email,
                    position: position,
                    companyId: ownerId
                },
                dataType: "json" 
            }).done(function(data) {
                if (data.success) {
                    addMember({
                        id: data.memberId,
                        name: name,
                        email: email,
                        roleId: data.roleId,
                        position: position
                    });

                    nameBox.val("");
                    emailBox.val("");
                    positionBox.val("");
                } else {
                    alert(data.message);
                }
            }).error(function(data) {
                alert(Helpers.flattenErrors(data.responseJSON));
            });
        }
    }

    //Deletes the given member
    api.deleteMember = function(memberId, memberRow) {
        $.ajax({
            url: "/group/" + groupId + "/member",
            method: "delete",
            data: {
                memberId: memberId
            }
        }).done(function(data) {
            if (data.success) {
                memberRow.remove();
                api.existingRecipients.push(allRecipients[memberId]);
                updateSelectableMembers();
            }
        });
    }

    //Deletes the given member by its id
    api.deleteMemberById = function(memberId) {
        var memberRow = $("#member_" + memberId);
        api.deleteMember(memberId, memberRow);
    }

    //Selects all the members
    api.selectAllMembers = function() {
        Helpers.selectAllCheckboxes($("#selectRecipientsTable"), "newParticipants[]");
    }

    return api;
})();

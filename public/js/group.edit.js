//Toggles the existing recipients
function toggleExistingRecipients() {
    var existingRecipientsBox = $("#existingRecipientsBox");
    if ($("#newGroupMemberId").find("option").length > 0) {
        existingRecipientsBox.show();
    } else {
        existingRecipientsBox.hide();
    }
}

//Removes a member
function removeMember(groupId, memberId) {
    var name = $("#member_" + memberId).find(".name").text();
    var email = $("#member_" + memberId).find(".email").text();

    $.ajax({
        url: "/group/" + groupId + "/member",
        method: "delete",
        data: {
            memberId: memberId
        },
        dataType: "json" 
    }).done(function(data) {
        if (data.success) {
            $("#member_" + memberId).remove();
            $("#newGroupMemberId").append(jQuery("<option />").val(memberId).text(name + " (" + email + ")"));
            toggleExistingRecipients();
        }
    });
}

//Adds a new member
function addMember(groupId, name, email, memberId) {
    var memberRow = jQuery("<tr />").attr("id", "member_" + memberId);
    memberRow.append(jQuery("<td class='name' />").text(name));
    memberRow.append(jQuery("<td class='email' />").text(email));

    var deleteBtn = jQuery("<a class='textButton'><span class='glyphicon glyphicon-trash'></span></a>");
    deleteBtn.click(function() {
        removeMember(groupId, memberId);
    });

    memberRow.append(jQuery("<td />").append(deleteBtn));

    $("#recipientsTable").append(memberRow);
}

//Creates a new member
function createMember(groupId) {
    var newRecipientNameBox = $("#newRecipientName");
    var newRecipientEmail = $("#newRecipientEmail");

    var name = newRecipientNameBox.val();
    var email = newRecipientEmail.val();

    if (name != "" && email != "") {
        $.ajax({
            url: "/group/" + groupId + "/member",
            method: "post",
            data: {
                name: name,
                email: email
            },
            dataType: "json" 
        }).done(function(data) {
            if (data.success) {
                addMember(groupId, name, email, data.memberId);
            }
        });

        newRecipientNameBox.val("");
        newRecipientEmail.val("");
    }
}

//Creates a memeber from an existing recipient
function createMemberFromExisting(groupId) {
    var selectedRecipientBox = $("#newGroupMemberId");
    var selectedRecipientId = selectedRecipientBox.val();

    if (selectedRecipientId != "") {
        $.ajax({
            url: "/group/" + groupId + "/member",
            method: "post",
            data: {
                recipientId: selectedRecipientId
            },
            dataType: "json" 
        }).done(function(data) {
            if (data.success) {
                addMember(groupId, data.name, data.email, selectedRecipientId);
                $("option[value='" + selectedRecipientId + "']").remove();
                toggleExistingRecipients();
            }
        });
    }
}
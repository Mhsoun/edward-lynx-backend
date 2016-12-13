var SurveyStep3 = (function() {
    var api = {};

    var selectCategoryBox = $("#selectCategoryBox");
    var selectQuestionsBox = $("#selectQuestionsBox");
    var resultsBox = $("#resultsBox");
    var selectedQuestionsTable = $("#selectedQuestionsTable");
    var editCategoryBox = $("#editCategoryBox");
    var selectedCategoryTitle = $("#selectedCategoryTitle");
    var selectedCategoryDescription = $("#selectedCategoryDescription");
    var editCategoryTitle = $("#editCategoryTitle");
    var editCategoryDescription = $("#editCategoryDescription");
    var selectParentCategory = $("#selectParentCategory");

    var categories = {};
    var currentCategory = null;

    api.answerTypes = [];

    //Indicates if the given category is valid for the current state
    function isValidCategory(category) {
        return category.lang == Survey.lang()
            && (category.targetSurveyType == -1 || category.targetSurveyType == Survey.typeId());
    }

    //Updates the selectable categories
    function updateSelectableCategories() {
        var selectCategory = $("#selectCategory");
        selectCategory.find("option").remove();

        for (var id in categories) {
            var category = categories[id];
            if (isValidCategory(category)) {
                selectCategory.append(jQuery("<option />")
                    .text(category.title)
                    .val(id));
            }
        }
    }

    //Adds the given category to the list of categories
    api.addCategory = function(id, title, description, lang, targetSurveyType, questions, parentCategoryId) {
        categories[id] = {
            id: id,
            title: title,
            description: description,
            lang: lang,
            targetSurveyType: targetSurveyType,
            questions: questions,
            includedQuestions: [],
            parentCategoryId: parentCategoryId
        };
    }

    //Selects a category and switches to the questions view
    api.selectCategory = function() {
        var selectedCategoryId = $("#selectCategory").val();

        if (selectedCategoryId != null) {
            currentCategory = categories[selectedCategoryId];
            showSelectQuestions();
            updateSelectableParentCategories();
        }
    }

    //Creates a new category and switches to the question view
    api.createCategory = function() {
        var language = Survey.lang();

        var categoryTitleBox = $("#categoryTitle");
        var categoryDescriptionBox = $("#categoryDescription");

        var categoryTitle = categoryTitleBox.val();
        var categoryDescription = categoryDescriptionBox.val();

        var targetSurveyType = Survey.typeId();

        if (categoryTitle != "") {
            $.ajax({
                url: "/survey/question/category",
                method: "post",
                data: {
                    categoryTitle: categoryTitle,
                    categoryDescription: categoryDescription,
                    categoryLanguage: language,
                    companyId: Survey.companyId(),
                    targetSurveyType: targetSurveyType
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    var categoryId = data.id;

                    api.addCategory(categoryId, categoryTitle, categoryDescription, language, targetSurveyType, [], -1);
                    currentCategory = categories[categoryId];
                    updateSelectableCategories();
                    updateSelectableParentCategories();
                    showSelectQuestions();
                } else {
                    alert(data.message);
                }
            });
        }
    }

    //Deletes the selected category
    api.deleteCategory = function() {
        var confirmDeletion = confirm(Survey.languageStrings["questions.deleteCategoryConfirmationText"]);

        if (confirmDeletion) {
            $.ajax({
                url: "/survey/question/category",
                method: "delete",
                data: {
                    categoryId: currentCategory.id
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    delete categories[currentCategory.id];
                    currentCategory = null;
                    updateSelectableCategories();
                    updateSelectableParentCategories();
                    api.showSelectCategory();
                }
            });
        }
    }

    //Toggles the edit for the selected category
    api.toggleEditCategory = function() {
        editCategoryBox.toggle();
    }

    //Restores the selected category
    api.restoreCategory = function() {
        editCategoryTitle.val(currentCategory.title);
        editCategoryDescription.val(currentCategory.description);
        editCategoryBox.hide();
    }

    //Saves the chagnes to the selected category
    api.saveCategory = function() {
        var newTitle = editCategoryTitle.val();
        var newDescription = editCategoryDescription.val();

        if (newTitle != "") {
            $.ajax({
                url: "/survey/question/category",
                method: "put",
                data: {
                    categoryId: currentCategory.id,
                    title: newTitle,
                    description: newDescription
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    currentCategory.title = newTitle;
                    currentCategory.description = newDescription;
                    selectedCategoryTitle.text(currentCategory.title);
                    selectedCategoryDescription.text(currentCategory.description);
                    editCategoryBox.hide();
                }
            });
        }
    }

    //Updates the selectable parent categories
    function updateSelectableParentCategories() {
        selectParentCategory.find("option").remove();
        selectParentCategory.append(jQuery("<option />")
            .text(Survey.languageStrings["questions.noParentCategory"])
            .val(-1));

        for (var id in categories) {
            if (currentCategory == null || id != currentCategory.id) {
                var category = categories[id];
                if (isValidCategory(category)) {
                    var option = jQuery("<option />")
                        .text(category.title)
                        .val(id);

                    if (currentCategory != null && id == currentCategory.parentCategoryId) {
                        option.attr("selected", "selected");
                    }

                    selectParentCategory.append(option);
                }
            }
        }
    }

    selectParentCategory.change(function() {
        if (currentCategory != null) {
            var parentCategoryId = selectParentCategory.val();

            $.ajax({
                url: "/survey/question/category",
                method: "put",
                data: {
                    categoryId: currentCategory.id,
                    parentCategoryId: parentCategoryId
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    currentCategory.parentCategoryId = parentCategoryId;
                } else {
                    selectParentCategory.val(-1);
                }
            });
        }
    });

    //Creates a new question in the current category
    api.createQuestion = function() {
        var questionTextBox = $("#questionText");
        var tagsTextBox = $("#questionTags");
        var newText = questionTextBox.val();
        var newScale = $("#questionScale").val();
        var newOptional = $("#questionOptional").is(":checked");
        var newIsNA = $("#questionIsNA").is(":checked");
        var tags = tagsTextBox.val().split(";");
        var customValues = [];

        $("input[name='questionCustomValues[]']").each(function (i, element) {
            customValues.push($(element).val());
        });

        if (newText != "") {
            $.ajax({
                url: "/survey/question",
                method: "post",
                data: {
                    categoryId: currentCategory.id,
                    questionText: newText,
                    companyId: Survey.companyId(),
                    answerType: newScale,
                    optional: newOptional,
                    isNA: newIsNA,
                    tags: tags,
                    customValues: customValues
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    questionTextBox.val("");
                    tagsTextBox.val("");

                    var question = {
                        id: data.id,
                        text: newText,
                        answerType: data.answerType,
                        optional: newOptional,
                        isNA: newIsNA,
                        tags: tags
                    };

                    //Add the question
                    currentCategory.questions.push(question);
                    currentCategory.includedQuestions.push(question.id);
                    clearCustomValues();

                    //Update the UI
                    addQuestionRow(selectedQuestionsTable, question, true);
                }
            });
        }
    }

    //Saves the changes to the given question
    function saveQuestion(questionElement, questionId, toggleEditQuestion) {
        var textBox = questionElement.find(".textBox");
        var newText = questionElement.find(".editBox").find(".editQuestionText").val();

        var tagsList = questionElement.find(".tagsList");
        var newTags = questionElement.find(".editBox").find(".editQuestionTags").val().split(";");

        if (newText != "") {
            $.ajax({
                url: "/survey/question",
                method: "put",
                data: {
                    questionId: questionId,
                    questionText: newText,
                    tags: newTags
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    var question = getQuestion(currentCategory, questionId);
                    question.text = newText;
                    question.tags = newTags;

                    toggleEditQuestion();
                    textBox.text(newText);
                    tagsList.text(newTags);
                }
            });
        }
    }

    //Selects all the questions
    api.selectAllQuestions = function() {
        Helpers.selectAllCheckboxes(selectedQuestionsTable, "selectedQuestions[]");
    }

    //Restores the given question
    function restoreQuestion(questionElement, toggleEditQuestion) {
        var textBox = questionElement.find(".textBox");
        var editTextArea = questionElement.find(".editBox").find(".editQuestionText");

        var tagsList = questionElement.find(".tagsList");
        var editTagsList = questionElement.find(".editBox").find(".editQuestionTags");

        editTextArea.val(textBox.text());
        editTagsList.val(tagsList.text());
        toggleEditQuestion();
    }

    //Updates the scale for the given question
    function updateQuestionScale(question, element) {
        var answerType = element.value;
        question.answerType = answerType;

        $.ajax({
            url: "/survey/question/",
            method: "put",
            data: {
                questionId: question.id,
                answerType: answerType
            },
            dataType: "json"
        });
    }

    //Updates the optional for the given question
    function updateQuestionOptional(question, value) {
        question.optional = value;

        $.ajax({
            url: "/survey/question/",
            method: "put",
            data: {
                questionId: question.id,
                optional: value
            },
            dataType: "json"
        });
    }

    //Updates the optional for the given question
    function updateQuestionIsNA(question, value) {
        question.isNA = value;

        $.ajax({
            url: "/survey/question/",
            method: "put",
            data: {
                questionId: question.id,
                isNA: value
            },
            dataType: "json"
        });
    }

    //Returns the target roles for the given question
    function getTargetRoles(question) {
        if (question.targetRoles === undefined) {
            question.targetRoles = SurveyStep6.Group.roles.map(function (role) {
                return role.id;
            });
        }

        return question.targetRoles;
    }

    //Adds the given question to the given table
    function addQuestionRow(categoryTable, question, include) {
        var questionRow = jQuery("<tr />");

        //Include checkbox
        var includeCheckbox = jQuery("<input type='checkbox' name='selectedQuestions[]'>").val(question.id);

        if (include || false) {
            includeCheckbox.prop("checked", true);
        }

        questionRow.append(jQuery("<td />").append(includeCheckbox));

        //Text
        var textCol = jQuery("<td />");

        //Create the text box
        var textBox = jQuery("<span class='textBox' />").text(question.text);

        //Create the edit question box
        var editTextBox = jQuery("<span class='editBox' style='display: none;'/>");
        editTextBox.append(jQuery("<label>" + Survey.languageStrings["surveys.questionText"] + "</label>"));
        editTextBox.append(jQuery("<br>"));
        editTextBox.append(jQuery("<textarea class='editQuestionText form-control' cols='50' rows='5' />").val(question.text));

        //Edit tag
        editTextBox.append(jQuery("<label>" + Survey.languageStrings["surveys.tags"] + "</label>"));
        editTextBox.append(jQuery("<br>"));
        editTextBox.append(jQuery("<input class='editQuestionTags form-control'/>").val(question.tags.join(";")));
        editTextBox.append(jQuery("<br>"));

        //Toggles the edit/view for a question
        function toggleEditQuestion() {
            if (editTextBox.is(":visible")) {
                editTextBox.hide();
                textBox.show();
            } else {
                editTextBox.show();
                textBox.hide();
            }
        }

        //Save button
        var saveButton = jQuery("<button type='button' class='btn btn-success'>" + Survey.languageStrings["buttons.save"] + "</button>");
        saveButton.click(function() {
            saveQuestion(questionRow, question.id, toggleEditQuestion);
        });
        editTextBox.append(saveButton);

        editTextBox.append(" ");

        //Discard button
        var discardButton = jQuery("<button type='button' class='btn btn-danger'>" + Survey.languageStrings["buttons.discardChanges"] + "</button>");
        discardButton.click(function() {
            restoreQuestion(questionRow, toggleEditQuestion);
        });
        editTextBox.append(discardButton);

        //Add the boxes to the column
        textCol.append(textBox);
        textCol.append(editTextBox);
        questionRow.append(textCol);

        //Tags list
        questionRow.append(jQuery("<td />").append(jQuery("<span class='tagsList' />").text(question.tags.join(";"))));

        //Scale selector
        var scaleCol = jQuery("<td />");
        var selectScale = jQuery("<select class='form-control' />")
        selectScale.change(function(element) {
            updateQuestionScale(question, element.target);
        });

        for (var i in api.answerTypes) {
            var answerType = api.answerTypes[i];
            var option = jQuery("<option />")
                .val(answerType.id)
                .text(answerType.descriptionText);

            if (answerType.id == question.answerType) {
                option.attr("selected", "selected");
            }

            selectScale.append(option);
        }

        scaleCol.append(selectScale);
        questionRow.append(scaleCol);

        //Optional checkbox
        var optionalCol = jQuery("<td />");

        var optionalCheckbox = jQuery("<input type='checkbox'>");
        optionalCheckbox.prop('checked', question.optional);
        optionalCol.append(optionalCheckbox);
        optionalCol.change(function() {
            updateQuestionOptional(question, optionalCheckbox.prop("checked"));
        });

        questionRow.append(optionalCol);

        //NA checkbox
        var naCol = jQuery("<td />");

        var naCheckbox = jQuery("<input type='checkbox'>");
        naCheckbox.prop('checked', question.isNA);
        naCol.append(naCheckbox);
        naCol.change(function() {
            updateQuestionIsNA(question, naCheckbox.prop("checked"));
        });

        questionRow.append(naCol);

        //Target group
        if (Survey.isGroup()) {
            var targetGroupCol = jQuery("<td />");

            var targetGroupDropdownMenu = jQuery(
                 "<ul class='nav nav-pills'>"
                +   "<li role='presentation' class='dropdown'>"
                +       "<a class='dropdown-toggle' data-toggle='dropdown' href='#' role='button' aria-haspopup='true' aria-expanded='false'>"
                +           Survey.languageStrings['buttons.select']
                +       "</a>"
                +       "<ul role='menu' class='dropdown-menu'></ul>"
                +   "</li>"
                + "</ul>")

            targetGroupDropdownMenu.on('click', '.dropdown-menu', function (e) {
                e.stopPropagation();
            });

            var targetGroupList = targetGroupDropdownMenu.find(".dropdown-menu");
            SurveyStep6.Group.roles.forEach(function (role) {
                var roleElement = jQuery("<li><a><input class='targetGroup' type='checkbox'><span class='lbl'> " + role.name + "</span></a></li>");
                var roleCheckbox = $(roleElement.find(".targetGroup"));
                roleCheckbox.prop("checked", getTargetRoles(question).some(function (roleId) {
                    return roleId === role.id;
                }));

                roleCheckbox.change(function (e) {
                    if (e.target.checked) {
                        getTargetRoles(question).push(role.id);
                    } else {
                        getTargetRoles(question).splice(getTargetRoles(question).indexOf(role.id), 1);
                    }
                });

                targetGroupList.append(roleElement);
            });

            targetGroupCol.append(targetGroupDropdownMenu);
            questionRow.append(targetGroupCol);
        }

        //Edit button
        var editButton = jQuery("<a class='textButton'><span class='glyphicon glyphicon-pencil'></span></a>");
        editButton.click(toggleEditQuestion);
        questionRow.append(jQuery("<td />").append(editButton));

        //Delete button
        var deleteButton = jQuery("<a class='textButton'><span class='glyphicon glyphicon-trash'></span></a>");
        deleteButton.click(function() {
            deleteQuestion(question.id, questionRow);
        });
        questionRow.append(jQuery("<td />").append(deleteButton));

        categoryTable.append(questionRow)
    }

    //Deletes the given question
    function deleteQuestion(questionId, questionRow) {
        var confirmDeletion = confirm(Survey.languageStrings["questions.deleteQuestionConfirmationText"]);

        if (confirmDeletion) {
            $.ajax({
                url: "/survey/question",
                method: "delete",
                data: {
                    questionId: questionId
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    if (questionRow != undefined) {
                        questionRow.remove();
                    }
                }
            });
        }
    }

    //Switches to the select questions view
    function showSelectQuestions()  {
        selectedCategoryTitle.text(currentCategory.title);
        selectedCategoryDescription.text(currentCategory.description);
        editCategoryTitle.val(currentCategory.title);
        editCategoryDescription.val(currentCategory.description);

        //Show the questions
        selectedQuestionsTable.find("tr").each(function(i, element) {
            element = $(element);
            if (!element.hasClass("tableHeader")) {
                element.remove();
            }
        });

        currentCategory.questions.forEach(function(question) {
            var isIncluded = questionIsIncluded(currentCategory, question.id);
            addQuestionRow(selectedQuestionsTable, question, isIncluded);
        });

        selectCategoryBox.hide();
        selectQuestionsBox.show();

        $("#step4InfoText").text(Survey.languageStrings["surveys.step4InfoTextCreateQuestions"]);
    }

    //Switches to group view
    api.switchToGroup = function() {
        $("#questionTargetGroupCol").show();
    }

    //Switches a none group view
    api.switchToNoneGroup = function() {
        $("#questionTargetGroupCol").hide();
    }

    //Updates the included questions for the current category
    function updateIncludedQuestions() {
        if (currentCategory != null) {
            currentCategory.includedQuestions = [];

            $("input[name='selectedQuestions[]']").each(function(i, element) {
                element = $(element);

                if (element.prop("checked")) {
                    currentCategory.includedQuestions.push(element.val());
                }
            });
        }
    }

    //Switches to the select category view
    api.showSelectCategory = function() {
        updateIncludedQuestions();
        selectCategoryBox.show();
        selectQuestionsBox.hide();
        resultsBox.hide();
        $("#step4InfoText").text(Survey.languageStrings["surveys.step4InfoTextCategory"]);
    }

    //Returns the given question in the given category
    function getQuestion(category, id) {
        for (var i in category.questions) {
            var question = category.questions[i];

            if (question.id == id) {
                return question;
            }
        }

        return null;
    }

    //Indicates if the given question is included in the given category
    function questionIsIncluded(category, questionId) {
        for (var i in category.questions) {
            if (category.includedQuestions[i] == questionId) {
                return true;
            }
        }

        return false;
    }

    //Returns the given answer type
    function getAnswerType(id) {
        for (var i in api.answerTypes) {
            var answerType = api.answerTypes[i];

            if (answerType.id == id) {
                return answerType;
            }
        }

        return null;
    }

    //Moves the given category
    function moveCategory() {
        var categoryElement = $(this).parents(".categoryContainer:first");

        if ($(this).is(".up")) {
            categoryElement.insertBefore(categoryElement.prev());
        } else {
            categoryElement.insertAfter(categoryElement.next());
        }
    }

    //Moves the given question
    function moveQuestion() {
        var questionRow = $(this).parents(".questionRow:first");

        if ($(this).is(".up")) {
            questionRow.insertBefore(questionRow.prev());
        } else {
            questionRow.insertAfter(questionRow.next());
        }
    }

    //Creates a move container
    function createMoveContainer(onMoveFn, marginLeft) {
        var container = jQuery("<span />");

        var moveUpButton = jQuery("<a class='textButton up'></a>");
        moveUpButton.append(
            jQuery("<span class='glyphicon glyphicon-menu-up' />")
                .css("font-size", "large")
                .css("float", "left")
                .css("margin-left", marginLeft || ""));

        container.append(moveUpButton);
        moveUpButton.click(onMoveFn);

        var moveDownButton = jQuery("<a class='textButton down'></a>");
        moveDownButton.append(
            jQuery("<span class='glyphicon glyphicon-menu-down' />")
                .css("font-size", "large")
                .css("float", "left")
                .css("margin-top", "12px")
                .css("margin-left", "-18px"));

        container.append(moveDownButton);
        moveDownButton.click(onMoveFn);

        return container;
    }

    //Create the results for the given category
    function createCategoryResults(category, resultsContainer) {
        if (category.includedQuestions.length > 0) {
            var categoryContainer = jQuery("<div class='categoryContainer' />");

            categoryContainer.append(createMoveContainer(moveCategory));

            //This sets the order of the category
            categoryContainer.append(jQuery("<input type='hidden' name='categories[]'>").val(category.id));

            categoryContainer.append(
                jQuery("<h3>")
                    .css("display", "inline")
                    .css('margin-left', '5px')
                    .text(category.title));

            var editButton = jQuery("<a class='textButton'><span class='glyphicon glyphicon-pencil'></span></a>")
                .css("margin-left", "5px");

            editButton.click(function() {
                currentCategory = category;
                showSelectQuestions();
                resultsBox.hide();
            });

            categoryContainer.append(editButton);

            //Create the table for the category
            var categoryTable = jQuery("<table class='table'/>");
            categoryTable.append(jQuery("<col />"));
            categoryTable.append(jQuery("<col width='55%' />"));
            categoryTable.append(jQuery("<col width='20%' />"));
            categoryTable.append(jQuery("<col width='20%' />"));
            categoryTable.append(jQuery("<col />"));

            var headerRowHead = jQuery("<thead />");
            var headerRow = jQuery("<tr />");
            headerRowHead.append(headerRow);

            headerRow.append(jQuery("<th>" + Survey.languageStrings["questions.questionOrder"] + "</th>"));
            headerRow.append(jQuery("<th>" + Survey.languageStrings["surveys.question"] + "</th>"));
            headerRow.append(jQuery("<th>" + Survey.languageStrings["surveys.tags"] + "</th>"));
            headerRow.append(jQuery("<th>" + Survey.languageStrings["questions.questionScale"] + "</th>"));
            headerRow.append(jQuery("<th>" + Survey.languageStrings["questions.optional"] + "</th>"));

            categoryTable.append(headerRowHead);

            //Now the questions
            category.includedQuestions.forEach(function (questionId) {
                var question = getQuestion(category, questionId);
                if (question != null) {
                    var questionRow = jQuery("<tr class='questionRow' />");
                    questionRow.append(createMoveContainer(moveQuestion, "15px"));
                    questionRow.append(jQuery("<td />").text(question.text));
                    questionRow.append(jQuery("<td />").text(question.tags.join(";")));
                    questionRow.append(jQuery("<td />").text(getAnswerType(question.answerType).descriptionText));
                    questionRow.append(jQuery("<td />").text(question.optional ? Survey.languageStrings['buttons.yes'] : Survey.languageStrings['buttons.no']));
                    questionRow.append(jQuery("<input type='hidden' name='questions[]'>").val(question.id));
                    questionRow.append(jQuery("<input type='hidden' name='questionTargetRoles[]'>").val(getTargetRoles(question).join(";")));
                    categoryTable.append(questionRow);
                }
            });

            categoryContainer.append(categoryTable);
            resultsContainer.append(categoryContainer);
        }
    }

    //Switches to the result view
    api.showResults = function() {
        updateIncludedQuestions();

        selectCategoryBox.hide();
        selectQuestionsBox.hide();
        resultsBox.show();

        //Create the results table
        var resultsContainer = $("#resultsContainer");
        resultsContainer.html("");

        for (var id in categories) {
            var category = categories[id];
            createCategoryResults(category, resultsContainer);
        }
    }

    Survey.onChangeLang(function(e) {
        updateSelectableCategories();
        api.showSelectCategory();

        if (currentCategory != null) {
            currentCategory.includedQuestions = [];
        }
    });

    Survey.onChangeType(function(e) {
        updateSelectableCategories();
        api.showSelectCategory();

        if (currentCategory != null) {
            currentCategory.includedQuestions = [];
        }
    });

    api.save = function (data) {
        data.categories = {};

        for (var id in categories) {
            data.categories[id] = {};
            data.categories[id].includedQuestions = categories[id].includedQuestions;
            data.categories[id].questionsTargetRolesIds = [];

            categories[id].questions.forEach(function (question) {
                if (question.targetRoles !== undefined) {
                    data.categories[id].questionsTargetRolesIds.push({
                        id: question.id,
                        targetRoles: question.targetRoles
                    });
                }
            });
        }
    };

    api.load = function (data) {
        updateSelectableCategories();

        var numQuestions = 0;

        for (var id in data.categories) {
            categories[id].includedQuestions = data.categories[id].includedQuestions;

            if (data.categories[id].questionsTargetRolesIds !== undefined) {
                data.categories[id].questionsTargetRolesIds.forEach(function (targetGroupQuestion) {
                    categories[id].questions.forEach(function (question) {
                        if (targetGroupQuestion.id == question.id) {
                            question.targetRoles = targetGroupQuestion.targetRoles;
                        }
                    });
                });
            }

            numQuestions += categories[id].includedQuestions.length;
        }

        if (numQuestions == 0) {
            api.showSelectCategory();
        } else {
            api.showResults();
        }
    };

    return api;
})();

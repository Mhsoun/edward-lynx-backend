var AnswerView = (function() {
    var api = {};
    var categories = [];
    var answerLink = "";
    var storeAnswer = true;

    var page = 1;
    var errorInfoBox = $("#errorInfoBox");

    var selectTopList = $("#selectTopList");
    var selectWorstList = $("#selectWorstList");

    var worstList = $("#worstList");
    var topList = $("#topList");

    //Handles errors
    function handleErrors() {
        var hasErrors = false;

        if (handleQuestionErrors()) {
            hasErrors = true;
        }

        if (handleExtraQuestionsErrors()) {
            hasErrors = true;
        }

        if (hasErrors) {
            errorInfoBox.show();
            $('html, body').animate({
                scrollTop: 0
            }, 200);
        } else {
            errorInfoBox.hide();
        }

        return hasErrors;
    }

    //Handles question errors
    function handleQuestionErrors() {
        var hasErrors = false;

        $("#page_" + page).find(".question").each(function(i, element) {
            element = $(element);
            if (element.hasClass("required")) {
                var error = element.find("input[type=radio]:checked").length == 0;

                if (element.hasClass('textQuestion')) {
                    var text = element.find(".textAnswer").val();
                    error = text == "" || !(/\S/.test(text));
                }

                if (element.hasClass('followUpQuestion') && !element.is(':visible')) {
                    error = false;
                }

                if (error) {
                    element.addClass('alert');
                    element.addClass('alert-danger');
                } else {
                    element.removeClass('alert');
                    element.removeClass('alert-danger');
                }

                if (error) {
                    hasErrors = true;
                }
            }
        });

        return hasErrors;
    }

    //Handles extra question errors
    function handleExtraQuestionsErrors() {
        var hasErrors = false;

        $("#page_" + page).find(".extraQuestion").each(function(i, element) {
            if ($(element).hasClass("required")) {
                var error = $(element).find("input").val() == "";

                if (error) {
                    element.setAttribute("class", "extraQuestion required alert alert-danger");
                } else {
                    element.setAttribute("class", "extraQuestion required");
                }

                if (error) {
                    hasErrors = true;
                }
            }
        });

        return hasErrors;
    }

    //Updates the selectable categories for the top/worst lists
    function updateTopWorstSelectable() {
        var selectable = [];

        //Indicates if the given category is used in the given list
        var isUsed = function(list, categoryId) {
            return $("input[name=" + list + "\\[\\]][value=" + categoryId + "]").length > 0;
        };
        categories.forEach(function(category) {
            if (!(isUsed("topList", category.id) || isUsed("worstList", category.id))) {
                selectable.push(category);
            }
        });

        var setSelectableList = function(list) {
            list.find("option").remove();
            selectable.forEach(function(category) {
                list.append(jQuery("<option />").val(category.id).text(category.name));
            });
        };

        setSelectableList(selectTopList);
        setSelectableList(selectWorstList);
    }

    $(document).ready(function() {
        updateTopWorstSelectable();
    });

    //Finds the category with the given id
    function findCategory(id) {
        for (var i in categories) {
            if (categories[i].id == id) {
                return categories[i];
            }
        }

        return null;
    }

    //Adds a select category for the given list
    function addSelectedCategory(selectList, list, listName) {
        if (list.find(".selectedCategory").length <= 2) {
            var selectedValue = selectList.val() || "";

            if (selectedValue != "") {
                var newListItem = jQuery("<div class='selectedCategory' style='max-width: 30%; margin-top: 5px' />");

                newListItem.append(jQuery("<input type='hidden' name='" + listName + "[]'>").val(selectedValue));
                newListItem.append(jQuery("<span />").text(findCategory(selectedValue).name));

                var deleteButton = jQuery("<a class='textButton' style='margin-right: 5px; float: right'><span class='glyphicon glyphicon-trash' /></a>");
                newListItem.append(deleteButton)
                deleteButton.click(function() {
                    newListItem.remove();
                    updateTopWorstSelectable();
                });

                list.append(newListItem);

                updateTopWorstSelectable();
            }
        }
    }

    $("#selectTopButton").click(function() {
        addSelectedCategory(selectTopList, topList, "topList");
    });

    $("#selectWorstButton").click(function() {
        addSelectedCategory(selectWorstList, worstList, "worstList");
    });

    //Adds the given category to the list of categories
    api.addCategory = function(category) {
        categories.push(category);
    }

    //Changes the page
    api.changePage = function(diff) {
        if (diff == -1 || !handleErrors()) {
            var oldPage = page;
            page += diff;
            $("#page_" + oldPage).hide();
            $("#page_" + page).show();

            $('html, body').animate({
                scrollTop: 0
            }, 200);
        }
    }

    $("#answerForm").submit(function() {
        if (!handleErrors()) {
            api.removeSavedAnswers();
            return true;
        }

        return false;
    });

    //Removes the saved answers
    api.removeSavedAnswers = function () {
        localStorage.removeItem("answers_" + answerLink);
        storeAnswer = false;
    };

    //Shows the given follow up questions
    api.showFollowUp = function (questionId, followUpQuestionId) {
        $("#question_" + followUpQuestionId).show();
    };

    //Hides the follow up questions
    api.hideFollowUp = function (questionId, followUpQuestions) {
        followUpQuestions.forEach(function (followUpQuestionId) {
            $("#question_" + followUpQuestionId).hide();
        });
    };

    //Sets the answer link
    api.setAnswerLink = function (newAnswerLink) {
        answerLink = newAnswerLink;
    };

    //Save/restore answers
    window.onbeforeunload = function() {
        if (storeAnswer) {
            var savedAnswers = {
                extraAnswers: [],
                answers: []
            };

            $(".question").each(function (i, questionElement) {
                questionElement = $(questionElement);
                var answer = {
                    id: questionElement.data("questionId"),
                    isText: questionElement.hasClass("textQuestion")
                };

                if (answer.isText) {
                    answer.value = questionElement.find(".textAnswer").val();
                } else {
                    answer.value = questionElement.find("input[type='radio']:checked").val() || null;
                }

                savedAnswers.answers.push(answer);
            });

            $(".extraAnswer").each(function (i, questionElement) {
                questionElement = $(questionElement);
                var extraAnswer = {
                    id: questionElement.data("questionId"),
                    isText: questionElement.hasClass("textValue"),
                    isDate: questionElement.hasClass("dateValue"),
                    isOptions: questionElement.hasClass("optionsValue"),
                    isHierarchy: questionElement.hasClass("hierarchyValue"),
                };

                extraAnswer.value = questionElement.val();
                if (extraAnswer.isHierarchy) {
                    extraAnswer.hierarchyValues = [];
                    $("#hierarchyBox_" + extraAnswer.id).find(".hierarchySelect").each(function (i, select) {
                        select = $(select);
                        extraAnswer.hierarchyValues.push({
                            id: select.data("parentId"),
                            value: select.val()
                        });
                    })
                }

                savedAnswers.extraAnswers.push(extraAnswer);
            });

            localStorage.setItem("answers_" + answerLink, JSON.stringify(savedAnswers));
        }
    }

    $(document).ready(function () {
        var savedAnswers = localStorage.getItem("answers_" + answerLink);

        if (savedAnswers != null) {
            var savedAnswers = JSON.parse(savedAnswers);

            savedAnswers.answers.forEach(function (answer) {
                var questionElement = $("#question_" + answer.id);
                if (questionElement.length > 0) {
                    if (answer.isText) {
                        if (questionElement.find(".textAnswer").val() == "") {
                            questionElement.find(".textAnswer").val(answer.value);
                        }
                    } else {
                        if (questionElement.find('input[type=radio]:checked').length == 0) {
                            questionElement.find('input[type=radio]').each(function (i, radioButton) {
                                radioButton = $(radioButton);
                                if (radioButton.val() == answer.value) {
                                    radioButton.prop("checked", true);
                                }
                            });
                        }
                    }
                }
            });

            savedAnswers.extraAnswers.forEach(function (answer) {
                var questionElement = $("[name=extraAnswer_" + answer.id + "]");
                if (questionElement.length > 0) {
                    questionElement.val(answer.value);
                }

                if (answer.isHierarchy) {
                    var hierarchyBox = $("#hierarchyBox_" + answer.id);
                    hierarchyBox.find(".hierarchySelect").val(answer.value);

                    answer.hierarchyValues.forEach(function (select) {
                        var children = hierarchyBox.find("#children_" + select.value);
                        if (select.value != "" && select.value != null) {
                            hierarchyBox.find("#select_" + select.id).val(select.value);
                            children.show();
                        }
                    });
                }
            });
        }
    });

    return api;
})();

<?php namespace App;

use Lang;
use App\AnswerType;
use App\SurveyTypes;

/**
* Contains functions for survey reports
*/
abstract class SurveyReportHelpers
{
    /**
    * Calculates the average for the given question
    */
    public static function calculateAverage($question)
    {
        $sum = 0;
        $count = 0;

        foreach ($question->answers as $answer) {
            $sum += $answer->value;
            $count++;
        }

        if ($count > 0) {
            $question->average = $sum / $count;
        } else {
            $question->average = 0;
        }
    }

    /**
    * Calculates the average for the given answers
    */
    public static function calculateAnswersAverage($answers)
    {
        $sum = 0;
        $count = 0;

        foreach ($answers as $answer) {
            $sum += $answer->value;
            $count++;
        }

        if ($count > 0) {
            return $sum / $count;
        } else {
            return 0;
        }
    }

    /**
    * Calculates the average for the given questions
    */
    public static function calculateQuestionsAverage($questions)
    {
        foreach ($questions as $question) {
            SurveyReportHelpers::calculateAverage($question);
        }

        return $questions;
    }

    /**
    * Calculates the average for the given categories
    */
    public static function calculateCategoriesAverage($categories)
    {
        foreach ($categories as $category) {
            $sum = 0;
            $count = 0;

            foreach ($category->questions as $question) {
                foreach ($question->answers as $answer) {
                    $sum += $answer->value;
                    $count++;
                }
            }

            if ($count > 0) {
                $category->average = $sum / $count;
            } else {
                $category->average = 0;
            }
        }
    }

    /**
    * Calcaulates the average for the given roles
    */
    public static function calculateRolesAverage($roles)
    {
        foreach ($roles as $role) {
            $sum = 0;
            $count = 0;

            foreach ($role->questions as $question) {
                foreach ($question->answers as $answer) {
                    $sum += $answer->value;
                    $count += 1;
                }
            }

            if ($count > 0) {
                $role->average = $sum / $count;
            } else {
                $role->average = 0;
            }
        }
    }

    /**
    * Sorts the given questions by title
    */
    public static function sortQuestionsByTitle(&$questions)
    {
        usort($questions, function ($x, $y) {
            if ($x->title > $y->title) {
                return 1;
            } else if ($x->title < $y->title) {
                return -1;
            } else {
                return 0;
            }
        });
    }

    /**
    * Sorts the given questions by their orders
    */
    public static function sortQuestionsByOrder(&$questions, $orders)
    {
        if (!is_array($questions)) {
            return;
        }

        usort($questions, function ($x, $y) use (&$orders) {
            $orderX = $orders[$x->id];
            $orderY = $orders[$y->id];

            if ($orderX > $orderY) {
                return 1;
            } else if ($orderX < $orderY) {
                return -1;
            } else {
                return 0;
            }
        });
    }

    /**
    * Determiens the role id of self
    */
    public static function getSelfRoleId($survey, $toEvaluate)
    {
        $selfRoleId = 0;

        if (SurveyTypes::isGroupLike($survey->type)) {
            $selfRoleId = $survey->toEvaluateRole()->id;
        } else if (SurveyTypes::isIndividualLike($survey->type)) {
            $selfRoleId = $toEvaluate != null ? \App\Roles::selfRoleId() : \App\Roles::candidatesRoleId();
        }

        return $selfRoleId;
    }

    /**
    * Returns the name of self
    */
    public static function getSelfRoleName($survey, $toEvaluate)
    {
        if (SurveyTypes::isGroupLike($survey)) {
            return $survey->toEvaluateRole()->name;
        } else if (SurveyTypes::isIndividualLike($survey)) {
            return $toEvaluate != null ? Lang::get('roles.self') : Lang::get('roles.candidates');
        } else {
            return '';
        }
    }

    /**
    * Merges the given question roles
    */
    public static function mergeQuestionRoles($roles, $newId, $newName)
    {
        $questions = [];
        foreach ($roles as $role) {
            foreach ($role->questions as $question) {
                $newQuestion = null;

                if (!array_key_exists($question->id, $questions)) {
                    $newQuestion = clone $question;
                    $newQuestion->answers = [];
                    $newQuestion->naAnswers = [];
                    unset($newQuestion->average);
                    $questions[$question->id] = $newQuestion;
                } else {
                    $newQuestion = $questions[$question->id];
                }

                foreach ($question->answers as $answer) {
                    array_push($newQuestion->answers, $answer);
                }

                foreach ($question->naAnswers as $answer) {
                    array_push($newQuestion->naAnswers, $answer);
                }
            }
        }

        return (object)[
            'id' => $newId,
            'name' => $newName,
            'questions' => array_values($questions)
        ];
    }

    //Converts from roles -> questions to questions -> roles
    public static function fromRolesToQuestions($roles)
    {
        $multipleDataPerQuestion = false;
        if (isset($roles[count($roles) - 1]->questions[0]->data)) {
            $multipleDataPerQuestion = true;
        }

        $questionsRoles = [];
        foreach ($roles as $role) {
            foreach ($role->questions as $question) {
                $newQuestion = null;

                if (!array_key_exists($question->id, $questionsRoles)) {
                    $newQuestion = clone $question;
                    $newQuestion->roles = [];

                    if ($multipleDataPerQuestion) {
                        unset($newQuestion->data);
                    } else {
                        unset($newQuestion->average);
                        unset($newQuestion->answers);
                        unset($newQuestion->naAnswers);
                    }

                    $questionsRoles[$question->id] = $newQuestion;
                } else {
                    $newQuestion = $questionsRoles[$question->id];
                }

                if ($multipleDataPerQuestion) {
                    array_push($newQuestion->roles, (object)[
                        'id' => $role->id,
                        'name' => $role->name,
                        'data' => $question->data,
                    ]);
                } else {
                    array_push($newQuestion->roles, (object)[
                        'id' => $role->id,
                        'name' => $role->name,
                        'average' => $question->average,
                    ]);
                }
            }
        }

        return array_values($questionsRoles);
    }

    /**
    * Returns the answers for questions for the given survey
    */
    public static function getQuestionAnswers($survey, $surveyRecipients, $isValidFn = null, $getAnswersFn = null, $mergeRoles = null)
    {
        $questions = [];
        $comments = [];

        //Create the mapping from (invited by, recipient id) -> role id
        $roleIds = [];
        foreach ($surveyRecipients as $recipient) {
            $roleIds[$recipient->invitedById . ':' . $recipient->recipientId] = $recipient->roleId;
        }

        if ($getAnswersFn == null) {
            $getAnswersFn = function ($survey, $questionId) {
                return $survey->answers()
                    ->where('questionId', '=', $questionId)
                    ->get();
            };
        }

        $answeredRecipientIds = [];

        //Get the answers
        foreach ($survey->questions as $surveyQuestion) {
            $question = $surveyQuestion->question;
            $questionAnswers = $getAnswersFn($survey, $question->id);

            //Get the answers for the question
            $answers = [];
            $answerType = $question->answerTypeObject();
            $naAnswers = [];

            $questionAnswers->each(function($answer) use ($answerType, &$roleIds, &$answers, &$naAnswers, &$isValidFn, &$answeredRecipientIds) {
                $valid = true;

                if ($isValidFn != null) {
                    $valid = $isValidFn($answer);
                }

                $recipientKey = $answer->invitedById . ':' . $answer->answeredById;

                if ($valid && array_key_exists($recipientKey, $roleIds)) {
                    $roleId = $roleIds[$answer->invitedById . ':' . $answer->answeredById];

                    $userAnswer = (object)[
                        'recipientId' => $answer->answeredById,
                        'invitedById' => $answer->invitedById,
                        'roleId' => $roleId,
                        'canBeCandidate' => ($answer->invitedById == $answer->answeredById) || ($answer->invitedById == 0)
                    ];

                    $isNA = false;

                    if (!array_key_exists($recipientKey, $answeredRecipientIds)) {
                        $answeredRecipientIds[$recipientKey] = (object)[
                            'recipientId' => $answer->answeredById,
                            'invitedById' => $answer->invitedById,
                            'roleId' => $roleId
                        ];
                    }

                    if (!$answerType->isText()) {
                        if ($answer->answerValue != AnswerType::NA_VALUE) {
                            $userAnswer->value = $answer->answerValue / $answerType->maxValue();
                        } else {
                            $isNA = true;
                        }
                    } else {
                        $userAnswer->text = $answer->answerText;
                    }

                    if (!$isNA) {
                        array_push($answers, $userAnswer);
                    } else {
                        array_push($naAnswers, $userAnswer);
                    }
                }
            });

            if (!$answerType->isText()) {
                array_push($questions, (object)[
                    'id' => $question->id,
                    'categoryId' => $question->category->id,
                    'category' => $question->category->title,
                    'title' => $question->text,
                    'answers' => $answers,
                    'naAnswers' => $naAnswers,
                    'answerType' => $answerType
                ]);
            } else {
                array_push($comments, (object)[
                    'id' => $question->id,
                    'categoryId' => $question->category->id,
                    'category' => $question->category->title,
                    'title' => $question->text,
                    'answers' => $answers,
                    'answerType' => $answerType
                ]);
            }
        }

        //Get the role names
        $roleNames = [];
        foreach ($answeredRecipientIds as $recipient) {
            if (!array_key_exists($recipient->roleId, $roleNames)) {
                $roleNames[$recipient->roleId] = \App\Roles::name($recipient->roleId);
            }
        }

        if (\App\SurveyTypes::isIndividualLike($survey->type)) {
            $candidatesRoleId = \App\Roles::candidatesRoleId();
            $roleNames[$candidatesRoleId] = \App\Roles::name($candidatesRoleId);
        }

        //Check if to merge roles
        if ($mergeRoles != null) {
            $mergedRoleData = $mergeRoles($roleNames, $answeredRecipientIds);
            $roleNames = $mergedRoleData->roleNames;
            $answeredRecipientIds = array_values($mergedRoleData->answeredRecipientIds);

            $updateRoleIds = function ($data, $recipientToRoleId) {
                foreach ($data as $question) {
                    foreach ($question->answers as $answer) {
                        $answer->roleId = $recipientToRoleId[$answer->invitedById . ':' . $answer->recipientId]->roleId;
                    }

                    if (isset($question->naAnswers)) {
                        foreach ($question->naAnswers as $answer) {
                            $answer->roleId = $recipientToRoleId[$answer->invitedById . ':' . $answer->recipientId]->roleId;
                        }
                    }
                }
            };

            //Update the role ids
            $updateRoleIds($questions, $mergedRoleData->answeredRecipientIds);
            $updateRoleIds($comments, $mergedRoleData->answeredRecipientIds);
        }

        return (object)[
            'questions' => $questions,
            'comments' => $comments,
            'answeredRecipientIds' => array_values($answeredRecipientIds),
            'roleNames' => $roleNames
        ];
    }

    /**
    * Groups the given questions by category
    */
    public static function groupQuestionsByCategory($questions, $categoryOrders = null, $calculateAverage = true)
    {
        $categories = [];

        foreach ($questions as $question) {
            //Create or get the category
            $category = null;

            if (!array_key_exists($question->categoryId, $categories)) {
                $category = (object)[
                    'id' => $question->categoryId,
                    'name' => $question->category,
                    'questions' => []
                ];

                $categories[$question->categoryId] = $category;
            } else {
                $category = $categories[$question->categoryId];
            }

            //Add the question to the category
            array_push($category->questions, $question);
        }

        //Calculate averages
        if ($calculateAverage) {
            SurveyReportHelpers::calculateCategoriesAverage($categories);
        }

        if ($categoryOrders != null) {
            usort($categories, function($first, $second) use ($categoryOrders) {
                return $categoryOrders[$first->id] - $categoryOrders[$second->id];
            });
        }

        return array_values($categories);
    }

    /**
    * Groups the answers by role id
    */
    public static function groupAnswersByRoleId($survey, $roleNames, $questions, $individualSelf)
    {
        $roles = [];

        $toEvaluateRoleId = -1;
        if (SurveyTypes::isGroupLike($survey->type)) {
            $toEvaluateRole =  $survey->roleGroups()
                ->where('toEvaluate', '=', true)
                ->first();

            if ($toEvaluateRole != null) {
                $toEvaluateRoleId = $toEvaluateRole->roleId;
            }
        }

        foreach ($questions as $question) {
            foreach ($question->answers as $answer) {
                //Create or get the role
                $role = null;

                $toEvaluate = false;
                $roleId = $answer->roleId;

                if (SurveyTypes::isGroupLike($survey->type)) {
                    $toEvaluate = $answer->roleId == $toEvaluateRoleId;
                } else if (SurveyTypes::isIndividualLike($survey->type)) {
                    if ($individualSelf != null) {
                        $toEvaluate = $answer->recipientId == $individualSelf->recipientId && $answer->canBeCandidate;
                    } else {
                        $toEvaluate = $answer->roleId == \App\Roles::selfRoleId();
                    }
                }

                if (SurveyTypes::isIndividualLike($survey->type)) {
                    if ($toEvaluate && $individualSelf == null) {
                        $roleId = \App\Roles::candidatesRoleId();
                    }
                }

                if (!array_key_exists($roleId, $roles)) {
                    $role = (object)[
                        'id' => $roleId,
                        'name' => $roleNames[$roleId],
                        'toEvaluate' => $toEvaluate,
                        'questions' => []
                    ];

                    $roles[$roleId] = $role;
                } else {
                    $role = $roles[$roleId];
                }

                //Create or get the question
                $roleQuestion = null;

                if (!array_key_exists($question->id, $role->questions)) {
                    $roleQuestion = clone $question;
                    $roleQuestion->answers = [];
                    $role->questions[$question->id] = $roleQuestion;
                } else {
                    $roleQuestion = $role->questions[$question->id];
                }

                //Add the answer
                array_push($roleQuestion->answers, $answer);
            }
        }

        foreach ($roles as $role) {
            $role->questions = array_values($role->questions);
        }

        //Sort by to evaluate, so that the role to evaluate is always first
        $roles = array_reverse(array_sort($roles, function($value) {
            return $value->toEvaluate;
        }));

        return $roles;
    }

    /**
    * Returns the answers grouped first by role, then by category.
    */
    public static function groupByRoleAndCategory($survey, $roleNames, $questions, $categoryOrders, $individualSelf)
    {
        $roleAnswers = [];

        foreach (SurveyReportHelpers::groupAnswersByRoleId($survey, $roleNames, $questions, $individualSelf) as $role) {
            $role->categories = SurveyReportHelpers::groupQuestionsByCategory($role->questions, $categoryOrders);

            $roleSum = 0;
            $roleCount = 0;

            //Calculate the average for each category
            foreach ($role->categories as $category) {
                $categorySum = 0;
                $categoryCount = 0;

                foreach ($category->questions as $question) {
                    foreach ($question->answers as $answer) {
                        $categorySum += $answer->value;
                        $categoryCount++;
                    }
                }

                if ($categoryCount > 0) {
                    $category->average = $categorySum / $categoryCount;
                } else {
                    $category->average = 0;
                }

                $roleSum += $categorySum;
                $roleCount += $categoryCount;
            }

            //Calculate the role average
            if ($roleCount > 0) {
                $role->average = $roleSum / $roleCount;
            } else {
                $role->average = 0;
            }

            unset($role->questions);
            array_push($roleAnswers, $role);
        }

        return SurveyReportHelpers::sortByRoleId($roleAnswers, $survey->type);
    }

    /**
    * Groups the given categories by role
    */
    public static function groupByCategoryAndRole($survey, $roleNames, $categories, $individualSelf)
    {
        $categoryRoleAnswers = [];

        foreach ($categories as $category) {
            $roleCategory = clone $category;
            $roleQuestions = [];

            foreach (SurveyReportHelpers::groupAnswersByRoleId($survey, $roleNames, $roleCategory->questions, $individualSelf) as $question) {
                array_push($roleQuestions, $question);
            }

            $roleCategory->roles = SurveyReportHelpers::sortByRoleId($roleQuestions, $survey->type);
            unset($roleCategory->questions);

            SurveyReportHelpers::calculateRolesAverage($roleCategory->roles);
            array_push($categoryRoleAnswers, $roleCategory);
        }

        return $categoryRoleAnswers;
    }

    /**
    * Returns the questions grouped by role id
    */
    public static function groupByRole($survey, $roleNames, $questions, $individualSelf)
    {
        $questionRoles = [];

        foreach (SurveyReportHelpers::groupAnswersByRoleId($survey, $roleNames, $questions, $individualSelf) as $role) {
            //Calculate the average for each question
            SurveyReportHelpers::calculateQuestionsAverage($role->questions);

            //Sort by highest average
            $role->questions = array_reverse(array_sort($role->questions, function($value) {
                return $value->average;
            }));

            array_push($questionRoles, $role);
        }

        return SurveyReportHelpers::sortByRoleId($questionRoles, $survey->type);
    }

    /**
    * Removes the answers that matches the given role ids
    */
    public static function filterQuestionsByRoleId($questions, $roleIds)
    {
        $newQuestions = [];

        foreach ($questions as $question) {
            $newQuestion = clone $question;

            $newQuestion->answers = array_filter($newQuestion->answers, function($answer) use (&$roleIds) {
                return !array_key_exists($answer->roleId, $roleIds);
            });

            $newQuestion->naAnswers = array_filter($newQuestion->naAnswers, function($answer) use (&$roleIds) {
                return !array_key_exists($answer->roleId, $roleIds);
            });

            array_push($newQuestions, $newQuestion);
        }

        return $newQuestions;
    }

    /**
    * Splits the answers into a self part (the role being evaluated) and others
    */
    public static function splitQuestions($survey, $questions, $individualSelf)
    {
        $self = [];
        $others = [];

        //Split the questions
        $questionIndex = 0;
        foreach ($questions as $question) {
            foreach ($question->answers as $answer) {
                //Get the list
                $list = null;
                $isSelf = false;

                if (SurveyTypes::isGroupLike($survey->type)) {
                    $isSelf = $answer->roleId == $survey->toEvaluateRole()->id;
                } else if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress) {
                    if ($individualSelf != null) {
                        $isSelf = $answer->recipientId == $individualSelf->recipientId;
                    } else {
                        $isSelf = $answer->roleId == \App\Roles::selfRoleId();
                    }
                }

                if ($isSelf) {
                    $list = $self;
                } else {
                    $list = $others;
                }

                //Create or get the question
                $listQuestion = null;

                if (!array_key_exists($questionIndex, $list)) {
                    $listQuestion = clone $question;
                    $listQuestion->answers = [];
                    array_push($list, $listQuestion);
                } else {
                    $listQuestion = $list[$questionIndex];
                }

                //Add the answer
                array_push($listQuestion->answers, $answer);

                //The list above is copied? so set the variable.
                if ($isSelf) {
                    $self = $list;
                } else {
                    $others = $list;
                }
            }

            $questionIndex++;
        }

        //Calculate the average
        $self = SurveyReportHelpers::calculateQuestionsAverage($self);
        $others = SurveyReportHelpers::calculateQuestionsAverage($others);

        return (object)[
            'self' => $self,
            'others' => $others
        ];
    }

    /**
    * Splits the categories into a self part (the role being evaluated) and others
    */
    public static function splitCategories($survey, $categoriesByRole, $individualSelf)
    {
        $selfAndOthersCategories = [];
        $selfRoleId = SurveyReportHelpers::getSelfRoleId($survey, $individualSelf);

        foreach ($categoriesByRole as $category) {
            $newCategory = clone $category;

            $splitRoles = \App\ArrayHelpers::split($newCategory->roles, function ($role) use ($selfRoleId) {
                return $role->id != $selfRoleId;
            });

            $newRole = \App\SurveyReportHelpers::mergeQuestionRoles($splitRoles[0], -1, Lang::get('roles.others'));
            $newRole->toEvaluate = false;

            SurveyReportHelpers::calculateCategoriesAverage([$newRole]);
            $newCategory->roles = array_merge($splitRoles[1], [$newRole]);
            $newCategory->roles = \App\SurveyReportHelpers::sortByRoleId($newCategory->roles, $survey->type);

            array_push($selfAndOthersCategories, $newCategory);
        }

        return $selfAndOthersCategories;
    }

    /**
    * Calculates the average for the self and others questions
    */
    public static function calculateSelfAndOthersAverage($survey, $selfAndOthersQuestions, $toEvaluate)
    {
        $selfAndOthersAverage = [];
        $selfRoleId = SurveyReportHelpers::getSelfRoleId($survey, $toEvaluate);

        if (SurveyTypes::isGroupLike($survey->type)) {
            $selfAndOthersAverage = [
                (object)[
                    'id' => $selfRoleId,
                    'name' => $survey->toEvaluateRole()->name,
                    'questions' => $selfAndOthersQuestions->self
                ],
                (object)[
                    'id' => -1,
                    'name' => Lang::get('roles.others'),
                    'questions' => $selfAndOthersQuestions->others
                ],
            ];
        } else if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress) {
            $selfAndOthersAverage = [
                (object)[
                    'id' => $selfRoleId,
                    'name' => $toEvaluate != null ? Lang::get('roles.self') : Lang::get('roles.candidates'),
                    'questions' => $selfAndOthersQuestions->self
                ],
                (object)[
                    'id' => -1,
                    'name' => Lang::get('roles.others'),
                    'questions' => $selfAndOthersQuestions->others
                ],
            ];
        }

        foreach ($selfAndOthersAverage as $role) {
            $sum = 0;
            $count = 0;

            foreach ($role->questions as $question) {
                foreach ($question->answers as $answer) {
                    $sum += $answer->value;
                    $count++;
                }
            }

            if ($count > 0) {
                $role->average = $sum / $count;
            } else {
                $role->average = 0;
            }

            unset($role->questions);
        }

        return $selfAndOthersAverage;
    }

    /**
    * Returns the answer frequency for the given answers
    */
    public static function getAnswerFrequencyForAnswers($answers, $answerType)
    {
        $answerCounts = [];

        foreach ($answerType->values() as $value) {
            $answerCounts[$value->value] = 0;
        }

        foreach ($answers as $answer) {
            $value = intval($answer->value * $answerType->maxValue());

            if (array_key_exists($value, $answerCounts)) {
                $answerCounts[$value]++;
            } else {
                $answerCounts[$value] = 1;
            }
        }

        return $answerCounts;
    }

    /**
    * Returns the frequency of each answers for the given questions
    */
    public static function getAnswerFrequency($questions)
    {
        $countAnswers = [];

        foreach ($questions as $question) {
            $newQuestion = clone $question;
            unset($newQuestion->answers);
            $newQuestion->answerFrequency = SurveyReportHelpers::getAnswerFrequencyForAnswers($question->answers, $question->answerType);
            array_push($countAnswers, $newQuestion);
        }

        return $countAnswers;
    }

    /**
    * Returns the frequency of each answers for the given categories
    */
    public static function getAnswerFrequencyInCategories($categories)
    {
        $newCategories = [];

        foreach ($categories as $category) {
            $newCategory = clone $category;
            $answerFrequency = [];
            $naAnswerFrequency = 0;

            foreach (SurveyReportHelpers::getAnswerFrequency($newCategory->questions) as $question) {
                foreach ($question->answerFrequency as $answer => $count) {
                    if (array_key_exists($answer, $answerFrequency)) {
                        $answerFrequency[$answer] += $count;
                    } else {
                        $answerFrequency[$answer] = $count;
                    }
                }

                $naCount = count($question->naAnswers);
                $naAnswerFrequency += $naCount;
            }

            unset($newCategory->questions);

            $newAnswerFrequency = [];
            $totalCount = $naAnswerFrequency;

            foreach ($answerFrequency as $answer => $count) {
                array_push($newAnswerFrequency, (object)[
                    'answer' => $answer,
                    'count' => $count
                ]);

                $totalCount += $count;
            }

            $newCategory->answerFrequency = $newAnswerFrequency;

            usort($newCategory->answerFrequency, function($first, $second) {
                return $first->answer - $second->answer;
            });

            $naAnswer = [];

            if ($naAnswerFrequency > 0) {
                $naAnswer = [(object)[
                    'answer' => 'N/A',
                    'count' => $naAnswerFrequency
                ]];
            }

            $newCategory->answerFrequency = array_merge($naAnswer, $newCategory->answerFrequency);
            $newCategory->numAnswers = $totalCount;

            array_push($newCategories, $newCategory);
        }

        return $newCategories;
    }

    /**
    * Returns the yes/no questions
    */
    public static function getYesOrNoQuestions($questions)
    {
        $yesOrNoQuestions = [];

        foreach ($questions as $question) {
            if ($question->answerType->id() == \App\AnswerType::YES_OR_NO_TYPE) {
                $numYes = 0;
                $numNo = 0;
                $numNa = 0;

                foreach ($question->answers as $answer) {
                    if ($answer->value == 1) {
                        $numYes++;
                    } else if ($answer->value == 0) {
                        $numNo++;
                    } else {
                        $numNa++;
                    }
                }

                $total = $numYes + $numNo + $numNa;

                array_push($yesOrNoQuestions, (object)[
                    'id' => $question->id,
                    'categoryId' => $question->categoryId,
                    'category' => $question->category,
                    'title' => $question->title,
                    'yesRatio' => $total > 0 ? $numYes / $total : 0,
                    'noRatio' => $total > 0 ? $numNo / $total : 0,
                    'naRatio' => $total > 0 ? $numNa / $total : 0,
                ]);
            }
        }

        return $yesOrNoQuestions;
    }

    /**
    * Returns the order for the categories
    */
    public static function getCategoryOrders($survey)
    {
        $orders = [];

        foreach ($survey->categories as $category) {
            $orders[$category->categoryId] = $category->order;
        }

        return $orders;
    }

    /**
    * Find a question by its id
    */
    public static function findQuestionById($questions, $id)
    {
        foreach ($questions as $question) {
            if ($question->id == $id) {
                return $question;
            }
        }

        return null;
    }

    /**
    * Find a category by its id
    */
    public static function findCategoryById($categories, $id)
    {
        foreach ($categories as $category) {
            if ($category->id == $id) {
                return $category;
            }
        }

        return null;
    }

    /**
    * Sorts by role id
    */
    public static function sortByRoleId($data, $type)
    {
        $sortOrdersIndividual = [];
        $sortOrdersGroup = [];

        if (\App\SurveyTypes::isIndividualLike($type)) {
            $sortOrdersIndividual[\App\Roles::getRoleIdByName(Lang::get('roles.manager'), $type)] = 1;
            $sortOrdersIndividual[\App\Roles::getRoleIdByName(Lang::get('roles.colleague'), $type)] = 2;
            $sortOrdersIndividual[\App\Roles::getRoleIdByName(Lang::get('roles.directReport'), $type)] = 3;
            $sortOrdersIndividual[\App\Roles::getRoleIdByName(Lang::get('roles.customer'), $type)] = 4;
            $sortOrdersIndividual[\App\Roles::selfRoleId()] = 1000;
        } else if (\App\SurveyTypes::isIndividualLike($type)) {
            $sortOrdersIndividual[\App\Roles::getRoleIdByName(Lang::get('roles.manager'), $type)] = 1;
            $sortOrdersIndividual[\App\Roles::getRoleIdByName(Lang::get('roles.managementTeam'), $type)] = 2;
            $sortOrdersIndividual[\App\Roles::getRoleIdByName(Lang::get('roles.teamMember'), $type)] = 3;
        }

        foreach ($data as $role) {
            if (\App\SurveyTypes::isIndividualLike($type)) {
                if (array_key_exists($role->id, $sortOrdersIndividual)) {
                    $role->sortOrder = $sortOrdersIndividual[$role->id];
                } else {
                    $role->sortOrder = 100 + $role->id;
                }
            } else if (\App\SurveyTypes::isGroupLike($type)) {
                if ($role->toEvaluate) {
                    $role->sortOrder = 1000;
                } else if (array_key_exists($role->id, $sortOrdersGroup)) {
                    $role->sortOrder = $sortOrdersGroup[$role->id];
                } else {
                    $role->sortOrder = 100 + $role->id;
                }
            } else {
                $role->sortOrder = $role->id;
            }
        }

        usort($data, function($first, $second) {
            return $first->sortOrder - $second->sortOrder;
        });

        return $data;
    }

    /**
    * Merges question and comment categories
    */
    public static function mergeQuestionsAndCommentsCategories($categoryOrders, $questionCategories, $commentCategories)
    {
        $categories = [];

        foreach ($questionCategories as $category) {
            $newCategory = clone $category;
            $newCategory->comments = [];
            $categories[$newCategory->id] = $newCategory;
        }

        foreach ($commentCategories as $category) {
            if (!array_key_exists($category->id, $categories)) {
                $newCategory = clone $category;
                $newCategory->questions = [];
                $newCategory->comments = [];
                $categories[$category->id] = $newCategory;
            } else {
                $newCategory = $categories[$category->id];
            }

            $newCategory->comments = $category->questions;
        }

        $categories = array_values($categories);
        usort($categories, function ($x, $y) use (&$categoryOrders) {
            return $categoryOrders[$x->id] - $categoryOrders[$y->id];
        });

        return $categories;
    }

    /**
    * Returns the data for the given survey
    */
    public static function getData($survey, $questionAndComments, $toEvaluate = null)
    {
        //Get the questions and comments
        $questions = $questionAndComments->questions;
        $comments = $questionAndComments->comments;
        $answeredRecipientIds = $questionAndComments->answeredRecipientIds;
        $roleNames = $questionAndComments->roleNames;

        $categoryOrders = SurveyReportHelpers::getCategoryOrders($survey);
        $categories = SurveyReportHelpers::groupQuestionsByCategory($questions, $categoryOrders);

        $rolesByCategory = SurveyReportHelpers::groupByRoleAndCategory($survey, $roleNames, $questions, $categoryOrders, $toEvaluate);
        $categoriesByRole = SurveyReportHelpers::groupByCategoryAndRole($survey, $roleNames, $categories, $toEvaluate);
        $questionsByRole = SurveyReportHelpers::groupByRole($survey, $roleNames, $questions, $toEvaluate);
        $selfAndOthersQuestions = SurveyReportHelpers::splitQuestions($survey, $questions, $toEvaluate);
        $selfAndOthersCategories = SurveyReportHelpers::splitCategories($survey, $categoriesByRole, $toEvaluate);
        $categoryAnswerFrequency = SurveyReportHelpers::getAnswerFrequencyInCategories($categories);
        $yesOrNoQuestions = SurveyReportHelpers::getYesOrNoQuestions($questions);

        if (SurveyTypes::isGroupLike($survey->type)) {
            $blindSpots = SurveyReportGroup::getBlindSpots($questionsByRole);
        } else {
            $blindSpots = SurveyReport360::getBlindSpots($selfAndOthersQuestions);
        }

        //Get the categories without the self role
        $otherCategories = [];
        $otherCategoryAnswerFrequency = [];

        if (SurveyTypes::isIndividualLike($survey->type)) {
            $ignoreId = \App\Roles::selfRoleId();
            $otherQuestions = SurveyReportHelpers::filterQuestionsByRoleId($questions, [$ignoreId => true]);
            $otherCategories = SurveyReportHelpers::groupQuestionsByCategory($otherQuestions, $categoryOrders);
            $otherCategoryAnswerFrequency = SurveyReportHelpers::getAnswerFrequencyInCategories($otherCategories);
        }

        //Get the splited questions average
        $selfAndOthersAverage = [];
        if ($survey->type == \App\SurveyTypes::Progress) {
            $selfAndOthersAverage = SurveyReportHelpers::calculateSelfAndOthersAverage($survey, $selfAndOthersQuestions, $toEvaluate);
        }

        $allRoles = [];
        $selfRoleId = SurveyReportHelpers::getSelfRoleId($survey, $toEvaluate);
        foreach ($roleNames as $id => $name) {
            $allRoles[$id] = (object)[
                'id' => $id,
                'name' => $name,
                'toEvaluate' => $id == $selfRoleId
            ];
        }

        //Pad roles
        if (SurveyTypes::isGroupLike($survey->type)) {
            foreach ($categoriesByRole as $category) {
                foreach ($allRoles as $role) {
                    $exists = false;
                    foreach ($category->roles as $categoryRole) {
                        if ($categoryRole->id == $role->id) {
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists) {
                        array_push($category->roles, (object)[
                            'id' => $role->id,
                            'name' => $role->name,
                            'toEvaluate' => $role->toEvaluate,
                            'average' => null,
                            'questions' => []
                        ]);
                    }
                }

                $category->roles = \App\SurveyReportHelpers::sortByRoleId($category->roles, $survey->type);
            }
        }

        return (object)[
            'questions' => $questions,
            'categories' => $categories,
            'rolesByCategory' => $rolesByCategory,
            'categoriesByRole' => $categoriesByRole,
            'questionsByRole' => $questionsByRole,
            'selfAndOthersAverage' => $selfAndOthersAverage,
            'selfAndOthersQuestions' => $selfAndOthersQuestions,
            'selfAndOthersCategories' => $selfAndOthersCategories,
            'otherCategories' => $otherCategories,
            'categoryAnswerFrequency' => $categoryAnswerFrequency,
            'otherCategoryAnswerFrequency' => $otherCategoryAnswerFrequency,
            'yesOrNoQuestions' => $yesOrNoQuestions,
            'comments' => $comments,
            'blindSpots' => $blindSpots,
            'answeredRecipientIds' => $answeredRecipientIds,
            'roleNames' => $roleNames,
            'allRoles' => $allRoles
        ];
    }
}

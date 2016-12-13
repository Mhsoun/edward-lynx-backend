<?php namespace App;

use Lang;
use App\AnswerType;
use App\SurveyTypes;

/**
* Contains functions for survey reports
*/
abstract class SurveyReport
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
            SurveyReport::calculateAverage($question);
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
                            'invitedById' => $answer->invitedBy,
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
            SurveyReport::calculateCategoriesAverage($categories);
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
    private static function groupAnswersByRoleId($survey, $roleNames, $questions, $individualSelf)
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
    private static function groupByRoleAndCategory($survey, $roleNames, $questions, $categoryOrders, $individualSelf)
    {
        $roleAnswers = [];

        foreach (SurveyReport::groupAnswersByRoleId($survey, $roleNames, $questions, $individualSelf) as $role) {
            $role->categories = SurveyReport::groupQuestionsByCategory($role->questions, $categoryOrders);

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

        return SurveyReport::sortByRoleId($roleAnswers, $survey->type);
    }

    /**
    * Groups the given categories by role
    */
    private static function groupByCategoryAndRole($survey, $roleNames, $categories, $individualSelf)
    {
        $categoryRoleAnswers = [];

        foreach ($categories as $category) {
            $roleCategory = clone $category;
            $roleQuestions = [];

            foreach (SurveyReport::groupAnswersByRoleId($survey, $roleNames, $roleCategory->questions, $individualSelf) as $question) {
                array_push($roleQuestions, $question);
            }

            $roleCategory->roles = SurveyReport::sortByRoleId($roleQuestions, $survey->type);
            unset($roleCategory->questions);

            SurveyReport::calculateRolesAverage($roleCategory->roles);
            array_push($categoryRoleAnswers, $roleCategory);
        }

        return $categoryRoleAnswers;
    }

    /**
    * Returns the questions grouped by role id
    */
    private static function groupByRole($survey, $roleNames, $questions, $individualSelf)
    {
        $questionRoles = [];

        foreach (SurveyReport::groupAnswersByRoleId($survey, $roleNames, $questions, $individualSelf) as $role) {
            //Calculate the average for each question
            SurveyReport::calculateQuestionsAverage($role->questions);

            //Sort by highest average
            $role->questions = array_reverse(array_sort($role->questions, function($value) {
                return $value->average;
            }));

            array_push($questionRoles, $role);
        }

        return SurveyReport::sortByRoleId($questionRoles, $survey->type);
    }

    /**
    * Removes the answers that matches the given role ids
    */
    private static function filterQuestionsByRoleId($questions, $roleIds)
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
    private static function splitQuestions($survey, $questions, $individualSelf)
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
        $self = SurveyReport::calculateQuestionsAverage($self);
        $others = SurveyReport::calculateQuestionsAverage($others);

        return (object)[
            'self' => $self,
            'others' => $others
        ];
    }

    /**
    * Splits the categories into a self part (the role being evaluated) and others
    */
    private static function splitCategories($survey, $categoriesByRole, $individualSelf)
    {
        $selfAndOthersCategories = [];
        $selfRoleId = SurveyReport::getSelfRoleId($survey, $individualSelf);

        foreach ($categoriesByRole as $category) {
            $newCategory = clone $category;

            $splitRoles = \App\ArrayHelpers::split($newCategory->roles, function ($role) use ($selfRoleId) {
                return $role->id != $selfRoleId;
            });

            $newRole = \App\SurveyReport::mergeQuestionRoles($splitRoles[0], -1, Lang::get('roles.others'));
            $newRole->toEvaluate = false;

            SurveyReport::calculateCategoriesAverage([$newRole]);
            $newCategory->roles = array_merge($splitRoles[1], [$newRole]);
            $newCategory->roles = \App\SurveyReport::sortByRoleId($newCategory->roles, $survey->type);

            array_push($selfAndOthersCategories, $newCategory);
        }

        return $selfAndOthersCategories;
    }

    /**
    * Calculates the average for the self and others questions
    */
    private static function calculateSelfAndOthersAverage($survey, $selfAndOthersQuestions, $toEvaluate)
    {
        $selfAndOthersAverage = [];
        $selfRoleId = SurveyReport::getSelfRoleId($survey, $toEvaluate);

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
    private static function getAnswerFrequencyForAnswers($answers, $answerType)
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
    private static function getAnswerFrequency($questions)
    {
        $countAnswers = [];

        foreach ($questions as $question) {
            $newQuestion = clone $question;
            unset($newQuestion->answers);
            $newQuestion->answerFrequency = SurveyReport::getAnswerFrequencyForAnswers($question->answers, $question->answerType);
            array_push($countAnswers, $newQuestion);
        }

        return $countAnswers;
    }

    /**
    * Returns the frequency of each answers for the given categories
    */
    private static function getAnswerFrequencyInCategories($categories)
    {
        $newCategories = [];

        foreach ($categories as $category) {
            $newCategory = clone $category;
            $answerFrequency = [];
            $naAnswerFrequency = 0;

            foreach (SurveyReport::getAnswerFrequency($newCategory->questions) as $question) {
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
    private static function getYesOrNoQuestions($questions)
    {
        $yesOrNoQuestions = [];

        foreach ($questions as $question) {
            if ($question->answerType->id() == \App\AnswerType::YES_OR_NO_TYPE) {
                $numYes = 0;
                $numNo = 0;

                foreach ($question->answers as $answer) {
                    if ($answer->value == 1) {
                        $numYes++;
                    } else if ($answer->value == 0) {
                        $numNo++;
                    }
                }

                $total = $numYes + $numNo;

                array_push($yesOrNoQuestions, (object)[
                    'id' => $question->id,
                    'categoryId' => $question->categoryId,
                    'category' => $question->category,
                    'title' => $question->title,
                    'yesRatio' => $total > 0 ? $numYes / $total : 0,
                    'noRatio' => $total > 0 ? $numNo / $total : 0,
                ]);
            }
        }

        return $yesOrNoQuestions;
    }

    /**
    * Returns the order for the categories
    */
    private static function getCategoryOrders($survey)
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
    * Returns the blind spots
    */
    private static function getBlindSpots($selfAndOthersQuestions)
    {
        $overestimatedQuestions = [];
        $underestimatedQuestions = [];
        $gapThreshold = 20;

        foreach ($selfAndOthersQuestions->self as $selfQuestion) {
            $otherQuestion = \App\SurveyReport::findQuestionById($selfAndOthersQuestions->others, $selfQuestion->id);
            if ($otherQuestion != null) {
                $selfAverage = round($selfQuestion->average * 100);
                $othersAverage = round($otherQuestion->average * 100);
                $gap = abs($selfAverage - $othersAverage);

                if ($selfAverage > $othersAverage && $gap >= $gapThreshold) {
                    array_push($overestimatedQuestions, (object)[
                        'id' => $selfQuestion->id,
                        'title' => $selfQuestion->title,
                        'category' => $selfQuestion->category,
                        'answerType' => $selfQuestion->answerType,
                        'self' => $selfAverage,
                        'others' => $othersAverage,
                        'gap' => $gap
                    ]);
                } else if ($othersAverage > $selfAverage && $gap >= $gapThreshold) {
                    array_push($underestimatedQuestions, (object)[
                        'id' => $selfQuestion->id,
                        'title' => $selfQuestion->title,
                        'category' => $selfQuestion->category,
                        'answerType' => $selfQuestion->answerType,
                        'self' => $selfAverage,
                        'others' => $othersAverage,
                        'gap' => $gap
                    ]);
                }
            }
        }


        //Sort the by gap, descending.
        $descendingGap = function($first, $second) {
            return $second->gap - $first->gap;
        };

        usort($overestimatedQuestions, $descendingGap);
        usort($underestimatedQuestions, $descendingGap);

        return (object)[
            'overestimated' => array_slice($overestimatedQuestions, 0, 5),
            'underestimated' => array_slice($underestimatedQuestions, 0, 5)
        ];
    }

    /**
    * Returns the blind spots for group surveys
    */
    private static function getBlindSpotsGroup($questionsByRole)
    {
        //Split into evaluating role and other roles
        $splitRoles = \App\ArrayHelpers::split($questionsByRole, function ($role) {
            return $role->toEvaluate;
        });

        $toEvaluate = $splitRoles[0][0];
        $comparePoint = 70;
        $blindSpots = [];

        foreach ($splitRoles[1] as $role) {
            $overestimatedQuestions = [];
            $underestimatedQuestions = [];

            foreach ($role->questions as $otherQuestion) {
                $selfQuestion = \App\SurveyReport::findQuestionById(
                    $toEvaluate->questions,
                    $otherQuestion->id);

                if ($selfQuestion != null) {
                    $selfAverage = round($selfQuestion->average * 100);
                    $othersAverage = round($otherQuestion->average * 100);
                    $gap = abs($selfAverage - $othersAverage);

                    if ($gap >= 20) {
                        if ($othersAverage < $comparePoint
                            && $selfAverage > $comparePoint
                            && $selfAverage > $othersAverage) {
                            array_push($overestimatedQuestions, (object)[
                                'id' => $selfQuestion->id,
                                'title' => $selfQuestion->title,
                                'category' => $selfQuestion->category,
                                'answerType' => $selfQuestion->answerType,
                                'self' => $selfAverage,
                                'others' => $othersAverage,
                                'gap' => $gap
                            ]);
                        } else if ($othersAverage > $comparePoint
                                   && $selfAverage < $comparePoint
                                   && $selfAverage < $othersAverage) {
                            array_push($underestimatedQuestions, (object)[
                                'id' => $selfQuestion->id,
                                'title' => $selfQuestion->title,
                                'category' => $selfQuestion->category,
                                'answerType' => $selfQuestion->answerType,
                                'self' => $selfAverage,
                                'others' => $othersAverage,
                                'gap' => $gap
                            ]);
                        }
                    }
                }
            }

            //Sort the by gap, descending.
            $descendingGap = function($first, $second) {
                return $second->gap - $first->gap;
            };

            usort($overestimatedQuestions, $descendingGap);
            usort($underestimatedQuestions, $descendingGap);

            array_push($blindSpots, (object)[
                'id' => $role->id,
                'name' => $role->name,
                'overestimated' => array_slice($overestimatedQuestions, 0, 5),
                'underestimated' => array_slice($underestimatedQuestions, 0, 5),
            ]);
        }

        return $blindSpots;
    }

    /**
    * Merges the given roles
    */
    private static function mergeRoles($survey, $roleNames, $answeredRecipientIds)
    {
        $type = $survey->type;

        $managerId = \App\Roles::getRoleIdByName(Lang::get('roles.manager'), $type);
        $managerCount = 0;

        $rolesToMerge = [];
        foreach (\App\Roles::get360() as $role) {
            if ($role->id != $managerId) {
                array_push($rolesToMerge, $role->id);
            }
        }

        $mergeLimit = 3;
        $rolesToMergeCount = [];
        $totalCount = 0;

        //Compute the count for the roles to merge
        foreach ($rolesToMerge as $roleId) {
            $count = 0;

            foreach ($answeredRecipientIds as $recipient) {
                if ($roleId == $recipient->roleId) {
                    $count++;
                }
            }

            $rolesToMergeCount[$roleId] = $count;
            $totalCount += $count;
        }

        if ($totalCount == 2) {
            foreach ($answeredRecipientIds as $recipient) {
                if ($recipient->roleId == $managerId) {
                    $managerCount++;
                }
            }
        }

        //Get the roles to actually merge
        $toMerge = [];
        foreach ($rolesToMergeCount as $roleId => $count) {
            if ($count < $mergeLimit && $count > 0) {
                array_push($toMerge, $roleId);
            }
        }

        //If there just are one role to merge, choose the one with least number of recipients
        if (count($toMerge) == 1) {
            $lowestCount = PHP_INT_MAX;
            $lowestRoleId = 0;

            foreach ($rolesToMergeCount as $roleId => $count) {
                if ($roleId != $toMerge[0]) {
                    if ($count < $lowestCount && $count > 0) {
                        $lowestCount = $count;
                        $lowestRoleId = $roleId;
                    }
                }
            }

            if ($lowestCount != PHP_INT_MAX) {
                array_push($toMerge, $lowestRoleId);
            }
        }

        //Special case for manager
        if (count($toMerge) == 2 && $managerCount == 1 && $totalCount == 2) {
            array_push($toMerge, $managerId);
        }

        $mergedRoleIdsMapping = [];

        //Merge the roles
        if (count($toMerge) > 0) {
            $mergedRoleName = '';
            $mergedRoleId = -2;

            foreach ($toMerge as $roleId) {
                $mergedRoleIdsMapping[$roleId] = $mergedRoleId;
            }

            if (count($toMerge) >= 3) {
                $mergedRoleName = Lang::get('roles.others');
            } else {
                $mergedRoleName = implode(' & ', array_map(function ($roleId) use (&$roleNames) {
                    return $roleNames[$roleId];
                }, $toMerge));
            }

            $roleNames[$mergedRoleId] = $mergedRoleName;
        }

        //Update the recipients
        $answeredRecipientIdsMapping = [];
        foreach ($answeredRecipientIds as $recipient) {
            if (array_key_exists($recipient->roleId, $mergedRoleIdsMapping)) {
                $recipient->roleId = $mergedRoleIdsMapping[$recipient->roleId];
            }

            $answeredRecipientIdsMapping[$recipient->recipientId . ':' . $recipient->invitedById] = $recipient;
        }

        return (object)[
            'answeredRecipientIds' => $answeredRecipientIds,
            'roleNames' => $roleNames,
        ];
    }

    /**
    * Returns the extra questions for the given survey
    */
    private static function getExtraQuestions($survey)
    {
        $extraQuestions = [];
        foreach ($survey->extraQuestions as $extraQuestion) {
            $values = [];
            $type = $extraQuestion->extraQuestion->type;
            if ($type == \App\ExtraAnswerValue::Text || $type == \App\ExtraAnswerValue::Date) {
                $typeName = $type == \App\ExtraAnswerValue::Text ? 'textValue' : 'dateValue';

                $values = $survey->extraAnswers()
                   ->where('extraQuestionId', '=', $extraQuestion->extraQuestionId)
                   ->distinct()
                   ->lists($typeName);
            } else if ($type == \App\ExtraAnswerValue::Options) {
                foreach ($extraQuestion->extraQuestion->values as $value) {
                    array_push($values, $value->name());
                }
            } else if ($type == \App\ExtraAnswerValue::Hierarchy) {
                foreach ($extraQuestion->extraQuestion->values as $value) {
                    if ($value->children()->count() == 0) {
                        array_push($values, $value->fullName());
                    }
                }
            }

            array_push($extraQuestions, (object)[
                'id' => $extraQuestion->extraQuestionId,
                'name' => $extraQuestion->extraQuestion->name(),
                'values' => $values
            ]);
        }

        return $extraQuestions;
    }

    /**
    * Groups the extra answers by the questions
    */
    private static function extraAnswerByQuestions($questions, $extraQuestions, $extraAnswers)
    {
        $recipientToExtraAnswers = [];
        foreach ($extraAnswers as $recipient) {
            $answers = [];
            foreach ($recipient->answers as $id => $answer) {
                $answers[$id] = $answer->value;
            }

            $recipientToExtraAnswers[$recipient->recipient->recipientId] = $answers;
        }

        $extraAnswerByQuestions = [];
        foreach ($questions as $question) {
            $newQuestion = clone $question;
            unset($newQuestion->naAnswers);
            unset($newQuestion->answers);

            $newQuestion->extraQuestions = [];
            foreach ($extraQuestions as $extraQuestion) {
                $newExtraQuestion = clone $extraQuestion;
                $newExtraQuestion->values = [];

                foreach ($extraQuestion->values as $value) {
                    $newExtraQuestion->values[$value] = (object)[
                        'value' => $value,
                        'answers' => [],
                        'naAnswers' => [],
                        'answerFrequency' => []
                    ];
                }

                foreach ($question->answers as $answer) {
                    if (array_key_exists($extraQuestion->id, $recipientToExtraAnswers[$answer->recipientId])) {
                        $extraQuestionAnswer = $recipientToExtraAnswers[$answer->recipientId][$extraQuestion->id];
                        array_push(
                            $newExtraQuestion->values[$extraQuestionAnswer]->answers,
                            $answer);
                    }
                }

                foreach ($question->naAnswers as $answer) {
                    if (array_key_exists($extraQuestion->id, $recipientToExtraAnswers[$answer->recipientId])) {
                        $extraQuestionAnswer = $recipientToExtraAnswers[$answer->recipientId][$extraQuestion->id];
                        array_push(
                            $newExtraQuestion->values[$extraQuestionAnswer]->naAnswers,
                            $answer);
                    }
                }

                $newExtraQuestion->values = array_values($newExtraQuestion->values);
                foreach ($newExtraQuestion->values as $value) {
                    $value->average = SurveyReport::calculateAnswersAverage($value->answers);
                    $value->answerFrequency = [];

                    foreach (SurveyReport::getAnswerFrequencyForAnswers($value->answers, $question->answerType) as $answer => $count) {
                        array_push($value->answerFrequency, (object)[
                            'answer' => $answer,
                            'count' => $count
                        ]);
                    }

                    usort($value->answerFrequency, function($first, $second) {
                        return $first->answer - $second->answer;
                    });

                    if (count($value->naAnswers) > 0) {
                        $value->answerFrequency = array_merge([
                            (object)[
                                'answer' => 'N/A',
                                'count' => count($value->naAnswers)
                            ]
                        ], $value->answerFrequency);
                    }

                    $totalCount = 0;
                    foreach ($value->answerFrequency as $answer) {
                        $totalCount += $answer->count;
                    }

                    foreach ($value->answerFrequency as $answer) {
                        if ($totalCount > 0) {
                            $answer->frequency = $answer->count / $totalCount;
                        } else {
                            $answer->frequency = 0;
                        }
                    }
                }

                $newQuestion->extraQuestions[$newExtraQuestion->id] = $newExtraQuestion;
            }

            array_push($extraAnswerByQuestions, $newQuestion);
        }

        return $extraAnswerByQuestions;
    }

    /**
    * Groups the extra answers by the questions
    */
    private static function questionsByExtraAnswer($questions, $extraQuestions, $extraAnswers)
    {
        $recipientToExtraAnswers = [];
        foreach ($extraAnswers as $recipient) {
            $answers = [];
            foreach ($recipient->answers as $id => $answer) {
                $answers[$id] = $answer->value;
            }

            $recipientToExtraAnswers[$recipient->recipient->recipientId] = $answers;
        }

        $questionsByExtraAnswer = [];

        $isValidAnswer = function ($extraQuestionId, $recipientId, $value) use (&$recipientToExtraAnswers) {
            return array_key_exists($extraQuestionId, $recipientToExtraAnswers[$recipientId])
                  && $recipientToExtraAnswers[$recipientId][$extraQuestionId] == $value;
        };

        foreach ($extraQuestions as $extraQuestion) {
            $newExtraQuestion = clone $extraQuestion;
            $newExtraQuestion->values = [];
            foreach ($extraQuestion->values as $value) {
                $valueQuestions = [];

                foreach ($questions as $question) {
                    $newQuestion = clone $question;
                    $newQuestion->answers = [];
                    $newQuestion->naAnswers = [];

                    foreach ($question->answers as $answer) {
                        if ($isValidAnswer($extraQuestion->id, $answer->recipientId, $value)) {
                            array_push($newQuestion->answers, $answer);
                        }
                    }

                    foreach ($question->naAnswers as $answer) {
                        if ($isValidAnswer($extraQuestion->id, $answer->recipientId, $value)) {
                            array_push($newQuestion->naAnswers, $answer);
                        }
                    }

                    array_push($valueQuestions, $newQuestion);
                }

                array_push($newExtraQuestion->values, (object)[
                    'value' => $value,
                    'questions' => $valueQuestions
                ]);
            }

            array_push($questionsByExtraAnswer, $newExtraQuestion);
        }

        return $questionsByExtraAnswer;
    }

    /**
    * Merges question and comment categories
    */
    private static function mergeQuestionsAndCommentsCategories($categoryOrders, $questionCategories, $commentCategories)
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
    * Returns summary for the extra answers grouped by categories
    */
    private static function extraAnswersByCategoriesSummary($extraAnswersByCategories, $extraQuestions, $categories)
    {
        $extraAnswersByCategoriesSummary = [];

        foreach ($extraAnswersByCategories as $category) {
            $categoryData = SurveyReport::findCategoryById($categories, $category->id);

            //This means that there exists no numerical questions
            if ($categoryData == null) {
                continue;
            }

            $newCategory = (object)[
                'id' => $category->id,
                'name' => $category->name,
                'extraQuestions' => []
            ];

            $extraQuestionData = [];
            foreach ($category->questions as $question) {
                foreach ($question->extraQuestions as $extraQuestion) {
                    foreach ($extraQuestion->values as $value) {
                        foreach ($value->answers as $answer) {
                            $key = $extraQuestion->id . ':' . $value->value;
                            $data = null;
                            if (!array_key_exists($key, $extraQuestionData)) {
                                $data = (object)[
                                    'sum' => 0,
                                    'count' => 0,
                                ];
                                $extraQuestionData[$key] = $data;
                            } else {
                                $data = $extraQuestionData[$key];
                            }

                            $data->sum += $answer->value;
                            $data->count++;
                        }
                    }
                }
            }

            foreach ($extraQuestions as $extraQuestion) {
                $newExtraQuestion = clone $extraQuestion;
                $newExtraQuestion->values = [];

                foreach ($extraQuestion->values as $value) {
                    $average = 0;
                    $key = $extraQuestion->id . ':' . $value;
                    if (array_key_exists($key, $extraQuestionData)) {
                        $data = $extraQuestionData[$key];
                        $average = $data->sum / $data->count;
                    }

                    array_push($newExtraQuestion->values, (object)[
                        'value' => $value,
                        'average' => $average
                    ]);
                }

                $newCategory->extraQuestions[$extraQuestion->id] = $newExtraQuestion;
            }

            $newCategory->average = $categoryData->average;
            array_push($extraAnswersByCategoriesSummary, $newCategory);
        }

        return $extraAnswersByCategoriesSummary;
    }

    /**
    * Returns the data for the given survey
    */
    private static function getData($survey, $questionAndComments, $toEvaluate = null)
    {
        //Get the questions and comments
        $questions = $questionAndComments->questions;
        $comments = $questionAndComments->comments;
        $answeredRecipientIds = $questionAndComments->answeredRecipientIds;
        $roleNames = $questionAndComments->roleNames;

        $categoryOrders = SurveyReport::getCategoryOrders($survey);
        $categories = SurveyReport::groupQuestionsByCategory($questions, $categoryOrders);

        $rolesByCategory = SurveyReport::groupByRoleAndCategory($survey, $roleNames, $questions, $categoryOrders, $toEvaluate);
        $categoriesByRole = SurveyReport::groupByCategoryAndRole($survey, $roleNames, $categories, $toEvaluate);
        $questionsByRole = SurveyReport::groupByRole($survey, $roleNames, $questions, $toEvaluate);
        $selfAndOthersQuestions = SurveyReport::splitQuestions($survey, $questions, $toEvaluate);
        $selfAndOthersCategories = SurveyReport::splitCategories($survey, $categoriesByRole, $toEvaluate);
        $categoryAnswerFrequency = SurveyReport::getAnswerFrequencyInCategories($categories);
        $yesOrNoQuestions = SurveyReport::getYesOrNoQuestions($questions);

        if (SurveyTypes::isGroupLike($survey->type)) {
            $blindSpots = SurveyReport::getBlindSpotsGroup($questionsByRole);
        } else {
            $blindSpots = SurveyReport::getBlindSpots($selfAndOthersQuestions);
        }

        //Get the categories without the self role
        $otherCategories = [];
        $otherCategoryAnswerFrequency = [];

        if (SurveyTypes::isIndividualLike($survey->type)) {
            $ignoreId = \App\Roles::selfRoleId();
            $otherQuestions = SurveyReport::filterQuestionsByRoleId($questions, [$ignoreId => true]);
            $otherCategories = SurveyReport::groupQuestionsByCategory($otherQuestions, $categoryOrders);
            $otherCategoryAnswerFrequency = SurveyReport::getAnswerFrequencyInCategories($otherCategories);
        }

        //Get the splited questions average
        $selfAndOthersAverage = [];
        if ($survey->type == \App\SurveyTypes::Progress) {
            $selfAndOthersAverage = SurveyReport::calculateSelfAndOthersAverage($survey, $selfAndOthersQuestions, $toEvaluate);
        }

        $allRoles = [];
        $selfRoleId = SurveyReport::getSelfRoleId($survey, $toEvaluate);
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

                $category->roles = \App\SurveyReport::sortByRoleId($category->roles, $survey->type);
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

    /**
    * Returns the data for the given LMTT survey
    */
    public static function getDataLMTT($survey)
    {
        $questionAndComments = SurveyReport::getQuestionAnswers($survey, $survey->recipients);
        return SurveyReport::getData($survey, $questionAndComments);
    }

    /**
    * Returns the data for the given 360 survey
    */
    public static function getData360($survey, $toEvaluate)
    {
        $isValidFn = function($answer) use (&$toEvaluate) {
            if ($toEvaluate != null) {
                return $answer->invitedById === $toEvaluate->recipientId;
            } else {
                return true;
            }
        };

        $getAnswersFn = function ($survey, $questionId) use (&$toEvaluate) {
            if ($toEvaluate != null) {
                return $survey->answers()
                    ->where('questionId', '=', $questionId)
                    ->where('invitedById', '=', $toEvaluate->recipientId)
                    ->get();
            } else {
                return $survey->answers()
                    ->where('questionId', '=', $questionId)
                    ->get();
            }
        };

        $mergeRolesFn = function ($roleNames, $answeredRecipientIds) use (&$survey) {
            return SurveyReport::mergeRoles($survey, $roleNames, $answeredRecipientIds);
        };

        $questionAndComments = SurveyReport::getQuestionAnswers(
            $survey,
            $survey->recipients,
            $isValidFn,
            $getAnswersFn,
            $mergeRolesFn);

        return SurveyReport::getData($survey, $questionAndComments, $toEvaluate);
    }

    /**
    * Returns the data for the given progress survey
    */
    public static function getDataProgress($survey, $toEvaluate)
    {
        $data = SurveyReport::getData360($survey, $toEvaluate);

        //Get the question orders
        $questionOrders = [];
        foreach ($data->questions as $question) {
            $questionOrders[$question->id] = $survey->questions()
                ->where('questionId', '=', $question->id)
                ->first()
                ->order;
        }

        //Split questions into roles
        $selfRoleId = \App\SurveyReport::getSelfRoleId($survey, $toEvaluate);
        $othersRoleName = Lang::get('roles.others');
        $othersRoleId = -1;

        $selfRole = null;
        $otherRoles = [];

        foreach ($data->questionsByRole as $role) {
            if ($selfRoleId == $role->id) {
                $selfRole = clone $role;
            } else {
                array_push($otherRoles, $role);
            }
        }

        $selfAndOtherRoles = [
            \App\SurveyReport::mergeQuestionRoles($otherRoles, -1, $othersRoleName),
            $selfRole
        ];

        foreach ($selfAndOtherRoles as $role) {
            \App\SurveyReport::sortQuestionsByOrder($role->questions, $questionOrders);
            $role->questions = \App\SurveyReport::calculateQuestionsAverage($role->questions);
        }

        $data->selfAndOtherRoles = $selfAndOtherRoles;
        $data->questionOrders = $questionOrders;
        return $data;
    }

    /**
    * Returns the data for the given normal survey
    */
    public static function getDataNormal($survey, $constraints = null)
    {
        //Get the questions and comments
        $questionAndComments = SurveyReport::getQuestionAnswers($survey, $survey->recipients, function($answer) use (&$survey, &$constraints) {
            if ($constraints !== null) {
                foreach ($constraints as $constraint) {
                    if (!$survey->hasExtraQuestionAnswer($answer->answeredById, $constraint->id, $constraint->value)) {
                        return false;
                    }
                }

                return true;
            } else {
                return true;
            }
        });

        $questions = $questionAndComments->questions;
        $comments = $questionAndComments->comments;
        $answeredRecipientIds = array_map(function ($recipient) {
            return $recipient->recipientId;
        }, $questionAndComments->answeredRecipientIds);

        $categoryOrders = SurveyReport::getCategoryOrders($survey);
        $categories = SurveyReport::groupQuestionsByCategory($questions, $categoryOrders);
        $categoryAnswerFrequency = SurveyReport::getAnswerFrequencyInCategories($categories);
        $yesOrNoQuestions = SurveyReport::getYesOrNoQuestions($questions);

        //Get the questions
        $normalQuestions = [];
        $answerRecipients = [];

        foreach ($questions as $question) {
            //Calculate the average
            $sum = 0;
            $count = 0;

            foreach ($question->answers as $answer) {
                $answerRecipients[$answer->recipientId] = $answer->recipientId;
                $sum += $answer->value;
                $count++;
            }

            if ($count > 0) {
                $question->average = $sum / $count;
            } else {
                $question->average = 0;
            }

            array_push($normalQuestions, $question);
        }

        $extraAnswersByRecipients = $survey->extraAnswersForRecipients($answeredRecipientIds);
        $extraQuestions = SurveyReport::getExtraQuestions($survey);

        $extraAnswerByQuestions = SurveyReport::extraAnswerByQuestions($questions, $extraQuestions, $extraAnswersByRecipients);
        $extraAnswersByCategories = SurveyReport::groupQuestionsByCategory($extraAnswerByQuestions, $categoryOrders, false);

        $commentsByCategory = SurveyReport::groupQuestionsByCategory($comments, $categoryOrders, false);
        $extraAnswersByCategories = SurveyReport::mergeQuestionsAndCommentsCategories($categoryOrders, $extraAnswersByCategories, $commentsByCategory);
        $extraAnswersByCategoriesSummary = SurveyReport::extraAnswersByCategoriesSummary($extraAnswersByCategories, $extraQuestions, $categories);

        $questionsByExtraAnswer = SurveyReport::questionsByExtraAnswer($questions, $extraQuestions, $extraAnswersByRecipients);
        $categoriesByExtraAnswer = [];
        foreach ($questionsByExtraAnswer as $extraQuestion) {
            $newExtraQuestion = clone $extraQuestion;
            $newExtraQuestion->values = [];

            foreach ($extraQuestion->values as $value) {
                $anyAnswers = false;
                foreach ($value->questions as $question) {
                    if (count($question->answers) > 0) {
                        $anyAnswers = true;
                        break;
                    }
                }

                if ($anyAnswers) {
                    $newValue = clone $value;
                    unset($newValue->questions);
                    $newValue->categories = SurveyReport::groupQuestionsByCategory($value->questions, $categoryOrders, true);
                    array_push($newExtraQuestion->values, $newValue);
                }
            }

            array_push($categoriesByExtraAnswer, $newExtraQuestion);
        }

        return (object)[
            'answerrecipientIds' => $answerRecipients,
            'questions' => $normalQuestions,
            'categories' => $categories,
            'categoryOrders' => $categoryOrders,
            'categoryAnswerFrequency' => $categoryAnswerFrequency,
            'yesOrNoQuestions' => $yesOrNoQuestions,
            'comments' => $comments,
            'answeredRecipientIds' => $answeredRecipientIds,
            'extraAnswersByRecipients' => $extraAnswersByRecipients,
            'extraAnswerByQuestions' => $extraAnswerByQuestions,
            'questionsByExtraAnswer' => $questionsByExtraAnswer,
            'extraAnswersByCategories' => $extraAnswersByCategories,
            'commentsByCategory' => $commentsByCategory,
            'extraAnswersByCategoriesSummary' => $extraAnswersByCategoriesSummary,
            'categoriesByExtraAnswer' => $categoriesByExtraAnswer
        ];
    }
}

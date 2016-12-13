<?php namespace App;

use Lang;
use App\AnswerType;
use App\SurveyTypes;

/**
* Contains functions for normal survey reports
*/
abstract class SurveyReportNormal
{
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
                    $value->average = SurveyReportHelpers::calculateAnswersAverage($value->answers);
                    $value->answerFrequency = [];

                    foreach (SurveyReportHelpers::getAnswerFrequencyForAnswers($value->answers, $question->answerType) as $answer => $count) {
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
    * Returns summary for the extra answers grouped by categories
    */
    private static function extraAnswersByCategoriesSummary($extraAnswersByCategories, $extraQuestions, $categories)
    {
        $extraAnswersByCategoriesSummary = [];

        foreach ($extraAnswersByCategories as $category) {
            $categoryData = SurveyReportHelpers::findCategoryById($categories, $category->id);

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
    * Creates data for the given survey
    */
    public static function create($survey, $constraints = null)
    {
        //Get the questions and comments
        $questionAndComments = SurveyReportHelpers::getQuestionAnswers($survey, $survey->recipients, function($answer) use (&$survey, &$constraints) {
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

        $categoryOrders = SurveyReportHelpers::getCategoryOrders($survey);
        $categories = SurveyReportHelpers::groupQuestionsByCategory($questions, $categoryOrders);
        $categoryAnswerFrequency = SurveyReportHelpers::getAnswerFrequencyInCategories($categories);
        $yesOrNoQuestions = SurveyReportHelpers::getYesOrNoQuestions($questions);

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
        $extraQuestions = SurveyReportNormal::getExtraQuestions($survey);

        $extraAnswerByQuestions = SurveyReportNormal::extraAnswerByQuestions($questions, $extraQuestions, $extraAnswersByRecipients);
        $extraAnswersByCategories = SurveyReportHelpers::groupQuestionsByCategory($extraAnswerByQuestions, $categoryOrders, false);

        $commentsByCategory = SurveyReportHelpers::groupQuestionsByCategory($comments, $categoryOrders, false);
        $extraAnswersByCategories = SurveyReportHelpers::mergeQuestionsAndCommentsCategories($categoryOrders, $extraAnswersByCategories, $commentsByCategory);
        $extraAnswersByCategoriesSummary = SurveyReportNormal::extraAnswersByCategoriesSummary($extraAnswersByCategories, $extraQuestions, $categories);

        $questionsByExtraAnswer = SurveyReportNormal::questionsByExtraAnswer($questions, $extraQuestions, $extraAnswersByRecipients);
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
                    $newValue->categories = SurveyReportHelpers::groupQuestionsByCategory($value->questions, $categoryOrders, true);
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

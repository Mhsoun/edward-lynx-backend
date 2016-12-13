<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Roles;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Mail;
use File;
use Log;
use DB;
use App\SurveyTypes;
use App\Models\Survey;

/**
* Controller for export survey data
*/
class SurveyExportController extends Controller
{
    /**
    * Exports the given survey using the given data exporter
    */
    private function exportData($survey, $dataExporter)
    {
        if (\App\SurveyTypes::isIndividualLike($survey->type)) {
            $dataExporter->addDataLine([
                'Answer type',
                'Answer value',
                'Normalized answer value',
                'Participant',
                'Invited by',
                'Role',
                'Question Id',
                'Category Id',
                'Category',
                'Question',
            ]);
        } else {
            $dataExporter->addDataLine([
                'Answer type',
                'Answer value',
                'Normalized answer value',
                'Participant',
                'Role',
                'Question Id',
                'Category Id',
                'Category',
                'Question',
            ]);
        }

        $questions = [];
        $roles = [];

        $currentRecipient = (object)[
            'invitedById' => 0,
            'recipientId' => 0,
            'name' => '',
            'invitedByName' => '',
            'roleId' => 0,
            'roleName' => '',
            'parserData' => []
        ];

        foreach ($survey->answers()->orderBy('invitedById', 'asc')->orderBy('answeredById', 'asc')->get() as $answer) {
            if (!array_key_exists($answer->questionId, $questions)) {
                $question = $answer->question;
                $questions[$answer->questionId] = $question;
            } else {
                $question = $questions[$answer->questionId];
            }

            if (!($currentRecipient->recipientId == $answer->answeredById
                  && $currentRecipient->invitedById == $answer->invitedById)) {
                $answeredBy = $answer->answeredBy();
                $currentRecipient->invitedById = $answer->invitedById;
                $currentRecipient->recipientId = $answer->answeredById;
                $currentRecipient->name = $answeredBy->recipient->name;
                $currentRecipient->invitedByName = $answeredBy->invitedByObj->name;
                $currentRecipient->roleId = $answeredBy->roleId;

                if (!array_key_exists($currentRecipient->roleId, $roles)) {
                    $roleName = \App\Roles::name($currentRecipient->roleId);
                    $roles[$currentRecipient->roleId] = $roleName;
                } else {
                    $roleName = $roles[$currentRecipient->roleId];
                }

                $currentRecipient->roleName = $roleName;

                $currentRecipient->parserData = \App\EmailContentParser::createParserData(
                    $survey,
                    $answeredBy);
            }

            $answerType = $question->answerTypeObject();
            $answerValue = null;
            $normalizedAnswerValue = "";

            if ($answerType->isText()) {
                $answerValue = $answer->answerText;
                $answerValue = preg_replace('/(\\r\\n)|(\\n)/', ' ', $answerValue);
                $answerValue = preg_replace('/"/', '""', $answerValue);
                $answerValue = $dataExporter->escapeText($answerValue);
            } else {
                $answerValue = $answerType->descriptionOfValue($answer->answerValue);

                if ($answer->answerValue != \App\AnswerType::NA_VALUE) {
                    $normalizedAnswerValue = round(100 * ($answer->answerValue / $answerType->maxValue()));
                }
            }

            $categoryName = $dataExporter->escapeText($question->category->title);
            $questionText = $dataExporter->escapeText(
                \App\EmailContentParser::parse($question->text, $currentRecipient->parserData, true));

            if (\App\SurveyTypes::isIndividualLike($survey->type)) {
                $dataExporter->addDataLine([
                    $answerType->descriptionText(),
                    $answerValue,
                    $normalizedAnswerValue,
                    $currentRecipient->name,
                    $currentRecipient->invitedByName,
                    $currentRecipient->roleName,
                    $answer->questionId,
                    $question->categoryId,
                    $categoryName,
                    $questionText
                ]);
            } else {
                $dataExporter->addDataLine([
                    $answerType->descriptionText(),
                    $answerValue,
                    $normalizedAnswerValue,
                    $currentRecipient->name,
                    $currentRecipient->roleName,
                    $answer->questionId,
                    $question->categoryId,
                    $categoryName,
                    $questionText
                ]);
            }
        }
    }

    /**
    * Exports the given survey to CSV
    */
    public function exportCSV(Request $request, $id)
    {
        $survey = Survey::findOrFail($id);

        $dataExporter = new \App\CSVDataExporter();
        $this->exportData($survey, $dataExporter);

        return response(mb_convert_encoding($dataExporter->getData(), "UTF-8"))
            ->header('Content-Type', 'text/cvs; charset=utf-8')
            ->header(
                'Content-disposition',
                'attachment; filename="survey_' . $survey->id . '_' . \Carbon\Carbon::now()->format('YmdHi') . '_data.csv"');
    }

    /**
    * Exports the given survey to excel
    */
    public function exportExcel(Request $request, $id)
    {
        $survey = Survey::findOrFail($id);

        $dataExporter = new \App\ExcelDataExporter();
        $this->exportData($survey, $dataExporter);

        return response($dataExporter->getData())
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header(
                'Content-disposition',
                'attachment; filename="survey_' . $survey->id . '_' . \Carbon\Carbon::now()->format('YmdHi') . '_data.xlsx"');
    }
}

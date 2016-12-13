<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use \App\Models\Survey;
use \App\SurveyTypes;
use \App\Models\SurveyQuestion;
use \App\Models\SurveyQuestionTargetRole;

class ChangedTargetRoles extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//Store current target role ids
		$questionsToRoleId = [];
		foreach (Survey::whereRaw('type IN (?, ?)', [SurveyTypes::Group, SurveyTypes::LTT])->get() as $survey) {
			foreach ($survey->questions as $question) {
				$questionsToRoleId[$question->surveyId . ':' . $question->questionId] = $question->targetRoleId;
			}
		}

		Schema::table('survey_questions', function (Blueprint $table)
		{
			$table->dropForeign('survey_questions_targetroleid_foreign');
			$table->dropColumn('targetRoleId');
		});

		Schema::create('survey_question_target_roles', function (Blueprint $table)
		{
			$table->integer('surveyId')->unsigned();
			$table->foreign('surveyId')
				->references('id')
				->on('surveys')
				->onDelete('cascade');

			$table->integer('questionId')->unsigned();
			$table->foreign('questionId')
				->references('id')
				->on('questions')
				->onDelete('cascade');

			$table->integer('roleId')->unsigned();
			$table->foreign('roleId')
				->references('id')
				->on('roles')
				->onDelete('cascade');

			$table->primary(['surveyId', 'questionId', 'roleId']);
		});

		//Create roles
		foreach ($questionsToRoleId as $question => $targetRoleId) {
			$questionData = explode(':', $question);
			$surveyId = intval($questionData[0]);
			$questionId = intval($questionData[1]);

			$surveyQuestion = SurveyQuestion::
				where('surveyId', '=', $surveyId)
				->where('questionId', '=', $questionId)
				->first();

			$roles = [];

			if ($targetRoleId != null) {
				array_push($roles, $targetRoleId);
			} else {
				$roles = \App\Roles::getLMTT()->map(function ($role) {
					return $role->id;
				});
			}

			foreach ($roles as $roleId) {
				$targetRole = new SurveyQuestionTargetRole;
				$targetRole->surveyId = $surveyId;
				$targetRole->questionId = $questionId;
				$targetRole->roleId = $roleId;
				$surveyQuestion->targetRoles()->save($targetRole);
			}
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('survey_questions', function (Blueprint $table)
		{
			$table->integer('targetRoleId')
				->unsigned()
				->nullable();

			$table->foreign('targetRoleId')
            	->references('id')
            	->on('roles')
            	->onDelete('cascade');
		});

		Schema::drop('survey_question_target_roles');
	}
}

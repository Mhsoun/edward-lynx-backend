<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CopiedSurveyCategories extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		foreach (\App\Models\Survey::all() as $survey) {
			// $survey = \App\Models\Survey::find(230);
			$questionMapping = [];

			//Question and categories
			foreach ($survey->categories as $category) {
				$newCategory = $category->category->copy();

				DB::table('survey_question_categories')
					->where('surveyId', $survey->id)
					->where('categoryId', $category->categoryId)
					->update(['categoryId' => $newCategory->id]);

				foreach ($category->questions as $question) {
					$newQuestion = $question->question->copy(true, $newCategory->id);
					$questionMapping[$question->questionId] = $newQuestion->id;

					DB::table('survey_questions')
		                ->where('surveyId', $survey->id)
		                ->where('questionId', $question->questionId)
		                ->update(['questionId' => $newQuestion->id]);
				}
			}

			//Answers
			foreach ($survey->answers as $answer) {
				$answer->questionId = $questionMapping[$answer->questionId];
				$answer->save();
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
		//
	}
}

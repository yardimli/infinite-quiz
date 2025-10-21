<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Run the migrations.
		 *
		 * @return void
		 */
		public function up(): void
		{
			Schema::table('quizzes', function (Blueprint $table) {
// Added: Column to store the number of correct answers needed to complete the quiz.
				$table->unsignedInteger('question_goal')->default(20)->after('llm_model');
			});
		}

		/**
		 * Reverse the migrations.
		 *
		 * @return void
		 */
		public function down(): void
		{
			Schema::table('quizzes', function (Blueprint $table) {
// Added: Reverses the migration by dropping the new column.
				$table->dropColumn('question_goal');
			});
		}
	};

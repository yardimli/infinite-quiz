<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Run the migrations.
		 */
		public function up(): void
		{
			Schema::table('quizzes', function (Blueprint $table) {
				// Add the new 'answer_count' column after the 'question_goal' column.
				// It's an unsigned tiny integer because the value will be small and positive (2-6).
				// A default value of 4 is set to ensure compatibility with existing quizzes.
				$table->unsignedTinyInteger('answer_count')->default(4)->after('question_goal');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('quizzes', function (Blueprint $table) {
				// This will remove the 'answer_count' column if the migration is rolled back.
				$table->dropColumn('answer_count');
			});
		}
	};

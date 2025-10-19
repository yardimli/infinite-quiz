<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		public function up(): void
		{
			Schema::table('quizzes', function (Blueprint $table) {
				$table->string('llm_model')->after('prompt')->nullable();
			});
		}

		public function down(): void
		{
			Schema::table('quizzes', function (Blueprint $table) {
				$table->dropColumn('llm_model');
			});
		}
	};

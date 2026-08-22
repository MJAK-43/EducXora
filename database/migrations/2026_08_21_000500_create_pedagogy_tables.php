<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learners', function (Blueprint $table): void {
            $table->string('current_level', 2)->nullable()->after('initial_level');
        });
        DB::statement('UPDATE learners SET current_level = initial_level WHERE current_level IS NULL');
        DB::statement('ALTER TABLE learners ALTER COLUMN current_level SET NOT NULL');
        DB::statement("ALTER TABLE learners ADD CONSTRAINT learners_current_level_check CHECK (current_level IN ('A1','A2','B1','B2','C1','C2'))");

        Schema::create('placement_questions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('source', 20);
            $table->string('language', 10)->default('de');
            $table->string('level', 2);
            $table->text('prompt');
            $table->jsonb('choices');
            $table->string('correct_choice', 1);
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['organization_id', 'language', 'level', 'status']);
            $table->index(['source', 'language', 'level', 'status']);
        });

        Schema::create('placement_attempts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('learner_id');
            $table->string('status', 20)->default('started');
            $table->string('language', 10)->default('de');
            $table->unsignedSmallInteger('question_count');
            $table->string('scoring_version', 30);
            $table->unsignedSmallInteger('raw_score')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('suggested_level', 2)->nullable();
            $table->unsignedBigInteger('suggested_group_id')->nullable();
            $table->string('validated_level', 2)->nullable();
            $table->unsignedBigInteger('validated_group_id')->nullable();
            $table->text('review_reason')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('started_at');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'id']);
            $table->foreign(['organization_id', 'learner_id'])->references(['organization_id', 'id'])->on('learners')->restrictOnDelete();
            $table->foreign(['organization_id', 'suggested_group_id'])->references(['organization_id', 'id'])->on('groups')->restrictOnDelete();
            $table->foreign(['organization_id', 'validated_group_id'])->references(['organization_id', 'id'])->on('groups')->restrictOnDelete();
            $table->index(['organization_id', 'learner_id', 'started_at']);
            $table->index(['organization_id', 'status', 'completed_at']);
        });

        Schema::create('placement_attempt_questions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('attempt_id');
            $table->foreignId('source_question_id')->constrained('placement_questions')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('prompt_snapshot');
            $table->jsonb('choices_snapshot');
            $table->string('correct_choice_snapshot', 1);
            $table->string('level_snapshot', 2);
            $table->string('selected_choice', 1)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('answered_at')->nullable();
            $table->timestampsTz();

            $table->foreign(['organization_id', 'attempt_id'])->references(['organization_id', 'id'])->on('placement_attempts')->cascadeOnDelete();
            $table->unique(['attempt_id', 'position']);
            $table->unique(['attempt_id', 'source_question_id']);
            $table->index(['organization_id', 'attempt_id']);
        });

        Schema::create('learner_level_histories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('learner_id');
            $table->string('from_level', 2);
            $table->string('to_level', 2);
            $table->string('source', 30);
            $table->unsignedBigInteger('placement_attempt_id')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign(['organization_id', 'learner_id'])->references(['organization_id', 'id'])->on('learners')->restrictOnDelete();
            $table->foreign(['organization_id', 'placement_attempt_id'])->references(['organization_id', 'id'])->on('placement_attempts')->restrictOnDelete();
            $table->index(['organization_id', 'learner_id', 'occurred_at']);
        });

        DB::statement("ALTER TABLE placement_questions ADD CONSTRAINT placement_questions_source_check CHECK ((source = 'system' AND organization_id IS NULL) OR (source = 'organization' AND organization_id IS NOT NULL))");
        DB::statement("ALTER TABLE placement_questions ADD CONSTRAINT placement_questions_language_check CHECK (language = 'de')");
        DB::statement("ALTER TABLE placement_questions ADD CONSTRAINT placement_questions_level_check CHECK (level IN ('A1','A2','B1','B2','C1','C2'))");
        DB::statement("ALTER TABLE placement_questions ADD CONSTRAINT placement_questions_choice_check CHECK (correct_choice IN ('A','B','C','D'))");
        DB::statement("ALTER TABLE placement_questions ADD CONSTRAINT placement_questions_status_check CHECK (status IN ('active','inactive'))");
        DB::statement("ALTER TABLE placement_questions ADD CONSTRAINT placement_questions_choices_shape_check CHECK (jsonb_typeof(choices) = 'object' AND jsonb_exists_all(choices, ARRAY['A','B','C','D']) AND choices - ARRAY['A','B','C','D'] = '{}'::jsonb)");
        DB::statement("ALTER TABLE placement_questions ADD CONSTRAINT placement_questions_disabled_state_check CHECK ((status = 'active' AND disabled_at IS NULL AND disabled_by IS NULL) OR (status = 'inactive' AND disabled_at IS NOT NULL))");
        DB::statement("ALTER TABLE placement_attempts ADD CONSTRAINT placement_attempts_status_check CHECK (status IN ('started','completed','reviewed'))");
        DB::statement("ALTER TABLE placement_attempts ADD CONSTRAINT placement_attempts_language_check CHECK (language = 'de')");
        DB::statement('ALTER TABLE placement_attempts ADD CONSTRAINT placement_attempts_question_count_check CHECK (question_count BETWEEN 15 AND 30)');
        DB::statement("ALTER TABLE placement_attempts ADD CONSTRAINT placement_attempts_levels_check CHECK ((suggested_level IS NULL OR suggested_level IN ('A1','A2','B1','B2','C1','C2')) AND (validated_level IS NULL OR validated_level IN ('A1','A2','B1','B2','C1','C2')))");
        DB::statement("ALTER TABLE placement_attempts ADD CONSTRAINT placement_attempts_state_check CHECK ((status = 'started' AND raw_score IS NULL AND percentage IS NULL AND suggested_level IS NULL AND completed_at IS NULL AND validated_level IS NULL AND reviewed_at IS NULL) OR (status = 'completed' AND raw_score IS NOT NULL AND percentage IS NOT NULL AND suggested_level IS NOT NULL AND completed_at IS NOT NULL AND validated_level IS NULL AND reviewed_at IS NULL) OR (status = 'reviewed' AND raw_score IS NOT NULL AND percentage IS NOT NULL AND suggested_level IS NOT NULL AND completed_at IS NOT NULL AND validated_level IS NOT NULL AND reviewed_at IS NOT NULL))");
        DB::statement("CREATE UNIQUE INDEX placement_attempts_one_started_per_learner ON placement_attempts (organization_id, learner_id) WHERE status = 'started'");
        DB::statement("ALTER TABLE placement_attempt_questions ADD CONSTRAINT placement_attempt_questions_choices_check CHECK ((correct_choice_snapshot IN ('A','B','C','D')) AND (selected_choice IS NULL OR selected_choice IN ('A','B','C','D')) AND level_snapshot IN ('A1','A2','B1','B2','C1','C2'))");
        DB::statement('ALTER TABLE placement_attempt_questions ADD CONSTRAINT placement_attempt_questions_answer_state_check CHECK ((selected_choice IS NULL AND is_correct IS NULL AND answered_at IS NULL AND answered_by IS NULL) OR (selected_choice IS NOT NULL AND is_correct IS NOT NULL AND answered_at IS NOT NULL))');
        DB::statement("ALTER TABLE learner_level_histories ADD CONSTRAINT learner_level_histories_levels_check CHECK (from_level IN ('A1','A2','B1','B2','C1','C2') AND to_level IN ('A1','A2','B1','B2','C1','C2') AND from_level <> to_level)");
        DB::statement("ALTER TABLE learner_level_histories ADD CONSTRAINT learner_level_histories_source_check CHECK (source IN ('placement_test','teacher_evaluation','director_override'))");
        DB::statement("ALTER TABLE learner_level_histories ADD CONSTRAINT learner_level_histories_attempt_check CHECK ((source = 'teacher_evaluation' AND placement_attempt_id IS NULL) OR (source IN ('placement_test','director_override') AND placement_attempt_id IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_level_histories');
        Schema::dropIfExists('placement_attempt_questions');
        Schema::dropIfExists('placement_attempts');
        Schema::dropIfExists('placement_questions');
        Schema::table('learners', function (Blueprint $table): void {
            $table->dropColumn('current_level');
        });
    }
};

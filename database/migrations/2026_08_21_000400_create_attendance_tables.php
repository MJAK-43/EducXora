<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sheets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('course_session_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('teacher_membership_id');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'id', 'teacher_membership_id']);
            $table->unique(['organization_id', 'course_session_id']);
            $table->foreign(['organization_id', 'course_session_id'])
                ->references(['organization_id', 'id'])->on('course_sessions')->restrictOnDelete();
            $table->foreign(['organization_id', 'group_id'])
                ->references(['organization_id', 'id'])->on('groups')->restrictOnDelete();
            $table->foreign(['teacher_membership_id', 'organization_id'])
                ->references(['id', 'organization_id'])->on('organization_user')->restrictOnDelete();
            $table->index(['organization_id', 'status', 'validated_at']);
            $table->index(['organization_id', 'teacher_membership_id', 'validated_at']);
            $table->index(['organization_id', 'group_id', 'validated_at']);
        });

        Schema::create('learner_attendances', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('attendance_sheet_id');
            $table->unsignedBigInteger('learner_id');
            $table->string('status', 20)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('recorded_at')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'id', 'attendance_sheet_id']);
            $table->unique(['organization_id', 'attendance_sheet_id', 'learner_id']);
            $table->foreign(['organization_id', 'attendance_sheet_id'])
                ->references(['organization_id', 'id'])->on('attendance_sheets')->cascadeOnDelete();
            $table->foreign(['organization_id', 'learner_id'])
                ->references(['organization_id', 'id'])->on('learners')->restrictOnDelete();
            $table->index(['organization_id', 'learner_id', 'status']);
            $table->index(['organization_id', 'attendance_sheet_id', 'status']);
        });

        Schema::create('teacher_attendances', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('attendance_sheet_id');
            $table->unsignedBigInteger('teacher_membership_id');
            $table->string('status', 20);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('recorded_at');
            $table->timestampsTz();

            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'id', 'attendance_sheet_id']);
            $table->unique(['organization_id', 'attendance_sheet_id']);
            $table->foreign(['organization_id', 'attendance_sheet_id'])
                ->references(['organization_id', 'id'])->on('attendance_sheets')->cascadeOnDelete();
            $table->foreign(['organization_id', 'attendance_sheet_id', 'teacher_membership_id'])
                ->references(['organization_id', 'id', 'teacher_membership_id'])->on('attendance_sheets')->cascadeOnDelete();
            $table->foreign(['teacher_membership_id', 'organization_id'])
                ->references(['id', 'organization_id'])->on('organization_user')->restrictOnDelete();
            $table->index(['organization_id', 'teacher_membership_id', 'recorded_at']);
        });

        Schema::create('attendance_corrections', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('attendance_sheet_id');
            $table->unsignedBigInteger('learner_attendance_id')->nullable();
            $table->unsignedBigInteger('teacher_attendance_id')->nullable();
            $table->string('before_status', 20);
            $table->string('after_status', 20);
            $table->string('reason', 500);
            $table->foreignId('corrected_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at');

            $table->unique(['organization_id', 'id']);
            $table->foreign(['organization_id', 'attendance_sheet_id'])
                ->references(['organization_id', 'id'])->on('attendance_sheets')->cascadeOnDelete();
            $table->foreign(['organization_id', 'learner_attendance_id'])
                ->references(['organization_id', 'id'])->on('learner_attendances')->restrictOnDelete();
            $table->foreign(['organization_id', 'learner_attendance_id', 'attendance_sheet_id'])
                ->references(['organization_id', 'id', 'attendance_sheet_id'])->on('learner_attendances')->restrictOnDelete();
            $table->foreign(['organization_id', 'teacher_attendance_id'])
                ->references(['organization_id', 'id'])->on('teacher_attendances')->restrictOnDelete();
            $table->foreign(['organization_id', 'teacher_attendance_id', 'attendance_sheet_id'])
                ->references(['organization_id', 'id', 'attendance_sheet_id'])->on('teacher_attendances')->restrictOnDelete();
            $table->index(['organization_id', 'attendance_sheet_id', 'created_at']);
            $table->index(['organization_id', 'learner_attendance_id', 'created_at']);
            $table->index(['organization_id', 'teacher_attendance_id', 'created_at']);
        });

        DB::statement('ALTER TABLE attendance_sheets ADD CONSTRAINT attendance_sheets_status_check CHECK (status IN (\'draft\',\'validated\'))');

        DB::statement('ALTER TABLE attendance_sheets ADD CONSTRAINT attendance_sheets_validation_check CHECK ((status = \'draft\' AND validated_at IS NULL AND validated_by IS NULL) OR (status = \'validated\' AND validated_at IS NOT NULL AND validated_by IS NOT NULL))');
        DB::statement('ALTER TABLE learner_attendances ADD CONSTRAINT learner_attendances_status_check CHECK (status IS NULL OR status IN (\'present\',\'absent\',\'excused\'))');
        DB::statement('ALTER TABLE learner_attendances ADD CONSTRAINT learner_attendances_recording_check CHECK ((status IS NULL AND recorded_at IS NULL) OR (status IS NOT NULL AND recorded_at IS NOT NULL))');

        DB::statement('ALTER TABLE teacher_attendances ADD CONSTRAINT teacher_attendances_status_check CHECK (status IN (\'present\',\'absent\',\'excused\'))');
        DB::statement('ALTER TABLE attendance_corrections ADD CONSTRAINT attendance_corrections_subject_check CHECK (num_nonnulls(learner_attendance_id, teacher_attendance_id) = 1)');
        DB::statement('ALTER TABLE attendance_corrections ADD CONSTRAINT attendance_corrections_status_check CHECK (before_status IN (\'present\',\'absent\',\'excused\') AND after_status IN (\'present\',\'absent\',\'excused\') AND before_status <> after_status)');
        DB::statement('ALTER TABLE attendance_corrections ADD CONSTRAINT attendance_corrections_reason_check CHECK (length(btrim(reason)) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('teacher_attendances');
        Schema::dropIfExists('learner_attendances');
        Schema::dropIfExists('attendance_sheets');
    }
};

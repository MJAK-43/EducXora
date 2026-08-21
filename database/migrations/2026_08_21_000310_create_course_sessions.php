<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::create('course_sessions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('teacher_membership_id');
            $table->string('room', 120)->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('status', 20)->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['organization_id', 'id']);
            $table->foreign(['organization_id', 'group_id'])
                ->references(['organization_id', 'id'])->on('groups')->restrictOnDelete();
            $table->foreign(['teacher_membership_id', 'organization_id'])
                ->references(['id', 'organization_id'])->on('organization_user')->restrictOnDelete();
            $table->index(['organization_id', 'starts_at', 'ends_at']);
            $table->index(['organization_id', 'group_id', 'starts_at']);
            $table->index(['organization_id', 'teacher_membership_id', 'starts_at']);
        });

        DB::statement("ALTER TABLE course_sessions ADD CONSTRAINT course_sessions_status_check CHECK (status IN ('scheduled','cancelled'))");
        DB::statement('ALTER TABLE course_sessions ADD CONSTRAINT course_sessions_time_check CHECK (ends_at > starts_at)');
        DB::statement("ALTER TABLE course_sessions ADD CONSTRAINT course_sessions_group_no_overlap EXCLUDE USING gist (organization_id WITH =, group_id WITH =, tstzrange(starts_at, ends_at, '[)') WITH &&) WHERE (status = 'scheduled')");
        DB::statement("ALTER TABLE course_sessions ADD CONSTRAINT course_sessions_teacher_no_overlap EXCLUDE USING gist (organization_id WITH =, teacher_membership_id WITH =, tstzrange(starts_at, ends_at, '[)') WITH &&) WHERE (status = 'scheduled')");
        DB::statement("ALTER TABLE course_sessions ADD CONSTRAINT course_sessions_room_no_overlap EXCLUDE USING gist (organization_id WITH =, lower(room) WITH =, tstzrange(starts_at, ends_at, '[)') WITH &&) WHERE (status = 'scheduled' AND room IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_user', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable();
        });

        DB::table('organization_user')->whereNull('uuid')->orderBy('id')->each(
            fn (object $membership) => DB::table('organization_user')
                ->where('id', $membership->id)
                ->update(['uuid' => (string) Str::uuid7()]),
        );

        Schema::table('organization_user', function (Blueprint $table): void {
            $table->unique('uuid');
        });
        DB::statement('ALTER TABLE organization_user ALTER COLUMN uuid SET NOT NULL');

        Schema::create('groups', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('language', 10);
            $table->string('level', 2);
            $table->unsignedSmallInteger('capacity');
            $table->unsignedBigInteger('teacher_membership_id');
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['organization_id', 'id']);
            $table->foreign(['teacher_membership_id', 'organization_id'])
                ->references(['id', 'organization_id'])->on('organization_user')->restrictOnDelete();
            $table->index(['organization_id', 'status', 'name']);
            $table->index(['organization_id', 'teacher_membership_id', 'status']);
        });

        Schema::create('group_learner_assignments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('learner_id');
            $table->timestampTz('assigned_at');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('detached_at')->nullable();
            $table->foreignId('detached_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['organization_id', 'id']);
            $table->foreign(['organization_id', 'group_id'])
                ->references(['organization_id', 'id'])->on('groups')->restrictOnDelete();
            $table->foreign(['organization_id', 'learner_id'])
                ->references(['organization_id', 'id'])->on('learners')->restrictOnDelete();
            $table->index(['organization_id', 'group_id', 'detached_at']);
            $table->index(['organization_id', 'learner_id', 'detached_at']);
        });

        DB::statement("ALTER TABLE groups ADD CONSTRAINT groups_level_check CHECK (level IN ('A1','A2','B1','B2','C1','C2'))");
        DB::statement("ALTER TABLE groups ADD CONSTRAINT groups_language_check CHECK (language IN ('de'))");
        DB::statement("ALTER TABLE groups ADD CONSTRAINT groups_status_check CHECK (status IN ('active','archived'))");
        DB::statement('ALTER TABLE groups ADD CONSTRAINT groups_capacity_check CHECK (capacity > 0)');
        DB::statement('CREATE UNIQUE INDEX group_assignments_one_active_group_per_learner ON group_learner_assignments (organization_id, learner_id) WHERE detached_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('group_learner_assignments');
        Schema::dropIfExists('groups');
        Schema::table('organization_user', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};

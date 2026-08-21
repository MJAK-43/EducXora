<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learners', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('birth_date');
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->string('language', 10)->default('de');
            $table->string('initial_level', 2);
            $table->date('registered_on')->default(DB::raw('CURRENT_DATE'));
            $table->string('photo_path', 512)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'status', 'registered_on']);
            $table->index(['organization_id', 'last_name', 'first_name']);
            $table->index(['organization_id', 'phone']);
            $table->index(['organization_id', 'email']);
        });

        DB::statement("ALTER TABLE learners ADD CONSTRAINT learners_level_check CHECK (initial_level IN ('A1','A2','B1','B2','C1','C2'))");
        DB::statement("ALTER TABLE learners ADD CONSTRAINT learners_status_check CHECK (status IN ('active','archived'))");
        DB::statement("ALTER TABLE learners ADD CONSTRAINT learners_language_check CHECK (language IN ('de'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('learners');
    }
};

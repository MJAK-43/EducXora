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
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone', 32)->nullable()->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->timestampTz('last_login_at')->nullable();
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'uuid' => (string) Str::uuid7(),
                'first_name' => $user->name,
                'last_name' => '',
            ]);
        });

        DB::statement('ALTER TABLE users ALTER COLUMN uuid SET NOT NULL');
        Schema::table('users', function (Blueprint $table): void {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropUnique(['phone']);
            $table->dropColumn([
                'uuid',
                'first_name',
                'last_name',
                'phone',
                'status',
                'last_login_at',
            ]);
        });
    }
};

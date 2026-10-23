<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('short_id', 5)->unique()->comment('3-5 digit numeric public ID');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('name');
            $table->string('avatar_url')->nullable();
            $table->text('public_key')->nullable()->comment('ECDH P-256 public key, base64');
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('blocked_at')->nullable();
            $table->unsignedInteger('report_count')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();

            $table->index('short_id');
            $table->index('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
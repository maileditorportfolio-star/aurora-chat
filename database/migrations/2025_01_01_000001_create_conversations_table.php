<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->enum('type', ['dm', 'group']);
            $table->string('title')->nullable();
            $table->timestamp('ttl_expires_at')->nullable()->comment('72h auto-purge');
            $table->timestamps();

            $table->index('type');
            $table->index('ttl_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            // Ciphertext ONLY — server never sees plaintext
            $table->longText('ciphertext')->comment('AES-GCM encrypted payload, base64');
            $table->string('sender_ephemeral_pubkey', 200)->nullable()->comment('ECDH ephemeral public key for this message');
            $table->string('iv', 24)->nullable()->comment('AES-GCM IV, base64');
            $table->string('mac', 64)->nullable()->comment('HMAC for integrity verification');
            $table->string('message_id', 36)->nullable()->unique()->comment('client-generated UUID for dedup');
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('ttl_expires_at')->nullable()->comment('72h TTL');
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('sender_id');
            $table->index('ttl_expires_at');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
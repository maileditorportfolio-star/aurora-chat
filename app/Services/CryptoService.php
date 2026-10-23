<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Server-side crypto helpers.
 *
 * IMPORTANT: The actual E2E encryption happens in the browser (WebCrypto).
 * This service only handles:
 *   - HMAC integrity verification
 *   - Key derivation for server-side message TTL / dedup
 *   - Server never decrypts message content
 */
class CryptoService
{
    /**
     * HMAC-SHA256 for message integrity.
     * Client computes this over the ciphertext; server stores & verifies.
     */
    public function hmac(string $data, string $key): string
    {
        return hash_hmac('sha256', $data, $key, true);
    }

    public function hmacBase64(string $data, string $key): string
    {
        return base64_encode($this->hmac($data, $key));
    }

    /**
     * Generate a server-side dedup key from a client message UUID.
     */
    public function dedupKey(string $messageId): string
    {
        return hash('sha256', $messageId);
    }

    /**
     * Generate a random server nonce for rate limiting / anti-replay.
     */
    public function nonce(int $length = 16): string
    {
        return Str::random($length);
    }

    /**
     * Derive a conversation-level encryption seed from the conversation UUID.
     * Used to derive per-message keys server-side if needed (but server never decrypts).
     */
    public function conversationSeed(string $conversationUuid): string
    {
        return hash('sha256', 'aurora-chat-seed:' . $conversationUuid, true);
    }
}
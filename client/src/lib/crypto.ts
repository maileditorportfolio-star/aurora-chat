const subtle = globalThis.crypto.subtle;

export type EncryptedMessage = {
  ciphertext: string;
  iv: string;
  senderEphemeralPubkey: string;
};

const encode = (value: ArrayBufferLike) =>
  btoa(String.fromCharCode(...Array.from(new Uint8Array(value as ArrayBuffer))));

const decode = (value: string) =>
  Uint8Array.from(atob(value), (char) => char.charCodeAt(0));

export async function createIdentity() {
  const keyPair = await subtle.generateKey(
    { name: 'ECDH', namedCurve: 'P-256' },
    true,
    ['deriveKey'],
  );
  const publicKey = await subtle.exportKey('jwk', keyPair.publicKey);
  const privateKey = await subtle.exportKey('jwk', keyPair.privateKey);
  return { publicKey, privateKey };
}

async function importPublicKey(jwk: JsonWebKey) {
  return subtle.importKey('jwk', jwk, { name: 'ECDH', namedCurve: 'P-256' }, true, []);
}

async function importPrivateKey(jwk: JsonWebKey) {
  return subtle.importKey('jwk', jwk, { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveKey']);
}

async function deriveConversationKey(privateJwk: JsonWebKey, peerPublicJwk: JsonWebKey) {
  const privateKey = await importPrivateKey(privateJwk);
  const publicKey = await importPublicKey(peerPublicJwk);
  return subtle.deriveKey(
    { name: 'ECDH', public: publicKey },
    privateKey,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt'],
  );
}

export async function encryptMessage(
  plaintext: string,
  privateJwk: JsonWebKey,
  peerPublicJwk: JsonWebKey,
): Promise<EncryptedMessage> {
  const key = await deriveConversationKey(privateJwk, peerPublicJwk);
  const iv = crypto.getRandomValues(new Uint8Array(12)) as Uint8Array<ArrayBuffer>;
  const encrypted = await subtle.encrypt(
    { name: 'AES-GCM', iv: iv as unknown as ArrayBuffer },
    key,
    new TextEncoder().encode(plaintext),
  );
  return {
    ciphertext: encode(encrypted),
    iv: encode(iv.buffer as ArrayBuffer),
    senderEphemeralPubkey: JSON.stringify(peerPublicJwk),
  };
}

export async function decryptMessage(
  message: EncryptedMessage,
  privateJwk: JsonWebKey,
): Promise<string> {
  const peerPublicJwk = JSON.parse(message.senderEphemeralPubkey) as JsonWebKey;
  const key = await deriveConversationKey(privateJwk, peerPublicJwk);
  const decrypted = await subtle.decrypt(
    { name: 'AES-GCM', iv: decode(message.iv) },
    key,
    decode(message.ciphertext),
  );
  return new TextDecoder().decode(decrypted);
}

export async function saveIdentity(identity: Awaited<ReturnType<typeof createIdentity>>) {
  localStorage.setItem('aurora-identity', JSON.stringify(identity));
}

export function loadIdentity(): Awaited<ReturnType<typeof createIdentity>> | null {
  const raw = localStorage.getItem('aurora-identity');
  return raw ? JSON.parse(raw) : null;
}

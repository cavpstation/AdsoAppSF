<?php

namespace Illuminate\Encryption;

use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class Encrypter implements EncrypterContract
{
    /**
     * The encryption key.
     *
     * @var string
     */
    protected $key;

    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher = 'AES-128-CBC';

    /**
     * The block size of the cipher.
     *
     * @var int
     */
    protected $block = 16;

    /**
     * Create a new encrypter instance.
     *
     * @param  string  $key
     * @param  string|null  $cipher
     * @return void
     */
    public function __construct($key, $cipher = null)
    {
        $this->key = (string) $key;

        if ($cipher) {
            $this->cipher = $cipher;
            $this->block = openssl_cipher_iv_length($this->cipher);
        }
    }

    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @return string
     */
    public function encrypt($value)
    {
        $iv = openssl_random_pseudo_bytes($this->getIvSize());

        $value = openssl_encrypt(serialize($value), $this->cipher, $this->key, 0, $iv);

        // Once we have the encrypted value we will go ahead base64_encode the input
        // vector and create the MAC for the encrypted value so we can verify its
        // authenticity. Then, we'll JSON encode the data in a "payload" array.
        $mac = $this->hash($iv = base64_encode($iv), $value);

        return base64_encode(json_encode(compact('iv', 'value', 'mac')));
    }
    /**
     * Decrypt the given value.
     *
     * @param  string  $payload
     * @return string
     */
    public function decrypt($payload)
    {
        $payload = $this->getJsonPayload($payload);

        $iv = base64_decode($payload['iv']);

        $decrypted = openssl_decrypt($payload['value'], $this->cipher, $this->key, 0, $iv);

        if ($decrypted  === false) {
            throw new DecryptException('Could not decrypt data.');
        }

        return unserialize($decrypted);
    }

    /**
     * Create a MAC for the given value.
     *
     * @param  string  $iv
     * @param  string  $value
     * @return string
     */
    protected function hash($iv, $value)
    {
        return hash_hmac('sha256', $iv.$value, $this->key);
    }

    /**
     * Get the JSON array from the given payload.
     *
     * @param  string  $payload
     * @return array
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    protected function getJsonPayload($payload)
    {
        $payload = json_decode(base64_decode($payload), true);

        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value. We'll also check the MAC for this encryption.
        if (!$payload || $this->invalidPayload($payload)) {
            throw new DecryptException('Invalid data.');
        }

        if (!$this->validMac($payload)) {
            throw new DecryptException('MAC is invalid.');
        }

        return $payload;
    }

    /**
     * Verify that the encryption payload is valid.
     *
     * @param  array|mixed  $data
     * @return bool
     */
    protected function invalidPayload($data)
    {
        return !is_array($data) || !isset($data['iv']) || !isset($data['value']) || !isset($data['mac']);
    }

    /**
     * Determine if the MAC for the given payload is valid.
     *
     * @param  array  $payload
     * @return bool
     *
     * @throws \RuntimeException
     */
    protected function validMac(array $payload)
    {
        $bytes = Str::randomBytes(16);

        $calcMac = hash_hmac('sha256', $this->hash($payload['iv'], $payload['value']), $bytes, true);

        return Str::equals(hash_hmac('sha256', $payload['mac'], $bytes, true), $calcMac);
    }

    /**
     * Get the IV size for the cipher.
     *
     * @return int
     */
    protected function getIvSize()
    {
        return $this->block;
    }
}

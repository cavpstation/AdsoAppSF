<?php
/**
 * Created by PhpStorm.
 * User: serabalint
 * Date: 2018. 05. 26.
 * Time: 19:14
 */

namespace Illuminate\Encryption\Strategies;
use Illuminate\Encryption\CipherMethods;


/**
 * Class Aes256CBC
 * @package Illuminate\Encryption\Strategies
 */
class Aes256CBC implements CipherMethodStrategy
{
    use CipherMethods;
    /**
     * @var string
     */
    protected $key;

    /**
     * Length of key
     */
    const LENGTH = 32;
    /**
     * Name of this cipher method
     */
    const CIPHER = 'AES-256-CBC';

    /**
     * Aes256CBC constructor.
     * @param string|null $key
     */
    public function __construct(string $key = null)
    {
        if ($key) {
            $this->key = $key;
        } else {
            $this->generateKey();
        }
    }

    /**
     * @return string
     */
    public function getCipher(): string
    {
        return self::CIPHER;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return self::LENGTH;
    }

    /**
     *
     */
    public function generateKey()
    {
        $this->key = random_bytes(self::LENGTH);
    }
}
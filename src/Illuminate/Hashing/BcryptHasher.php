<?php

namespace Illuminate\Hashing;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use RuntimeException;

class BcryptHasher implements HasherContract
{
    /**
     * Default crypt cost factor.
     *
     * @var int
     */
    protected $rounds = 10;
    
    /**
     * Hash the given value.
     *
     * @param  string $value
     * @param  array $options
     * @return string
     *
     * @throws \RuntimeException
     */
    public function make($value, array $options = [])
    {
        $cost = isset($options['rounds']) ? $options['rounds'] : $this->rounds;
        
        $hash = password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);
        
        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing not supported.');
        }
        
        return $hash;
    }
    
    /**
     * Check the given plain value against a hash.
     *
     * @param  string $value
     * @param  string $hashedValue
     * @param  array $options
     * @return bool
     */
    public function check($value, $hashedValue, array $options = [])
    {
        if (strlen($hashedValue) === 0) {
            return false;
        }
        
        return password_verify($value, $hashedValue);
    }
    
    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string $hashedValue
     * @param  array $options
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = [])
    {
        $cost = isset($options['rounds']) ? $options['rounds'] : $this->rounds;
        
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
    
    /**
     * Set the default password work factor.
     *
     * @param  int $rounds
     * @return $this
     */
    public function setRounds($rounds)
    {
        $this->rounds = (int)$rounds;
        
        return $this;
    }
    
    /**
     * Check if the given string is a hash produced by this hasher.
     *
     * @param  string $value
     * @param  array $options
     * @param  bool $strict
     * @return bool
     */
    public function isHash($value, array $options = [], $strict = false)
    {
        if (strlen($value) === 0) {
            return false;
        }
        
        $info = password_get_info($value);
        
        if ($info['algo'] !== PASSWORD_BCRYPT) {
            return false;
        }
        
        if ($strict && count(array_diff_assoc($options, $info['options']))) {
            return false;
        }
        
        return true;
    }
}

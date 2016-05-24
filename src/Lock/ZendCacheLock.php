<?php

namespace WShafer\NinjaMutexZf2\Lock;

use NinjaMutex\Lock\LockAbstract;
use NinjaMutex\Lock\LockExpirationInterface;
use Zend\Cache\Storage\StorageInterface;

class ZendCacheLock extends LockAbstract implements LockExpirationInterface
{
    /**
     * Maximum expiration time in seconds (30 days)
     */
    const MAX_EXPIRATION = 2592000;

    /**
     * Zend Cache
     *
     * @var StorageInterface
     */
    protected $cache;

    /**
     * @var int Expiration time of the lock in seconds
     */
    protected $expiration = 0;

    public function __construct(StorageInterface $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    /**
     * @param int $expiration Expiration time of the lock in seconds. If it's equal to zero (default), the lock will never expire.
     *                        Max 2592000s (30 days), if greater it will be capped to 2592000 without throwing an error.
     *                        WARNING: Using value higher than 0 may lead to race conditions. If you set too low expiration time
     *                        e.g. 30s and critical section will run for 31s another process will gain lock at the same time,
     *                        leading to unpredicted behaviour. Use with caution.
     */
    public function setExpiration($expiration)
    {
        if ($expiration > static::MAX_EXPIRATION) {
            $expiration = static::MAX_EXPIRATION;
        }

        $this->expiration = $expiration;
    }

    /**
     * Clear lock without releasing it
     * Do not use this method unless you know what you do
     *
     * @param  string $name name of lock
     * @return bool
     */
    public function clearLock($name)
    {
        if (!isset($this->locks[$name])) {
            return false;
        }

        unset($this->locks[$name]);
        return true;
    }

    /**
     * @param  string $name name of lock
     * @param  bool   $blocking
     * @return bool
     */
    protected function getLock($name, $blocking)
    {
        if ($this->isLocked($name)) {
            return false;
        }

        $options = $this->cache->getOptions();

        $oldTTL = 0;
        
        if (!empty($options->getTtl())) {
            $oldTTL = $options->getTtl();
        }
        
        $options->setTtl($this->expiration);
        
        if (!$this->cache->setItem($name, serialize($this->getLockInformation()))) {
            $options->setTtl($oldTTL);
            return false;
        }

        $this->locks[$name] = $name;

        return true;
    }

    /**
     * Release lock
     *
     * @param  string $name name of lock
     * @return bool
     */
    public function releaseLock($name)
    {
        if (isset($this->locks[$name])) {
            $this->cache->removeItem($name);
            return true;
        }

        return false;
    }

    /**
     * Check if lock is locked
     *
     * @param  string $name name of lock
     * @return bool
     */
    public function isLocked($name)
    {
        return $this->cache->hasItem($name);
    }
}

<?php

namespace Access2Me\Helper;

use Access2Me\Model;

class CacheException extends \Exception {}

interface CacheInterface
{
    public function exists($key);
    public function get($key);
    public function set($key, $value, $ttl = false);
    public function delete($key);
}

class Cache implements CacheInterface
{
    /**
     * @var \Access2Me\Model\CacheRepository
     */
    private $cacheRepo;

    public function __construct(Model\CacheRepository $cacheRepo)
    {
        $this->cacheRepo = $cacheRepo;
    }

    protected function encodeKey($key)
    {
        return sha1($key);
    }

    protected function hasExpired(Model\Cache $entry)
    {
        $expiresAt = $entry->getExpiresAt();
        return $expiresAt !== null && $entry->getExpiresAt() < new \DateTime();
    }

    public function exists($key)
    {
        $entry = $this->cacheRepo->getByKey($this->encodeKey($key));
        return $entry && !$this->hasExpired($entry);
    }

    public function get($key)
    {
        $entry = $this->cacheRepo->getByKey($this->encodeKey($key));
        if (!$entry || $this->hasExpired($entry)) {
            throw new CacheException('No such item or it has expired');
        }
        return $entry->getValue();
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $ttl string in \DateInterval format
     */
    public function set($key, $value, $ttl = null)
    {
        $key = $this->encodeKey($key);
        $entry = $this->cacheRepo->getByKey($key);
        if (!$entry) {
            $entry = new Model\Cache();
            $entry->setKey($key);
        }

        $expiresAt = null;
        if ($ttl != null) {
            $expiresAt = new \DateTime();
            $expiresAt->add(new \DateInterval($ttl));
        }

        $entry->setExpiresAt($expiresAt);
        $entry->setValue($value);
        $this->cacheRepo->save($entry);
    }

    public function delete($key)
    {
        $this->cacheRepo->delete($this->encodeKey($key));
    }
}

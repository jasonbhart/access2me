<?php

namespace Access2Me\Model;

class CacheRepository
{
    const TABLE_NAME = 'cache';

    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    /**
     * Encodes value to be stored in database
     * 
     * @param mixed
     */
    protected function encodeValue($value)
    {
        // store null values as NULL in databases instead of "N;"
        if ($value === null) {
            return null;
        }

        return serialize($value);
    }

    protected function decodeValue($value)
    {
        if ($value === null) {
            return null;
        }

        return unserialize($value);
    }

    protected function encodeExpiresAt($dt)
    {
        return $dt instanceof \DateTime ? $dt->format('Y-m-d H:i:s') : null;
    }

    protected function decodeExpiresAt($dt)
    {
        try {
            return !empty($dt) ? new \DateTime($dt) : null;
        } catch (\Exception $ex) {
            return null;
        }
    }
    
    /**
     * Helper method to decode values in all passed objects
     * 
     * @param Cache|Cache[] $entries
     */
    protected function decodeEntries($entries)
    {
        if ($entries === false) {
            return;
        }

        if (is_object($entries)) {
            $entries->setValue($this->decodeValue($entries->getValue()));
            $entries->setExpiresAt($this->decodeExpiresAt($entries->getExpiresAt()));
        } else {
            foreach ($entries as $entry) {
                $entry->setValue($this->decodeValue($entry->getValue()));
                $entry->setExpiresAt($this->decodeExpiresAt($entry->getExpiresAt()));
            }
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        $query = 'SELECT 1 FROM `' . self::TABLE_NAME . '`'
                . ' WHERE `key` = :key'
                . ' LIMIT 1';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);

        $st->bindValue(':key', $key);
        $st->execute();
        $entry = $st->fetchAll();

        return (bool)count($entry);
    }

    /**
     * @param string $key
     * @return Cache
     */
    public function getByKey($key)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`'
                . ' WHERE `key` = :key'
                . ' AND (`expires_at` > NOW() OR `expires_at` IS NULL)'
                . ' LIMIT 1';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->setFetchMode(\PDO::FETCH_CLASS, '\\Access2Me\\Model\\Cache');
        $st->bindValue(':key', $key);
        $st->execute();
        $entry = $st->fetch();

        $this->decodeEntries($entry);

        return $entry;
    }

    /**
     * @param Cache $entry
     */
    public function insert($entry)
    {
        $id = $this->db->insert(
            self::TABLE_NAME,
            array(
                'key',
                'value',
                'expires_at'
            ),
            array(
                $entry->getKey(),
                $this->encodeValue($entry->getValue()),
                $this->encodeExpiresAt($entry->getExpiresAt())
            ),
            true
        );
    }

    /**
     * @param Cache $entry
     */
    public function update($entry)
    {
        $query = 'UPDATE `' . self::TABLE_NAME .'`'
            . ' SET'
            . ' `value` = :value,'
            . ' `expires_at` = :expires_at'
            . ' WHERE `key` = :key';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':value', $this->encodeValue($entry->getValue()));
        $st->bindValue(':expires_at', $this->encodeExpiresAt($entry->getExpiresAt()));
        $st->bindValue(':key', $entry->getKey());
        $st->execute();
    }

    /**
     * @param Cache $entry
     */
    public function save($entry)
    {
        if (!$entry->getId()) {
            $this->insert($entry);
        } else {
            $this->update($entry);
        }
    }
    
    /**
     * @param string key
     */
    public function delete($key)
    {
        $query = 'DELETE FROM `' . self::TABLE_NAME . '`'
            . ' WHERE `key` = :key';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':key', $key);
        $st->execute();
    }
}

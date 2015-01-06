<?php

namespace Access2Me\Model;


class AuthTokenRepository
{
    const USER_LIST_MANAGER = 1;

    const TABLE_NAME = 'auth_tokens';

    /**
     * @var \Database
     */
    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    protected function encodeRoles($roles)
    {
        return count($roles) > 0 ? implode(',', $roles) : null;
    }

    protected function decodeRoles($roles)
    {
        return $roles != null ? explode(',', $roles) : [];
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
     * Helper method to decode values in all passed entries
     * 
     * @param array $entries
     */
    protected function decodeEntries(&$entries)
    {
        if ($entries === false) {
            return;
        }

        if (is_object($entries)) {
            $entries['roles'] = $this->decodeRoles($entries['roles']);
            $entries['expires_at'] = $this->decodeExpiresAt($entries['expires_at']);
        } else {
            foreach ($entries as &$entry) {
                $entry['roles'] = $this->decodeRoles($entry['roles']);
                $entry['expires_at'] = $this->decodeExpiresAt($entry['expires_at']);
            }
        }
    }

    public function get($id)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `id` = :id LIMIT 1;';
        
        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':id', $id, \PDO::PARAM_INT);
        $st->execute();
        
        $entry = $st->fetch();
        $st->closeCursor();
        
        $this->decodeEntries($entry);
        
        return $entry !== false ? $entry : null;
    }

    public function getByToken($token)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`'
            . ' WHERE `token` = :token'
            . ' LIMIT 1';
        $entries = $this->db->getArray($query, ['token' => $token]);

        $this->decodeEntries($entries);

        return $entries ? $entries[0] : null;
    }

    /**
     * @param array $entry
     */
    public function insert($entry)
    {
        return $this->db->insert(
            self::TABLE_NAME,
            array(
                'token',
                'user_id',
                'roles',
                'expires_at'
            ),
            array(
                $entry['token'],
                $entry['user_id'],
                $this->encodeRoles($entry['roles']),
                $this->encodeExpiresAt($entry['expires_at'])
            ),
            true
        );
    }

    /**
     * @param array $entry
     */
    public function update($entry)
    {
        $query = 'UPDATE `' . self::TABLE_NAME . '`'
            . ' SET'
            . ' `token` = :token,'
            . ' `user_id` = :user_id,'
            . ' `roles` = :roles,'
            . ' `expires_at` = :expires_at'
            . ' WHERE id = :id';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':token', $entry['token']);
        $st->bindValue(':user_id', $entry['user_id'], \PDO::PARAM_INT);
        $st->bindValue(':roles', $this->encodeRoles($entry['roles']));
        $st->bindValue(':expires_at', $this->encodeExpiresAt($entry['expires_at']));
        $st->bindValue(':id', $entry['id'], \PDO::PARAM_INT);
        $st->execute();
    }

    public function save($entry)
    {
        if (!isset($entry['id'])) {
            return $this->insert($entry);
        } else {
            $this->update($entry);
            return $entry['id'];
        }
    }

    /**
     * @param int id
     */
    public function delete($id)
    {
        $query = 'DELETE FROM `' . self::TABLE_NAME . '`'
            . ' WHERE `id` = :id';

        return $this->db->execute($query, ['id' => $id]);
    }
}

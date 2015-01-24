<?php

namespace Access2Me\Model;

class UserRepository
{
    const TABLE_NAME = 'users';

    /**
     * @var \Database
     */
    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    protected function encodeAccessToken($accessToken)
    {
        return $accessToken;

        // store null values as NULL in databases instead of json "null"
        if ($accessToken === null) {
            return null;
        }

        return json_encode($accessToken);
    }

    protected function decodeAccessToken($encodedAccessToken)
    {
        return $encodedAccessToken;
        return json_decode($encodedAccessToken, true);
    }

    protected function decodeUsers(&$users)
    {
        if ($users === false) {
            return;
        }

        // check if this is an entity array
        if (isset($users['username'])) {
            // google client encodes token itself
            //$users['gmail_access_token'] = $this->decodeAccessToken($users['gmail_access_token']);
        } else {
            foreach ($users as &$user) {
                //$user['gmail_access_token'] = $this->decodeAccessToken($user['gmail_access_token']);
            }
        }
    }

    public function getById($userId)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `id` = :id';
        $users = $this->db->getArray($query, array('id' => $userId));
        $this->decodeUsers($users);
        return $users ? $users[0] : null;
    }

    public function getByUsername($username)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `username` = :username';
        $users = $this->db->getArray($query, array('username' => $username));
        $this->decodeUsers($users);
        return $users ? $users[0] : null;
    }

    public function getByMailbox($mailbox)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `mailbox` = :mailbox';
        $users = $this->db->getArray($query, array('mailbox' => $mailbox));
        $this->decodeUsers($users);
        return $users ? $users[0] : null;
    }
    
    public function findAll()
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`';
        $users = $this->db->getArray($query);
        $this->decodeUsers($users);
        return $users ? $users : [];
    }

    public function findAllByMailboxes($mailboxes)
    {
        $values = [];
        $conn = $this->db->getConnection();
        foreach ($mailboxes as $mailbox) {
            $values[] = $conn->quote($mailbox);
        }

        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`'
            . ' WHERE `mailbox` IN (' . implode(',', $values) . ')';
        $users = $this->db->getArray($query);
        $this->decodeUsers($users);

        return $users ? $users : [];
    }

    /**
     * @param array $entry
     */
    public function insert($entry)
    {
        return $this->db->insert(
            self::TABLE_NAME,
            array(
                'mailbox',
                'email',
                'name',
                'username',
                'password',
                'gmail_access_token'
            ),
            array(
                $entry['mailbox'],
                $entry['email'],
                $entry['name'],
                $entry['username'],
                $entry['password'],
                $this->encodeAccessToken($entry['gmail_access_token'])
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
            . ' `mailbox` = :mailbox,'
            . ' `email` = :email,'
            . ' `name` = :name,'
            . ' `username` = :username,'
            . ' `password` = :password,'
            . ' `gmail_access_token` = :gmail_access_token'
            . ' WHERE id = :id';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':mailbox', $entry['mailbox']);
        $st->bindValue(':email', $entry['email']);
        $st->bindValue(':name', $entry['name']);
        $st->bindValue(':username', $entry['username']);
        $st->bindValue(':password', $entry['password']);
        $st->bindValue(':gmail_access_token', $this->encodeAccessToken($entry['gmail_access_token']));
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
}


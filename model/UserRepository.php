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

    public function getById($userId)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `id` = :id';
        $users = $this->db->getArray($query, array('id' => $userId));
        return $users ? $users[0] : null;
    }

    public function findAll()
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '`';
        $users = $this->db->getArray($query);
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
                'gmail_access_token',
                'gmail_refresh_token',
                'recipients_imported'
            ),
            array(
                $entry['mailbox'],
                $entry['email'],
                $entry['name'],
                $entry['username'],
                $entry['password'],
                $entry['gmail_access_token'],
                $entry['gmail_refresh_token'],
                $entry['recipients_imported']
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
            . ' `gmail_access_token` = :gmail_access_token,'
            . ' `gmail_refresh_token` = :gmail_refresh_token,'
            . ' `recipients_imported` = :recipients_imported'
            . ' WHERE id = :id';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':mailbox', $entry['mailbox']);
        $st->bindValue(':email', $entry['email']);
        $st->bindValue(':name', $entry['name']);
        $st->bindValue(':username', $entry['username']);
        $st->bindValue(':password', $entry['password']);
        $st->bindValue(':gmail_access_token', $entry['gmail_access_token']);
        $st->bindValue(':gmail_refresh_token', $entry['gmail_refresh_token']);
        $st->bindValue(':recipients_imported', $entry['recipients_imported'], \PDO::PARAM_INT);
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

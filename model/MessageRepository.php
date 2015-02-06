<?php

namespace Access2Me\Model;

class MessageRepository
{
    // this concers to a sender
    // actually we need only processed or not
    // todo: refactor
    const STATUS_NOT_VERIFIED = 0;
    const STATUS_VERIFY_REQUESTED = 1;
    const STATUS_VERIFIED = 2;

    const STATUS_FILTER_PASSED = 3;
    const STATUS_FILTER_FAILED = 4;

    const STATUS_SENDER_WHITELISTED = 5;
    const STATUS_SENDER_BLACKLISTED = 6;

    const TABLE_NAME = 'messages';

    /**
     * @var \Database
     */
    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    public function getById($id)
    {
        $query = "SELECT * FROM `" . self::TABLE_NAME ."` WHERE `id` = :id LIMIT 1;";
        
        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':id', $id, \PDO::PARAM_INT);
        $st->execute();
        
        $message = $st->fetch();
        
        $st->closeCursor();
        
        return $message !== false ? $message : null;
    }

    public function findByStatus($status)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `status` = :status';
        $messages = $this->db->getArray($query, ['status' => $status]);
        return $messages ? $messages : array();
    }

    public function findByUserAndSender($userId, $sender)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `user_id` = :user_id AND `from_email` = :sender';
        $messages = $this->db->getArray($query, array('user_id' => $userId, 'sender' => $sender));
        return $messages ? $messages : array();
    }

    public function findByUser($userId, $limit = -1, $offset = -1)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `user_id` = :user_id';
        $params = ['user_id' => $userId];

        if ($limit != -1) {
            $query .= ' LIMIT :limit';
            $params['limit'] = $limit;

            if ($offset != -1) {
                $query .= ' OFFSET :offset';
                $params['offset'] = $offset;
            }
        }

        $messages = $this->db->getArray($query, $params);
        return $messages ? $messages : array();
    }

    public function getCountByUser($userId)
    {
        $query = 'SELECT COUNT(1) cnt FROM `' . self::TABLE_NAME . '` WHERE `user_id` = :user_id';
        $params = ['user_id' => $userId];

        $res = $this->db->getArray($query, $params);
        return $res !== false ? (int)$res[0]['cnt'] : 0;
    }

        /**
     * @param array $entry
     */
    public function insert($entry)
    {
        return $this->db->insert(
            self::TABLE_NAME,
            array(
                'message_id',
                'user_id',
                'from_name',
                'from_email',
                'reply_email',
                'to_email',
                'created_at',
                'subject',
                'header',
                'body',
                'status',
                'appended_to_unverified'
            ),
            array(
                $entry['message_id'],
                $entry['user_id'],
                $entry['from_name'],
                $entry['from_email'],
                $entry['reply_email'],
                $entry['to_email'],
                $entry['created_at'],
                $entry['subject'],
                $entry['header'],
                $entry['body'],
                $entry['status'],
                $entry['appended_to_unverified']
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
            . ' `message_id` = :message_id,'
            . ' `user_id` = :user_id,'
            . ' `from_name` = :from_name,'
            . ' `from_email` = :from_email,'
            . ' `reply_email` = :reply_email,'
            . ' `to_email` = :to_email,'
            . ' `created_at` = :created_at,'
            . ' `subject` = :subject,'
            . ' `header` = :header,'
            . ' `body` = :body,'
            . ' `status` = :status,'
            . ' `appended_to_unverified` = :appended_to_unverified'
            . ' WHERE id = :id';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':message_id', $entry['message_id']);
        $st->bindValue(':user_id', $entry['user_id'], \PDO::PARAM_INT);
        $st->bindValue(':from_name', $entry['from_name']);
        $st->bindValue(':from_email', $entry['from_email']);
        $st->bindValue(':reply_email', $entry['reply_email']);
        $st->bindValue(':to_email', $entry['to_email']);
        $st->bindValue(':created_at', $entry['created_at']);
        $st->bindValue(':subject', $entry['subject']);
        $st->bindValue(':header', $entry['header']);
        $st->bindValue(':body', $entry['body']);
        $st->bindValue(':status', $entry['status'], \PDO::PARAM_INT);
        $st->bindValue(':appended_to_unverified', $entry['appended_to_unverified'], \PDO::PARAM_INT);
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

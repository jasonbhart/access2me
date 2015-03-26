<?php

namespace Access2Me\Model;


class FiltersRepository
{
    const TABLE_NAME = 'filters';

    private $db;

    public function __construct(\Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $id
     * @return Filter|null
     */
    public function getById($id)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `id` = :id';

        $filters = $this->db->getArray($query, ['id' => $id], '\\Access2Me\\Model\\Filter');
        return $filters ? $filters[0] : null;
    }

    /**
     * Returns list of filters that belong to certain user
     * 
     * @param int $userId
     * @return Filter[]
     */
    public function findByUserId($userId)
    {
        $query = 'SELECT * FROM `' . self::TABLE_NAME . '` WHERE `user_id` = :user_id';
        
        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->setFetchMode(\PDO::FETCH_CLASS, '\\Access2Me\\Model\\Filter');
        $st->bindValue(':user_id', $userId);
        $st->execute();
        $senders = $st->fetchAll();

        return $senders;
    }

    /**
     * Returns count of filters owned by user
     * @param int $userId
     * @return int
     */
    public function getCountByUser($userId)
    {
        $query = 'SELECT COUNT(1) cnt FROM `' . self::TABLE_NAME . '` WHERE `user_id` = :user_id';
        $res = $this->db->getArray($query, ['user_id' => $userId]);

        return $res !== false ? (int)$res[0]['cnt'] : 0;
    }

    /**
     * @param Filter $filter
     */
    public function insert($filter)
    {
        return $this->db->insert(
            self::TABLE_NAME,
            array(
                'user_id',
                'type',
                'property',
                'method',
                'value'
            ),
            array(
                $filter->getUserId(),
                $filter->getType(),
                $filter->getProperty(),
                $filter->getMethod(),
                $filter->getValue()
            ),
            true
        );
    }

    /**
     * @param Filter $filter
     */
    public function update($filter)
    {
        $query = 'UPDATE `' . self::TABLE_NAME .'`'
            . ' SET'
            . ' `user_id` = :user_id,'
            . ' `type` = :type,'
            . ' `property` = :property,'
            . ' `method` = :method,'
            . ' `value` = :value'
            . ' WHERE id = :id';

        $conn = $this->db->getConnection();
        $st = $conn->prepare($query);
        $st->bindValue(':user_id', $filter->getUserId(), \PDO::PARAM_INT);
        $st->bindValue(':type', $filter->getType(), \PDO::PARAM_INT);
        $st->bindValue(':property', $filter->getProperty());
        $st->bindValue(':method', $filter->getMethod(), \PDO::PARAM_INT);
        $st->bindValue(':value', $filter->getValue());
        $st->bindValue(':id', $filter->getId(), \PDO::PARAM_INT);
        $st->execute();
    }

    /**
     * @param Filter $filter
     * @return int
     */
    public function save($filter)
    {
        if ($filter->getId() === null) {
            return $this->insert($filter);
        } else {
            $this->update($filter);
            return $filter->getId();
        }
    }

    /**
     * @param int $id
     */
    public function delete($id)
    {
        $query = 'DELETE FROM `' . self::TABLE_NAME . '`' . ' WHERE `id` = :id';
        return $this->db->execute($query, ['id' => $id]);
    }
}

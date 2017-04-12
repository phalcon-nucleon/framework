<?php

namespace Neutrino\Interfaces\Repositories;

/**
 * Interface RepositoryInterface
 *
 * @package Neutrino\Interfaces\Repositories
 */
interface RepositoryInterface
{
    /**
     * Retourne tous les models d'une table.
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface|\Neutrino\Model[]|\Phalcon\Mvc\Model[]
     */
    public function all();

    /**
     * @param null|array $params Wheres Criteria ...
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface|int
     */
    public function count(array $params = null);

    /**
     * Recherche & renvoie une liste de model selon les criteria transmis
     *
     * @param array      $params Wheres Criteria ...
     * @param array|null $order  Order By ...
     * @param int|null   $limit  Limit By ...
     *
     * @return \Neutrino\Model[]|\Phalcon\Mvc\Model[]|\Phalcon\Mvc\Model\ResultsetInterface
     */
    public function find(array $params = [], array $order = null, $limit = null);

    /**
     * Recherche & renvoie le premier model selon les criteria transmis
     *
     * @param array      $params Wheres Criteria ...
     * @param array|null $order  Order By ...
     *
     * @return bool|\Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function first(array $params = [], array $order = null);

    /**
     * @param array $params
     * @param bool  $create
     * @param bool  $withTransaction
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function firstOrNew(array $params = [], $create = false, $withTransaction = false);

    /**
     * @param array $params
     * @param bool  $withTransaction
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function firstOrCreate(array $params = [], $withTransaction = false);

    /**
     * Appel la methode create sur le, ou les, models transmis, dans une transaction.
     *
     * @param \Neutrino\Model|\Neutrino\Model[]|\Phalcon\Mvc\Model|\Phalcon\Mvc\Model[] $value
     * @param bool                                                                      $withTransaction
     *
     * @return bool
     */
    public function create($value, $withTransaction = true);

    /**
     * Appel la methode save sur le, ou les, models transmis, dans une transaction.
     *
     * @param \Neutrino\Model|\Neutrino\Model[]|\Phalcon\Mvc\Model|\Phalcon\Mvc\Model[] $value
     * @param bool                                                                      $withTransaction
     *
     * @return bool
     */
    public function save($value, $withTransaction = true);

    /**
     * Appel la methode update sur le, ou les, models transmis, dans une transaction.
     *
     * @param \Neutrino\Model|\Neutrino\Model[]|\Phalcon\Mvc\Model|\Phalcon\Mvc\Model[] $value
     * @param bool                                                                      $withTransaction
     *
     * @return bool
     */
    public function update($value, $withTransaction = true);

    /**
     * Appel la methode delete sur le, ou les, models transmis, dans une transaction.
     *
     * @param \Neutrino\Model|\Neutrino\Model[]|\Phalcon\Mvc\Model|\Phalcon\Mvc\Model[] $value
     * @param bool                                                                      $withTransaction
     *
     * @return bool
     */
    public function delete($value, $withTransaction = true);
}

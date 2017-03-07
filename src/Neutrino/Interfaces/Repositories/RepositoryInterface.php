<?php

namespace Neutrino\Interfaces\Repositories;

interface RepositoryInterface
{
    /**
     * Retourne tous les models d'une table.
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface|\Neutrino\Model[]|\Phalcon\Mvc\Model[]
     */
    public function all();

    /**
     * @param null|array $criteria
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface|int
     */
    public function count(array $criteria = null);

    /**
     * Recherche & renvoie une liste de model selon les criteria transmis
     *
     * @param null|string|array $criteria
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface|\Neutrino\Model[]|\Phalcon\Mvc\Model[]
     */
    public function find($criteria = null);

    /**
     * Recherche & renvoie le premier model selon les criteria transmis
     *
     * @param array $params
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model|bool
     */
    public function first(array $params = []);

    /**
     * @param array $params
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function firstOrNew(array $params = []);

    /**
     * @param array $params
     *
     * @return \Neutrino\Model|\Phalcon\Mvc\Model
     */
    public function firstOrCreate(array $params = []);

    /**
     * Appel la methode create sur le, ou les, models transmis, dans une transaction.
     *
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     *
     * @return bool
     */
    public function create($value);

    /**
     * Appel la methode save sur le, ou les, models transmis, dans une transaction.
     *
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     *
     * @return bool
     */
    public function save($value);

    /**
     * Appel la methode update sur le, ou les, models transmis, dans une transaction.
     *
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     *
     * @return bool
     */
    public function update($value);

    /**
     * Appel la methode delete sur le, ou les, models transmis, dans une transaction.
     *
     * @param \Neutrino\Model|\Neutrino\Model[] $value
     *
     * @return bool
     */
    public function delete($value);
}
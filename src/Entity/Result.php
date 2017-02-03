<?php

namespace Paysera\Component\RestClientCommon\Entity;

use Countable;
use Iterator;

abstract class Result extends Entity implements Iterator, Countable
{
    const METADATA_KEY = '_metadata';

    private $dataKey;
    private $position;

    public function __construct(array $data = [], $dataKey = 'items')
    {
        parent::__construct($data);

        $this->dataKey = $dataKey;
        $this->position = 0;
    }

    public function current()
    {
        $data = $this->getItems()[$this->position];
        return $this->createItem($data);
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
       return isset($this->getItems()[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function count()
    {
        return count($this->getItems());
    }



    public function getItems()
    {
        return $this->get($this->dataKey);
    }

    public function getMetadata()
    {
        return $this->get(self::METADATA_KEY);
    }

    public function getDataKey()
    {
        return $this->dataKey;
    }

    /**
     * @return int|null
     */
    public function getTotal()
    {
        return isset($this->getMetadata()['total']) ? $this->getMetadata()['total'] : null;
    }

    /**
     * @return int|null
     */
    public function getOffset()
    {
        return isset($this->getMetadata()['offset']) ? $this->getMetadata()['offset'] : null;
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return isset($this->getMetadata()['limit']) ? $this->getMetadata()['limit'] : null;
    }

    protected function createItem(array $data)
    {
        return new Entity($data);
    }
}

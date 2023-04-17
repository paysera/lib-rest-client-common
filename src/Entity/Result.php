<?php

namespace Paysera\Component\RestClientCommon\Entity;

use Countable;
use Iterator;

/**
 * @api
 */
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

    #[\ReturnTypeWillChange]
    public function current()
    {
        $data = $this->getItems()[$this->position];
        return $this->createItem($data);
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->position;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
       return isset($this->getItems()[$this->position]);
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
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
     * @return string|null
     */
    public function getCursorBefore()
    {
        return isset($this->getMetadata()['cursors']['before']) ? $this->getMetadata()['cursors']['before'] : null;
    }

    /**
     * @return string|null
     */
    public function getCursorAfter()
    {
        return isset($this->getMetadata()['cursors']['after']) ? $this->getMetadata()['cursors']['after'] : null;
    }

    /**
     * @return bool
     */
    public function hasNext()
    {
        return isset($this->getMetadata()['has_next']) ? $this->getMetadata()['has_next'] : false;
    }

    /**
     * @return bool
     */
    public function hasPrevious()
    {
        return isset($this->getMetadata()['has_previous']) ? $this->getMetadata()['has_previous'] : false;
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

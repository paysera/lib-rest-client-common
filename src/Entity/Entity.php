<?php

namespace Paysera\Component\RestClientCommon\Entity;

use ArrayAccess;

/**
 * @api
 */
class Entity implements ArrayAccess
{
    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    protected function setDataByReference(array &$data)
    {
        $this->data =& $data;
        return $this;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    protected function &getByReference($name)
    {
        return $this->data[$name];
    }

    /**
     * @param string $name
     * @param mixed $data
     * @return $this
     */
    protected function setByReference($name, &$data)
    {
        $this->data[$name] =& $data;
        return $this;
    }

    /**
     * @return array
     */
    protected function &getDataByReference()
    {
        return $this->data;
    }
}

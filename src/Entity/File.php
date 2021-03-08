<?php

namespace Paysera\Component\RestClientCommon\Entity;

/**
 * @api
 */
class File extends Entity
{
    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->get('content');
    }

    /**
     * @return string|null
     */
    public function getMimeType()
    {
        return $this->get('mime_type');
    }

    /**
     * @return int|null
     */
    public function getSize()
    {
        return $this->get('size');
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName($name)
    {
        $this->set('name', $name);
        return $this;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->set('content', $content);
        return $this;
    }

    /**
     * @param string|null $mimeType
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        $this->set('mime_type', $mimeType);
        return $this;
    }

    /**
     * @param int|null $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->set('size', $size);
        return $this;
    }
}

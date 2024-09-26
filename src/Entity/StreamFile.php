<?php

declare(strict_types=1);

namespace Paysera\Component\RestClientCommon\Entity;

use Psr\Http\Message\StreamInterface;

/**
 * @api
 */
class StreamFile extends Entity
{
    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function setName(string $name): self
    {
        $this->set('name', $name);
        return $this;
    }

    public function getContent(): StreamInterface
    {
        return $this->get('content');
    }

    public function setContent(StreamInterface $content): self
    {
        $this->set('content', $content);
        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->get('content_type');
    }

    public function setContentType(string $contentType): self
    {
        $this->set('content_type', $contentType);
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->get('size');
    }

    public function setSize(int $size): self
    {
        $this->set('size', $size);
        return $this;
    }
}

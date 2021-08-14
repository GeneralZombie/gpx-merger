<?php

declare (strict_types=1);

namespace TwohundredCouches\GpxMerger\Model;

class GpxMetaData
{
    protected ?string $name;

    protected ?string $description;

    protected ?string $author;

    /**
     * @param string|null $name The name of the gpx file.
     * @param string|null $description A description of the file.
     * @param string|null $author The author of the file.
     */
    public function __construct(?string $name, ?string $description = null, ?string $author = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->author = $author;
    }

    public static function create(?string $name, ?string $description = null, ?string $author = null) {
        return new self($name, $description, $author);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }
}
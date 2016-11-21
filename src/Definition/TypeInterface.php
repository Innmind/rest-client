<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

interface TypeInterface
{
    public static function fromString(string $type, Types $types): self;

    /**
     * Transform the data received via http to a data understandable for php
     *
     * @param mixed $data
     *
     * @throws DenormalizationException
     *
     * @return mixed
     */
    public function denormalize($data);

    /**
     * Transform the php data to something serializable
     *
     * @param mixed $data
     *
     * @throws NormalizationException
     *
     * @return mixed
     */
    public function normalize($data);

    public function __toString(): string;
}

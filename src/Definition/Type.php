<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

interface Type
{
    public static function fromString(string $type, Types $build): self;

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

    public function toString(): string;
}

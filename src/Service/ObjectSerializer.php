<?php

namespace App\Service;

use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ObjectSerializer extends Serializer
{

    public function __construct()
    {
        parent::__construct([new DateTimeNormalizer(), new ObjectNormalizer()]);
    }
}

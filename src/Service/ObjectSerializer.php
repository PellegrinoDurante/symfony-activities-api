<?php

namespace App\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ObjectSerializer extends Serializer
{

    public function __construct()
    {
        $annotationReader = new AnnotationReader();
        $loader = new AnnotationLoader($annotationReader);
        $classMetadataFactory = new ClassMetadataFactory($loader);
        $objectNormalizer = new ObjectNormalizer($classMetadataFactory);
        parent::__construct([new DateTimeNormalizer(), $objectNormalizer]);
    }
}

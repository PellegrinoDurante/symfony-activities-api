<?php

namespace App\Controller;

use Exception;
use JetBrains\PhpStorm\Pure;

class MissingRequiredFieldException extends Exception
{

    #[Pure] public function __construct(private string $field)
    {
        parent::__construct(sprintf('Required field `%s` is missing!', $this->field));
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }
}

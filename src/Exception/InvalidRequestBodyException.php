<?php

namespace App\Controller;

use Exception;
use JetBrains\PhpStorm\Pure;

class InvalidRequestBodyException extends Exception
{

    #[Pure] public function __construct()
    {
        parent::__construct("The request's body is not a valid JSON string.");
    }
}

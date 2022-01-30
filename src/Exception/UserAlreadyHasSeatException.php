<?php

namespace App\Controller;

use Exception;
use JetBrains\PhpStorm\Pure;

class UserAlreadyHasSeatException extends Exception
{

    #[Pure] public function __construct()
    {
        parent::__construct('User already joined activity');
    }
}

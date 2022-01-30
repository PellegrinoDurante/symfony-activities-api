<?php

namespace App\Controller;

use Exception;
use JetBrains\PhpStorm\Pure;

class NoAvailableSeatsLeftException extends Exception
{

    #[Pure] public function __construct()
    {
        parent::__construct('No available seats left');
    }
}

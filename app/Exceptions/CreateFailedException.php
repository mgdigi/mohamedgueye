<?php

namespace App\Exceptions;
use App\Traits\ApiResponse;

use Exception;

class CreateFailedException extends Exception
{
    use ApiResponse;


    public function render($request)
    {
        return $this->errorResponse('Erreur lors de la création du compte', 500);
    }
}

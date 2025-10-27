<?php

namespace App\Exceptions;


use App\Traits\ApiResponse;
use Exception;

class CompteNotFoundException extends Exception
{
    use ApiResponse;

    public function render($request)
    {
        return $this->errorResponse('Le compte demandé est introuvable', 404);
    }
}

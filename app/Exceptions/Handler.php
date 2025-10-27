<?php

namespace App\Exceptions;
use App\Models\Compte;
use App\Exceptions\CompteNotFoundException;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register()
{
    $this->renderable(function (ModelNotFoundException $e, $request) {
        if ($e->getModel() === Compte::class) {
            throw new CompteNotFoundException();
        }
    });

    $this->renderable(function (\Illuminate\Database\QueryException $e, $request) {
        if (str_contains($e->getMessage(), 'invalid input syntax for type uuid')) {
            throw new CompteNotFoundException();
        }
    });
}
}

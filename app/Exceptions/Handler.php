<?php

namespace App\Exceptions;

use Filament\Support\Exceptions\Halt;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof Halt) {
            // Filament Halt is used to short-circuit page rendering with a status code.
            // Convert to a plain HTTP response so tests can assert the correct status.
            $message = (string) $e->getMessage();
            $status = is_numeric($message) ? (int) $message : 403;

            $status = ($status >= 100 && $status < 600) ? $status : 403;

            return response('', $status);
        }

        return parent::render($request, $e);
    }
}

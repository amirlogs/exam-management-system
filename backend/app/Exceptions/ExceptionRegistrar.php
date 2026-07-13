<?php

namespace App\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Throwable;

class ExceptionRegistrar
{
    public function handle(Exceptions $exceptions): void
    {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (!$request->is("api/*")) {
                return;
            }

            $message = "An error occurred";
            $statusCode = 500;
            $errors = null;

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $message = "Validation failed";
                $statusCode = 422;
                $errors = $e->errors();
            } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                $message = "Unauthenticated";
                $statusCode = 401;
            } elseif (
                $e instanceof
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
            ) {
                $message = "Resource not found";
                $statusCode = 404;
            } elseif (
                $e instanceof
                \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
            ) {
                $message = "Access denied";
                $statusCode = 403;
            } elseif (
                $e instanceof
                \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
            ) {
                $message = "Method not allowed";
                $statusCode = 405;
            } elseif (
                $e instanceof
                \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            ) {
                $message = $e->getMessage();
                $statusCode = $e->getStatusCode();
            } else {
                $message = $e->getMessage() || "Internal server error";
                $statusCode = 500;
            }
            if (config("app.debug")) {
                if (!$errors) {
                    $errors = [
                        "file" => $e->getFile(),
                        "line" => $e->getLine(),
                        "message" => $e->getMessage(),
                    ];
                }
            } else {
                $errors = null;
            }

            return response()->json(
                [
                    "success" => false,
                    "message" => $message,
                    "data" => null,
                    "errors" => $errors,
                ],
                $statusCode,
            );
        });
    }
}

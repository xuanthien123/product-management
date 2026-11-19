<?php

namespace App\Exceptions;

use App\Http\Helpers\ApiResponse;
use App\Models\Product;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ApiExceptionHandler
{
    private const NOT_FOUND_MESSAGES = [
        Product::class => 'Product not found.',
    ];

    public static function register(Exceptions $exceptions): void
    {
        $getNotFoundMessage = static fn (ModelNotFoundException $exception): string => self::NOT_FOUND_MESSAGES[$exception->getModel()] ?? 'Resource not found.';

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    $exception->getMessage(),
                    $exception->status,
                    $exception->errors(),
                );
            }
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Unauthenticated.', 401);
            }
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($getNotFoundMessage) {
            if ($request->is('api/*')) {
                return ApiResponse::error($getNotFoundMessage($exception), 404);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($getNotFoundMessage) {
            if ($request->is('api/*')) {
                $previous = $exception->getPrevious();

                return ApiResponse::error(
                    $exception->getStatusCode() === 404
                        ? ($previous instanceof ModelNotFoundException
                            ? $getNotFoundMessage($previous)
                            : 'Resource not found.')
                        : 'Request failed.',
                    $exception->getStatusCode(),
                );
            }
        });
    }
}

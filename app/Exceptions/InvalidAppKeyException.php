<?php

// app/Exceptions/InvalidAppKeyException.php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class InvalidAppKeyException extends Exception
{
    public static function missing(): self
    {
        return new self('Missing X-App-Key header.', Response::HTTP_UNAUTHORIZED);
    }

    public static function invalidOrInactive(): self
    {
        return new self('Invalid or inactive App Key.', Response::HTTP_UNAUTHORIZED);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => null,
        ], $this->getCode() ?: Response::HTTP_UNAUTHORIZED);
    }
}

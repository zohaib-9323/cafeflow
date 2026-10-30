<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * A request that is well-formed but breaks a business rule (an expired promo code,
 * an illegal status change, not enough stock). It is shown to the user as a message,
 * not treated as a server error.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, public readonly array $details = [], public readonly string $field = 'general')
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'errors' => [$this->field => [$this->getMessage()]],
                'details' => $this->details ?: null,
            ], 422);
        }

        return back()->withInput()->withErrors([$this->field => $this->getMessage()]);
    }
}

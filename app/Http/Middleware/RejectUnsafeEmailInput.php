<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RejectUnsafeEmailInput
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->containsUnsafeEmail($request->all())) {
            throw ValidationException::withMessages([
                'email' => 'Az email-cím nem tartalmazhat sortörést.',
            ]);
        }

        return $next($request);
    }

    private function containsUnsafeEmail(array $values): bool
    {
        foreach ($values as $key => $value) {
            if (is_array($value) && $this->containsUnsafeEmail($value)) {
                return true;
            }

            $normalizedKey = strtolower((string) $key);
            $isEmailField = $normalizedKey === 'email' || substr($normalizedKey, -6) === '_email';

            if ($isEmailField && is_string($value) && preg_match('/[\r\n]/', $value) === 1) {
                return true;
            }
        }

        return false;
    }
}

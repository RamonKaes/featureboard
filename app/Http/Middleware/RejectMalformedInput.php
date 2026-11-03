<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RejectMalformedInput
{
    /**
     * Refuse input that would otherwise end in an exception instead of a validation error,
     * such as text PostgreSQL cannot store.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $isValid = true;

        array_walk_recursive($input, function (mixed $value) use (&$isValid): void {
            if (is_string($value) && (str_contains($value, "\0") || ! mb_check_encoding($value, 'UTF-8'))) {
                $isValid = false;
            }
        });

        // Fortify lowercases this field before it validates, which throws on an array.
        abort_if(is_array($request->input(Fortify::username())), Response::HTTP_BAD_REQUEST);

        abort_unless($isValid, Response::HTTP_BAD_REQUEST);

        return $next($request);
    }
}

<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureCustomer;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [EnsureAccountIsActive::class]);
        $middleware->api(prepend: [\Illuminate\Routing\Middleware\ThrottleRequests::class.':api']);
        $middleware->alias(['customer' => EnsureCustomer::class]);
        // Payment providers can't send a CSRF token; webhooks are authenticated by signature instead.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $r) => $r->user()->homeRoute());
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Every JSON error uses the API envelope: { success: false, message, errors? }.
        $json = fn (string $message, int $status, array $extra = []) => response()->json(['success' => false, 'message' => $message] + $extra, $status);

        $exceptions->render(function (ValidationException $e, Request $request) use ($json) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $json($e->getMessage(), $e->status, ['errors' => $e->errors()]);
            }
        });
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($json) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $json('Unauthenticated.', 401);
            }
        });
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($json) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $json('You are not allowed to do that.', 403);
            }
        });
        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request) use ($json) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $json('Not found.', 404);
            }
        });
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($json) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $json('Too many requests. Please slow down.', 429);
            }
        });
        $exceptions->render(function (Throwable $e, Request $request) use ($json) {
            if (($request->is('api/*') || $request->expectsJson()) && ! config('app.debug')) {
                $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

                return $json($status === 500 ? 'Server error.' : ($e->getMessage() ?: 'Error.'), $status);
            }
        });
    })->create();

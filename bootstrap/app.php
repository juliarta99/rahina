<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        // Webhook Fonnte tidak membawa token CSRF; diamankan dengan secret turunan APP_KEY + cek device.
        $middleware->validateCsrfTokens(except: ['webhooks/fonnte/*']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, Throwable $exception, Request $request) {
            $code = $response->getStatusCode();
            if (in_array($code, [500, 503, 404, 403], true)) {
                if (! $request->expectsJson() && ($request->header('X-Inertia') || app()->environment('production', 'testing'))) {
                    return inertia('error', ['status' => $code])
                        ->toResponse($request)
                        ->setStatusCode($code);
                }
            } elseif ($code === 419) {
                return back()->with([
                    'message' => 'Halaman telah kedaluwarsa, silakan muat ulang.',
                ]);
            }

            return $response;
        });
    })->create();

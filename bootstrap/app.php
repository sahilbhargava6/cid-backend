<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Global Security Headers Middleware for API Responses
        $middleware->append(function (Request $request, Closure $next) {
            /** @var Response $response */
            $response = $next($request);

            if (method_exists($response, 'headers')) {
                $response->headers->set('X-Content-Type-Options', 'nosniff');
                $response->headers->set('X-Frame-Options', 'DENY');
                $response->headers->set('X-XSS-Protection', '1; mode=block');
                $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
                
                // Prevent browsers from caching sensitive API responses
                if ($request->is('api/*')) {
                    $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                    $response->headers->set('Pragma', 'no-cache');
                }
            }

            return $response;
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

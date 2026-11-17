<?php

use App\Http\Middleware\PrivateResponse;
use App\Support\ExceptionMessage;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', PrivateResponse::class);
        $middleware->appendToGroup('api', PrivateResponse::class);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function ($request) {
            $user = $request->user();

            return match ($user?->role) {
                'pemilik' => route('pemilik.dashboard'),
                'karyawan' => route('karyawan.dashboard'),
                default => '/login',
            };
        });

        // Trust reverse proxy (Nginx Proxy Manager) agar skema HTTPS & host terdeteksi
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (Throwable $exception, Request $request) {
            $json = $request->is('api/*') || $request->expectsJson();
            if ($exception instanceof ValidationException) {
                if (! $json) {
                    return null;
                }

                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?? 'Periksa kembali isian Anda.',
                    'errors' => $exception->errors(),
                ], $exception->status);
            }

            if ($exception instanceof AuthenticationException) {
                if (! $json) {
                    return null;
                }
                $status = 401;
                $headers = [];
            } elseif ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $headers = $exception->getHeaders();
            } else {
                if (config('app.debug') && ! $json) {
                    return null;
                }
                $status = 500;
                $headers = [];
            }

            [$title, $message] = ExceptionMessage::forStatus($status);

            return $json
                ? response()->json(['message' => $message], $status, $headers)
                : response()->view('errors.message', compact('title', 'message', 'status'), $status, $headers);
        });
    })->create();

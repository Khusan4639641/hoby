<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
             \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'modify.request'
        ],

        'api' => [
            'throttle:1000,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'tojson',
            'modify.request',
            'locale.api'
        ],

        'apiV3' => [
            'throttle:6000,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'modify.request' => \App\Http\Middleware\ModifyRequest::class,
        'locale' => \App\Http\Middleware\Locale::class,
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\Guest::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'global' => \App\Http\Middleware\GlobalMiddleware::class,
        'entry' => \App\Http\Middleware\Entry::class,
        'cabinet' => \App\Http\Middleware\Cabinet::class,
        'billing' => \App\Http\Middleware\Billing::class,
        'inactive' => \App\Http\Middleware\Inactive::class,
        'active' => \App\Http\Middleware\Active::class,
        'tojson' => \App\Http\Middleware\ToJsonApi::class,
        'locale.api' => \App\Http\Middleware\LocaleApi::class,
        'api.authenticate' => \App\Http\Middleware\ApiAuthenticate::class,

        'profile' => \App\Http\Middleware\Profile::class, // 08.04 для проверки покупателя на верификацию
        'redirect' => \App\Http\Middleware\Redirect::class, // 18.05 для редиректа

        'ml_access' => \App\Http\Middleware\MLAccess::class,
        'delta.auth' => \App\Http\Middleware\DeltaAuthMiddleware::class,

    ];


    protected $middlewarePriority = [
        \App\Http\Middleware\Authenticate::class,
        \App\Http\Middleware\GlobalMiddleware::class,
        \App\Http\Middleware\ToJsonApi::class,
    ];
}

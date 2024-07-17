<?php

namespace App\Exceptions;

use App\Classes\Exceptions\MLException;
use App\Classes\Exceptions\MLRequestIDException;
use App\Classes\Exceptions\MLValidationException;
use App\Classes\Payments\PaymentException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param \Throwable $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {

        if ($exception instanceof MLValidationException ||
            $exception instanceof MLException ||
            $exception instanceof HttpException) {
            return;
        }

        if ($exception instanceof PaymentException) {
            Log::channel('payments')->error($exception->getMessage());
        }

        if (!env('APP_DEBUG')) {
            if ($exception) {
                if ($this->shouldReport($exception) && app()->bound('sentry')) {
                    app('sentry')->captureException($exception);
                }
                parent::report($exception);
                if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {
                    $isAppDebug = env('APP_DEBUG');
                    $errors = [];
                    if ($isAppDebug) {
                        $errors = $exception->getMessage();
                    }
                    header('Content-type: application/json');
                    echo json_encode([
                        'status'   => 'error',
                        'response' => [
                            'code'    => '404',
                            'message' => 'not_found',
                            'errors'  => $errors
                        ],
                        'data'     => [],
                    ]);

                    Log::channel('errors')->info($exception);

                    exit;
                } else {
                    system_dump($exception);
                }
            }
        }

    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $exception->getMessage()], 401)
            : redirect()->guest(route('home'));
    }

}

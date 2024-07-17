<?php

namespace App\Classes\Exceptions;

use Throwable;

class BaseException extends \Exception
{

    private string $url;
    private array $request;
    private array $response;

    public function __construct(string    $message = "",
                                string    $url = "",
                                array     $request = [],
                                array     $response = [],
                                int       $code = 0,
                                Throwable $previous = null)
    {
        $this->url = $url;
        $this->request = $request;
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function urlText(): string
    {
        return $this->url;
    }

    public function requestArray(): array
    {
        return $this->request;
    }

    public function responseArray(): array
    {
        return $this->response;
    }

}

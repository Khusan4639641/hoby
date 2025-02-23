<?php

namespace App\Classes\Exceptions;

use Throwable;

class KatmReportException extends \Exception
{

    private array $data;

    public function __construct(string    $message,
                                array     $data = [],
                                int       $code = 0,
                                Throwable $previous = null)
    {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    public function getData(): array
    {
        return $this->data;
    }

}

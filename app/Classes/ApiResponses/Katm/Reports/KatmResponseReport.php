<?php

namespace App\Classes\ApiResponses\Katm\Reports;

use App\Classes\Exceptions\KatmException;

class KatmResponseReport
{

    private array $data;

    public function __construct(string $report)
    {
        $this->data = json_decode($report, true);
    }

    public function info(): array
    {
        return $this->data;
    }

    public function text(): string
    {
        return json_encode($this->data);
    }

    /**
     * @throws KatmException
     */
    public function report(): array
    {
        $data = $this->info();
        if (!isset($data['report'])) {
            throw new KatmException("Элемент report не найден", "", [], $data);
        }
        return $data['report'];
    }

}

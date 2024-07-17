<?php

namespace App\Jobs\MkoJobs;

use App\Services\MFO\Account1CService;
use Illuminate\Http\Client\{HttpClientException, PendingRequest};
use Illuminate\Support\Facades\{DB, Http};
use Illuminate\Support\{Carbon, Collection};

class MkoFrom1C
{
    public Account1CService $account1cService;
    private string          $cHost;
    private PendingRequest  $httpClient;
    private string          $oneCDataError     = 'Ошибка получения данных с сервера 1С';
    public static string    $clientsTempTable  = 'clients_1c_temp';
    public static string    $accountsTempTable = 'accounts_1c_temp';
    private Carbon          $dateFrom;
    private Carbon          $dateTo;

    public function __construct($dateFrom, $dateTo)
    {
        $this->dateFrom         = $dateFrom;
        $this->dateTo           = $dateTo;
        $this->cHost            = config('test.ODIN_C_HOST');
        $this->httpClient       = Http::withBasicAuth(config('test.ODIN_C_LOGIN'), config('test.ODIN_C_PASSWORD'));
        $this->account1cService = new Account1CService();
    }

    public function collect001(): void
    {
        DB::table('accounts_1c_temp')->truncate();
        $this->account1cService->generateReport($this->dateFrom, $this->dateTo);
    }

    public function collect002(): void
    {
        DB::table(self::$clientsTempTable)->truncate();
        $url    = $this->cHost . '/mcoclients/';
        $result = $this->httpClient->get($url);
        $data   = $result->json();

        if (!$data) {
            throw new HttpClientException($this->oneCDataError . ': ' .
                                          $result->toPsrResponse()
                                              ->getReasonPhrase(), 500);
        }
        $records  = $data["КлиентыМКО"];
        $toTempDb = [];
        foreach ($records as $key => $client) {
            $toTempDb[$key]['id']                  = (int)$client['НомерУчетнойКарточки'];
            $toTempDb[$key]['nibbd']               = $client['КодНИББД'];
            $toTempDb[$key]['surname']             = null;
            $toTempDb[$key]['name']                = null;
            $toTempDb[$key]['patronymic']          = null;
            $toTempDb[$key]['gender']              = null;
            $toTempDb[$key]['birth_date']          = null;
            $toTempDb[$key]['region']              = null;
            $toTempDb[$key]['local_region']        = null;
            $toTempDb[$key]['passport_type']       = null;
            $toTempDb[$key]['passport_number']     = null;
            $toTempDb[$key]['passport_date_issue'] = null;
            $toTempDb[$key]['passport_issued_by']  = null;
            $toTempDb[$key]['inn']                 = $client['ИНН'];
            $toTempDb[$key]['client_type']         = $client['ТипСубъекта'];
            $toTempDb[$key]['record_type']         = 1;
        }
        DB::table(self::$clientsTempTable)->insert($toTempDb);
    }

    public function collect005(): Collection
    {
        $url    = $this->cHost . '/deposits/' . $this->dateTo->addDay()->format('Ymd');
        $result = $this->httpClient->get($url);
        $data   = $result->json();

        if (!$data) {
            throw new HttpClientException($this->oneCDataError . ': ' .
                                          $result->toPsrResponse()
                                              ->getReasonPhrase(), 500);
        }

        return new Collection($data['Депозиты']);
    }

    public function collect006(): Collection
    {
        $url    = $this->cHost . '/loans/' . $this->dateTo->addDay()->format('Ymd');
        $result = $this->httpClient->get($url);
        $data   = $result->json();
        if (!$data) {
            throw new HttpClientException($this->oneCDataError . ': ' . $result->body(), 500);
        }

        return new Collection($data['Кредиты']);
    }

    public function collect007(): Collection
    {
        $url    = $this->cHost . '/mcoinfo';
        $result = $this->httpClient->get($url);
        $data   = $result->json();
        if (!$data) {
            throw new HttpClientException($this->oneCDataError . ': ' . $result->body(), 500);
        }

        return new Collection($data);
    }

    public function collect008(): Collection
    {
        $url    = $this->cHost . '/founders';
        $result = $this->httpClient->get($url);
        $data   = $result->json();
        if (!$data) {
            throw new HttpClientException($this->oneCDataError . ': ' . $result->body(), 500);
        }

        return new Collection($data['Учредители']);
    }

    public function getClient(): PendingRequest
    {
        return $this->httpClient;
    }
}

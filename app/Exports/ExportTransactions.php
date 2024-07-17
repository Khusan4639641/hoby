<?php

namespace App\Exports;

use App\Http\Requests\FakeTransactionExportRequest;
use App\Services\FakeTransaction;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;


class ExportTransactions implements FromView
{
    /**
     * @return \Illuminate\Support\Collection
     */
    use Exportable;

    protected $filter;

    protected $Company = [];

    protected $fakeTransaction = [];

    /**
     * @var FakeTransactionExportRequest
     */
    private $fakeRequest;

    public function __construct(FakeTransactionExportRequest $fakeRequest) {
        $this->fakeTransaction = new FakeTransaction();
        $this->fakeRequest = $fakeRequest;
    }

    public function view(): View
    {
        if (
            !( Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance') )
        ) {
            abort('403');
        }
        return view('panel.fake.create', [
            'transactions' => $this->fakeTransaction->dataForExcel($this->fakeRequest)
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\LetterController as Controller;
use App\Models\Contract;
use App\Models\Letter;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LetterController extends Controller {

    /**
     * @param Collection | array $items
     * @return array
     */
    protected function formatDataTables($items) {

        $i = 0;
        $data = [];

        foreach ( $items as $item ) {
            $data[$i][] = '<div class="date">'.$item->id.'</div>';
            $data[$i][] = '<div class="inner">
                               <div class="title"><a href="'.localeRoute('panel.postal-regions.edit', $item).'">'.mb_substr($item->name, 0, 70).'</a></div>
                           </div>';
            $data[$i][] = '<button onclick="confirmDelete('.$item->id.')" type="button"
                                class="btn-delete">'.__('app.btn_delete').'</button>';
            $i++;
        }

        return parent::formatDataTables($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $user = Auth::user();

        //if ($user->hasPermission('modify-news')) {
        if (true) {

            return view('panel.letters.index', compact('user'));

        } else {

            $this->message('danger', __('app.err_access_denied'));
            return redirect(
                localeRoute('panel.index'))->with('message', $this->result['response']['message']
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {

        $contract = Contract::with('buyer')->find($request->contract_id);

        $help_phone = callCenterNumber(3);


        $pdf = \PDF::loadView('panel.contract.letters.pretension_pdf', compact('contract', 'help_phone'));
        $pdf->setOptions(['isFontSubsettingEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->stream('test.pdf');
        return base64_encode($pdf->stream('test.pdf'));

        //dd($request->all());

        $result = $this->add($request);

        if ($result['status'] != 'success') {

            //Define redirect route
            if ($result['response']['code'] == 403)
                $route = 'panel.index';
            else
                $route = 'panel.letters.create';

            return redirect(localeRoute($route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with('message', $result['response']['message']);

        } else {
            return redirect(localeRoute('panel.letters.index'))
                    ->with('message', $result['response']['message']);
        }
    }
}

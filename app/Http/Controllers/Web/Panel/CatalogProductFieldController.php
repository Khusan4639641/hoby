<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\CatalogProductFieldController as Controller;
use App\Models\CatalogCategory;
use App\Models\CatalogProductField;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


class CatalogProductFieldController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function index() {
        $user = Auth::user();

        if (  $user->hasPermission( 'modify-product-field' ) ) {
            return view( 'panel.catalog.field.index', compact( 'user' ) );
        } else {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        }
    }


    /**
     * @param Collection $items
     *
     * @return array
     */
    protected function formatDataTables( $items ) {
        $data  = [];
        $i = 0;
        foreach ($items as $item){
            $data[ $i ][] = '<div class="title">' .  $item->title . '</div>';
            $data[ $i ][] = '<a href="' . localeRoute( 'panel.catalog.fields.edit', $item ) . '" class="link">' . __( 'app.btn_change' ) . '</a>';
            $data[ $i ][] = '<button onclick="confirmDelete(' . $item->id . ')" type="button"
                                class="btn-delete">' . __( 'app.btn_delete' ) . '</button>';
            $i++;
        }
        return parent::formatDataTables( $data );
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create() {
        $user = Auth::user();
        if ( $user->can( 'add', CatalogProductField::class ) ) {
            $languages          = LocaleHelper::languages();

            return view( 'panel.catalog.field.create', compact( 'languages' ) );
        } else {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function store( Request $request ) {
        $result = $this->add( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if ( $result['response']['code'] == 403 ) {
                $route = 'panel.catalog.fields.index';
            } else {
                $route = 'panel.catalog.fields.create';
            }

            return redirect( localeRoute( $route ) )
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect( localeRoute( 'panel.catalog.fields.index' ) )->with( 'message', $result['response']['message'] );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show( $id ) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     *
     * @return Application|Factory|View
     */
    public function edit( $id ) {
        $result = $this->detail( $id );

        if ( $result['status'] != 'success' && $result['response']['code'] == 403 ) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            $languages          = LocaleHelper::languages();
            $field            = $result['data'];

            return view( 'panel.catalog.field.edit', compact( 'languages', 'field' ) );
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param CatalogProductField $field
     * @return Application|RedirectResponse|Redirector
     */
    public function update( Request $request, CatalogProductField $field ) {

        $result = $this->modify( $request, $field );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if ( $result['response']['code'] == 403 ) {
                $route = localeRoute( 'panel.catalog.fields.index' );
            } else {
                $route = localeRoute( 'panel.catalog.fields.edit', $field );
            }

            return redirect( $route )
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect( localeRoute( 'panel.catalog.fields.index' ) )->with( 'message', $result['response']['message'] );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param CatalogProductField $field
     * @return Application|RedirectResponse|Redirector
     */
    public function destroy( CatalogProductField $field ) {
        $result = $this->delete( $field );

        return redirect( localeRoute( 'panel.catalog.fields.index' ) )->with( 'message', $result['response']['message'] );
    }
}

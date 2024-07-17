<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\CatalogCategoryController as Controller;
use App\Models\CatalogCategory;
use App\Models\CatalogProduct;
use App\Models\CatalogProductField;
use App\Models\Permission;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


class CatalogCategoryController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function index() {
        $user = Auth::user();
        if (  $user->hasPermission( 'modify-category' ) ) {
            return view( 'panel.catalog.category.index', compact( 'user' ) );
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
        $this->makeItems($items, $data, $i);
        return parent::formatDataTables( $data );
    }

    private function makeItems ($items, &$data, &$i){

        foreach ($items as $item){
            $space = str_repeat("—", $item->level);
            $data[ $i ][] = '<div class="title">' . $space . " " .  $item->locale->title . '</div>';
            $data[ $i ][] = '<div class="preview-text">' . mb_substr($item->locale->preview_text, 0, 100) . '...</div>';
            $data[ $i ][] = '<a href="' . localeRoute( 'panel.catalog.categories.edit', $item ) . '" class="link">' . __( 'app.btn_change' ) . '</a>';
            $data[ $i ][] = '<button onclick="confirmDelete(' . $item->id . ')" type="button"
                                class="btn-delete">' . __( 'app.btn_delete' ) . '</button>';
            $i++;
            if(count($item->child) > 0) {
                $this->makeItems($item->child, $data, $i);
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create() {
        $user = Auth::user();
        if ( $user->can( 'add', CatalogCategory::class ) ) {
            $languages          = LocaleHelper::languages();
            $categories         = \App\Http\Controllers\Core\CatalogCategoryController::tree();
            $allFields = CatalogProductField::all();

            return view( 'panel.catalog.category.create', compact( 'languages', 'categories', 'allFields' ) );
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
                $route = 'panel.catalog.categories.index';
            } else {
                $route = 'panel.catalog.categories.create';
            }

            return redirect( localeRoute( $route ) )
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect( localeRoute( 'panel.catalog.categories.index' ) )->with( 'message', $result['response']['message'] );
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
            $category            = $result['data'];
            $categories         = CatalogCategoryController::tree(0, [$category->id]);

            $allFields = CatalogProductField::all();
            $categoryFields = $category->fields;

            $categoryFields = $categoryFields->keyBy('id');

            return view( 'panel.catalog.category.edit', compact( 'languages', 'categories', 'category', 'allFields', 'categoryFields' ) );
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param CatalogCategory $category
     * @return Application|RedirectResponse|Redirector
     */
    public function update( Request $request, CatalogCategory $category ) {

        $result = $this->modify( $request, $category );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if ( $result['response']['code'] == 403 ) {
                $route = localeRoute( 'panel.catalog.categories.index' );
            } else {
                $route = localeRoute( 'panel.catalog.categories.edit', $category );
            }

            return redirect( $route )
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect( localeRoute( 'panel.catalog.categories.index' ) )->with( 'message', $result['response']['message'] );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param CatalogCategory $category
     * @return Application|RedirectResponse|Redirector
     * @throws \Exception
     */
    public function destroy( CatalogCategory $category ) {
        $result = $this->delete( $category );

        return redirect( localeRoute( 'panel.catalog.categories.index' ) )->with( 'message', $result['response']['message'] );
    }
}

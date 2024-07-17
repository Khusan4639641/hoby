<?php

namespace App\Console\Commands;

use App\Facades\OldCrypt;
use App\Models\CatalogCategory;
use App\Models\CatalogCategoryLanguage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class UtilsAction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'utils:action {action} {arg*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $arg = $this->argument('arg');
        switch($action){
            case 'delete':
                $this->delete($arg);
                break;
            case 'crypt':
                $this->cryptString($arg);
                break;
            case 'catalog':
                $this->loadCatalog($arg);
                break;
            case 'password':
                $this->genPassword($arg);
                break;
        }
        return 0;
    }

    protected function delete($arg){

    }

    protected function genPassword($arg){
        $this->info(Hash::make($arg[0]));
    }

    protected function cryptString($arg){
        if(sizeof($arg) > 0) {
            $method = $arg[0];
            $string = $arg[1];
            switch ($method) {
                case 'encode':
                    $result = OldCrypt::encryptString($string);
                    break;
                case 'decode':
                    $result = OldCrypt::decryptString($string);
                    break;
                default:
                    $result = OldCrypt::encryptString($method);
            }
            $this->info($result);
        }else{
            $this->info('Incorrect argument in command line. utils:action crypt encode|decode string');
        }
    }

    protected function loadCatalog($arg){
        $arCatalog = json_decode($arg[0],1);


        function recursiveLoadCatalog($arCatalog, $parentId = 0){
            foreach($arCatalog as $title=>$subtitle){
                $catalog = new CatalogCategory();
                $catalogLang = new CatalogCategoryLanguage();
                $catalog->parent_id = $parentId;
                //dd($catalog);
                $catalog->save();
                $catalogLang->language_code = 'uz';
                $catalogLang->category_id = $catalog->id;
                $catalogLang->title = $title;
                $catalogLang->slug = Str::slug($title, '-');
                $catalogLang->detail_text = '';
                $catalogLang->preview_text = '';
                $catalogLang->save();
                //dd($subtitle);
                if(sizeof($subtitle)>0){
                    recursiveLoadCatalog($subtitle, $catalog->id);
                }
            }
        }

        recursiveLoadCatalog($arCatalog);

        dd($arCatalog);
    }
}

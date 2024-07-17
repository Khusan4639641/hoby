<?php

namespace App\Helpers;

use App\Models\File;

use Illuminate\Http\File as IlluminateFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use App\Services\FileHistoryService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;


class FileHelper {


    /**
     * @return string
     */
    public static function sourcePath(): string
    {
        return Config::get('test.sftp_file_server_domain') . 'storage/';
    }


    /**
     * @param string $filename
     *
     * @return string
     */
    public static function url($filename) {
        return self::sourcePath() . $filename;
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public static function exists($filename) {
        return Storage::disk('sftp')->exists($filename);
    }

    /**
     * @param string $filename
     * @param string $view
     * @param array $data
     *
     * @return void
     */
    public static function generateAndUploadPDF(string $filename, string $view, array $data = []): void
    {
        $pdf = \PDF::loadView($view, $data);
        Storage::disk('sftp')->put($filename, $pdf->output());
    }

    /**
     * @param int $regUserID
     * @param int $modelID
     * @param string $modelName
     * @param string $type
     * @param string $langCode
     * @param string $path
     * @param string $htmlText
     * @return File|null
     */
    public static function uploadHtml(int $regUserID, int $modelID, string $modelName, string $type, string $langCode, string $path, string $htmlText): ?File
    {
        $fileName = md5(time()) . '.html';
        $fullPath = $path . $fileName;
        $saved = Storage::disk('sftp')->put($fullPath, $htmlText);
        if ($saved) {
            $file = new File();
            $file->element_id = $modelID;
            $file->model = $modelName;
            $file->type = $type;
            $file->name = $fileName;
            $file->path = $fullPath;
            $file->language_code = $langCode;
            $file->user_id = $regUserID;
            $file->doc_path = 1;

            $file->save();
            return $file;
        }
        return null;
    }

    /**
     * @param int $modelID
     * @param string $modelName
     * @param string $type
     * @param string $langCode
     * @param string $path
     * @param string $view
     * @param array $data
     *
     * @return void
     * @throws Throwable
     */
    public static function generateAndUploadHtml(int $modelID, string $modelName, string $type, string $langCode, string $path, string $view, array $data = []): void
    {

        $userID = Auth::id();

        $html = view($view, $data)->render();

        $fileName = md5(time()) . '.html';
        $fullPath = $path . $fileName;

        $saved = Storage::disk('sftp')->put($fullPath, $html);

        if ($saved) {
            $file = new File();
            $file->element_id = $modelID;
            $file->model = $modelName;
            $file->type = $type;
            $file->name = $fileName;
            $file->path = $fullPath;
            $file->language_code = $langCode;
            $file->user_id = $userID;
            $file->doc_path = 1;

            $file->save();
        }
    }

    /**
     * @param int $modelID
     * @param string $modelName
     * @param string $type
     * @param string $locationPath
     * @param string $filename
     * @param string $text
     * @param string $extension
     * @param int $userID
     * @param string $langCode
     * @return void
     */
    public static function uploadToPath(
        int    $modelID,
        string $modelName,
        string $type,
        string $locationPath,
        string $filename,
        string $text,
        string $extension,
        int    $userID,
        string $langCode = "ru"
    ): int
    {
        $filename .= "." . $extension;
        $fullPath = $locationPath . $filename;
        $saved = Storage::disk('sftp')->put($fullPath, $text);
        if ($saved) {
            $file = new File();
            $file->element_id = $modelID;
            $file->model = $modelName;
            $file->type = $type;
            $file->name = $filename;
            $file->path = $fullPath;
            $file->language_code = $langCode;
            $file->user_id = $userID;
            $file->doc_path = 1;
            $file->save();
            return $file->id;
        }
        return 0;
    }

    /**
     * @param array $params
     * @param array $delete
     * @param bool $private
     *
     * @param null $user
     *
     * @return bool
     */
    public static function upload(array $params = [], array $delete = [], $private = false, $user = null) {

        if ( !$user ) {
            $user = Auth::user();
        }


        if ( $params['model'] !== 'news-language' ) {
            $params['user_id'] = $user->id;
            return self::uploadNew( $params );
        }



        $request = Request::all();

        if (isset( $request['api_token'] ) && !$private ) {
            unset( $request['api_token'] );
            $params = $request;
        }

        if ( $user->can( 'add', File::class) ) {
            if ( count( $params ) > 0 ) {

                //Удаление файлов по списку   - НЕ нужно удалять старые акты
                if ( is_array( $delete ) && count( $delete ) > 0 ) {
                    foreach ( $delete as $f ) {
                        self::delete( $f );
                    }
                }

                $path = "{$params['model']}/{$params['element_id']}/";

                if ( Request::get( 'api_token' ) && !$private ) {

                    if ( Request::get( 'files' ) !== '' ) {
                        $items = json_decode( Request::get( 'files' ), true );
                        if ( count( $items ) > 0 ) {
                            foreach ( $items as $item ) {


                                if(isset($item['string'])){
                                    if(isset($item['delete_id']) && is_numeric($item['delete_id']))
                                        self::delete($item['delete_id']);

                                    $fileInfo = pathinfo( $item['name'] );
                                    $fileName = md5( $item['name'] . time() . uniqid() ) . "." . $fileInfo['extension'];
                                    $fullPath = $path . $fileName;
                                    $saved    = Storage::put( $fullPath, base64_decode( str_replace( ' ', '+', $item['string'] ) ) );
                                    //$saved    = Storage::disk('ftp')->put( $fullPath, base64_decode( str_replace( ' ', '+', $item['string'] ) ) );

                                    if ( $saved ) {
                                        $file                   = new File();
                                        $file->element_id       = $params['element_id'];
                                        $file->model            = $params['model'];
                                        $file->type             = $item['type'];
                                        $file->language_code    = $params['language_code'] ?? null;
                                        $file->path             = $fullPath;
                                        $file->name             = $fileName;
                                        $file->user_id          = $user->id;

                                        $file->save();
                                        FileHistoryService::add($file->id);
                                    } else {
                                        return false;
                                    }
                                }
                            }

                            return true; //json_encode(array('id'=>$file->id));
                        }
                    }
                } else {

                    $items = $params['files'];
                    if ( count( $items ) > 0 ) {

                        foreach ( $items as $type => $item ) {

                            if ( is_array( $item ) ) {

                                foreach ( $item as $value ) {
                                    $fileInfo = pathinfo( $value->getClientOriginalName() );
                                    $fileName = md5( $value->getClientOriginalName() . time() ) . "." . $fileInfo['extension'];
                                    $fullPath = $path . $fileName;

                                    $saved = Storage::putFileAs( $path, $value, $fileName );

                                    if ( $saved ) {
                                        $file                   = new File();
                                        $file->element_id       = $params['element_id'];
                                        $file->model            = $params['model'];
                                        $file->language_code    = $params['language_code'] ?? null;
                                        $file->type             = $type;
                                        $file->name             = $fileName;
                                        $file->path             = $fullPath;
                                        $file->user_id          = $user->id;

                                        $file->save();
                                      FileHistoryService::add($file->id);
                                    } else {
                                        return false;
                                    }

                                }
                            } else {
                                $fileInfo = pathinfo( $item->getClientOriginalName() );
                                $fileName = md5( $item->getClientOriginalName() . time() ) . "." . $fileInfo['extension'];
                                $fullPath = $path . $fileName;

                                Log::info('save contract act path ' . $path . ' filename: ' . $fileName);
                                $saved = Storage::putFileAs( $path, $item, $fileName );

                                if ( $saved ) {
                                    $file             = new File();
                                    $file->element_id = $params['element_id'];
                                    $file->model      = $params['model'];
                                    $file->type       = $type;
                                    $file->name             = $fileName;
                                    $file->path       = $fullPath;
                                    $file->language_code    = $params['language_code'] ?? null;
                                    $file->user_id    = $user->id;

                                    $file->save();
                                    FileHistoryService::add($file->id);
                                } else {
                                    return false;
                                }
                            }
                        }

                        return true;
                    }
                }
            }
        }
        return false;
    }


    /** Загрузка файлов на другой сервер - ////
     * @param array $params
     * @param array $delete
     * @param bool $private
     *
     * @param null $user
     *
     * @return bool
     */
    public static function uploadNew(array $params,$return = false) {

        $result = true;

        try {

            if ( $params ) {
                $path = "{$params['model']}/{$params['element_id']}/";
                foreach($params['files'] as $type => $item){

//                    $fileInfo = pathinfo( $item->getClientOriginalName() );
//                    $fileName = md5( $item->getClientOriginalName() . time() ) . "." . $fileInfo['extension'];
                    $fileName = md5( $item->getClientOriginalName() . time() ) . "." . $item->extension();

                    $saved = Storage::disk('sftp')->putFileAs( $path, $item, $fileName );

                    if ($saved) {

                        $fullPath = $path . $fileName;

                        $file                   = new File();
                        $file->element_id       = $params['element_id'];
                        $file->model            = $params['model'];
                        $file->type             = $type;
                        $file->name             = $fileName;
                        $file->path             = $fullPath;
                        $file->language_code    = $params['language_code'] ?? null;
                        $file->user_id          = $params['user_id'];
                        $file->doc_path         = 1;

                        $file->save();
                        FileHistoryService::add($file->id);
                        if($return) return $file;
                    }

                }

            }

        } catch (\Exception $e) {
            $result = false;
            Log::info('new files error');
            Log::info($e);
        }

        return $result;

    }


    /** Загрузка файла на другой сервер - ////
     * @param UploadedFile $file
     * @param string $type
     * @param string $model
     * @param integer $element_id
     * @param integer $user_id
     * @param string|null $language_code
     *
     * @return string|boolean
     */
    public static function simpleUpload(
        UploadedFile $file, string $type, string $model, int $element_id, int $user_id, string $language_code = null
    ) {

        $result = true;

        try {
            $path = "{$model}/{$element_id}/";
            $fileName = md5($file->getClientOriginalName(). time() )  .  "."  .  $file->extension();

            // Работает локально
//            $saved = Storage::disk('local')->put( $path . $fileName, $file );
            // Не работает локально, если $file не является инстансом либо Illuminate\Http\File, либо Illuminate\Http\UploadedFile
//            $saved = Storage::disk('local')->putFileAs( $path, $file, $fileName );
            $saved = Storage::disk('sftp')->putFileAs( $path, $file, $fileName );

            if ($saved) {
                $fullPath = $path . $fileName;

                $file = File::create([
                    'element_id'       => $element_id,
                    'model'            => $model,
                    'type'             => $type,
                    'name'             => $fileName,
                    'path'             => $fullPath,
                    'language_code'    => $language_code,
                    'user_id'          => $user_id,
                    'doc_path'         => 1
                ]);

                return $fullPath;
            }
        } catch (\Exception $e) {
            $result = false;
            Log::channel("files")->error('simple file upload error');
            Log::channel("files")->error($e);
        }

        return $result;
    }

    /** Сохранение PDF-файла письма на другой сервер - ////
     * @param mixed $file_contents
     * @param array $file_data
     * @return string|boolean
     */
    public static function saveLetterFile($file_contents, array $file_data) {
        try {
            $path = "{$file_data["model"]}/{$file_data["element_id"]}";
            if (
                !($file_contents instanceof IlluminateFile)
                && !($file_contents instanceof UploadedFile)
            ) {
                $fileName = md5($file_data["type"] . $file_data["element_id"] . time() ) . "." . $file_data["extension"];
//                 Работает локально
//                $saved = Storage::disk('local')->put( $path . "/" . $fileName, $file_contents );
                // Не работает локально, если $file не является инстансом либо Illuminate\Http\File, либо Illuminate\Http\UploadedFile
//                $saved = Storage::disk('local')->putFileAs( $path, $file_contents, $fileName );
                $saved = Storage::disk('sftp')->put($path . "/" .  $fileName, $file_contents);
            } else {
                $file = $file_contents; // Для Illuminate\Http\File или Illuminate\Http\UploadedFile
                $fileName = md5($file_data["type"] . $file_data["element_id"] . time() ) . "." . $file->extension();
//                $saved = Storage::disk('local')->putFileAs( $path, $file, $fileName );
                $saved = Storage::disk('sftp')->putFileAs( $path, $file, $fileName );
            }

            if ($saved) {
                return $path . "/" .  $fileName; // $fullPath, например: contracts-recovery/1235511/c609b489fe5b1a4fca0896b548b655b5.jpg
            }

        } catch (\Exception $e) {
            Log::channel("files")->error('saveLetterFile file save error:');
            Log::channel("files")->error($e);
        }

        return false;
    }


    /**
     * Удаление файла по его id
     *
     * @param $fileId
     * @return bool
     */
    public static function delete( $id ) {

        $user = Auth::user();


        if(is_array($id)) {
            $check = true;
            for($i = 0 ; $i < count($id); $i ++){
                if (is_numeric($id[$i])) {
                    $file = File::find($id[$i]);
                    if ($user->can('delete', $file)) {

                        if ($file) {
                            //Delete file
                            if (Storage::exists($file->path))
                                Storage::delete($file->path);

                            //Delete preview
                            $preview = str_replace($file->name, 'preview_'.$file->name, $file->path);
                            if (Storage::exists($preview))
                                Storage::delete($preview);

                            File::destroy($id[$i]);
                        }else
                            $check = false;
                    }else
                        $check = false;
                }else
                    $check = false;
            }

        } else {
            $check = false;
            if (is_numeric($id)) {
                $file = File::find($id);
                if ($user->can('delete', $file)) {

                    if ($file) {
                        //Delete file
                        if (Storage::exists($file->path))
                            Storage::delete($file->path);

                        //Delete preview
                        $preview = str_replace($file->name, 'preview_'.$file->name, $file->path);
                            if (Storage::exists($preview))
                                Storage::delete($preview);

                        File::destroy($id);

                        $check = true;
                    }
                }
            }
        }

        return Request::get( 'api_token' ) ? json_encode( array('success'=>$check) ) : $check;
    }

    public static function importFile($file){

        $user = Auth::user();


        $fileInfo = pathinfo( $file['name'] );
        $fileName = md5( $file['name'] . time() . uniqid() ) . "." . $fileInfo['extension'];

        $fullPath = "company/{$user->id}/$fileName";

        $saved = Storage::putFileAs( $fullPath, $file, $fileName );

        if ( $saved ) {

            return true;

            /*$file                   = new File();
            $file->element_id       = $params['element_id'];
            $file->model            = $params['model'];
            $file->type             = $item['type'];
            $file->language_code    = $params['language_code'] ?? null;
            $file->path             = $fullPath;
            $file->name             = $fileName;
            $file->user_id          = $user->id;

            $file->save(); */
        }

        return false;

    }


}

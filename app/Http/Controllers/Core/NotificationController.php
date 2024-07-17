<?php


namespace App\Http\Controllers\Core;


use Illuminate\Support\Facades\Auth;

class NotificationController extends CoreController {

    public function all() {
        $user = Auth::user();

        if ( $user ) {
            $this->result['status'] = 'success';
            $this->result['data'] = $user->notifications;
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'auth.error_user_not_found' ) );
        }

        return $this->result();
    }

}

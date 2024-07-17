<?php


namespace App\Http\Controllers\Core;

use App\Http\Controllers\Core\PaymentController;
use App\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends CoreController {

    /* Отчеты продавца */

    /**
     * @param array $params
     *
     * @return array|false|string
     */
    public function partner( $params = [] ) {
        $user = Auth::user();

        if ( $user->hasRole( 'partner' ) ) {

            $baseFilter = [
                'status__moe' => 3,
                'status__not' => 9
            ];

            $request = request()->all();
            if ( isset( $request['api_token'] ) ) {
                unset( $request['api_token'] );
                $params = $request;
            }

            if ( isset( $params['filter'] ) ) {

                $dateStart = $dateEnd = date( "Y-m-d" );
                $countDays = 1; //текущий день включительно
                $division  = "M";


                switch ( $params['period'] ) {
                    case 'last_day':
                        $dt        = new \DateTime();
                        $dateStart = $dt->modify( '-1 day' )->modify( '1 day' )->format( "Y-m-d" );
                        $dateEnd   = date( "Y-m-d" );
                        $division  = "D";
                        break;
                    case 'last_week':
                        $dt        = new \DateTime();
                        $dateStart = $dt->modify( '-1 week' )->modify( '1 day' )->format( "Y-m-d" );
                        $dateEnd   = date( "Y-m-d" );
                        $division  = "D";
                        break;
                    case 'last_month':
                        $dt        = new \DateTime();
                        $dateStart = $dt->modify( '-1 month' )->modify( '1 day' )->format( "Y-m-d" );
                        $dateEnd   = date( "Y-m-d" );
                        $division  = "W";
                        break;
                    case 'last_half_year':
                        $dt        = new \DateTime();
                        $dateStart = $dt->modify( '-6 month' )->modify( '1 day' )->format( "Y-m-d" );
                        $dateEnd   = date( "Y-m-d" );
                        $division  = "M";
                        break;
                    case 'custom':
                        if ( isset( $params['date'] ) && is_array( $params['date'] ) ) {
                            $dt        = new \DateTime( $params['date'][0] );
                            $dateStart = ( $dt === false ) ? date( "Y-m-d" ) : $dt->format( "Y-m-d" );

                            $dt      = new \DateTime( $params['date'][1] );
                            $dateEnd = ( $dt === false ) ? date( "Y-m-d" ) : $dt->format( "Y-m-d" );
                            unset( $params['date'] );
                        }
                        break;
                }

                $params['datetime__between'] = [ $dateStart . " 00:00:00", $dateEnd . " 23:59:59" ];

                $date      = Carbon::createFromDate( $dateStart );
                $countDays += $date->diffInDays( $dateEnd ); // Количество дней в выбранном периоде

                if ( $params['period'] == 'custom' ) {
                    if ( $countDays <= 7 ) {
                        $division = "D";
                    } elseif ( $countDays <= 30 ) {
                        $division = "W";
                    }
                }


                //Нарезка на куски по периодам

                $dtStart   = new \DateTime( $dateStart );
                $dtEnd     = new \DateTime( $dateEnd );
                $interval  = new \DateInterval( "P1$division" );
                $dateRange = new \DatePeriod( $dtStart, $interval, $dtEnd->modify( '1 day' ) );

                $parts          = [];
                $i              = 1;
                $iterator_count = iterator_count( $dateRange );
                $remainder      = $countDays;

                foreach ( $dateRange as $date ) {
                    $parts[ $i ]['start'] = $date->format( 'Y-m-d' );


                    $_date     = clone $date;
                    $iDivision = $date->diff( $_date->modify( "+1 month" ) )->days;

                    switch ( $division ) {
                        case 'D':
                            $iDivision = 1;
                            break;
                        case "W":
                            $iDivision = 7;
                            break;
                    }

                    if ( $i == $iterator_count && $remainder > 0 ) {
                        $parts[ $i ]['end'] = $date->modify( $remainder - 1 . " days" )->format( 'Y-m-d' );
                    } else {
                        $remainder          -= $iDivision;
                        $parts[ $i ]['end'] = $date->modify( $iDivision - 1 . " days" )->format( 'Y-m-d' );
                    }

                    $i ++;
                }

                unset( $params['period'] );
            }

            $filterParams = array_merge( $baseFilter, $params );

            //Filter elements
            $orders          = [
                'main'     => [],
                'received' => [],
            ];
            $orderController = new OrderController();
            $filter          = $orderController->filter( $filterParams );
            if ( $filter['total'] > 0 ) {
                $orders['main'] = $filter['result'];
            }

            $filterParams['contract.entries__has'] = true;
            $filterParams['credit']                = 0;

            $filter = $orderController->filter( $filterParams );
            if ( $filter['total'] > 0 ) {
                $orders['received'] = $filter['result'];
            }

            if ( isset( $params['filter'] ) ) {
                $data = [];

                $i = 0;
                foreach ( $parts as $part ) {
                    $_arrOrders = [
                        'main'     => [],
                        'received' => [],
                    ];
                    foreach ( $orders['main'] as $order ) {
                        $dtOrderCreatedAt = new \DateTime( $order->created_at );
                        $dtPartStart      = new \DateTime( $part['start'] );
                        $dtPartEnd        = new \DateTime( $part['end'] );


                        if ( $dtOrderCreatedAt >= $dtPartStart && $dtOrderCreatedAt <= $dtPartEnd ) {
                            $_arrOrders['main'][] = $order;
                        }
                    }

                    foreach ( $orders['received'] as $order ) {
                        $dtOrderCreatedAt = new \DateTime( $order->created_at );
                        $dtPartStart      = new \DateTime( $part['start'] );
                        $dtPartEnd        = new \DateTime( $part['end'] );


                        if ( $dtOrderCreatedAt >= $dtPartStart && $dtOrderCreatedAt <= $dtPartEnd ) {
                            $_arrOrders['received'][] = $order;
                        }
                    }

                    $dtPartStart = new \DateTime( $part['start'] );
                    $dtPartEnd   = new \DateTime( $part['end'] );

                    $_periods = [];

                    $_periods[] = $dtPartStart->format( 'd.m.Y' );
                    if ( $dtPartStart != $dtPartEnd ) {
                        $_periods[] = $dtPartEnd->format( 'd.m.Y' );
                    }

                    $data[ $i ]['period']     = implode( ' - ', $_periods );
                    $data[ $i ]['statistics'] = $this->partnerCalcOrders( $_arrOrders );
                    $i ++;
                }
            } else {
                $data = [
                    'statistics' => $this->partnerCalcOrders( $orders ),
                    'affiliates' => Company::whereParentId( $user->company_id )->get()
                ];
            }


            $this->result['status'] = 'success';
            $this->result['data']   = $data;
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }


        return $this->result();
    }

    private function partnerCalcOrders( $orders ) {
        $credit   = 0; //Долг Змаркет
        $debit    = 0; //Долг перед змаркет
        $sold     = 0; //Всего продано
        $received = 0; //Получено выплат

        foreach ( $orders['main'] as $order ) {

            if ( $order->credit > 0 ) {
                $credit += $order->credit;
            }

            if ( $order->debit > 0 ) {
                $debit += $order->debit;
            }

            if ( $order->partner_total > 0 ) {
                $sold += $order->partner_total;
            }
        }

        foreach ( $orders['received'] as $item ) {
            $received += $item->total;
        }

        return [
            'credit'   => $credit,
            'debit'    => $debit,
            'received' => $received,
            'sold'     => $sold,
        ];
    }


    /* Отчеты финансового отдела */


    public function finance( $params = [] ) {
        $user = Auth::user();

        if ( $user->hasRole( 'finance' ) ) {


            // Базовый фильтр для платежей
            $paymentBaseFilter = [
                'status' => 1
            ];

            // Базовый фильтр для договоров
            $orderBaseFilter = [
                'status__moe' => 3,
                'status__not' => 9
            ];

            $data = [];

            $request = request()->all();
            if ( isset( $request['api_token'] ) ) {
                unset( $request['api_token'] );
                $params = $request;
            }

            if ( isset( $params['filter'] ) ) {

                $dateStart = $dateEnd = date( "Y-m-d" );
                $countDays = 1; //текущий день включительно
                $division  = "M";


                switch ( $params['period'] ) {
                    case 'last_day':
                        $dt        = new \DateTime();
                        $dateStart = $dt->modify( '-1 day' )->modify( '1 day' )->format( "Y-m-d" );
                        $dateEnd   = date( "Y-m-d" );
                        $division  = "D";
                        break;
                    case 'last_week':
                        $dt        = new \DateTime();
                        $dateStart = $dt->modify( '-1 week' )->modify( '1 day' )->format( "Y-m-d" );
                        $dateEnd   = date( "Y-m-d" );
                        $division  = "D";
                        break;
                    case 'last_month':
                        $dt        = new \DateTime();
                        $dateStart = $dt->modify( '-1 month' )->modify( '1 day' )->format( "Y-m-d" );
                        $dateEnd   = date( "Y-m-d" );
                        $division  = "W";
                        break;
                    case 'last_half_year':
                        $dt        = new \DateTime();
                        $dateStart = $dt->modify( '-6 month' )->modify( '1 day' )->format( "Y-m-d" );
                        $dateEnd   = date( "Y-m-d" );
                        $division  = "M";
                        break;
                    case 'custom':
                        if ( isset( $params['date'] ) && is_array( $params['date'] ) ) {
                            $dt        = new \DateTime( $params['date'][0] );
                            $dateStart = ( $dt === false ) ? date( "Y-m-d" ) : $dt->format( "Y-m-d" );

                            $dt      = new \DateTime( $params['date'][1] );
                            $dateEnd = ( $dt === false ) ? date( "Y-m-d" ) : $dt->format( "Y-m-d" );
                            unset( $params['date'] );
                        }
                        break;
                }

                $params['datetime__between'] = [ $dateStart . " 00:00:00", $dateEnd . " 23:59:59" ];

                $date      = Carbon::createFromDate( $dateStart );
                $countDays += $date->diffInDays( $dateEnd ); // Количество дней в выбранном периоде

                if ( $params['period'] == 'custom' ) {
                    if ( $countDays <= 7 ) {
                        $division = "D";
                    } elseif ( $countDays <= 30 ) {
                        $division = "W";
                    }
                }


                //Нарезка на куски по периодам

                $dtStart   = new \DateTime( $dateStart );
                $dtEnd     = new \DateTime( $dateEnd );
                $interval  = new \DateInterval( "P1$division" );
                $dateRange = new \DatePeriod( $dtStart, $interval, $dtEnd->modify( '1 day' ) );

                $parts          = [];
                $i              = 1;
                $iterator_count = iterator_count( $dateRange );
                $remainder      = $countDays;

                foreach ( $dateRange as $date ) {
                    $parts[ $i ]['start'] = $date->format( 'Y-m-d' );


                    $_date     = clone $date;
                    $iDivision = $date->diff( $_date->modify( "+1 month" ) )->days;

                    switch ( $division ) {
                        case 'D':
                            $iDivision = 1;
                            break;
                        case "W":
                            $iDivision = 7;
                            break;
                    }

                    if ( $i == $iterator_count && $remainder > 0 ) {
                        $parts[ $i ]['end'] = $date->modify( $remainder - 1 . " days" )->format( 'Y-m-d' );
                    } else {
                        $remainder          -= $iDivision;
                        $parts[ $i ]['end'] = $date->modify( $iDivision - 1 . " days" )->format( 'Y-m-d' );
                    }

                    $i ++;
                }

                unset( $params['period'] );

                switch ($params['statistic_type']){
                    case "payments":

                        $filterParams = array_merge( $paymentBaseFilter, $params );

                        $payments = [];
                        $paymentController = new PaymentController();
                        $paymentFilter     = $paymentController->filter( $filterParams );
                        if ( $paymentFilter['total'] > 0 ) {
                            $payments = $paymentFilter['result'];
                        }

                        $i = 0;
                        foreach ( $parts as $part ) {
                            $_arrPayments = [];
                            foreach ( $payments as $payment ) {
                                $dtPaymentCreatedAt = new \DateTime( $payment->created_at );
                                $dtPartStart      = new \DateTime( $part['start'] );
                                $dtPartEnd        = new \DateTime( $part['end'] );


                                if ( $dtPaymentCreatedAt >= $dtPartStart && $dtPaymentCreatedAt <= $dtPartEnd ) {
                                    $_arrPayments[] = $payment;
                                }
                            }

                            $dtPartStart = new \DateTime( $part['start'] );
                            $dtPartEnd   = new \DateTime( $part['end'] );

                            $_periods = [];

                            $_periods[] = $dtPartStart->format( 'd.m.Y' );
                            if ( $dtPartStart != $dtPartEnd ) {
                                $_periods[] = $dtPartEnd->format( 'd.m.Y' );
                            }
                            $data[ $i ]['period']     = implode( ' - ', $_periods );
                            $data[ $i ]['statistics'] = $this->financeCalcPayments( $_arrPayments );
                            $i++;
                        }

                        break;

                    case "orders":

                        $filterParams = array_merge( $orderBaseFilter, $params );

                        $orders = [];
                        $paymentController = new OrderController();
                        $orderFilter     = $paymentController->filter( $filterParams );
                        if ( $orderFilter['total'] > 0 ) {
                            $orders = $orderFilter['result'];
                        }

                        $i = 0;
                        foreach ( $parts as $part ) {
                            $_arrOrders = [];
                            foreach ( $orders as $order ) {
                                $dtPaymentCreatedAt = new \DateTime( $order->created_at );
                                $dtPartStart      = new \DateTime( $part['start'] );
                                $dtPartEnd        = new \DateTime( $part['end'] );


                                if ( $dtPaymentCreatedAt >= $dtPartStart && $dtPaymentCreatedAt <= $dtPartEnd ) {
                                    $_arrOrders[] = $order;
                                }
                            }

                            $dtPartStart = new \DateTime( $part['start'] );
                            $dtPartEnd   = new \DateTime( $part['end'] );

                            $_periods = [];

                            $_periods[] = $dtPartStart->format( 'd.m.Y' );
                            if ( $dtPartStart != $dtPartEnd ) {
                                $_periods[] = $dtPartEnd->format( 'd.m.Y' );
                            }
                            $data[ $i ]['period']     = implode( ' - ', $_periods );
                            $data[ $i ]['statistics'] = $this->financeCalcOrders( $_arrOrders );
                            $i++;
                        }
                        break;
                }
            }

            $this->result['status'] = 'success';
            $this->result['data']   = $data;
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }


        return $this->result();
    }

    private function financeCalcOrders( $orders ) {
        $sold   = 0; //Продано на сумму
        $paid   = 0; //Выплачено
        $earned = 0; //Заработано

        foreach ( $orders as $order ) {

            $sold += $order->total;

            if ( $order->credit == 0 ) {
                $paid += $order->total;
            }

            $earned += ($order->total - $order->partner_total);
        }

        return [
            'sold'   => $sold,
            'paid'    => $paid,
            'earned' => $earned
        ];
    }

    private function financeCalcPayments( $payments ) {
        $debit  = 0; //Приход
        $credit = 0; //Расход

        foreach ( $payments as $payment ) {
            if ( $payment->amount > 0 ) {
                $debit += $payment->amount;
            } else {
                $credit += $payment->amount;
            }
        }

        return [
            'debit'  => $debit,
            'credit' => $credit
        ];
    }

}

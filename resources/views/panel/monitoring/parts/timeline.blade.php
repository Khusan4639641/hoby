<div>
    <div class="container py-2 mt-4 mb-4">
        @php
            $date = 0;
        @endphp
        @foreach($timelinePayments as $payment)
            @if ($date != date('m', strtotime($payment['payment']->created_at)))
                @php
                    $date = date('m', strtotime($payment['payment']->created_at));
                @endphp
                <div class="row">
                    <div class="col-auto text-center flex-column d-none d-sm-flex">
                        <div class="row h-50">
                            <div class="col">&nbsp;</div>
                            <div class="col">&nbsp;</div>
                        </div>
                        <h5 class="m-2">
                            <span class="badge badge-pill bg-light border">&nbsp;</span>
                        </h5>
                        <div class="row h-50">
                            <div class="col border-right">&nbsp;</div>
                            <div class="col">&nbsp;</div>
                        </div>
                    </div>
                    <div class="col py-2">
                        <div class="card bg-info">
                            <div class="card-body">
                                <p class="card-text text-uppercase font-weight-bold text-white">
                                    {{ __('app.month_' . $date) . ', ' . date('Y', strtotime($payment['payment']->created_at)) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-auto text-center flex-column d-none d-sm-flex">
                    <div class="row h-50">
                        <div class="col">&nbsp;</div>
                        <div class="col">&nbsp;</div>
                    </div>
                    <h5 class="m-2">
                        <span class="badge badge-pill bg-light border">&nbsp;</span>
                    </h5>
                    <div class="row h-50">
                        <div class="col border-right">&nbsp;</div>
                        <div class="col">&nbsp;</div>
                    </div>
                </div>
                <div class="col py-2">
                    <div class="card">
                        <div class="card-body">
                            @include('panel.monitoring.parts.timeline-parts.row', [
                                'payment' => $payment['payment'],
                                'account' => $payment['account'],
                                'bonus' => $payment['bonus'],
                                'contracts' => $payment['contracts'],
                                ])
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>
</div>

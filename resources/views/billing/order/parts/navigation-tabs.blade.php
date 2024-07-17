<div class="d-flex justify-content-between align-items-center">
    <ul class="nav nav-tabs border-b" id="orderStatus" role="tablist">
        <li class="nav-item" role="presentation">
            <a :class="'nav-link text-decoration-none text-dark' + (status === 'complete'?' active':'')"
               data-toggle="tab"
               href="#complete"
               role="tab"
               aria-selected="false"
               @click="changeStatus('complete')"
            >
                {{__('billing/index.txt_all_orders')}} ({{@$counter['complete']}})
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a :class="'nav-link text-decoration-none text-dark' + (status == 'payment'?' active':'')"
               data-toggle="tab"
               href="#payment"
               role="tab"
               aria-selected="false"
               @click="changeStatus('payment')"
            >
                {{__('contract.status_0')}} ({{@$counter['in_moderation']}})
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a :class="'nav-link text-decoration-none text-dark' + (status == 'active'?' active': '')"
               data-toggle="tab"
               href="#active"
               role="tab"
               aria-selected="true"
               @click="changeStatus('active')"
            >
                {{__('contract.status_1')}} ({{@$counter['in_installation']}})
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a :class="'nav-link text-decoration-none text-dark' + (status == 'act_needed'?' active':'')"
               data-toggle="tab"
               href="#act_needed"
               role="tab"
               aria-selected="false"
               @click="changeStatus('act_needed')"
            >
                {{__('billing/order.act_needed')}} ({{@$counter['act_needed']}})
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a :class="'nav-link text-decoration-none text-dark' + (status == 'expired'?' active':'')"
               data-toggle="tab"
               href="#expired"
               role="tab"
               aria-selected="false"
               @click="changeStatus('expired')"
            >
                {{__('contract.status_4')}} ({{@$counter['overdue']}})
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a :class="'nav-link text-decoration-none text-dark' + (status == 'cancel'?' active':'')"
               data-toggle="tab"
               href="#cancel"
               role="tab"
               aria-selected="false"
               @click="changeStatus('cancel')"
            >
                {{__('contract.status_5')}} ({{@$counter['cancelled']}})
            </a>
        </li>
        {{--      Запросы на отмену  --}}
        @if(Auth::user()->hasRole('sales-manager'))
            <li class="nav-item" role="presentation">
                <a :class="'nav-link text-decoration-none text-dark' + (status == 'orders_for_cancellation'?' active':'')"
                   {{--           data-toggle="tab"--}}
                   href="{{ localeRoute('billing.contracts_for_cancellation')  }}"
                   role="tab"
                   aria-selected="false"
                    {{--           @click="changeStatus('orders_for_cancellation')"--}}
                >
                    {{__('billing/order.contracts_sent_to_cancellation')}}
                </a>
            </li>
        @endif
    </ul>

    <form
        action="#"
        class="d-flex align-items-center"
        @submit.prevent="updateList"
    >
        <input
            type="text"
            placeholder="{{ __('billing/order.placeholder_search') }}"
            class="search-input mr-2"
            v-model="searchInputValue"
        >

        <button type="submit" class="btn btn-orange">{{ __('billing/order.search') }}</button>
    </form>

</div>

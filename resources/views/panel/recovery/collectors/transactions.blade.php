@extends('templates.panel.app')

@section('title', __('panel/contract.header_contracts_recovery'))
@section('class', 'transactions')

@section('content')
<div class="transactions" id="collector-transactions">
    <div class="transactions__wrapper">
        <h1>@{{ fullName(contract.buyer) }}</h1>
        <p>
            <b>Паспорт:</b> <span>AA0000</span>
            <br>
            <b>Прописка:</b> <span>AA0000</span>
        </p>
    </div>
    <div class="transactions__wrapper transactions__content">
        <div class="transaction" v-for="transaction in transactions">
            <div class="transaction__content">
                <template v-if="transaction.type === 'location'">
                    <iframe
                        width="100%"
                        height="450"
                        style="border:0"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        :src="locationUrl(transaction.content)"
                    >
                    </iframe>
                </template>
                <template v-else-if="transaction.type === 'date'">
                    <div class="transaction__message">Ожидаемая дата оплаты: @{{ transaction.content }}</div>
                </template>
                <template v-else-if="transaction.type === 'photo'">
                    <div class="transaction__message">
                        <a data-fancybox :data-src="transaction.content" href="#">
                            <img :src="transaction.content" alt="" height="300px">
                        </a>
                    </div>
                </template>
                <template v-else>
                    <div class="transaction__message">@{{ transaction.content }}</div>
                </template>
            </div>
            <div class="transaction__date">@{{ moment(transaction.created_at).format('DD.MM.YYYY HH:mm:ss') }}</div>
        </div>
    </div>
    <div class="transactions__wrapper">
        <paginate
            :page-count="pagination.lastPage"
            v-model="pagination.currentPage"
            :click-handler="setPage"
            prev-text="< Назад"
            next-text="Следующая >"
            container-class="pagination"
            prev-class="pagination__prev"
            next-class="pagination__next"
            page-class="pagination__page"
            active-class="pagination__page--active"
            disabled-class="pagination__page--disabled"
        >
        </paginate>
    </div>
</div>

<style>
    .transactions {
        display: flex;
        flex-direction: column;
        gap: 60px;
    }
    .transactions__wrapper {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    .transactions__content {
        background-color: #f2f2f2;
        padding: 30px;
    }

    .transaction {
        border: 1px solid #E0E0E0;
        border-radius: 8px;
        background-color: #fff;
        padding: 12px;

        display: flex;
        align-items: flex-end;
        gap: 30px;
    }

    .transaction__date {
        font-size: 12px;
    }

    .transaction__content {
        flex: 1;
    }

    .pagination {
        display: flex;
        gap: 12px;
    }

    .pagination__prev,
    .pagination__next,
    .pagination__page--active {
        color: var(--orange);
    }

    .pagination__page--disabled {
        color: #000;
    }

    .pagination__prev {
        margin-right: 20px;
    }
    .pagination__next {
        margin-left: 20px;
    }
</style>

<script>
    const collectorData = @json($collector);
    const contractData = @json($contract);
    const apiToken = @json(Auth::user()->api_token);
</script>

@include('panel.recovery.collectors.parts.transactionsVue')
@endsection

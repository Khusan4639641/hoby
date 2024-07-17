@extends('templates.panel.app')

@section('content')

    <div class="text-center">
        <div class="row">
            <div class="col-md-4">
                <h3>
                    КРЕДИТНОЕ БЮРО <br>
                    «КРЕДИТНО-ИНФОРМАЦИОННЫЙ <br>
                    АНАЛИТИЧЕСКИЙ ЦЕНТР»
                </h3>
            </div>
            <div class="col-md-4">
                <img src="{{ asset('/assets/icons/katm-logo.webp') }}" alt="katm-logo">
            </div>
            <div class="col-md-4">
                <h3>
                    CREDIT BUREAU <br>
                    “CREDIT-INFORMATION <br>
                    ANALYTICAL CENTER”
                </h3>
            </div>
        </div>
        <hr class="bg-dark">
        <p>
            100027, Тоshkent shahar, Qoratosh ko‘chasi, 1-uy
            | www.infokredit.uz
            | info@infokredit.uz
            | lotus manzil: katm
            | Tel: +99895 195-99-02, +99871 238-69-31
        </p>
        <hr class="bg-dark">
        <p class="text-muted">КРЕДИТНЫЙ ОТЧЁТ «СКОРИНГ КИАЦ» (№021)</p>
    </div>

    <div class="alert alert-info font-weight-bold text-center">
        ИДЕНТИФИКАЦИЯ ЗАЁМЩИКА
    </div>
    @if($buyer === 'legal')
        <ul class="list-unstyled" style="width: 33%">
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Наименование заёмщика &nbsp;</span>
                <span class="font-weight-bold w-50">ООО «ART»</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Тип клиента:&nbsp;</span>
                <span class="font-weight-bold w-50">Юридическое лицо</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">ИНН:&nbsp;</span>
                <span class="font-weight-bold w-50">423 456 789</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Адрес по прописке:&nbsp;</span>
                <span class="font-weight-bold w-50">г. Ташкент, улица А. Орипов, 28</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Телефонный номер:&nbsp;</span>
                <span class="font-weight-bold w-50">+998 71 400 00 00, +998 71 254 00 00</span>
            </li>
        </ul>
    @elseif($buyer === 'physical')
        <ul class="list-unstyled" style="width: 33%;">
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Наименование заёмщика &nbsp;</span>
                <span class="font-weight-bold w-50">Alimova Robiya Toxirovna</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Дата рождения:&nbsp;</span>
                <span class="font-weight-bold w-50">11 марта 1987</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Пол:&nbsp;</span>
                <span class="font-weight-bold w-50">Ж</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Адрес по прописке:&nbsp;</span>
                <span class="font-weight-bold w-50">г. Ташкент, улица А. Орипов, 28</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">ПИНФЛ:&nbsp;</span>
                <span class="font-weight-bold w-50">41103 8700 55674</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">ИНН:&nbsp;</span>
                <span class="font-weight-bold w-50">623 456 789</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Тип документа:&nbsp;</span>
                <span class="font-weight-bold w-50">Биометрический паспорт Гражданина РУз</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Данные документа:&nbsp;</span>
                <span class="font-weight-bold w-50">АА 1234567 от 20 декабря 2016</span>
            </li>
            <li class="d-flex justify-content-between">
                <span class="text-muted w-50">Телефонный номер:&nbsp;</span>
                <span class="font-weight-bold w-50">+998 93 000 11 00</span>
            </li>
        </ul>
    @endif

    <div class="alert alert-info font-weight-bold text-center">
        СКОРИНГОВЫЙ БАЛЛ ЗАЁМЩИКА
    </div>
    <div class="row">
        <div class="col-md-6">
            <ul class="list-unstyled" style="width: 60%;">
                <li class="d-flex justify-content-between">
                    <span class="text-muted w-50">Скоринговый балл заёмщика: &nbsp;</span>
                    <span class="font-weight-bold w-50">237</span>
                </li>
                <li class="d-flex justify-content-between">
                    <span class="text-muted w-50">Класс оценки:&nbsp;</span>
                    <span class="font-weight-bold w-50">С2, Средний уровень</span>
                </li>
                <li class="d-flex justify-content-between">
                    <span class="text-muted w-50">Время формирования::&nbsp;</span>
                    <span class="font-weight-bold w-50">15 марта 2020, 09:56</span>
                </li>
                <li class="d-flex justify-content-between">
                    <span class="text-muted w-50">Версия скоринга:&nbsp;</span>
                    <span class="font-weight-bold w-50">1.2</span>
                </li>
            </ul>
        </div>
        <div class="col-md-6 d-flex align-items-center">
            <span class="text-center p-3 font-weight-bold mb-3" style="border-radius: 20px; border: 2px solid #d1ecf1">
                C2 <br>
                СРЕДНИЙ
            </span>
        </div>
    </div>

    <div class="alert alert-info font-weight-bold text-center mb-0">
        ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ
    </div>
    <table class="table table-bordered text-center">
        <thead>
        <tr class="text-uppercase">
            <th rowspan="2">№</th>
            <th rowspan="2">{{__('panel/partner.title')}}</th>
            <th colspan="2">ОТКРЫТЫЕ</th>
            <th colspan="2">ЗАКРЫТЫЕ</th>
        </tr>
        <tr class="text-uppercase">
            <th>{{__('cabinet/order.lbl_amount')}}</th>
            <th>{{__('panel/finance.lbl_sum')}}</th>
            <th>{{__('cabinet/order.lbl_amount')}}</th>
            <th>{{__('panel/finance.lbl_sum')}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($additionalInformation as $index => $information)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $information['title'] }}</td>
                <td>{{ $information['opened']['amount'] }}</td>
                <td>{{ $information['opened']['sum'] }}</td>
                <td>{{ $information['closed']['amount'] }}</td>
                <td>{{ $information['closed']['sum'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td>12</td>
            <td>Оповещение</td>
            <td class="text-left" colspan="4">
                <ul class="list-unstyled">
                    <li>
                        - среднемесячный платёж: 30 300 000 сум
                    </li>
                    <li>
                        - дата первого договора: 14.12.2015 г.
                    </li>
                    <li>
                        - у субъекта имеются заявки, ожидающие решения в другой кредитной организации
                    </li>
                    <li>
                        - субъект обращался за разными кредитами в последнее время
                    </li>
                    <li>
                        - субъекту было отказано в выдаче кредита в последнее время
                    </li>
                    <li>
                        - у субъекта имеется обязательство по поручительству
                    </li>
                    <li>
                        - у субъекта имеется обязательство по ипотечному кредитованию
                    </li>
                </ul>
            </td>
        </tr>
        </tbody>
    </table>


    <p class="font-weight-bold mb-2">Примечание:</p>
    <ul class="list-unstyled">
        <li>
            - Скоринговый балл имеет рекомендательный характер и может быть основанием для выдачи или отказа кредитов
            только по усмотрению кредитора;
        </li>
        <li>
            - Скоринговый балл не охватывает кредитную информацию об обязательствах, исполненную (закрытую) более 5 лет
            назад;
        </li>
        <li>
            - Оценка скорингового балла производится в диапазоне 0-500. Диапазон разделён на 5 классов А, В, С, D, E и
            каждый класс разделён на 3 уровня;
        </li>
        <li>
            - Расчёт скорингового балла состоит из идентификационных данных и кредитной истории заёмщика полученных от
            кредитных организаций;
        </li>
        <li>
            - Среднемесячный платёж включает в себя расчёт исходя из остатков основного долга и прогнозного платежа по
            процентам (аннуитетный);
        </li>
        <li>
            - Кредиты в иностранной валюте рассчитываются в сумовом эквиваленте по курсу ЦБ РУз на дату формирования
            скорингового балла;
        </li>
        <li>
            - При изменениях расчётов меняется версия скорингового балла.
        </li>
    </ul>

    <p class="text-muted">
        Пользователь кредитного отчёта: АТ Халқ банки (Инагамов А.) <br>
        Кредитная заявка: 134567 от 15 марта 2020 <br>
        Запрос Пользователя на получение кредитного отчёта: № 0352019144836101 от 15 марта 2020 <br>
        Внимание: Предоставление и использование кредитной информации регулируется Законом <br>
        Республики Узбекистан «Об обмене кредитной информацией» № 301 от 04.10.2011 года.
    </p>
@endsection

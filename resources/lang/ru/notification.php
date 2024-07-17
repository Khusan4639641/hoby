<?php

return [
    'title' => 'Уведомления',

    'list_empty' => 'Список уведомлений пуст',

    'buyer' => [
        'status_changed' => "Ваш статус изменен на <strong>:status</strong>",
        'reason' => "Причина отказа: <strong>:reason</strong>",
        'order_created' => "Договор <a target='_blank' href=':order_link'>№:order_number</a> на сумму :order_total сум. успешно создан",
        'order_status_changed' => "Статус договора <a target='_blank' href=':order_link'>№:order_number</a> изменен на <strong>:status_caption</strong>",
        'order_delay' => "Статус договора <a target='_blank' href=':order_link'>№:order_number</a> изменен на <strong>:status_caption</strong>",
    ],

    'kyc' => [
        'buyer_verify' => "Пользователь <a target='_blank' href=':buyer_link'>:buyer_name</a> отправил свои данные на верификацию",
    ],

    'partner' => [
        'order_created' => "Поступил новый договор <a target='_blank' href=':order_link'>№:order_number</a> на сумму :order_total сум.",
    ],
];

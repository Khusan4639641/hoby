<?php
return [
    'myid_client_birth_date_not_found'           => 'Дата рождения не найдена',
    'myid_client_blocked_for_invalid_request'    => 'Клиент был заблокирован на :ttl секунды из за неправильных данных',
    'user_has_overdue_contract'                  => 'У вас есть просроченные контракты',
    'myid_result_pinfl_duplicate'                 => 'Клиент c таким ПИНФЛ уже зарегистрирован',
    'myid_result_pinfl_not_found'                 => 'ПИНФЛ не найден',
    'myid_result_user_has_no_enough_age'         => 'Что бы пройти регистрацию, Вам должно быть от 22 до 65 лет',
    'myid_result_invalid_citizenship'            => 'Для прохождения верификации, необходимо быть гражданином Узбекистана',
    'myid_result_too_many_requests'              => 'Временно заблокирован из-за неверных данных',
    'myid_result_passport_type'                  => 'Введите ID карту или биометрический паспорт',
    'myid_result_passport_expire'                => 'Срок действия паспорта истек',
    'expired_contracts_forwarded_to_autopay'     => 'Из-за просроченных контрактов {contracts} ваш долг отправлен в Autopay',
    'myid_result_code_1'                         => 'Все проверки успешно прошли',
    'myid_result_code_2'                         => 'Паспортные данные введены неправильно',
    'myid_result_code_3'                         => 'Не удалось подтвердить жизненность',
    'myid_result_code_4'                         => 'Не удалось распознать',
    'myid_result_code_5'                         => 'Сервис ГЦП недоступен или работает некорректно',
    'myid_result_code_6'                         => 'Пользователь скончался',
    'myid_result_code_7'                         => 'Фото с ГЦП не получено',
    'myid_result_code_9'                         => 'Срок выполнения задачи истек',
    'myid_result_code_10'                        => 'Срок ожидания задачи в очереди истек',
    'myid_result_code_11'                        => 'Сервис не может обработать запрос. Попробуйте повторить позже',
    'myid_result_code_14'                        => 'Из фотографии не удалось определить лицо пользователя',
    'myid_result_code_17'                        => 'Из фотографии не удалось определить лицо пользователя',
    'myid_result_code_18'                        => 'Сервис не может обработать запрос',
    'myid_result_code_19'                        => 'Не удалось обработать запрос по распознаванию лица',
    'myid_result_code_20'                        => 'Плохое или размытое изображение',
    'myid_result_code_21'                        => 'Лицо не полностью попало в кадр',
    'myid_result_code_22'                        => 'Несколько лиц попали в кадр',
    'myid_result_code_23'                        => 'Представленное изображение в градациях серого, требуется цветное изображение',
    'myid_result_code_24'                        => 'Обнаружены затемненные очки',
    'myid_result_code_25'                        => 'Сервис данный тип фотографии не поддерживает',
    'myid_result_code_26'                        => 'Глаза закрыты либо не видны',
    'user_verified'                             => 'Пользователь уже верифицирован',
    'image_format_error'                        => 'Неверный формат изображения',
    'payment_category_user'                     => 'Пополнение личного счёта',
    'payment_category_user_auto'                => 'Оплата',
    'payment_category_info_contract'            => 'по договору #',
    'payment_category_info_contract_account'    => 'с л/с',
    'payment_category_info_contract_card'       => ' с карты',
    'payment_category_auto'                     => 'Автосписание',
    'payment_category_refund'                   => 'Возврат',
    'payment_category_info_to_card'             => 'На карту',
    'payment_category_fill'                     => 'Бонусы',
    'payment_category_a2c'                      => 'Бонусы на карту',
    'payment_category_upay'                     => 'Списание с личного счёта бонусов',

    'user_phone_equals_to_buyers'   => 'Нельзя вводить свой личный номер',
    'duplicate_phone_number'        => 'Нельзя вводить одинаковые номера',
    'lang_not_set'                  => 'Язык не определен',
    'unknown_type_of_card'          => 'Неизвестный тип карты.',
    'card_already_exists'           => 'Данная карта уже имеется в системе',
    'contract_out_of_date'          => 'Срок активации договора истек',
    'err_black_list'                => 'Клиент не может оформить договор. Обратитесь в коллцентр ' . callCenterNumber(4),
    'limit_error'                   => 'Сумма договора превышает лимит покупки!',

    'incorrect_parameters'      => 'Некорректные параметры',
    'buyer_not_verified'        => 'API. Клиент :fio не верифицирован!',
    'buyer_not_found'           => 'API. Клиент не найден!',
    'buyer_verified'            => 'Клиент :fio верифицирован!',
    'contract_is_already_sent'  => 'Заявка на отмену контракта уже была отправлена',
    'contract_is_already_activated' => 'Договор уже был активирован',
    'contract_not_found'        => 'Договор не найден!',
    'contract_not_be_activated'        => 'Данный договор не может быть активирован!',
    'contract_statuses_not_found'        => 'Запись со статусом договора не найдена',
    'contract_is_already_aborted'        => 'Договор уже отменен!',
    'contract_is_aborted'        => 'Договор уже был отменен!',
    'contract_request_is_already_aborted'        => 'Заявка на отмену договора - отменена!',
    'contract_is_active_with_same_request_id'        => 'Данный договор в нашей системе уже активирован',
    'internal_error'            => 'Внутренняя ошибка сервера!',
    'otp_not_found'             => 'Неверный СМС код!',
    'schedule_not_found'        => 'Расписание ежемесячных платежей не найдено!',
    "schedule_is_not_contract's"=> 'Расписание ежемесячных платежей не принадлежит этому контракту!',
    "schedule_is_closed"        => 'Расписание ежемесячных платежей уже полачено!',
    "not_enough_money"          => 'Недостаточно денег!',
    "contract_is_not_buyer's"   => 'Договор не принадлежит клиенту!',
    "contract_without_schedule" => 'Договор не имеет расписания ежемесячных платежей!',
    'bad_request'               => "Неправильный запрос!",
    'card_not_empty_error'      => "Поле карты обязательна!",

    'calculate_empty_sum' => 'API. Сумма не задана :sum!',
    'calculate_empty_credit_limit' => 'API. Период не задан :credit_limit!',
    'calculate_sum_more_zero' => 'API. Сумма должна быть больше 0 :sum!',
    'calculate_sum_max_limit' => 'API. Сумма не может превышать :limit :sum!',
    'calculate_credit_limit_max_limit' => 'Период должен быть 3, 6, 9, 12 или 24 месяцев :credit_limit!',
    'calculate_partner_not_found' => 'Партнер не найден!',

    'credit_date'=>'API. Дата погашения кредита :credit_date не указана!',
    'credit_limit'=>'API. Не указан срок кредита в месяцах :credit_limit!',
    'credit_limit_max_limit'=>'Срок кредита должен быть 3, 6, 9, 12 или 24 мес :credit_limit!',
    'credit_empty_user'=>'API. ID пользователя не задан :user_id!',
    'credit_empty_partner'=>'API. Телефон или ID вендора не задан :phone или :id!',
    'credit_empty_products'=>'API. Товар(ы) не указан(ы)!',
    'credit_buyer_not_found'=>'API. Клиент не найден!',
    'credit_partner_not_found'=>'API. Партнер не найден!',
    'credit_limit_for_24'=>'Для оформления договора на 24 мес сумма товаров должна быть от :limit_for_24!',
    'credit_limits'=>'Допустимые сроки оформления :plans_get мес!',

    'check_sms_user_not_found' => 'API. ID пользователя не задан :user_id!',
    'check_sms_code_not_found' => 'API. Код подтвеждения не верный!',
    'check_sms_credit_id_not_found' => 'API. Credit_id пользователя не задан :credit_id!',
    'check_sms_contract_success' => 'Договор успешно подтвержден! ',
    'check_sms_contract_error' => 'Договор не подтвержден!',

    'basket_error_create_basket' => 'Невозможно создать договор',

    'contracts_buyer_not_found' => 'Договоров нет',
    'you_have_exceeded_the_maximum_allowable_number_of_contracts_in_the_phones_category' => 'Вы превысили максимально допустимое количество договоров по категории \'Смартфоны и телефоны\'',
    'guarants_phone_should_not_be_equal_to_buyers' => 'Номер телефона поручителя не должен совпадать с номером телефона покупателя',
    'buyer_name_is_required' => 'Имя покупателя обязательно для заполнения',
    'buyer_guarants_phones_are_equal' => 'Номера телефонов доверителей не должны быть одинаковыми',
    'buyer_does_not_exist' => 'Не удалось найти покупателя',
    'error_phone_exist' => 'Данный номер уже существует!',
    "myid_contract_attempts limit" => "Вы использовали все возможные попытки верификации. Пожалуйста пройдите ручную верефикацию",
    'error' => 'Ошибка:',
    'error_phone_prefix' => 'Неверный номер телефона. Укажите существующий префикс!',
    'contract_activation_in_progress' => 'Идет активация контракта. пожалуйста, подождите',
];

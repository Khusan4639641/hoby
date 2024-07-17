<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Языковые ресурсы для проверки значений
    |--------------------------------------------------------------------------
    |
    | Последующие языковые строки содержат сообщения по-умолчанию, используемые
    | классом, проверяющим значения (валидатором). Некоторые из правил имеют
    | несколько версий, например, size. Вы можете поменять их на любые
    | другие, которые лучше подходят для вашего приложения.
    |
    */

    'accepted'             => 'Siz :attribute qabul qilishingiz kerak.',
    'active_url'           => ':attribute maydonida yaroqsiz URL mavjud.',
    'after'                => ':attribute maydonida :date keyin sana bo`lishi kerak.',
    'after_or_equal'       => ':attribute maydonida keyingi sana  yoki :date ga teng bo`lishi kerak.',
    'alpha'                => ':attribute maydonida faqat harflar bo`lishi mumkin.',
    'alpha_dash'           => ':attribute maydonida faqat harflar, raqamlar, tire va pastki chiziq bo`lishi mumkin.',
    'alpha_num'            => ':attribute maydonida faqat harflar va raqamlar bo`lishi mumkin.',
    'array'                => ':attribute maydoni massiv bo`lishi kerak.',
    'before'               => ':attribute maydonida oldingi sana bo`lishi kerak :date.',
    'before_or_equal'      => ':attribute maydoni sanadan oldingi yoki unga teng bo`lishi kerak :date.',
    'between'              => [
        'numeric' => ':attribute maydoni :min и :max orasida bo`lishi kerak .',
        'file'    => ':attribute maydonidagi fayl hajmi :min va :maks Kilobayt(lar) orasida bo`lishi kerak.',
        'string'  => ':attribute maydonidagi belgilar soni :min va :max orasida bo`lishi kerak.',
        'array'   => ':attribute maydonidagi elementlar soni :min va :max orasida bo`lishi kerak.',
    ],
    'boolean'              => ':attribute maydoni qiymati turi mantiqiy bo`lishi kerak.',
    'confirmed'            => ':attribute maydoni tasdiqlashga mos kelmaydi.',
    'date'                 => ':attribute maydoni sana emas.',
    'date_equals'          => ':attribute maydoni :date ga teng sana bo`lishi kerak.',
    'date_format'          => ':attribute maydoni formati :format ga mos kelmaydi.',
    'different'            => ' :attribute va :other maydonlari boshqacha bo`lishi kerak.',
    'digits'               => ':attribute maydonining uzunligi :digits. bo`lishi kerak ',
    'digits_between'       => ':attribute raqamli maydonining uzunligi :min va :max oralig`ida bo`lishi kerak.',
    'dimensions'           => ':attribute maydonida rasm o‘lchamlari noto‘g‘ri',
    'distinct'             => ':attribute maydonida takrorlanayotgan qiymat mavjud.',
    'email'                => ':attribute maydoni toʻgʻri elektron pochta manzili boʻlishi kerak.',
    'ends_with'            => ':attribute maydoni quyidagi qiymatlardan biri bilan tugashi kerak: :values',
    'exists'               => ':attribute maydoni uchun tanlangan qiymat noto`g`ri.',
    'exists_real_error'    => 'Berilgan :attribute qiymatga ega ma`lumot bazada majud emas.',
    'file'                 => ':attribute maydoni fayl bo`lishi kerak',
    'filled'               => ':attribute maydonini to‘ldirish shart.',
    'gt'                   => [
        'numeric' => ':attribute maydoni :value dan katta bo`lishi kerak.',
        'file'    => ':attribute maydonidagi fayl hajmi :value Kilobayt(lar) dan katta bo`lishi kerak.',
        'string'  => ':attribute maydonidagi belgilar soni :value dan katta bo`lishi kerak.',
        'array'   => ':attribute maydonidagi elementlar soni :value dan katta bo`lishi kerak.',
    ],
    'gte'                  => [
        'numeric' => 'Поле :attribute должно быть больше или равно :value.',
        'file'    => ':atribut maydonidagi fayl hajmi :value Kilobayt(lar) dan katta yoki teng bo`lishi kerak.',
        'string'  => ':attribute maydonidagi belgilar soni :value dan katta yoki teng bo`lishi kerak.',
        'array'   => ':attribute maydonidagi elementlar soni :value dan katta yoki teng bo`lishi kerak.',
    ],
    'image'                => ':attribute maydoni rasm bo`lishi kerak',
    'in'                   => ':attribute  uchun tanlangan qiymat noto‘g‘ri.',
    'in_array'             => ':attribute maydoni :other da mavjud emas.',
    'integer'              => ':attribute maydoni butun son bo`lishi kerak.',
    'ip'                   => ':attribute maydoni toʻgʻri IP manzil boʻlishi kerak.',
    'ipv4'                 => ':attribute  maydoni toʻgʻri IPv4 manzili boʻlishi kerak',
    'ipv6'                 => ':attribute maydoni toʻgʻri IPv6 manzili boʻlishi kerak.',
    'json'                 => ':attribute maydoni JSON qatori boʻlishi kerak.',
    'lt'                   => [
        'numeric' => ':attribute maydoni :value dan kichik bo`lishi kerak.',
        'file'    => ':attribute maydonidagi fayl hajmi :value Kilobayt(lar)dan kichik bo`lishi kerak.',
        'string'  => ':attribute maydonidagi belgilar soni :value dan kam bo`lishi kerak.',
        'array'   => ':attribute maydonidagi elementlar soni :value dan kam bo`lishi kerak.',
    ],
    'lte'                  => [
        'numeric' => ':attribute maydoni :value dan kichik yoki teng bo`lishi kerak.',
        'file'    => ':attribute maydonidagi fayl hajmi :value Kilobayt(lar) dan kichik yoki teng bo`lishi kerak.',
        'string'  => ':attribute maydonidagi belgilar soni :value dan kam yoki teng bo`lishi kerak.',
        'array'   => ':attribute maydonidagi elementlar soni :value dan kam yoki teng bo`lishi kerak.',
    ],
    'max'                  => [
        'numeric' => ':attribute maydoni :max dan katta bo`lishi mumkin emas.',
        'file'    => ':attribute maydonidagi fayl hajmi :max Kilobayt(lar)dan oshmasligi kerak..',
        'string'  => ':attribute maydonidagi belgilar soni :max dan oshmasligi kerak.',
        'array'   => ':attribute maydonidagi elementlar soni :max dan oshmasligi kerak.',
    ],
    'mimes'                => ':attribute maydoni quyidagi turlardan biridagi fayl bo`lishi kerak: :values.',
    'mimetypes'            => ':attribute maydoni quyidagi turlardan biridagi fayl bo`lishi kerak: :values.',
    'min'                  => [
        'numeric' => ':attribute maydoni :min dan kichkina bo`lishi mumkin emas.',
        'file'    => ':attribute maydonidagi fayl hajmi kamida:min Kilobayt boʻlishi kerak.',
        'string'  => ':attribute maydonidagi belgilar soni kamida :min bo`lishi kerak.',
        'array'   => ':attribute maydonidagi elementlar soni kamida :min bo`lishi kerak.',
    ],
    'not_in'               => ':attribute uchun tanlangan qiymat noto‘g‘ri.',
    'not_regex'            => ':attribute uchun tanlangan format noto‘g‘ri.',
    'numeric'              => ':attribute maydoni raqam bo`lishi kerak.',
    'password'             => 'Noto`g`ri parol.',
    'present'              => ':attribute maydoni mavjud bo`lishi kerak.',
    'regex'                => ':attribute maydoni noto`g`ri formatda tuzilgan.',
    'required'             => ':attribute maydoni to`ldirilgan bo`lishi shart.',
    'required_if'          => ':attribute maydoni to`ldirilgan bo`lishi shart, agar :other teng bo`lsa :value ga.',
    'required_unless'      => ':attribute maydoni :other :valuesga teng bo`lmaganda talab qilinadi.',
    'required_with'        => ':values ko`rsatilganda :attribute maydoni talab qilinadi.',
    'required_with_all'    => ':values ko`rsatilganda :attribute maydoni talab qilinadi.',
    'required_without'     => ':values ko`rsatilmaganida :attribute maydoni talab qilinadi.',
    'required_without_all' => ':attribute maydoni :values dan hech biri belgilanmagan bo`lsa talab qilinadi.',
    'same'                 => ':attribute va :other maydonlarning qiymatlari mos kelishi kerak.',
    'size'                 => [
        'numeric' => ':attribute maydoni :size ga teng bo`lishi kerak.',
        'file'    => ':attribute maydonidagi fayl hajmi :size Kilobayt(lar) ga teng bo`lishi kerak.',
        'string'  => ':attribute maydonidagi belgilar soni :size ga teng bo`lishi kerak.',
        'array'   => ':attribute maydonidagi elementlar soni :size ga teng bo`lishi kerak.',
    ],
    'starts_with'          => ':attribute maydoni quyidagi qiymatlardan biri bilan boshlanishi kerak: :values',
    'string'               => ':attribute maydoni qator bo`lishi kerak.',
    'timezone'             => ':attribute maydoni toʻgʻri vaqt mintaqasi boʻlishi kerak.',
    'unique'               => 'Bunday :attribute maydoni qiymati allaqachon mavjud.',
    'uploaded'             => ':attribute maydonini yuklash amalga oshmadi.',
    'url'                  => ':attribute maydoni noto`g`ri tuzilgan.',
    'uuid'                 => ':attribute maydoni haqiqiy UUID bo`lishi kerak.',

    /*
    |--------------------------------------------------------------------------
    | Собственные языковые ресурсы для проверки значений
    |--------------------------------------------------------------------------
    |
    | Здесь Вы можете указать собственные сообщения для атрибутов.
    | Это позволяет легко указать свое сообщение для заданного правила атрибута.
    |
    | http://laravel.com/docs/validation#custom-error-messages
    | Пример использования
    |
    |   'custom' => [
    |       'email' => [
    |           'required' => 'Нам необходимо знать Ваш электронный адрес!',
    |       ],
    |   ],
    |
    */

    'custom' => [
        // Nurlan 25.04.2022
        'user_id_invoice' => [
            'required'          => 'Foydalanuvchining ID-si to`ldirilgan bo`lishi shart!',
            'unique'            => 'Foydalanuvchining ID-si jadvalda yagona bo`lishi kerak!',
            'numeric'           => 'Foydalanuvchining ID-si raqamli son bo`lishi kerak!',
        ],
        'contract_id_invoice' => [
            'required'          => 'Kontrakt ID-si to`ldirilgan bo`lishi shart!',
            'unique'            => 'Kontrakt ID-si jadvalda yagona bo`lishi kerak!',
            'numeric'           => 'Kontrakt ID-si raqamli son bo`lishi kerak!',
        ],
        'invoice_number' => [
            'required'          => 'Hisob-faktura raqami to`ldirilgan bo`lishi shart!',
            'unique'            => 'Hisob-faktura raqami jadvalda yagona bo`lishi kerak!',
            'digits_between'    => 'Hisob-faktura raqami 14-20 xonali va raqamli son bo`lishi kerak!',
        ],
    ],



    /*
    |--------------------------------------------------------------------------
    | Собственные названия атрибутов
    |--------------------------------------------------------------------------
    |
    | Последующие строки используются для подмены программных имен элементов
    | пользовательского интерфейса на удобочитаемые. Например, вместо имени
    | поля "email" в сообщениях будет выводиться "электронный адрес".
    |
    | Пример использования
    |
    |   'attributes' => [
    |       'email' => 'электронный адрес',
    |   ],
    |
    */

    'attributes' => [
        'buyer_id'              => 'ID',
        'name'                  => 'Ism',
        'username'              => 'Taxallus',
        'email'                 => 'E-pochta manzili',
        'first_name'            => 'Ism',
        'last_name'             => 'Familiya',
        'password'              => 'Parol',
        'password_confirmation' => 'Parolni tasdiqlash',
        'city'                  => 'Shahar',
        'country'               => 'Mamlakat',
        'address'               => 'Manzil',
        'phone'                 => 'Telefon',
        'birthday'              => 'Tug`ilgan sana',
        'mobile'                => 'Mobil.raqam',
        'age'                   => 'Yosh',
        'sex'                   => 'jins',
        'gender'                => 'Jins',
        'day'                   => 'kun',
        'month'                 => 'Oy',
        'year'                  => 'yil',
        'hour'                  => 'soat',
        'minute'                => 'Daqiqa',
        'second'                => 'ikkinchi',
        'title'                 => 'Ism',
        'content'               => 'Tarkib',
        'description'           => 'Tavsif',
        'excerpt'               => 'Iqtibos',
        'date'                  => 'Sana',
        'time'                  => 'Vaqt',
        'available'             => 'Mavjud',
        'size'                  => 'Hajmi',
        'surname'               => 'Familiya',
        'patronymic'            => 'Otasini ismi',
        'address_residential'   => 'Yashash manzili',
        'address_region'        => 'Mintaqa',
        'address_area'          => 'Tuman',
        'address_city'          => 'Shahar',
        'passport_number'       => 'Pasport raqami',
        'passport_issued_by'    => 'Kim tomonidan berilgan',
        'passport_selfie'       => 'Pasport bilan selfi',
        'passport_first_page'   => 'Pasportning birinchi sahifasi',
        'passport_with_address' => 'Ro`yxatga olingan pasport sahifasi',
        'pinfl'                 => 'PINFL',
        'home_phone'            => 'Uy telefoni',
        'work_company'          => 'Ish nomi',
        'work_phone'            => 'Ish telefoni',
        'card_number' => "Karta raqami",
        'card_valid_date' => "Yaroqlilik muddati",
        'amount' => 'Miqdor',
        'uppercase' => ':attribute maydoni katta harf bilan boshlanishi kerak',
        'lat_only' => 'Maydon :attribute faqat lotin tilida to`ldiriladi',

        'address_registration_region'   => 'Mintaqa',
        'address_registration_address'  => 'Manzil',
        'address_registration_area'     => 'Tuman',

        'address_residential_region'    => 'Mintaqa',
        'address_residential_area'      => 'Tuman',
        'address_residential_address'   => 'Manzil',
    ],
];

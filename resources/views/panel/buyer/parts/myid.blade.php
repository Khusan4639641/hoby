<script>
    var myid_report = new Vue({
        el: '#myid_report',
        data: {
            myIdReportKeys: [
                {
                    groupName: 'common_data',
                    keys: [
                        "first_name",
                        "first_name_en",
                        "middle_name",
                        "last_name",
                        "last_name_en",
                        "pinfl",
                        "inn",
                        "gender",
                        "birth_place",
                        "birth_country",
                        "birth_country_id",
                        "birth_country_id_cbu",
                        "birth_date",
                        "nationality",
                        "nationality_id",
                        "nationality_id_cbu",
                        "citizenship",
                        "citizenship_id",
                        "citizenship_id_cbu",
                        "sdk_hash",
                        "last_update_pass_data",
                        "last_update_inn",
                        "last_update_address",
                    ]
                },
                {
                    groupName: 'doc_data',
                    keys: [
                        "pass_data",
                        "issued_by",
                        "issued_by_id",
                        "issued_date",
                        "expiry_date",
                        "doc_type",
                        "doc_type_id",
                        "doc_type_id_cbu"
                    ]
                },
                {
                    groupName: 'contacts',
                    keys: [ "email", "phone",]
                },
                {
                    groupName: 'address',
                    keys: [ "permanent_address", "permanent_registration", "temporary_address", "temporary_registration" ]
                },
            ],
            myIdReportTranslations: {
                common_data: 'Общие данные',
                doc_data: 'Данные документа',
                contacts: 'Контакты',
                address: 'Адрес',
                
                first_name: 'Имя',
                first_name_en: 'Имя на английском языке',
                middle_name: 'Отчество',
                last_name: 'Фамилия',
                last_name_en: 'Фамилия на английском языке',
                pinfl: 'Персональный идентификационный номер физического лица',
                inn: 'Идентификационный номер налогоплательщика',
                gender: 'Пол',
                birth_place: 'Место рождения',
                birth_country: 'Страна рождения',
                birth_country_id: 'Идентификатор страны рождения (по справочнику ГЦП)',
                birth_country_id_cbu: 'Идентификатор страны рождения (по справочнику ЦБ)',
                birth_date: 'Дата рождения',
                nationality: 'Национальность',
                nationality_id: 'Идентификатор национальности (по справочнику ГЦП)',
                nationality_id_cbu: 'Идентификатор национальности (по справочнику ЦБ)',
                citizenship: 'Гражданство',
                citizenship_id: 'Идентификатор гражданства (по справочнику ГЦП)',
                citizenship_id_cbu: 'Идентификатор гражданства (по справочнику ЦБ)',
                sdk_hash: 'Хэш код, который можно направить в последующих запросах в SDK в целях получения данных пользователя',
                last_update_pass_data: 'Дата и время последнего обновления в системе документа, удостоверяющего личность (формат UTC)',
                last_update_inn: 'Дата и время последнего обновления в системе идентификационного номера налогоплательщика (формат UTC)',
                last_update_address: 'Дата и время последнего обновления в системе адреса регистрации (формат UTC)',
                
                pass_data: 'Серия и номер документа, удостоверяющего личность',
                issued_by: 'Место выдачи документа, удостоверяющего личность',
                issued_by_id: 'Идентификатор места выдачи документа, удостоверяющего личность',
                issued_date: 'Дата выдачи документа, удостоверяющего личность',
                expiry_date: 'Срок действия документа, удостоверяющего личность',
                doc_type: 'Тип документа',
                doc_type_id: 'Идентификатор типа документа (Справочник №2)',
                doc_type_id_cbu: 'Идентификатор типа документа (по справочник ЦБ)',

                phone: 'Номер телефона',
                email: 'Адрес электронной почты',

                permanent_address: 'Адрес постоянной регистрации',
                temporary_address: 'Адрес временной регистрации',
                permanent_registration: 'Доп. данные постоянной регистрации',
                temporary_registration: 'Доп. данные временной регистрации',
                region: 'Значение региона',
                country: 'Значение страны',
                cadastre: 'Кадастр',
                district: 'Значение района (города)',
                date_from: 'Дата начала регистрации',
                date_till: 'Дата окончания регистрации',
                region_id: 'Идентификационный номер региона (по справочнику МВД)',
                country_id: 'Идентификационный номер страны (По справочнику МВД)',
                district_id: 'Идентификационный номер района/города (По справочнику МВД)',
                region_id_cbu: 'Идентификационный номер региона (по справочнику ЦБ)',
                country_id_cbu: 'Идентификационный номер страны (По справочнику ЦБ)',
                district_id_cbu: 'Идентификационный номер района/города (По справочнику ЦБ)',
                registration_date: 'Дата регистрации',
            },
            buyer_id: @json($buyer_id),
            myIdReport: null,
            loading:false
        },
        filters: {
            formatDate: function (date) {
                if (!date || !String(date).length) return '-'
                return moment(date).format('DD.MM.YYYY')
                return date
            },
            formatGender: function (val) {
                if (!val || !String(val).length) return '-'
                return val == 1 ? 'Мужчина' : 'Женщина'
            },
            checkEmpty: function (val) {
                if (val) return val
                return '-'
            }
        },
        methods: {
            fetchMyIdData: function () {
                this.loading = true
                axios.get(`/api/v3/myid/job/report/${this.buyer_id}?api_token=${window.globalApiToken}`, 
                {headers: {'Content-Language': window.Laravel.locale}})
                .then(({data}) => {
                    if (data?.status === "success") this.myIdReport = data.data?.profile
                    else this.myIdReport = null
                    this.loading = false
                }).catch(e => {
                    this.loading = false
                    console.log('error');
                    console.log(e);
                });
            },
            RandNum: function (min, max) {
                return Math.random() * (max - min) + min;
            }
        },
        created: function () {
            this.fetchMyIdData();
        },
    });
</script>

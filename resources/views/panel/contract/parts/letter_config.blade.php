<script>
    // TODO: get all areas after selectedRegion
    {{--const contract       = @json($contract->buyer ?? null);--}}
    {{--const selectedArea   = @json($contract->buyer->getPostalAreaSelected() ?? null);--}}
    {{--const selectedRegion = @json($contract->buyer->getPostalRegionSelected() ?? null);--}}
    {{--const postalAreas    = @json($postal_areas ?? null);--}}
    {{--const postalRegions  = @json($postal_regions ?? null);--}}
    {{--const callCenter     = @json($help_phone ?? null);--}}
    {{--const letters        = @json(count($letters ?? []));--}}
    {{--const client         = {--}}
    {{--    passportNumber: @json($passportNumber ?? ''),--}}
    {{--    personals: @json($contract->buyer->personals),--}}
    {{--    passportFirstPage: @json($contract->buyer->personals->passport_first_page),--}}
    {{--    passportWithAddress: @json($contract->buyer->personals->passport_with_address),--}}
    {{--    passportFirstPagePath: @json(\App\Helpers\FileHelper::url($contract->buyer->personals->passport_first_page->path ?? '')),--}}
    {{--    passportWithAddressPath: @json(\App\Helpers\FileHelper::url($contract->buyer->personals->passport_with_address->path ?? '')),--}}
    {{--    apiToken: @json(Auth::user()->api_token),--}}
    {{--    id: @json( $contract->buyer->id ),--}}
    {{--    fio: @json($contract->buyer->surname . ' ' . $contract->buyer->name . ' ' . $contract->buyer->patronymic),--}}
    {{--    addressRegistration: @json($contract->buyer->addressRegistration->address ?? ''),--}}
    {{--    contractRecoverId: @json($contract->recover->id ?? '__' ),--}}
    {{--    contractId: @json($contract->id),--}}
    {{--    debt: @json($contract->delaySum),--}}
    {{--    balance: @json($contract->balance),--}}
    {{--    contractCreatedAt: @json($contract->created_at_date),--}}
    {{--    now: @json(date("d.m.Y")),--}}
    {{--    phone: @json($contract->buyer->phone),--}}
    {{--    delayDays: @json($contract->delayDays),--}}
    {{--    companyName: @json($contract->company_name),--}}
    {{--    contractOnlyView: @json($contract->only_view),--}}
    {{--    jobAddress: @json($contract->buyer->addressWorkplace && $contract->buyer->addressWorkplace->address ? $contract->buyer->addressWorkplace->address : ''),--}}
    {{--    postalArea: @json($clientPostalArea->id ?? null),--}}
    {{--    postalRegion: @json($clientPostalRegion->id ?? null)--}}
    {{--};--}}
    {{--const generalCompanyId = @json( $contract->general_company_id );--}}
    const lang = @json(config('app.locale'));
    const mailStamp = '{{ asset('images/logo_uzpost.png')}}';

    // const url_pathname = window.location.pathname.slice(20); // dev_nurlan. Deprecated

    const app = new Vue({
        el: "#letter",
        data: {
            isVisible: false,
            mailStamp,
            apiToken: '',
            selectedArea: '',
            selectedRegion: '',
            filteredAreas: '1',
            postalAreas: '',
            postalRegions: '',
            letters: '',
            lang,
            client: '',
            company: {},
            isLoading: false,
            buyer: null,
            generalCompany: null,
            callCenter: null,
            buyerAddress: '',
            myId: null,
            contractId: null,
            ltea_total_max_amount:'',
            // letter_type: url_pathname.slice(0, url_pathname.indexOf("/")) // dev_nurlan. Deprecated
        },
        methods: {
            getDaysText(number) {
                if (!String(number).length) return ' дней'
                if (number === 0) {
                    return "0 дней";
                } else if (number % 10 === 1 && number % 100 !== 11) {
                    return number + " день";
                } else if ([2, 3, 4].includes(number % 10) && ![12, 13, 14].includes(number % 100)) {
                    return number + " дня";
                } else {
                    return number + " дней";
                }
            },
            onSelectChange(event) {
                this.selectedRegion = event.target.value
                this.filteredAreas = this.postalAreas.filter(area => area.postal_region_id == this.selectedRegion).sort((a, b) => a.name.localeCompare(b.name))
                this.selectedArea = ''
            },

            toggleModal(isVisible) {
                if (isVisible) {
                    document.querySelector('.modal').style.display = 'block'
                }
                this.isVisible = isVisible
            },

            showPhotoViewer(event) {
                new PhotoViewer([{src: event.target.src, title: event.target.id}]);
            },

            printDocument() {
                polipop.closeAll();
                window.print();
            },

            async checkMyId(buyer) {
                let checkMyStatus = new FormData()
                checkMyStatus.append('api_token', this.apiToken);
                checkMyStatus.append('user_id', buyer.id);

                return axios.post('/api/v1/recovery/myid-status', checkMyStatus)
                    .then(response => response.data)
            },

            async getLetterData() {
                this.contractId = window.location.href.split('/').at(-1)
                try {
                    const {data} = await axios.get(`/api/v1/letters/letter-filling-data?contract_id=${this.contractId}`,
                        {
                            headers: {
                                Authorization: `Bearer ${this.apiToken}`,
                                'Content-Language': this.lang
                            },
                        },
                    )
                    const resp = await data.data
                    this.buyer = resp.buyer;
                    this.notary = resp.buyer.contract.notary_setting
                    this.generalCompany = resp.buyer.contract.general_company
                    this.letters = resp.buyer.contract.letters
                    this.callCenter = resp.callcenter_number
                    this.selectedRegion = this.buyer.addresses.registration_address.postal_region?.external_id
                    this.selectedArea = this.buyer.addresses.registration_address.postal_area?.external_id
                    this.buyerAddress = this.buyer.addresses.registration_address.address

                    this.filteredAreas = this.postalAreas.filter(area => area.postal_region_id == this.selectedRegion).sort((a, b) => a.name.localeCompare(b.name))

                    // check my id form and show link in page
                    this.myId = await this.checkMyId(resp.buyer);
                } catch (err) {
                    console.error(err)
                }
            },

            async getAreas() {
                try {
                    const {data} = await axios.get(`/api/v1/letters/postal-regions-and-areas`,
                        {
                            headers: {
                                Authorization: `Bearer ${this.apiToken}`,
                                'Content-Language': this.lang
                            },
                        },
                    )
                    this.postalAreas = data.postal_areas
                    this.postalRegions = data.postal_regions.sort((a, b) => a.name.localeCompare(b.name))
                } catch (err) {
                    polipop.add({content: err, title: `Ошибка`, type: 'error'})
                    console.error(err)
                }
            },

            async saveAddress(address) {
                this.isLoading = true
                const data = {
                    api_token: this.apiToken,
                    buyer_id: this.buyer.id,
                    postal_region: address.postal_region ?? undefined,
                    postal_area: address.postal_area ?? undefined,
                    address: address.address ?? undefined
                }

                try {
                    const {data: response} = await axios.post('/api/v1/buyer/save-address', data);
                    if (response.status === 'success') {
                        polipop.add({content: response.data[0], title: `Успешно`, type: 'success'})
                        await this.getLetterData()

                    }

                } catch (err) {
                    err.response.data.error.forEach((error) => polipop.add({
                        content: error.text,
                        title: `Ошибка`,
                        type: 'error'
                    }))
                } finally {
                    this.isLoading = false
                }
            },

            async sendLetter(letter_type) {
                await this.saveAddress({postal_region: this.selectedRegion, postal_area: this.selectedArea})
                this.isLoading = true;
                const formData = new FormData();

                if (this.letter_type) formData.append('letter_type', this.letter_type);

                formData.append('api_token', this.apiToken);
                formData.append('contract_id', this.buyer.contract.id);
                formData.append('postal_region', this.selectedRegion);
                formData.append('postal_area', this.selectedArea);

                try {
                    const {data: resp} = await axios.post('/api/v1/letters/send', formData);
                    if (resp.status === 'success') {
                        polipop.add({content: resp.response.message[0].text, title: `Успешно`, type: 'success'});
                    } else {
                        if (resp.response.code === 404) {
                            polipop.add({content: resp.response.message[0].text, title: `Ошибка`, type: 'error'});
                        }
                        Object.values(resp.response.message).forEach(val => {
                            polipop.add({content: val.text, title: `Ошибка`, type: 'error'});
                        })
                    }

                } catch (err) {
                    console.log(err)
                } finally {
                    this.isLoading = false
                    this.isVisible = false
                }
            },
        },
        async created() {
            this.apiToken = Cookies.get('api_token')
            await this.getAreas()
            await this.getLetterData()
        }
    })

    function createDocX(myId, contract) {
        let server_name = '{{$_SERVER['SERVER_NAME']}}'
        window.open(`//${server_name}/uz/panel/contracts/myid/form-1/${myId.id}/docx`, '_blank');
    }

    function createPDF(myId, contract) {
        let server_name = '{{$_SERVER['SERVER_NAME']}}'
        window.open(`//${server_name}/uz/panel/contracts/myid/form-1/${myId.id}/pdf`, '_blank');
    }
</script>

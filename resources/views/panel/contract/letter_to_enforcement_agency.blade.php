@extends('templates.panel.app')
@section('title', __('panel/contract.template_enforcement_agency_letter'))

@section('content')

  <div id="letter" v-if="generalCompany">

    <div class="row mb-5">
      <div class="col-6">

        <div class="page-a4">
          <section class="topside">
            <div class="topside__top">
              <h2>@{{ generalCompany.name_uzlat }}</h2>
              <div class="topside__top-style">
                <hr>
                <hr>
                <hr>
              </div>
              <h5 class="topside__top-address">{{__('panel/contract.address')}}: @{{ generalCompany.address }}</h5>
            </div>

            <div class="topside__info d-flex align-items-center justify-content-between">
              <div class="topside__info-date">
                <div>№ @{{ buyer.contract.recovery.id }}</div>
                <div>Сана: @{{ moment(Date.now()).format('DD.MM.YYYY') }}</div>
              </div>

              <div class="font-weight-900">
                Мажбурий ижро бюро
                <br>
                @{{ buyer.addresses.registration_address.postal_region?.name }}
                <br>
                @{{ buyer.addresses.registration_address.postal_area?.name }}
                <br>
                бўлими бошлиғига
              </div>
            </div>

          </section>

          <section class="letter">
            <p>
              @{{ generalCompany.name_uzlat }} сизга @{{ notary?.region }}да хусусий амалиёт билан
              шуғулланувчи нотариус @{{ notary?.surname }}  @{{ notary?.name }} @{{ notary?.patronymic }} томонидан чиқарилган ижро хатида ундурувчи @{{
              generalCompany.name_uzlat }} фойдасига қарздор @{{ buyer.surname }} @{{ buyer.name }} @{{  buyer.patronymic }} (@{{ buyer.personals.passport_number
              }}), жами @{{ ltea_total_max_amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") || '__' }} сўм қарздорликни ундириш ҳақидаги нотариус ижро хати юбормоқда.
              <br>
              Нотариус ижро хати, Акт ҳамда қарздорнинг паспорт нусхаси илова қилинади.
            </p>
            <h3 style="font-weight: bold; margin-left: 63px; margin-top: 10px">Илова: “___” варақда</h3>
          </section>

          <section class="bottomside">
            <div class="bottomside__register d-flex justify-content-between">
              <p class="flex">
                <span>Директор: </span>
                @{{ generalCompany.name_uzlat }}
              </p>
              <img class="bottomside__register-stamp" :src="generalCompany.stamp" alt="stamp">
              <p><span>@{{ generalCompany.director_uzlat }}</span></p>
            </div>
          </section>

          <p class="bottomside__number"><span>Бажарди:</span> К.Д.Рузиева (90) 052-52-48</p>

          <div style="margin-top: 60px">
            <section class="topside">
              <div class="topside__top">
                <h2>@{{ generalCompany.name_uzlat }}</h2>
                <div class="topside__top-style">
                  <hr>
                  <hr>
                  <hr>
                </div>
                <h5 class="topside__top-address">{{__('panel/contract.address')}}: @{{ generalCompany.address }}</h5>
              </div>

              <div class="topside__info row align-items-center justify-content-between">
                <div class="topside__info-date">
                  <div>№ @{{ buyer.contract.recovery?.id }}</div>
                  <div>Сана: @{{ moment(Date.now()).format('DD.MM.YYYY') }}</div>
                </div>

                <div class="font-weight-900">
                  Мажбурий ижро бюро
                  <br>
                  @{{ buyer.addresses.registration_address.postal_area?.name }}
                  <br>
                  бўлими бошлиғига
                </div>
              </div>

            </section>

            <section class="letter">
              <p>
                @{{ generalCompany.name_uzlat }} сизга @{{ notary?.region }}да хусусий амалиёт билан
                шуғулланувчи нотариус @{{ notary?.surname }}  @{{ notary?.name }} @{{ notary?.patronymic }} томонидан чиқарилган ижро хатида ундурувчи @{{
                generalCompany.name_uzlat }} фойдасига қарздор @{{ buyer.surname }} @{{ buyer.name }} @{{  buyer.patronymic }} (@{{
                buyer.personals.passport_number }}), жами @{{ ltea_total_max_amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") || '__' }} сўм қарздорликни ундириш
                ҳақидаги нотариус ижро хати юбормоқда.
                <br>
                Нотариус ижро хати, Акт ҳамда қарздорнинг паспорт нусхаси илова қилинади.
              </p>
              <h3 style="font-weight: bold; margin-left: 63px; margin-top: 10px">Илова: “___” варақда</h3>
            </section>

            <section class="bottomside">
              <div class="bottomside__register d-flex justify-content-between">
                <p><span>Директор: @{{ generalCompany.name_uzlat }}</span></p>
                <img class="bottomside__register-stamp" :src="generalCompany.stamp" alt="stamp">
                <p><span>@{{ generalCompany.director_uzlat }}</span></p>
              </div>
            </section>

            <p class="bottomside__number"><span>Бажарди:</span> К.Д.Рузиева (90) 052-52-48</p>
          </div>

        </div>

      </div>

      <div class="col-6">
        <div class="info">
          <div class="info__images d-flex">
            <img
              v-if="buyer.personals && buyer.personals.passport_first_page.path"
              id="passport_first_page"
              @click="showPhotoViewer"
              :src="buyer.personals.passport_first_page.path"
            >
            <img v-else
             src="{{ asset('/images/images/media/noimage.svg') }}"
             alt="no-image"
             style="background-color:  #e6e6e6;"
            >

            <img
              v-if="buyer.personals && buyer.personals.passport_with_address.path"
              id="passport_with_address"
              @click="showPhotoViewer"
              :src="buyer.personals.passport_with_address.path"
            >
            <img v-else
             src="{{ asset('/images/images/media/noimage.svg') }}"
             alt="no-image"
             style="background-color: #e6e6e6;"
            >
          </div>

          <form class="mt-5" @submit.prevent="saveAddress({postal_region: selectedRegion, postal_area: selectedArea})">
            <div class="form-group">
              <span>{{__('panel/letters.region')}}:</span>
              <select class="form-control areas" @change="onSelectChange($event)">
                <option value="" disabled selected>{{__('panel/letters.choose_region')}}</option>
                <option
                  v-for="(postalRegion, index) in postalRegions"
                  :selected="postalRegion.external_id === selectedRegion"
                  :key="index"
                  :value="postalRegion.external_id"
                >
                  @{{ postalRegion.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <span>{{__('panel/buyer.address_area')}}:</span>
              <select class="form-control areas" v-model="selectedArea">
                <option value="" disabled selected>{{__('panel/letters.choose_region')}}</option>
                <option
                  v-for="(postalArea, index) in filteredAreas"
                  :selected="postalArea.external_id === selectedArea"
                  :key="index"
                  :value="postalArea.external_id"
                >
                  @{{ postalArea.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <span>{{__('panel/buyer.ltea_total_max_amount')}}:</span>
              <input class="form-control currency-mask" type="number" v-model="ltea_total_max_amount" />
            </div>

            <div class="d-flex justify-content-end mt-2">
              <button :disabled="isLoading || !selectedArea || !selectedRegion" type="submit" class="btn btn-primary mr-2">{{__('panel/letters.change_address')}}</button>
              <button class="btn btn-secondary" type="button" @click="printDocument">&check; {{__('app.btn_print')}}</button>
            </div>
          </form>


        </div>
      </div>

    </div>

  </div>


  @include('panel.contract.parts.letter_config')

@endsection


@extends('templates.panel.app')
@section('title', __('panel/contract.template_workplace_letter'))

@section('content')

  <div id="letter" v-if="generalCompany">
    <div class="row">
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
                <div>№ @{{ buyer.recover?.id }}</div>
                <div>{{ __('panel/contract.pretension_date') }} @{{ moment(Date.now()).format('DD.MM.YYYY') }}</div>
              </div>

              <div class="topside__info-company">
                <span>@{{ generalCompany.director_uzlat ? generalCompany.director_uzlat : '_______________________________'  }}</span><br>
                rahbari
                <br>
                <span class="text-left">Manzil:</span><span> @{{ buyer.addresses.workplace_address ? buyer.addresses.workplace_address.address : '_______________________________'  }}</span><br>
              </div>
            </div>

          </section>

          <section class="letter mt-3">
            <p>
              Sizning xodimingiz <span> </span>@{{ buyer.surname }} @{{  buyer.name }} @{{ buyer.patronymic}}
              tomonidan @{{ generalCompany.name_uzlat }} bilan <span>@{{ buyer.recover?.id }}</span>-sonli
              elektron shartnoma tuzilgan.
            </p>

            <p>
              Mazkur shartnoma shartlariga muvofiq @{{ generalCompany.name_uzlat }}  o‘zining shartnomaviy
              majburiyatlarini
              bajarib,<span>@{{ moment(buyer.contract.created_at, 'DD.MM.YYYY').format('DD.MM.YYYY') }} kuni imzolangan @{{ buyer.contract.id }} sonli dalolatnoma</span>
              asosida shartnomada
              ko’rsatilgan mahsulotni xaridorga yetkazib bergan, haridor esa topshirish-qabul qilish dalolatnomasida
              ko‘rsatilgan jadval asosida jami
              <br>
              <span>@{{ buyer.contract.balance }}</span> so'mlik mahsulot bahosini bo‘lib bo‘lib to‘lash majburiyatini
              olgan.
            </p>
            <p>
              Biroq, <span>@{{ client.now }}</span> yil holatiga ko‘ra, (kechiktirilgan kunlar soni <span>@{{ buyer.contract.delay_days }})</span>
              xaridor tomonidan <span>@{{ buyer.contract.debts_amount }}</span> so‘m pul mablag‘lari to‘lanmasdan,
              shartnomaviy majburiyatlar qo‘pol ravishda buzilmoqda.
            </p>
            <p>
              Yuqoridagilarni inobatga olgan holda, hamda sud hujjatlari va boshqa organlar hujjatlarini ijro etish
              to‘g‘risidagi O‘zbekiston Respublikasi Qonuni, hamda O‘zbekiston Respublikasi Prezidentining <span>“Elektron tijorat maʼmurchiligini takomillashtirish va uni yanada rivojlantirish uchun qulay Sharoitlar yaratish to‘g‘risida” 2021 yildagi 14-sonli </span>
              Qarori ijrosini taʼminlash maqsadida xodimingiz @{{ buyer.surname }} @{{  buyer.name }} @{{ buyer.patronymic}}
              tomonidan qarzdorlikni to‘lab berilishida amaliy yordam berishingizni so‘rayman.
            </p>
          </section>

          <section class="bottomside">
            <div class="bottomside__register d-flex justify-content-between">
              <h3>
                @{{ generalCompany.name_uzlat }}
                <br>
                direktori
              </h3>
              <img
                :src="generalCompany.stamp"
                alt="stamp"
                class="bottomside__register-stamp"
              >
              <h3 class="">@{{ generalCompany.director_uzlat }}</h3>
            </div>

            <p class="bottomside__number">Yurist: +99890 065-65-78</p>
          </section>

        </div>

        <div v-if="letters.length" class="page-a4">
          <div class="contract-letters">
            <template v-for="(letter, index) in letters">
              <div class="ticket mt-2">
                <h4>Ягона миллий тизим орқали юборилган жўнатмалар учун квитанция</h4>
                <img :src="mailStamp">
                <p>Жўнатма рақами: @{{ letter.id }}</p>
                <p>Жўнатилган вақт: @{{ letter.created_at }}</p>
                <p>Кимга: @{{ letter.reciever }}</p>
                <p>Қаерга: @{{ buyer.addresses.residential_address.address }}</p>
              </div>
            </template>
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
            <img v-else src="{{ asset('/images/images/media/noimage.svg') }}"
                 alt="no-image"
                 style="background-color:  #e6e6e6;"
            >

            <img
              v-if="buyer.personals && buyer.personals.passport_with_address.path"
              id="passport_with_address"
              @click="showPhotoViewer"
              :src="buyer.personals.passport_with_address.path"
            >
            <img v-else src="{{ asset('/images/images/media/noimage.svg') }}"
                 alt="no-image"
                 style="background-color: #e6e6e6;"
            >
          </div>

          <h3 style="padding: 20px 0">{{__('billing/profile.btn_edit_data')}}</h3>

          <div class="form-group">
            <label>Ism kiriting</label>
            <input
              class="form-control"
              v-model="generalCompany.director_uzlat"
              placeholder="Ism kiriting"
            />
          </div>

          <div class="form-group">
            <label>Manzil</label>
            <input
              class="form-control"
              v-model="buyer.addresses.workplace_address.address"
              placeholder="Manzil"
            />
          </div>

          <div class="d-flex justify-content-end mt-2">
            <button class="btn btn-outline-secondary mr-2" @click="printDocument">
              &check; {{__('app.btn_print')}}</button>
            <button class="btn btn-outline-secondary" @click="saveAddress(buyer.addresses.workplace_address.address)">💾 {{__('app.btn_save')}}</button>
          </div>

        </div>
      </div>

    </div>
  </div>

  @include('panel.contract.parts.letter_config')
@endsection

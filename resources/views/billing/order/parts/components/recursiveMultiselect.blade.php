<style>
  span.validation-error {
    color: red;
    font-size: 12px;
    line-height: 12px;
    display: block;
    margin-top: 4px;
    position: absolute;
    bottom: -16px;
  }
</style>
<script type="text/x-template" id="recursive-multiselect-template">
  <fragment>
    <div v-if="categories.length" class="form-group col-12 col-sm-3 position-relative">
      <label>{{__('billing/order.lbl_product_category')}}</label>

      <multiselect
        bg-color="grey"
        class="modified single"
        :value="currentCategory"
        label="title"
        track-by="id"
        :loading="loading"
        :disabled="loading"
        :multiple="false"
        :options="filteredCategories"
        deselect-label="Отменить выбор"
        selected-label="Выбрано"
        select-label=""
        placeholder="{{__('billing/order.txt_select_category')}}"
        :allowEmpty="false"
        @select="selectCategory"
      >
        <span slot="noResult">@{{i18n.buyer.validations.no_result}}</span>
        <span slot="noOptions">@{{i18n.buyer.validations.no_select_options}}</span>
          <div slot-scope="props" slot="option" class="d-flex align-items-center justify-content-between">
              <span>@{{ props?.option?.title }}</span>
              <span v-if="props.option?.is_definite === 0" class="btn-icon" style="position: absolute; right: 10px"><img src="{{asset('assets/icons/pencil.svg')}}" alt="edit"></span>
          </div>
      </multiselect>
        <span class="validation-error" v-if="(!noProperCategory && !currentCategory) && !isoverlayed">{{__('billing/order.category_is_required')}}</span>
    </div>
        <RecursiveMultiselect
            v-if="currentCategory"
            :isoverlayed="isoverlayed"
            :categoryId="currentCategory.id"
            :constants="constants"
            :selectedCategories="childCategories"
            :disablesmartphonescat="disablesmartphonescat"
            :noProperCategory="noProperCategory"
            @select="selectChildCategory"
        />
    </fragment>
</script>
<script>
    const RecursiveMultiselect = {
        name: 'RecursiveMultiselect',
        props: ['disablesmartphonescat', 'constants', 'categoryId', 'selectedCategories', 'isoverlayed', 'noProperCategory'],
        template: '#recursive-multiselect-template',
        data(){
            return {
                loading: true,
                categories: [],
                currentCategoryId: null,
                childCategories: [],
                apiToken: globalApiToken,
            }
        },

    watch: {
      categoryId: {
        handler() {
          this.currentCategoryId = null
          this.categories = []
          this.loadCategories()
        },
        immediate: true
      },
      selectedCategories: {
        handler(categories) {
          const selectedCategories = structuredClone(categories)
          this.currentCategoryId = selectedCategories.pop()
          this.childCategories = selectedCategories
        },
        immediate: true
      },
    },
    methods: {
        pressCategoryTitle(e){
            if (e.keyCode === 32 && e.target.selectionStart === 0){
                e.preventDefault()
            }
        },
       isIMEICategory(categoryId) {
            return this.constants.categoriesWithImei.includes(categoryId)
       },

      loadCategories() {
        this.loading = true
        axios.get(`/api/v3/categories/panel-list?api_token=${this.apiToken}&parent_id=${this.categoryId}`, {headers: {'Content-Language': window.Laravel.locale}})
          .then(({data}) => {
            const {status, data: content} = data
            if (data.status !== 'success') {
              return
            }

            if (!content.length) {
              this.$emit('select', [], true)
              return
            }
                        this.categories = content
                    })
                    .catch((error) => {
                        console.log(error)
                    })
                    .finally(() => {
                        this.loading = false
                    })
            },
            selectCategory(category) {
                this.currentCategory = category
                if (!this.noProperCategory) this.$emit('select', [category])
                else this.$emit('select', [category], true)
            },
            selectChildCategory(categories, completed = false) {
                const updatedCategories = structuredClone(categories)
                updatedCategories.push(this.currentCategory)

                if (!this.noProperCategory) this.$emit('select', updatedCategories, completed)
                else this.$emit('select', updatedCategories, true)

            }
        },
    computed: {
          currentCategory() {
            return this.categories.find((category) => category.id === this.currentCategoryId)
          },
          filteredCategories() {
            return this.categories.map((category) => {

                if (this.isIMEICategory(category.id) && !this.isIMEICategory(this.currentCategoryId)) {
                category.$isDisabled = this.disablesmartphonescat
              } else {
                category.$isDisabled = false
              }

              return category
            })
          }
        },
    }
</script>

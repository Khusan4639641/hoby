<script>
    let pagination_template =   /*html*/`
    <div class="pagination mb-3 justify-content-end">

        <div class="pagination__right">
            <span v-if="!loading" class="pagination__txt">страница @{{ options.currentPage }} из @{{ options.pageCount }}</span>
            <div class="pagination__right_buttons">
                <button :disabled="loading || options.currentPage === 1" @click="$emit('prevbutton')" class="pagination__right--left pagination__button">
                    Пред
                </button>
                <button :disabled="loading || options.currentPage === options.pageCount" @click="$emit('nextbutton')" class="pagination__right--right pagination__button">
                    След
                </button>
            </div>
        </div>
    </div>
    `
    let pagination = Vue.component('pagination',{
        props: ['options', 'loading'],
        template: pagination_template,
        methods: {
            selectChange(e){
                this.$emit('changeperpage', e.target.value)
            }
        },
    })
</script>

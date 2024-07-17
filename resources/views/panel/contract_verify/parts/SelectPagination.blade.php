<script>
    let select_pagination = `
      <div class="d-flex justify-content-end align-items-center pagination">
        <button
            class="page-item page-link"
            :disabled="loading"
            @click="$emit('prev-button')"
        >
            Предыдущая
        </button>
        <div class="page-item page-item-select page-link d-flex align-items-center justify-content-between" >
            <select v-model="pagination.currentPage" onfocus='this.size=5;' onblur='this.size=1;' onchange='this.size=1; this.blur();'>
                <option v-for="page in pagination.pageCount" :value="page">@{{ page }}</option>
            </select>
            <span>из @{{ pagination.pageCount }}</span>
        </div>
        <button
            @click="$emit('next-button')"
            :disabled="loading"
            class="page-item page-link"
        >
            Следующая
        </button>
      </div>`

    let SelectPagination = Vue.component('SelectPagination',{
        props: ['pagination', 'loading'],
        template: select_pagination,
    })
</script>

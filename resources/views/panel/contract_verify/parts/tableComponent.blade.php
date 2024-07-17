
<script>
    let datatable_template =   /*html*/`
                <table class="table contract-list m-0">
                    <thead v-if="columns">
                        <tr>
                            <th
                                v-for="(item, index) in columns"
                                :key="index"
                                :width="setWidth(item.width)"
                                scope="col"
                                class="text-sm text-left font-medium "
                            >
                                <div>@{{ item.label }}</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody   v-for="(element, rowindex) in rows" :key="rowindex">
                        <tr class="border-b main-row" :class="{'expanded': element.isEditing}" >
                            <td
                                v-for="(item, index) in columns"
                                :width="setWidth(item.width)"
                                :key="index"
                                style="vertical-align: middle;"
                                :class="{'p-0 border-none': item.key === 'products' }"
                            >
                                <slot
                                    :item="element"
                                    :rowindex="rowindex"
                                    v-if="item.key === 'actions'"
                                    name="actions"
                                ></slot>
                                <slot :item="element" :rowindex="rowindex" :name="item.key" v-else-if="item.key === 'general_company'">
                                    @{{element?.general_company?.name_ru}}
                                </slot>
                                <slot :item="element" :rowindex="rowindex" :name="item.key" v-else>
                                    @{{element[item.key]}}
                                </slot>
                            </td>
                        </tr>
                        <tr v-if="element?.isEditing" class="border-b expandable-row">
                            <td colspan="5" class="px-3">
                                <slot
                                    :item="element"
                                    :rowindex="rowindex"
                                    name="products"
                                ></slot>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-if="!rows.length">
                        <tr>
                            <td colspan="5">
                                <span class="p-4 d-flex bg-light text-muted text-center w-100 align-items-center justify-content-center" >Нет данных</span>
                            </td>
                        </tr>
                    </tbody>

                </table>

    `
    let DataTable = Vue.component('DataTable',{
        props: ['rows', 'columns'],
        methods: {
            setWidth(width){
                return width ? width : false;
            },
        },
        template: datatable_template
    })
</script>

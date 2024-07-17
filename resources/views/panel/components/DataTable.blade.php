{{--
    columns => theaders
        column: {
            title: string,
            key: string => to get necassary row
        }
    rows => trows  => {
        trow: will get by thead key
    }
--}}

<script>
    let datatable_template =   /*html*/`
              <div class="table-responsive">
                <table class="table contract-list m-0">
                    <thead v-if="columns">
                        <tr>
                            <th
                                v-for="(column, index) in columns"
                                :key="index"
                                scope="col"
                                class="text-sm center font-medium "
                            >
                                <div>@{{ column.title }}</div>
                            </th>
                        </tr>
                    </thead>

                     <tbody v-if="isLoading">
                        <tr>
                            <td colspan="12">
                                <span class="p-4 d-flex bg-light text-muted text-center w-100 align-items-center justify-content-center" >Загрузка...</span>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-else-if="!rows.length">
                        <tr>
                            <td colspan="12">
                                <span class="p-4 d-flex bg-light text-muted text-center w-100 align-items-center justify-content-center" >Нет данных</span>
                            </td>
                        </tr>
                    </tbody>

                    <tbody
                        v-else
                        v-for="(row, rowIndex) in rows"
                        :key="rowIndex"
                    >
                        <tr
                            class="border-b main-row cursor-pointer"
                        >
                            <td
                                v-for="(column, index) in columns"
                                :key="index"
                                class="text-center"
                                :style="'vertical-align: middle;'"
                            >

                                <template v-if="row.isEditing">
                                    <span v-if="column.key == 'actions'"></span>

                                    <label class="switch mr-2" v-else-if="column.key == 'is_subconto'" >
                                        <input type="checkbox" id="gridCheck" v-model="row[column.key]">
                                        <span class="slider round"></span>
                                    </label>

                                    <input
                                        v-else-if="column.deep"
                                        type="text"
                                        class="form-control modified"
                                        v-model="row[column.key][column.deep]"
                                    />

                                    <input
                                        v-else
                                        :readonly="column?.readonly || column.key === 'id'"
                                        type="text"
                                        class="form-control modified"
                                        v-model="row[column.key]"
                                    />
                                </template>
                                <template v-else>
                                    <template v-if="column.deep">@{{ row[column.key][column.deep] }}</template>
                                    <template v-else>@{{ row[column.key] }}</template>
                                </template>

                               <slot
                                    v-if="column.key == 'actions'"
                                    :item="row"
                                    :rowIndex="rowIndex"
                                    name="actions"
                                >
                                </slot>

                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

    `
    let DataTable = Vue.component('DataTable',{
        props: ['rows', 'columns', 'isLoading'],
        template: datatable_template
    })
</script>

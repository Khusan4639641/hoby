<div 
    class="modal fade-scale" 
    id="saveModalTemplate" 
    tabindex="-1" 
    role="dialog"
>
    <div 
        class="modal-dialog" 
        role="document" 
        style="max-width:408px;"
    >
        <div class="modal-content">
            <input 
                type="hidden" 
                id="deleteID"
            >
            <div class="modal-header border-0">
                <button 
                    type="button" 
                    class="close" 
                    data-dismiss="modal" 
                    aria-label="Close"
                >
                    <span aria-hidden="true">
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path 
                                d="M6.66699 6.646L17.333 17.31M6.66699 17.31L17.333 6.646" 
                                stroke="#1E1E1E"
                                stroke-width="1.4" 
                                stroke-miterlimit="10" 
                                stroke-linecap="round"
                                stroke-linejoin="round" 
                            />
                        </svg>
                    </span>
                </button>
            </div>
            <div class="modal-body px-5 pt-0 text-center">
                <h5 class="modal-title mb-3">
                    {{__('payment_info.modal_template_msg')}}
                </h5>
                <p>
                    <span style="color: var(--orange);">
                        <b>@{{templateName}}</b>
                    </span>
                </p>
            </div>
            <div 
                class="modal-footer px-4 border-0 pb-4 d-flex justify-content-center" 
                style="gap:16px;"
            >
                <button 
                    style="min-width: 160px;" 
                    type="button"
                    class="btn px-3 d-inline-flex align-items-center justify-content-center btn-orange-light"
                    data-dismiss="modal"
                >
                    {{__('app.no')}}
                </button>
                <button 
                    v-if="acc_id"
                    style="min-width: 160px;" 
                    :class="{'processing':btnLoading}" 
                    type="submit"
                    @click="editTemplate"
                    class="btn px-3 d-inline-flex align-items-center justify-content-center btn-orange  "
                >
                {{__('app.yes')}}
                    <div class="spinner-border "></div>
                </button>
                <button
                    v-else 
                    style="min-width: 160px;" 
                    :class="{'processing':btnLoading}" 
                    type="submit"
                    @click="saveTemplate"
                    class="btn px-3 d-inline-flex align-items-center justify-content-center btn-orange  "
                >
                    {{__('app.yes')}}
                    <div class="spinner-border "></div>
                </button>
            </div>
        </div>
    </div>
</div>

<!---------------------- Delete Modal --------------------------------->
<div 
    class="modal fade-scale" 
    id="deleteModalTemplate" 
    tabindex="-1" 
    role="dialog"
>
    <div 
        class="modal-dialog" 
        role="document" 
        style="max-width:408px;"
    >
        <div class="modal-content">
            <input 
                type="hidden" 
                id="deleteID"
            >
            <div class="modal-header border-0">
                <button 
                    type="button" 
                    class="close" 
                    data-dismiss="modal" 
                    aria-label="Close"
                >
                    <span aria-hidden="true">
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            width="24" 
                            height="24" 
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path 
                                d="M6.66699 6.646L17.333 17.31M6.66699 17.31L17.333 6.646" 
                                stroke="#1E1E1E"
                                stroke-width="1.4" 
                                stroke-miterlimit="10" 
                                stroke-linecap="round"
                                stroke-linejoin="round" 
                            />
                        </svg>
                    </span>
                </button>
            </div>
            <div class="modal-body px-5 pt-0 text-center">
                <h5 class="modal-title mb-3">
                    {{__('payment_info.modal_template_delete_msg')}}
                </h5>
            </div>
            <div 
                class="modal-footer px-4 border-0 pb-4 d-flex justify-content-center" 
                style="gap:16px;"
            >
                <button 
                    style="min-width: 160px;" 
                    type="button"
                    class="btn px-3 d-inline-flex align-items-center justify-content-center btn-orange-light"
                    data-dismiss="modal"
                >
                    {{__('app.no')}}
                </button>
                <button 
                    style="min-width: 160px;" 
                    :class="{'processing':btnLoading}" 
                    type="submit"
                    @click="deleteTemplate"
                    class="btn px-3 d-inline-flex align-items-center justify-content-center btn-orange  "
                >
                    {{__('app.yes')}}
                    <div class="spinner-border "></div>
                </button>
            </div>
        </div>
    </div>
</div>
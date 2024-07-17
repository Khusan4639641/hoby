<style>
    .just-padding {
        height: 236px; 
        overflow: auto;
        border-radius: 5px;
    }
    .just-padding::-webkit-scrollbar {
        width: 6px;
    }
    .just-padding::-webkit-scrollbar-track {
        background-color: rgb(246, 246, 246);
        border-radius: 10px;
    }
    .just-padding::-webkit-scrollbar-thumb {
        border-radius: 10px;
        background-color: #6610F5;
    }

    .list-group.list-group-root {
        padding: 0;
        overflow: hidden;
    }

    .list-group.list-group-root .list-group {
        margin-bottom: 0;
    }

    .list-group.list-group-root .list-group-item {
        border-radius: 0;
        border-width: 0 0 0 0;
        cursor: pointer;
        background-color:rgb(246, 246, 246);
    }
    .list-group.list-group-root .list-group-item:hover {
        color: rgb(102, 16, 245);
        background-color:rgb(230, 230, 230);
    }
    .brand-selected {
        color: rgb(102, 16, 245)!important;
        background-color:rgb(230, 230, 230)!important;
    }

    .list-group.list-group-root > .list-group-item:first-child {
        border-top-width: 1px 0 0 0;
        
    }

    .list-group.list-group-root > .list-group > .list-group-item {
        padding-left: 40px;
        
    }

    /* .list-group.list-group-root > .list-group > .list-group > .list-group-item {
        background-color:red;
        padding-left: 45px;
    } */



    .fade-scale {
        transform: scale(.8) translateY(10px);
        opacity: 0;
        -webkit-transition: all .15s linear;
        -o-transition: all .15s linear;
        transition: all .15s linear;
    }
    .form-control.modified + .invalid-feedback {
        animation: slide-down .35s ease-in-out
    }
    @keyframes slide-down {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .form-control.modified.is-invalid {
        background-color: #ffd7d736;
    }
    .form-control.modified.is-invalid:focus {
        border-color: #ff7885;
    }
    .fade-scale.show {
        opacity: 1;
        transform: scale(1) translateY(0px);
    }
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn.processing {
        opacity: .3;
    }
    .btn .spinner-border {
        display: none;
        width: 1rem;
        height: 1rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
    }
    .btn.processing .spinner-border {
        display: inline-block;
    }
    .multiselect.modified.single .multiselect__tags {
        min-height: 56px;
        border-radius: 14px;
    }
    .multiselect.modified.single .multiselect__input, .multiselect.modified.single .multiselect__single {
        line-height: 32px;
    }
    .multiselect.modified.single .multiselect__tags span span.multiselect__single,
    .multiselect.modified.single .multiselect__tags .multiselect__input::placeholder {
        opacity: .4;
    }
    .btn:focus {
        border: 1px solid transparent;
    }
    button:focus {
        outline: none;
        box-shadow: none;
    }
    .form-group label {
        font-style: normal;
        font-weight: 400;
        font-size: 15px;
        line-height: 24px;
        letter-spacing: 0.01em;
        color: #2A2A2A;
    }
    .form-control.modified {
       
    }
    .spinner-border {
        width: 20px;
        height: 20px;
        border: 1px solid currentColor;
        border-right-color: transparent;
    }
    .modal-backdrop.show {
        opacity: .2;
    }
    .modal-content {
        border: none !important;
        border-radius: 16px;
        outline: 0;
        box-shadow: 0px 16px 20px rgb(0 0 0 / 20%);
    }
    .modal__title h5 {
        font-style: normal;
        font-weight: 700;
        font-size: 18px;
        line-height: 19px;
        color: #1E1E1E;
    }
    .modal__title p {
        font-family: 'Gilroy';
        font-style: normal;
        font-weight: 500;
        font-size: 18px;
        line-height: 0%;
        color: #1E1E1E;
    }
    .btn.disabled {
        cursor: not-allowed;
        pointer-events: none;
        opacity: .65;
    }
    .btn.btn-orange-light {
        background-color: #6610F51A;
    }
    .btn.btn-orange-light:hover {
        background-color: #6610f533;
    }
    .form-control.modified {
        border-radius: 14px;
        height: 56px;
        line-height: 56px;
        padding: 16px !important;
    }
    .form-control.modified.is-invalid {
        box-shadow: none !important;
        border: 1px solid #ff97a1 !important;
    }
    select.form-control.modified {
        cursor: pointer;
    }
    section {
        margin-bottom: 1rem;
    }
    section hr {
        margin-left: -2rem;
        margin-right: -2rem;
    }
    section .section-title {
        font-family: 'Gilroy';
        font-style: normal;
        font-weight: 700;
        font-size: 24px;
        line-height: 30px;
        color: #1E1E1E;
        margin-bottom: 1.5rem;
    }
    .form-group {
        width: 100%;
        margin-bottom: 1.5rem;
    }
    .btn {
        border-radius: 14px !important;
        padding: 15px 42px !important;
    }
    table.history-table th {
        font-family: 'Gilroy';
        font-style: normal;
        font-weight: 500;
        font-size: 12px;
        line-height: 16px;
        color: #888888;
        vertical-align: middle;
        border: none;
    }
    table.history-table td {
        font-family: 'Gilroy';
        font-style: normal;
        font-weight: 500;
        font-size: 12px;
        line-height: 16px;
        color: #1E1E1E;
        vertical-align: middle;
        padding: 8px;
    }
    table.history-table tr td.col-success {
        background-color: #40DC7533;
        color: #0FBE7B;
        text-align: center;
    }
    table.history-table tr td.col-error {
        background-color: #F8434333;
        color: #F84343;
        text-align: center;
    }
    table.history-table tr td:first-child, table.history-table tr th:first-child {
        /* padding-left: 0 !important; */
        border-left: 4px solid transparent
    }
    table.history-table tr.row-success:hover {
        background-color: #40dc751a;
    }
    table.history-table tr.row-error:hover {
        background-color: #f843431a;
    }
    table.history-table tr.row-success td:first-child {
        /* padding-left: 0 !important; */
        border-left-color: #53DB8C;
    }
    table.history-table tr.row-error td:first-child {
        /* padding-left: 0 !important; */
        border-left-color: #F84343;
    }
    table.history-table tr td:last-child, table.history-table tr th:last-child {
        /* padding-right: 0 !important; */
    }
    .modal-title {
        font-family: 'Gilroy';
        font-style: normal;
        font-weight: 700;
        font-size: 24px;
        line-height: 32px;
        text-align: center;
        letter-spacing: 0.01em;
        color: #1E1E1E;
    }
</style>
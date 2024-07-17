<div id="alert_tag_id" class="row w-100">

</div>
<div class="row">

    <div class="col-12 col-lg-6">
        <div class="scoring_katm">
            <div class="form-group">
                <label class="mb-2">{{__('panel/buyer.current_phone_number')}}</label>
                <input class="form-control"
                       type="text"
                       name="old_phone_number"
                       value="{{$buyer->phone}}"
                       disabled required>
            </div><!-- /.form-group -->

            <div class="form-group">
                <label id="new_phone_title_id">{{__('panel/buyer.new_phone_number')}}</label>

                <input class="form-control"
                       type="text"
                       id="phone"
                       name="new_phone_number"
                       required maxlength="17">

                <input type="hidden" id="input_phone_id">
            </div><!-- /.form-group -->

            <div class="form-group w-100">
                <label id="definition_title_id">{{__('panel/buyer.definition')}}</label>
                <textarea name="definition" id="definition_id" required class="form-control" rows="6"></textarea>
            </div>

            <input type="hidden" id="user_id" name="user_id" value="{{$buyer->id}}">

        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="scoring_katm">
            <div class="form-group" style="">
                <label id="image_title_id">{{__('panel/buyer.photo_passport_with_phone')}}</label>
                <img id="image" src="{{asset('images/icons/icon_user_grey_square.svg')}}" alt="image"
                     style="border-radius: 8px;border: solid #E5E5E5 2px;padding: 10px 60px;height: 265px;width: auto">
            </div>

            <div class="form-group w-100">
                <input class=d-none type="file" id="input__file">
                <button class="btn btn-orange w-100"
                        onclick="clickButton()">
                    {{__('panel/buyer.upload_image')}}
                </button>
            </div>
        </div>
    </div>
    <div id="button_tag_id" class="form-group  justify-content-end w-100 d-none">
        <a href="{{url()->current()}}" class="btn btn-orange "> {{__('panel/buyer.cancel')}}</a>
        <button class="btn btn-orange ml-4 mr-3" onclick="sendData() ">
            {{__('panel/buyer.send')}}
        </button>
    </div>
</div>



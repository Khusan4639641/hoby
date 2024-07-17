<?php
@if(count($buyerPersonals->files) > 0)
    <ul>
        @foreach($buyerPersonals->files as $file)
            <li class="buyer-personal-files">
                <a
                    href="{{ \App\Helpers\FileHelper::url($file->path) }}"
                    data-imagesrc="{{ $file->path }}"
                    data-docpath="{{ $file->doc_path }}"
                    data-imagelabel="{{__('panel/buyer.'.$file->type)}}"
                >
                    {{__('panel/buyer.'.$file->type)}}
                </a>
            </li>
        @endforeach
    </ul>
    {{--                <button id="showImage">img show</button>--}}
@endif

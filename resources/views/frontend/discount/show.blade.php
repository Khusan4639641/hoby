@extends('templates.frontend.app')
@section('class', 'discount detail')
@section('title', $discount->locale->title)

@section('content')

    <div class="container">

       <div class="row">
           <div class="col-12 col-md-6">
               <img class="img" src="/storage/{{$discount->locale->image_list->path}}" alt="">
           </div>
           <div class="col-12 col-md-6">
               <h1>{{$discount->locale->title}}</h1>
               <div class="dates">
                   <div class="title">{{__('frontend/discount.date')}}</div>
                   <div class="value">
                       <span>{{__('frontend/discount.from')}}</span> {{$discount->date_start}} {{$discount->time_start}}
                       <span>{{__('frontend/discount.to')}}</span> {{$discount->date_end}} {{$discount->time_end}}
                   </div>
               </div>

               <div class="detail-text">
                   {!! $discount->locale->detail_text !!}
               </div><!-- /.detail-text -->

               <div class="controls">
                   <div class="row">
                       <div class="col-12 col-sm-6 part">
                           <div class="label">{{__('frontend/discount.discount_3')}}</div>
                            <div class="btn btn-success">
                                {{$discount->discount_3}} {{__('frontend/discount.curr')}}
                            </div>
                       </div>

                       <div class="col-12 col-sm-6 part">
                           <div class="label">{{__('frontend/discount.discount_6')}}</div>
                           <div class="btn btn-success">
                               {{$discount->discount_6}} {{__('frontend/discount.curr')}}
                           </div>
                       </div>

                       <div class="col-12 col-sm-6 part">
                           <div class="label">{{__('frontend/discount.discount_6')}}</div>
                           <div class="btn btn-success">
                               {{$discount->discount_6}} {{__('frontend/discount.curr')}}
                           </div>
                       </div>

                       <div class="col-12 col-sm-6 part">
                           <div class="label">{{__('frontend/discount.discount_12')}}</div>
                           <div class="btn btn-success">
                               {{$discount->discount_12}} {{__('frontend/discount.curr')}}
                           </div>
                       </div>
                   </div>
               </div>
           </div>
       </div>
    </div><!-- /.container -->

@endsection

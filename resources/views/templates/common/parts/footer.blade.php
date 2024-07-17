<footer class="footer">
    <div class="top">
        <div class="container">
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3 part">
                    <div class="support">
                        <div class="list-phones">
                            <div class="lead">{{__('template.footer_support')}}</div>
                            <ul class="list-unstyled">
                                <li>+998 95 479 0770</li>
                                <li>+998 95 479 7007</li>
                            </ul>
                        </div>
                        <div class="lead">{{__('template.footer_working_hours')}}</div>
                        <p class="working">
                            09:00 &mdash; 19:00 <br>
                            Понедельник &mdash; Суббота
                        </p>
                    </div><!-- /.support -->

                </div><!-- ./part -->
                <div class="col-12 col-sm-6 col-md-3 part">
                    <div class="faq">
                        <div class="lead">{{__('template.footer_faq')}}</div>
                        {{App\Http\Controllers\Web\Frontend\FaqController::widget()}}
                    </div><!-- /.faq -->
                </div><!-- ./part -->
                <div class="col-12 col-sm-6 col-md-3 part">
                    <div class="faq">
                        <div class="lead">{{__('template.footer_pages')}}</div>
                        {{App\Helpers\MenuHelper::render('frontend', 'pages')}}
                    </div><!-- /.faq -->
                </div><!-- ./part -->
                <div class="col-12 col-sm-6 col-md-3 part">
                    <div class="lead">{!! __('template.footer_test_name')!!}</div>
                    <p>{{__('template.footer_test_address')}}</p>

                    <div class="social">
                        <div class="lead">{{__('template.footer_social')}}</div>
                        <ul class="list-social">
                            <li><a href="#"><img src="{{asset('images/icons/icon_social_fb.svg')}}"></a></li>
                            <li><a href="#"><img src="{{asset('images/icons/icon_social_inst.svg')}}"></a></li>
                            <li><a href="#"><img src="{{asset('images/icons/icon_social_tg.svg')}}"></a></li>
                        </ul>
                    </div>
                </div><!-- ./part -->
            </div><!-- /.row -->
        </div><!-- /.container -->
    </div><!-- /.top -->

    <div class="bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-md-6">
                    {{App\Helpers\MenuHelper::render('frontend', 'bottom')}}
                </div>

                <div class="col-12 col-sm-6 col-md-3 pr-0">
                    <div class="copyright">{!! __('template.txt_copyright', ['year' => date('Y')]); !!}</div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="logo">
                        <img height="40px" width="auto" src="{{asset('images/logo_white.svg')}}">
                    </div>
                </div>
            </div><!-- /.row -->
        </div><!-- /.container -->
    </div><!-- /.bottom -->

</footer><!-- /.footer -->

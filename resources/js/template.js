$(document).ready(function(){
    renderTemplate();

    $(".catalog-open").click(function (){
        $('.catalog-overlay').toggle();
    });

    showSubCategories();

    let $rootCategories = $('.catalog-overlay .categories-root');

    $('ul.cats-menu li', $rootCategories).hover(function (){
        let id = $(this).data('id');
        $(this).siblings('li').removeClass('active');
        $(this).addClass('active');
        showSubCategories(parseInt(id));
    }, function (){

    });

})

$(window).on('resize', function(){
    renderTemplate();
})

function renderTemplate(){
    let wH = $(window).height();
    let mH = $(window).height() - $('.header').outerHeight() - 32;
    $('.content').css('min-height', mH + 'px');

    let wW = $(window).width();
    if(wW > 767) {
        let cbH = wH - $('header').outerHeight(true) - $('.center-header').outerHeight(true) - 64;
        let asW = $('aside').height() - $('.center-header').outerHeight(true) ;
        if(cbH < asW) cbH = asW;
        $('.center-body').css('min-height', cbH + 'px');

        let crH = wH - $('header').outerHeight();
        $('.categories-root').outerHeight(crH + 'px');
        /*$('aside').css('height', (wH - $('header').outerHeight(true) - 32) + 'px');*/
        /*$('aside .menu').height($('aside').height() - $('aside .left-card').outerHeight() + 'px');*/
    }else {
        $('.categories-root').height('auto');
        $('.center-body').css('min-height', 'auto');
        /*$('aside').css('height', 'auto');*/
        /*$('aside .menu').css('height', 'auto');*/
    }
}

function showSubCategories(parent = 0){
    let $subCategories = $('.catalog-overlay .categories-sub');

    $('.category-level1', $subCategories).hide();
    if(parent > 0)
        $('.category-level1[data-parent='+parent+']').show();
    else
        $('.category-level1:first').show();
}

/*
 *  Document   : app.js
 *  Author     : pixelcave
 *  Description: Custom scripts and plugin initializations (available to all pages)
 *
 *  Feel free to remove the plugin initilizations from uiInit() if you would like to
 *  use them only in specific pages. Also, if you remove a js plugin you won't use, make
 *  sure to remove its initialization from uiInit().
 */

var App = function() {

    /* Helper variables - set in uiInit() */
    var page, pageContent, header, sidebar, sBrand, sExtraInfo, sidebarAlt, sScroll, sScrollAlt;

    /* Initialization UI Code */
    var uiInit = function() {

        // Set variables - Cache some often used Jquery objects in variables */
        page            = $('#page-container');
        header          = $('header');
        pageContent     = $('#page-content');

        sidebar         = $('#sidebar');
        sBrand          = $('#sidebar-brand');
        sExtraInfo      = $('#sidebar-extra-info');
        sScroll         = $('#sidebar-scroll');

        sidebarAlt      = $('#sidebar-alt');
        sScrollAlt      = $('#sidebar-scroll-alt');

        // Initialize sidebars functionality
        handleSidebar('init');

        // Sidebar navigation functionality
        handleNav();

        // Color Theme Preview functionality
        colorThemePreview();

        // Header glass effect on scrolling
        $(window).on('scroll', function(){
            if ((header.hasClass('navbar-fixed-top') || header.hasClass('navbar-fixed-bottom'))) {
                if ($(this).scrollTop() > 50) {
                    header.addClass('navbar-glass');
                } else {
                    header.removeClass('navbar-glass');
                }
            }
        });

        // Resize #page-content to fill empty space if exists
        $(window).on('resize orientationchange', function(){ resizePageContent(); }).resize();

        // Add the correct copyright year
        var yearCopy = $('#year-copy'), d = new Date();
        if (d.getFullYear() === 2014) { yearCopy.html('2014'); } else { yearCopy.html('2014-' + d.getFullYear().toString().substr(2,2)); }

        // Intialize ripple effect on buttons
        rippleEffect($('.btn-effect-ripple'), 'btn-ripple');

        // Initialize Tabs
        $('[data-toggle="tabs"] a, .enable-tabs a').click(function(e){ e.preventDefault(); $(this).tab('show'); });

        // Initialize Tooltips
        $('[data-toggle="tooltip"], .enable-tooltip').tooltip({container: 'body', animation: false});

        // Initialize Popovers
        $('[data-toggle="popover"], .enable-popover').popover({container: 'body', animation: true});

        // Initialize Image Lightbox
        $('[data-toggle="lightbox-image"]').magnificPopup({type: 'image', image: {titleSrc: 'title'}});

        // Initialize image gallery lightbox
        $('[data-toggle="lightbox-gallery"]').magnificPopup({
            delegate: 'a',
            type: 'image',
            gallery: {
                enabled: true,
                navigateByImgClick: true,
                arrowMarkup: '<button type="button" class="mfp-arrow mfp-arrow-%dir%" title="%title%"></button>',
                tPrev: 'Previous',
                tNext: 'Next',
                tCounter: '<span class="mfp-counter">%curr% of %total%</span>'
            },
            image: {titleSrc: 'title'}
        });

        // Initialize Editor
        $('.textarea-editor').wysihtml5();

        // Initialize Chosen
        $('.select-chosen').chosen({width: "100%"});

        // Initialize Select2
        $('.select-select2').select2();

        // Initialize Slider for Bootstrap
        $('.input-slider').slider();

        // Initialize Tags Input
        $('.input-tags').tagsInput({ width: 'auto', height: 'auto'});

        // Initialize Timepicker
        $('.input-timepicker').timepicker({minuteStep: 1,showSeconds: true,showMeridian: true});
        $('.input-timepicker24').timepicker({minuteStep: 1,showSeconds: true,showMeridian: false});

        // Initialize Datepicker
        $('.input-datepicker, .input-daterange').datepicker({weekStart: 1}).on('changeDate', function(e){ $(this).datepicker('hide'); });

        // Easy Pie Chart
        $('.pie-chart').easyPieChart({
            barColor: $(this).data('bar-color') ? $(this).data('bar-color') : '#777777',
            trackColor: $(this).data('track-color') ? $(this).data('track-color') : '#eeeeee',
            lineWidth: $(this).data('line-width') ? $(this).data('line-width') : 3,
            size: $(this).data('size') ? $(this).data('size') : '80',
            animate: 800,
            scaleColor: false
        });

        // Initialize Placeholder (for IE9)
        $('input, textarea').placeholder();
    };

    /* Page Loading functionality */
    var pageLoading = function(){
        var pageWrapper = $('#page-wrapper');

        if (pageWrapper.hasClass('page-loading')) {
            pageWrapper.removeClass('page-loading');
        }
    };

    /* Sidebar Navigation functionality */
    var handleNav = function() {
        // Get all vital links
        var allLinks        = $('.sidebar-nav a', sidebar);
        var menuLinks       = $('.sidebar-nav-menu', sidebar);
        var submenuLinks    = $('.sidebar-nav-submenu', sidebar);

        // Add ripple effect to all navigation links
        allLinks.on('click', function(e){
            var link = $(this), ripple, d, x, y;

            // Remove .animate class from all ripple elements
            sidebar.find('.sidebar-nav-ripple').removeClass('animate');

            // If the ripple element doesn't exist in this link, add it
            if(link.children('.sidebar-nav-ripple').length == 0) {
                link.prepend('<span class="sidebar-nav-ripple"></span>');
            }

            // Get the ripple element
            var ripple = link.children('.sidebar-nav-ripple');

            // If the ripple element doesn't have dimensions set them accordingly
            if(!ripple.height() && !ripple.width()) {
                d = Math.max(link.outerWidth(), link.outerHeight());
                ripple.css({height: d, width: d});
            }

            // Get coordinates for our ripple element
            x = e.pageX - link.offset().left - ripple.width()/2;
            y = e.pageY - link.offset().top - ripple.height()/2;

            // Position the ripple element and add the class .animate to it
            ripple.css({top: y + 'px', left: x + 'px'}).addClass('animate');
        });

        // Primary Accordion functionality
        menuLinks.on('click', function(e){
            var link = $(this);
            var windowW = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

            // If we are in mini sidebar mode
            if (page.hasClass('sidebar-visible-lg-mini') && (windowW > 991)) {
                if (link.hasClass('open')) {
                    link.removeClass('open');
                }
                else {
                    $('#sidebar .sidebar-nav-menu.open').removeClass('open');
                    link.addClass('open');
                }
            }
            else if (!link.parent().hasClass('active')) {
                if (link.hasClass('open')) {
                    link.removeClass('open');
                }
                else {
                    $('#sidebar .sidebar-nav-menu.open').removeClass('open');
                    link.addClass('open');
                }

                // Resize Page Content
                setTimeout(resizePageContent, 50);
            }

            return false;
        });

        // Submenu Accordion functionality
        submenuLinks.on('click', function(e){
            var link = $(this);

            if (link.parent().hasClass('active') !== true) {
                if (link.hasClass('open')) {
                    link.removeClass('open');
                }
                else {
                    link.closest('ul').find('.sidebar-nav-submenu.open').removeClass('open');
                    link.addClass('open');
                }

                // Resize Page Content
                setTimeout(resizePageContent, 50);
            }

            return false;
        });
    };

    /* Ripple effect on click functionality */
    var rippleEffect = function(element, cl){
        // Add required classes to the element
        element.css({
            'overflow': 'hidden',
            'position': 'relative'
        });

        // On element click
        element.on('click', function(e){
            var elem = $(this), ripple, d, x, y;

            // If the ripple element doesn't exist in this element, add it..
            if(elem.children('.' + cl).length == 0) {
                elem.prepend('<span class="' + cl + '"></span>');
            }
            else { // ..else remove .animate class from ripple element
                elem.children('.' + cl).removeClass('animate');
            }

            // Get the ripple element
            var ripple = elem.children('.' + cl);

            // If the ripple element doesn't have dimensions set them accordingly
            if(!ripple.height() && !ripple.width()) {
                d = Math.max(elem.outerWidth(), elem.outerHeight());
                ripple.css({height: d, width: d});
            }

            // Get coordinates for our ripple element
            x = e.pageX - elem.offset().left - ripple.width()/2;
            y = e.pageY - elem.offset().top - ripple.height()/2;

            // Position the ripple element and add the class .animate to it
            ripple.css({top: y + 'px', left: x + 'px'}).addClass('animate');
        });
    };

    /* Sidebars Functionality */
    var handleSidebar = function(mode){
        if (mode === 'init') {
            // Init sidebars scrolling functionality
            handleSidebar('sidebar-scroll');
            handleSidebar('sidebar-alt-scroll');

            // init sidebar profile validation
            handleSidebar('init-profile-validation');

            handleSidebar('init-events');
        } else if (mode == 'init-profile-validation') {
            // initialize profile form validation
            $('#form-side-profile').validate({
                errorClass: 'help-block animation-slideUp',
                errorElement: 'div',
                errorPlacement1: function(error, e) {
                    e.parents('.form-group > div').append(error);
                },
                highlight: function(e) {
                    $(e).closest('.form-group').removeClass('has-success has-error').addClass('has-error');
                    $(e).closest('.help-block').remove();
                },
                success: function(e) {
                    if (e.closest('.form-group').find('.help-block').length === 2) {
                        e.closest('.help-block').remove();
                    } else {
                        e.closest('.form-group').removeClass('has-success has-error');
                        e.closest('.help-block').remove();
                    }
                },
                rules: {
                    'side-profile-fullname': {
                        required: true,
                        minlength: 5
                    },
                    'side-profile-email': {
                        required: true,
                        email: true
                    },
                    'side-profile-password': {
                        minlength: 5
                    },
                    'side-profile-password-confirm': {
                        equalTo: '#side-profile-password'
                    }
                },
                messages: {
                    'side-profile-fullname': {
                        required: 'Please enter your full name',
                        minlength: 'Please enter your full name'
                    },
                    'side-profile-email': 'Please enter a valid email address',
                    'side-profile-password': {
                        minlength: 'Your password must be at least 5 characters long'
                    },
                    'side-profile-password-confirm': {
                        minlength: 'Your password must be at least 5 characters long',
                        equalTo: 'Please enter the same password as above'
                    }
                }
            });
        } else if (mode == 'init-events') {
            // Handle main sidebar's scrolling functionality on resize or orientation change
            var sScrollTimeout;

            $(window).on('resize orientationchange', function(){
                clearTimeout(sScrollTimeout);

                sScrollTimeout = setTimeout(function(){
                    handleSidebar('sidebar-scroll');
                }, 150);
            });

            // handle toggling Attach email header
            $('#sidebar-alt-email-header-toggler').change(function(e) {
                var url = $(this).parents('form')[0].action;
                var value = this.checked;
                $.post(url,
                    {
                        name: 'email-header',
                        value: value
                    },
                    function(result) {
                        if (result.status) {
                            var message = 'Info header will ' + (value ? '' : 'not') + ' be attached';
                            App.flashMessages.add(message, 'success');
                        } else {
                            App.flashMessages.add('Error happened', 'error');
                        }
                    },
                    'json'
                );
            });
        } else {
            var windowW = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

            if (mode === 'toggle-sidebar') {
                if ( windowW > 991) { // Toggle main sidebar in large screens (> 991px)
                    if (page.hasClass('sidebar-visible-lg-full')) {
                        page.removeClass('sidebar-visible-lg-full').addClass('sidebar-visible-lg-mini');
                    } else if (page.hasClass('sidebar-visible-lg-mini')) {
                        page.removeClass('sidebar-visible-lg-mini').addClass('sidebar-visible-lg-full');
                    } else {
                        page.addClass('sidebar-visible-lg-mini');
                    }

                    // Resize Page Content
                    setTimeout(resizePageContent, 50);
                } else { // Toggle main sidebar in small screens (< 992px)
                    page.toggleClass('sidebar-visible-xs');

                    if (page.hasClass('sidebar-visible-xs')) {
                        handleSidebar('close-sidebar-alt');
                    }
                }

                // Handle main sidebar scrolling functionality
                handleSidebar('sidebar-scroll');
            }
            else if (mode === 'open-sidebar') {
                if ( windowW > 991) { // Open main sidebar in large screens (> 991px)
                    page.removeClass('sidebar-visible-lg-mini').addClass('sidebar-visible-lg-full');
                } else { // Open main sidebar in small screens (< 992px)
                    page.addClass('sidebar-visible-xs');
                    handleSidebar('close-sidebar-alt');
                }
                // Handle main sidebar scrolling functionality
                handleSidebar('sidebar-scroll');

                // Resize Page Content
                setTimeout(resizePageContent, 50);
            }
            else if (mode === 'close-sidebar') {
                if ( windowW > 991) { // Close main sidebar in large screens (> 991px)
                    page.removeClass('sidebar-visible-lg-full').addClass('sidebar-visible-lg-mini');
                } else { // Close main sidebar in small screens (< 992px)
                    page.removeClass('sidebar-visible-xs');
                }

                // Handle main sidebar scrolling functionality
                handleSidebar('sidebar-scroll');
            }
            else if (mode === 'toggle-sidebar-alt') {
                if ( windowW > 991) { // Toggle alternative sidebar in large screens (> 991px)
                    page.toggleClass('sidebar-alt-visible-lg');
                } else { // Toggle alternative sidebar in small screens (< 992px)
                    page.toggleClass('sidebar-alt-visible-xs');

                    if (page.hasClass('sidebar-alt-visible-xs')) {
                        handleSidebar('close-sidebar');
                    }
                }
            }
            else if (mode === 'open-sidebar-alt') {
                if ( windowW > 991) { // Open alternative sidebar in large screens (> 991px)
                    page.addClass('sidebar-alt-visible-lg');
                } else { // Open alternative sidebar in small screens (< 992px)
                    page.addClass('sidebar-alt-visible-xs');
                    handleSidebar('close-sidebar');
                }
            }
            else if (mode === 'close-sidebar-alt') {
                if ( windowW > 991) { // Close alternative sidebar in large screens (> 991px)
                    page.removeClass('sidebar-alt-visible-lg');
                } else { // Close alternative sidebar in small screens (< 992px)
                    page.removeClass('sidebar-alt-visible-xs');
                }
            }
            else if (mode == 'sidebar-scroll') { // Handle main sidebar scrolling
                if (page.hasClass('sidebar-visible-lg-mini') && (windowW > 991)) { // Destroy main sidebar scrolling when in mini sidebar mode
                    if (sScroll.length && sScroll.parent('.slimScrollDiv').length) {
                        sScroll
                            .slimScroll({destroy: true});
                        sScroll
                            .attr('style', '');
                    }
                }
                else if ((header.hasClass('navbar-fixed-top') || header.hasClass('navbar-fixed-bottom'))) {
                    var sHeight = $(window).height() - (sBrand.outerHeight() + (sExtraInfo.css('display') === 'none' ? 0 : sExtraInfo.outerHeight()));

                    if (sScroll.length && (!sScroll.parent('.slimScrollDiv').length)) { // If scrolling does not exist init it..
                        sScroll
                            .slimScroll({
                                height: sHeight,
                                color: '#fff',
                                size: '3px',
                                touchScrollStep: 100,
                                railVisible: false,
                                railOpacity: 1
                            });
                    }
                    else { // ..else resize scrolling height
                        sScroll
                            .add(sScroll.parent())
                            .css('height', sHeight);
                    }
                }
            }
            else if (mode == 'sidebar-alt-scroll') { // Init alternative sidebar scrolling
                if (sScrollAlt.length && (!sScrollAlt.parent('.slimScrollDiv').length)) { // If scrolling does not exist init it..
                    sScrollAlt.slimScroll({ height: sidebarAlt.outerHeight(), color: '#fff', size: '3px', touchScrollStep: 100, railVisible: false, railOpacity: 1 });

                    // Resize alternative sidebar scrolling height on window resize or orientation change
                    var sScrollAltTimeout;

                    $(window).on('resize orientationchange', function(){
                        clearTimeout(sScrollAltTimeout);

                        sScrollAltTimeout = setTimeout(function(){
                            handleSidebar('sidebar-alt-scroll');
                        }, 150);
                    });
                }
                else { // ..else resize scrolling height
                    sScrollAlt.add(sScrollAlt.parent()).css('height', sidebarAlt.outerHeight());
                }
            }
        }
    };

    /* Resize #page-content to fill empty space if exists */
    var resizePageContent = function() {
        var windowH     = $(window).height();
        var headerH     = header.outerHeight();
        var sidebarH    = sidebar.outerHeight();

        if (header.hasClass('navbar-fixed-top') || header.hasClass('navbar-fixed-bottom')) {
            pageContent.css('min-height', windowH);
        } else if (sidebarH > windowH) {
            pageContent.css('min-height', sidebarH - headerH);
        } else {
            pageContent.css('min-height', windowH - headerH);
        }
    };

    /* Color Theme preview, preview a color theme on a page */
    var colorThemePreview = function() {
        var colorList   = $('.sidebar-themes');
        var themeLink   = $('#theme-link');
        var themeColor  = 'default';
        var themeHeader = header.hasClass('navbar-inverse') ? 'navbar-inverse' : 'navbar-default';

        // If there is an active color theme save its url
        if (themeLink.length) {
            themeColor = themeLink.attr('href');
        }

        // Set the active color theme link as active
        $('a[data-theme="' + themeColor + '"][data-theme-navbar="' + themeHeader + '"]', colorList)
            .parent('li')
            .addClass('active');

        // When a color theme link is clicked
        $('a', colorList).click(function(e){
            themeColor  = $(this).data('theme');
            themeHeader = $(this).data('theme-navbar');

            // Set this color theme link as active
            $('li', colorList).removeClass('active');
            $(this).parent('li').addClass('active');

            // Update navbar class
            header.removeClass('navbar-inverse navbar-default').addClass(themeHeader);

            // Update color theme
            if (themeColor === 'default') {
                if (themeLink.length) {
                    themeLink.remove();
                    themeLink = $('#theme-link');
                }
            } else {
                if (themeLink.length) {
                    themeLink.attr('href', themeColor);
                } else {
                    $('link[href="css/themes.css"]')
                        .before('<link id="theme-link" rel="stylesheet" href="' + themeColor + '">');

                    themeLink = $('#theme-link');
                }
            }
        });
    };

    /* Datatables basic Bootstrap integration (pagination integration included under the Datatables plugin in plugins.js) */
    var dtIntegration = function() {
        $.extend(true, $.fn.dataTable.defaults, {
            "sDom": "<'row'<'col-sm-6 col-xs-5'l><'col-sm-6 col-xs-7'f>r>t<'row'<'col-sm-5 hidden-xs'i><'col-sm-7 col-xs-12 clearfix'p>>",
            "sPaginationType": "bootstrap",
            "oLanguage": {
                "sLengthMenu": "_MENU_",
                "sSearch": "<div class=\"input-group\">_INPUT_<span class=\"input-group-addon\"><i class=\"fa fa-search\"></i></span></div>",
                "sInfo": "<strong>_START_</strong>-<strong>_END_</strong> of <strong>_TOTAL_</strong>",
                "oPaginate": {
                    "sPrevious": "",
                    "sNext": ""
                }
            }
        });
        $.extend($.fn.dataTableExt.oStdClasses, {
            "sWrapper": "dataTables_wrapper form-inline",
            "sFilterInput": "form-control",
            "sLengthSelect": "form-control"
        });
    };

    return {
        init: function() {
            uiInit(); // Initialize UI
            pageLoading(); // Initialize Page Loading
        },
        sidebar: function(mode, extra) {
            handleSidebar(mode, extra); // Handle sidebars - access functionality from everywhere
        },
        datatables: function() {
            dtIntegration(); // Datatables Bootstrap integration
        },
        flashMessages: {
            add: function(message, type) {
                var types = {
                    'success': 'alert-success',
                    'info': 'alert-info',
                    'error': 'alert-danger'
                };

                if (types[type] === undefined)
                    type = 'error';

                var html = '<div class="alert alert-dismissable ' + types[type] + '">'
                    + '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'
                    + '<div>' + message + '</div>'
                    + '</div>';

                html = jQuery(html).hide();
                $('#flash-messages').append(html);
                html.fadeIn();

                // remove message after N ms
                setTimeout(function() { html.fadeOut('slow', function() { html.remove(); } ); }, 10000);
            }
        }
    };
}();

/* Initialize App when page loads */
$(function(){ App.init(); });
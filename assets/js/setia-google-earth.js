/**
 * SETIA Content Generator - Google Earth Style Enhancement
 * اسکریپت بهبود استایل Google Earth برای تمام صفحات افزونه
 */

jQuery(document).ready(function($) {
    'use strict';
    
    /**
     * افزودن کلاس Google Earth به المان‌های اصلی
     */
    function initGoogleEarthStyle() {
        // افزودن کلاس به کانتینرهای اصلی
        $('.setia-main-container, .setia-schema-wrap, .setia-history-wrap, .setia-settings-wrapper').addClass('setia-google-earth');
        
        // افزودن آیکون به هدرها اگر آیکون نداشته باشند
        $('.setia-header h1, .setia-settings-header h1, .setia-schema-header h1').each(function() {
            if ($(this).find('.dashicons').length === 0) {
                $(this).prepend('<span class="dashicons dashicons-admin-site"></span>');
            }
        });
        
        // اضافه کردن افکت‌های انیمیشن به کارت‌ها
        $('.setia-card, .setia-schema-card').addClass('setia-animated-card');
    }
    
    /**
     * افزودن افکت‌های انیمیشن به المان‌های مختلف
     */
    function addAnimationEffects() {
        // انیمیشن ورود برای کارت‌ها
        $('.setia-card, .setia-schema-card').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.1) + 's',
                'animation-name': 'fadeInUp',
                'animation-duration': '0.5s',
                'animation-fill-mode': 'both'
            });
        });
        
        // افکت hover برای دکمه‌ها
        $('.setia-btn').hover(
            function() {
                $(this).css('transform', 'translateY(-2px)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
            }
        );
    }
    
    /**
     * بهبود تب‌ها با افکت‌های Google Earth
     */
    function enhanceTabs() {
        // افزودن کلاس به تب‌ها
        $('.setia-tabs, .setia-settings-tabs').addClass('setia-google-earth-tabs');
        
        // افزودن افکت انتقال به تب‌ها
        $('.setia-tab-item').on('click', function() {
            const targetTab = $(this).data('tab');
            
            // فعال کردن تب جدید
            $('.setia-tab-item').removeClass('active');
            $(this).addClass('active');
            
            // نمایش محتوای تب با انیمیشن
            $('.setia-tab-content').removeClass('active').css('opacity', 0);
            $('#' + targetTab).addClass('active').animate({opacity: 1}, 300);
            
            // ذخیره تب فعال در localStorage
            localStorage.setItem('setia_active_tab', targetTab);
        });
        
        // بازیابی تب فعال از localStorage
        const activeTab = localStorage.getItem('setia_active_tab');
        if (activeTab) {
            $('.setia-tab-item[data-tab="' + activeTab + '"]').trigger('click');
        }
    }
    
    /**
     * افزودن افکت‌های پیشرفته به فرم‌ها
     */
    function enhanceForms() {
        // افکت فوکوس برای ورودی‌ها
        $('.setia-form-input, .setia-form-select, .setia-form-textarea').focus(function() {
            $(this).parent().addClass('input-focused');
        }).blur(function() {
            $(this).parent().removeClass('input-focused');
        });
        
        // افکت برای چک باکس‌ها
        $('.setia-form-checkbox input[type="checkbox"]').change(function() {
            if ($(this).is(':checked')) {
                $(this).parent().addClass('checkbox-checked');
            } else {
                $(this).parent().removeClass('checkbox-checked');
            }
        });
        
        // اعمال وضعیت اولیه چک باکس‌ها
        $('.setia-form-checkbox input[type="checkbox"]').each(function() {
            if ($(this).is(':checked')) {
                $(this).parent().addClass('checkbox-checked');
            }
        });
    }
    
    /**
     * افزودن افکت‌های اسکرول به صفحه
     */
    function enhanceScrolling() {
        // اسکرول نرم به بالای صفحه
        $('.setia-back-to-top').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, 800);
        });
        
        // نمایش/مخفی کردن دکمه بازگشت به بالا
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $('.setia-back-to-top').fadeIn(300);
            } else {
                $('.setia-back-to-top').fadeOut(300);
            }
        });
        
        // اضافه کردن دکمه بازگشت به بالا اگر وجود نداشته باشد
        if ($('.setia-back-to-top').length === 0) {
            $('body').append('<button class="setia-back-to-top" title="بازگشت به بالا"><span class="dashicons dashicons-arrow-up-alt2"></span></button>');
        }
    }
    
    /**
     * اجرای توابع بهبود استایل
     */
    function init() {
        initGoogleEarthStyle();
        addAnimationEffects();
        enhanceTabs();
        enhanceForms();
        enhanceScrolling();
        
        // افزودن کلاس به body برای استایل‌های سراسری
        $('body').addClass('setia-google-earth-theme');
        
        console.log('SETIA Google Earth Style initialized');
    }
    
    // اجرای اسکریپت
    init();
}); 
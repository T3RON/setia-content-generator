/**
 * SETIA Simple Settings JavaScript
 * 
 * اسکریپت ساده و کارآمد برای مدیریت تنظیمات
 * 
 * @package SETIA_Content_Generator
 * @version 1.0.0
 * @author SETIA Team
 */

(function($) {
    'use strict';

    // متغیرهای سراسری
    const SETIA_Settings = {
        form: null,
        messageContainer: null,
        loadingOverlay: null,
        
        // راه‌اندازی اولیه
        init: function() {
            this.form = $('#setia-simple-settings-form');
            this.messageContainer = $('#setia-message');
            this.loadingOverlay = $('#setia-loading-overlay');
            
            this.bindEvents();
            this.initValidation();
        },
        
        // اتصال رویدادها
        bindEvents: function() {
            // ذخیره تنظیمات
            this.form.on('submit', this.handleSaveSettings.bind(this));
            
            // تست API
            $('.setia-test-api').on('click', this.handleTestAPI.bind(this));
            
            // بازنشانی تنظیمات
            $('#setia-reset-settings').on('click', this.handleResetSettings.bind(this));
            
            // نمایش/مخفی کردن رمز عبور
            this.initPasswordToggle();
        },
        
        // اعتبارسنجی فرم
        initValidation: function() {
            // اعتبارسنجی کلید API Gemini
            $('#gemini_api_key').on('input', function() {
                const value = $(this).val();
                const isValid = value === '' || /^AIza[0-9A-Za-z\-_]{35,}$/.test(value);
                
                $(this).toggleClass('invalid', !isValid);
                
                if (!isValid && value !== '') {
                    SETIA_Settings.showFieldError($(this), 'فرمت کلید API Gemini نامعتبر است');
                } else {
                    SETIA_Settings.hideFieldError($(this));
                }
            });
            
            // اعتبارسنجی فیلدهای اجباری
            $('input[required], select[required]').on('blur', function() {
                const value = $(this).val();
                const isValid = value !== '';
                
                $(this).toggleClass('invalid', !isValid);
                
                if (!isValid) {
                    SETIA_Settings.showFieldError($(this), 'این فیلد اجباری است');
                } else {
                    SETIA_Settings.hideFieldError($(this));
                }
            });
        },
        
        // نمایش/مخفی کردن رمز عبور
        initPasswordToggle: function() {
            $('input[type="password"]').each(function() {
                const $input = $(this);
                const $wrapper = $('<div class="password-wrapper"></div>');
                const $toggle = $('<button type="button" class="password-toggle" title="نمایش/مخفی کردن رمز عبور"><span class="dashicons dashicons-visibility"></span></button>');
                
                $input.wrap($wrapper);
                $input.after($toggle);
                
                $toggle.on('click', function() {
                    const type = $input.attr('type') === 'password' ? 'text' : 'password';
                    $input.attr('type', type);
                    
                    const icon = type === 'password' ? 'dashicons-visibility' : 'dashicons-hidden';
                    $toggle.find('.dashicons').removeClass('dashicons-visibility dashicons-hidden').addClass(icon);
                });
            });
        },
        
        // ذخیره تنظیمات
        handleSaveSettings: function(e) {
            e.preventDefault();
            
            // بررسی اعتبارسنجی
            if (!this.validateForm()) {
                this.showMessage('لطفاً خطاهای فرم را برطرف کنید', 'error');
                return;
            }
            
            this.showLoading();
            
            const formData = new FormData(this.form[0]);
            formData.append('action', 'setia_save_simple_settings');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 30000,
                success: this.handleSaveSuccess.bind(this),
                error: this.handleSaveError.bind(this)
            });
        },
        
        // موفقیت در ذخیره
        handleSaveSuccess: function(response) {
            this.hideLoading();
            
            if (response.success) {
                this.showMessage(response.data, 'success');
                this.updateSystemInfo();
            } else {
                this.showMessage(response.data || 'خطا در ذخیره تنظیمات', 'error');
            }
        },
        
        // خطا در ذخیره
        handleSaveError: function(xhr, status, error) {
            this.hideLoading();
            
            let message = 'خطا در ارتباط با سرور';
            
            if (status === 'timeout') {
                message = 'زمان انتظار تمام شد. لطفاً دوباره تلاش کنید';
            } else if (xhr.responseJSON && xhr.responseJSON.data) {
                message = xhr.responseJSON.data;
            }
            
            this.showMessage(message, 'error');
        },
        
        // تست API
        handleTestAPI: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const apiType = $button.data('api-type');
            const inputId = $button.data('input-id');
            const apiKey = $('#' + inputId).val();
            
            if (!apiKey) {
                this.showMessage('لطفاً ابتدا کلید API را وارد کنید', 'warning');
                $('#' + inputId).focus();
                return;
            }
            
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'setia_test_simple_api',
                    api_type: apiType,
                    api_key: apiKey,
                    nonce: $('#setia_nonce').val()
                },
                timeout: 30000,
                success: function(response) {
                    SETIA_Settings.setButtonLoading($button, false);
                    
                    if (response.success) {
                        SETIA_Settings.showMessage(response.data, 'success');
                    } else {
                        SETIA_Settings.showMessage(response.data || 'خطا در تست API', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    SETIA_Settings.setButtonLoading($button, false);
                    
                    let message = 'خطا در تست API';
                    
                    if (status === 'timeout') {
                        message = 'زمان انتظار تمام شد';
                    }
                    
                    SETIA_Settings.showMessage(message, 'error');
                }
            });
        },
        
        // بازنشانی تنظیمات
        handleResetSettings: function(e) {
            e.preventDefault();
            
            if (!confirm('آیا مطمئن هستید که می‌خواهید تمام تنظیمات را بازنشانی کنید؟\nاین عمل قابل بازگشت نیست.')) {
                return;
            }
            
            this.showLoading();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'setia_reset_simple_settings',
                    nonce: $('#setia_nonce').val()
                },
                timeout: 30000,
                success: function(response) {
                    SETIA_Settings.hideLoading();
                    
                    if (response.success) {
                        SETIA_Settings.showMessage(response.data, 'success');
                        // بازنشانی فرم
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        SETIA_Settings.showMessage(response.data || 'خطا در بازنشانی تنظیمات', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    SETIA_Settings.hideLoading();
                    SETIA_Settings.showMessage('خطا در بازنشانی تنظیمات', 'error');
                }
            });
        },
        
        // اعتبارسنجی فرم
        validateForm: function() {
            let isValid = true;
            
            // بررسی فیلدهای اجباری
            this.form.find('input[required], select[required]').each(function() {
                const $field = $(this);
                const value = $field.val();
                
                if (!value) {
                    SETIA_Settings.showFieldError($field, 'این فیلد اجباری است');
                    isValid = false;
                } else {
                    SETIA_Settings.hideFieldError($field);
                }
            });
            
            // بررسی فرمت کلید API Gemini
            const geminiKey = $('#gemini_api_key').val();
            if (geminiKey && !/^AIza[0-9A-Za-z\-_]{35,}$/.test(geminiKey)) {
                this.showFieldError($('#gemini_api_key'), 'فرمت کلید API Gemini نامعتبر است');
                isValid = false;
            }
            
            return isValid;
        },
        
        // نمایش خطای فیلد
        showFieldError: function($field, message) {
            this.hideFieldError($field);
            
            const $error = $('<div class="field-error">' + message + '</div>');
            $field.addClass('invalid').after($error);
        },
        
        // مخفی کردن خطای فیلد
        hideFieldError: function($field) {
            $field.removeClass('invalid').next('.field-error').remove();
        },
        
        // نمایش پیام
        showMessage: function(message, type) {
            this.messageContainer
                .removeClass('notice-success notice-error notice-warning notice-info')
                .addClass('notice-' + type)
                .find('p').html(message);
            
            this.messageContainer.slideDown();
            
            // مخفی کردن خودکار پیام‌های موفقیت
            if (type === 'success') {
                setTimeout(() => {
                    this.hideMessage();
                }, 5000);
            }
            
            // اسکرول به بالا
            $('html, body').animate({
                scrollTop: this.messageContainer.offset().top - 50
            }, 500);
        },
        
        // مخفی کردن پیام
        hideMessage: function() {
            this.messageContainer.slideUp();
        },
        
        // نمایش لودینگ
        showLoading: function() {
            this.loadingOverlay.fadeIn();
            $('body').addClass('setia-loading');
        },
        
        // مخفی کردن لودینگ
        hideLoading: function() {
            this.loadingOverlay.fadeOut();
            $('body').removeClass('setia-loading');
        },
        
        // تنظیم حالت لودینگ دکمه
        setButtonLoading: function($button, loading) {
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
                $button.data('original-text', $button.text()).text('در حال پردازش...');
            } else {
                $button.removeClass('loading').prop('disabled', false);
                if ($button.data('original-text')) {
                    $button.text($button.data('original-text'));
                }
            }
        },
        
        // بروزرسانی اطلاعات سیستم
        updateSystemInfo: function() {
            const now = new Date();
            const persianDate = now.toLocaleDateString('fa-IR') + ' ' + now.toLocaleTimeString('fa-IR');
            
            $('.setia-system-info tr').each(function() {
                const $row = $(this);
                const $firstCell = $row.find('td:first');
                
                if ($firstCell.text().includes('آخرین بروزرسانی')) {
                    $row.find('td:last').text(persianDate);
                }
            });
        }
    };

    // راه‌اندازی پس از بارگذاری DOM
    $(document).ready(function() {
        SETIA_Settings.init();
    });

    // کلیدهای میانبر
    $(document).on('keydown', function(e) {
        // Ctrl+S برای ذخیره
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            SETIA_Settings.form.trigger('submit');
        }
        
        // Escape برای بستن پیام‌ها
        if (e.key === 'Escape') {
            SETIA_Settings.hideMessage();
        }
    });

    // تابع سراسری برای دسترسی خارجی
    window.SETIA_Settings = SETIA_Settings;

})(jQuery);
/**
 * فایل جاوااسکریپت افزونه SETIA
 * نسخه 1.1.0 - بهبود یافته
 */

jQuery(document).ready(function($) {
    'use strict';

    // بررسی وجود متغیرهای مورد نیاز
    if (typeof setiaParams === 'undefined') {
        console.warn('SETIA: متغیرهای JavaScript بارگذاری نشده‌اند');
        return;
    }

    // مدیریت تب‌ها با بررسی خطا
    $('.setia-tab').on('click', function(e) {
        try {
            e.preventDefault();
            $('.setia-tab').removeClass('active');
            $(this).addClass('active');
        } catch (error) {
            console.error('SETIA: خطا در تغییر تب:', error);
        }
    });

    // دکمه‌های تولید محتوا با مدیریت بهتر خطا
    $('.setia-suggest-button, .setia-generate-button, .setia-generate-content-button, .setia-generate-summary-button, .setia-generate-meta-button').on('click', function(e) {
        e.preventDefault();

        try {
            var button = $(this);
            var originalText = button.text();

            if (button.prop('disabled')) {
                return false;
            }

            button.text('در حال پردازش...');
            button.prop('disabled', true);

            setTimeout(function() {
                button.text(originalText);
                button.prop('disabled', false);
            }, 1500);
        } catch (error) {
            console.error('SETIA: خطا در پردازش دکمه:', error);
        }
    });
    
    // Enhanced test image generation با مدیریت بهتر خطا
    $('#generate_test_image').on('click', function(e) {
        e.preventDefault();

        try {
            var $button = $(this);
            var prompt = $('#test_prompt').val();
            var style = $('#test_image_style').val() || 'realistic';
            var aspectRatio = $('#test_aspect_ratio').val() || '1:1';
            var imagine_art_api_key = $('#imagine_art_api_key').val();

            // اعتبارسنجی ورودی
            if (!prompt || !prompt.trim()) {
                showNotification('لطفا موضوعی برای تولید تصویر وارد کنید', 'error');
                return false;
            }

            if (prompt.length > 500) {
                showNotification('متن پرامپت نباید بیش از 500 کاراکتر باشد', 'error');
                return false;
            }

            // جلوگیری از کلیک مجدد
            if ($button.prop('disabled')) {
                return false;
            }

            $('#test_image_result').show();
            $('#test_image_loading').show();
            $('#test_image_preview').empty();
            $button.prop('disabled', true).text('در حال تولید...');

            // ارسال درخواست به سرور
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                timeout: 60000,
                data: {
                    action: 'setia_generate_test_image',
                    nonce: $('#test_nonce').val(),
                    prompt: prompt.trim(),
                    image_style: style,
                    aspect_ratio: aspectRatio,
                    imagine_art_api_key: imagine_art_api_key
                },
                success: function(response) {
                    $('#test_image_loading').hide();
                    $button.prop('disabled', false).html('<span class="button-icon">🎨</span> تولید تصویر تست');

                    if (response && response.success) {
                        $('#test_image_preview').html(
                            '<div class="test-image-success">' +
                            '<h4>تصویر با موفقیت تولید شد:</h4>' +
                            '<img src="' + response.data.image_url + '" alt="تصویر تولید شده" class="generated-image" loading="lazy">' +
                            '</div>'
                        );
                        showNotification('تصویر با موفقیت تولید شد', 'success');
                    } else {
                        var errorMessage = 'خطای نامشخص';
                        if (response && response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        $('#test_image_preview').html(
                            '<div class="test-image-error">' +
                            '<p>خطا در تولید تصویر: ' + errorMessage + '</p>' +
                            '</div>'
                        );
                        showNotification('خطا در تولید تصویر: ' + errorMessage, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $('#test_image_loading').hide();
                    $button.prop('disabled', false).html('<span class="button-icon">🎨</span> تولید تصویر تست');

                    var errorMessage = 'خطا در ارتباط با سرور';
                    if (status === 'timeout') {
                        errorMessage = 'زمان انتظار تمام شد. لطفاً دوباره تلاش کنید';
                    } else if (xhr.responseText) {
                        try {
                            var errorData = JSON.parse(xhr.responseText);
                            if (errorData.data && errorData.data.message) {
                                errorMessage = errorData.data.message;
                            }
                        } catch (e) {
                            // اگر پاسخ JSON نبود، همان پیام پیش‌فرض را نگه دار
                        }
                    }

                    $('#test_image_preview').html(
                        '<div class="test-image-error">' +
                        '<p>خطا در تولید تصویر: ' + errorMessage + '</p>' +
                        '</div>'
                    );
                    showNotification(errorMessage, 'error');
                    console.error('SETIA AJAX Error:', {xhr: xhr, status: status, error: error});
                }
            });
        } catch (error) {
            console.error('SETIA: خطا در تولید تصویر تست:', error);
            showNotification('خطای غیرمنتظره رخ داد', 'error');
        }
    });

    // Notification function - بهبود یافته
    function showNotification(message, type, duration) {
        try {
            // پیش‌فرض‌ها
            type = type || 'info';
            duration = duration || 4000;

            // حذف اعلان‌های قبلی از همان نوع
            $('.setia-notification-' + type).remove();

            // ایجاد اعلان جدید
            var $notification = $('<div class="setia-notification setia-notification-' + type + '">' +
                '<span class="notification-message">' + message + '</span>' +
                '<button class="notification-close" type="button">&times;</button>' +
                '</div>');

            // اضافه کردن به صفحه
            $('body').append($notification);

            // انیمیشن ورود
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);

            // دکمه بستن
            $notification.find('.notification-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            });

            // حذف خودکار
            setTimeout(function() {
                if ($notification.hasClass('show')) {
                    $notification.removeClass('show');
                    setTimeout(function() {
                        $notification.remove();
                    }, 300);
                }
            }, duration);

        } catch (error) {
            console.error('SETIA: خطا در نمایش اعلان:', error);
            // fallback به alert در صورت خطا
            alert(message);
        }
    }
    
    // جلوگیری از کش شدن
    $('head').append('<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />');
    $('head').append('<meta http-equiv="Pragma" content="no-cache" />');
    $('head').append('<meta http-equiv="Expires" content="0" />');
    
    // اضافه کردن کلاس‌های اضافی برای استایل
    $('.setia-section').each(function() {
        $(this).find('.form-table').addClass('setia-form-table');
        $(this).find('input[type="text"]').addClass('setia-input');
        $(this).find('select').addClass('setia-select');
    });

    // Enhanced settings page functionality
    initializeEnhancedSettings();

    function initializeEnhancedSettings() {
        // Fallback help toggle functionality (if settings-enhanced.js doesn't load)
        if (!window.setiaEnhancedLoaded) {
            $('.setia-help-toggle').off('click.fallback').on('click.fallback', function(e) {
                e.preventDefault();
                var $toggle = $(this);
                var $steps = $toggle.siblings('.setia-help-steps');

                // Close other help sections
                $('.setia-help-toggle').not($toggle).removeClass('active');
                $('.setia-help-steps').not($steps).slideUp(300);

                // Toggle current section
                if ($toggle.hasClass('active')) {
                    $toggle.removeClass('active');
                    $steps.slideUp(300);
                } else {
                    $toggle.addClass('active');
                    $steps.slideDown(300);
                }
            });
        }

        // API key validation
        $('#gemini_api_key, #imagine_art_api_key').on('input', function() {
            validateApiKey($(this));
        });

        // Status indicators update
        updateStatusIndicators();

        // Update status indicators when API keys change
        $('#gemini_api_key, #imagine_art_api_key').on('input', function() {
            setTimeout(updateStatusIndicators, 100);
        });
    }

    function validateApiKey($input) {
        var value = $input.val();
        var $status = $input.siblings('.input-status');

        if (value.length === 0) {
            $status.removeClass('valid invalid').addClass('empty');
            return;
        }

        var isValid = false;
        if ($input.attr('id') === 'gemini_api_key') {
            isValid = value.startsWith('AIza') && value.length > 20;
        } else if ($input.attr('id') === 'imagine_art_api_key') {
            isValid = value.startsWith('sk-') && value.length > 20;
        }

        $status.removeClass('valid invalid empty').addClass(isValid ? 'valid' : 'invalid');
    }

    function updateStatusIndicators() {
        var geminiKey = $('#gemini_api_key').val();
        var imagineKey = $('#imagine_art_api_key').val();

        $('#gemini-status .status-dot').removeClass('active inactive').addClass(geminiKey ? 'active' : 'inactive');
        $('#imagine-status .status-dot').removeClass('active inactive').addClass(imagineKey ? 'active' : 'inactive');
    }

    // نمایش/مخفی کردن تنظیمات تصویر
    $('#setia-image').on('change', function() {
        if ($(this).is(':checked')) {
            $('#setia-image-options-container').slideDown();
        } else {
            $('#setia-image-options-container').slideUp();
        }
    });

    // تنظیم وضعیت اولیه تنظیمات تصویر
    if ($('#setia-image').is(':checked')) {
        $('#setia-image-options-container').show();
    }

    // مدیریت ارسال فرم تولید محتوا - حذف شده و به main-page-enhanced.js منتقل شده
    // این بخش دیگر استفاده نمی‌شود چون main-page-enhanced.js مسئول form handling است

    // مدیریت دکمه انتشار پست
    $('#setia-publish-btn').on('click', function() {
        var contentId = $('#setia-content-form').data('content-id');
        
        if (!contentId) {
            alert('خطا: شناسه محتوا یافت نشد.');
            return;
        }
        
        $(this).prop('disabled', true).text('در حال انتشار...');
        
        $.ajax({
            url: setiaParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_publish_content',
                nonce: setiaParams.nonce,
                content_id: contentId,
                status: 'publish'
            },
            success: function(response) {
                $('#setia-publish-btn').prop('disabled', false).text('انتشار پست');
                
                if (response.success) {
                    window.location.href = response.data.edit_url;
                } else {
                    alert('خطا در انتشار محتوا: ' + (response.data ? response.data.message : 'خطای نامشخص'));
                }
            },
            error: function() {
                $('#setia-publish-btn').prop('disabled', false).text('انتشار پست');
                alert('خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
            }
        });
    });

    // مدیریت دکمه ذخیره پیش‌نویس
    $('#setia-draft-btn').on('click', function() {
        var contentId = $('#setia-content-form').data('content-id');
        
        if (!contentId) {
            alert('خطا: شناسه محتوا یافت نشد.');
            return;
        }
        
        $(this).prop('disabled', true).text('در حال ذخیره...');
        
        $.ajax({
            url: setiaParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_publish_content',
                nonce: setiaParams.nonce,
                content_id: contentId,
                status: 'draft'
            },
            success: function(response) {
                $('#setia-draft-btn').prop('disabled', false).text('ذخیره پیش‌نویس');
                
                if (response.success) {
                    window.location.href = response.data.edit_url;
                } else {
                    alert('خطا در ذخیره محتوا: ' + (response.data ? response.data.message : 'خطای نامشخص'));
                }
            },
            error: function() {
                $('#setia-draft-btn').prop('disabled', false).text('ذخیره پیش‌نویس');
                alert('خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
            }
        });
    });

    // مدیریت دکمه کپی محتوا
    $('#setia-copy-btn').on('click', function() {
        var content = $('#setia-content-preview').html();
        
        // ایجاد یک المان موقت برای کپی
        var tempElement = $('<div>').html(content).appendTo('body').css('position', 'absolute').css('left', '-9999px');
        
        // انتخاب متن
        var range = document.createRange();
        range.selectNodeContents(tempElement[0]);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        
        // کپی
        try {
            document.execCommand('copy');
            alert('محتوا با موفقیت کپی شد.');
        } catch (err) {
            alert('خطا در کپی محتوا: ' + err);
        }
        
        // حذف المان موقت
        tempElement.remove();
    });

    // مدیریت دکمه تولید مجدد
    $('#setia-regenerate-btn').on('click', function() {
        if (confirm('آیا از تولید مجدد محتوا اطمینان دارید؟')) {
            $('#setia-content-form').submit();
        }
    });

    // مدیریت دکمه تست اتصال
    $('#setia-debug-btn').on('click', function() {
        console.log("SETIA DEBUG: Debug button clicked");

        // تست ساده AJAX
        $.ajax({
            url: setiaParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_test_form_data',
                nonce: setiaParams.nonce,
                form_data: 'test=1&topic=تست&keywords=تست'
            },
            success: function(response) {
                console.log("SETIA DEBUG: Simple test response:", response);
                alert('تست اتصال موفق!\nسرور پاسخ داد: ' + JSON.stringify(response));
            },
            error: function(xhr, status, error) {
                console.error('SETIA ERROR: Simple test failed:', error);
                alert('تست اتصال ناموفق: ' + error);
            }
        });
    });

    // مدیریت دکمه تست فرم
    $('#setia-test-btn').on('click', function() {
        console.log("SETIA DEBUG: Test button clicked");

        // جمع‌آوری داده‌های فرم
        var formData = $('#setia-content-form').serialize();
        console.log("SETIA DEBUG: Test form data:", formData);

        // ارسال درخواست تست
        $.ajax({
            url: setiaParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_test_form_data',
                nonce: setiaParams.nonce,
                form_data: formData
            },
            success: function(response) {
                console.log("SETIA DEBUG: Test response:", response);
                if (response.success) {
                    alert('تست موفق!\nموضوع: ' + response.data.topic + '\nکلمات کلیدی: ' + response.data.keywords);
                } else {
                    alert('خطا در تست: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('SETIA ERROR: Test AJAX error:', error);
                alert('خطا در ارسال درخواست تست');
            }
        });
    });
});

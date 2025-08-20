/**
 * SETIA Content Generator - Main Page JavaScript
 * اسکریپت صفحه اصلی افزونه با استایل مدرن
 */

jQuery(document).ready(function($) {
    'use strict';

    // تنظیم سیستم تب‌ها
    $('.setia-tab-item').on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).data('tab');
        
        // فعال کردن تب انتخاب شده
        $('.setia-tab-item').removeClass('active');
        $(this).addClass('active');
        
        // نمایش محتوای تب انتخاب شده
        $('.setia-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });

    // انتخاب اولین تب به صورت پیش‌فرض
    $('.setia-tab-item:first').trigger('click');

    // نمایش/مخفی‌سازی بخش‌های پیشرفته
    $('.setia-toggle-advanced').on('click', function(e) {
        e.preventDefault();
        const target = $(this).data('target');
        $('#' + target).slideToggle(300);
        
        // تغییر آیکون و متن دکمه
        $(this).find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
        const text = $(this).find('.toggle-text');
        text.text(text.text() === 'نمایش تنظیمات پیشرفته' ? 'مخفی کردن تنظیمات پیشرفته' : 'نمایش تنظیمات پیشرفته');
    });

    // مدیریت مدال‌ها
    $('.setia-modal-open').on('click', function(e) {
        e.preventDefault();
        const modalId = $(this).data('modal');
        $('#' + modalId).addClass('active');
    });

    $('.setia-modal-close, .setia-modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $('.setia-modal-overlay').removeClass('active');
        }
    });

    // کنترل نمایش فیلدها بر اساس انتخاب نوع محتوا
    $('#content_type').on('change', function() {
        const contentType = $(this).val();
        
        // مخفی کردن همه فیلدهای وابسته به نوع محتوا
        $('.content-type-dependent').hide();
        
        // نمایش فیلدهای مرتبط با نوع محتوای انتخاب شده
        $('.content-type-' + contentType).show();
        
        // تنظیم ویژگی required برای فیلد keywords بر اساس نوع محتوا
        if (contentType === 'article' || contentType === 'blog') {
            $('#keywords').attr('required', true);
        } else {
            $('#keywords').attr('required', false);
        }
    }).trigger('change');

    // مدیریت فرم تولید محتوا
    $('#setia-generate-form').on('submit', function(e) {
        e.preventDefault();
        
        // نمایش نشانگر بارگذاری
        $('.setia-generate-btn').prop('disabled', true).addClass('loading');
        $('.setia-generate-btn .setia-btn-text').text('در حال تولید محتوا...');
        $('.setia-generate-btn').prepend('<span class="setia-spinner"></span>');
        
        // جمع‌آوری داده‌های فرم
        const formData = new FormData(this);
        
        // اطمینان از وجود نانس در داده‌ها
        // اولویت با نانس فرم است، سپس از متغیر جاوااسکریپت استفاده می‌شود
        if (!formData.has('setia_nonce') && typeof setiaAjax !== 'undefined' && setiaAjax.nonce) {
            formData.append('nonce', setiaAjax.nonce);
        }
        
        // استفاده از کلاس بهینه‌سازی شده AJAX اگر موجود باشد
        if (typeof window.SetiaAjax !== 'undefined') {
            // تبدیل FormData به آبجکت معمولی برای SetiaAjax
            const formDataObj = {};
            for (let [key, value] of formData.entries()) {
                console.log(`Form field: ${key} = ${value}`); // اضافه کردن لاگ برای دیباگ
                formDataObj[key] = value;
            }
            
            // اطمینان از وجود فیلدهای ضروری
            if (!formDataObj.prompt || formDataObj.prompt.trim() === '') {
                showAlert('danger', 'لطفاً موضوع یا پرامپت را وارد کنید.');
                handleError();
                return;
            }
            
            // در تب محتوا، کلمات کلیدی نیز الزامی است
            if ($('#content_type').val() === 'article' || $('#content_type').val() === 'blog') {
                if (!formDataObj.keywords || formDataObj.keywords.trim() === '') {
                    showAlert('danger', 'لطفاً کلمات کلیدی را وارد کنید.');
                    handleError();
                    return;
                }
            }
            
            formDataObj.action = 'setia_generate_content';
            
            window.SetiaAjax.request('setia_generate_content', formDataObj)
                .then(response => {
                    handleResponse(response);
                })
                .catch(error => {
                    handleError(error);
                });
        } else {
            // روش قدیمی با jQuery AJAX
        formData.append('action', 'setia_generate_content');
        formData.append('nonce', setiaAjax.nonce);
        
        // ارسال درخواست به سرور
        $.ajax({
            url: setiaAjax.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                    handleResponse(response);
                },
                error: function(xhr) {
                    handleError(xhr);
                }
            });
        }
        
        // تابع مدیریت پاسخ موفق
        function handleResponse(response) {
            // حذف نشانگر بارگذاری
            $('.setia-generate-btn').prop('disabled', false).removeClass('loading');
            $('.setia-generate-btn .setia-spinner').remove();
            $('.setia-generate-btn .setia-btn-text').text('تولید محتوا');
            
            if (response.success) {
                console.log("SETIA DEBUG: Response data keys:", Object.keys(response.data));
                
                // ذخیره داده‌ها در متغیر جهانی برای استفاده در سایر بخش‌ها
                window.setiaGeneratedContent = response.data;
                
                // نمایش محتوای تولید شده
                $('#content_preview').html(response.data.content);
                
                // اگر تصویر وجود داشت، نمایش آن
                if (response.data.image_url) {
                    console.log("SETIA DEBUG: Image URL found:", response.data.image_url);
                    // اضافه کردن تصویر به پیش‌نمایش یا نمایش در جای مناسب
                    const imageHtml = `<div class="setia-generated-image"><img src="${response.data.image_url}" alt="${response.data.title || 'تصویر تولید شده'}" /></div>`;
                    
                    // اضافه کردن تصویر به بالای محتوا
                    $('#content_preview').prepend(imageHtml);
                }
                
                // نمایش اعلان موفقیت
                showAlert('success', 'محتوا با موفقیت تولید شد.');
                
                // فعال کردن دکمه‌های عملیات روی محتوا
                $('.setia-content-action').prop('disabled', false);
            } else {
                console.error("SETIA DEBUG: Error response:", response);
                // نمایش خطا
                showAlert('danger', response.data.message || 'خطا در تولید محتوا.');
            }
        }
        
        // تابع مدیریت خطا
        function handleError(xhr) {
                // حذف نشانگر بارگذاری
                $('.setia-generate-btn').prop('disabled', false).removeClass('loading');
                $('.setia-generate-btn .setia-spinner').remove();
                $('.setia-generate-btn .setia-btn-text').text('تولید محتوا');
                
                // نمایش خطا
                showAlert('danger', 'خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
            }
    });

    // عملیات روی محتوای تولید شده
    $('.setia-content-action').on('click', function(e) {
        e.preventDefault();
        
        const action = $(this).data('action');
        
        // بررسی وجود محتوای تولید شده
        if (!window.setiaGeneratedContent) {
            showAlert('warning', 'ابتدا محتوایی تولید کنید.');
            return;
        }
        
        // دسترسی به محتوای HTML از صفحه
        const content = $('#content_preview').html();
        
        switch (action) {
            case 'copy':
                copyToClipboard(content);
                showAlert('success', 'محتوا در کلیپ‌بورد کپی شد.');
                break;
                
            case 'save':
                saveContent(content);
                break;
                
            case 'create_post':
                createPost(content);
                break;
        }
    });

    // کپی کردن محتوا در کلیپ‌بورد
    function copyToClipboard(html) {
        const tempElement = document.createElement('div');
        tempElement.innerHTML = html;
        const text = tempElement.textContent || tempElement.innerText || '';
        
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }

    // ذخیره محتوا در تاریخچه
    function saveContent(content) {
        const generatedData = window.setiaGeneratedContent || {};
        const title = generatedData.title || generatedData.optimized_title || $('#prompt').val() || 'محتوای بدون عنوان';
        
        const data = {
            action: 'setia_save_content',
            nonce: setiaAjax.nonce,
            title: title,
            content: content,
            prompt: $('#prompt').val(),
            content_type: $('#content_type').val(),
            keywords: generatedData.keywords || $('#keywords').val(),
            image_url: generatedData.image_url || ''
        };
        
        console.log('SETIA DEBUG: Saving content with data:', data);
        
        // استفاده از کلاس بهینه‌سازی شده AJAX اگر موجود باشد
        if (typeof window.SetiaAjax !== 'undefined') {
            window.SetiaAjax.request('setia_save_content', data)
                .then(response => {
                    if (response.success) {
                        showAlert('success', 'محتوا با موفقیت در تاریخچه ذخیره شد.');
                    } else {
                        showAlert('danger', response.data.message || 'خطا در ذخیره محتوا.');
                    }
                })
                .catch(() => {
                    showAlert('danger', 'خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
                });
        } else {
            // روش قدیمی با jQuery AJAX
            $.ajax({
                url: setiaAjax.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'محتوا با موفقیت در تاریخچه ذخیره شد.');
                    } else {
                        showAlert('danger', response.data.message || 'خطا در ذخیره محتوا.');
                    }
                },
                error: function() {
                    showAlert('danger', 'خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
                }
            });
        }
    }

    // ایجاد نوشته جدید با محتوای تولید شده
    function createPost(content) {
        const generatedData = window.setiaGeneratedContent || {};
        const title = generatedData.title || generatedData.optimized_title || $('#prompt').val() || 'محتوای بدون عنوان';
        
        const data = {
            action: 'setia_create_post',
            nonce: setiaAjax.nonce,
            title: title,
            content: content,
            category: $('#post_category').val() || 0,
            keywords: generatedData.keywords || $('#keywords').val(),
            image_url: generatedData.image_url || '',
            seo_meta: generatedData.seo_meta || {}
        };
        
        console.log('SETIA DEBUG: Creating post with data:', data);
        
        // استفاده از کلاس بهینه‌سازی شده AJAX اگر موجود باشد
        if (typeof window.SetiaAjax !== 'undefined') {
            window.SetiaAjax.request('setia_create_post', data)
                .then(response => {
                    if (response.success) {
                        showAlert('success', 'نوشته جدید با موفقیت ایجاد شد.');
                        
                        // هدایت به صفحه ویرایش نوشته
                        if (response.data.edit_url) {
                            setTimeout(function() {
                                window.location.href = response.data.edit_url;
                            }, 1500);
                        }
                    } else {
                        showAlert('danger', response.data.message || 'خطا در ایجاد نوشته.');
                    }
                })
                .catch(() => {
                    showAlert('danger', 'خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
                });
        } else {
            // روش قدیمی با jQuery AJAX
            $.ajax({
                url: setiaAjax.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'نوشته جدید با موفقیت ایجاد شد.');
                        
                        // هدایت به صفحه ویرایش نوشته
                        if (response.data.edit_url) {
                            setTimeout(function() {
                                window.location.href = response.data.edit_url;
                            }, 1500);
                        }
                    } else {
                        showAlert('danger', response.data.message || 'خطا در ایجاد نوشته.');
                    }
                },
                error: function() {
                    showAlert('danger', 'خطا در ارتباط با سرور. لطفاً مجدداً تلاش کنید.');
                }
            });
        }
    }

    // نمایش اعلان‌ها
    function showAlert(type, message) {
        // حذف اعلان‌های قبلی
        $('.setia-alert').remove();
        
        // ایجاد اعلان جدید
        const alert = $('<div class="setia-alert setia-alert-' + type + '">' + message + '</div>');
        $('#setia-alerts').append(alert);
        
        // حذف اعلان پس از چند ثانیه
        setTimeout(function() {
            alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
}); 
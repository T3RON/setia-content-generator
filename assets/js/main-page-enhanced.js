/**
 * Enhanced Main Page JavaScript for SETIA Content Generator
 */

jQuery(document).ready(function($) {
    'use strict';

    // علامت‌گذاری که این فایل لود شده
    window.setiaMainPageEnhanced = true;

    // متغیرهای سراسری برای ردیابی محتوای تولید شده
    window.lastGeneratedContentId = null;
    window.lastFormData = null;

    // Initialize enhanced main page functionality
    initializeMainPage();
    
    function initializeMainPage() {
        // Main tabs functionality
        initializeMainTabs();

        // Form validation and enhancement
        initializeFormValidation();

        // Toggle functionality
        initializeToggles();

        // Tab functionality
        initializeTabs();

        // Content generation
        initializeContentGeneration();

        // Product generation
        initializeProductGeneration();

        // Character counters
        initializeCounters();

        // Progress indicator
        initializeProgressIndicator();

        // Result actions
        initializeResultActions();
    }
    
    function initializeFormValidation() {
        // Real-time validation for topic
        $('#setia-topic').on('input', function() {
            validateField($(this), {
                required: true,
                minLength: 5,
                maxLength: 100
            });
        });
        
        // Real-time validation for keywords
        $('#setia-keywords').on('input', function() {
            validateField($(this), {
                required: true,
                minLength: 3
            });
            updateKeywordsCounter();
        });
        
        // Form submission validation
        $('#setia-content-form').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                showNotification('لطفا خطاهای فرم را برطرف کنید', 'error');
            }
        });
    }
    
    function validateField($field, rules) {
        const value = $field.val().trim();
        const $validation = $field.siblings('.input-validation');
        let isValid = true;
        
        if (rules.required && !value) {
            isValid = false;
        }
        
        if (rules.minLength && value.length < rules.minLength) {
            isValid = false;
        }
        
        if (rules.maxLength && value.length > rules.maxLength) {
            isValid = false;
        }
        
        $validation.removeClass('valid invalid').addClass(isValid ? 'valid' : 'invalid');
        return isValid;
    }
    
    function validateForm() {
        let isValid = true;
        
        // Validate topic
        if (!validateField($('#setia-topic'), { required: true, minLength: 5, maxLength: 100 })) {
            isValid = false;
        }
        
        // Validate keywords
        if (!validateField($('#setia-keywords'), { required: true, minLength: 3 })) {
            isValid = false;
        }
        
        return isValid;
    }
    
    function initializeToggles() {
        // Image generation toggle
        $('#setia-image').on('change', function() {
            const $container = $('#setia-image-options-container');
            if ($(this).is(':checked')) {
                $container.slideDown(300);
            } else {
                $container.slideUp(300);
            }
        });
        
        // Initialize toggle states
        if ($('#setia-image').is(':checked')) {
            $('#setia-image-options-container').show();
        }
    }
    
    function initializeTabs() {
        $('.setia-tab-button').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Update tab buttons
            $('.setia-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Update tab content
            $('.setia-tab-content').removeClass('active');
            $('#' + tabId + '-tab').addClass('active');
        });
    }
    
    function initializeContentGeneration() {
        $('#setia-content-form').on('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            generateContent();
        });
    }
    
    function generateContent() {
        const $button = $('#setia-generate-btn');
        const $buttonText = $button.find('.button-text');
        const $buttonLoader = $button.find('.button-loader');
        
        // Update UI for loading state
        $button.prop('disabled', true).addClass('loading');
        $buttonText.text('در حال تولید...');
        $buttonLoader.show();
        
        // Update progress indicator
        updateProgressStep(2);
        
        // Collect form data
        const formData = {
            action: 'setia_generate_content',
            nonce: setiaParams.nonce,
            topic: $('#setia-topic').val(),
            keywords: $('#setia-keywords').val(),
            tone: $('#setia-tone').val(),
            category: $('#setia-category').val(),
            length: $('#setia-length').val(),
            seo: $('#setia-seo').is(':checked') ? 'yes' : 'no',
            generate_image: $('#setia-image').is(':checked') ? 'yes' : 'no',
            image_style: $('#setia-image-style').val(),
            aspect_ratio: $('#setia-aspect-ratio').val(),
            negative_prompt: $('#setia-negative-prompt').val(),
            image_prompt_details: $('#image_prompt_details').val(),
            instructions: $('#setia-instructions').val()
        };

        // ذخیره داده‌های فرم برای استفاده در تولید مجدد
        window.lastFormData = formData;
        
        // Show generation progress
        showGenerationProgress();
        
        // Send AJAX request using optimized system
        if (typeof window.SetiaAjax !== 'undefined') {
            // استفاده از سیستم AJAX بهینه‌سازی شده
            window.SetiaAjax.generateContent(formData, {
                ajaxOptions: {
                    timeout: 120000 // 2 minutes timeout
                }
            }).then(function(response) {
                handleGenerationSuccess(response);
            }).catch(function(error) {
                handleGenerationError(error.message || error);
            }).finally(function() {
                // Reset button state
                $button.prop('disabled', false).removeClass('loading');
                $buttonText.text('تولید محتوا');
                $buttonLoader.hide();

                hideGenerationProgress();
            });
        } else {
            // Fallback to traditional AJAX
            $.ajax({
                url: setiaParams.ajaxUrl,
                type: 'POST',
                data: formData,
                timeout: 120000, // 2 minutes timeout
                success: function(response) {
                    handleGenerationSuccess(response);
                },
                error: function(xhr, status, error) {
                    handleGenerationError(error);
                },
                complete: function() {
                    // Reset button state
                    $button.prop('disabled', false).removeClass('loading');
                    $buttonText.text('تولید محتوا');
                    $buttonLoader.hide();

                    hideGenerationProgress();
                }
            });
        }
    }
    
    function showGenerationProgress() {
        // Create modern progress modal
        const progressHtml = `
            <div id="generation-progress" class="setia-generation-progress">
                <div class="progress-overlay"></div>
                <div class="progress-content">
                    <div class="progress-header">
                        <div class="progress-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2V6M12 18V22M4.93 4.93L7.76 7.76M16.24 16.24L19.07 19.07M2 12H6M18 12H22M4.93 19.07L7.76 16.24M16.24 7.76L19.07 4.93" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3>در حال تولید محتوا...</h3>
                        <p>لطفا صبر کنید، این فرآیند ممکن است تا 2 دقیقه طول بکشد</p>
                    </div>

                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                        <div class="progress-percentage">0%</div>
                    </div>

                    <div class="progress-steps">
                        <div class="progress-steps-container">
                            <div class="progress-step-item active" id="text-step">
                                <div class="step-icon">📝</div>
                                <div class="step-text">تولید متن</div>
                            </div>
                            <div class="progress-step-item" id="seo-step">
                                <div class="step-icon">🔍</div>
                                <div class="step-text">بهینه‌سازی سئو</div>
                            </div>
                            <div class="progress-step-item" id="image-step">
                                <div class="step-icon">🖼️</div>
                                <div class="step-text">تولید تصویر</div>
                            </div>
                        </div>
                    </div>

                    <div class="progress-footer">
                        <button type="button" class="cancel-generation-btn" onclick="cancelGeneration()">
                            لغو تولید
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(progressHtml);

        // Animate progress bar and steps
        animateProgressSteps();
    }

    function animateProgressSteps() {
        // Animate progress bar
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 100) progress = 100;

            $('.progress-fill').css('width', progress + '%');
            $('.progress-percentage').text(Math.round(progress) + '%');

            if (progress >= 100) {
                clearInterval(progressInterval);
            }
        }, 800);

        // Simulate step progression
        setTimeout(() => {
            $('#text-step').addClass('completed').removeClass('active');
            $('#seo-step').addClass('active');
        }, 8000);

        setTimeout(() => {
            $('#seo-step').addClass('completed').removeClass('active');
            $('#image-step').addClass('active');
        }, 16000);

        setTimeout(() => {
            $('#image-step').addClass('completed').removeClass('active');
        }, 24000);
    }

    function cancelGeneration() {
        if (confirm('آیا مطمئن هستید که می‌خواهید تولید محتوا را لغو کنید؟')) {
            hideGenerationProgress();
            // Reset button state
            const $button = $('#setia-generate-btn');
            $button.prop('disabled', false).removeClass('loading');
            $button.find('.button-text').text('تولید محتوا');
            $button.find('.button-loader').hide();
        }
    }
    
    function hideGenerationProgress() {
        $('#generation-progress').fadeOut(400, function() {
            $(this).remove();
        });
    }

    // Add smooth progress step transitions
    function updateProgressStepWithAnimation(step) {
        $('.progress-step').removeClass('active completed');

        // Mark previous steps as completed
        for (let i = 1; i < step; i++) {
            $(`.progress-step[data-step="${i}"]`).addClass('completed');
        }

        // Mark current step as active
        $(`.progress-step[data-step="${step}"]`).addClass('active');

        // Add a subtle animation delay for visual appeal
        setTimeout(() => {
            $(`.progress-step[data-step="${step}"]`).addClass('pulse-animation');
            setTimeout(() => {
                $(`.progress-step[data-step="${step}"]`).removeClass('pulse-animation');
            }, 600);
        }, 100);
    }
    
    function handleGenerationSuccess(response) {
        if (response.success) {
            // ذخیره شناسه محتوای تولید شده
            if (response.data.content_id) {
                window.lastGeneratedContentId = response.data.content_id;
            }

            // Update progress indicator
            updateProgressStep(3);

            // Show result container
            $('#setia-result-container').fadeIn(500);

            // Populate content
            populateGeneratedContent(response.data);

            // Enable result actions
            enableResultActions();

            // Scroll to results
            $('html, body').animate({
                scrollTop: $('#setia-result-container').offset().top - 100
            }, 800);

            showNotification('محتوا با موفقیت تولید شد!', 'success');
        } else {
            handleGenerationError(response.data.message || 'خطای نامشخص');
        }
    }
    
    function handleGenerationError(error) {
        showNotification('خطا در تولید محتوا: ' + error, 'error');
        updateProgressStep(1); // Reset to first step
    }
    
    function populateGeneratedContent(data) {
        // Populate optimized title
        if (data.optimized_title) {
            $('#setia-optimized-title').text(data.optimized_title);
            $('.setia-optimized-title-container').show();
        }
        
        // Populate content preview
        if (data.content) {
            $('#setia-content-preview').html(data.content);
            updateContentStats(data.content);
        }
        
        // Populate SEO data
        if (data.seo) {
            $('#setia-seo-title').text(data.seo.title || '');
            $('#setia-seo-description').text(data.seo.description || '');
            $('#setia-seo-keywords').text(data.seo.keywords || '');
            $('#setia-seo-meta-length').text((data.seo.description || '').length + ' کاراکتر');
        }
        
        // Populate image
        if (data.image_url) {
            const imageHtml = `<img src="${data.image_url}" alt="تصویر تولید شده" />`;
            $('#setia-image-preview-container').html(imageHtml);
        }

        // فعال کردن دکمه‌ها
        $('#setia-publish-btn, #setia-draft-btn, #setia-regenerate-btn, #setia-copy-btn').prop('disabled', false);

        // ذخیره شناسه محتوا
        if (data.content_id) {
            $('#setia-content-form').data('content-id', data.content_id);
        }
    }
    
    function updateContentStats(content) {
        // Calculate word count
        const wordCount = content.replace(/<[^>]*>/g, '').split(/\s+/).filter(word => word.length > 0).length;
        $('#content-word-count').text(wordCount.toLocaleString('fa-IR'));
        
        // Calculate reading time (assuming 200 words per minute for Persian)
        const readingTime = Math.ceil(wordCount / 200);
        $('#content-read-time').text(readingTime.toLocaleString('fa-IR'));
    }
    
    function initializeCounters() {
        // Keywords counter
        $('#setia-keywords').on('input', updateKeywordsCounter);
        
        // Instructions character counter
        $('#setia-instructions').on('input', function() {
            const count = $(this).val().length;
            $('#instructions-count').text(count.toLocaleString('fa-IR'));
            
            if (count > 500) {
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
    }
    
    function updateKeywordsCounter() {
        const keywords = $('#setia-keywords').val().split(',').filter(k => k.trim().length > 0);
        $('#keywords-count').text(keywords.length.toLocaleString('fa-IR'));
    }
    
    function initializeProgressIndicator() {
        // Progress indicator is updated by other functions
    }
    
    function updateProgressStep(step) {
        updateProgressStepWithAnimation(step);
    }
    
    function initializeResultActions() {
        // Publish button
        $('#setia-publish-btn').on('click', function() {
            publishContent();
        });
        
        // Draft button
        $('#setia-draft-btn').on('click', function() {
            saveDraft();
        });
        
        // Regenerate button
        $('#setia-regenerate-btn').on('click', function() {
            if (!window.lastFormData) {
                showNotification('ابتدا محتوایی تولید کنید تا بتوانید آن را مجدداً تولید کنید', 'error');
                return;
            }

            if (confirm('آیا مطمئن هستید که می‌خواهید محتوا را مجدداً تولید کنید؟')) {
                // بازنشانی شناسه محتوای قبلی
                window.lastGeneratedContentId = null;

                // مخفی کردن نتایج قبلی
                $('#setia-result-container').fadeOut(300, function() {
                    // تولید مجدد محتوا
                    generateContent();
                });
            }
        });
        
        // Copy button
        $('#setia-copy-btn').on('click', function() {
            copyContent();
        });
        
        // Export button
        $('#setia-export-btn').on('click', function() {
            exportToWord();
        });
    }
    
    function enableResultActions() {
        // فعال‌سازی تمام دکمه‌های عملیات
        $('.setia-result-actions .setia-button').prop('disabled', false);

        // اضافه کردن انیمیشن ظاهر شدن
        $('.setia-result-actions').addClass('actions-enabled');

        // نمایش پیام راهنما
        if (!window.actionsHelpShown) {
            setTimeout(() => {
                showNotification('دکمه‌های عملیات فعال شدند. می‌توانید محتوا را منتشر، ذخیره یا کپی کنید.', 'info');
                window.actionsHelpShown = true;
            }, 1000);
        }
    }

    function disableAllActionButtons() {
        $('.setia-result-actions .setia-button').prop('disabled', true);
    }

    function enableAllActionButtons() {
        $('.setia-result-actions .setia-button').prop('disabled', false);
    }
    
    function publishContent() {
        const $button = $('#setia-publish-btn');
        const originalText = $button.find('.button-text').text();

        // بررسی وجود محتوا
        if (!lastGeneratedContentId) {
            showNotification('ابتدا محتوایی تولید کنید', 'error');
            return;
        }

        // غیرفعال کردن تمام دکمه‌ها
        disableAllActionButtons();

        // تغییر وضعیت دکمه
        $button.find('.button-text').text('در حال انتشار...');
        $button.find('.button-icon').text('⏳');

        $.ajax({
            url: setiaParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_publish_content',
                nonce: setiaParams.nonce,
                content_id: lastGeneratedContentId,
                status: 'publish'
            },
            success: function(response) {
                if (response.success) {
                    showNotification('محتوا با موفقیت منتشر شد', 'success');
                    setTimeout(() => {
                        window.open(response.data.edit_url, '_blank');
                    }, 1000);
                } else {
                    showNotification('خطا در انتشار محتوا: ' + (response.data?.message || 'خطای نامشخص'), 'error');
                }
            },
            error: function() {
                showNotification('خطا در ارتباط با سرور', 'error');
            },
            complete: function() {
                // بازگردانی وضعیت دکمه‌ها
                enableAllActionButtons();
                $button.find('.button-text').text(originalText);
                $button.find('.button-icon').text('📝');
            }
        });
    }

    function saveDraft() {
        const $button = $('#setia-draft-btn');
        const originalText = $button.find('.button-text').text();

        // بررسی وجود محتوا
        if (!lastGeneratedContentId) {
            showNotification('ابتدا محتوایی تولید کنید', 'error');
            return;
        }

        // غیرفعال کردن تمام دکمه‌ها
        disableAllActionButtons();

        // تغییر وضعیت دکمه
        $button.find('.button-text').text('در حال ذخیره...');
        $button.find('.button-icon').text('⏳');

        $.ajax({
            url: setiaParams.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_publish_content',
                nonce: setiaParams.nonce,
                content_id: lastGeneratedContentId,
                status: 'draft'
            },
            success: function(response) {
                if (response.success) {
                    showNotification('محتوا به عنوان پیش‌نویس ذخیره شد', 'success');
                    setTimeout(() => {
                        window.open(response.data.edit_url, '_blank');
                    }, 1000);
                } else {
                    showNotification('خطا در ذخیره محتوا: ' + (response.data?.message || 'خطای نامشخص'), 'error');
                }
            },
            error: function() {
                showNotification('خطا در ارتباط با سرور', 'error');
            },
            complete: function() {
                // بازگردانی وضعیت دکمه‌ها
                enableAllActionButtons();
                $button.find('.button-text').text(originalText);
                $button.find('.button-icon').text('💾');
            }
        });
    }
    
    function copyContent() {
        const $button = $('#setia-copy-btn');
        const originalIcon = $button.find('.button-icon').text();

        // جمع‌آوری تمام محتوای تولید شده
        let fullContent = '';

        // عنوان
        const title = $('#setia-content-result .content-title').text();
        if (title) {
            fullContent += title + '\n\n';
        }

        // محتوای اصلی
        const mainContent = $('#setia-content-result .content-body').text() ||
                           $('#setia-content-result .setia-content-display').text() ||
                           $('#setia-content-preview').text();
        if (mainContent) {
            fullContent += mainContent + '\n\n';
        }

        // متا توضیحات
        const metaDescription = $('#setia-content-result .meta-description').text();
        if (metaDescription) {
            fullContent += 'توضیحات متا: ' + metaDescription + '\n\n';
        }

        // کلمات کلیدی
        const keywords = $('#setia-content-result .keywords').text();
        if (keywords) {
            fullContent += 'کلمات کلیدی: ' + keywords;
        }

        if (!fullContent.trim()) {
            showNotification('محتوایی برای کپی کردن یافت نشد', 'error');
            return;
        }

        // تغییر آیکون دکمه
        $button.find('.button-icon').text('⏳');

        // کپی کردن محتوا
        navigator.clipboard.writeText(fullContent.trim()).then(function() {
            $button.find('.button-icon').text('✅');
            showNotification('محتوا در کلیپ‌بورد کپی شد', 'success');

            // بازگردانی آیکون بعد از 2 ثانیه
            setTimeout(() => {
                $button.find('.button-icon').text(originalIcon);
            }, 2000);
        }).catch(function() {
            $button.find('.button-icon').text('❌');
            showNotification('خطا در کپی کردن محتوا', 'error');

            // بازگردانی آیکون بعد از 2 ثانیه
            setTimeout(() => {
                $button.find('.button-icon').text(originalIcon);
            }, 2000);
        });
    }

    function exportToWord() {
        const $button = $('#setia-export-btn');
        const originalText = $button.find('.button-text').text();
        const originalIcon = $button.find('.button-icon').text();

        // بررسی وجود محتوا
        if (!lastGeneratedContentId) {
            showNotification('ابتدا محتوایی تولید کنید', 'error');
            return;
        }

        // غیرفعال کردن تمام دکمه‌ها
        disableAllActionButtons();

        // تغییر وضعیت دکمه
        $button.find('.button-text').text('در حال تهیه...');
        $button.find('.button-icon').text('⏳');

        // جمع‌آوری محتوا برای خروجی Word
        const title = $('#setia-content-result .content-title').text() || 'محتوای تولید شده';
        const content = $('#setia-content-result .content-body').text() ||
                       $('#setia-content-result .setia-content-display').text() ||
                       $('#setia-content-preview').text() || '';
        const metaDescription = $('#setia-content-result .meta-description').text() || '';
        const keywords = $('#setia-content-result .keywords').text() || '';

        if (!content.trim()) {
            showNotification('محتوایی برای خروجی یافت نشد', 'error');
            enableAllActionButtons();
            $button.find('.button-text').text(originalText);
            $button.find('.button-icon').text(originalIcon);
            return;
        }

        // ایجاد محتوای HTML برای تبدیل به Word
        const htmlContent = `
            <!DOCTYPE html>
            <html dir="rtl" lang="fa">
            <head>
                <meta charset="UTF-8">
                <title>${title}</title>
                <style>
                    body { font-family: 'Vazirmatn', Arial, sans-serif; direction: rtl; text-align: right; }
                    h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
                    .meta-info { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
                    .content { line-height: 1.8; margin: 20px 0; }
                </style>
            </head>
            <body>
                <h1>${title}</h1>
                <div class="content">${content.replace(/\n/g, '<br>')}</div>
                ${metaDescription ? `<div class="meta-info"><strong>توضیحات متا:</strong><br>${metaDescription}</div>` : ''}
                ${keywords ? `<div class="meta-info"><strong>کلمات کلیدی:</strong><br>${keywords}</div>` : ''}
            </body>
            </html>
        `;

        // ایجاد فایل Word
        const blob = new Blob([htmlContent], { type: 'application/msword' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${title.replace(/[^\w\s]/gi, '').substring(0, 50)}.doc`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        // نمایش پیام موفقیت
        showNotification('فایل Word با موفقیت دانلود شد', 'success');

        // بازگردانی وضعیت دکمه‌ها
        setTimeout(() => {
            enableAllActionButtons();
            $button.find('.button-text').text(originalText);
            $button.find('.button-icon').text(originalIcon);
        }, 1000);
    }
    
    function showNotification(message, type) {
        const $notification = $(`
            <div class="setia-notification setia-notification-${type}">
                ${message}
            </div>
        `);
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }

    // ===== MAIN TABS FUNCTIONALITY =====
    function initializeMainTabs() {
        $('.setia-main-tab-button').on('click', function() {
            const tabId = $(this).data('main-tab');

            // Remove active class from all buttons and contents
            $('.setia-main-tab-button').removeClass('active');
            $('.setia-main-tab-content').removeClass('active');

            // Add active class to clicked button and corresponding content
            $(this).addClass('active');
            $('#' + tabId + '-main-tab').addClass('active');

            // Update progress indicator based on tab
            if (tabId === 'content') {
                $('.setia-progress-indicator .progress-step').eq(0).find('.step-title').text('مشخصات محتوا');
            } else if (tabId === 'product') {
                $('.setia-progress-indicator .progress-step').eq(0).find('.step-title').text('مشخصات محصول');
            }
        });
    }

    // ===== PRODUCT GENERATION FUNCTIONALITY =====
    function initializeProductGeneration() {
        console.log('SETIA DEBUG: Initializing product generation functionality');

        // Check if elements exist
        const $form = $('#setia-product-form');
        const $button = $('#setia-generate-product-btn');

        console.log('SETIA DEBUG: Form exists:', $form.length > 0);
        console.log('SETIA DEBUG: Button exists:', $button.length > 0);

        // Product form submission
        $('#setia-product-form').on('submit', function(e) {
            console.log('SETIA DEBUG: Form submit event triggered');
            e.preventDefault();
            generateProduct();
        });

        // Direct button click handler as backup
        $('#setia-generate-product-btn').on('click', function(e) {
            console.log('SETIA DEBUG: Button click event triggered');
            e.preventDefault();
            generateProduct();
        });

        // Product name validation
        $('#setia-product-name').on('input', function() {
            validateField($(this), {
                required: true,
                minLength: 3,
                maxLength: 200
            });
        });

        // Product result actions
        $('#setia-view-product-btn').on('click', function() {
            const productId = $(this).data('product-id');
            if (productId) {
                window.open('/wp-admin/post.php?post=' + productId + '&action=edit', '_blank');
            }
        });

        $('#setia-edit-product-btn').on('click', function() {
            const productId = $(this).data('product-id');
            if (productId) {
                window.open('/wp-admin/post.php?post=' + productId + '&action=edit', '_blank');
            }
        });

        $('#setia-regenerate-product-btn').on('click', function() {
            if (confirm('آیا مطمئن هستید که می‌خواهید محصول را مجدداً تولید کنید؟')) {
                generateProduct();
            }
        });

        $('#setia-duplicate-product-btn').on('click', function() {
            const productName = $('#setia-product-name').val();
            if (productName) {
                $('#setia-product-name').val(productName + ' - کپی');
                generateProduct();
            }
        });
    }

    function generateProduct() {
        console.log('SETIA DEBUG: generateProduct function called');

        const $form = $('#setia-product-form');
        const $button = $('#setia-generate-product-btn');
        const $results = $('#setia-product-results');

        console.log('SETIA DEBUG: Form element found:', $form.length > 0);
        console.log('SETIA DEBUG: Button element found:', $button.length > 0);
        console.log('SETIA DEBUG: setiaParams available:', typeof setiaParams !== 'undefined');

        if (typeof setiaParams !== 'undefined') {
            console.log('SETIA DEBUG: AJAX URL:', setiaParams.ajaxUrl);
            console.log('SETIA DEBUG: Nonce:', setiaParams.nonce);
        }

        // Validate form
        if (!validateProductForm()) {
            console.log('SETIA DEBUG: Form validation failed');
            return;
        }

        console.log('SETIA DEBUG: Form validation passed');

        // Show loading state
        $button.addClass('loading').prop('disabled', true);
        $button.find('.button-text').text('در حال تولید محصول...');

        // Update progress
        updateProgressStep(2, 'در حال تولید محصول...');

        // Prepare form data
        const formData = new FormData($form[0]);
        formData.append('action', 'setia_generate_product');
        formData.append('nonce', setiaParams.nonce);

        // Remove the form's nonce field to avoid conflicts
        formData.delete('setia_product_nonce');

        console.log('SETIA DEBUG: Sending AJAX request...');
        console.log('SETIA DEBUG: FormData contents:');
        for (let pair of formData.entries()) {
            console.log('SETIA DEBUG:', pair[0], '=', pair[1]);
        }

        // Send AJAX request
        $.ajax({
            url: setiaParams.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 120000, // 2 minutes timeout
            success: function(response) {
                console.log('SETIA DEBUG: AJAX Success Response:', response);

                if (response.success) {
                    console.log('SETIA DEBUG: Product generation successful');
                    displayProductResults(response.data);
                    updateProgressStep(3, 'محصول آماده است');

                    // Show results
                    $results.fadeIn(500);

                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $results.offset().top - 100
                    }, 800);

                } else {
                    console.log('SETIA DEBUG: Product generation failed:', response.data);
                    showNotification('خطا در تولید محصول: ' + (response.data.message || 'خطای نامشخص'), 'error');
                    updateProgressStep(1, 'مشخصات محصول');
                }
            },
            error: function(xhr, status, error) {
                console.error('SETIA DEBUG: AJAX Error Details:');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                console.error('Status Code:', xhr.status);

                showNotification('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
                updateProgressStep(1, 'مشخصات محصول');
            },
            complete: function() {
                // Reset button state
                $button.removeClass('loading').prop('disabled', false);
                $button.find('.button-text').text('تولید محصول');
            }
        });
    }

    function validateProductForm() {
        let isValid = true;

        // Validate product name
        const productName = $('#setia-product-name').val().trim();
        if (!productName || productName.length < 3) {
            showFieldError('#setia-product-name', 'نام محصول باید حداقل 3 کاراکتر باشد');
            isValid = false;
        }

        return isValid;
    }

    function showFieldError(fieldSelector, message) {
        const $field = $(fieldSelector);
        $field.addClass('error');

        // Remove existing error message
        $field.siblings('.error-message').remove();

        // Add error message
        $field.after('<div class="error-message" style="color: #ef4444; font-size: 12px; margin-top: 4px;">' + message + '</div>');

        // Remove error after 5 seconds
        setTimeout(function() {
            $field.removeClass('error');
            $field.siblings('.error-message').remove();
        }, 5000);
    }

    function displayProductResults(data) {
        // Display product info
        if (data.product_info) {
            $('#setia-product-preview-container').html(data.product_info);
        }

        // Display product images
        if (data.images && data.images.length > 0) {
            let imagesHtml = '';
            data.images.forEach(function(image, index) {
                imagesHtml += `
                    <div class="setia-product-image-item">
                        <img src="${image.url}" alt="${image.alt || 'تصویر محصول'}" />
                        <p>${image.title || 'تصویر ' + (index + 1)}</p>
                    </div>
                `;
            });
            $('#setia-product-images-container').html(imagesHtml);
        }

        // Display technical specifications
        if (data.technical_specs && data.technical_specs.length > 0) {
            let specsHtml = '<div class="setia-product-specs-list">';
            data.technical_specs.forEach(function(spec) {
                specsHtml += `
                    <div class="setia-spec-item">
                        <span class="spec-name">${spec.name}:</span>
                        <span class="spec-value">${spec.value}</span>
                    </div>
                `;
            });
            specsHtml += '</div>';
            $('#setia-product-specs-container').html(specsHtml);
        }

        // Display schema markup
        if (data.schema) {
            $('#setia-product-schema-container').html(`
                <pre><code>${JSON.stringify(data.schema, null, 2)}</code></pre>
            `);
        }

        // Enable action buttons
        if (data.product_id) {
            $('#setia-view-product-btn, #setia-edit-product-btn').prop('disabled', false)
                .data('product-id', data.product_id);
        }

        $('#setia-regenerate-product-btn, #setia-duplicate-product-btn').prop('disabled', false);
    }

    // دکمه‌های عملیات نتیجه در initializeResultActions() تعریف شده‌اند
    // این بخش حذف شده تا از تداخل جلوگیری شود
});

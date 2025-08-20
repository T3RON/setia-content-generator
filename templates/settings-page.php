<?php
/**
 * قالب صفحه تنظیمات افزونه SETIA Content Generator
 * طراحی مدرن Flat Design مشابه Windows 11
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// دریافت تنظیمات از Settings Manager
$settings_manager = SETIA_Settings_Manager::get_instance();
$all_settings = $settings_manager->get_settings();

// تهیه تنظیمات flat برای compatibility با template
$settings = array(
    'gemini_api_key' => $all_settings['api']['gemini_api_key'] ?? '',
    'gemma_api_key' => $all_settings['api']['gemma_api_key'] ?? '',
    'imagine_art_api_key' => $all_settings['api']['imagine_art_api_key'] ?? '',
    'default_tone' => $all_settings['content']['default_tone'] ?? 'عادی',
    'default_length' => $all_settings['content']['default_length'] ?? 'متوسط',
    'enable_seo' => $all_settings['content']['enable_seo'] ? 'yes' : 'no',
    'enable_image_generation' => $all_settings['image']['enable_generation'] ? 'yes' : 'no',
    'default_image_style' => $all_settings['image']['default_style'] ?? 'realistic',
    'default_aspect_ratio' => $all_settings['image']['default_aspect_ratio'] ?? '16:9',
    'max_history_items' => $all_settings['system']['history_retention'] ?? 100,
    'cache_expiration' => $all_settings['system']['cache_duration'] ?? 24,
    'auto_save_drafts' => $all_settings['system']['enable_auto_save'] ? 'yes' : 'no',
    'debug_mode' => $all_settings['system']['enable_debug'] ? 'yes' : 'no'
);

// دریافت نسخه افزونه
$plugin_version = SETIA_Content_Generator::VERSION;
?>

<div class="wrap">
    <div class="setia-settings-wrapper">
        <!-- هدر صفحه -->
    <div class="setia-settings-header">
            <div class="setia-settings-title">
                <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/setia-logo.png'; ?>" alt="SETIA Logo" class="setia-settings-logo">
                <h1>تنظیمات افزونه تولید محتوا</h1>
                <span class="setia-settings-version">نسخه <?php echo esc_html($plugin_version); ?></span>
            </div>
            <div class="setia-settings-status" id="settings-status">
                <span class="status-indicator" id="auto-save-status"></span>
            </div>
        </div>

        <!-- اعلان‌ها در اینجا نمایش داده می‌شوند -->

        <!-- تب‌های تنظیمات -->
        <div class="setia-nav-tabs">
            <button class="setia-tab-button active" data-tab="api-settings">
                <span class="dashicons dashicons-admin-network"></span>
                تنظیمات API
            </button>
            <button class="setia-tab-button" data-tab="content-settings">
                <span class="dashicons dashicons-editor-paste-text"></span>
                تنظیمات تولید محتوا
                </button>
            <button class="setia-tab-button" data-tab="image-settings">
                <span class="dashicons dashicons-format-image"></span>
                تنظیمات تصاویر
                </button>
            <button class="setia-tab-button" data-tab="advanced-settings">
                <span class="dashicons dashicons-admin-tools"></span>
                تنظیمات پیشرفته
                </button>
            <button class="setia-tab-button" data-tab="system-tools">
                <span class="dashicons dashicons-admin-generic"></span>
                ابزارهای سیستم
                </button>
        </div>

        <!-- فرم تنظیمات -->
        <form id="setia-settings-form" class="setia-settings-form" method="post">
            <!-- فیلدهای امنیتی -->
            <input type="hidden" name="setia_settings_nonce" value="<?php echo wp_create_nonce('setia_settings_nonce'); ?>">
            <input type="hidden" name="setia_test_nonce" value="<?php echo wp_create_nonce('setia_test_nonce'); ?>">
                
            <!-- تب تنظیمات API -->
                <div id="api-settings" class="setia-tab-content active">
                            <div class="setia-card">
                                <div class="setia-card-header">
                        <h2 class="setia-card-title">تنظیمات API هوش مصنوعی</h2>
                    </div>
                    <div class="setia-card-description">
                        برای استفاده از قابلیت‌های هوش مصنوعی، کلیدهای API خود را در این بخش وارد کنید.
                                </div>

                    <!-- کلید API Gemini -->
                                    <div class="setia-form-group">
                        <label for="gemini_api_key" class="setia-form-label">
                            کلید API Google AI (Gemini)
                            <span class="required-star">*</span>
                        </label>
                        <div class="setia-api-key-input">
                            <input type="password" id="gemini_api_key" name="gemini_api_key" class="setia-form-input" value="<?php echo esc_attr($settings['gemini_api_key']); ?>" placeholder="AIza..." data-validation="gemini-key">
                            <button type="button" class="setia-api-key-toggle" title="نمایش/مخفی کردن کلید">
                                <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                        <div class="setia-form-help">
                            کلید API را از <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a> دریافت کنید.
                                </div>
                        <button type="button" id="test-gemini-api" class="setia-btn setia-btn-secondary setia-test-api-btn">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            تست اتصال به Gemini
                        </button>
                        <div id="gemini-test-result" class="setia-test-result" style="display: none;"></div>
                            </div>

                    <!-- کلید API Imagine Art -->
                                    <div class="setia-form-group">
                        <label for="imagine_art_api_key" class="setia-form-label">کلید API Imagine Art</label>
                        <div class="setia-api-key-input">
                            <input type="password" id="imagine_art_api_key" name="imagine_art_api_key" class="setia-form-input" value="<?php echo esc_attr($settings['imagine_art_api_key']); ?>" placeholder="کلید API Imagine Art را وارد کنید">
                            <button type="button" class="setia-api-key-toggle">
                                <span class="dashicons dashicons-visibility"></span>
                                        </button>
                        </div>
                        <div class="setia-form-help">
                            کلید API را از <a href="https://vyro.ai/" target="_blank">سایت Vyro.ai</a> دریافت کنید.
                        </div>
                        <button type="button" id="test-imagine-art-api" class="setia-btn setia-btn-secondary setia-test-api-btn">تست اتصال</button>
                        <div id="imagine_art-test-result" class="setia-test-result" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>

            <!-- تب تنظیمات تولید محتوا -->
            <div id="content-settings" class="setia-tab-content">
                            <div class="setia-card">
                                <div class="setia-card-header">
                        <h2 class="setia-card-title">تنظیمات پیش‌فرض تولید محتوا</h2>
                    </div>
                    <div class="setia-card-description">
                        تنظیمات پیش‌فرض برای تولید محتوا را در این بخش تعیین کنید.
                                </div>

                    <!-- لحن پیش‌فرض -->
                                    <div class="setia-form-group">
                        <label for="default_tone" class="setia-form-label">لحن پیش‌فرض</label>
                        <select id="default_tone" name="default_tone" class="setia-form-select">
                            <option value="عادی" <?php selected($settings['default_tone'], 'عادی'); ?>>عادی</option>
                            <option value="رسمی" <?php selected($settings['default_tone'], 'رسمی'); ?>>رسمی</option>
                            <option value="دوستانه" <?php selected($settings['default_tone'], 'دوستانه'); ?>>دوستانه</option>
                            <option value="علمی" <?php selected($settings['default_tone'], 'علمی'); ?>>علمی</option>
                            <option value="خبری" <?php selected($settings['default_tone'], 'خبری'); ?>>خبری</option>
                            <option value="طنز" <?php selected($settings['default_tone'], 'طنز'); ?>>طنز</option>
                        </select>
                                    </div>

                    <!-- طول پیش‌فرض -->
                    <div class="setia-form-group">
                        <label for="default_length" class="setia-form-label">طول پیش‌فرض محتوا</label>
                        <select id="default_length" name="default_length" class="setia-form-select">
                            <option value="کوتاه" <?php selected($settings['default_length'], 'کوتاه'); ?>>کوتاه (300-500 کلمه)</option>
                            <option value="متوسط" <?php selected($settings['default_length'], 'متوسط'); ?>>متوسط (500-800 کلمه)</option>
                            <option value="بلند" <?php selected($settings['default_length'], 'بلند'); ?>>بلند (800-1200 کلمه)</option>
                            <option value="خیلی بلند" <?php selected($settings['default_length'], 'خیلی بلند'); ?>>خیلی بلند (1200+ کلمه)</option>
                        </select>
                                </div>

                    <!-- فعال‌سازی بهینه‌سازی SEO -->
                    <div class="setia-form-group">
                        <label class="setia-form-label">بهینه‌سازی SEO</label>
                        <div class="setia-form-checkbox">
                            <input type="checkbox" id="enable_seo" name="enable_seo" value="yes" <?php echo ($settings['enable_seo'] === 'yes') ? 'checked="checked"' : ''; ?>>
                            <label for="enable_seo">فعال‌سازی بهینه‌سازی خودکار SEO</label>
                            </div>
                        <div class="setia-form-help">
                            با فعال کردن این گزینه، محتوای تولید شده به صورت خودکار برای موتورهای جستجو بهینه‌سازی می‌شود.
                        </div>
                    </div>
                </div>
                        </div>
                        
            <!-- تب تنظیمات تصاویر -->
            <div id="image-settings" class="setia-tab-content">
                            <div class="setia-card">
                                <div class="setia-card-header">
                        <h2 class="setia-card-title">تنظیمات تولید تصاویر</h2>
                    </div>
                    <div class="setia-card-description">
                        تنظیمات پیش‌فرض برای تولید تصاویر را در این بخش تعیین کنید.
                    </div>

                    <!-- فعال‌سازی تولید تصویر -->
                    <div class="setia-form-group">
                        <label class="setia-form-label">تولید خودکار تصویر</label>
                        <div class="setia-form-checkbox">
                            <input type="checkbox" id="enable_image_generation" name="enable_image_generation" value="yes" <?php echo ($settings['enable_image_generation'] === 'yes') ? 'checked="checked"' : ''; ?>>
                            <label for="enable_image_generation">فعال‌سازی تولید خودکار تصویر شاخص</label>
                        </div>
                        <div class="setia-form-help">
                            با فعال کردن این گزینه، برای هر محتوای تولید شده، یک تصویر شاخص نیز تولید می‌شود.
                        </div>
                                </div>

                    <!-- استایل پیش‌فرض تصویر -->
                    <div class="setia-form-group">
                        <label for="default_image_style" class="setia-form-label">استایل پیش‌فرض تصویر</label>
                        <select id="default_image_style" name="default_image_style" class="setia-form-select">
                            <option value="realistic" <?php selected($settings['default_image_style'], 'realistic'); ?>>واقع‌گرایانه</option>
                            <option value="cartoon" <?php selected($settings['default_image_style'], 'cartoon'); ?>>کارتونی</option>
                            <option value="artistic" <?php selected($settings['default_image_style'], 'artistic'); ?>>هنری</option>
                            <option value="abstract" <?php selected($settings['default_image_style'], 'abstract'); ?>>انتزاعی</option>
                        </select>
                    </div>

                    <!-- نسبت ابعاد پیش‌فرض -->
                    <div class="setia-form-group">
                        <label for="default_aspect_ratio" class="setia-form-label">نسبت ابعاد پیش‌فرض</label>
                        <select id="default_aspect_ratio" name="default_aspect_ratio" class="setia-form-select">
                            <option value="16:9" <?php selected($settings['default_aspect_ratio'], '16:9'); ?>>16:9 (افقی)</option>
                            <option value="1:1" <?php selected($settings['default_aspect_ratio'], '1:1'); ?>>1:1 (مربعی)</option>
                            <option value="4:3" <?php selected($settings['default_aspect_ratio'], '4:3'); ?>>4:3 (کلاسیک)</option>
                            <option value="9:16" <?php selected($settings['default_aspect_ratio'], '9:16'); ?>>9:16 (عمودی)</option>
                        </select>
                    </div>

                    <!-- تست تولید تصویر -->
                    <div class="setia-form-group">
                        <label class="setia-form-label">تست تولید تصویر</label>
                        <div class="setia-test-image-form">
                            <input type="text" id="test_image_prompt" placeholder="پرامپت برای تست تولید تصویر..." class="setia-form-input">
                            <select id="test_image_style" class="setia-form-select">
                                <option value="realistic">واقع‌گرایانه</option>
                                <option value="cartoon">کارتونی</option>
                                <option value="artistic">هنری</option>
                                <option value="abstract">انتزاعی</option>
                            </select>
                            <select id="test_image_aspect_ratio" class="setia-form-select">
                                <option value="16:9">16:9</option>
                                <option value="1:1">1:1</option>
                                <option value="4:3">4:3</option>
                                <option value="9:16">9:16</option>
                            </select>
                            <input type="text" id="test_image_negative_prompt" placeholder="پرامپت منفی (اختیاری)..." class="setia-form-input">
                            <button type="button" id="generate-test-image-btn" class="setia-btn setia-btn-primary">تولید تصویر تست</button>
                        </div>
                        <div id="setia-test-image-result" class="setia-test-result"></div>
                    </div>
                </div>
            </div>

            <!-- تب تنظیمات پیشرفته -->
            <div id="advanced-settings" class="setia-tab-content">
                <div class="setia-card">
                    <div class="setia-card-header">
                        <h2 class="setia-card-title">تنظیمات پیشرفته</h2>
                    </div>
                    <div class="setia-card-description">
                        تنظیمات پیشرفته برای کاربران حرفه‌ای
                    </div>

                    <!-- حداکثر تعداد موارد تاریخچه -->
                    <div class="setia-form-group">
                        <label for="max_history_items" class="setia-form-label">حداکثر تعداد موارد تاریخچه</label>
                        <input type="number" id="max_history_items" name="max_history_items" class="setia-form-input" value="<?php echo esc_attr($settings['max_history_items']); ?>" min="50" max="1000">
                        <div class="setia-form-help">
                            تعداد حداکثر محتوای ذخیره شده در تاریخچه (50 تا 1000)
                        </div>
                    </div>

                    <!-- مدت زمان انقضای کش -->
                    <div class="setia-form-group">
                        <label for="cache_expiration" class="setia-form-label">مدت انقضای کش (ساعت)</label>
                        <input type="number" id="cache_expiration" name="cache_expiration" class="setia-form-input" value="<?php echo esc_attr($settings['cache_expiration']); ?>" min="1" max="168">
                        <div class="setia-form-help">
                            مدت زمان نگهداری کش به ساعت (1 تا 168 ساعت)
                        </div>
                    </div>

                    <!-- ذخیره خودکار پیش‌نویس -->
                    <div class="setia-form-group">
                        <label class="setia-form-label">ذخیره خودکار پیش‌نویس‌ها</label>
                        <div class="setia-form-checkbox">
                            <input type="checkbox" id="auto_save_drafts" name="auto_save_drafts" value="yes" <?php echo ($settings['auto_save_drafts'] === 'yes') ? 'checked="checked"' : ''; ?>>
                            <label for="auto_save_drafts">فعال‌سازی ذخیره خودکار پیش‌نویس‌ها</label>
                        </div>
                        <div class="setia-form-help">
                            محتوای تولید شده به صورت خودکار به عنوان پیش‌نویس ذخیره می‌شود
                        </div>
                    </div>

                    <!-- حالت دیباگ -->
                    <div class="setia-form-group">
                        <label class="setia-form-label">حالت دیباگ</label>
                        <div class="setia-form-checkbox">
                            <input type="checkbox" id="debug_mode" name="debug_mode" value="yes" <?php echo ($settings['debug_mode'] === 'yes') ? 'checked="checked"' : ''; ?>>
                            <label for="debug_mode">فعال‌سازی حالت دیباگ</label>
                        </div>
                        <div class="setia-form-help">
                            برای عیب‌یابی مشکلات. تنها در صورت نیاز فعال کنید.
                        </div>
                    </div>
                </div>
            </div>

            <!-- تب ابزارهای سیستم -->
            <div id="system-tools" class="setia-tab-content">
                <div class="setia-card">
                    <div class="setia-card-header">
                        <h2 class="setia-card-title">ابزارهای سیستم</h2>
                    </div>
                    <div class="setia-card-description">
                        ابزارهای نگهداری و مدیریت سیستم
                    </div>

                    <!-- پاک کردن کش -->
                    <div class="setia-form-group">
                        <label class="setia-form-label">پاک کردن کش</label>
                        <button type="button" id="clear-cache-btn" class="setia-btn setia-btn-danger">پاک کردن کش افزونه</button>
                        <div class="setia-form-help">
                            تمام داده‌های کش شده توسط افزونه را پاک می‌کند. در صورت بروز مشکل در عملکرد افزونه، این گزینه را امتحان کنید.
                        </div>
                                    </div>

                    <!-- بازنشانی تنظیمات -->
                                    <div class="setia-form-group">
                        <label class="setia-form-label">بازنشانی تنظیمات</label>
                        <button type="button" id="reset-settings-btn" class="setia-btn setia-btn-danger">بازنشانی به تنظیمات پیش‌فرض</button>
                        <div class="setia-form-help">
                            تمام تنظیمات افزونه را به حالت پیش‌فرض بازمی‌گرداند. این عمل قابل بازگشت نیست!
                                    </div>
                                </div>

                    <!-- صادر/وارد کردن تنظیمات -->
                    <div class="setia-form-group">
                        <label class="setia-form-label">صادر/وارد کردن تنظیمات</label>
                        <div class="setia-button-group">
                            <button type="button" id="export-settings-btn" class="setia-btn setia-btn-secondary">صادر کردن تنظیمات</button>
                            <button type="button" id="import-settings-btn" class="setia-btn setia-btn-secondary">وارد کردن تنظیمات</button>
                            <input type="file" id="import-settings-file" style="display: none;" accept=".json">
                        </div>
                        <div class="setia-form-help">
                            تنظیمات افزونه را به صورت فایل JSON صادر یا وارد کنید. برای انتقال تنظیمات بین سایت‌ها مفید است.
                            </div>
                        </div>
                    </div>
                </div>

            <!-- دکمه‌های اصلی -->
            <div class="setia-form-actions">
                <button type="submit" id="save-settings-btn" class="setia-btn setia-btn-primary">ذخیره تنظیمات</button>
                <div id="saving-indicator" class="setia-saving-indicator" style="display: none;">
                    <span class="spinner is-active"></span>
                    <span class="setia-saving-text">در حال ذخیره...</span>
                </div>
                </div>
            </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // اضافه کردن کد دیباگ برای بررسی مشکل تولید تصویر
    $('#generate-test-image-btn').on('click', function() {
        console.log('SETIA DEBUG: Test image button clicked');
        
        var $resultContainer = $('#setia-test-image-result');
        var $submitButton = $(this);
        var originalButtonText = $submitButton.text();
        
        // نمایش وضعیت در حال پردازش
        $submitButton.text('در حال پردازش...').prop('disabled', true);
        $resultContainer.html('<div class="setia-notice setia-notice-info">در حال ارسال درخواست به سرور...</div>');
        
        // دریافت مقادیر فرم
        var apiKey = $('#imagine_art_api_key').val();
        var prompt = $('#test_image_prompt').val();
        var style = $('#test_image_style').val();
        var aspectRatio = $('#test_image_aspect_ratio').val();
        var negativePrompt = $('#test_image_negative_prompt').val();
        
        // بررسی اعتبار ورودی‌ها
        if (!prompt) {
            $resultContainer.html('<div class="setia-notice setia-notice-error">لطفاً یک پرامپت برای تولید تصویر وارد کنید</div>');
            $submitButton.text(originalButtonText).prop('disabled', false);
            return;
        }
        
        // ارسال درخواست AJAX
        $.ajax({
            url: setia_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'setia_test_image_generation',
                nonce: setia_admin_ajax.nonce,
                imagine_art_api_key: apiKey,
                prompt: prompt,
                image_style: style,
                aspect_ratio: aspectRatio,
                negative_prompt: negativePrompt
            },
            beforeSend: function() {
                console.log('SETIA DEBUG: Sending image generation request with parameters:', {
                    action: 'setia_test_image_generation',
                    nonce: 'HIDDEN',
                    prompt: prompt,
                    image_style: style,
                    aspect_ratio: aspectRatio,
                    negative_prompt: negativePrompt
                });
                console.log('SETIA DEBUG: Ajax URL:', setia_admin_ajax.ajax_url);
            },
            success: function(response) {
                console.log('Image generation response:', response);
                
                if (response.success) {
                    var imageUrl = response.data.image_url;
                    var message = response.data.message || 'تصویر با موفقیت تولید شد';
                    
                    // نمایش تصویر و پیام موفقیت
                    $resultContainer.html(
                        '<div class="setia-notice setia-notice-success">' + message + '</div>' +
                        '<div class="setia-test-image-container">' +
                        '<img src="' + imageUrl + '" alt="تصویر تولید شده" class="setia-test-image">' +
                        '</div>'
                    );
                } else {
                    // نمایش پیام خطا
                    var errorMessage = response.data.message || 'خطایی در تولید تصویر رخ داد';
                    var additionalDebug = response.data.additional_debug ? '<br><small>' + response.data.additional_debug + '</small>' : '';
                    
                    $resultContainer.html(
                        '<div class="setia-notice setia-notice-error">' + errorMessage + additionalDebug + '</div>'
                    );
                    
                    // اگر تصویر fallback وجود دارد، آن را نمایش می‌دهیم
                    if (response.data.image_url && response.data.is_fallback) {
                        $resultContainer.append(
                            '<div class="setia-test-image-container">' +
                            '<img src="' + response.data.image_url + '" alt="تصویر پیش‌فرض" class="setia-test-image">' +
                            '<p class="setia-fallback-notice">تصویر پیش‌فرض</p>' +
                            '</div>'
                        );
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                $resultContainer.html(
                    '<div class="setia-notice setia-notice-error">خطا در ارسال درخواست: ' + error + '</div>'
                );
            },
            complete: function() {
                // بازگرداندن دکمه به حالت اولیه
                $submitButton.text(originalButtonText).prop('disabled', false);
            }
        });
    });
    
    // ارسال فرم تنظیمات با AJAX
    $('#setia-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('SETIA DEBUG: Settings form submitted');
        
        var $form = $(this);
        var formData = $form.serialize();
        var $submitButton = $('#save-settings-btn');
        var originalButtonText = $submitButton.text();
        
        // نمایش وضعیت در حال پردازش
        $submitButton.text('در حال ذخیره...').prop('disabled', true);
        $('#saving-indicator').show();
        
        // دیباگ: نمایش وضعیت چک باکس‌ها
        console.log('SETIA DEBUG: Debug Mode Checkbox Checked:', $('#debug_mode').is(':checked'));
        console.log('SETIA DEBUG: Enable SEO Checkbox Checked:', $('#enable_seo').is(':checked'));
        console.log('SETIA DEBUG: Enable Image Generation Checkbox Checked:', $('#enable_image_generation').is(':checked'));
        console.log('SETIA DEBUG: Auto Save Drafts Checkbox Checked:', $('#auto_save_drafts').is(':checked'));
        
        // ارسال درخواست AJAX
        $.ajax({
            url: setia_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'setia_save_settings',
                nonce: $('input[name="setia_settings_nonce"]').val(),
                form_data: formData
            },
            beforeSend: function() {
                console.log('SETIA DEBUG: Sending settings data');
                console.log('SETIA DEBUG: Form data:', formData);
                console.log('SETIA DEBUG: Nonce:', $('input[name="setia_settings_nonce"]').val());
            },
            success: function(response) {
                console.log('Settings save response:', response);
                
                if (response.success) {
                    // نمایش پیام موفقیت
                    var $messageContainer = $('<div class="setia-message success">تنظیمات با موفقیت ذخیره شد</div>');
                    $('.setia-settings-header').after($messageContainer);
                    
                    // حذف پیام پس از 3 ثانیه
                    setTimeout(function() {
                        $messageContainer.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 3000);
                    
                    // علامت‌گذاری فرم به عنوان ذخیره شده (برای جلوگیری از نمایش هشدار هنگام خروج)
                    $form.data('changed', false);
                } else {
                    // نمایش پیام خطا
                    var errorMessage = response.data ? response.data.message : 'خطایی در ذخیره تنظیمات رخ داد';
                    var $errorContainer = $('<div class="setia-message error">خطا: ' + errorMessage + '</div>');
                    $('.setia-settings-header').after($errorContainer);
                    
                    // حذف پیام پس از 5 ثانیه
                    setTimeout(function() {
                        $errorContainer.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                var $errorContainer = $('<div class="setia-message error">خطا در ارسال درخواست: ' + error + '</div>');
                $('.setia-settings-header').after($errorContainer);
                
                // حذف پیام پس از 5 ثانیه
                setTimeout(function() {
                    $errorContainer.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            },
            complete: function() {
                // بازگرداندن دکمه به حالت اولیه
                $submitButton.text(originalButtonText).prop('disabled', false);
                $('#saving-indicator').hide();
            }
        });
    });

    // ردیابی تغییرات فرم
    $('#setia-settings-form :input').on('change input', function() {
        $('#setia-settings-form').data('changed', true);
    });

    // هشدار قبل از خروج اگر تغییرات ذخیره نشده باشد
    $(window).on('beforeunload', function() {
        if ($('#setia-settings-form').data('changed')) {
            return 'ممکن است تغییرات شما ذخیره نشده باشند.';
        }
    });
    
    // مدیریت تب‌ها
    $('.setia-tab-item').on('click', function() {
        var tabId = $(this).data('tab');
        
        // حذف کلاس active از همه تب‌ها و محتوای آن‌ها
        $('.setia-tab-item').removeClass('active');
        $('.setia-tab-content').removeClass('active');
        
        // اضافه کردن کلاس active به تب انتخاب شده و محتوای آن
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // تست اتصال API
    $('.setia-test-api-btn').on('click', function() {
        var apiType = $(this).attr('id').replace('test-', '').replace('-api', '');
        var apiKey = $('#' + apiType + '_api_key').val();
        var $resultContainer = $('#' + apiType + '-test-result');
        var $button = $(this);
        var originalText = $button.text();
        
        if (!apiKey) {
            $resultContainer.html('<div class="setia-notice setia-notice-error">لطفاً ابتدا کلید API را وارد کنید</div>').show();
            return;
        }
        
        // نمایش وضعیت در حال پردازش
        $button.text('در حال تست...').prop('disabled', true);
        $resultContainer.html('<div class="setia-notice setia-notice-info">در حال تست اتصال...</div>').show();
        
        // ارسال درخواست AJAX
        $.ajax({
            url: setia_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'setia_test_api_connection',
                nonce: $('input[name="setia_settings_nonce"]').val(),
                api_type: apiType,
                api_key: apiKey
            },
            success: function(response) {
                console.log('API test response:', response);
                
                if (response.success) {
                    $resultContainer.html('<div class="setia-notice setia-notice-success">' + response.data.message + '</div>');
                } else {
                    var errorMessage = response.data ? response.data.message : 'خطایی در تست اتصال رخ داد';
                    $resultContainer.html('<div class="setia-notice setia-notice-error">' + errorMessage + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                $resultContainer.html('<div class="setia-notice setia-notice-error">خطا در ارسال درخواست: ' + error + '</div>');
            },
            complete: function() {
                // بازگرداندن دکمه به حالت اولیه
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // پاک کردن کش
    $('#clear-cache-btn').on('click', function() {
        if (confirm('آیا از پاک کردن کش اطمینان دارید؟')) {
            var $button = $(this);
            var originalText = $button.text();
            
            // نمایش وضعیت در حال پردازش
            $button.text('در حال پاک کردن...').prop('disabled', true);
            
            // ارسال درخواست AJAX
            $.ajax({
                url: setia_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'setia_clear_cache',
                    nonce: $('input[name="setia_settings_nonce"]').val()
                },
                success: function(response) {
                    console.log('Cache clear response:', response);
                    
                    if (response.success) {
                        alert('کش با موفقیت پاک شد');
                    } else {
                        var errorMessage = response.data ? response.data.message : 'خطایی در پاک کردن کش رخ داد';
                        alert('خطا: ' + errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    alert('خطا در ارسال درخواست: ' + error);
                },
                complete: function() {
                    // بازگرداندن دکمه به حالت اولیه
                    $button.text(originalText).prop('disabled', false);
                }
            });
        }
    });
    
    // بازنشانی تنظیمات
    $('#reset-settings-btn').on('click', function() {
        if (confirm('آیا از بازنشانی تنظیمات به حالت پیش‌فرض اطمینان دارید؟ این عمل قابل بازگشت نیست!')) {
            var $button = $(this);
            var originalText = $button.text();
            
            // نمایش وضعیت در حال پردازش
            $button.text('در حال بازنشانی...').prop('disabled', true);
            
            // ارسال درخواست AJAX
            $.ajax({
                url: setia_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'setia_reset_settings',
                    nonce: $('input[name="setia_settings_nonce"]').val()
                },
                success: function(response) {
                    console.log('Settings reset response:', response);
                    
                    if (response.success) {
                        alert('تنظیمات با موفقیت بازنشانی شد');
                        // بارگذاری مجدد صفحه برای نمایش تنظیمات پیش‌فرض
                        window.location.reload();
                    } else {
                        var errorMessage = response.data ? response.data.message : 'خطایی در بازنشانی تنظیمات رخ داد';
                        alert('خطا: ' + errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    alert('خطا در ارسال درخواست: ' + error);
                },
                complete: function() {
                    // بازگرداندن دکمه به حالت اولیه
                    $button.text(originalText).prop('disabled', false);
                }
            });
        }
    });
});
</script>
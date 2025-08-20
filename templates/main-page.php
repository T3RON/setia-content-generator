<?php
// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    // اگر از طریق وردپرس دسترسی نداریم، سعی می‌کنیم به فایل wp-load.php دسترسی پیدا کنیم
    $wp_load_paths = array(
        '../../../../wp-load.php', // مسیر نسبی استاندارد برای پلاگین‌ها
        '../../../../../wp-load.php',
        '../../../../../../wp-load.php',
        '../../../wp-load.php',
        '../../wp-load.php',
        '../wp-load.php',
        './wp-load.php'
    );
    
    $wp_load_found = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $wp_load_found = true;
            break;
        }
    }
    
    // اگر هنوز ABSPATH تعریف نشده، خطای 403 نمایش می‌دهیم
    if (!defined('ABSPATH') || !$wp_load_found) {
        header('HTTP/1.1 200 OK');
        echo '<html><head><title>SETIA Content Generator</title></head>';
        echo '<body style="font-family: Tahoma, Arial; direction: rtl; text-align: center;">';
        echo '<h1>افزونه تولید محتوا SETIA</h1>';
        echo '<p>این افزونه باید از طریق پیشخوان وردپرس اجرا شود.</p>';
        echo '<p><a href="javascript:history.back()">بازگشت به صفحه قبل</a></p>';
        echo '</body></html>';
        exit;
    }
}

// دریافت تنظیمات برای مقادیر پیش‌فرض تصویر
$setia_settings = get_option('setia_settings', array());
$default_image_style = isset($setia_settings['default_image_style']) ? $setia_settings['default_image_style'] : 'realistic';
$default_aspect_ratio = isset($setia_settings['default_aspect_ratio']) ? $setia_settings['default_aspect_ratio'] : '16:9';
$plugin_version = isset($setia_settings['version']) ? $setia_settings['version'] : '1.0.0';
?>

<div class="setia-wrapper">
    <!-- هدر صفحه -->
    <div class="setia-header">
        <div class="setia-title">
            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/setia-logo.png'; ?>" alt="SETIA Content Generator" class="setia-logo">
            <h1>تولید محتوا با هوش مصنوعی</h1>
        </div>
        <div class="setia-version"><?php echo esc_html($plugin_version); ?></div>
    </div>
    
    <!-- بخش اعلان‌ها -->
    <div id="setia-alerts"></div>
    
    <!-- فرم ساده تولید محتوا -->
    <div class="setia-container">
        <div class="setia-card">
            <div class="setia-card-header">
                <h3 class="setia-card-title">تولید محتوا و تصویر</h3>
            </div>
            <div class="setia-card-description">
                فقط عنوان یا موضوع مورد نظر خود را وارد کنید، تمامی محتوا به صورت خودکار تولید خواهد شد
            </div>
            
            <form id="setia-simple-form" class="setia-form">
                <?php wp_nonce_field('setia-nonce', 'setia_nonce'); ?>
                
                <div class="setia-form-group">
                    <label for="prompt" class="setia-form-label">عنوان یا موضوع</label>
                    <input type="text" id="prompt" name="prompt" class="setia-form-input" placeholder="عنوان یا موضوع مورد نظر خود را وارد کنید..." required>
                    <div class="setia-form-help">مثال: فواید ورزش صبحگاهی و تاثیر آن بر سلامت روان</div>
                </div>
                
                <!-- فیلدهای مخفی برای تنظیمات پیش‌فرض -->
                <input type="hidden" id="keywords" name="keywords" value="خودکار">
                <input type="hidden" id="tone" name="tone" value="professional">
                <input type="hidden" id="length" name="length" value="medium">
                <input type="hidden" id="optimize_seo" name="optimize_seo" value="yes">
                <input type="hidden" id="generate_image" name="generate_image" value="yes">
                <input type="hidden" id="image_style" name="image_style" value="<?php echo esc_attr($default_image_style); ?>">
                <input type="hidden" id="aspect_ratio" name="aspect_ratio" value="<?php echo esc_attr($default_aspect_ratio); ?>">
                
                <div class="setia-form-group">
                    <button type="submit" class="setia-btn setia-btn-primary setia-full-width" id="setia-generate-btn">
                        <span class="setia-btn-text">تولید محتوا و تصویر</span>
                        <span class="dashicons dashicons-welcome-write-blog"></span>
                        <div class="setia-btn-loader"></div>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- بخش نمایش نتیجه -->
        <div class="setia-card" style="margin-top: 20px;">
            <div class="setia-card-header">
                <h3 class="setia-card-title">نتیجه تولید محتوا</h3>
                <div class="setia-content-tools">
                    <button type="button" class="setia-btn setia-btn-sm setia-btn-secondary setia-content-action" data-action="copy" disabled>
                        <span class="dashicons dashicons-clipboard"></span>
                        کپی
                    </button>
                    <button type="button" class="setia-btn setia-btn-sm setia-btn-secondary setia-content-action" data-action="save" disabled>
                        <span class="dashicons dashicons-saved"></span>
                        ذخیره
                    </button>
                    <button type="button" class="setia-btn setia-btn-sm setia-btn-primary setia-content-action" data-action="create_post" disabled>
                        <span class="dashicons dashicons-welcome-add-page"></span>
                        ایجاد نوشته
                    </button>
                </div>
            </div>
            
            <!-- نتیجه تولید محتوا -->
            <div id="content_preview" class="setia-content-preview">
                <div class="setia-placeholder">محتوای تولید شده اینجا نمایش داده خواهد شد</div>
            </div>
            
            <!-- نتیجه تولید تصویر -->
            <div id="image_preview" class="setia-image-preview" style="margin-top: 20px; text-align: center; display: none;">
                <h4>تصویر تولید شده</h4>
                <img id="generated_image" src="" alt="تصویر تولید شده" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // فرم تولید محتوا
    $('#setia-simple-form').on('submit', function(e) {
        e.preventDefault();
        
        const $button = $('#setia-generate-btn');
        $button.prop('disabled', true).addClass('loading');
        $button.find('.setia-btn-text').text('در حال تولید...');
        $button.find('.setia-btn-loader').show();
        
        // تنظیم کلمات کلیدی به صورت خودکار از عنوان
        const prompt = $('#prompt').val();
        // استخراج کلمات کلیدی از عنوان (5 کلمه اول)
        const keywords = prompt.split(' ').slice(0, 5).join(', ');
        $('#keywords').val(keywords);
        
        // ارسال درخواست به سرور
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'setia_generate_content',
                prompt: prompt,
                nonce: $('input[name="setia_nonce"]').val(),
                keywords: keywords,
                tone: $('#tone').val(),
                length: $('#length').val(),
                optimize_seo: 'yes',
                generate_image: 'yes',
                image_style: $('#image_style').val(),
                aspect_ratio: $('#aspect_ratio').val()
            },
            success: function(response) {
                if (response.success) {
                    // نمایش محتوا
                    $('#content_preview').html('<div class="setia-content">' + response.data.content + '</div>');
                    
                    // نمایش تصویر اگر تولید شده باشد
                    if (response.data.image_url) {
                        $('#generated_image').attr('src', response.data.image_url);
                        $('#image_preview').show();
                    }
                    
                    // فعال کردن دکمه‌های اقدام
                    $('.setia-content-action').prop('disabled', false);
                    
                    // ذخیره آی‌دی محتوا برای استفاده در دکمه‌های اقدام
                    if (response.data.content_id) {
                        $('#setia-simple-form').data('content-id', response.data.content_id);
                    }
                } else {
                    // نمایش خطا
                    $('#content_preview').html('<div class="setia-error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $('#content_preview').html('<div class="setia-error">خطا در اتصال به سرور. لطفا دوباره تلاش کنید.</div>');
            },
            complete: function() {
                // بازگرداندن وضعیت دکمه
                $button.prop('disabled', false).removeClass('loading');
                $button.find('.setia-btn-text').text('تولید محتوا و تصویر');
                $button.find('.setia-btn-loader').hide();
                
                // اسکرول به قسمت نتیجه
                $('html, body').animate({
                    scrollTop: $('#content_preview').offset().top - 50
                }, 500);
                
                // نمایش نوتیفیکیشن برای راهنمایی کاربر
                if ($('#image_preview').is(':visible')) {
                    showNotification('محتوا و تصویر با موفقیت تولید شدند. می‌توانید از دکمه‌های بالا برای کپی یا ایجاد نوشته استفاده کنید.');
                } else {
                    showNotification('محتوا با موفقیت تولید شد، اما تولید تصویر با خطا مواجه شد.');
                }
            }
        });
    });
    
    // دکمه کپی محتوا
    $(document).on('click', '.setia-content-action[data-action="copy"]', function() {
        const content = $('.setia-content').html();
        const tempTextarea = $('<textarea>').val(content).appendTo('body').select();
        document.execCommand('copy');
        tempTextarea.remove();
        alert('محتوا با موفقیت کپی شد');
    });
    
    // دکمه ایجاد نوشته
    $(document).on('click', '.setia-content-action[data-action="create_post"]', function() {
        const contentId = $('#setia-simple-form').data('content-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'setia_publish_content',
                nonce: $('input[name="setia_nonce"]').val(),
                content_id: contentId
            },
            success: function(response) {
                if (response.success && response.data.edit_url) {
                    alert('نوشته با موفقیت ایجاد شد');
                    window.open(response.data.edit_url, '_blank');
                } else {
                    alert(response.data.message || 'خطا در ایجاد نوشته');
                }
            }
        });
    });
    
    // نمایش پیام اطلاع‌رسانی
    function showNotification(message, type = 'success') {
        const $notification = $('<div class="setia-notification ' + type + '"></div>').text(message);
        $('#setia-alerts').append($notification);
        
        // نمایش پیام با انیمیشن
        setTimeout(function() {
            $notification.addClass('show');
        }, 10);
        
        // حذف پیام بعد از 5 ثانیه
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 5000);
    }
});
</script>

<style>
.setia-full-width {
    width: 100%;
}
.setia-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    padding: 20px;
}
.setia-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.setia-card-title {
    margin: 0;
    font-size: 18px;
}
.setia-form-group {
    margin-bottom: 20px;
}
.setia-form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}
.setia-form-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: inherit;
    font-size: 15px;
}
.setia-btn {
    position: relative;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: bold;
    transition: all 0.2s;
}
.setia-btn-primary {
    background: #2271b1;
    color: white;
}
.setia-btn-primary:hover {
    background: #135e96;
}
.setia-btn-loader {
    display: none;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s infinite linear;
}
.loading .setia-btn-loader {
    display: block;
}
@keyframes spin {
    to {transform: rotate(360deg);}
}
.setia-error {
    color: #d32f2f;
    padding: 15px;
    background: #fff8f8;
    border-left: 4px solid #d32f2f;
}
.setia-content {
    padding: 15px;
    line-height: 1.7;
}
.setia-placeholder {
    padding: 20px;
    text-align: center;
    color: #777;
    font-style: italic;
}
.setia-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    background: #4CAF50;
    color: white;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 9999;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
    max-width: 300px;
}
.setia-notification.show {
    opacity: 1;
    transform: translateY(0);
}
.setia-notification.error {
    background: #F44336;
}
.setia-notification.warning {
    background: #FF9800;
}
</style>
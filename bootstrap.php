<?php
/**
 * فایل بوت استرپ برای بارگذاری وردپرس
 * SETIA Content Generator Plugin
 */

// اگر ABSPATH تعریف نشده، سعی می‌کنیم وردپرس را بارگذاری کنیم
if (!defined('ABSPATH')) {
    // مسیرهای احتمالی برای فایل wp-load.php
    $wp_load_paths = array(
        '../../../wp-load.php', // مسیر نسبی استاندارد برای پلاگین‌ها
        '../../../../wp-load.php',
        '../../../../../wp-load.php',
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

// تعریف ثابت‌های مورد نیاز
if (!defined('SETIA_PLUGIN_DIR')) {
    define('SETIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('SETIA_PLUGIN_URL')) {
    define('SETIA_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// بارگذاری فایل‌های مورد نیاز
require_once(SETIA_PLUGIN_DIR . 'setia-content-generator.php');

// اطمینان از اینکه کلاس اصلی افزونه بارگذاری شده است
if (!class_exists('SETIA_Content_Generator')) {
    echo 'خطا: کلاس اصلی افزونه بارگذاری نشد.';
    exit;
}

// ایجاد نمونه از کلاس اصلی اگر قبلاً ایجاد نشده است
global $setia_content_generator;
if (!isset($setia_content_generator) || !is_object($setia_content_generator)) {
    $setia_content_generator = new SETIA_Content_Generator();
}

// بازگرداندن نمونه کلاس اصلی
return $setia_content_generator; 
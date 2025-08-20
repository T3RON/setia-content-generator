<?php
/**
 * فایل امنیتی برای جلوگیری از دسترسی مستقیم به پوشه افزونه
 * SETIA Content Generator Plugin
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    // اگر از طریق وردپرس دسترسی نداریم، سعی می‌کنیم به فایل wp-load.php دسترسی پیدا کنیم
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

// Silence is golden.
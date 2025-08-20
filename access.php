<?php
/**
 * صفحه تست دسترسی به افزونه
 * SETIA Content Generator Plugin
 */

// بارگذاری بوت استرپ
if (file_exists('./bootstrap.php')) {
    require_once('./bootstrap.php');
} else {
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
}

// اگر ABSPATH تعریف نشده، پیام خطا نمایش می‌دهیم
if (!defined('ABSPATH')) {
    header('HTTP/1.1 200 OK');
    echo '<html><head><title>SETIA Content Generator</title></head>';
    echo '<body style="font-family: Tahoma, Arial; direction: rtl; text-align: center;">';
    echo '<h1>افزونه تولید محتوا SETIA</h1>';
    echo '<p>خطا: وردپرس بارگذاری نشد.</p>';
    echo '<p><a href="javascript:history.back()">بازگشت به صفحه قبل</a></p>';
    echo '</body></html>';
    exit;
}

// نمایش صفحه موفقیت
header('HTTP/1.1 200 OK');
echo '<html><head><title>SETIA Content Generator - تست دسترسی</title>';
echo '<style>
    body {
        font-family: Tahoma, Arial;
        direction: rtl;
        text-align: center;
        background-color: #f5f5f5;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 800px;
        margin: 50px auto;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h1 {
        color: #2c3e50;
    }
    .success {
        color: #27ae60;
        font-weight: bold;
    }
    .button {
        display: inline-block;
        background: #3498db;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 20px;
    }
    .info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
        text-align: right;
    }
</style>';
echo '</head>';
echo '<body>';
echo '<div class="container">';
echo '<h1>افزونه تولید محتوا SETIA</h1>';
echo '<p class="success">✓ دسترسی با موفقیت انجام شد</p>';
echo '<p>افزونه به درستی نصب شده و قابل دسترسی است.</p>';

echo '<div class="info">';
echo '<h3>اطلاعات سیستم:</h3>';
echo '<p><strong>نسخه وردپرس:</strong> ' . get_bloginfo('version') . '</p>';
echo '<p><strong>نسخه PHP:</strong> ' . phpversion() . '</p>';
echo '<p><strong>مسیر افزونه:</strong> ' . plugin_dir_path(__FILE__) . '</p>';
echo '<p><strong>URL افزونه:</strong> ' . plugin_dir_url(__FILE__) . '</p>';
echo '</div>';

echo '<a href="' . admin_url('admin.php?page=setia-content-generator') . '" class="button">رفتن به پیشخوان افزونه</a>';
echo '</div>';
echo '</body></html>';
exit; 
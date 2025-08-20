<?php
/**
 * راهنمای فعال‌سازی بهبودهای بخش تنظیمات افزونه SETIA
 * 
 * این فایل راهنمای جامع فعال‌سازی تمام بهبودهای اعمال شده بر روی بخش تنظیمات افزونه است
 */

// فعال‌سازی خودکار بهبودها در هنگام فعال‌سازی افزونه
add_action('plugins_loaded', 'setia_activate_settings_enhancements');

function setia_activate_settings_enhancements() {
    // بررسی اینکه آیا افزونه SETIA فعال است
    if (!defined('SETIA_VERSION')) {
        return;
    }
    
    // فعال‌سازی فایل‌های بهبود یافته
    require_once plugin_dir_path(__FILE__) . 'settings-enhancements.php';
    
    // اضافه کردن اکشن برای بارگذاری اسکریپت‌های پیشرفته
    add_action('admin_enqueue_scripts', 'setia_enqueue_settings_enhancements');
    
    // نمایش پیغام موفقیت
    add_action('admin_notices', 'setia_settings_enhancements_success_notice');
}

function setia_enqueue_settings_enhancements($hook) {
    // بررسی اینکه در صفحه تنظیمات افزونه هستیم
    if ($hook !== 'toplevel_page_setia-settings') {
        return;
    }
    
    // بارگذاری اسکریپت‌های پیشرفته
    wp_enqueue_script(
        'setia-settings-enhancements',
        plugin_dir_url(__FILE__) . 'assets/js/setia-settings-enhancements.js',
        array('jquery'),
        SETIA_VERSION . '.' . time(),
        true
    );
    
    // اضافه کردن متغیرهای جاوااسکریپت
    wp_localize_script('setia-settings-enhancements', 'setia_enhancements', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('setia_enhancements_nonce'),
        'rtl' => is_rtl() ? 'true' : 'false',
        'messages' => array(
            'saving' => __('در حال ذخیره...', 'setia'),
            'saved' => __('با موفقیت ذخیره شد', 'setia'),
            'error' => __('خطا در ذخیره‌سازی', 'setia'),
            'testing' => __('در حال تست...', 'setia'),
            'test_success' => __('اتصال موفق بود', 'setia'),
            'test_error' => __('خطا در اتصال', 'setia')
        )
    ));
}

function setia_settings_enhancements_success_notice() {
    if (get_transient('setia_settings_enhancements_activated')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>✅ بهبودهای بخش تنظیمات با موفقیت فعال شد!</strong></p>
            <p>ویژگی‌های جدید: ذخیره خودکار، اعتبارسنجی زمان واقعی، پشتیبانی کامل فارسی، ابزارهای پیشرفته و رابط کاربری بهبود یافته.</p>
        </div>
        <?php
        delete_transient('setia_settings_enhancements_activated');
    }
}

// تنظیم transient در هنگال فعال‌سازی
register_activation_hook(__FILE__, function() {
    set_transient('setia_settings_enhancements_activated', true, 30);
});

// افزودن لینک مستقیم به تنظیمات در منوی پیشخوان
add_action('admin_menu', 'setia_add_settings_link');

function setia_add_settings_link() {
    global $submenu;
    
    if (isset($submenu['setia-settings'])) {
        $submenu['setia-settings'][] = array(
            'راهنمای تنظیمات',
            'manage_options',
            admin_url('admin.php?page=setia-settings&tab=system-status'),
            'راهنمای تنظیمات'
        );
    }
}

// افزودن ستون وضعیت در لیست افزونه‌ها
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'setia_add_settings_action_links');

function setia_add_settings_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=setia-settings') . '">تنظیمات</a>';
    array_unshift($links, $settings_link);
    
    $guide_link = '<a href="' . admin_url('admin.php?page=setia-settings&tab=system-status') . '" style="color: #0073aa; font-weight: bold;">راهنما</a>';
    array_unshift($links, $guide_link);
    
    return $links;
}

// بررسی سلامت سیستم
add_action('wp_ajax_setia_system_health_check', 'setia_system_health_check');

function setia_system_health_check() {
    check_ajax_referer('setia_enhancements_nonce', 'nonce');
    
    $health = array(
        'php_version' => phpversion(),
        'wp_version' => get_bloginfo('version'),
        'plugin_version' => defined('SETIA_VERSION') ? SETIA_VERSION : 'unknown',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'curl_available' => function_exists('curl_version'),
        'allow_url_fopen' => ini_get('allow_url_fopen'),
        'ssl_available' => extension_loaded('openssl'),
        'json_available' => extension_loaded('json'),
        'mbstring_available' => extension_loaded('mbstring'),
        'status' => 'good'
    );
    
    // بررسی مشکلات احتمالی
    $issues = array();
    
    if (version_compare($health['php_version'], '7.4', '<')) {
        $issues[] = 'نسخه PHP شما قدیمی است. توصیه می‌شود به PHP 7.4 یا بالاتر ارتقا دهید.';
        $health['status'] = 'warning';
    }
    
    if (!$health['curl_available']) {
        $issues[] = 'ماژول cURL فعال نیست. این ماژول برای اتصال به APIها لازم است.';
        $health['status'] = 'error';
    }
    
    if (!$health['ssl_available']) {
        $issues[] = 'SSL برای اتصالات امن لازم است.';
        $health['status'] = 'error';
    }
    
    $health['issues'] = $issues;
    
    wp_send_json($health);
}
<?php
// بررسی تداخل handlers

echo "=== بررسی Handlers ثبت شده ===\n\n";

// تقلید از WordPress globals
global $wp_filter;

// شبیه‌سازی add_action
function debug_add_action($hook, $callback, $priority = 10) {
    global $debug_actions;
    if (!isset($debug_actions[$hook])) {
        $debug_actions[$hook] = array();
    }
    $debug_actions[$hook][] = array(
        'callback' => $callback,
        'priority' => $priority
    );
}

$debug_actions = array();

// شبیه‌سازی ثبت handlers
echo "شبیه‌سازی ثبت handlers...\n";

// از ajax-handlers.php
debug_add_action('wp_ajax_setia_save_settings', 'SETIA_Ajax_Handlers::save_settings', 10);

// از class-settings-manager.php  
debug_add_action('wp_ajax_setia_save_settings', 'SETIA_Settings_Manager::ajax_save_settings', 10);

// از setia-content-generator.php
debug_add_action('wp_ajax_setia_save_settings', 'SETIA_Content_Generator->ajax_handlers->save_settings', 10);

echo "\nHandlers ثبت شده برای wp_ajax_setia_save_settings:\n";
if (isset($debug_actions['wp_ajax_setia_save_settings'])) {
    foreach ($debug_actions['wp_ajax_setia_save_settings'] as $index => $handler) {
        echo ($index + 1) . ". " . 
             (is_array($handler['callback']) ? 
              (is_object($handler['callback'][0]) ? get_class($handler['callback'][0]) : $handler['callback'][0]) . 
              '::' . $handler['callback'][1] : 
              $handler['callback']) . 
             " (اولویت: " . $handler['priority'] . ")\n";
    }
} else {
    echo "هیچ handler ثبت نشده!\n";
}

echo "\n⚠️  مشکل: چندین handler برای همان اکشن ثبت شده است!\n";
echo "راه حل: باید یکی از آنها را غیرفعال کنیم.\n";

echo "\n=== بررسی تنظیمات فعلی ===\n";

// اگر فایل تنظیمات وجود دارد، آن را بخوانیم
$settings_file = 'setia_settings_backup.json';
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    echo "تنظیمات موجود:\n";
    print_r($settings);
} else {
    echo "فایل تنظیمات موجود نیست.\n";
}

echo "\n✅ بررسی handlers تمام شد!\n";
?>
<?php
// دیباگ فایل برای بررسی مشکلات AJAX

// تنظیم هدرها برای جلوگیری از کش
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: text/html; charset=utf-8');

// نمایش اطلاعات سرور و درخواست
echo "<h1>SETIA Debug Info</h1>";
echo "<h2>Server Info</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";

echo "<h2>WordPress Info</h2>";
echo "<pre>";
if (file_exists('./wp-load.php')) {
    require_once('./wp-load.php');
    echo "WordPress Version: " . get_bloginfo('version') . "\n";
    echo "Active Theme: " . wp_get_theme()->get('Name') . "\n";
    echo "SETIA Settings: " . print_r(get_option('setia_settings'), true) . "\n";
    echo "AJAX URL: " . admin_url('admin-ajax.php') . "\n";
    
    // بررسی اکشن‌های AJAX
    global $wp_filter;
    echo "AJAX Actions:\n";
    if (isset($wp_filter['wp_ajax_setia_save_settings'])) {
        echo "setia_save_settings is registered\n";
    } else {
        echo "setia_save_settings is NOT registered\n";
    }
    
    if (isset($wp_filter['wp_ajax_setia_test_image_generation'])) {
        echo "setia_test_image_generation is registered\n";
    } else {
        echo "setia_test_image_generation is NOT registered\n";
    }
} else {
    echo "WordPress core not found\n";
}
echo "</pre>";

// بررسی تنظیمات فعلی
echo "<h2>Current Settings</h2>";
echo "<pre>";
$settings = get_option('setia_settings', array());
print_r($settings);
echo "</pre>";

// تست ذخیره تنظیمات
echo "<h2>Test Settings Save</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='test_save_settings' value='1'>";
echo "<label>Debug Mode: <input type='checkbox' name='debug_mode' value='yes'></label><br>";
echo "<button type='submit'>Save Test Setting</button>";
echo "</form>";

if (isset($_POST['test_save_settings'])) {
    echo "<h3>Save Settings Test Results</h3>";
    echo "<pre>";
    
    // دریافت تنظیمات فعلی
    $settings = get_option('setia_settings', array());
    
    // بروزرسانی تنظیمات
    $settings['debug_mode'] = isset($_POST['debug_mode']) ? 'yes' : 'no';
    
    // ذخیره تنظیمات
    $result = update_option('setia_settings', $settings);
    
    echo "Update Result: " . ($result ? 'Success' : 'Failed') . "\n";
    echo "New Settings: \n";
    print_r(get_option('setia_settings'));
    
    echo "</pre>";
}

// تست اکشن‌های AJAX
echo "<h2>AJAX Functions Test</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='test_ajax_functions' value='1'>";
echo "<button type='submit'>Test AJAX Functions</button>";
echo "</form>";

if (isset($_POST['test_ajax_functions'])) {
    echo "<h3>AJAX Functions Test Results</h3>";
    echo "<pre>";
    
    // بررسی تابع save_settings
    if (class_exists('SETIA_Ajax_Handlers')) {
        echo "SETIA_Ajax_Handlers class exists\n";
        
        // بررسی وجود تابع save_settings
        $ajax_handlers = new SETIA_Ajax_Handlers(null);
        if (method_exists($ajax_handlers, 'save_settings')) {
            echo "save_settings method exists\n";
        } else {
            echo "save_settings method does NOT exist\n";
        }
        
        // بررسی وجود تابع handle_test_image_generation
        if (method_exists($ajax_handlers, 'handle_test_image_generation')) {
            echo "handle_test_image_generation method exists\n";
        } else {
            echo "handle_test_image_generation method does NOT exist\n";
        }
    } else {
        echo "SETIA_Ajax_Handlers class does NOT exist\n";
    }
    
    echo "</pre>";
}

// بررسی متغیرهای جاوااسکریپت
echo "<h2>JavaScript Variables</h2>";
echo "<pre>";
echo "WordPress AJAX URL: " . admin_url('admin-ajax.php') . "\n";
echo "Settings Nonce: " . wp_create_nonce('setia_settings_nonce') . "\n";
echo "</pre>";

// بررسی ساختار فرم تنظیمات
echo "<h2>Form Structure Test</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='test_form_structure' value='1'>";
echo "<button type='submit'>Test Form Structure</button>";
echo "</form>";

if (isset($_POST['test_form_structure'])) {
    echo "<h3>Form Structure Test Results</h3>";
    echo "<pre>";
    
    // بررسی فایل قالب تنظیمات
    $settings_template = file_exists('./templates/settings-page.php');
    echo "Settings Template Exists: " . ($settings_template ? 'Yes' : 'No') . "\n";
    
    if ($settings_template) {
        $content = file_get_contents('./templates/settings-page.php');
        
        // بررسی فیلد نانس
        $has_nonce_field = strpos($content, 'name="settings_nonce"') !== false;
        echo "Has Nonce Field: " . ($has_nonce_field ? 'Yes' : 'No') . "\n";
        
        // بررسی چک باکس حالت دیباگ
        $has_debug_checkbox = strpos($content, 'name="debug_mode"') !== false;
        echo "Has Debug Mode Checkbox: " . ($has_debug_checkbox ? 'Yes' : 'No') . "\n";
        
        // بررسی کد جاوااسکریپت
        $has_ajax_code = strpos($content, 'action: \'setia_save_settings\'') !== false;
        echo "Has AJAX Save Code: " . ($has_ajax_code ? 'Yes' : 'No') . "\n";
    }
    
    echo "</pre>";
} 
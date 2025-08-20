<?php
/**
 * تست بهبودهای اعمال شده در سیستم تولید محتوا و تصویر
 * این فایل برای تست و بررسی عملکرد بهبودهای ایجاد شده استفاده می‌شود
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

class SETIA_Improvement_Tester {
    
    private $content_generator;
    
    public function __construct() {
        // بارگذاری کلاس تولید محتوا
        if (class_exists('SETIA_Content_Generator_Base')) {
            $this->content_generator = new SETIA_Content_Generator_Base();
        }
        
        // ثبت اکشن‌های تست
        add_action('admin_menu', array($this, 'add_test_page'));
        add_action('wp_ajax_setia_test_improvements', array($this, 'run_tests'));
    }
    
    /**
     * افزودن صفحه تست به منوی مدیریت
     */
    public function add_test_page() {
        add_submenu_page(
            'setia-content-generator',
            'تست بهبودها',
            'تست بهبودها',
            'manage_options',
            'setia-test-improvements',
            array($this, 'render_test_page')
        );
    }
    
    /**
     * نمایش صفحه تست
     */
    public function render_test_page() {
        if (!current_user_can('manage_options')) {
            wp_die('شما اجازه دسترسی به این صفحه را ندارید.');
        }
        
        ?>
        <div class="wrap">
            <h1>تست بهبودهای سیستم تولید محتوا و تصویر</h1>
            <p>این صفحه برای بررسی عملکرد بهبودهای اعمال شده در سیستم استفاده می‌شود.</p>
            
            <div class="card">
                <h2>آزمایش API ها</h2>
                <button id="test-apis" class="button button-primary">تست اتصال API ها</button>
                <div id="api-results" class="test-results"></div>
            </div>
            
            <div class="card">
                <h2>آزمایش تولید محتوای Fallback</h2>
                <button id="test-fallback-content" class="button button-primary">تست محتوای پشتیبان</button>
                <div id="fallback-content-results" class="test-results"></div>
            </div>
            
            <div class="card">
                <h2>آزمایش تولید تصویر Fallback</h2>
                <button id="test-fallback-image" class="button button-primary">تست تصویر پشتیبان</button>
                <div id="fallback-image-results" class="test-results"></div>
            </div>
            
            <div class="card">
                <h2>آزمایش سیستم لاگ و خطایابی</h2>
                <button id="test-logging" class="button button-primary">تست سیستم لاگ</button>
                <div id="logging-results" class="test-results"></div>
            </div>
            
            <div class="card">
                <h2>گزارش وضعیت سیستم</h2>
                <button id="system-report" class="button button-primary">ایجاد گزارش سیستم</button>
                <div id="system-report-results" class="test-results"></div>
            </div>
        </div>
        
        <style>
            .card {
                background: white;
                padding: 20px;
                margin: 20px 0;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .test-results {
                margin-top: 15px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 3px;
                min-height: 50px;
            }
            .success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .warning {
                background: #fff3cd;
                color: #856404;
                border: 1px solid #ffeaa7;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-apis').click(function() {
                $(this).prop('disabled', true).text('در حال تست...');
                $('#api-results').html('<p>در حال تست اتصال API ها...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'setia_test_improvements',
                        test_type: 'apis',
                        nonce: '<?php echo wp_create_nonce('setia-test-nonce'); ?>'
                    },
                    success: function(response) {
                        $('#api-results').html(response.data.html);
                    },
                    error: function() {
                        $('#api-results').html('<p class="error">خطا در تست API ها</p>');
                    },
                    complete: function() {
                        $('#test-apis').prop('disabled', false).text('تست اتصال API ها');
                    }
                });
            });
            
            $('#test-fallback-content').click(function() {
                $(this).prop('disabled', true).text('در حال تست...');
                $('#fallback-content-results').html('<p>در حال تست محتوای پشتیبان...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'setia_test_improvements',
                        test_type: 'fallback_content',
                        nonce: '<?php echo wp_create_nonce('setia-test-nonce'); ?>'
                    },
                    success: function(response) {
                        $('#fallback-content-results').html(response.data.html);
                    },
                    error: function() {
                        $('#fallback-content-results').html('<p class="error">خطا در تست محتوای پشتیبان</p>');
                    },
                    complete: function() {
                        $('#test-fallback-content').prop('disabled', false).text('تست محتوای پشتیبان');
                    }
                });
            });
            
            $('#test-fallback-image').click(function() {
                $(this).prop('disabled', true).text('در حال تست...');
                $('#fallback-image-results').html('<p>در حال تست تصویر پشتیبان...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'setia_test_improvements',
                        test_type: 'fallback_image',
                        nonce: '<?php echo wp_create_nonce('setia-test-nonce'); ?>'
                    },
                    success: function(response) {
                        $('#fallback-image-results').html(response.data.html);
                    },
                    error: function() {
                        $('#fallback-image-results').html('<p class="error">خطا در تست تصویر پشتیبان</p>');
                    },
                    complete: function() {
                        $('#test-fallback-image').prop('disabled', false).text('تست تصویر پشتیبان');
                    }
                });
            });
            
            $('#system-report').click(function() {
                $(this).prop('disabled', true).text('در حال ایجاد گزارش...');
                $('#system-report-results').html('<p>در حال ایجاد گزارش سیستم...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'setia_test_improvements',
                        test_type: 'system_report',
                        nonce: '<?php echo wp_create_nonce('setia-test-nonce'); ?>'
                    },
                    success: function(response) {
                        $('#system-report-results').html(response.data.html);
                    },
                    error: function() {
                        $('#system-report-results').html('<p class="error">خطا در ایجاد گزارش</p>');
                    },
                    complete: function() {
                        $('#system-report').prop('disabled', false).text('ایجاد گزارش سیستم');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * اجرای تست ها
     */
    public function run_tests() {
        check_ajax_referer('setia-test-nonce', 'nonce');
        
        $test_type = sanitize_text_field($_POST['test_type'] ?? '');
        $html = '';
        
        switch ($test_type) {
            case 'apis':
                $html = $this->test_api_connections();
                break;
                
            case 'fallback_content':
                $html = $this->test_fallback_content();
                break;
                
            case 'fallback_image':
                $html = $this->test_fallback_image();
                break;
                
            case 'system_report':
                $html = $this->generate_system_report();
                break;
                
            default:
                $html = '<p class="error">نوع تست نامشخص است</p>';
        }
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * تست اتصال API ها
     */
    private function test_api_connections() {
        $html = '<h3>نتایج تست اتصال API ها:</h3>';
        
        // تست Gemini API
        if (class_exists('SETIA_Content_Generator_Base')) {
            $generator = new SETIA_Content_Generator_Base();
            
            $html .= '<h4>Google Gemini API:</h4>';
            $test_result = $generator->generate_text('سلام، این یک تست است');
            
            if ($test_result['success']) {
                $html .= '<p class="success">✅ اتصال به Gemini API موفقیت‌آمیز بود</p>';
            } else {
                $html .= '<p class="error">❌ خطا در اتصال به Gemini API: ' . esc_html($test_result['error']) . '</p>';
            }
            
            // تست Imagine Art API
            $html .= '<h4>Imagine Art API:</h4>';
            $image_result = $generator->generate_image('یک گل زیبا', array('style' => 'realistic'));
            
            if ($image_result['success']) {
                $html .= '<p class="success">✅ اتصال به Imagine Art API موفقیت‌آمیز بود</p>';
                $html .= '<img src="' . esc_url($image_result['image_url']) . '" style="max-width: 200px;" alt="تست تصویر">';
            } else {
                $html .= '<p class="error">❌ خطا در اتصال به Imagine Art API: ' . esc_html($image_result['error']) . '</p>';
                
                // تست Unsplash fallback
                $fallback_image = $generator->get_default_image('تست تصویر');
                if ($fallback_image['image_url']) {
                    $html .= '<p class="warning">⚠️ استفاده از تصویر پشتیبان Unsplash</p>';
                    $html .= '<img src="' . esc_url($fallback_image['image_url']) . '" style="max-width: 200px;" alt="تست تصویر پشتیبان">';
                }
            }
        }
        
        return $html;
    }
    
    /**
     * تست محتوای پشتیبان
     */
    private function test_fallback_content() {
        $html = '<h3>نتایج تست محتوای پشتیبان:</h3>';
        
        if (class_exists('SETIA_Content_Generator_Base')) {
            $generator = new SETIA_Content_Generator_Base();
            
            $test_data = array(
                'topic' => 'تکنولوژی هوش مصنوعی',
                'keywords' => 'هوش مصنوعی، تکنولوژی، آینده',
                'tone' => 'حرفه‌ای',
                'length' => 'متوسط'
            );
            
            $fallback_content = $generator->generate_fallback_content('تست محتوای پشتیبان', $test_data);
            
            if ($fallback_content) {
                $html .= '<p class="success">✅ محتوای پشتیبان با موفقیت تولید شد</p>';
                $html .= '<div style="border: 1px solid #ddd; padding: 10px; margin: 10px 0;">';
                $html .= '<h4>محتوای تولید شده:</h4>';
                $html .= '<p>' . wp_kses_post(substr($fallback_content, 0, 500)) . '...</p>';
                $html .= '</div>';
            } else {
                $html .= '<p class="error">❌ خطا در تولید محتوای پشتیبان</p>';
            }
        }
        
        return $html;
    }
    
    /**
     * تست تصویر پشتیبان
     */
    private function test_fallback_image() {
        $html = '<h3>نتایج تست تصویر پشتیبان:</h3>';
        
        if (class_exists('SETIA_Content_Generator_Base')) {
            $generator = new SETIA_Content_Generator_Base();
            
            $fallback_image = $generator->get_default_image('تست تصویر پشتیبان');
            
            if ($fallback_image['image_url']) {
                $html .= '<p class="success">✅ تصویر پشتیبان با موفقیت تولید شد</p>';
                $html .= '<img src="' . esc_url($fallback_image['image_url']) . '" style="max-width: 300px;" alt="تست تصویر پشتیبان">';
                $html .= '<p>آدرس تصویر: <code>' . esc_html($fallback_image['image_url']) . '</code></p>';
            } else {
                $html .= '<p class="error">❌ خطا در تولید تصویر پشتیبان</p>';
            }
        }
        
        return $html;
    }
    
    /**
     * ایجاد گزارش کامل وضعیت سیستم
     */
    private function generate_system_report() {
        $html = '<h3>گزارش وضعیت سیستم:</h3>';
        
        // اطلاعات پایه
        $html .= '<h4>اطلاعات سیستم:</h4>';
        $html .= '<ul>';
        $html .= '<li>نسخه وردپرس: ' . get_bloginfo('version') . '</li>';
        $html .= '<li>نسخه PHP: ' . phpversion() . '</li>';
        $html .= '<li>نسخه افزونه: 1.0.0</li>';
        $html .= '</ul>';
        
        // تنظیمات API
        $html .= '<h4>تنظیمات API:</h4>';
        $html .= '<ul>';
        
        $settings = get_option('setia_settings', array());
        $html .= '<li>کلید Gemini API: ' . (!empty($settings['gemini_api_key']) ? '✅ تنظیم شده' : '❌ تنظیم نشده') . '</li>';
        $html .= '<li>کلید Imagine Art API: ' . (!empty($settings['imagine_art_api_key']) ? '✅ تنظیم شده' : '❌ تنظیم نشده') . '</li>';
        $html .= '</ul>';
        
        // بررسی فایل‌های مورد نیاز
        $html .= '<h4>وضعیت فایل‌ها:</h4>';
        $html .= '<ul>';
        
        $required_files = array(
            'class-content-generator.php' => '/includes/class-content-generator.php',
            'ajax-handlers.php' => '/ajax-handlers.php',
            'fix-issues.php' => '/fix-issues.php'
        );
        
        foreach ($required_files as $name => $path) {
            $full_path = plugin_dir_path(__FILE__) . $path;
            $html .= '<li>' . $name . ': ' . (file_exists($full_path) ? '✅ موجود' : '❌ موجود نیست') . '</li>';
        }
        
        $html .= '</ul>';
        
        // بررسی لاگ ها
        $html .= '<h4>آخرین لاگ ها:</h4>';
        $html .= '<pre style="background: #f9f9f9; padding: 10px; max-height: 200px; overflow-y: scroll;">';
        
        $log_file = plugin_dir_path(__FILE__) . 'setia-debug.log';
        if (file_exists($log_file)) {
            $logs = array_slice(array_reverse(file($log_file)), 0, 20);
            foreach ($logs as $log) {
                $html .= esc_html($log) . "\n";
            }
        } else {
            $html .= 'فایل لاگ یافت نشد';
        }
        
        $html .= '</pre>';
        
        return $html;
    }
}

// راه‌اندازی تستر
new SETIA_Improvement_Tester();
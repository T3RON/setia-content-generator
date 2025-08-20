<?php
/*
Plugin Name: SETIA Content Generator
Description: افزونه تولید خودکار محتوا با کمک هوش مصنوعی (Gemini و Gemma) - با استایل Google Earth
Version: 1.0
Author: SETIA Team
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

// تعریف ثابت‌های مورد نیاز
if (!defined('SETIA_PLUGIN_DIR')) {
    define('SETIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('SETIA_PLUGIN_URL')) {
    define('SETIA_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// کلاس fallback برای Parsedown
if (!class_exists('SETIA_Fallback_Parsedown')) {
    class SETIA_Fallback_Parsedown {
        public function text($text) {
            return wpautop($text);
        }
    }
}

// کلاس اصلی افزونه
class SETIA_Content_Generator {
    
    // نسخه افزونه
    const VERSION = '1.0.0';
    
    // متغیرهای خصوصی برای کلیدهای API
    private $gemini_api_key;
    private $gemma_api_key;
    public $imagine_art_api_key;
    
    // تنظیمات پیش‌فرض
    private $default_settings = [
        'gemini_api_key' => '',
        'gemma_api_key' => '',
        'imagine_art_api_key' => '',
        'default_tone' => 'عادی',
        'default_length' => 'متوسط',
        'enable_seo' => 'yes',
        'enable_image_generation' => 'yes',
        'default_image_style' => 'realistic',
        'default_aspect_ratio' => '16:9'
    ];
    
    // نمونه کلاس مدیریت AJAX
    private $ajax_handlers;
    
    // راه‌اندازی افزونه
    public function __construct() {
        // بارگذاری تنظیمات
        $this->load_settings();
        
        // اضافه کردن دیتابیس لازم هنگام فعال‌سازی
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        
        // منوی ادمین
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // ثبت قلاب‌های اکشن و فیلتر
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // اضافه کردن فونت ایران‌سنس به head صفحه با اولویت بالا
        add_action('admin_head', array($this, 'add_iransans_font_inline'), 5);
        
        // اضافه کردن اسکیما به head صفحه
        add_action('wp_head', array($this, 'add_schema_to_head'), 10);
        
        // بارگذاری Parsedown
        $this->include_parsedown();

        // بارگذاری کلاس‌های لازم
        $this->load_dependencies();

        /**
         * ثبت اکشن‌های AJAX
         */
        $this->register_ajax_handlers();
    }
    
    // اضافه کردن فونت ایران‌سنس به صورت مستقیم در head صفحه
    public function add_iransans_font_inline() {
        ?>
        <style type="text/css">
            @font-face {
                font-family: 'IRANSans';
                font-style: normal;
                font-weight: normal;
                src: url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb.eot');
                src: url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb.eot?#iefix') format('embedded-opentype'),
                     url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb.woff2') format('woff2'),
                     url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb.woff') format('woff'),
                     url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb.ttf') format('truetype');
            }
            
            @font-face {
                font-family: 'IRANSans';
                font-style: normal;
                font-weight: bold;
                src: url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb_Bold.eot');
                src: url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb_Bold.eot?#iefix') format('embedded-opentype'),
                     url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb_Bold.woff2') format('woff2'),
                     url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb_Bold.woff') format('woff'),
                     url('<?php echo plugin_dir_url(__FILE__); ?>assets/fonts/IRANSansWeb_Bold.ttf') format('truetype');
            }
            
            /* فورس کردن فونت ایران‌سنس برای تمام عناصر پلاگین با اولویت فوق‌العاده بالا */

            .wrap .setia-main-container *:not(.dashicons):not([class^="dashicons-"]):not(.ab-icon):not(.ab-item):before,
            .wrap .setia-schema-wrap *:not(.dashicons):not([class^="dashicons-"]):not(.ab-icon):not(.ab-item):before,
            .wrap .setia-history-wrap *:not(.dashicons):not([class^="dashicons-"]):not(.ab-icon):not(.ab-item):before,

            .wrap .setia-main-container,
            .wrap .setia-schema-wrap,
            .wrap .setia-history-wrap,

            .wrap .setia-section,
            .wrap .setia-card,
            .wrap .setia-modal,
            .wrap .setia-button,
            .wrap .setia-input,
            .wrap .setia-select,
            .wrap .setia-form-group,
            .wrap .setia-form-group *:not(.dashicons):not([class^="dashicons-"]) {
                font-family: 'IRANSans', Tahoma, sans-serif !important;
            }
            
            /* تنظیمات RTL و فونت */
            body.rtl .wrap h1, 
            body.rtl .wrap h2, 
            body.rtl .wrap h3, 
            body.rtl .wrap h4, 
            body.rtl .wrap h5, 
            body.rtl .wrap h6, 
            body.rtl .wrap p, 
            body.rtl .wrap div, 
            body.rtl .wrap span, 
            body.rtl .wrap input, 
            body.rtl .wrap textarea, 
            body.rtl .wrap select, 
            body.rtl .wrap button,
            body.rtl .wrap label,
            body.rtl .wrap li,
            body.rtl .wrap a {
                font-family: 'IRANSans', Tahoma, sans-serif !important;
            }
            
            /* اعمال فونت به تمام عناصر افزونه */
            body.rtl .setia-section:not(.dashicons):not([class^="dashicons-"]),
            body.rtl .setia-main-container:not(.dashicons):not([class^="dashicons-"]),
            body.rtl .setia-schema-wrap:not(.dashicons):not([class^="dashicons-"]) *,
            body.rtl .setia-history-wrap:not(.dashicons):not([class^="dashicons-"]) * {
                font-family: 'IRANSans', Tahoma, sans-serif !important;
                font-weight: normal;
                letter-spacing: 0;
            }
            
            /* حفاظت از آیکون‌های وردپرس */
            .dashicons, 
            .dashicons-before:before, 
            [class^='dashicons-']:before, 
            [class*=' dashicons-']:before,
            #adminmenu .wp-menu-image:before,
            #adminmenu div.wp-menu-image:before,
            #wpadminbar .ab-icon:before, 
            #wpadminbar .ab-item:before,
            #wpadminbar > #wp-toolbar > #wp-admin-bar-root-default .ab-icon,
            .wp-menu-image:before {
                font-family: dashicons !important;
                font-style: normal !important;
                font-weight: normal !important;
            }
            
            /* اعمال فونت به تمام صفحات افزونه بر اساس کلاس contains 'setia-' */
            body[class*='setia-'] *:not(.dashicons):not([class^='dashicons-']) {
                font-family: 'IRANSans', Tahoma, sans-serif !important;
            }
        </style>
        <?php
    }
    
    // بارگذاری Parsedown
    private function include_parsedown() {
        // بررسی اینکه آیا کلاس قبلاً تعریف شده است
        if (class_exists('Parsedown', false)) {
            return true;
        }

        // مسیرهای احتمالی برای فایل Parsedown.php (بدون فایل حذف شده)
        $possible_paths = array(
            plugin_dir_path(__FILE__) . 'inc/Parsedown.php',
            plugin_dir_path(__FILE__) . 'includes/Parsedown.php'
        );

        // بررسی و بارگذاری از اولین مسیر موجود
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once $path;
                    if (class_exists('Parsedown', false)) {
                        return true;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        // بارگذاری نسخه ساده داخلی
        $simple_parsedown_path = plugin_dir_path(__FILE__) . 'includes/simple-parsedown.php';

        if (file_exists($simple_parsedown_path)) {
            require_once $simple_parsedown_path;
            if (class_exists('SETIA_Simple_Parsedown', false)) {
                // ایجاد نام مستعار برای کلاس
                if (!class_exists('Parsedown', false)) {
                    class_alias('SETIA_Simple_Parsedown', 'Parsedown');
                    return true;
                }
            }
        }

        // اگر هیچ نسخه‌ای از Parsedown یافت نشد، کلاس ساده ایجاد می‌کنیم
        if (!class_exists('Parsedown', false)) {
            // بارگذاری کلاس fallback
            $this->load_fallback_parsedown();
            return true;
        }

        return false;
    }

    // بارگذاری کلاس fallback برای Parsedown
    private function load_fallback_parsedown() {
        if (!class_exists('Parsedown', false)) {
            class_alias('SETIA_Fallback_Parsedown', 'Parsedown');
        }
    }

    // بارگذاری وابستگی‌ها
    private function load_dependencies() {
        // بارگذاری توابع تبدیل تاریخ
        require_once plugin_dir_path(__FILE__) . 'includes/date-functions.php';

        // بارگذاری کلاس مدیریت تنظیمات جدید
        require_once plugin_dir_path(__FILE__) . 'includes/class-settings-manager.php';
        
        // فعال‌سازی Settings Manager
        $settings_manager = SETIA_Settings_Manager::get_instance();

        // بارگذاری کلاس اجکس هندلر
        require_once plugin_dir_path(__FILE__) . 'ajax-handlers.php';
        $this->ajax_handlers = new SETIA_Ajax_Handlers($this);

        // بارگذاری کلاس اجکس هندلر پیشرفته
        require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers-enhanced.php';
    }
    

    
    // بارگذاری تنظیمات از دیتابیس
    public function load_settings() {
        $settings = get_option('setia_settings', $this->default_settings);
        $this->gemini_api_key = $settings['gemini_api_key'];
        $this->gemma_api_key = $settings['gemma_api_key'];
        $this->imagine_art_api_key = isset($settings['imagine_art_api_key']) ? $settings['imagine_art_api_key'] : '';
    }
    
    // فعال‌سازی افزونه
    public function activate_plugin() {
        // ایجاد تنظیمات پیش‌فرض اگر وجود ندارد
        if (!get_option('setia_settings')) {
            update_option('setia_settings', $this->default_settings);
        }
        
        // ایجاد جداول دیتابیس مورد نیاز
        $this->create_database_tables();
        
        // ایجاد پوشه‌های مورد نیاز
        $this->create_plugin_directories();
        
        // تازه‌سازی نسخه دارایی‌های استاتیک
        $this->refresh_asset_versions();
        
        // پاک کردن کش وردپرس
        $this->clear_wordpress_cache();
        
        // بخش زمانبندی و کرون‌های مرتبط با آن حذف شده است
    }

    // تازه‌سازی نسخه دارایی‌های استاتیک
    public function refresh_asset_versions() {
        $current_time = time();
        update_option('setia_asset_version', $current_time);
        update_option('setia_css_version', $current_time);
        update_option('setia_js_version', $current_time);
    }
    
    // پاک کردن کش وردپرس
    private function clear_wordpress_cache() {
        global $wp_object_cache;

        // پاک کردن کش آبجکت
        if (is_object($wp_object_cache) && method_exists($wp_object_cache, 'flush')) {
            $wp_object_cache->flush(0);
        }

        // پاک کردن کش ترنزینت
        $GLOBALS['wpdb']->query("DELETE FROM `{$GLOBALS['wpdb']->options}` WHERE `option_name` LIKE ('_transient_%')");
        $GLOBALS['wpdb']->query("DELETE FROM `{$GLOBALS['wpdb']->options}` WHERE `option_name` LIKE ('_site_transient_%')");

        // پاک کردن کش افزونه‌های محبوب

        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }

        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }

        // WP Fastest Cache
        if (function_exists('wpfc_clear_all_cache')) {
            wpfc_clear_all_cache(true);
        }

        // Autoptimize
        if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
            autoptimizeCache::clearall();
        }

        // پاکسازی فایل‌های موقت
        $this->cleanup_temp_files();
    }

    // پاکسازی فایل‌های موقت
    private function cleanup_temp_files() {
        // پاک کردن فایل‌های session موقت
        $tmp_dir = plugin_dir_path(__FILE__) . 'tmp/';
        if (is_dir($tmp_dir)) {
            $files = glob($tmp_dir . 'sess_*');
            foreach ($files as $file) {
                if (is_file($file) && (time() - filemtime($file)) > 3600) { // فایل‌های قدیمی‌تر از 1 ساعت
                    @unlink($file);
                }
            }
        }

        // پاک کردن فایل‌های Parsedown موقت
        $temp_parsedown = plugin_dir_path(__FILE__) . 'includes/temp-parsedown.php';
        if (file_exists($temp_parsedown)) {
            @unlink($temp_parsedown);
        }

        // پاک کردن فایل‌های تصاویر قدیمی (بیش از 30 روز)
        $upload_dir = wp_upload_dir();
        $setia_images = glob($upload_dir['path'] . '/imagine_*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        foreach ($setia_images as $image) {
            if (is_file($image) && (time() - filemtime($image)) > (30 * 24 * 3600)) { // 30 روز
                @unlink($image);
            }
        }
    }

    // ایجاد پوشه‌های مورد نیاز
    private function create_plugin_directories() {
        $upload_dir = wp_upload_dir();
        $setia_dir = trailingslashit($upload_dir['basedir']) . 'setia';
        
        if (!file_exists($setia_dir)) {
            wp_mkdir_p($setia_dir);
        }
        
        // ایجاد فایل .htaccess برای امنیت بیشتر
        $htaccess_file = $setia_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "Order Allow,Deny\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }
    
    // ایجاد جداول دیتابیس
    private function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // جدول نگهداری تاریخچه محتوای تولید شده
        $table_name = $wpdb->prefix . 'setia_generated_content';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) DEFAULT NULL,
            title varchar(255) DEFAULT NULL,
            content longtext DEFAULT NULL,
            content_type varchar(50) DEFAULT 'post',
            primary_keyword varchar(255) DEFAULT NULL,
            word_count int(11) DEFAULT 0,
            topic varchar(255) NOT NULL,
            keywords text NOT NULL,
            tone varchar(50) NOT NULL,
            category varchar(100) NOT NULL,
            length varchar(50) NOT NULL,
            generated_text longtext NOT NULL,
            generated_image_url varchar(255) DEFAULT NULL,
            seo_meta text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            INDEX idx_content_type (content_type),
            INDEX idx_created_at (created_at),
            INDEX idx_post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Update existing records to have proper data structure
        $this->migrate_existing_data();
    }

    // Migration function to update existing data
    private function migrate_existing_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        // Check if migration is needed (if title column is empty but topic exists)
        $needs_migration = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE title IS NULL AND topic IS NOT NULL");

        if ($needs_migration > 0) {
            // Update existing records with proper structure
            $wpdb->query("
                UPDATE $table_name
                SET
                    title = CASE
                        WHEN title IS NULL OR title = '' THEN topic
                        ELSE title
                    END,
                    content = CASE
                        WHEN content IS NULL OR content = '' THEN generated_text
                        ELSE content
                    END,
                    content_type = CASE
                        WHEN content_type IS NULL OR content_type = '' THEN 'post'
                        ELSE content_type
                    END,
                    primary_keyword = CASE
                        WHEN primary_keyword IS NULL OR primary_keyword = '' THEN keywords
                        ELSE primary_keyword
                    END,
                    word_count = CASE
                        WHEN word_count = 0 OR word_count IS NULL THEN
                            CHAR_LENGTH(generated_text) - CHAR_LENGTH(REPLACE(generated_text, ' ', '')) + 1
                        ELSE word_count
                    END
                WHERE title IS NULL OR content IS NULL OR content_type IS NULL OR primary_keyword IS NULL OR word_count = 0
            ");
        }
    }
    
    // افزودن منوی ادمین
    public function admin_menu() {
        // صفحه اصلی پلاگین
        add_menu_page(
            'افزونه تولید محتوا با هوش مصنوعی',
            'تولید محتوا با هوش مصنوعی',
            'edit_posts',
            'setia-content-generator',
            array($this, 'main_page'),
            'dashicons-edit',
            30
        );

        // تغییر نام زیرمنوی اول (که خودکار ایجاد می‌شود) به نام مناسب‌تر
        global $submenu;
        if (isset($submenu['setia-content-generator'])) {
            $submenu['setia-content-generator'][0][0] = 'تولید محتوا';
        }
        
        // زیرمنوی تنظیمات
        add_submenu_page(
            'setia-content-generator',
            'تنظیمات افزونه',
            'تنظیمات',
            'manage_options',
            'setia-settings',
            array($this, 'settings_page')
        );
        
        // زیرمنوی تاریخچه
        add_submenu_page(
            'setia-content-generator',
            'تاریخچه محتوای تولید شده',
            'تاریخچه',
            'edit_posts',
            'setia-history',
            array($this, 'history_page')
        );

        // زیرمنوی تنظیمات اسکیما - اضافه شده در تاریخ امروز
        add_submenu_page(
            'setia-content-generator',
            'تنظیمات اسکیمای گوگل',
            'اسکیمای گوگل',
            'manage_options',
            'setia-schema-settings',
            array($this, 'schema_settings_page')
        );


    }
    
    // بارگذاری استایل و اسکریپت‌ها
    public function enqueue_admin_assets($hook) {
        // بارگذاری استایل‌های مورد نیاز در صفحات مدیریت
        wp_enqueue_style('setia-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin.css', array(), $this->version);
        
        // بارگذاری اسکریپت‌های مورد نیاز
        wp_enqueue_script('setia-admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), $this->version, true);
        
        // تعریف متغیرهای جاوااسکریپت
        wp_localize_script('setia-admin-js', 'setiaParams', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('setia-nonce'),
            'pluginUrl' => plugin_dir_url(__FILE__),
            'version' => $this->version
        ));
        
        // تعریف متغیرهای جاوااسکریپت برای بخش تنظیمات
        wp_localize_script('setia-admin-js', 'setia_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('setia_test_connection')
        ));
        
        // بارگذاری استایل‌های اختصاصی صفحات
        wp_enqueue_style('setia-settings-styles', plugin_dir_url(__FILE__) . 'assets/css/setia-settings.css', array(), $this->version);
        wp_enqueue_style('setia-schema-styles', SETIA_PLUGIN_URL . 'assets/css/setia-schema.css', array(), '1.0.1');
        
        // بارگذاری اسکریپت‌های مورد نیاز
        wp_enqueue_script('setia-settings-scripts', plugin_dir_url(__FILE__) . 'assets/js/settings-modern.js', array('jquery'), $this->version, true);
        wp_enqueue_script('setia-schema-scripts', SETIA_PLUGIN_URL . 'assets/js/setia-schema.js', array('jquery'), $this->version, true);
        
        // بارگذاری فایل‌های CSS و JS برای صفحه اصلی افزونه

        // Force cache busting with current timestamp
        $current_time = time();
        update_option('setia_asset_version', $current_time);

        // دریافت ورژن فایل‌های استاتیک - یا استفاده از زمان فعلی برای جلوگیری از کش
        $asset_version = $current_time;

        // ایجاد نسخه تصادفی برای جلوگیری از کش شدن فایل‌ها
        $random_version = $asset_version . '.' . mt_rand(1000, 9999);

        // بارگذاری فونت ایران‌سنس در تمام صفحات ادمین
        if (file_exists(plugin_dir_path(__FILE__) . 'assets/css/iranians-font.css')) {
            wp_enqueue_style('setia-iranians-font', plugin_dir_url(__FILE__) . 'assets/css/iranians-font.css', array(), $random_version);
            // error_log("SETIA DEBUG: Font CSS enqueued");
        } else {
            // error_log("SETIA DEBUG: Font CSS file not found");
        }

            // کد درون خطی برای اطمینان از بارگذاری فونت
            $font_inline_css = "
            /* اعمال فونت به تمام عناصر ادمین - به جز آیکون‌ها */
            /* این قسمت غیرفعال شده تا آیکون‌ها درست نمایش داده شوند */
            /*
            body.wp-admin,
            body.wp-admin *:not(.dashicons):not([class^='dashicons-']):not(.dashicons-before):not(.ab-icon):not(.ab-item):before,
            #wpadminbar *:not(.dashicons):not([class^='dashicons-']):not(.ab-icon):not(.ab-item):before,
            #adminmenu *:not(.wp-menu-image):not(.dashicons):not([class^='dashicons-']):before,
            #wpbody *:not(.dashicons):not([class^='dashicons-']),
            #wpbody-content *:not(.dashicons):not([class^='dashicons-']),
            .wrap *:not(.dashicons):not([class^='dashicons-']) {
                    font-family: 'IRANSans', Tahoma, sans-serif !important;
                }
            */
                
            /* اعمال فونت به ورودی‌ها و دکمه‌ها */
            input, 
            select, 
            textarea,
            button:not(.dashicons):not([class^='dashicons-']),
            .button:not(.dashicons):not([class^='dashicons-']),
            .wp-core-ui .button:not(.dashicons):not([class^='dashicons-']),
            .wp-core-ui .button-primary:not(.dashicons):not([class^='dashicons-']),
            .wp-core-ui .button-secondary:not(.dashicons):not([class^='dashicons-']) {
                    font-family: 'IRANSans', Tahoma, sans-serif !important;
                }
            
            /* اعمال فونت به عناصر اختصاصی افزونه با اولویت بالاتر */

            .setia-schema-wrap,
            .setia-schema-wrap *:not(.dashicons):not([class^='dashicons-']),
                        .setia-history-wrap,
            .setia-history-wrap *:not(.dashicons):not([class^='dashicons-']),
            .setia-card,
            .setia-card *:not(.dashicons):not([class^='dashicons-']),
            .setia-modal,
            .setia-modal *:not(.dashicons):not([class^='dashicons-']),
            .setia-button,
            .setia-input,
            .setia-select,
            .setia-form-group,
            .setia-form-group *:not(.dashicons):not([class^='dashicons-']),
            .setia-section,
            .setia-section *:not(.dashicons):not([class^='dashicons-']),
            .setia-main-container,
            .setia-main-container *:not(.dashicons):not([class^='dashicons-']) {
                font-family: 'IRANSans', Tahoma, sans-serif !important;
                text-rendering: optimizeLegibility !important;
                -webkit-font-smoothing: antialiased !important;
            }
            
            /* بخش زمانبندی حذف شده است */
            
            /* اعمال فونت ایرانسنس به صفحه setia-content-generator */
            body.toplevel_page_setia-content-generator *:not(.dashicons):not([class^='dashicons-']):not(.dashicons-before):not([class*=' dashicons-']):not(.ab-icon):not(.ab-item):before,
            body.toplevel_page_setia-content-generator .wrap *:not(.dashicons):not([class^='dashicons-']),
            body.toplevel_page_setia-content-generator .setia-main-container *:not(.dashicons):not([class^='dashicons-']) {
                font-family: 'IRANSans', Tahoma, sans-serif !important;
            }
            
            /* حفاظت از آیکون‌های وردپرس */
            .dashicons, 
            .dashicons-before:before, 
            [class^='dashicons-']:before, 
            [class*=' dashicons-']:before,
            #adminmenu .wp-menu-image:before,
            #adminmenu div.wp-menu-image:before,
            #wpadminbar .ab-icon:before, 
            #wpadminbar .ab-item:before,
            #wpadminbar > #wp-toolbar > #wp-admin-bar-root-default .ab-icon,
            .wp-menu-image:before {
                font-family: dashicons !important;
                font-style: normal !important;
                font-weight: normal !important;
                }
            ";
            
            // افزودن CSS درون خطی
            wp_add_inline_style('setia-iranians-font', $font_inline_css);

        // بررسی اینکه آیا در صفحات پلاگین هستیم
        $is_setia_page = (
            strpos($hook, 'setia-content-generator') !== false ||
            strpos($hook, 'setia-history') !== false ||
            strpos($hook, 'setia-schema-settings') !== false ||
            $hook === 'toplevel_page_setia-content-generator' ||
            $hook === 'setia-content-generator_page_setia-history' ||
            $hook === 'setia-content-generator_page_setia-schema-settings'
        );

        // Debug: Log page detection (disabled for production)
        // error_log("SETIA DEBUG: Is SETIA page = " . ($is_setia_page ? 'YES' : 'NO'));

        // General admin styles for all plugin pages
        if ($is_setia_page) {
            // بروزرسانی ورژن فایل‌ها برای جلوگیری از کش
            $css_version = get_option('setia_css_version', time());
            $random_version = $css_version . '.' . mt_rand(1000, 9999);

            // Debug: Log CSS version (disabled for production)
            // error_log("SETIA DEBUG: CSS version = " . $random_version);

            // بررسی وجود فایل‌ها قبل از بارگذاری
            $css_files = [
                'setia-fixed' => 'assets/css/setia-fixed.css',
                'admin' => 'assets/css/admin.css',
                'main-page-enhanced' => 'assets/css/main-page-enhanced.css',
                'history-advanced' => 'assets/css/history-advanced.css',
                'google-earth' => 'assets/css/setia-google-earth.css'
            ];

            foreach ($css_files as $handle => $file_path) {
                $full_path = plugin_dir_path(__FILE__) . $file_path;
                $file_url = plugin_dir_url(__FILE__) . $file_path;

                if (file_exists($full_path) && is_readable($full_path)) {
                    wp_enqueue_style('setia-' . $handle . '-style', $file_url, array(), $random_version);
                    // error_log("SETIA DEBUG: CSS enqueued successfully - " . $handle . ": " . $file_url);
                } else {
                    // error_log("SETIA ERROR: CSS file not found or not readable - " . $handle . ": " . $full_path);
                }
            }

            // بارگذاری استایل بهبود یافته برای صفحه اصلی
            if (strpos($hook, 'toplevel_page_setia-content-generator') !== false) {
                wp_enqueue_style('setia-main-enhanced-style', plugin_dir_url(__FILE__) . 'assets/css/main-page-enhanced.css', array('setia-admin-style'), $random_version);
            }
            
            // بارگذاری CSS صفحه تنظیمات - بررسی جامع‌تر
            $is_settings_page = (
                $hook === 'setia-content-generator_page_setia-settings' ||
                strpos($hook, 'setia-settings') !== false ||
                strpos($hook, 'page_setia-settings') !== false ||
                (isset($_GET['page']) && $_GET['page'] === 'setia-settings') ||
                (strpos($_SERVER['REQUEST_URI'], 'setia-settings') !== false)
            );
            
            if ($is_settings_page) {
                // Debug log
                error_log('SETIA DEBUG: Loading modern settings assets for hook: ' . $hook);

                // بارگذاری استایل مدرن جدید
                wp_enqueue_style('setia-settings-modern', plugin_dir_url(__FILE__) . 'assets/css/settings-modern.css', array(), $random_version);

                // بارگذاری JavaScript مدرن
                wp_enqueue_script('setia-settings-modern', plugin_dir_url(__FILE__) . 'assets/js/settings-modern.js', array('jquery'), $random_version, true);

                // Localize script
                wp_localize_script('setia-settings-modern', 'setiaAjax', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('setia_settings_nonce'),
                'autoSaveNonce' => wp_create_nonce('setia_auto_save'),
                'clearCacheNonce' => wp_create_nonce('setia_clear_cache'),
                'exportNonce' => wp_create_nonce('setia_export_settings'),
                'loading' => __('در حال بارگذاری...', 'setia-content-generator')
            ));

                // اضافه کردن CSS inline برای اطمینان از لود شدن
                $modern_inline_css = '
                    .setia-modern-settings {
                        font-family: "IRANSans", "Segoe UI", Tahoma, Arial, sans-serif !important;
                        direction: rtl !important;
                        text-align: right !important;
                        background: #f8fafc !important;
                        min-height: 100vh;
                        margin: 0 -20px 0 -2px;
                        padding: 0;
                        color: #1e293b;
                        line-height: 1.6;
                    }
                    .setia-settings-header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                        color: white !important;
                        padding: 2rem !important;
                        margin-bottom: 0;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                    }
                    .setia-settings-layout {
                        display: flex !important;
                        max-width: 1400px;
                        margin: 0 auto;
                        background: white;
                        min-height: calc(100vh - 120px);
                        box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
                    }
                ';
                wp_add_inline_style('setia-settings-modern', $modern_inline_css);

                // بارگذاری استایل قدیمی به عنوان fallback
                wp_enqueue_style('setia-settings-enhanced-style', plugin_dir_url(__FILE__) . 'assets/css/settings-enhanced.css', array(), $random_version);
                
                // اضافه کردن CSS کامل به صورت inline برای اطمینان از بارگذاری
                $inline_settings_css = '
                    /* استایل‌های اصلی صفحه تنظیمات */
                    .setia-settings-page {
                        font-family: "IRANSans", Tahoma, sans-serif !important;
                        direction: rtl !important;
                        background: #f0f2f5 !important;
                        padding: 20px;
                    }
                    
                    .setia-settings-container {
                        max-width: 1200px;
                        margin: 0 auto;
                        background: white;
                        border-radius: 12px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                        overflow: hidden;
                    }
                    
                    .setia-settings-header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 30px;
                        text-align: center;
                    }
                    
                    .setia-settings-header h1 {
                        margin: 0;
                        font-size: 28px;
                        font-weight: 600;
                    }
                    
                    .setia-settings-tabs {
                        display: flex;
                        background: #f8f9fa;
                        border-bottom: 1px solid #e9ecef;
                    }
                    
                    .setia-tab-button {
                        flex: 1;
                        padding: 15px 20px;
                        background: none;
                        border: none;
                        cursor: pointer;
                        font-size: 16px;
                        font-weight: 500;
                        color: #6c757d;
                        transition: all 0.3s ease;
                        border-bottom: 3px solid transparent;
                    }
                    
                    .setia-tab-button.active {
                        color: #667eea;
                        border-bottom-color: #667eea;
                        background: white;
                    }
                    
                    .setia-tab-content {
                        padding: 30px;
                        display: none;
                    }
                    
                    .setia-tab-content.active {
                        display: block;
                    }
                    
                    .setia-form-group {
                        margin-bottom: 25px;
                    }
                    
                    .setia-form-group label {
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                        color: #495057;
                    }
                    
                    .setia-form-group input,
                    .setia-form-group select,
                    .setia-form-group textarea {
                        width: 100%;
                        padding: 12px 15px;
                        border: 2px solid #e9ecef;
                        border-radius: 8px;
                        font-size: 14px;
                        transition: border-color 0.3s ease;
                    }
                    
                    .setia-form-group input:focus,
                    .setia-form-group select:focus,
                    .setia-form-group textarea:focus {
                        outline: none;
                        border-color: #667eea;
                        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                    }
                    
                    .setia-button {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        border: none;
                        padding: 12px 24px;
                        border-radius: 8px;
                        font-size: 16px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                    }
                    
                    .setia-button:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                    }
                    
                    .setia-status-indicator {
                        display: inline-block;
                        width: 12px;
                        height: 12px;
                        border-radius: 50%;
                        margin-left: 8px;
                    }
                    
                    .setia-status-connected {
                        background: #28a745;
                        box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
                    }
                    
                    .setia-status-disconnected {
                        background: #dc3545;
                        box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
                    }
                    
                    .setia-notification {
                        padding: 15px;
                        border-radius: 8px;
                        margin-bottom: 20px;
                        border-left: 4px solid;
                    }
                    
                    .setia-notification.success {
                        background: #d4edda;
                        border-color: #28a745;
                        color: #155724;
                    }
                    
                    .setia-notification.error {
                        background: #f8d7da;
                        border-color: #dc3545;
                        color: #721c24;
                    }
                    
                    .setia-notification.info {
                        background: #d1ecf1;
                        border-color: #17a2b8;
                        color: #0c5460;
                    }
                ';
                
                wp_add_inline_style('setia-settings-enhanced-style', $inline_settings_css);
            }

            // بارگذاری استایل پیشرفته برای صفحه تاریخچه
            if (strpos($hook, 'setia-history') !== false ||
                strpos($hook, 'setia-content-generator_page_setia-history') !== false ||
                $hook == 'setia-content-generator_page_setia-history') {

                // Force load history CSS with high priority
                wp_enqueue_style('setia-history-advanced-style', plugin_dir_url(__FILE__) . 'assets/css/history-advanced.css', array(), $random_version, 'all');

                // Add inline CSS to ensure basic styling works
                $inline_css = "
                .setia-history-advanced {
                    direction: rtl !important;
                    text-align: right !important;
                    font-family: 'Vazir', 'Tahoma', sans-serif !important;
                }
                .setia-page-header {
                    background: linear-gradient(135deg, #2271b1 0%, #135e96 100%) !important;
                    color: white !important;
                    padding: 2rem !important;
                    margin: 0 -20px 2rem -20px !important;
                    border-radius: 0 0 8px 8px !important;
                }
                .setia-stats-dashboard {
                    display: grid !important;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
                    gap: 1.5rem !important;
                    margin-bottom: 2rem !important;
                }
                .setia-stat-card {
                    background: white !important;
                    border: 1px solid #e0e0e0 !important;
                    border-radius: 8px !important;
                    padding: 1.5rem !important;
                    display: flex !important;
                    align-items: center !important;
                    gap: 1rem !important;
                }
                ";
                wp_add_inline_style('setia-history-advanced-style', $inline_css);


            }


        }
    }

    // بارگذاری اسکریپت‌های ادمین
    public function enqueue_admin_scripts($hook) {
        // بررسی اینکه آیا در صفحات پلاگین هستیم
        $is_setia_script_page = (
            strpos($hook, 'setia-') !== false ||
            strpos($hook, 'page_setia-') !== false ||
            strpos($hook, 'setia-content-generator') !== false ||
            $hook === 'toplevel_page_setia-content-generator' ||
            $hook === 'setia-content-generator_page_setia-history' ||
            $hook === 'setia-content-generator_page_setia-schema-settings' ||
            $hook === 'setia-content-generator_page_setia-settings'
        );

        if (!$is_setia_script_page) {
            return;
        }

        // بارگذاری اسکریپت‌ها
        wp_enqueue_script('jquery');

        // تولید نسخه تصادفی برای جلوگیری از کش
        $asset_version = time(); // Force new version for footer-upgrade fix
        update_option('setia_asset_version', $asset_version);
        $random_version = $asset_version . '.' . mt_rand(1000, 9999);

        // بارگذاری سیستم AJAX بهینه‌سازی شده برای همه صفحات
        wp_enqueue_script('setia-ajax-optimized', plugin_dir_url(__FILE__) . 'assets/js/ajax-optimized.js', array('jquery'), $random_version, true);
        
        // بارگذاری اسکریپت استایل Google Earth برای همه صفحات
        wp_enqueue_script('setia-google-earth-script', plugin_dir_url(__FILE__) . 'assets/js/setia-google-earth.js', array('jquery'), $random_version, true);

        // بارگذاری اسکریپت‌های مختص هر صفحه
        if (strpos($hook, 'toplevel_page_setia-content-generator') !== false) {
            wp_enqueue_script('setia-main-enhanced-script', plugin_dir_url(__FILE__) . 'assets/js/main-page-enhanced.js', array('jquery', 'setia-ajax-optimized'), $random_version, true);
            $script_handle = 'setia-main-enhanced-script';
        } elseif (strpos($hook, 'setia-history') !== false ||
                  strpos($hook, 'setia-content-generator_page_setia-history') !== false ||
                  $hook === 'setia-content-generator_page_setia-history') {
            wp_enqueue_script('setia-history-advanced-script', plugin_dir_url(__FILE__) . 'assets/js/history-advanced.js', array('jquery', 'setia-ajax-optimized'), $random_version, true);
            $script_handle = 'setia-history-advanced-script';
        } elseif (strpos($hook, 'setia-schema-settings') !== false ||
                  $hook === 'setia-content-generator_page_setia-schema-settings') {
            wp_enqueue_script('setia-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery', 'setia-ajax-optimized'), $random_version, true);
            $script_handle = 'setia-admin-script';
        } elseif ($hook === 'setia-content-generator_page_setia-settings' ||
                  strpos($hook, 'setia-settings') !== false ||
                  strpos($hook, 'page_setia-settings') !== false ||
                  (isset($_GET['page']) && $_GET['page'] === 'setia-settings') ||
                  (strpos($_SERVER['REQUEST_URI'], 'setia-settings') !== false)) {
            wp_enqueue_script('setia-settings-enhanced-script', plugin_dir_url(__FILE__) . 'assets/js/settings-enhanced.js', array('jquery', 'setia-ajax-optimized'), $random_version, true);
            $script_handle = 'setia-settings-enhanced-script';
            
            // اضافه کردن JavaScript اضافی برای اطمینان از عملکرد
            wp_add_inline_script('setia-settings-enhanced-script', '
                jQuery(document).ready(function($) {
                    console.log("SETIA Settings JavaScript loaded successfully");
                    
                    // فعال‌سازی تب‌ها
                    $(".setia-tab-button").click(function() {
                        var tabId = $(this).data("tab");
                        
                        $(".setia-tab-button").removeClass("active");
                        $(this).addClass("active");
                        
                        $(".setia-tab-content").removeClass("active");
                        $("#" + tabId).addClass("active");
                    });
                    
                    // تست اتصال API
                    $(".test-api-button").click(function() {
                        var button = $(this);
                        var apiType = button.data("api");
                        var apiKey = $("#" + apiType + "_api_key").val();
                        
                        if (!apiKey) {
                            alert("لطفاً ابتدا کلید API را وارد کنید");
                            return;
                        }
                        
                        button.prop("disabled", true).text("در حال تست...");
                        
                        $.ajax({
                            url: setiaAjax.ajaxUrl,
                            type: "POST",
                            data: {
                                action: "setia_test_api_connection",
                                api_type: apiType,
                                api_key: apiKey,
                                nonce: $("#setia_settings_nonce").val()
                            },
                            success: function(response) {
                                if (response.success) {
                                    button.removeClass("setia-button-secondary").addClass("setia-button-success");
                                    button.text("✓ متصل");
                                    $("#" + apiType + "_status").removeClass("setia-status-disconnected").addClass("setia-status-connected");
                                } else {
                                    button.removeClass("setia-button-secondary").addClass("setia-button-error");
                                    button.text("✗ خطا");
                                    alert("خطا در اتصال: " + (response.data || "خطای نامشخص"));
                                }
                            },
                            error: function() {
                                button.removeClass("setia-button-secondary").addClass("setia-button-error");
                                button.text("✗ خطا");
                                alert("خطا در ارسال درخواست");
                            },
                            complete: function() {
                                button.prop("disabled", false);
                                setTimeout(function() {
                                    button.removeClass("setia-button-success setia-button-error").addClass("setia-button-secondary");
                                    button.text("تست اتصال");
                                }, 3000);
                            }
                        });
                    });
                    
                    // ذخیره خودکار
                    var autoSaveTimeout;
                    $("input, select, textarea").on("change keyup", function() {
                        clearTimeout(autoSaveTimeout);
                        autoSaveTimeout = setTimeout(function() {
                            console.log("Auto-saving settings...");
                            // اینجا می‌توانید کد ذخیره خودکار را اضافه کنید
                        }, 2000);
                    });
                });
            ');
        }

        // تعریف متغیرهای مختلف برای استفاده در JavaScript
        if (isset($script_handle)) {
            wp_localize_script($script_handle, 'setiaAjax', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'loading' => esc_html__('در حال بارگذاری...', 'setia')
            ));
        }
    }
    
    // صفحه اصلی تولید محتوا
    public function main_page() {
        // اضافه کردن متادستور برای جلوگیری از کش شدن
        echo '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />';
        echo '<meta http-equiv="Pragma" content="no-cache" />';
        echo '<meta http-equiv="Expires" content="0" />';
        
        // بارگذاری مستقیم استایل‌ها و اسکریپت‌ها
        $plugin_url = plugin_dir_url(__FILE__);
        $random_version = time() . '.' . mt_rand(1000, 9999);
        
        // بارگذاری استایل مشترک و Google Earth
        echo '<link rel="stylesheet" href="' . $plugin_url . 'assets/css/setia-common.css?v=' . $random_version . '" type="text/css" media="all" />';
        echo '<link rel="stylesheet" href="' . $plugin_url . 'assets/css/setia-google-earth.css?v=' . $random_version . '" type="text/css" media="all" />';
        
        // اطمینان از بارگذاری jQuery
        wp_enqueue_script('jquery');
        wp_enqueue_style('dashicons');
        
        // تعریف متغیرهای JavaScript - بدون اضافه کردن نانس
        echo '<script type="text/javascript">
            var setiaAjax = {
                ajaxUrl: "' . admin_url('admin-ajax.php') . '"
            };
        </script>';
        
        // بارگذاری JavaScript در انتهای صفحه
        add_action('admin_footer', function() use ($plugin_url, $random_version) {
            echo '<script type="text/javascript" src="' . $plugin_url . 'assets/js/setia-main.js?v=' . $random_version . '"></script>';
        });
        
        require_once plugin_dir_path(__FILE__) . 'templates/main-page.php';
    }
    
    // صفحه تنظیمات
    public function settings_page() {
        // Just render the template - all assets are handled by enqueue hooks
        require_once plugin_dir_path(__FILE__) . 'templates/settings-page.php';
    }

    // بارگذاری استایل‌ها و اسکریپت‌های صفحه تنظیمات (متد قبلی - غیرفعال شده)
    private function load_settings_assets() {
        $plugin_url = plugin_dir_url(__FILE__);
        $asset_version = get_option('setia_asset_version', time());
        $random_version = $asset_version . '.' . mt_rand(1000, 9999);

        // بارگذاری CSS
        wp_enqueue_style('setia-settings-styles', $plugin_url . 'assets/css/setia-settings.css', array(), $random_version);
        
        // اطمینان از بارگذاری jQuery و Dashicons
        wp_enqueue_script('jquery');
        wp_enqueue_style('dashicons');

        // بارگذاری JavaScript
        wp_enqueue_script('setia-settings-scripts', $plugin_url . 'assets/js/setia-settings.js', array('jquery'), $random_version, true);

        // تعریف متغیرهای JavaScript
        wp_localize_script('setia-settings-scripts', 'setiaAjax', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('setia_settings_nonce'),
                'clearCacheNonce' => wp_create_nonce('setia_clear_cache'),
                'exportNonce' => wp_create_nonce('setia_export_settings')
            ));
    }

    // صفحه تاریخچه
    public function history_page() {
        // اضافه کردن متادستور برای جلوگیری از کش شدن
        echo '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />';
        echo '<meta http-equiv="Pragma" content="no-cache" />';
        echo '<meta http-equiv="Expires" content="0" />';

        // بارگذاری مستقیم استایل‌ها و اسکریپت‌ها
        $plugin_url = plugin_dir_url(__FILE__);
        $random_version = time() . '.' . mt_rand(1000, 9999);
        
        // بارگذاری استایل مشترک، Google Earth و استایل اختصاصی صفحه تاریخچه
        echo '<link rel="stylesheet" href="' . $plugin_url . 'assets/css/setia-common.css?v=' . $random_version . '" type="text/css" media="all" />';
        echo '<link rel="stylesheet" href="' . $plugin_url . 'assets/css/setia-google-earth.css?v=' . $random_version . '" type="text/css" media="all" />';
        echo '<link rel="stylesheet" href="' . $plugin_url . 'assets/css/history-advanced.css?v=' . $random_version . '" type="text/css" media="all" />';
        
        // اطمینان از بارگذاری jQuery
        wp_enqueue_script('jquery');
        wp_enqueue_style('dashicons');
        
        // تعریف متغیرهای JavaScript
        echo '<script type="text/javascript">
            var setiaHistory = {
                ajaxUrl: "' . admin_url('admin-ajax.php') . '",
                nonce: "' . wp_create_nonce('setia-history-nonce') . '",
                currentPage: 1,
                itemsPerPage: 20,
                sortBy: "created_at",
                sortOrder: "desc",
                totalItems: 0,
                selectedItems: [],
                currentFilters: {},
                loading: "' . esc_html__('در حال بارگذاری...', 'setia') . '",
                confirmDelete: "' . esc_html__('آیا از حذف این محتوا اطمینان دارید؟', 'setia') . '",
                confirmBulkDelete: "' . esc_html__('آیا از حذف موارد انتخاب شده اطمینان دارید؟', 'setia') . '",
                noItemsSelected: "' . esc_html__('لطفاً ابتدا موارد مورد نظر را انتخاب کنید', 'setia') . '",
                exportSuccess: "' . esc_html__('فایل Excel با موفقیت ایجاد شد', 'setia') . '",
                exportError: "' . esc_html__('خطا در ایجاد فایل Excel', 'setia') . '"
            };
        </script>';
        
        // بارگذاری JavaScript در انتهای صفحه
        add_action('admin_footer', function() use ($plugin_url, $random_version) {
            echo '<script type="text/javascript" src="' . $plugin_url . 'assets/js/history-advanced.js?v=' . $random_version . '"></script>';
        });

        require_once plugin_dir_path(__FILE__) . 'templates/history-page.php';
    }
    
    // تابع ارسال درخواست به Gemini برای تولید متن
    public function generate_text($prompt, $parameters = array()) {
        // اعتبارسنجی ورودی
        if (empty(trim($prompt))) {
            return array(
                'success' => false,
                'error' => 'متن پرامپت خالی است'
            );
        }

        if (empty($this->gemini_api_key)) {
            return array(
                'success' => false,
                'error' => 'کلید API Gemini تنظیم نشده است'
            );
        }

        $api_key = $this->gemini_api_key;
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($api_key);

        // تنظیم پارامترها با اعتبارسنجی
        $body = array(
            "contents" => array(
                array("parts" => array(array("text" => sanitize_text_field($prompt))))
            ),
            "generationConfig" => array(
                "temperature" => max(0, min(2, floatval($parameters['temperature'] ?? 0.7))),
                "maxOutputTokens" => max(1, min(8192, intval($parameters['max_tokens'] ?? 2048))),
                "topP" => max(0, min(1, floatval($parameters['top_p'] ?? 0.95))),
                "topK" => max(1, min(100, intval($parameters['top_k'] ?? 40)))
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code != 200) {
            return array(
                'success' => false,
                'error' => 'کد خطا: ' . $response_code,
                'response' => wp_remote_retrieve_body($response)
            );
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return array(
                'success' => false,
                'error' => 'ساختار پاسخ نامعتبر است',
                'response' => $data
            );
        }
        
        return array(
            'success' => true,
            'text' => $data['candidates'][0]['content']['parts'][0]['text']
        );
    }
    
    // تولید تصویر با Imagine Art (Vyro)
    public function generate_image($prompt, $parameters = array()) {
        try {
            // اعتبارسنجی ورودی
            if (empty(trim($prompt))) {
                return $this->get_default_image('متن پرامپت خالی است');
            }

            // محدود کردن طول پرامپت
            if (strlen($prompt) > 1000) {
                $prompt = substr($prompt, 0, 1000);
            }

            // Translate prompt to English if it might be Farsi
            $translated_prompt = $this->_translate_prompt_to_english($prompt);
            if (!$translated_prompt) {
                error_log("SETIA: Prompt translation failed. Using original prompt: " . $prompt);
                $translated_prompt = $prompt;
            }

            if (empty($this->imagine_art_api_key)) {
                error_log("SETIA ERROR: Imagine Art API key is empty");
                return $this->get_default_image('کلید API Imagine Art تنظیم نشده است');
            }
            $api_token = $this->imagine_art_api_key; 
            
            // استفاده از آدرس API نسخه v2
            $endpoint = 'https://api.vyro.ai/v2/image/generations';

            // تنظیم فیلدهای درخواست مطابق با مستندات API نسخه v2
            $fields = [
                'prompt' => $translated_prompt,
                'style' => 'photographic', // مقدار پیش‌فرض استاندارد
                'aspect_ratio' => '1:1'    // مقدار پیش‌فرض استاندارد
            ];
            
            // اضافه کردن style اگر وجود دارد و معتبر است
            if (isset($parameters['style']) && !empty($parameters['style'])) {
                $valid_styles = ['photographic', 'realistic', 'anime', 'painting', 'sketch', '3d'];
                if (in_array($parameters['style'], $valid_styles)) {
                    $fields['style'] = $parameters['style'];
                }
            }
            
            // اضافه کردن aspect_ratio اگر وجود دارد و معتبر است
            if (isset($parameters['aspect_ratio']) && !empty($parameters['aspect_ratio'])) {
                $valid_ratios = ['1:1', '16:9', '9:16', '4:3', '3:4', '3:2', '2:3'];
                if (in_array($parameters['aspect_ratio'], $valid_ratios)) {
                    $fields['aspect_ratio'] = $parameters['aspect_ratio'];
                }
            }

            // Add negative_prompt if provided and not empty
            if (isset($parameters['negative_prompt']) && !empty(trim($parameters['negative_prompt']))) {
                $fields['negative_prompt'] = $parameters['negative_prompt'];
            }

            error_log("SETIA: Sending image generation request to: " . $endpoint);
            error_log("SETIA: Image generation prompt: " . $translated_prompt);
            error_log("SETIA: Image generation parameters: " . print_r($fields, true));
            
            // آزمایش ارتباط با سرور قبل از ارسال درخواست اصلی
            $test_connection = wp_remote_get('https://api.vyro.ai/v2/status', [
                'timeout' => 10,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_token
                ]
            ]);

            if (is_wp_error($test_connection)) {
                return $this->get_default_image('خطا در اتصال به سرور Vyro: ' . $test_connection->get_error_message());
            }
            
            // برای کاهش احتمال خطا، از curl مستقیم استفاده می‌کنیم
            if (function_exists('curl_version')) {
                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                
                // تنظیم داده‌های فرم به صورت مستقیم
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                
                // تنظیم هدر Authorization
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer ' . $api_token
                ));
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                $response_body = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                $curl_error = curl_error($ch);
                
                // ثبت اطلاعات درخواست و پاسخ برای اشکال‌زدایی
                error_log("SETIA DEBUG: Request URL: " . $endpoint);
                error_log("SETIA DEBUG: Response Code: " . $http_code);
                error_log("SETIA DEBUG: Content-Type: " . $content_type);
                if ($http_code !== 200) {
                    error_log("SETIA DEBUG: Response Body: " . $response_body);
                }

                curl_close($ch);

                if ($curl_error) {
                    error_log("SETIA ERROR: cURL error: " . $curl_error);
                    return $this->get_default_image('خطا در ارسال درخواست cURL: ' . $curl_error);
                }

                if ($http_code != 200) {
                    error_log("SETIA ERROR: API returned error code: " . $http_code . " Response: " . $response_body);
                    return $this->get_default_image('کد خطای API: ' . $http_code . ' - ' . $response_body);
                }
            } else {
                // روش جایگزین با استفاده از wp_remote_post
                $headers = array(
                    'Authorization' => 'Bearer ' . $api_token
                );
                
                $body = array();
                foreach ($fields as $name => $value) {
                    $body[$name] = $value;
                }
                
                $response = wp_remote_post($endpoint, [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 60,
                    'sslverify' => false
                ]);

                if (is_wp_error($response)) {
                    error_log("SETIA ERROR: wp_remote_post error: " . $response->get_error_message());
                    return $this->get_default_image('خطا در اتصال به API: ' . $response->get_error_message());
                }

                $http_code = wp_remote_retrieve_response_code($response);
                $response_body = wp_remote_retrieve_body($response);
                $content_type = wp_remote_retrieve_header($response, 'content-type');

                if ($http_code != 200) {
                    error_log("SETIA ERROR: API returned error code: " . $http_code . " Response: " . $response_body);
                    return $this->get_default_image('کد خطای API: ' . $http_code . ' - ' . $response_body);
                }
            }

            // بررسی نوع محتوای پاسخ
            if (strpos($content_type, 'image/') === 0) {
                // پاسخ مستقیماً یک تصویر است
                error_log("SETIA: Image generation successful. Response is direct image data");
                
                // ذخیره تصویر در وردپرس
                $upload_dir = wp_upload_dir();
                $filename = 'vyro_image_' . time() . '.png';
                $file_path = $upload_dir['path'] . '/' . $filename;
                
                // ذخیره داده‌های تصویر در فایل
                $bytes_written = file_put_contents($file_path, $response_body);
                
                if ($bytes_written === false) {
                    error_log("SETIA ERROR: Failed to save image data to file");
                    return $this->get_default_image('خطا در ذخیره تصویر دریافتی');
                }
                
                $file_url = $upload_dir['url'] . '/' . $filename;
                error_log("SETIA: Image saved locally at: " . $file_url);
                
                return [
                    'success' => true,
                    'image_url' => $file_url
                ];
            } else {
                // پاسخ JSON است
                $response_data = json_decode($response_body, true);
                
                // بررسی ساختار پاسخ مطابق با مستندات API نسخه v2
                if (isset($response_data['image_url'])) {
                    $image_url = $response_data['image_url'];
                    
                    // ذخیره تصویر در وردپرس
                    $upload_dir = wp_upload_dir();
                    $filename = 'imagine_' . time() . '.jpg';
                    $file_path = $upload_dir['path'] . '/' . $filename;
                    
                    // دانلود تصویر از URL
                    $image_response = wp_remote_get($image_url);
                    if (is_wp_error($image_response)) {
                        return $this->get_default_image('خطا در دانلود تصویر: ' . $image_response->get_error_message());
                    }
                    
                    $image_data = wp_remote_retrieve_body($image_response);
                    $bytes_written = file_put_contents($file_path, $image_data);
                    
                    if ($bytes_written === false) {
                        return $this->get_default_image('خطا در ذخیره فایل تصویر');
                    }
                    
                    $file_url = $upload_dir['url'] . '/' . $filename;
                    
                    return [
                        'success' => true,
                        'image_url' => $file_url
                    ];
                } else {
                    error_log("SETIA ERROR: Unexpected API response format: " . $response_body);
                    return $this->get_default_image('پاسخ API در فرمت مورد انتظار نیست');
                }
            }
        } catch (Exception $e) {
            error_log("SETIA ERROR: Exception in generate_image: " . $e->getMessage());
            return $this->get_default_image('خطا: ' . $e->getMessage());
        }
    }
    
    // تولید تصویر پیش‌فرض با متن خطا
    private function get_default_image($error_message) {
        try {
            // استفاده از پلت‌فرم placeholder با متن خطا
            $error_text = urlencode(mb_substr($error_message, 0, 100));
            $placeholder_url = "https://via.placeholder.com/800x400/F44336/FFFFFF?text=" . $error_text;

            // ذخیره تصویر پیش‌فرض در وردپرس
            $upload_dir = wp_upload_dir();
            $filename = 'error_image_' . time() . '.jpg';
            $file_path = $upload_dir['path'] . '/' . $filename;

            // دانلود تصویر پیش‌فرض
            $response = wp_remote_get($placeholder_url);
            if (is_wp_error($response)) {
                // برگرداندن آدرس مستقیم به جای ذخیره
                return [
                    'success' => true, // همیشه موفق برمی‌گردانیم تا روند تولید محتوا متوقف نشود
                    'image_url' => $placeholder_url,
                    'is_fallback' => true,
                    'error' => $error_message
                ];
            }

            $image_data = wp_remote_retrieve_body($response);
            $bytes_written = file_put_contents($file_path, $image_data);

            if ($bytes_written === false) {
                return [
                    'success' => true,
                    'image_url' => $placeholder_url,
                    'is_fallback' => true,
                    'error' => $error_message
                ];
            }

            $file_url = $upload_dir['url'] . '/' . $filename;

            return [
                'success' => true, // همیشه موفق برمی‌گردانیم تا روند تولید محتوا متوقف نشود
                'image_url' => $file_url,
                'is_fallback' => true,
                'error' => $error_message
            ];
        } catch (Exception $e) {
            // در صورت خطا در ساخت تصویر پیش‌فرض، یک URL ثابت برمی‌گردانیم
            return [
                'success' => true,
                'image_url' => 'https://via.placeholder.com/800x400/F44336/FFFFFF?text=Error',
                'is_fallback' => true,
                'error' => $error_message
            ];
        }
    }
    
    // Helper function to translate Farsi prompt to English using Gemini
    private function _translate_prompt_to_english($farsi_prompt) {
        if (empty(trim($farsi_prompt))) {
            return $farsi_prompt; // Return original if empty or whitespace
        }

        // Simple check, assuming non-ASCII might be Farsi. This is a basic heuristic.
        // A more robust solution might involve better language detection or a user setting.
        if (!preg_match('/[\\x{0600}-\\x{06FF}]/u', $farsi_prompt)) {
             // Likely not Farsi (or at least no Arabic script characters), return original
            return $farsi_prompt;
        }

        $translation_instruction = "Translate the following Farsi text to English. Only return the translated English text, without any additional explanations or introductions: \\n\\nFarsi: \"{$farsi_prompt}\"\\nEnglish:";
        
        // Parameters for translation - typically want more deterministic output
        $translation_params = [
            'temperature' => 0.3, // Lower temperature for more factual translation
            'max_tokens' => 500    // Max tokens for the translated output
        ];
        
        $response = $this->generate_text($translation_instruction, $translation_params);
        
        if ($response['success'] && !empty(trim($response['text']))) {
            // Clean up the response: sometimes Gemini might add quotes or prefixes.
            $translated_text = trim($response['text']);
            // Remove potential leading/trailing quotes that Gemini might add
            $translated_text = trim($translated_text, '\\"\'');
            return $translated_text;
        } else {
            error_log("SETIA: Translation API call failed or returned empty. Error: " . ($response['error'] ?? 'Empty response'));
            return null; // Indicate failure
        }
    }
    
    // بهینه‌سازی خودکار محتوا برای SEO
    private function optimize_content_for_seo($content, $title, $focus_keyword) {
        // بررسی و اصلاح محتوا برای بهبود SEO
        
        // تبدیل محتوا به پاراگراف‌ها
        $paragraphs = explode("\n\n", $content);
        
        // بررسی کلمه کلیدی در پاراگراف اول - مشکل شماره 1 در Yoast
        $first_paragraph = isset($paragraphs[0]) ? $paragraphs[0] : '';
        if (!empty($first_paragraph)) {
            // همیشه کلمه کلیدی را به پاراگراف اول اضافه می‌کنیم تا مشکل Yoast حل شود
            $paragraphs[0] = $this->add_keyword_to_first_paragraph($first_paragraph, $focus_keyword);
        }
        
        // بررسی تراکم کلمه کلیدی
        $keyword_count = substr_count(strtolower($content), strtolower($focus_keyword));
        $word_count = str_word_count(strip_tags($content));
        $max_keyword_count = min(10, floor($word_count / 100)); // حداکثر 10 بار یا 1% از کل کلمات
        
        // کاهش تراکم کلمه کلیدی اگر بیش از حد است
        if ($keyword_count > $max_keyword_count) {
            $content = $this->reduce_keyword_density($content, $focus_keyword, $max_keyword_count);
            // بازسازی پاراگراف‌ها پس از کاهش تراکم
            $paragraphs = explode("\n\n", $content);
        }
        
        // بهبود توزیع کلمه کلیدی در متن - مشکل شماره 4 در Yoast
        $paragraphs = $this->improve_keyword_distribution($paragraphs, $focus_keyword);
        
        // اضافه کردن لینک‌های داخلی و خارجی
        $content_with_paragraphs = implode("\n\n", $paragraphs);
        $has_internal_link = $this->has_internal_link($content_with_paragraphs);
        $has_external_link = $this->has_external_link($content_with_paragraphs);
        
        // اضافه کردن لینک داخلی - همیشه حداقل یک لینک داخلی اضافه می‌کنیم
        $internal_links = $this->get_related_internal_links($focus_keyword);
        
        if (!empty($internal_links)) {
            // اگر محتوا کوتاه است (کمتر از 5 پاراگراف)، لینک را به انتهای محتوا اضافه می‌کنیم
            if (count($paragraphs) < 5) {
                $paragraphs[] = $this->create_internal_links_paragraph(array_slice($internal_links, 0, 3));
            } else {
                // اضافه کردن لینک به یکی از پاراگراف‌های میانی
                $mid_point = floor(count($paragraphs) / 2);
                $link_paragraph = rand($mid_point - 1, $mid_point + 1);
                $link_paragraph = max(1, min($link_paragraph, count($paragraphs) - 2)); // اطمینان از محدوده معتبر
                
                // اضافه کردن پاراگراف جدید با لینک‌های داخلی بعد از پاراگراف انتخاب شده
                array_splice($paragraphs, $link_paragraph + 1, 0, array($this->create_internal_links_paragraph(array_slice($internal_links, 0, 3))));
            }
            
            // اگر محتوا طولانی است (بیش از 10 پاراگراف) و لینک‌های بیشتری داریم، یک لینک دیگر هم اضافه کنیم
            if (count($paragraphs) > 10 && count($internal_links) > 3) {
                $second_link_paragraph = floor(count($paragraphs) * 0.8); // حدود 80% به انتهای محتوا
                array_splice($paragraphs, $second_link_paragraph, 0, array($this->create_internal_links_paragraph(array_slice($internal_links, 3, 2))));
            }
        } else {
            // اگر هیچ لینک داخلی پیدا نشد، یک لینک به صفحه اصلی اضافه می‌کنیم
            $home_link = array(
                array(
                    'title' => 'صفحه اصلی',
                    'url' => get_home_url()
                )
            );
            $last_paragraph_index = count($paragraphs) - 1;
            $paragraphs[] = $this->create_internal_links_paragraph($home_link);
        }
        
        // اضافه کردن لینک خارجی اگر وجود ندارد
        if (!$has_external_link) {
            $external_links = $this->suggest_external_links($focus_keyword);
            if (!empty($external_links)) {
                $paragraphs[] = $this->create_external_links_paragraph($external_links);
            }
        }
        
        // بهینه‌سازی زیرعنوان‌ها - مشکل شماره 2 در Yoast
        $paragraphs = $this->optimize_subheadings($paragraphs, $focus_keyword);
        
        // بهینه‌سازی تصاویر - مشکل شماره 3 در Yoast
        $content_with_alt_tags = $this->optimize_image_alt_tags(implode("\n\n", $paragraphs), $focus_keyword);
        
        return $content_with_alt_tags;
    }
    
    // اضافه کردن کلمه کلیدی به پاراگراف اول
    private function add_keyword_to_first_paragraph($paragraph, $keyword) {
        // بررسی اگر کلمه کلیدی قبلاً در جمله اول پاراگراف اول وجود دارد
        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph, 2);
        $first_sentence = isset($sentences[0]) ? $sentences[0] : $paragraph;
        
        if (mb_stripos($first_sentence, $keyword) !== false) {
            return $paragraph; // کلمه کلیدی قبلاً در جمله اول وجود دارد
        }
        
        // اضافه کردن کلمه کلیدی به ابتدای جمله اول
        if (count($sentences) > 1) {
            // اضافه کردن کلمه کلیدی به ابتدای جمله اول
            $sentences[0] = $keyword . '، ' . lcfirst($sentences[0]);
            return implode(' ', $sentences);
        } else {
            // اگر فقط یک جمله وجود دارد
            return $keyword . '، ' . lcfirst($paragraph);
        }
    }
    
    // بهبود توزیع کلمه کلیدی در متن
    private function improve_keyword_distribution($paragraphs, $focus_keyword) {
        // تقسیم متن به چهار بخش برای توزیع بهتر
        $total_paragraphs = count($paragraphs);
        $section_size = ceil($total_paragraphs / 4);
        
        $sections = [
            array_slice($paragraphs, 0, $section_size),                     // بخش اول
            array_slice($paragraphs, $section_size, $section_size),         // بخش دوم
            array_slice($paragraphs, $section_size * 2, $section_size),     // بخش سوم
            array_slice($paragraphs, $section_size * 3)                     // بخش چهارم
        ];
        
        // بررسی هر بخش برای وجود کلمه کلیدی
        foreach ($sections as $section_index => $section) {
            $section_text = implode(' ', $section);
            
            // اگر کلمه کلیدی در این بخش وجود ندارد
            if (mb_stripos($section_text, $focus_keyword) === false) {
                // انتخاب یک پاراگراف مناسب از این بخش برای اضافه کردن کلمه کلیدی
                if (!empty($section)) {
                    // ترجیح می‌دهیم پاراگراف‌های معمولی (نه عنوان) را انتخاب کنیم
                    $normal_paragraphs = [];
                    foreach ($section as $idx => $para) {
                        if (!preg_match('/^#+\s+/', $para)) {
                            $normal_paragraphs[$section_index * $section_size + $idx] = $para;
                        }
                    }
                    
                    // اگر پاراگراف معمولی پیدا شد، از آن استفاده می‌کنیم
                    if (!empty($normal_paragraphs)) {
                        $paragraph_index = array_rand($normal_paragraphs);
                        
                        // اضافه کردن کلمه کلیدی به پاراگراف
                        if (isset($paragraphs[$paragraph_index])) {
                            $sentences = preg_split('/(?<=[.!?])\s+/', $paragraphs[$paragraph_index]);
                            if (!empty($sentences)) {
                                // انتخاب جمله اول یا دوم برای اضافه کردن کلمه کلیدی
                                $sentence_index = min(1, count($sentences) - 1);
                                $sentences[$sentence_index] = rtrim($sentences[$sentence_index], '.!?') . 
                                    ' که ارتباط مستقیمی با ' . $focus_keyword . ' دارد.';
                                $paragraphs[$paragraph_index] = implode(' ', $sentences);
                            } else {
                                // اگر جمله‌ای پیدا نشد، کلمه کلیدی را به انتهای پاراگراف اضافه می‌کنیم
                                $paragraphs[$paragraph_index] .= ' این موضوع با ' . $focus_keyword . ' ارتباط مستقیم دارد.';
                            }
                        }
                    } 
                    // اگر هیچ پاراگراف معمولی پیدا نشد، یک پاراگراف جدید اضافه می‌کنیم
                    else {
                        $new_paragraph = 'در ادامه بحث ' . $focus_keyword . '، باید به نکات مهمی توجه کرد. این موضوع از جنبه‌های مختلفی قابل بررسی است و برای درک بهتر ' . $focus_keyword . ' لازم است تمام جوانب را در نظر بگیریم.';
                        
                        // اضافه کردن پاراگراف جدید به انتهای این بخش
                        $insert_index = ($section_index + 1) * $section_size - 1;
                        if ($insert_index >= count($paragraphs)) {
                            $paragraphs[] = $new_paragraph;
                        } else {
                            array_splice($paragraphs, $insert_index, 0, [$new_paragraph]);
                        }
                    }
                }
            }
        }
        
        return $paragraphs;
    }
    
    // کاهش تراکم کلمه کلیدی
    private function reduce_keyword_density($content, $keyword, $max_count) {
        // جایگزینی برخی از تکرارهای کلمه کلیدی با مترادف‌ها یا عبارات مشابه
        $synonyms = $this->get_keyword_synonyms($keyword);
        
        $keyword_lower = strtolower($keyword);
        $content_lower = strtolower($content);
        
        // پیدا کردن موقعیت‌های کلمه کلیدی
        $positions = array();
        $last_pos = 0;
        while (($last_pos = strpos($content_lower, $keyword_lower, $last_pos)) !== false) {
            $positions[] = $last_pos;
            $last_pos += strlen($keyword_lower);
        }
        
        // حذف برخی از تکرارها با استفاده از مترادف‌ها
        $positions_to_replace = array_slice($positions, $max_count);
        
        // جایگزینی از انتها به ابتدا برای جلوگیری از تغییر موقعیت‌ها
        rsort($positions_to_replace);
        
        foreach ($positions_to_replace as $position) {
            $synonym_index = array_rand($synonyms);
            $synonym = $synonyms[$synonym_index];
            
            $content = substr_replace($content, $synonym, $position, strlen($keyword));
        }
        
        return $content;
    }
    
    // بهینه‌سازی تصاویر برای اضافه کردن alt tag
    private function optimize_image_alt_tags($content, $focus_keyword) {
        // جستجوی تگ‌های تصویر در محتوا (مارک‌داون و HTML)
        
        // 1. بهینه‌سازی تصاویر HTML
        $pattern = '/<img(.*?)>/i';
        preg_match_all($pattern, $content, $matches);
        $has_images = false;
        
        foreach ($matches[0] as $img_tag) {
            $has_images = true;
            // ایجاد alt tag مناسب با کلمه کلیدی
            $alt_text = $this->generate_image_alt_text($focus_keyword);
            
            // بررسی آیا تصویر قبلاً دارای alt است
            if (preg_match('/alt=(["\'])(.*?)\1/i', $img_tag, $alt_matches)) {
                // اگر alt وجود دارد اما کلمه کلیدی در آن نیست
                if (stripos($alt_matches[2], $focus_keyword) === false) {
                    // جایگزینی alt موجود با alt جدید
                    $new_img_tag = str_replace($alt_matches[0], 'alt="' . esc_attr($alt_text) . '"', $img_tag);
                    $content = str_replace($img_tag, $new_img_tag, $content);
                }
            } else {
                // اضافه کردن alt به تصویر
                $new_img_tag = str_replace('<img', '<img alt="' . esc_attr($alt_text) . '"', $img_tag);
                $content = str_replace($img_tag, $new_img_tag, $content);
            }
            
            // اضافه کردن کلاس به تصویر اگر ندارد
            if (strpos($img_tag, 'class=') === false) {
                $new_img_tag = str_replace('<img', '<img class="wp-image-seo-optimized"', $new_img_tag);
                $content = str_replace($img_tag, $new_img_tag, $content);
            }
        }
        
        // 2. بهینه‌سازی تصاویر مارک‌داون
        $md_pattern = '/!\[(.*?)\]\((.*?)\)/';
        preg_match_all($md_pattern, $content, $md_matches);
        
        foreach ($md_matches[0] as $index => $md_img) {
            $has_images = true;
            $alt_text = $md_matches[1][$index];
            $img_url = $md_matches[2][$index];
            
            // همیشه alt جدید با کلمه کلیدی اضافه می‌کنیم
            $new_alt = $this->generate_image_alt_text($focus_keyword);
            $new_md_img = '![' . $new_alt . '](' . $img_url . ')';
            $content = str_replace($md_img, $new_md_img, $content);
        }
        
        // 3. اضافه کردن یک تصویر نمونه با alt مناسب اگر هیچ تصویری در محتوا نباشد
        if (!$has_images) {
            $alt_text = $this->generate_image_alt_text($focus_keyword);
            $sample_image = '<img src="https://via.placeholder.com/800x450?text=' . urlencode($focus_keyword) . '" alt="' . esc_attr($alt_text) . '" class="wp-image-sample aligncenter" />';
            
            // اضافه کردن تصویر به محل مناسب (ترجیحاً بعد از پاراگراف اول یا دوم)
            $paragraphs = explode("\n\n", $content);
            if (count($paragraphs) > 2) {
                array_splice($paragraphs, 2, 0, $sample_image);
                $content = implode("\n\n", $paragraphs);
            } else {
                $content .= "\n\n" . $sample_image;
            }
        }
        
        return $content;
    }
    
    // تولید متن جایگزین (alt) برای تصویر بر اساس بهترین شیوه‌های Yoast SEO
    private function generate_image_alt_text($focus_keyword) {
        // ایجاد چند الگوی مختلف برای alt تصاویر
        $alt_patterns = array(
            'تصویر %s با کیفیت بالا',
            '%s - نمایش تصویری',
            'نمونه‌ای از %s',
            'تصویر مرتبط با %s',
            '%s در یک نگاه',
            'راهنمای تصویری %s',
            'نمایش گرافیکی %s',
        );
        
        // انتخاب تصادفی یکی از الگوها
        $pattern = $alt_patterns[array_rand($alt_patterns)];
        
        // ساخت متن alt با استفاده از کلمه کلیدی
        $alt_text = sprintf($pattern, $focus_keyword);
        
        // اضافه کردن نام سایت در برخی موارد (با احتمال 30%)
        if (mt_rand(1, 100) <= 30) {
            $alt_text .= ' - ' . get_bloginfo('name');
        }
        
        // طبق توصیه Yoast، طول alt نباید بیش از 125 کاراکتر باشد
        if (mb_strlen($alt_text) > 125) {
            $alt_text = mb_substr($alt_text, 0, 122) . '...';
        }
        
        return $alt_text;
    }
    
    // بررسی وجود لینک داخلی
    private function has_internal_link($content) {
        $site_url = get_site_url();
        return strpos($content, $site_url) !== false || preg_match('/\[.*\]\(\/[^\)]*\)/', $content);
    }
    
    // بررسی وجود لینک خارجی
    private function has_external_link($content) {
        $site_url = get_site_url();
        $domain = parse_url($site_url, PHP_URL_HOST);
        
        // بررسی لینک‌های HTML
        if (preg_match('/href=["\']https?:\/\/(?!' . preg_quote($domain, '/') . ')/', $content)) {
            return true;
        }
        
        // بررسی لینک‌های مارک‌داون
        if (preg_match('/\[.*\]\(https?:\/\/(?!' . preg_quote($domain, '/') . ')[^\)]*\)/', $content)) {
            return true;
        }
        
        return false;
    }
    
    // دریافت لینک‌های داخلی مرتبط
    private function get_related_internal_links($keyword) {
        $links = array();
        
        // جستجوی پست‌های مرتبط با کلمه کلیدی
        $args = array(
            'post_type' => array('post', 'page', 'product'), // اضافه کردن انواع پست‌های دیگر
            'post_status' => 'publish',
            'posts_per_page' => 5, // افزایش تعداد نتایج
            's' => $keyword,
            'orderby' => 'relevance',
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $links[] = array(
                    'title' => get_the_title(),
                    'url' => get_permalink()
                );
            }
            wp_reset_postdata();
        }
        
        // اگر نتایج کافی نبود، از کلمات کلیدی مرتبط استفاده کنیم
        if (count($links) < 3) {
            $related_keywords = $this->get_keyword_synonyms($keyword);
            
            foreach ($related_keywords as $related_keyword) {
                if (count($links) >= 5) break; // حداکثر 5 لینک کافی است
                
                $args['s'] = $related_keyword;
                $related_query = new WP_Query($args);
                
                if ($related_query->have_posts()) {
                    while ($related_query->have_posts()) {
                        $related_query->the_post();
                        
                        // بررسی تکراری نبودن لینک
                        $is_duplicate = false;
                        foreach ($links as $existing_link) {
                            if ($existing_link['url'] == get_permalink()) {
                                $is_duplicate = true;
                                break;
                            }
                        }
                        
                        if (!$is_duplicate) {
                            $links[] = array(
                                'title' => get_the_title(),
                                'url' => get_permalink()
                            );
                        }
                        
                        if (count($links) >= 5) break; // حداکثر 5 لینک کافی است
                    }
                    wp_reset_postdata();
                }
            }
        }
        
        // اگر هنوز نتایج کافی نبود، از پست‌های اخیر استفاده کنیم
        if (count($links) < 2) {
            $recent_args = array(
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'posts_per_page' => 5 - count($links),
                'orderby' => 'date',
                'order' => 'DESC'
            );
            
            $recent_query = new WP_Query($recent_args);
            
            if ($recent_query->have_posts()) {
                while ($recent_query->have_posts()) {
                    $recent_query->the_post();
                    
                    // بررسی تکراری نبودن لینک
                    $is_duplicate = false;
                    foreach ($links as $existing_link) {
                        if ($existing_link['url'] == get_permalink()) {
                            $is_duplicate = true;
                            break;
                        }
                    }
                    
                    if (!$is_duplicate) {
                        $links[] = array(
                            'title' => get_the_title(),
                            'url' => get_permalink()
                        );
                    }
                }
                wp_reset_postdata();
            }
        }
        
        // اگر هیچ لینکی پیدا نشد، حداقل یک لینک به صفحه اصلی اضافه کنیم
        if (empty($links)) {
            $links[] = array(
                'title' => 'صفحه اصلی',
                'url' => get_home_url()
            );
            
            // و یک لینک به آرشیو دسته‌بندی‌ها
            $categories = get_categories(array('number' => 1));
            if (!empty($categories)) {
                $category = $categories[0];
                $links[] = array(
                    'title' => $category->name,
                    'url' => get_category_link($category->term_id)
                );
            }
        }
        
        return $links;
    }
    
    // ایجاد پاراگراف لینک‌های داخلی
    private function create_internal_links_paragraph($links) {
        if (empty($links)) {
            return '';
        }
        
        // انتخاب یکی از متن‌های مختلف برای معرفی لینک‌ها
        $intro_texts = array(
            "برای اطلاعات بیشتر، مقالات زیر را مطالعه کنید:",
            "مطالب مرتبط که ممکن است برای شما مفید باشد:",
            "برای مطالعه بیشتر در این زمینه، پیشنهاد می‌کنیم به این مطالب مراجعه کنید:",
            "برای تکمیل اطلاعات خود، این مقالات را نیز بخوانید:",
            "مقالات مرتبط با این موضوع:"
        );
        
        $intro = $intro_texts[array_rand($intro_texts)];
        $paragraph = "$intro\n\n";
        
        foreach ($links as $link) {
            $paragraph .= "* [{$link['title']}]({$link['url']})\n";
        }
        
        return $paragraph;
    }
    
    // پیشنهاد لینک‌های خارجی
    private function suggest_external_links($keyword) {
        // لینک‌های خارجی پیش‌فرض برای موضوعات مختلف
        $default_external_links = array(
            array(
                'title' => 'ویکی‌پدیا',
                'url' => 'https://fa.wikipedia.org/wiki/' . urlencode($keyword)
            ),
            array(
                'title' => 'گوگل اسکولار',
                'url' => 'https://scholar.google.com/scholar?q=' . urlencode($keyword)
            )
        );
        
        return $default_external_links;
    }
    
    // ایجاد پاراگراف لینک‌های خارجی
    private function create_external_links_paragraph($links) {
        if (empty($links)) {
            return '';
        }
        
        $paragraph = "منابع و مراجع:\n\n";
        
        foreach ($links as $link) {
            $paragraph .= "* [{$link['title']}]({$link['url']})\n";
        }
        
        return $paragraph;
    }
    
    // بهینه‌سازی زیرعنوان‌ها
    private function optimize_subheadings($paragraphs, $focus_keyword) {
        $headings_count = 0;
        $keyword_in_headings_count = 0;
        $subheadings = [];
        
        // شمارش تعداد زیرعنوان‌ها و تعداد زیرعنوان‌های حاوی کلمه کلیدی
        foreach ($paragraphs as $index => $paragraph) {
            if (preg_match('/^#{2,3}\s+(.+)$/m', $paragraph, $matches)) {
                $headings_count++;
                $subheadings[$index] = $matches[1];
                
                if (stripos($matches[1], $focus_keyword) !== false) {
                    $keyword_in_headings_count++;
                }
            }
        }
        
        // اگر کمتر از 50% زیرعنوان‌ها حاوی کلمه کلیدی هستند، کلمه کلیدی را به برخی از زیرعنوان‌ها اضافه کنیم
        // این برای رفع مشکل Yoast SEO است که می‌گوید "از عبارات کلیدی یا مترادف بیشتر در زیر عنوان های H2 و H3 استفاده کنید!"
        if ($headings_count > 0 && ($keyword_in_headings_count / $headings_count) < 0.5) {
            // تعداد زیرعنوان‌هایی که باید کلمه کلیدی به آنها اضافه شود
            $headings_to_modify = ceil($headings_count * 0.5) - $keyword_in_headings_count;
            
            // فهرست زیرعنوان‌هایی که کلمه کلیدی ندارند
            $headings_without_keyword = [];
            foreach ($subheadings as $index => $heading) {
                if (stripos($heading, $focus_keyword) === false) {
                    $headings_without_keyword[$index] = $heading;
                }
            }
            
            // اضافه کردن کلمه کلیدی به تعدادی از زیرعنوان‌ها
            $modified_count = 0;
            foreach ($headings_without_keyword as $index => $heading) {
                if ($modified_count >= $headings_to_modify) {
                    break;
                }
                
                // تشخیص سطح عنوان (H2 یا H3)
                preg_match('/^(#{2,3})\s+/', $paragraphs[$index], $level_matches);
                $heading_level = $level_matches[1];
                
                // اضافه کردن کلمه کلیدی به زیرعنوان
                $new_heading = $heading_level . ' ' . $heading . ' و ' . $focus_keyword;
                $paragraphs[$index] = $new_heading;
                
                $modified_count++;
            }
        } 
        // اگر بیش از 75% زیرعنوان‌ها حاوی کلمه کلیدی هستند، برخی را با مترادف جایگزین کنیم
        else if ($headings_count > 0 && ($keyword_in_headings_count / $headings_count) > 0.75) {
            // اصلاح برخی از زیرعنوان‌ها
            $synonyms = $this->get_keyword_synonyms($focus_keyword);
            $headings_to_modify = ceil($keyword_in_headings_count - ($headings_count * 0.5)); // اصلاح تا رسیدن به 50%
            
            $modified_count = 0;
            foreach ($paragraphs as $index => $paragraph) {
                if ($modified_count >= $headings_to_modify) {
                    break;
                }
                
                if (preg_match('/^(#{2,3})\s+(.+)$/m', $paragraph, $matches) && stripos($matches[2], $focus_keyword) !== false) {
                    $synonym_index = array_rand($synonyms);
                    $synonym = $synonyms[$synonym_index];
                    
                    // جایگزینی کلمه کلیدی با مترادف در زیرعنوان
                    $new_heading = $matches[1] . ' ' . str_ireplace($focus_keyword, $synonym, $matches[2]);
                    $paragraphs[$index] = $new_heading;
                    
                    $modified_count++;
                }
            }
        }
        
        return $paragraphs;
    }
    
    // دریافت مترادف‌های کلمه کلیدی
    private function get_keyword_synonyms($keyword) {
        // مترادف‌های پیش‌فرض برای کلمه کلیدی
        $synonyms = array(
            'این موضوع',
            'این مبحث',
            'این مورد',
            'این مطلب',
            'این محتوا'
        );
        
        return $synonyms;
    }
    
    // تابع ایجاد پست وردپرس
    public function create_wordpress_post($title, $content, $category_id, $seo_meta, $featured_image_url = null, $post_status = 'draft') {
        // بهینه‌سازی محتوا برای SEO
        if (!empty($seo_meta['focus_keyword'])) {
            $content = $this->optimize_content_for_seo($content, $title, $seo_meta['focus_keyword']);
        }
        
        // تبدیل متن مارک‌داون به HTML با استفاده از Parsedown
        $html_content = $content; // مقدار پیش‌فرض در صورت خطا
        
        // بررسی وجود کلاس Parsedown و تبدیل مارک‌داون به HTML
        if (class_exists('Parsedown')) {
            try {
                $parsedown = new Parsedown();
                $html_content = $parsedown->text($content);
                error_log("SETIA: Markdown successfully converted with Parsedown in create_wordpress_post.");
            } catch (Exception $e) {
                error_log("SETIA ERROR: Exception when using Parsedown in create_wordpress_post: " . $e->getMessage());
                // استفاده از wpautop به عنوان پشتیبان
                $html_content = wpautop($content);
            }
        } else {
            error_log('SETIA ERROR: Parsedown class not found in create_wordpress_post. Using wpautop as fallback.');
            $html_content = wpautop($content);
        }
        
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $html_content,
            'post_status'   => $post_status, // استفاده از وضعیت پست ارسال شده
            'post_author'   => get_current_user_id(),
            'post_category' => array($category_id)
        );
        
        // ایجاد پست
        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id)) {
            // اضافه کردن متا دیتای سئو
            if (!empty($seo_meta)) {
                // ذخیره تمام متادیتای Yoast SEO
                foreach ($seo_meta as $key => $value) {
                    if (strpos($key, '_yoast_') === 0) {
                        update_post_meta($post_id, $key, $value);
                    } else {
                        update_post_meta($post_id, '_' . $key, $value);
                    }
                }
                
                // تنظیم عنوان و توضیحات سئو
                update_post_meta($post_id, '_yoast_wpseo_title', $seo_meta['title']);
                update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo_meta['description']);
                
                // تنظیم کلمه کلیدی اصلی
                update_post_meta($post_id, '_yoast_wpseo_focuskw', $seo_meta['focus_keyword']);
                
                // تنظیم امتیاز سئو و خوانایی
                update_post_meta($post_id, '_yoast_wpseo_linkdex', $seo_meta['_yoast_wpseo_linkdex'] ?? 80);
                update_post_meta($post_id, '_yoast_wpseo_content_score', $seo_meta['_yoast_wpseo_content_score'] ?? 90);
                
                // تنظیم زمان مطالعه
                if (isset($seo_meta['_yoast_wpseo_estimated-reading-time-minutes'])) {
                    update_post_meta($post_id, '_yoast_wpseo_estimated-reading-time-minutes', 
                        $seo_meta['_yoast_wpseo_estimated-reading-time-minutes']);
                }
                
                // تنظیم سایر متادیتای Yoast
                update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', '0');
                update_post_meta($post_id, '_yoast_wpseo_meta-robots-nofollow', '0');
                update_post_meta($post_id, '_yoast_wpseo_meta-robots-adv', 'none');
                update_post_meta($post_id, '_yoast_wpseo_is_cornerstone', '0');
            }
            
            // اضافه کردن تصویر شاخص
            if (!empty($featured_image_url)) {
                $attachment_id = $this->set_featured_image($post_id, $featured_image_url);
                
                // بهینه‌سازی تصویر برای SEO
                if ($attachment_id && !empty($seo_meta['focus_keyword'])) {
                    $this->optimize_images_for_seo($post_id, $attachment_id, $seo_meta['focus_keyword']);
                }
            }
            
            // تولید و اضافه کردن اسکیمای گوگل
            if (!empty($seo_meta['focus_keyword'])) {
                $schema_markup = $this->generate_schema_markup($post_id, $post_data, $seo_meta['focus_keyword']);
                if ($schema_markup) {
                    // ذخیره اسکیما به صورت JSON در متادیتای پست
                    update_post_meta($post_id, '_setia_schema_markup', wp_json_encode($schema_markup));
                    
                    // اگر افزونه Yoast SEO فعال است، اسکیما را در متادیتای آن نیز ذخیره کنیم
                    if (defined('WPSEO_VERSION')) {
                        update_post_meta($post_id, '_yoast_wpseo_schema_article_type', $schema_markup['@type']);
                        update_post_meta($post_id, '_yoast_wpseo_schema_page_type', 'WebPage');
                    }
                }
            }
            
            return array(
                'success' => true,
                'post_id' => $post_id,
                'edit_url' => get_edit_post_link($post_id, '')
            );
        } else {
            return array(
                'success' => false,
                'error' => $post_id->get_error_message()
            );
        }
    }
    
    // تنظیم تصویر شاخص برای پست
    private function set_featured_image($post_id, $image_url) {
        // بررسی URL تصویر
        if (empty($image_url)) {
            error_log('SETIA: تنظیم تصویر شاخص شکست خورد - URL تصویر خالی است');
            return false;
        }
        
        // ثبت گزارش
        error_log('SETIA: تلاش برای تنظیم تصویر شاخص با URL: ' . $image_url);
        
        // بررسی اگر URL شروع با http یا https نیست
        if (!preg_match('/^https?:\/\//i', $image_url)) {
            error_log('SETIA ERROR: آدرس تصویر فاقد پروتکل HTTP/HTTPS است: ' . $image_url);
            return false;
        }
        
        // روش مستقیم برای دانلود و الحاق تصویر از URL به پست با استفاده از وردپرس
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // دانلود مستقیم تصویر با استفاده از API وردپرس
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            error_log('SETIA ERROR: خطا در دانلود تصویر: ' . $tmp->get_error_message());
            // امتحان روش پشتیبان در صورت خطا
            return $this->manual_set_featured_image($post_id, $image_url);
        }
        
        // فایل دانلود شده را به آرایه‌ی مورد نیاز media_handle_sideload تبدیل می‌کنیم
        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );
        
        // در صورتی که نام فایل معتبر نیست، یک نام تصادفی ایجاد می‌کنیم
        if (empty($file_array['name']) || strlen($file_array['name']) < 5 || !preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $file_array['name'])) {
            $file_array['name'] = 'setia-featured-image-' . time() . '.jpg';
        }
        
        // تصویر را به کتابخانه رسانه اضافه و به پست الحاق می‌کنیم
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        // فایل موقت را حذف می‌کنیم
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            error_log('SETIA ERROR: خطا در الحاق تصویر به پست: ' . $attachment_id->get_error_message());
            // امتحان روش پشتیبان در صورت خطا
            return $this->manual_set_featured_image($post_id, $image_url);
        }
        
        // تصویر را به عنوان تصویر شاخص تنظیم می‌کنیم
        $result = set_post_thumbnail($post_id, $attachment_id);
        
        if ($result) {
            error_log('SETIA: تصویر شاخص با موفقیت تنظیم شد. شناسه تصویر: ' . $attachment_id);
        } else {
            error_log('SETIA ERROR: خطا در تنظیم تصویر شاخص با شناسه ' . $attachment_id);
        }
        
        return $attachment_id;
    }
    
    // روش دستی تنظیم تصویر شاخص به عنوان پشتیبان
    private function manual_set_featured_image($post_id, $image_url) {
        error_log('SETIA: استفاده از روش پشتیبان برای تنظیم تصویر شاخص');
        
        // دانلود تصویر و ذخیره در کتابخانه رسانه
        $upload_dir = wp_upload_dir();
        
        // دانلود تصویر
        $response = wp_remote_get($image_url, array(
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            error_log('SETIA ERROR: خطا در دانلود تصویر از URL: ' . $response->get_error_message());
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        
        if (empty($image_data)) {
            error_log('SETIA ERROR: محتوای دانلود شده خالی است');
            return false;
        }
        
        // تشخیص نام فایل از URL
        $filename = basename(parse_url($image_url, PHP_URL_PATH));
        
        // اطمینان از اینکه نام فایل معتبر است
        if (empty($filename) || strlen($filename) < 5 || !preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $filename)) {
            $filename = 'setia-featured-image-' . time() . '.jpg';
        }
        
        // ایجاد مسیر فایل
        $file = $upload_dir['path'] . '/' . $filename;
        
        // ذخیره تصویر در فایل
        $saved = file_put_contents($file, $image_data);
        
        if ($saved === false) {
            error_log('SETIA ERROR: خطا در ذخیره تصویر در فایل: ' . $file);
            return false;
        }
        
        // اطمینان از وجود فایل‌های مورد نیاز وردپرس
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // اضافه کردن فایل به کتابخانه رسانه
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $file, $post_id);
        
        if (is_wp_error($attachment_id)) {
            error_log('SETIA ERROR: خطا در ایجاد پیوست: ' . $attachment_id->get_error_message());
            return false;
        }
        
        // تولید متادیتا برای پیوست
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // تنظیم به عنوان تصویر شاخص
        $result = set_post_thumbnail($post_id, $attachment_id);
        
        if ($result) {
            error_log('SETIA: تصویر شاخص با موفقیت با روش پشتیبان تنظیم شد. شناسه تصویر: ' . $attachment_id);
            return $attachment_id;
        } else {
            error_log('SETIA ERROR: خطا در تنظیم تصویر شاخص با روش پشتیبان');
            return false;
        }
    }
    
    // قابلیت جدید 1: بهینه‌سازی تصاویر برای SEO
    public function optimize_images_for_seo($post_id, $featured_image_id, $focus_keyword) {
        if (!$featured_image_id) {
            return false;
        }
        
        // بهینه‌سازی تصویر شاخص
        $alt_text = $this->generate_image_alt_text($focus_keyword);
        $caption = $this->generate_image_caption($focus_keyword);
        $description = $this->generate_image_description($focus_keyword);
        
        // تنظیم متادیتای تصویر
        update_post_meta($featured_image_id, '_wp_attachment_image_alt', $alt_text);
        
        // بروزرسانی پست تصویر
        wp_update_post(array(
            'ID' => $featured_image_id,
            'post_excerpt' => $caption, // caption
            'post_content' => $description // description
        ));
        
        // تنظیم متادیتای مربوط به Yoast SEO برای تصویر
        update_post_meta($post_id, '_yoast_wpseo_opengraph-image', wp_get_attachment_url($featured_image_id));
        update_post_meta($post_id, '_yoast_wpseo_twitter-image', wp_get_attachment_url($featured_image_id));
        
        // بهینه‌سازی تصاویر درون محتوا
        $this->optimize_content_images($post_id, $focus_keyword);
        
        return true;
    }
    
    // تولید متن جایگزین (caption) برای تصویر
    private function generate_image_caption($focus_keyword) {
        return 'تصویری در مورد ' . $focus_keyword;
    }
    
    // تولید توضیحات برای تصویر
    private function generate_image_description($focus_keyword) {
        return 'این تصویر نشان‌دهنده مفهوم ' . $focus_keyword . ' است که برای درک بهتر محتوا ارائه شده است.';
    }
    
    // بهینه‌سازی تصاویر درون محتوا
    private function optimize_content_images($post_id, $focus_keyword) {
        // دریافت محتوای پست
        $post = get_post($post_id);
        if (!$post) return false;
        
        $content = $post->post_content;
        
        // بررسی وجود تصاویر در محتوا
        if (preg_match_all('/<img[^>]+>/i', $content, $matches)) {
            foreach ($matches[0] as $img_tag) {
                // بررسی وجود alt
                if (!preg_match('/alt=["\']/i', $img_tag)) {
                    // اضافه کردن alt به تصویر
                    $alt_text = $this->generate_image_alt_text($focus_keyword);
                    $new_img_tag = preg_replace('/<img/i', '<img alt="' . esc_attr($alt_text) . '"', $img_tag);
                    
                    // جایگزینی تگ تصویر در محتوا
                    $content = str_replace($img_tag, $new_img_tag, $content);
                }
            }
            
            // بروزرسانی محتوای پست
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $content
            ));
        }
        
        return true;
    }
    
    // قابلیت برنامه‌ریزی زمانی برای انتشار محتوا حذف شده است
    
    // قابلیت جدید 3: بازنویسی خودکار محتوا
    public function rewrite_content($content, $rewrite_type = 'standard') {
        // بررسی وجود کلید API
        if (empty($this->gemini_api_key)) {
            return array(
                'success' => false,
                'error' => 'کلید API برای بازنویسی محتوا تنظیم نشده است'
            );
        }
        
        // ساخت پرامپت بر اساس نوع بازنویسی
        $prompt = $this->build_rewrite_prompt($content, $rewrite_type);
        
        // تنظیم پارامترهای بازنویسی
        $params = array(
            'temperature' => 0.7,
            'max_tokens' => min(4000, strlen($content) * 1.5)
        );
        
        // ارسال درخواست به Gemini
        $response = $this->generate_text($prompt, $params);
        
        return $response;
    }
    
    // ساخت پرامپت برای بازنویسی محتوا
    private function build_rewrite_prompt($content, $rewrite_type) {
        $prompt = "لطفاً متن زیر را ";
        
        switch ($rewrite_type) {
            case 'simple':
                $prompt .= "به زبان ساده‌تر و قابل فهم‌تر بازنویسی کن:";
                break;
            case 'academic':
                $prompt .= "به سبک آکادمیک و علمی بازنویسی کن:";
                break;
            case 'creative':
                $prompt .= "به شکل خلاقانه‌تر و جذاب‌تر بازنویسی کن:";
                break;
            case 'seo':
                $prompt .= "با تمرکز بر بهینه‌سازی برای موتورهای جستجو بازنویسی کن:";
                break;
            default:
                $prompt .= "بازنویسی کن با حفظ معنا و مفهوم اصلی اما با کلمات و ساختار متفاوت:";
        }
        
        $prompt .= "\n\n" . $content;
        $prompt .= "\n\nنکات مهم برای بازنویسی:";
        $prompt .= "\n1. ساختار کلی متن و بخش‌بندی آن را حفظ کن.";
        $prompt .= "\n2. از کلمات مترادف و ساختارهای جمله متفاوت استفاده کن.";
        $prompt .= "\n3. از همان فرمت مارک‌داون استفاده کن (عناوین، لیست‌ها، لینک‌ها و غیره).";
        $prompt .= "\n4. اطلاعات اصلی و کلیدی را تغییر نده.";
        
        return $prompt;
    }
    
    // قابلیت جدید 4: پیش‌نمایش نتایج گوگل
    public function generate_serp_preview($title, $description, $url) {
        // محدود کردن طول عنوان و توضیحات برای نمایش در نتایج گوگل
        $title = mb_strlen($title) > 60 ? mb_substr($title, 0, 57) . '...' : $title;
        $description = mb_strlen($description) > 160 ? mb_substr($description, 0, 157) . '...' : $description;
        
        // ساخت URL نمایشی
        $display_url = $url;
        if (strlen($display_url) > 70) {
            $parsed_url = parse_url($url);
            $display_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/...';
        }
        
        // ساخت HTML پیش‌نمایش
        $preview_html = '<div class="setia-serp-preview">';
        $preview_html .= '<div class="setia-serp-title">' . esc_html($title) . '</div>';
        $preview_html .= '<div class="setia-serp-url">' . esc_html($display_url) . '</div>';
        $preview_html .= '<div class="setia-serp-description">' . esc_html($description) . '</div>';
        $preview_html .= '</div>';
        
        return array(
            'html' => $preview_html,
            'data' => array(
                'title' => $title,
                'description' => $description,
                'display_url' => $display_url
            )
        );
    }
    
    // قابلیت جدید 5: تحلیل رقابتی کلمات کلیدی
    public function analyze_keyword_competition($keyword) {
        // بررسی وجود کلید API
        if (empty($this->gemini_api_key)) {
            return array(
                'success' => false,
                'error' => 'کلید API برای تحلیل کلمات کلیدی تنظیم نشده است'
            );
        }
        
        // ساخت پرامپت برای تحلیل کلمه کلیدی
        $prompt = "لطفاً یک تحلیل رقابتی برای کلمه کلیدی «{$keyword}» ارائه دهید. این تحلیل باید شامل موارد زیر باشد:
1. میزان رقابت (کم، متوسط، زیاد)
2. پیشنهاد 5 کلمه کلیدی مرتبط با رقابت کمتر
3. پیشنهاد 3 عنوان مقاله جذاب با استفاده از این کلمه کلیدی
4. توصیه‌هایی برای بهینه‌سازی محتوا با این کلمه کلیدی

لطفاً پاسخ را در قالب JSON با ساختار زیر ارائه دهید:
```json
{
  \"competition_level\": \"متوسط\",
  \"related_keywords\": [\"کلمه1\", \"کلمه2\", \"کلمه3\", \"کلمه4\", \"کلمه5\"],
  \"suggested_titles\": [\"عنوان1\", \"عنوان2\", \"عنوان3\"],
  \"optimization_tips\": [\"توصیه1\", \"توصیه2\", \"توصیه3\", \"توصیه4\"]
}
```";
        
        // تنظیم پارامترهای تولید متن
        $params = array(
            'temperature' => 0.3, // دقت بالاتر برای تولید JSON
            'max_tokens' => 1000
        );
        
        // ارسال درخواست به Gemini
        $response = $this->generate_text($prompt, $params);
        
        if (!$response['success']) {
            return $response;
        }
        
        // استخراج JSON از پاسخ
        $text = $response['text'];
        $json_start = strpos($text, '{');
        $json_end = strrpos($text, '}');
        
        if ($json_start === false || $json_end === false) {
            return array(
                'success' => false,
                'error' => 'خطا در پردازش پاسخ API'
            );
        }
        
        $json_string = substr($text, $json_start, $json_end - $json_start + 1);
        $data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => 'خطا در تجزیه پاسخ JSON: ' . json_last_error_msg()
            );
        }
        
        return array(
            'success' => true,
            'data' => $data
        );
    }
    
    // تولید اسکیمای گوگل برای محتوا
    private function generate_schema_markup($post_id, $post_data, $focus_keyword) {
        // تشخیص نوع محتوا برای انتخاب اسکیمای مناسب
        $schema_type = $this->determine_schema_type($post_data, $focus_keyword);
        
        // اگر اسکیما غیرفعال است یا نوع آن تشخیص داده نشده، چیزی برنگردان
        if ($schema_type === false) {
            return false;
        }
        
        // دریافت اطلاعات پست
        $post = get_post($post_id);
        if (!$post) return false;
        
        // دریافت URL تصویر شاخص
        $featured_image_url = '';
        $featured_image_id = get_post_thumbnail_id($post_id);
        if ($featured_image_id) {
            $image_data = wp_get_attachment_image_src($featured_image_id, 'full');
            if ($image_data) {
                $featured_image_url = $image_data[0];
            }
        }
        
        // دریافت نویسنده
        $author_id = $post->post_author;
        $author_name = get_the_author_meta('display_name', $author_id);
        
        // تاریخ انتشار و بروزرسانی
        $date_published = get_the_date('c', $post_id);
        $date_modified = get_the_modified_date('c', $post_id);
        
        // ساخت اسکیما بر اساس نوع تشخیص داده شده
        $schema = array();
        
        switch ($schema_type) {
            case 'Article':
                $schema = $this->generate_article_schema($post, $featured_image_url, $author_name, $date_published, $date_modified);
                break;
            case 'BlogPosting':
                $schema = $this->generate_blog_posting_schema($post, $featured_image_url, $author_name, $date_published, $date_modified);
                break;
            case 'NewsArticle':
                $schema = $this->generate_news_article_schema($post, $featured_image_url, $author_name, $date_published, $date_modified);
                break;
            case 'Product':
                $schema = $this->generate_product_schema($post, $featured_image_url, $focus_keyword);
                break;
            case 'HowTo':
                $schema = $this->generate_howto_schema($post, $featured_image_url, $focus_keyword);
                break;
            case 'FAQ':
                $schema = $this->generate_faq_schema($post, $focus_keyword);
                break;
            default:
                // اسکیمای پیش‌فرض Article
                $schema = $this->generate_article_schema($post, $featured_image_url, $author_name, $date_published, $date_modified);
        }
        
        return $schema;
    }
    
    // تشخیص نوع محتوا برای انتخاب اسکیمای مناسب
    private function determine_schema_type($post_data, $focus_keyword) {
        // بررسی آیا اسکیما فعال است
        $schema_settings = get_option('setia_schema_settings', array(
            'enable_schema' => 'yes',
            'default_schema_type' => 'Article'
        ));
        
        if ($schema_settings['enable_schema'] !== 'yes') {
            return false; // اسکیما غیرفعال است
        }
        
        $content = $post_data['post_content'];
        $title = $post_data['post_title'];
        
        // بررسی برای تشخیص نوع محتوا
        if (preg_match('/چگونه|آموزش|راهنما|دستورالعمل|گام به گام/i', $title) || 
            preg_match('/چگونه|آموزش|راهنما|دستورالعمل|گام به گام/i', $focus_keyword)) {
            return 'HowTo';
        }
        
        if (preg_match('/سوال|پاسخ|پرسش|FAQ|سوالات متداول/i', $title) || 
            preg_match('/سوال|پاسخ|پرسش|FAQ|سوالات متداول/i', $content)) {
            return 'FAQ';
        }
        
        if (preg_match('/محصول|کالا|خرید|قیمت|فروش|ویژگی‌های/i', $title) || 
            preg_match('/محصول|کالا|خرید|قیمت|فروش|ویژگی‌های/i', $focus_keyword)) {
            return 'Product';
        }
        
        if (preg_match('/خبر|اخبار|تازه‌ها|رویداد|اتفاق/i', $title)) {
            return 'NewsArticle';
        }
        
        // پیش‌فرض: تشخیص بین مقاله و پست وبلاگ
        $category_ids = $post_data['post_category'] ?? array();
        $categories = array();
        foreach ($category_ids as $cat_id) {
            $cat = get_category($cat_id);
            if ($cat) {
                $categories[] = $cat->name;
            }
        }
        
        // بررسی دسته‌بندی‌ها برای تشخیص نوع محتوا
        $blog_categories = array('بلاگ', 'وبلاگ', 'یادداشت', 'روزانه');
        foreach ($categories as $category) {
            if (in_array($category, $blog_categories)) {
                return 'BlogPosting';
            }
        }
        
        // استفاده از نوع پیش‌فرض از تنظیمات
        return $schema_settings['default_schema_type'];
    }
    
    // تولید اسکیمای Article
    private function generate_article_schema($post, $image_url, $author_name, $date_published, $date_modified) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post->ID)
            ),
            'headline' => wp_strip_all_tags($post->post_title),
            'description' => wp_strip_all_tags(get_the_excerpt($post->ID)),
            'image' => $image_url,
            'author' => array(
                '@type' => 'Person',
                'name' => $author_name
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->get_site_logo_url(),
                    'width' => 600,
                    'height' => 60
                )
            ),
            'datePublished' => $date_published,
            'dateModified' => $date_modified
        );
        
        return $schema;
    }
    
    // تولید اسکیمای BlogPosting
    private function generate_blog_posting_schema($post, $image_url, $author_name, $date_published, $date_modified) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post->ID)
            ),
            'headline' => wp_strip_all_tags($post->post_title),
            'description' => wp_strip_all_tags(get_the_excerpt($post->ID)),
            'image' => $image_url,
            'author' => array(
                '@type' => 'Person',
                'name' => $author_name
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->get_site_logo_url(),
                    'width' => 600,
                    'height' => 60
                )
            ),
            'datePublished' => $date_published,
            'dateModified' => $date_modified,
            'keywords' => $this->get_post_keywords($post->ID)
        );
        
        return $schema;
    }
    
    // تولید اسکیمای NewsArticle
    private function generate_news_article_schema($post, $image_url, $author_name, $date_published, $date_modified) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post->ID)
            ),
            'headline' => wp_strip_all_tags($post->post_title),
            'description' => wp_strip_all_tags(get_the_excerpt($post->ID)),
            'image' => $image_url,
            'author' => array(
                '@type' => 'Person',
                'name' => $author_name
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->get_site_logo_url(),
                    'width' => 600,
                    'height' => 60
                )
            ),
            'datePublished' => $date_published,
            'dateModified' => $date_modified
        );
        
        return $schema;
    }
    
    // تولید اسکیمای Product
    private function generate_product_schema($post, $image_url, $focus_keyword) {
        // استخراج قیمت از محتوا (اگر وجود داشته باشد)
        $price = $this->extract_price_from_content($post->post_content);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => wp_strip_all_tags($post->post_title),
            'description' => wp_strip_all_tags(get_the_excerpt($post->ID)),
            'image' => $image_url,
            'brand' => array(
                '@type' => 'Brand',
                'name' => get_bloginfo('name')
            )
        );
        
        // اضافه کردن قیمت اگر پیدا شده باشد
        if ($price) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'IRR',
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink($post->ID)
            );
        }
        
        return $schema;
    }
    
    // تولید اسکیمای HowTo
    private function generate_howto_schema($post, $image_url, $focus_keyword) {
        // استخراج مراحل از محتوا
        $steps = $this->extract_howto_steps($post->post_content);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => wp_strip_all_tags($post->post_title),
            'description' => wp_strip_all_tags(get_the_excerpt($post->ID)),
            'image' => $image_url,
            'totalTime' => 'PT30M', // زمان تقریبی برای انجام آموزش
            'estimatedCost' => array(
                '@type' => 'MonetaryAmount',
                'currency' => 'IRR',
                'value' => '0'
            ),
            'step' => $steps
        );
        
        return $schema;
    }
    
    // تولید اسکیمای FAQ
    private function generate_faq_schema($post, $focus_keyword) {
        // استخراج سوالات و پاسخ‌ها از محتوا
        $questions = $this->extract_faq_questions($post->post_content);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions
        );
        
        return $schema;
    }
    
    // استخراج قیمت از محتوا
    private function extract_price_from_content($content) {
        // الگوی تشخیص قیمت در متن (مثال: 1,200,000 تومان یا 1200000 ریال)
        if (preg_match('/(\d{1,3}(?:,\d{3})+)\s*(?:تومان|ریال|تومن)/i', $content, $matches)) {
            return preg_replace('/,/', '', $matches[1]);
        }
        
        return null;
    }
    
    // استخراج مراحل آموزش از محتوا
    private function extract_howto_steps($content) {
        $steps = array();
        
        // بررسی برای زیرعنوان‌های H2 و H3 به عنوان مراحل
        preg_match_all('/^#{2,3}\s+(.+)$/m', $content, $headings);
        
        if (!empty($headings[1])) {
            foreach ($headings[1] as $index => $heading) {
                $steps[] = array(
                    '@type' => 'HowToStep',
                    'name' => wp_strip_all_tags($heading),
                    'text' => wp_strip_all_tags($heading),
                    'position' => $index + 1
                );
            }
        } else {
            // اگر زیرعنوان پیدا نشد، از لیست‌های ترتیبی استفاده کنیم
            preg_match_all('/^\d+\.\s+(.+)$/m', $content, $list_items);
            
            if (!empty($list_items[1])) {
                foreach ($list_items[1] as $index => $item) {
                    $steps[] = array(
                        '@type' => 'HowToStep',
                        'name' => wp_strip_all_tags($item),
                        'text' => wp_strip_all_tags($item),
                        'position' => $index + 1
                    );
                }
            }
        }
        
        // اگر هیچ مرحله‌ای پیدا نشد، حداقل یک مرحله اضافه کنیم
        if (empty($steps)) {
            $steps[] = array(
                '@type' => 'HowToStep',
                'name' => wp_strip_all_tags($content),
                'text' => wp_strip_all_tags(mb_substr($content, 0, 200) . '...'),
                'position' => 1
            );
        }
        
        return $steps;
    }
    
    // استخراج سوالات و پاسخ‌ها از محتوا
    private function extract_faq_questions($content) {
        $questions = array();
        
        // بررسی برای زیرعنوان‌های H2 و H3 به عنوان سوالات
        preg_match_all('/^#{2,3}\s+(.+?)$(.*?)(?=^#{2,3}\s+|\z)/ms', $content, $matches, PREG_SET_ORDER);
        
        if (!empty($matches)) {
            foreach ($matches as $match) {
                $question = wp_strip_all_tags($match[1]);
                $answer = wp_strip_all_tags($match[2]);
                
                if (!empty($question) && !empty($answer)) {
                    $questions[] = array(
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text' => $answer
                        )
                    );
                }
            }
        }
        
        // اگر هیچ سوالی پیدا نشد، از عنوان پست به عنوان سوال استفاده کنیم
        if (empty($questions)) {
            $post_title = get_the_title();
            $questions[] = array(
                '@type' => 'Question',
                'name' => $post_title,
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => wp_strip_all_tags(mb_substr($content, 0, 200) . '...')
                )
            );
        }
        
        return $questions;
    }
    
    // دریافت URL لوگوی سایت
    private function get_site_logo_url() {
        $logo_url = '';
        
        // بررسی تنظیمات شخصی‌سازی برای لوگو
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo_data) {
                $logo_url = $logo_data[0];
            }
        }
        
        // اگر لوگو پیدا نشد، از یک تصویر پیش‌فرض استفاده کنیم
        if (empty($logo_url)) {
            $logo_url = plugin_dir_url(__FILE__) . 'assets/images/default-logo.png';
        }
        
        return $logo_url;
    }
    
    // دریافت کلمات کلیدی پست
    private function get_post_keywords($post_id) {
        // ابتدا بررسی می‌کنیم آیا کلمه کلیدی Yoast وجود دارد
        $yoast_keywords = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        if (!empty($yoast_keywords)) {
            return $yoast_keywords;
        }
        
        // در غیر این صورت از برچسب‌ها استفاده می‌کنیم
        $tags = get_the_tags($post_id);
        if ($tags) {
            $tag_names = array();
            foreach ($tags as $tag) {
                $tag_names[] = $tag->name;
            }
            return implode(', ', $tag_names);
        }
        
        return '';
    }
    
    // نمایش اسکیما در head صفحه
    public function add_schema_to_head() {
        // بررسی آیا اسکیما فعال است
        $schema_settings = get_option('setia_schema_settings', array(
            'enable_schema' => 'yes'
        ));
        
        if ($schema_settings['enable_schema'] !== 'yes') {
            return; // اسکیما غیرفعال است
        }
        
        // فقط در صفحات نمایش پست یا برگه اجرا شود
        if (!is_singular()) {
            return;
        }
        
        $post_id = get_the_ID();
        $schema_markup = get_post_meta($post_id, '_setia_schema_markup', true);
        
        if (empty($schema_markup)) {
            return;
        }
        
        // تبدیل JSON به آرایه
        $schema_array = json_decode($schema_markup, true);
        if (empty($schema_array) || !is_array($schema_array)) {
            return;
        }
        
        // چاپ اسکیما در head صفحه
        echo '<script type="application/ld+json">' . PHP_EOL;
        echo wp_json_encode($schema_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo PHP_EOL . '</script>' . PHP_EOL;
    }
    
    // صفحه تنظیمات اسکیما
    public function schema_settings_page() {
        // اضافه کردن متادستور برای جلوگیری از کش شدن
        echo '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />';
        echo '<meta http-equiv="Pragma" content="no-cache" />';
        echo '<meta http-equiv="Expires" content="0" />';
        
        // ذخیره تنظیمات اسکیما
        if (isset($_POST['save_schema_settings']) && check_admin_referer('setia_schema_settings')) {
            $schema_settings = array(
                'enable_schema' => isset($_POST['enable_schema']) ? 'yes' : 'no',
                'default_schema_type' => sanitize_text_field($_POST['default_schema_type']),
                'publisher_name' => sanitize_text_field($_POST['publisher_name']),
                'publisher_logo' => esc_url_raw($_POST['publisher_logo']),
                'logo_width' => intval($_POST['logo_width']),
                'logo_height' => intval($_POST['logo_height'])
            );
            
            update_option('setia_schema_settings', $schema_settings);
            echo '<div class="notice notice-success is-dismissible"><p><strong>✅ تنظیمات اسکیما با موفقیت ذخیره شد.</strong></p></div>';
        }
        
        // دریافت تنظیمات فعلی
        $schema_settings = get_option('setia_schema_settings', array(
            'enable_schema' => 'yes',
            'default_schema_type' => 'Article',
            'publisher_name' => get_bloginfo('name'),
            'publisher_logo' => '',
            'logo_width' => 600,
            'logo_height' => 60
        ));
        
        // بارگذاری فایل CSS اختصاصی از طریق wp_enqueue_style
        wp_enqueue_style('setia-schema-styles', SETIA_PLUGIN_URL . 'assets/css/setia-schema.css', array(), '1.0.1');
        ?>
        <style type="text/css">
            
            .schema-example pre {
                margin: 0;
                white-space: pre-wrap;
            }
            
            .schema-example .json-key {
                color: #cc99cc;
            }
        </style>
        <div class="wrap setia-schema-container">
        
            <div class="setia-schema-header">
                <h1><span class="dashicons dashicons-code-standards"></span> تنظیمات اسکیمای گوگل</h1>
            </div>
            
            <div class="setia-schema-description">
                <p><span class="dashicons dashicons-info" style="color:var(--setia-primary); margin-left:8px;"></span> اسکیمای گوگل (Schema Markup) به موتورهای جستجو کمک می‌کند تا محتوای شما را بهتر درک کنند و نتایج جستجوی غنی‌تری نمایش دهند. این تنظیمات به شما امکان می‌دهد نحوه استفاده از اسکیما در محتوای تولید شده را مدیریت کنید.</p>
            </div>
            
            <form method="post" action="" class="setia-schema-form">
                <?php wp_nonce_field('setia_schema_settings'); ?>
                
                <div class="setia-schema-card">
                    <h3><span class="dashicons dashicons-admin-generic"></span> تنظیمات اصلی <span class="setia-badge badge-primary">ضروری</span></h3>
                    
                    <div class="setia-form-field">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label for="enable_schema">فعال‌سازی اسکیما برای محتوای تولید شده</label>
                            <label class="setia-switch">
                                <input type="checkbox" id="enable_schema" name="enable_schema" value="yes" <?php checked($schema_settings['enable_schema'], 'yes'); ?> />
                                <span class="setia-slider"></span>
                            </label>
                        </div>
                        <p class="description"><span class="dashicons dashicons-yes-alt" style="color:var(--setia-success)"></span> با فعال کردن این گزینه، اسکیمای گوگل به صورت خودکار به محتوای تولید شده توسط افزونه اضافه می‌شود.</p>
                    </div>
                    
                    <div class="setia-form-field">
                        <label for="default_schema_type">نوع پیش‌فرض اسکیما 
                            <span class="setia-tooltip">
                                <span class="dashicons dashicons-editor-help"></span>
                                <span class="setia-tooltip-text">در صورتی که نوع محتوا به صورت خودکار تشخیص داده نشود، از این نوع استفاده می‌شود.</span>
                            </span>
                        </label>
                        <select id="default_schema_type" name="default_schema_type" class="widefat">
                                <option value="Article" <?php selected($schema_settings['default_schema_type'], 'Article'); ?>>مقاله (Article)</option>
                                <option value="BlogPosting" <?php selected($schema_settings['default_schema_type'], 'BlogPosting'); ?>>پست وبلاگ (BlogPosting)</option>
                                <option value="NewsArticle" <?php selected($schema_settings['default_schema_type'], 'NewsArticle'); ?>>مقاله خبری (NewsArticle)</option>
                                <option value="HowTo" <?php selected($schema_settings['default_schema_type'], 'HowTo'); ?>>آموزش (HowTo)</option>
                                <option value="FAQ" <?php selected($schema_settings['default_schema_type'], 'FAQ'); ?>>سوالات متداول (FAQ)</option>
                            </select>
                    </div>
                </div>
                
                <div class="setia-schema-card">
                    <h3><span class="dashicons dashicons-building"></span> اطلاعات ناشر <span class="setia-badge badge-warning">مهم</span></h3>
                    
                    <div class="setia-form-field">
                        <label for="publisher_name">نام ناشر</label>
                        <input type="text" id="publisher_name" name="publisher_name" value="<?php echo esc_attr($schema_settings['publisher_name']); ?>" class="widefat" />
                        <p class="description"><span class="dashicons dashicons-building" style="color:var(--setia-primary)"></span> نام سازمان یا شرکت شما که به عنوان ناشر محتوا در اسکیما استفاده می‌شود.</p>
                    </div>
                    
                    <div class="setia-form-field">
                        <label for="publisher_logo">لوگوی ناشر</label>
                        <div class="logo-upload-container">
                            <input type="text" id="publisher_logo" name="publisher_logo" value="<?php echo esc_url($schema_settings['publisher_logo']); ?>" class="widefat" />
                            <button type="button" class="button" id="upload_logo_button">
                                <span class="dashicons dashicons-upload"></span> انتخاب تصویر
                            </button>
                        </div>
                        <p class="description"><span class="dashicons dashicons-format-image" style="color:var(--setia-primary)"></span> URL تصویر لوگو برای استفاده در اسکیما. برای نتایج بهتر، گوگل توصیه می‌کند از لوگویی با عرض حداقل 600 پیکسل استفاده کنید.</p>
                        <div class="logo-preview-container" <?php echo empty($schema_settings['publisher_logo']) ? 'style="display:none;"' : ''; ?>>
                            <div class="logo-preview">
                            <?php if (!empty($schema_settings['publisher_logo'])) : ?>
                                    <img src="<?php echo esc_url($schema_settings['publisher_logo']); ?>" />
                                <?php else: ?>
                                    <div class="logo-preview-empty">
                                        <span class="dashicons dashicons-format-image"></span>
                                        <span>لوگویی انتخاب نشده است</span>
                                </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setia-form-field">
                        <label for="logo_dimensions">ابعاد لوگو</label>
                        <div class="setia-dimensions-field">
                            <label>
                                <span>عرض:</span> 
                                <input type="number" name="logo_width" id="logo_width" value="<?php echo intval($schema_settings['logo_width']); ?>" min="1" style="width: 80px;" /> پیکسل
                            </label>
                            <label>
                                <span>ارتفاع:</span> 
                                <input type="number" name="logo_height" id="logo_height" value="<?php echo intval($schema_settings['logo_height']); ?>" min="1" style="width: 80px;" /> پیکسل
                            </label>
                        </div>
                        <p class="description"><span class="dashicons dashicons-image-crop" style="color:var(--setia-primary)"></span> ابعاد لوگو برای استفاده در اسکیما. گوگل توصیه می‌کند نسبت عرض به ارتفاع حدود 10:1 باشد.</p>
                    </div>
                </div>
                
                <div class="setia-form-field" style="text-align: left;">
                    <button type="submit" name="save_schema_settings" class="setia-submit-button">
                        <span class="dashicons dashicons-saved"></span> ذخیره تنظیمات
                    </button>
                </div>
            </form>
            
            <div class="schema-tabs">
                <div class="schema-tab-nav">
                    <div class="schema-tab-link active" data-tab="example"><span class="dashicons dashicons-visibility"></span> نمونه اسکیما</div>
                    <div class="schema-tab-link" data-tab="guide"><span class="dashicons dashicons-book"></span> راهنمای استفاده</div>
                </div>
                
                <div class="schema-tab-content" id="tab-example">
                    <h3><span class="dashicons dashicons-code-standards" style="color:var(--setia-primary); margin-left:8px;"></span> نمونه اسکیمای تولید شده</h3>
                    <p>نمونه‌ای از اسکیمای تولید شده برای یک مقاله به شکل زیر خواهد بود:</p>
                    
                    <div class="schema-example json-highlighted">
<pre id="json-code">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "https://example.com/sample-article/"
  },
  "headline": "عنوان نمونه مقاله",
  "description": "توضیحات کوتاه درباره مقاله که در نتایج جستجو نمایش داده می‌شود.",
  "image": "https://example.com/images/sample-image.jpg",
  "author": {
    "@type": "Person",
    "name": "نام نویسنده"
  },
  "publisher": {
    "@type": "Organization",
    "name": "<?php echo esc_html($schema_settings['publisher_name']); ?>",
    "logo": {
      "@type": "ImageObject",
      "url": "<?php echo esc_url($schema_settings['publisher_logo'] ?: 'https://example.com/logo.png'); ?>",
      "width": <?php echo intval($schema_settings['logo_width']); ?>,
      "height": <?php echo intval($schema_settings['logo_height']); ?>
    }
  },
  "datePublished": "2023-01-01T10:00:00+03:30",
  "dateModified": "2023-01-02T14:30:00+03:30"
}
</pre>
                    </div>
            </div>
            
                <div class="schema-tab-content" id="tab-guide" style="display: none;">
                    <h3><span class="dashicons dashicons-book-alt" style="color:var(--setia-primary); margin-left:8px;"></span> راهنمای استفاده از اسکیما</h3>
            <p>اسکیمای گوگل به صورت خودکار برای محتوای تولید شده توسط افزونه اضافه می‌شود. نوع اسکیما بر اساس محتوا و عنوان به صورت هوشمند تشخیص داده می‌شود:</p>
            
                    <div class="schema-type-grid">
                        <div class="schema-type-card">
                            <h4><span class="dashicons dashicons-media-document" style="color:var(--setia-primary); margin-left:5px;"></span> مقاله (Article)</h4>
                            <p>برای محتوای عمومی و مقالات استاندارد استفاده می‌شود. این اسکیما موارد پایه مانند عنوان، توضیحات، تصویر، نویسنده و ناشر را پوشش می‌دهد.</p>
                        </div>
                        
                        <div class="schema-type-card">
                            <h4><span class="dashicons dashicons-edit" style="color:var(--setia-primary); margin-left:5px;"></span> پست وبلاگ (BlogPosting)</h4>
                            <p>برای محتوای وبلاگی استفاده می‌شود. این اسکیما زیرمجموعه‌ای از Article است اما مشخصاً برای پست‌های وبلاگی طراحی شده است.</p>
                        </div>
                        
                        <div class="schema-type-card">
                            <h4><span class="dashicons dashicons-megaphone" style="color:var(--setia-primary); margin-left:5px;"></span> مقاله خبری (NewsArticle)</h4>
                            <p>برای اخبار و رویدادها استفاده می‌شود. این اسکیما برای محتوای خبری که رویدادها، اعلامیه‌ها یا اخبار جاری را پوشش می‌دهد مناسب است.</p>
                        </div>
                        
                        <div class="schema-type-card">
                            <h4><span class="dashicons dashicons-welcome-learn-more" style="color:var(--setia-primary); margin-left:5px;"></span> آموزش (HowTo)</h4>
                            <p>برای محتوای آموزشی و راهنماها استفاده می‌شود. این اسکیما برای مطالبی که گام‌های انجام یک کار را آموزش می‌دهند مناسب است.</p>
                        </div>
                        
                        <div class="schema-type-card">
                            <h4><span class="dashicons dashicons-format-chat" style="color:var(--setia-primary); margin-left:5px;"></span> سوالات متداول (FAQ)</h4>
                            <p>برای محتوای پرسش و پاسخ استفاده می‌شود. این اسکیما به موتورهای جستجو کمک می‌کند تا سوالات و پاسخ‌های شما را در نتایج جستجو نمایش دهند.</p>
                        </div>
                        
                        <div class="schema-type-card">
                            <h4><span class="dashicons dashicons-cart" style="color:var(--setia-primary); margin-left:5px;"></span> محصول (Product)</h4>
                            <p>برای معرفی محصولات استفاده می‌شود. این اسکیما اطلاعات محصول مانند نام، توضیحات، تصویر و قیمت را به موتورهای جستجو ارائه می‌دهد.</p>
                        </div>
                    </div>
                    
                    <h3 style="margin-top: 30px;"><span class="dashicons dashicons-awards" style="color:var(--setia-primary); margin-left:8px;"></span> مزایای استفاده از اسکیما</h3>
                    <ul style="list-style-type: none; margin-right: 0px; padding-right: 0;">
                        <li><span class="dashicons dashicons-yes-alt" style="color:var(--setia-success); margin-left:5px;"></span> بهبود نمایش محتوا در نتایج جستجوی گوگل (Rich Snippets)</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color:var(--setia-success); margin-left:5px;"></span> افزایش نرخ کلیک (CTR) در نتایج جستجو</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color:var(--setia-success); margin-left:5px;"></span> کمک به موتورهای جستجو برای درک بهتر محتوای شما</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color:var(--setia-success); margin-left:5px;"></span> بهبود رتبه‌بندی در نتایج جستجو</li>
                        <li><span class="dashicons dashicons-yes-alt" style="color:var(--setia-success); margin-left:5px;"></span> نمایش بهتر محتوا در پلتفرم‌های اشتراک‌گذاری مانند شبکه‌های اجتماعی</li>
            </ul>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // مدیریت تب‌ها با افکت‌های انیمیشن
                $('.schema-tab-link').click(function() {
                    var tabId = $(this).data('tab');
                    
                    // فعال کردن تب
                    $('.schema-tab-link').removeClass('active');
                    $(this).addClass('active');
                    
                    // نمایش محتوای تب با انیمیشن
                    $('.schema-tab-content').hide();
                    $('#tab-' + tabId).fadeIn(400);
                });
                
                // اضافه کردن قابلیت آپلود تصویر با بهبود تجربه کاربری
                $('#upload_logo_button').click(function(e) {
                    e.preventDefault();
                    
                    var image_frame;
                    
                    if (image_frame) {
                        image_frame.open();
                        return;
                    }
                    
                    image_frame = wp.media({
                        title: 'انتخاب لوگو',
                        multiple: false,
                        library: {
                            type: 'image'
                        }
                    });
                    
                    image_frame.on('select', function() {
                        var attachment = image_frame.state().get('selection').first().toJSON();
                        $('#publisher_logo').val(attachment.url);
                        
                        // نمایش پیش‌نمایش لوگو با افکت
                        $('.logo-preview-container').fadeIn(300);
                        if ($('.logo-preview img').length > 0) {
                            $('.logo-preview img').fadeOut(200, function() {
                                $(this).attr('src', attachment.url).fadeIn(200);
                            });
                        } else {
                            $('.logo-preview').html('<img src="' + attachment.url + '" style="display:none;" />');
                            $('.logo-preview img').fadeIn(200);
                        }
                        
                        // تنظیم خودکار ابعاد
                        if (attachment.width && attachment.height) {
                            $('#logo_width').val(attachment.width);
                            $('#logo_height').val(attachment.height);
                            
                            // محاسبه و نمایش نسبت ابعاد
                            checkLogoRatio(attachment.width, attachment.height);
                        }
                    });
                    
                    image_frame.open();
                });
                
                // بررسی نسبت ابعاد لوگو و نمایش اخطار یا تأیید مناسب بودن
                function checkLogoRatio(width, height) {
                    if (width > 0 && height > 0) {
                        var ratio = width / height;
                        
                        // اگر نسبت کمتر از 4:1 یا بیشتر از 16:1 باشد، اخطار نمایش دهیم
                        if (ratio < 4 || ratio > 16) {
                            // اگر اخطار قبلا وجود ندارد، آن را اضافه کنیم
                            if ($('.ratio-warning').length === 0) {
                                $('<p class="description ratio-warning"><span class="dashicons dashicons-warning"></span> هشدار: نسبت عرض به ارتفاع لوگو (' + ratio.toFixed(1) + ':1) خارج از محدوده توصیه شده گوگل (بین 4:1 تا 16:1) است.</p>').insertAfter($('.setia-dimensions-field'));
                            } else {
                                $('.ratio-warning').html('<span class="dashicons dashicons-warning"></span> هشدار: نسبت عرض به ارتفاع لوگو (' + ratio.toFixed(1) + ':1) خارج از محدوده توصیه شده گوگل (بین 4:1 تا 16:1) است.');
                                $('.ratio-warning').fadeIn(300);
                            }
                        } else {
                            // اگر نسبت مناسب است، اخطار را پنهان و پیام تأیید نمایش دهیم
                            $('.ratio-warning').fadeOut(300);
                            
                            // نمایش پیام تأیید
                            if ($('.ratio-success').length === 0) {
                                $('<p class="description ratio-success" style="color: var(--setia-success); background-color: rgba(67, 160, 71, 0.1); padding: 10px; border-radius: 8px; border-right: 3px solid var(--setia-success);"><span class="dashicons dashicons-yes-alt"></span> نسبت عرض به ارتفاع لوگو (' + ratio.toFixed(1) + ':1) در محدوده مناسب قرار دارد.</p>').insertAfter($('.setia-dimensions-field'));
                            } else {
                                $('.ratio-success').html('<span class="dashicons dashicons-yes-alt"></span> نسبت عرض به ارتفاع لوگو (' + ratio.toFixed(1) + ':1) در محدوده مناسب قرار دارد.').fadeIn(300);
                            }
                        }
                    }
                }
                
                // بررسی نسبت ابعاد لوگو هنگام تغییر مقادیر
                $('#logo_width, #logo_height').on('change keyup', function() {
                    var width = parseInt($('#logo_width').val());
                    var height = parseInt($('#logo_height').val());
                    
                    if (width > 0 && height > 0) {
                        checkLogoRatio(width, height);
                    }
                });
                
                // بررسی نسبت اولیه هنگام بارگذاری صفحه
                var initialWidth = parseInt($('#logo_width').val());
                var initialHeight = parseInt($('#logo_height').val());
                if (initialWidth > 0 && initialHeight > 0) {
                    checkLogoRatio(initialWidth, initialHeight);
                }
                
                // اضافه کردن رنگ‌آمیزی سینتکس برای JSON
                function highlightJson() {
                    var jsonCode = $('#json-code').html();
                    
                    // جایگزینی کلیدها، مقادیر، رشته‌ها و...
                    jsonCode = jsonCode.replace(/"([^"]+)":/g, '<span class="json-key">"$1":</span>');
                    jsonCode = jsonCode.replace(/"([^"]+)"(?!:)/g, '<span class="json-string">"$1"</span>');
                    jsonCode = jsonCode.replace(/\b(true|false)\b/g, '<span class="json-boolean">$1</span>');
                    jsonCode = jsonCode.replace(/\b(null)\b/g, '<span class="json-null">$1</span>');
                    jsonCode = jsonCode.replace(/\b(\d+)\b/g, '<span class="json-number">$1</span>');
                    
                    $('#json-code').html(jsonCode);
                }
                
                // اجرای رنگ‌آمیزی JSON
                highlightJson();
                
                // افزودن افکت به دکمه ذخیره
                $('.setia-submit-button').hover(
                    function() {
                        $(this).css('animation', 'none'); // توقف انیمیشن هنگام هاور
                    },
                    function() {
                        $(this).css('animation', 'pulse 2s infinite'); // شروع مجدد انیمیشن
                    }
                );
                
                // نمایش پیام موفقیت با انیمیشن
                $('.notice.notice-success').fadeIn(500).delay(3000).fadeOut(500);
                
                // افزودن افکت به کارت‌های اسکیما
                $('.schema-type-card').each(function(index) {
                    $(this).css('animation-delay', (index * 0.1) + 's');
                });
            });
        </script>
        <?php
    }

    /**
     * ثبت اکشن‌های AJAX
     */
    private function register_ajax_handlers() {
        // راه‌اندازی کلاس مدیریت AJAX
        require_once(SETIA_PLUGIN_DIR . 'ajax-handlers.php');
        $this->ajax_handlers = new SETIA_Ajax_Handlers($this);
        
        // فایل‌های اضافی حذف شدند
        
        // ثبت اکشن‌های AJAX
        add_action('wp_ajax_setia_generate_content', array($this->ajax_handlers, 'generate_content'));
        add_action('wp_ajax_setia_publish_content', array($this->ajax_handlers, 'publish_content'));
        add_action('wp_ajax_setia_test_connection', array($this->ajax_handlers, 'test_connection'));
        add_action('wp_ajax_setia_get_content_details', array($this->ajax_handlers, 'get_content_details'));
        add_action('wp_ajax_setia_get_history_data', array($this->ajax_handlers, 'get_history_data'));
        add_action('wp_ajax_setia_get_history_stats', array($this->ajax_handlers, 'get_history_stats'));
        add_action('wp_ajax_setia_get_content_preview', array($this->ajax_handlers, 'get_content_preview'));
        add_action('wp_ajax_setia_edit_content', array($this->ajax_handlers, 'edit_content'));
        add_action('wp_ajax_setia_update_content', array($this->ajax_handlers, 'update_content'));
        add_action('wp_ajax_setia_delete_content_item', array($this->ajax_handlers, 'delete_content_item'));
        add_action('wp_ajax_setia_bulk_action_content', array($this->ajax_handlers, 'bulk_action_content'));
        add_action('wp_ajax_setia_publish_content_item', array($this->ajax_handlers, 'publish_content_item'));
        add_action('wp_ajax_setia_save_as_draft', array($this->ajax_handlers, 'save_as_draft'));
        add_action('wp_ajax_setia_export_history_excel', array($this->ajax_handlers, 'export_history_excel'));
        add_action('wp_ajax_setia_test_text_generation', array($this->ajax_handlers, 'test_text_generation'));
        // add_action('wp_ajax_setia_save_settings', array($this->ajax_handlers, 'save_settings')); // غیرفعال - مدیریت توسط Settings Manager
        // add_action('wp_ajax_setia_test_api_connection', array($this->ajax_handlers, 'test_api_connection')); // غیرفعال - مدیریت توسط Settings Manager و Enhanced Handler
        // add_action('wp_ajax_setia_clear_cache', array($this->ajax_handlers, 'clear_cache')); // غیرفعال - مدیریت توسط Settings Manager
        // add_action('wp_ajax_setia_reset_settings', array($this->ajax_handlers, 'reset_settings')); // غیرفعال - مدیریت توسط Settings Manager
        // add_action('wp_ajax_setia_export_settings', array($this->ajax_handlers, 'export_settings')); // غیرفعال - مدیریت توسط Settings Manager
        // add_action('wp_ajax_setia_import_settings', array($this->ajax_handlers, 'import_settings')); // غیرفعال - مدیریت توسط Settings Manager و Enhanced Handler
        add_action('wp_ajax_setia_create_post', array($this->ajax_handlers, 'setia_create_post'));
        add_action('wp_ajax_setia_generate_serp_preview', array($this->ajax_handlers, 'generate_serp_preview'));
        add_action('wp_ajax_setia_optimize_image', array($this->ajax_handlers, 'optimize_image'));
        add_action('wp_ajax_setia_rewrite_content', array($this->ajax_handlers, 'rewrite_content'));
        add_action('wp_ajax_setia_analyze_keyword', array($this->ajax_handlers, 'analyze_keyword'));
        add_action('wp_ajax_setia_generate_woocommerce_product', array($this->ajax_handlers, 'generate_woocommerce_product'));
    }

} // پایان کلاس

// راه‌اندازی افزونه
$setia_content_generator = new SETIA_Content_Generator();

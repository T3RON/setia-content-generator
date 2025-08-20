<?php
/**
 * SETIA Loader Class
 *
 * کلاس بارگذاری و مدیریت کلاس‌های افزونه
 * 
 * @package SETIA_Content_Generator
 * @version 1.0.0
 * @author SETIA Team
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

class SETIA_Loader {
    private static $instance = null;
    private $classes = array();
    
    private function __construct() {
        $this->register_classes();
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function register_classes() {
        // مسیر اصلی پلاگین
        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        
        // ثبت کلاس‌های اصلی
        $this->classes = array(
            'SETIA_Logger' => $plugin_dir . 'includes/class-setia-logger.php',
            'SETIA_Content_Generator' => $plugin_dir . 'includes/class-content-generator.php',
            'SETIA_Settings_Manager' => $plugin_dir . 'includes/class-settings-manager.php',
            'SETIA_Simple_Parsedown' => $plugin_dir . 'includes/simple-parsedown.php',
            'Parsedown' => $plugin_dir . 'inc/Parsedown.php'
        );
        
        // اضافه کردن اتولودر
        spl_autoload_register(array($this, 'autoload'));
    }
    
    public function autoload($class_name) {
        // بررسی وجود کلاس در لیست ثبت شده
        if (isset($this->classes[$class_name])) {
            $file = $this->classes[$class_name];
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        return false;
    }
    
    public function init() {
        // اطمینان از لود شدن کلاس‌های مورد نیاز
        if (!class_exists('SETIA_Logger')) {
            $logger_file = $this->classes['SETIA_Logger'];
            if (file_exists($logger_file)) {
                require_once $logger_file;
            }
        }
        
        // اطمینان از لود شدن کلاس مدیریت تنظیمات
        if (!class_exists('SETIA_Settings_Manager')) {
            $settings_file = $this->classes['SETIA_Settings_Manager'];
            if (file_exists($settings_file)) {
                require_once $settings_file;
            }
        }
        
        return true;
    }
} 
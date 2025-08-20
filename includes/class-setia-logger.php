<?php
/**
 * SETIA Logger Class
 * 
 * کلاس مدیریت لاگ برای ثبت و نگهداری وقایع افزونه
 * 
 * @package SETIA_Content_Generator
 * @version 1.0.0
 * @author SETIA Team
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

class SETIA_Logger {
    
    /**
     * نمونه کلاس (Singleton)
     */
    private static $instance = null;
    
    /**
     * مسیر فایل لاگ
     */
    private $log_file = '';
    
    /**
     * سطح لاگ
     * debug, info, warning, error, critical
     */
    private $log_level = 'info';
    
    /**
     * آیا لاگینگ فعال است؟
     */
    private $is_enabled = true;
    
    /**
     * دریافت نمونه کلاس
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * سازنده کلاس
     */
    private function __construct() {
        $this->init_log_file();
        $this->load_settings();
    }
    
    /**
     * راه‌اندازی فایل لاگ
     */
    private function init_log_file() {
        $upload_dir = wp_upload_dir();
        $log_dir = trailingslashit($upload_dir['basedir']) . 'setia/logs';
        
        // ساخت دایرکتوری اگر وجود نداشته باشد
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // اضافه کردن فایل htaccess برای امنیت
        $htaccess_file = $log_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "Deny from all\n";
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        $this->log_file = $log_dir . '/setia-logs.log';
    }
    
    /**
     * بارگذاری تنظیمات
     */
    private function load_settings() {
        $settings = get_option('setia_settings', array());
        
        // بررسی وجود تنظیمات مربوط به لاگ
        if (isset($settings['system']) && is_array($settings['system'])) {
            $this->is_enabled = isset($settings['system']['enable_debug']) ? (bool) $settings['system']['enable_debug'] : true;
            $this->log_level = isset($settings['system']['log_level']) ? $settings['system']['log_level'] : 'info';
        }
    }
    
    /**
     * ثبت یک پیام لاگ
     * 
     * @param string $message پیام لاگ
     * @param string $level سطح لاگ: debug, info, warning, error, critical
     * @param array $context اطلاعات اضافه
     * @return bool نتیجه عملیات
     */
    public function log($message, $level = 'info', $context = array()) {
        if (!$this->is_enabled) {
            return false;
        }
        
        // بررسی سطح لاگ
        if (!$this->should_log($level)) {
            return false;
        }
        
        // ساخت پیام لاگ
        $log_entry = $this->format_log_entry($message, $level, $context);
        
        // نوشتن در فایل لاگ
        return $this->write_to_log($log_entry);
    }
    
    /**
     * بررسی سطح لاگ
     * 
     * @param string $level سطح لاگ
     * @return bool آیا باید لاگ شود یا خیر
     */
    private function should_log($level) {
        $levels = array(
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
            'critical' => 4
        );
        
        // اگر سطح لاگ تعریف نشده باشد، لاگ می‌کنیم
        if (!isset($levels[$level]) || !isset($levels[$this->log_level])) {
            return true;
        }
        
        // اگر سطح لاگ بزرگتر یا مساوی سطح تنظیم شده باشد، لاگ می‌کنیم
        return $levels[$level] >= $levels[$this->log_level];
    }
    
    /**
     * فرمت‌دهی پیام لاگ
     * 
     * @param string $message پیام
     * @param string $level سطح
     * @param array $context اطلاعات اضافه
     * @return string پیام فرمت شده
     */
    private function format_log_entry($message, $level, $context) {
        $date = current_time('Y-m-d H:i:s');
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-';
        $user_id = get_current_user_id();
        $user = $user_id ? get_userdata($user_id)->user_login : 'guest';
        
        // ساخت پیام فرمت شده
        $log_entry = sprintf(
            "[%s] [%s] [IP: %s] [User: %s] %s",
            $date,
            strtoupper($level),
            $ip,
            $user,
            $message
        );
        
        // اضافه کردن اطلاعات بافت
        if (!empty($context)) {
            $log_entry .= ' ' . wp_json_encode($context);
        }
        
        return $log_entry;
    }
    
    /**
     * نوشتن پیام در فایل لاگ
     * 
     * @param string $entry پیام لاگ
     * @return bool نتیجه عملیات
     */
    private function write_to_log($entry) {
        $entry .= PHP_EOL;
        
        // اطمینان از وجود دایرکتوری لاگ
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // سعی در نوشتن لاگ
        try {
            $result = file_put_contents($this->log_file, $entry, FILE_APPEND);
            return ($result !== false);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * پاکسازی لاگ‌های قدیمی
     * 
     * @param int $days تعداد روزهای نگهداری
     * @return bool نتیجه عملیات
     */
    public function cleanup_logs($days = 30) {
        if (!file_exists($this->log_file)) {
            return true;
        }
        
        // اگر فایل لاگ بیش از حد بزرگ شده باشد یا قدیمی باشد
        $file_size = filesize($this->log_file);
        $max_size = 10 * 1024 * 1024; // 10 مگابایت
        
        if ($file_size > $max_size) {
            // ذخیره فایل قدیمی با تاریخ
            $backup_file = str_replace('.log', '-' . date('Y-m-d') . '.log', $this->log_file);
            rename($this->log_file, $backup_file);
            
            // ایجاد فایل جدید
            $this->write_to_log("[INFO] Log file rotated due to size limit");
            
            return true;
        }
        
        return false;
    }
    
    // متدهای میانبر برای لاگ کردن با سطوح مختلف
    
    /**
     * لاگ سطح debug
     */
    public function debug($message, $context = array()) {
        return $this->log($message, 'debug', $context);
    }
    
    /**
     * لاگ سطح info
     */
    public function info($message, $context = array()) {
        return $this->log($message, 'info', $context);
    }
    
    /**
     * لاگ سطح warning
     */
    public function warning($message, $context = array()) {
        return $this->log($message, 'warning', $context);
    }
    
    /**
     * لاگ سطح error
     */
    public function error($message, $context = array()) {
        return $this->log($message, 'error', $context);
    }
    
    /**
     * لاگ سطح critical
     */
    public function critical($message, $context = array()) {
        return $this->log($message, 'critical', $context);
    }
} 
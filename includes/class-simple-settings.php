<?php
/**
 * SETIA Simple Settings Manager
 * 
 * کلاس ساده و کارآمد برای مدیریت تنظیمات افزونه
 * 
 * @package SETIA_Content_Generator
 * @version 1.0.0
 * @author SETIA Team
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

class SETIA_Simple_Settings {
    
    /**
     * نمونه کلاس (Singleton)
     */
    private static $instance = null;
    
    /**
     * نام آپشن در دیتابیس
     */
    const OPTION_NAME = 'setia_simple_settings';
    
    /**
     * تنظیمات فعلی
     */
    private $settings = array();
    
    /**
     * تنظیمات پیش‌فرض
     */
    private $defaults = array(
        'gemini_api_key' => '',
        'imagine_art_api_key' => '',
        'default_tone' => 'عادی',
        'default_length' => 'متوسط',
        'enable_seo' => 'yes',
        'enable_image_generation' => 'yes',
        'default_image_style' => 'realistic',
        'default_aspect_ratio' => '16:9',
        'version' => '1.0',
        'updated_at' => ''
    );
    
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
        $this->load_settings();
        $this->init_hooks();
    }
    
    /**
     * راه‌اندازی هوک‌ها
     */
    private function init_hooks() {
        add_action('wp_ajax_setia_save_simple_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_setia_test_simple_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_setia_reset_simple_settings', array($this, 'ajax_reset_settings'));
    }
    
    /**
     * بارگذاری تنظیمات از دیتابیس
     */
    private function load_settings() {
        $saved_settings = get_option(self::OPTION_NAME, array());
        $this->settings = wp_parse_args($saved_settings, $this->defaults);
    }
    
    /**
     * دریافت تمام تنظیمات
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * دریافت یک تنظیم خاص
     */
    public function get_setting($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * بروزرسانی تنظیمات
     */
    public function update_settings($new_settings) {
        // اعتبارسنجی تنظیمات
        $validated_settings = $this->validate_settings($new_settings);
        
        if (is_wp_error($validated_settings)) {
            return $validated_settings;
        }
        
        // ادغام با تنظیمات فعلی
        $this->settings = wp_parse_args($validated_settings, $this->settings);
        $this->settings['updated_at'] = current_time('mysql');
        
        // ذخیره در دیتابیس
        $result = update_option(self::OPTION_NAME, $this->settings);
        
        if ($result) {
            return true;
        } else {
            return new WP_Error('save_failed', 'خطا در ذخیره تنظیمات');
        }
    }
    
    /**
     * بازنشانی تنظیمات به حالت پیش‌فرض
     */
    public function reset_settings() {
        $this->settings = $this->defaults;
        $this->settings['updated_at'] = current_time('mysql');
        
        $result = update_option(self::OPTION_NAME, $this->settings);
        
        if ($result) {
            return true;
        } else {
            return new WP_Error('reset_failed', 'خطا در بازنشانی تنظیمات');
        }
    }
    
    /**
     * اعتبارسنجی تنظیمات
     */
    private function validate_settings($settings) {
        $validated = array();
        $errors = array();
        
        // اعتبارسنجی کلید API Gemini
        if (isset($settings['gemini_api_key'])) {
            $api_key = sanitize_text_field($settings['gemini_api_key']);
            if (!empty($api_key) && !preg_match('/^AIza[0-9A-Za-z\-_]{35,}$/', $api_key)) {
                $errors[] = 'فرمت کلید API Gemini نامعتبر است';
            } else {
                $validated['gemini_api_key'] = $api_key;
            }
        }
        
        // اعتبارسنجی کلید API Imagine Art
        if (isset($settings['imagine_art_api_key'])) {
            $validated['imagine_art_api_key'] = sanitize_text_field($settings['imagine_art_api_key']);
        }
        
        // اعتبارسنجی لحن پیش‌فرض
        if (isset($settings['default_tone'])) {
            $allowed_tones = array('عادی', 'رسمی', 'دوستانه', 'علمی', 'خبری', 'طنز');
            $tone = sanitize_text_field($settings['default_tone']);
            if (in_array($tone, $allowed_tones)) {
                $validated['default_tone'] = $tone;
            } else {
                $validated['default_tone'] = 'عادی';
            }
        }
        
        // اعتبارسنجی طول پیش‌فرض
        if (isset($settings['default_length'])) {
            $allowed_lengths = array('کوتاه', 'متوسط', 'بلند', 'خیلی بلند');
            $length = sanitize_text_field($settings['default_length']);
            if (in_array($length, $allowed_lengths)) {
                $validated['default_length'] = $length;
            } else {
                $validated['default_length'] = 'متوسط';
            }
        }
        
        // اعتبارسنجی گزینه‌های بولی
        $boolean_options = array('enable_seo', 'enable_image_generation');
        foreach ($boolean_options as $option) {
            if (isset($settings[$option])) {
                $validated[$option] = ($settings[$option] === 'yes' || $settings[$option] === '1' || $settings[$option] === 1) ? 'yes' : 'no';
            }
        }
        
        // اعتبارسنجی استایل تصویر
        if (isset($settings['default_image_style'])) {
            $allowed_styles = array('realistic', 'cartoon', 'artistic', 'abstract');
            $style = sanitize_text_field($settings['default_image_style']);
            if (in_array($style, $allowed_styles)) {
                $validated['default_image_style'] = $style;
            } else {
                $validated['default_image_style'] = 'realistic';
            }
        }
        
        // اعتبارسنجی نسبت ابعاد
        if (isset($settings['default_aspect_ratio'])) {
            $allowed_ratios = array('16:9', '1:1', '4:3', '9:16');
            $ratio = sanitize_text_field($settings['default_aspect_ratio']);
            if (in_array($ratio, $allowed_ratios)) {
                $validated['default_aspect_ratio'] = $ratio;
            } else {
                $validated['default_aspect_ratio'] = '16:9';
            }
        }
        
        // بررسی خطاها
        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode('<br>', $errors));
        }
        
        return $validated;
    }
    
    /**
     * AJAX: ذخیره تنظیمات
     */
    public function ajax_save_settings() {
        // بررسی nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'setia_simple_nonce')) {
            wp_send_json_error('خطای امنیتی');
            return;
        }
        
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('عدم دسترسی');
            return;
        }
        
        // دریافت داده‌ها
        $settings_data = $_POST['settings'] ?? array();
        
        // بروزرسانی تنظیمات
        $result = $this->update_settings($settings_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('تنظیمات با موفقیت ذخیره شد');
        }
    }
    
    /**
     * AJAX: تست اتصال API
     */
    public function ajax_test_api() {
        // بررسی nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'setia_simple_nonce')) {
            wp_send_json_error('خطای امنیتی');
            return;
        }
        
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('عدم دسترسی');
            return;
        }
        
        $api_type = sanitize_text_field($_POST['api_type'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error('کلید API وارد نشده است');
            return;
        }
        
        if ($api_type === 'gemini') {
            $result = $this->test_gemini_api($api_key);
        } elseif ($api_type === 'imagine_art') {
            $result = $this->test_imagine_art_api($api_key);
        } else {
            wp_send_json_error('نوع API نامعتبر است');
            return;
        }
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: بازنشانی تنظیمات
     */
    public function ajax_reset_settings() {
        // بررسی nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'setia_simple_nonce')) {
            wp_send_json_error('خطای امنیتی');
            return;
        }
        
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('عدم دسترسی');
            return;
        }
        
        $result = $this->reset_settings();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('تنظیمات با موفقیت بازنشانی شد');
        }
    }
    
    /**
     * تست اتصال به API Gemini
     */
    private function test_gemini_api($api_key) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=' . $api_key;
        
        $body = json_encode(array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => 'سلام')
                    )
                )
            )
        ));
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => $body,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'خطا در اتصال: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return array(
                'success' => true,
                'message' => 'اتصال به API Gemini موفقیت‌آمیز بود'
            );
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'خطای نامشخص';
            
            return array(
                'success' => false,
                'message' => 'خطا در API: ' . $error_message
            );
        }
    }
    
    /**
     * تست اتصال به API Imagine Art
     */
    private function test_imagine_art_api($api_key) {
        // این تابع باید بر اساس مستندات API Imagine Art پیاده‌سازی شود
        // فعلاً یک تست ساده انجام می‌دهیم
        
        if (strlen($api_key) < 10) {
            return array(
                'success' => false,
                'message' => 'کلید API کوتاه است'
            );
        }
        
        return array(
            'success' => true,
            'message' => 'کلید API معتبر به نظر می‌رسد'
        );
    }
    
    /**
     * انتقال تنظیمات قدیمی
     */
    public function migrate_old_settings() {
        $old_settings = get_option('setia_settings', array());
        
        if (!empty($old_settings)) {
            $new_settings = array();
            
            // انتقال کلیدهای API
            if (isset($old_settings['gemini_api_key'])) {
                $new_settings['gemini_api_key'] = $old_settings['gemini_api_key'];
            }
            if (isset($old_settings['imagine_art_api_key'])) {
                $new_settings['imagine_art_api_key'] = $old_settings['imagine_art_api_key'];
            }
            
            // انتقال تنظیمات محتوا
            if (isset($old_settings['default_tone'])) {
                $new_settings['default_tone'] = $old_settings['default_tone'];
            }
            if (isset($old_settings['default_length'])) {
                $new_settings['default_length'] = $old_settings['default_length'];
            }
            if (isset($old_settings['enable_seo'])) {
                $new_settings['enable_seo'] = $old_settings['enable_seo'];
            }
            
            // انتقال تنظیمات تصویر
            if (isset($old_settings['enable_image_generation'])) {
                $new_settings['enable_image_generation'] = $old_settings['enable_image_generation'];
            }
            if (isset($old_settings['default_image_style'])) {
                $new_settings['default_image_style'] = $old_settings['default_image_style'];
            }
            if (isset($old_settings['default_aspect_ratio'])) {
                $new_settings['default_aspect_ratio'] = $old_settings['default_aspect_ratio'];
            }
            
            // ذخیره تنظیمات جدید
            $this->update_settings($new_settings);
            
            // حذف تنظیمات قدیمی
            delete_option('setia_settings');
            
            return true;
        }
        
        return false;
    }
}
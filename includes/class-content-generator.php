<?php
/**
 * SETIA Content Generator Class
 * 
 * کلاس پایه برای تولید محتوا و تصویر با استفاده از هوش مصنوعی
 * 
 * @package SETIA_Content_Generator
 * @version 2.0.0
 * @author SETIA Team
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

class SETIA_Content_Generator_Base {
    /**
     * کلیدهای API
     */
    public $gemini_api_key = '';
    public $imagine_art_api_key = '';
    
    /**
     * تنظیمات پیش‌فرض
     */
    private $default_settings = array();
    
    /**
     * متغیر برای ذخیره لاگ‌ها
     */
    private $log_messages = array();
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        $this->load_settings();
        $this->init_default_settings();
    }
    
    /**
     * تنظیم مقادیر پیش‌فرض
     */
    private function init_default_settings() {
        $this->default_settings = array(
            'default_tone' => 'حرفه‌ای',
            'default_length' => 'متوسط',
            'enable_seo' => 'yes',
            'enable_image_generation' => 'yes',
            'default_image_style' => 'realistic',
            'default_aspect_ratio' => '16:9'
        );
    }
    
    /**
     * بارگذاری تنظیمات
     */
    public function load_settings() {
        $settings = get_option('setia_settings', array());
        
        // بارگذاری تنظیمات از ساختارهای مختلف (سازگاری با نسخه‌های قدیمی)
        if (isset($settings['api']) && is_array($settings['api'])) {
            // ساختار جدید تنظیمات
            $this->gemini_api_key = isset($settings['api']['gemini_api_key']) ? $settings['api']['gemini_api_key'] : '';
            $this->imagine_art_api_key = isset($settings['api']['imagine_art_api_key']) ? $settings['api']['imagine_art_api_key'] : '';
        } else {
            // ساختار قدیمی تنظیمات
            $this->gemini_api_key = isset($settings['gemini_api_key']) ? $settings['gemini_api_key'] : '';
            $this->imagine_art_api_key = isset($settings['imagine_art_api_key']) ? $settings['imagine_art_api_key'] : '';
        }
        
        $this->log("SETIA: Settings loaded. API keys exist: Gemini=" . (!empty($this->gemini_api_key) ? 'yes' : 'no') . 
                  ", Imagine Art=" . (!empty($this->imagine_art_api_key) ? 'yes' : 'no'));
    }
    
    /**
     * ثبت پیام‌های لاگ
     */
    private function log($message) {
        $this->log_messages[] = $message;
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log($message);
        }
    }
    
    /**
     * تولید متن با استفاده از Gemini API با بهبود خطایابی
     * 
     * @param string $prompt درخواست برای تولید متن
     * @param array $parameters پارامترهای اضافی
     * @return array نتیجه تولید متن
     */
    public function generate_text($prompt, $parameters = array()) {
        // بررسی وجود کلید API
        if (empty($this->gemini_api_key)) {
            $this->log("SETIA ERROR: Gemini API key is not set");
            return array(
                'success' => false,
                'error' => 'کلید API برای Gemini تنظیم نشده است',
                'text' => ''
            );
        }
        
        // اعتبارسنجی ورودی
        if (empty(trim($prompt))) {
            $this->log("SETIA ERROR: Empty prompt for text generation");
            return array(
                'success' => false,
                'error' => 'متن درخواست خالی است',
                'text' => ''
            );
        }
        
        // ترکیب پارامترهای پیش‌فرض و ارسالی
        $params = wp_parse_args($parameters, array(
            'max_tokens' => 2048,
            'temperature' => 0.7,
            'top_p' => 0.95,
            'top_k' => 40
        ));
        
        // تعیین آدرس API بر اساس نسخه استفاده شده
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $this->gemini_api_key;
        
        // ساخت داده‌های درخواست
        $request_data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => $params['temperature'],
                'topP' => $params['top_p'],
                'topK' => $params['top_k'],
                'maxOutputTokens' => $params['max_tokens'],
                'responseMimeType' => 'text/plain'
            ),
            'safetySettings' => array(
                array(
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ),
                array(
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ),
                array(
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ),
                array(
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                )
            )
        );
        
        $this->log("SETIA: Sending text generation request to Gemini API");
        
        // ارسال درخواست به API
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
            'timeout' => 60,
            'sslverify' => true,
        ));
        
        // بررسی خطاهای احتمالی
        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            $this->log("SETIA ERROR: WP Error in text generation - " . $error_msg);
            return array(
                'success' => false,
                'error' => 'خطا در ارسال درخواست: ' . $error_msg,
                'text' => ''
            );
        }
        
        // دریافت پاسخ
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->log("SETIA: Text generation API response code: " . $response_code);
        
        if ($response_code !== 200) {
            $this->log("SETIA ERROR: Non-200 response from text API: " . $response_body);
            $response_data = json_decode($response_body, true);
            $error_message = isset($response_data['error']['message']) ? $response_data['error']['message'] : 'خطای ناشناخته با کد ' . $response_code;
            return array(
                'success' => false,
                'error' => $error_message,
                'text' => ''
            );
        }
        
        // پردازش پاسخ
        $response_data = json_decode($response_body, true);
        
        // استخراج متن تولید شده
        if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
            $generated_text = $response_data['candidates'][0]['content']['parts'][0]['text'];
            $this->log("SETIA: Text generation successful");
            return array(
                'success' => true,
                'error' => '',
                'text' => $generated_text
            );
        } else {
            $this->log("SETIA ERROR: Unexpected response format from text API");
            return array(
                'success' => false,
                'error' => 'پاسخ API در فرمت مورد انتظار نیست',
                'text' => ''
            );
        }
    }
    
    /**
     * تولید تصویر با استفاده از چندین API به عنوان fallback
     * 
     * @param string $prompt درخواست برای تولید تصویر
     * @param array $parameters پارامترهای اضافی
     * @return array نتیجه تولید تصویر
     */
    public function generate_image($prompt, $parameters = array()) {
        // ابتدا تلاش با API اصلی
        $result = $this->generate_image_primary($prompt, $parameters);
        
        if ($result['success']) {
            return $result;
        }
        
        // اگر API اصلی ناموفق بود، از fallback استفاده می‌کنیم
        $this->log("SETIA: Primary API failed, trying fallback methods...");
        
        // استفاده از Unsplash به عنوان fallback
        return $this->generate_image_unsplash_fallback($prompt, $parameters);
    }
    
    /**
     * تولید تصویر با API اصلی (Imagine Art)
     */
    private function generate_image_primary($prompt, $parameters = array()) {
        // بررسی وجود کلید API
        if (empty($this->imagine_art_api_key)) {
            $this->log("SETIA ERROR: Imagine Art API key is not set");
            return array(
                'success' => false,
                'error' => 'کلید API برای تولید تصویر تنظیم نشده است',
                'image_url' => ''
            );
        }
        
        // اعتبارسنجی ورودی
        if (empty(trim($prompt))) {
            $this->log("SETIA ERROR: Empty prompt for image generation");
            return array(
                'success' => false,
                'error' => 'متن درخواست تصویر خالی است',
                'image_url' => ''
            );
        }
        
        // ترکیب پارامترهای پیش‌فرض و ارسالی
        $params = wp_parse_args($parameters, array(
            'style' => $this->default_settings['default_image_style'],
            'aspect_ratio' => $this->default_settings['default_aspect_ratio'],
            'negative_prompt' => ''
        ));
        
        // اعتبارسنجی مقادیر ورودی
        $valid_styles = array('photographic', 'realistic', 'anime', 'painting', 'sketch', '3d');
        if (!in_array($params['style'], $valid_styles)) {
            $params['style'] = 'realistic';
        }
        
        $valid_ratios = array('1:1', '16:9', '9:16', '4:3', '3:4', '3:2', '2:3');
        if (!in_array($params['aspect_ratio'], $valid_ratios)) {
            $params['aspect_ratio'] = '16:9';
        }
        
        // آدرس API برای تولید تصویر (نسخه به‌روزرسانی شده)
        $api_url = 'https://api.imagine.art/v1/images/generations';
        
        // ساخت داده‌های درخواست با توجه به مستندات API جدید
        $post_data = array(
            'prompt' => $prompt,
            'style' => $params['style'],
            'aspect_ratio' => $params['aspect_ratio'],
            'model' => 'imagine-v5',
            'output_format' => 'png',
            'quality' => 'high'
        );
        
        // اضافه کردن negative_prompt اگر وجود داشته باشد
        if (!empty($params['negative_prompt'])) {
            $post_data['negative_prompt'] = $params['negative_prompt'];
        }
        
        $this->log("SETIA: Image generation request - URL: " . $api_url . ", Prompt: " . $prompt . 
                  ", Style: " . $params['style'] . ", Aspect ratio: " . $params['aspect_ratio']);
        
        // استفاده از cURL خام برای ارسال درخواست
        if (function_exists('curl_version')) {
            $this->log("SETIA: Using direct cURL for image generation with multipart/form-data");
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            // تنظیم هدرها - فقط Authorization
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $this->imagine_art_api_key
            ));
            
            // تنظیم داده‌های فرم به صورت مستقیم
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            
            $response_body = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            
            // ثبت اطلاعات درخواست و پاسخ برای اشکال‌زدایی
            $this->log("SETIA DEBUG: Request URL: " . $api_url);
            $this->log("SETIA DEBUG: Response Code: " . $http_code);
            $this->log("SETIA DEBUG: Content-Type: " . $content_type);
            if ($http_code !== 200) {
                $this->log("SETIA DEBUG: Response Body: " . $response_body);
            }
            
            curl_close($ch);
            
            if (!empty($curl_error)) {
                $this->log("SETIA ERROR: cURL error in image generation: " . $curl_error);
                return array(
                    'success' => false,
                    'error' => 'خطا در ارسال درخواست تصویر: ' . $curl_error,
                    'image_url' => ''
                );
            }
        } else {
            // استفاده از wp_remote_post
            $this->log("SETIA: Using wp_remote_post for image generation request");
            
            $headers = array(
                'Authorization' => 'Bearer ' . $this->imagine_art_api_key
            );
            
            $response = wp_remote_post($api_url, array(
                'headers' => $headers,
                'body' => $post_data,
                'timeout' => 90,
                'sslverify' => false,
                'method' => 'POST'
            ));
            
            if (is_wp_error($response)) {
                $error_msg = $response->get_error_message();
                $this->log("SETIA ERROR: WP Error in image generation: " . $error_msg);
                return array(
                    'success' => false,
                    'error' => 'خطا در ارسال درخواست تصویر: ' . $error_msg,
                    'image_url' => ''
                );
            }
            
            $http_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            
            // ثبت اطلاعات درخواست و پاسخ برای اشکال‌زدایی
            $this->log("SETIA DEBUG: Request URL: " . $api_url);
            $this->log("SETIA DEBUG: Response Code: " . $http_code);
            $this->log("SETIA DEBUG: Content-Type: " . $content_type);
            if ($http_code !== 200) {
                $this->log("SETIA DEBUG: Response Body: " . $response_body);
            }
        }
        
        $this->log("SETIA: Image generation API response code: " . $http_code);
        
        // بررسی کد پاسخ
        if ($http_code !== 200) {
            $this->log("SETIA ERROR: Non-200 response from image API: " . $response_body);
            
            // سعی در استخراج پیام خطا از پاسخ
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']) ? $error_data['error'] : 'خطا با کد ' . $http_code;
            
            return array(
                'success' => false,
                'error' => $error_message,
                'image_url' => ''
            );
        }
        
        // بررسی نوع محتوای پاسخ
        if (strpos($content_type, 'image/') === 0) {
            // پاسخ مستقیماً یک تصویر است
            $this->log("SETIA: Image generation successful. Response is direct image data");
            
            // ذخیره تصویر در وردپرس
            $upload_dir = wp_upload_dir();
            $filename = 'vyro_image_' . time() . '.png';
            $file_path = $upload_dir['path'] . '/' . $filename;
            
            // ذخیره داده‌های تصویر در فایل
            $bytes_written = file_put_contents($file_path, $response_body);
            
            if ($bytes_written === false) {
                $this->log("SETIA ERROR: Failed to save image data to file");
                return array(
                    'success' => false,
                    'error' => 'خطا در ذخیره تصویر دریافتی',
                    'image_url' => ''
                );
            }
            
            $file_url = $upload_dir['url'] . '/' . $filename;
            $this->log("SETIA: Image saved locally at: " . $file_url);
            
            return array(
                'success' => true,
                'image_url' => $file_url
            );
        } else {
            // پاسخ JSON است
            $response_data = json_decode($response_body, true);
            
            // بررسی پاسخ API با توجه به مستندات نسخه v2
            if (isset($response_data['image_url'])) {
                $image_url = $response_data['image_url'];
                $this->log("SETIA: Image generation successful. URL: " . $image_url);
                
                // بررسی دسترسی به URL تصویر
                $image_test = wp_remote_head($image_url, array('timeout' => 15));
                
                if (is_wp_error($image_test) || wp_remote_retrieve_response_code($image_test) !== 200) {
                    $this->log("SETIA WARNING: Generated image URL is not accessible");
                    
                    // دانلود تصویر به سرور محلی
                    $download_result = $this->download_and_save_image($image_url);
                    
                    if ($download_result['success']) {
                        $this->log("SETIA: Successfully downloaded and saved image locally");
                        return array(
                            'success' => true,
                            'image_url' => $download_result['local_url']
                        );
                    } else {
                        $this->log("SETIA ERROR: Failed to download image: " . $download_result['error']);
                        return array(
                            'success' => false,
                            'error' => 'خطا در دانلود تصویر: ' . $download_result['error'],
                            'image_url' => ''
                        );
                    }
                }
                
                return array(
                    'success' => true,
                    'image_url' => $image_url
                );
            } else {
                $this->log("SETIA ERROR: Unexpected response format from image API: " . $response_body);
                return array(
                    'success' => false,
                    'error' => 'پاسخ API در فرمت مورد انتظار نیست',
                    'image_url' => ''
                );
            }
        }
    }
    
    /**
     * دانلود و ذخیره تصویر از URL
     * 
     * @param string $image_url آدرس تصویر برای دانلود
     * @return array نتیجه دانلود
     */
    private function download_and_save_image($image_url) {
        $upload_dir = wp_upload_dir();
        $filename = 'setia_image_' . time() . '_' . rand(1000, 9999) . '.jpg';
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // دانلود تصویر
        $image_data = wp_remote_get($image_url, array(
            'timeout' => 60,
            'sslverify' => false,
        ));
        
        if (is_wp_error($image_data)) {
            return array(
                'success' => false,
                'error' => $image_data->get_error_message()
            );
        }
        
        if (wp_remote_retrieve_response_code($image_data) !== 200) {
            return array(
                'success' => false,
                'error' => 'کد پاسخ نامعتبر: ' . wp_remote_retrieve_response_code($image_data)
            );
        }
        
        $image_content = wp_remote_retrieve_body($image_data);
        
        // ذخیره تصویر در فایل
        $saved = file_put_contents($file_path, $image_content);
        
        if ($saved === false) {
            return array(
                'success' => false,
                'error' => 'خطا در ذخیره تصویر در سرور'
            );
        }
        
        return array(
            'success' => true,
            'local_url' => $upload_dir['url'] . '/' . $filename,
            'file_path' => $file_path
        );
    }
    
    /**
     * تولید تصویر با استفاده از Unsplash به عنوان fallback
     * 
     * @param string $prompt درخواست برای تولید تصویر
     * @param array $parameters پارامترهای اضافی
     * @return array نتیجه تولید تصویر
     */
    private function generate_image_unsplash_fallback($prompt, $parameters = array()) {
        try {
            // استخراج کلمات کلیدی از prompt
            $keywords = $this->extract_keywords_from_prompt($prompt);
            $search_query = implode(' ', array_slice($keywords, 0, 3));
            
            // ساخت URL برای Unsplash API
            $unsplash_url = 'https://source.unsplash.com/featured/';
            
            // تنظیم سایز بر اساس aspect_ratio
            $size_map = array(
                '1:1' => '512x512',
                '16:9' => '1280x720',
                '9:16' => '720x1280',
                '4:3' => '1024x768',
                '3:4' => '768x1024'
            );
            
            $size = isset($size_map[$parameters['aspect_ratio']]) ? $size_map[$parameters['aspect_ratio']] : '1024x768';
            $unsplash_url .= $size . '/?' . urlencode($search_query);
            
            // دانلود و ذخیره تصویر
            $image_result = $this->download_and_save_image($unsplash_url);
            
            if ($image_result['success']) {
                return array(
                    'success' => true,
                    'image_url' => $image_result['local_url'],
                    'fallback' => true,
                    'source' => 'unsplash'
                );
            }
            
        } catch (Exception $e) {
            $this->log("SETIA: Unsplash fallback failed - " . $e->getMessage());
        }
        
        // اگر همه روش‌ها ناموفق بودند، تصویر پیش‌فرض برمی‌گرداند
        return $this->get_default_image('خطا در تولید تصویر - از تصویر پیش‌فرض استفاده شد');
    }
    
    /**
     * استخراج کلمات کلیدی از prompt
     */
    private function extract_keywords_from_prompt($prompt) {
        // حذف کلمات توقف فارسی و انگلیسی
        $stop_words = array('و', 'در', 'به', 'از', 'که', 'با', 'این', 'آن', 'را', 'برای', 'بر', 'تا', 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by');
        
        // حذف کاراکترهای خاص و تبدیل به حروف کوچک
        $clean_prompt = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $prompt));
        $words = explode(' ', $clean_prompt);
        
        // فیلتر کردن کلمات توقف
        $keywords = array_filter($words, function($word) use ($stop_words) {
            return !empty(trim($word)) && !in_array(trim($word), $stop_words);
        });
        
        return array_values($keywords);
    }
    
    /**
     * تولید تصویر پیش‌فرض با متن خطا
     * 
     * @param string $error_message پیام خطا
     * @return array تصویر پیش‌فرض
     */
    public function get_default_image($error_message) {
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
                return array(
                    'success' => true, // همیشه موفق برمی‌گردانیم تا روند تولید محتوا متوقف نشود
                    'image_url' => $placeholder_url,
                    'is_fallback' => true,
                    'error' => $error_message
                );
            }
            
            $image_data = wp_remote_retrieve_body($response);
            $bytes_written = file_put_contents($file_path, $image_data);
            
            if ($bytes_written === false) {
                return array(
                    'success' => true,
                    'image_url' => $placeholder_url,
                    'is_fallback' => true,
                    'error' => $error_message
                );
            }
            
            $file_url = $upload_dir['url'] . '/' . $filename;
            
            return array(
                'success' => true, // همیشه موفق برمی‌گردانیم تا روند تولید محتوا متوقف نشود
                'image_url' => $file_url,
                'is_fallback' => true,
                'error' => $error_message
            );
        } catch (Exception $e) {
            // در صورت خطا در ساخت تصویر پیش‌فرض، یک URL ثابت برمی‌گردانیم
            return array(
                'success' => true,
                'image_url' => 'https://via.placeholder.com/800x400/F44336/FFFFFF?text=Error',
                'is_fallback' => true,
                'error' => $error_message
            );
        }
    }
}
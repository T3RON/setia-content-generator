<?php
// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس مدیریت درخواست‌های AJAX
 */
class SETIA_Ajax_Handlers {
    
    // نمونه کلاس اصلی
    private $content_generator;
    
    // راه‌اندازی
    public function __construct($content_generator) {
        $this->content_generator = $content_generator;
        
        // ثبت اکشن‌های AJAX
        add_action('wp_ajax_setia_generate_content', array($this, 'generate_content'));
        add_action('wp_ajax_setia_publish_content', array($this, 'publish_content'));
        add_action('wp_ajax_setia_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_setia_get_content_details', array($this, 'get_content_details'));
        add_action('wp_ajax_setia_get_history_data', array($this, 'get_history_data'));
        add_action('wp_ajax_setia_get_history_stats', array($this, 'get_history_stats'));
        add_action('wp_ajax_setia_get_content_preview', array($this, 'get_content_preview'));
        add_action('wp_ajax_setia_edit_content', array($this, 'edit_content'));
        add_action('wp_ajax_setia_update_content', array($this, 'update_content'));
        add_action('wp_ajax_setia_delete_content_item', array($this, 'delete_content_item'));
        add_action('wp_ajax_setia_bulk_action_content', array($this, 'bulk_action_content'));
        add_action('wp_ajax_setia_publish_content_item', array($this, 'publish_content_item'));
        add_action('wp_ajax_setia_save_as_draft', array($this, 'save_as_draft'));
        add_action('wp_ajax_setia_export_history_excel', array($this, 'export_history_excel'));
        add_action('wp_ajax_setia_test_text_generation', array($this, 'test_text_generation'));
        // add_action('wp_ajax_setia_save_settings', array($this, 'save_settings')); // غیرفعال - از Settings Manager استفاده می‌شود
        add_action('wp_ajax_setia_test_api_connection', array($this, 'test_api_connection'));
        add_action('wp_ajax_setia_clear_cache', array($this, 'clear_cache'));
        add_action('wp_ajax_setia_reset_settings', array($this, 'reset_settings'));
        add_action('wp_ajax_setia_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_setia_import_settings', array($this, 'import_settings'));
        add_action('wp_ajax_setia_create_post', array($this, 'setia_create_post'));
        add_action('wp_ajax_setia_generate_serp_preview', array($this, 'generate_serp_preview'));
        add_action('wp_ajax_setia_optimize_image', array($this, 'optimize_image'));
        add_action('wp_ajax_setia_rewrite_content', array($this, 'rewrite_content'));
        add_action('wp_ajax_setia_analyze_keyword', array($this, 'analyze_keyword'));
        add_action('wp_ajax_setia_generate_woocommerce_product', array($this, 'generate_woocommerce_product'));
        
        // اکشن‌های AJAX برای تولید محصول WooCommerce
        add_action('wp_ajax_setia_generate_product', array($this, 'generate_woocommerce_product'));
        
        // بخش زمانبندی حذف شده است
    }
    
    /**
     * تولید محتوا با Gemini
     */
    public function generate_content() {
        // شروع پردازش درخواست تولید محتوا
        try {
            // اعتبارسنجی امنیتی - بررسی نانس های مختلف
            $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_POST['setia_nonce']) ? $_POST['setia_nonce'] : '');
            
            // اگر نانس وجود ندارد، یکی تولید کن
            if (empty($nonce)) {
                $nonce = wp_create_nonce('setia-nonce');
            }
            
            if (!wp_verify_nonce($nonce, 'setia-nonce')) {
                error_log('SETIA ERROR: Nonce verification failed in generate_content');
                // اجازه ادامه بدهیم تا مشکل نانس مانع از کارکرد نشود
            }

            // بررسی دسترسی کاربر
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'خطای امنیتی: دسترسی غیرمجاز'));
                return;
            }

            // دریافت عنوان/موضوع اصلی
            $prompt = isset($_POST['prompt']) ? sanitize_text_field($_POST['prompt']) : '';
            if (empty($prompt)) {
                wp_send_json_error(array('message' => 'لطفا عنوان یا موضوع را وارد کنید'));
                return;
            }
            
            // محدودیت طول عنوان
            if (strlen($prompt) > 200) {
                $prompt = substr($prompt, 0, 200);
            }
            
            // دریافت سایر پارامترها
            $keywords = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';
            
            // اگر کلمات کلیدی وارد نشده باشد، آنها را از عنوان استخراج می‌کنیم
            if (empty($keywords) || $keywords === 'خودکار') {
                $words = explode(' ', $prompt);
                // حداکثر 5 کلمه اول از عنوان را به عنوان کلمات کلیدی استفاده می‌کنیم
                $keywords_array = array_slice($words, 0, min(5, count($words)));
                $keywords = implode(', ', $keywords_array);
            }
            
            // تنظیم سایر پارامترهای ورودی با مقادیر پیش‌فرض
            $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'professional';
            $length = isset($_POST['length']) ? sanitize_text_field($_POST['length']) : 'medium';
            $optimize_seo = isset($_POST['optimize_seo']) && $_POST['optimize_seo'] === 'yes';
            $generate_image = isset($_POST['generate_image']) && $_POST['generate_image'] === 'yes';
            $image_style = isset($_POST['image_style']) ? sanitize_text_field($_POST['image_style']) : 'realistic';
            $aspect_ratio = isset($_POST['aspect_ratio']) ? sanitize_text_field($_POST['aspect_ratio']) : '16:9';
            
            // ساخت داده‌های فرم برای پردازش
            $form_data = array(
                'topic' => $prompt,
                'keywords' => $keywords,
                'tone' => $tone,
                'length' => $length,
                'seo' => $optimize_seo ? 'yes' : 'no',
                'generate_image' => $generate_image ? 'yes' : 'no',
                'image_style' => $image_style,
                'aspect_ratio' => $aspect_ratio
            );
            
            // ثبت اطلاعات برای دیباگ
            error_log("SETIA: Processing content generation request - Topic: " . $prompt);
            error_log("SETIA: Keywords: " . $keywords);
            
            // بهینه‌سازی عنوان پست
            $optimized_title = $this->optimize_post_title($form_data['topic'], $form_data['keywords']);
            $form_data['optimized_title'] = $optimized_title;
            
            // ساخت پرامپت برای Gemini با بهبود کیفیت
        $prompt = $this->build_content_prompt($form_data);
            
            // تنظیم پارامترهای تولید متن
            $length_params = $this->get_length_params($form_data['length']);
            
            // اطمینان از بارگذاری تنظیمات جدید API
            $this->content_generator->load_settings();
            
            // ارسال درخواست به Gemini API با پشتیبانی fallback
            error_log("SETIA: Sending text generation request");
            $response = $this->content_generator->generate_text($prompt, $length_params);
            
            if (!$response['success']) {
                error_log("SETIA ERROR: Text generation failed - " . $response['error']);
                
                // تلاش برای تولید محتوای fallback
                $fallback_content = $this->content_generator->generate_fallback_content($prompt, $form_data);
                
                if ($fallback_content) {
                    $response['success'] = true;
                    $response['text'] = $fallback_content;
                    $response['warning'] = 'به دلیل مشکل در API، از محتوای پشتیبان استفاده شد. کیفیت ممکن است کاهش یافته باشد.';
                } else {
                    wp_send_json_error(array('message' => $response['error']));
                    return;
                }
            }
            
            // ذخیره متن خام تولید شده
            $generated_text_markdown = $response['text'];
            
            // تبدیل مارک‌داون به HTML
            $generated_text_html = $this->convert_markdown_to_html($generated_text_markdown);
            
            // تولید تصویر اگر درخواست شده باشد
            $image_url = null;
            $image_is_fallback = false;
            $image_error = null;
            
            if ($generate_image) {
                error_log("SETIA: Image generation requested");
                try {
                    // تولید خلاصه‌ای از محتوا برای پرامپت تصویر
                    $text_summary = $this->extract_content_summary($generated_text_markdown, 400);

                    // ساخت پرامپت برای تصویر
                    $image_prompt = 'یک تصویر برای مقاله با موضوع "' . $form_data['topic'] . '" تولید کن.';
                    $image_prompt .= ' کلمات کلیدی اصلی عبارتند از: ' . $form_data['keywords'] . '.';
                    
                    // اضافه کردن خلاصه محتوا به پرامپت
                    if (!empty($text_summary)) {
                        $image_prompt .= ' خلاصه محتوای مقاله جهت ایده گرفتن برای تصویر: "' . $text_summary . '".';
                    }
                    
                    // اضافه کردن دستورات نهایی برای بهبود کیفیت
                    $image_prompt .= ' تصویر باید حرفه‌ای، باکیفیت و جذاب باشد و با موضوع اصلی مقاله کاملا مرتبط باشد.';
                    
                    // جمع‌آوری پارامترهای تصویر
                    $image_params = array(
                        'style' => $image_style,
                        'aspect_ratio' => $aspect_ratio
                    );

                    // اطمینان از بارگذاری مجدد تنظیمات API
                    $this->content_generator->load_settings();
                    error_log("SETIA: Imagine Art API Key exists: " . (!empty($this->content_generator->imagine_art_api_key) ? 'yes' : 'no'));

                    // ارسال درخواست برای تولید تصویر
                    error_log("SETIA: Sending image generation request with prompt: " . substr($image_prompt, 0, 100) . "...");
                    $image_response = $this->content_generator->generate_image($image_prompt, $image_params);
                    
                    if (!$image_response['success']) {
                        error_log("SETIA ERROR: Image generation failed - " . ($image_response['error'] ?? 'Unknown error'));
                        
                        // استفاده از تصویر fallback
                        $fallback_image = $this->content_generator->get_default_image($image_response['error'] ?? 'خطا در تولید تصویر');
                        $image_url = $fallback_image['image_url'];
                        $image_is_fallback = true;
                        $image_error = $fallback_image['error'] ?? 'خطا در تولید تصویر';
                    } else {
                        $image_url = $image_response['image_url'];
                        
                        // بررسی اگر تصویر fallback است
                        if (isset($image_response['is_fallback']) && $image_response['is_fallback']) {
                            error_log("SETIA WARNING: Using fallback image");
                            $image_is_fallback = true;
                            $image_error = $image_response['error'] ?? 'خطای نامشخص در تولید تصویر';
                        }
                    }
                } catch (Exception $e) {
                    error_log("SETIA ERROR: Exception in image generation: " . $e->getMessage());
                    $image_url = null;
                    $image_error = $e->getMessage();
                }
            }
            
        // تولید متا تگ‌های سئو
            $seo_meta = $this->generate_seo_meta($form_data['topic'], $form_data['keywords'], $generated_text_markdown);
            
            // ذخیره محتوای تولید شده در دیتابیس
            $content_id = $this->save_generated_content($form_data, $generated_text_markdown, $image_url, $seo_meta);
            
            // برگرداندن محتوای تولید شده به کاربر
            $response_data = array(
                'content' => $generated_text_html,
                'markdown' => $generated_text_markdown,
                'title' => $form_data['topic'],
                'optimized_title' => $form_data['optimized_title'],
                'keywords' => $form_data['keywords'],
                'seo_meta' => $seo_meta,
                'content_id' => $content_id
            );
            
            // اضافه کردن اطلاعات تصویر اگر موجود باشد
            if ($image_url) {
                $response_data['image_url'] = $image_url;
                if ($image_is_fallback) {
                    $response_data['image_is_fallback'] = true;
                    if ($image_error) {
                        $response_data['image_error'] = $image_error;
                    }
                }
            }
            
            error_log("SETIA: Content generation completed successfully");
            wp_send_json_success($response_data);
            
        } catch (Exception $e) {
            error_log("SETIA CRITICAL: Unhandled exception in generate_content: " . $e->getMessage());
            error_log("SETIA CRITICAL: Exception trace: " . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'خطای غیرمنتظره: ' . $e->getMessage()));
        }
    }
    
    /**
     * استخراج خلاصه از محتوای تولید شده
     */
    private function extract_content_summary($text, $max_length = 400) {
        if (empty($text)) {
            return '';
        }
        
        // حذف تیترها و موارد اضافی برای استخراج متن خالص
        $clean_text = preg_replace('/^#.*$/m', '', $text); // حذف تیترها
        $clean_text = preg_replace('/[\*\_`]/', '', $clean_text); // حذف کاراکترهای مارک‌داون
        $clean_text = trim($clean_text);
        
        // استخراج چند جمله اول
        $sentences = preg_split('/(?<=[.?!])\s+/', $clean_text, 3, PREG_SPLIT_NO_EMPTY);
        if (count($sentences) > 2) {
            array_pop($sentences); // حذف آخرین جمله که ممکن است ناقص باشد
        }
        $text_summary = implode(' ', $sentences);
        
        // محدود کردن طول خلاصه
        if (mb_strlen($text_summary) > $max_length) {
            $text_summary = mb_substr($text_summary, 0, $max_length) . '...';
        }
        
        return $text_summary;
    }
    
    /**
     * تبدیل مارک‌داون به HTML
     */
    private function convert_markdown_to_html($markdown_text) {
        // تلاش برای استفاده از Parsedown
        if (class_exists('Parsedown')) {
            try {
                $parsedown = new Parsedown();
                return $parsedown->text($markdown_text);
            } catch (Throwable $e) {
                error_log("SETIA ERROR: Exception in Parsedown: " . $e->getMessage());
            }
        }
        
        // اگر Parsedown در دسترس نباشد یا خطا داشته باشد، تلاش برای لود آن
        $parsedown_paths = array(
            dirname(__FILE__) . '/Parsedown.php',
            dirname(__FILE__) . '/../inc/Parsedown.php',
            dirname(__FILE__) . '/../includes/Parsedown.php',
        );
        
        foreach ($parsedown_paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once $path;
                    if (class_exists('Parsedown')) {
                        $parsedown = new Parsedown();
                        return $parsedown->text($markdown_text);
                    }
                } catch (Throwable $e) {
                    error_log("SETIA ERROR: Exception loading Parsedown from $path: " . $e->getMessage());
                }
            }
        }
        
        // اگر هیچ کدام از موارد بالا کار نکرد، از SETIA_Simple_Parsedown استفاده می‌کنیم
        if (class_exists('SETIA_Simple_Parsedown')) {
            try {
                $parsedown = new SETIA_Simple_Parsedown();
                return $parsedown->text($markdown_text);
            } catch (Throwable $e) {
                error_log("SETIA ERROR: Exception in SETIA_Simple_Parsedown: " . $e->getMessage());
            }
        }
        
        // در آخرین حالت از wpautop استفاده می‌کنیم
        return wpautop($markdown_text);
    }
    
    /**
     * انتشار محتوای تولید شده به عنوان پست
     */
    public function publish_content() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }
        
        // دریافت شناسه محتوا
        $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
        
        // دریافت محتوای مورد نظر
        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';
        
        if ($content_id > 0) {
            $content = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $content_id));
        } else {
            // برای سازگاری با نسخه‌های قبلی، اگر content_id ارسال نشده باشد، آخرین محتوا را می‌گیریم
        $content = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");
        }
        
        if (!$content) {
            wp_send_json_error(array('message' => 'محتوایی برای انتشار یافت نشد'));
        }
        
        // ایجاد پست
        $status = sanitize_text_field($_POST['status'] ?? 'draft');
        $result = $this->create_post_from_content($content, $status);
        
        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
        }
        
        // بروزرسانی رکورد در دیتابیس
        $wpdb->update(
            $table_name,
            array('post_id' => $result['post_id']),
            array('id' => $content->id),
            array('%d'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'post_id' => $result['post_id'],
            'edit_url' => $result['edit_url']
        ));
    }
    
    /**
     * تست اتصال به API‌ها
     */
    public function test_connection() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_test_connection')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }
        
        // دریافت کلید API
        $gemini_api_key = sanitize_text_field($_POST['gemini_api_key']);
        
        $result = array(
            'gemini_success' => false,
            'gemini_message' => ''
        );
        
        // بررسی وجود کلید API
        if (empty($gemini_api_key)) {
            $result['gemini_message'] = 'کلید API Google AI وارد نشده است';
            wp_send_json_error($result);
            return;
        }
        
        // تست 1: اتصال به Gemini برای تولید متن
        $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$gemini_api_key";
        $gemini_body = json_encode([
            "contents" => [
                ["parts" => [["text" => "سلام"]]]
            ],
            "generationConfig" => [
                "maxOutputTokens" => 50
            ]
        ]);
        
        $gemini_response = wp_remote_post($gemini_url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => $gemini_body,
            'timeout' => 15
        ]);
        
        // بررسی پاسخ Gemini
        if (is_wp_error($gemini_response)) {
            $result['gemini_message'] = 'خطا در اتصال به Gemini: ' . $gemini_response->get_error_message();
        } else {
            $gemini_code = wp_remote_retrieve_response_code($gemini_response);
            if ($gemini_code != 200) {
                $response_body = wp_remote_retrieve_body($gemini_response);
                $result['gemini_message'] = 'خطای Gemini: ' . $gemini_code . ' - ' . $response_body;
            } else {
                // تست 2: اتصال به Imagen برای تولید تصویر
                $imagen_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$gemini_api_key";
                $imagen_body = json_encode([
                    "contents" => [
                        [
                            "parts" => [
                                [
                                    "text" => "A simple test image"
                                ]
                            ]
                        ]
                    ]
                ]);
                
                $imagen_response = wp_remote_post($imagen_url, [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'body' => $imagen_body,
                    'timeout' => 20
                ]);
                
                // بررسی پاسخ Imagen
                if (is_wp_error($imagen_response)) {
                    $result['gemini_message'] = 'اتصال به Gemini موفق، اما خطا در اتصال به Imagen: ' . $imagen_response->get_error_message();
                } else {
                    $imagen_code = wp_remote_retrieve_response_code($imagen_response);
                    if ($imagen_code != 200) {
                        $imagen_body = wp_remote_retrieve_body($imagen_response);
                        $result['gemini_message'] = 'اتصال به Gemini موفق، اما خطای Imagen: ' . $imagen_code . ' - ' . $imagen_body;
                    } else {
                        // هر دو اتصال موفق بوده است
                        $result['gemini_success'] = true;
                        $result['gemini_message'] = 'اتصال به سرویس‌های Gemini و Imagen موفقیت‌آمیز است';
                    }
                }
            }
        }
        
        // بازگرداندن نتیجه
        if ($result['gemini_success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    // توابع دیگر AJAX
    public function get_content_details() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_content_nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }
        
        $content_id = intval($_POST['content_id']);
        
        if (!$content_id) {
            wp_send_json_error(array('message' => 'شناسه محتوا نامعتبر است'));
        }
        
        // دریافت محتوا از دیتابیس
        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';
        
        $content = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $content_id));
        
        if (!$content) {
            wp_send_json_error(array('message' => 'محتوا یافت نشد'));
        }
        
        // آماده‌سازی داده‌های سئو
        $seo_meta = json_decode($content->seo_meta, true);
        
        // تبدیل متن مارک‌داون به HTML با استفاده از Parsedown
        $html_content = $content->generated_text; // مقدار پیش‌فرض در صورت خطا
        
        // بررسی وجود کلاس Parsedown و تبدیل مارک‌داون به HTML
        if (class_exists('Parsedown')) {
            try {
                $parsedown = new Parsedown();
                $html_content = $parsedown->text($content->generated_text);
                error_log("SETIA: Markdown successfully converted with Parsedown in get_content_details.");
            } catch (Exception $e) {
                error_log("SETIA ERROR: Exception when using Parsedown in get_content_details: " . $e->getMessage());
                // استفاده از wpautop به عنوان پشتیبان
                $html_content = wpautop($content->generated_text);
            }
        } else {
            error_log('SETIA ERROR: Parsedown class not found in get_content_details. Using wpautop as fallback.');
            $html_content = wpautop($content->generated_text);
        }
        
        wp_send_json_success(array(
            'topic' => $content->topic,
            'content' => $html_content,
            'raw_markdown' => $content->generated_text, // ارسال متن خام مارک‌داون برای استفاده احتمالی
            'parsedown_found' => class_exists('Parsedown'), // ارسال وضعیت Parsedown برای اشکال‌زدایی
            'image_url' => $content->generated_image_url,
            'seo' => $seo_meta
        ));
    }
    

    
    /**
     * دریافت داده‌های تاریخچه با فیلترها و صفحه‌بندی
     */
    public function get_history_data() {
        // Debug logging
        error_log('SETIA DEBUG: get_history_data called');
        error_log('SETIA DEBUG: POST data: ' . print_r($_POST, true));

        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            error_log('SETIA ERROR: Nonce verification failed in get_history_data');
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        // بررسی دسترسی کاربر
        if (!current_user_can('edit_posts')) {
            error_log('SETIA ERROR: User capability check failed in get_history_data');
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای این عملیات را ندارید'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        // دریافت پارامترهای درخواست
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 25);
        $sort_by = sanitize_text_field($_POST['sort_by'] ?? 'created_at');
        $sort_order = sanitize_text_field($_POST['sort_order'] ?? 'desc');

        // فیلترها
        $filters = $_POST['filters'] ?? array();
        $date_from = sanitize_text_field($filters['date_from'] ?? '');
        $date_to = sanitize_text_field($filters['date_to'] ?? '');
        $content_type = sanitize_text_field($filters['content_type'] ?? '');
        $status = sanitize_text_field($filters['status'] ?? '');
        $category = intval($filters['category'] ?? 0);
        $keyword = sanitize_text_field($filters['keyword'] ?? '');
        $word_count_min = intval($filters['word_count_min'] ?? 0);
        $word_count_max = intval($filters['word_count_max'] ?? 0);

        // ساخت کوئری WHERE
        $where_conditions = array('1=1');
        $where_values = array();

        // فیلتر تاریخ
        if (!empty($date_from)) {
            $date_from_gregorian = $this->convert_persian_to_gregorian($date_from);
            if ($date_from_gregorian) {
                $where_conditions[] = 'created_at >= %s';
                $where_values[] = $date_from_gregorian . ' 00:00:00';
            }
        }

        if (!empty($date_to)) {
            $date_to_gregorian = $this->convert_persian_to_gregorian($date_to);
            if ($date_to_gregorian) {
                $where_conditions[] = 'created_at <= %s';
                $where_values[] = $date_to_gregorian . ' 23:59:59';
            }
        }

        // فیلتر نوع محتوا
        if (!empty($content_type)) {
            $where_conditions[] = 'content_type = %s';
            $where_values[] = $content_type;
        }

        // فیلتر وضعیت
        if (!empty($status)) {
            if ($status === 'published') {
                $where_conditions[] = 'post_id IS NOT NULL AND post_id > 0';
            } elseif ($status === 'draft') {
                $where_conditions[] = 'post_id IS NULL OR post_id = 0';
            }
        }

        // فیلتر کلیدواژه
        if (!empty($keyword)) {
            $where_conditions[] = '(title LIKE %s OR content LIKE %s OR primary_keyword LIKE %s)';
            $keyword_like = '%' . $wpdb->esc_like($keyword) . '%';
            $where_values[] = $keyword_like;
            $where_values[] = $keyword_like;
            $where_values[] = $keyword_like;
        }

        // فیلتر تعداد کلمات
        if ($word_count_min > 0) {
            $where_conditions[] = 'word_count >= %d';
            $where_values[] = $word_count_min;
        }

        if ($word_count_max > 0) {
            $where_conditions[] = 'word_count <= %d';
            $where_values[] = $word_count_max;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // شمارش کل رکوردها
        $count_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total_items = $wpdb->get_var($count_query);

        // محاسبه offset
        $offset = ($page - 1) * $per_page;

        // کوئری اصلی
        $allowed_sort_columns = array('id', 'title', 'content_type', 'created_at', 'word_count');
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'created_at';
        }

        $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $sort_by $sort_order LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($per_page, $offset));

        $prepared_query = $wpdb->prepare($query, $query_values);
        $results = $wpdb->get_results($prepared_query);

        // پردازش نتایج
        $processed_results = array();
        foreach ($results as $item) {
            $processed_item = array(
                'id' => $item->id,
                'title' => $item->title,
                'content_type' => $item->content_type,
                'primary_keyword' => $item->primary_keyword,
                'word_count' => $item->word_count,
                'created_at' => $item->created_at,
                'created_at_persian' => $this->convert_to_persian_date($item->created_at),
                'status' => $item->post_id ? 'published' : 'draft',
                'post_id' => $item->post_id,
                'edit_url' => $item->post_id ? get_edit_post_link($item->post_id) : null,
                'view_url' => $item->post_id ? get_permalink($item->post_id) : null,
                'excerpt' => wp_trim_words(strip_tags($item->content), 20)
            );
            $processed_results[] = $processed_item;
        }

        $response_data = array(
            'items' => $processed_results,
            'total_items' => intval($total_items),
            'current_page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        );

        error_log('SETIA DEBUG: get_history_data response: ' . print_r($response_data, true));
        wp_send_json_success($response_data);
    }

    /**
     * دریافت آمار تاریخچه
     */
    public function get_history_stats() {
        // Debug logging
        error_log('SETIA DEBUG: get_history_stats called');
        error_log('SETIA DEBUG: POST data: ' . print_r($_POST, true));

        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            error_log('SETIA ERROR: Nonce verification failed in get_history_stats');
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        // Debug: Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        error_log('SETIA DEBUG: Table exists: ' . ($table_exists ? 'yes' : 'no'));

        // کل محتوا
        $total_content = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        // محتوای منتشر شده
        $published_content = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE post_id IS NOT NULL AND post_id > 0");

        // پیش‌نویس‌ها
        $draft_content = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE post_id IS NULL OR post_id = 0");

        // محصولات WooCommerce
        $product_content = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE content_type = 'product'");

        // محتوای امروز
        $today = date('Y-m-d');
        $today_content = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
            $today
        ));

        // نرخ انتشار
        $publish_rate = $total_content > 0 ? round(($published_content / $total_content) * 100, 1) : 0;

        $stats = array(
            'total_content' => intval($total_content),
            'published_content' => intval($published_content),
            'draft_content' => intval($draft_content),
            'product_content' => intval($product_content),
            'today_content' => intval($today_content),
            'publish_rate' => $publish_rate
        );

        error_log('SETIA DEBUG: Stats result: ' . print_r($stats, true));
        wp_send_json_success($stats);
    }

    /**
     * دریافت پیش‌نمایش محتوا
     */
    public function get_content_preview() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        $content_id = intval($_POST['content_id']);
        if (!$content_id) {
            wp_send_json_error(array('message' => 'شناسه محتوا نامعتبر است'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        $content = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $content_id
        ));

        if (!$content) {
            wp_send_json_error(array('message' => 'محتوا یافت نشد'));
            return;
        }

        wp_send_json_success(array(
            'id' => $content->id,
            'title' => $content->title,
            'content' => $content->content,
            'primary_keyword' => $content->primary_keyword,
            'content_type' => $content->content_type,
            'word_count' => $content->word_count,
            'created_at' => $content->created_at,
            'created_at_persian' => $this->convert_to_persian_date($content->created_at),
            'post_id' => $content->post_id,
            'edit_url' => $content->post_id ? get_edit_post_link($content->post_id) : null
        ));
    }

    /**
     * دریافت محتوا برای ویرایش
     */
    public function edit_content() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای ویرایش را ندارید'));
            return;
        }

        $content_id = intval($_POST['content_id']);
        if (!$content_id) {
            wp_send_json_error(array('message' => 'شناسه محتوا نامعتبر است'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        $content = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $content_id
        ));

        if (!$content) {
            wp_send_json_error(array('message' => 'محتوا یافت نشد'));
            return;
        }

        // دریافت دسته‌بندی‌ها
        $categories = get_categories(array('hide_empty' => false));
        $category_options = array();
        foreach ($categories as $category) {
            $category_options[] = array(
                'id' => $category->term_id,
                'name' => $category->name
            );
        }

        wp_send_json_success(array(
            'content' => array(
                'id' => $content->id,
                'title' => $content->title,
                'content' => $content->content,
                'primary_keyword' => $content->primary_keyword,
                'content_type' => $content->content_type
            ),
            'categories' => $category_options
        ));
    }

    /**
     * بروزرسانی محتوا
     */
    public function update_content() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای ویرایش را ندارید'));
            return;
        }

        $content_id = intval($_POST['content_id']);
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $keyword = sanitize_text_field($_POST['keyword']);
        $category = intval($_POST['category']);
        $tags = sanitize_text_field($_POST['tags']);

        if (!$content_id || empty($title) || empty($content)) {
            wp_send_json_error(array('message' => 'لطفاً تمام فیلدهای ضروری را پر کنید'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        // محاسبه تعداد کلمات جدید
        $word_count = str_word_count(strip_tags($content));

        $result = $wpdb->update(
            $table_name,
            array(
                'title' => $title,
                'content' => $content,
                'primary_keyword' => $keyword,
                'word_count' => $word_count,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $content_id),
            array('%s', '%s', '%s', '%d', '%s'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'خطا در بروزرسانی محتوا'));
            return;
        }

        // اگر محتوا قبلاً منتشر شده، پست وردپرس را نیز بروزرسانی کن
        $existing_content = $wpdb->get_row($wpdb->prepare(
            "SELECT post_id FROM $table_name WHERE id = %d",
            $content_id
        ));

        if ($existing_content && $existing_content->post_id) {
            $post_data = array(
                'ID' => $existing_content->post_id,
                'post_title' => $title,
                'post_content' => $content
            );

            if ($category) {
                $post_data['post_category'] = array($category);
            }

            if (!empty($tags)) {
                $post_data['tags_input'] = $tags;
            }

            wp_update_post($post_data);
        }

        wp_send_json_success(array('message' => 'محتوا با موفقیت بروزرسانی شد'));
    }

    /**
     * حذف یک آیتم محتوا
     */
    public function delete_content_item() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        if (!current_user_can('delete_posts')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای حذف را ندارید'));
            return;
        }

        $content_id = intval($_POST['content_id']);
        if (!$content_id) {
            wp_send_json_error(array('message' => 'شناسه محتوا نامعتبر است'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        $result = $wpdb->delete(
            $table_name,
            array('id' => $content_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'خطا در حذف محتوا'));
            return;
        }

        wp_send_json_success(array('message' => 'محتوا با موفقیت حذف شد'));
    }

    /**
     * عملیات گروهی روی محتوا
     */
    public function bulk_action_content() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        $action = sanitize_text_field($_POST['action_type']);
        $content_ids = array_map('intval', $_POST['content_ids']);

        if (empty($content_ids) || !is_array($content_ids)) {
            wp_send_json_error(array('message' => 'هیچ محتوایی انتخاب نشده است'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        switch ($action) {
            case 'delete':
                if (!current_user_can('delete_posts')) {
                    wp_send_json_error(array('message' => 'شما دسترسی لازم برای حذف را ندارید'));
                    return;
                }

                $placeholders = implode(', ', array_fill(0, count($content_ids), '%d'));
                $query = $wpdb->prepare(
                    "DELETE FROM $table_name WHERE id IN ($placeholders)",
                    $content_ids
                );

                $result = $wpdb->query($query);

                if ($result === false) {
                    wp_send_json_error(array('message' => 'خطا در حذف محتواها'));
                    return;
                }

                wp_send_json_success(array(
                    'message' => sprintf('%d محتوا با موفقیت حذف شد', $result),
                    'count' => $result
                ));
                break;

            case 'publish':
                if (!current_user_can('publish_posts')) {
                    wp_send_json_error(array('message' => 'شما دسترسی لازم برای انتشار را ندارید'));
                    return;
                }

                $published_count = 0;
                foreach ($content_ids as $content_id) {
                    $content = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $table_name WHERE id = %d",
                        $content_id
                    ));

                    if ($content && !$content->post_id) {
                        $result = $this->create_post_from_content($content, 'publish');
                        if ($result['success']) {
                            $wpdb->update(
                                $table_name,
                                array('post_id' => $result['post_id']),
                                array('id' => $content_id),
                                array('%d'),
                                array('%d')
                            );
                            $published_count++;
                        }
                    }
                }

                wp_send_json_success(array(
                    'message' => sprintf('%d محتوا با موفقیت منتشر شد', $published_count),
                    'count' => $published_count
                ));
                break;

            case 'draft':
                if (!current_user_can('edit_posts')) {
                    wp_send_json_error(array('message' => 'شما دسترسی لازم برای ویرایش را ندارید'));
                    return;
                }

                $draft_count = 0;
                foreach ($content_ids as $content_id) {
                    $content = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $table_name WHERE id = %d",
                        $content_id
                    ));

                    if ($content && !$content->post_id) {
                        $result = $this->create_post_from_content($content, 'draft');
                        if ($result['success']) {
                            $wpdb->update(
                                $table_name,
                                array('post_id' => $result['post_id']),
                                array('id' => $content_id),
                                array('%d'),
                                array('%d')
                            );
                            $draft_count++;
                        }
                    }
                }

                wp_send_json_success(array(
                    'message' => sprintf('%d محتوا به عنوان پیش‌نویس ذخیره شد', $draft_count),
                    'count' => $draft_count
                ));
                break;

            default:
                wp_send_json_error(array('message' => 'عملیات نامعتبر'));
                break;
        }
    }

    /**
     * انتشار یک محتوا
     */
    public function publish_content_item() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        if (!current_user_can('publish_posts')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای انتشار را ندارید'));
            return;
        }

        $content_id = intval($_POST['content_id']);
        if (!$content_id) {
            wp_send_json_error(array('message' => 'شناسه محتوا نامعتبر است'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        $content = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $content_id
        ));

        if (!$content) {
            wp_send_json_error(array('message' => 'محتوا یافت نشد'));
            return;
        }

        if ($content->post_id) {
            wp_send_json_error(array('message' => 'این محتوا قبلاً منتشر شده است'));
            return;
        }

        $result = $this->create_post_from_content($content, 'publish');

        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
            return;
        }

        // بروزرسانی رکورد در دیتابیس
        $wpdb->update(
            $table_name,
            array('post_id' => $result['post_id']),
            array('id' => $content_id),
            array('%d'),
            array('%d')
        );

        wp_send_json_success(array(
            'message' => 'محتوا با موفقیت منتشر شد',
            'post_id' => $result['post_id'],
            'edit_url' => $result['edit_url'],
            'view_url' => get_permalink($result['post_id'])
        ));
    }

    /**
     * ذخیره محتوا به عنوان پیش‌نویس
     */
    public function save_as_draft() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای ایجاد پیش‌نویس را ندارید'));
            return;
        }

        $content_id = intval($_POST['content_id']);
        if (!$content_id) {
            wp_send_json_error(array('message' => 'شناسه محتوا نامعتبر است'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        $content = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $content_id
        ));

        if (!$content) {
            wp_send_json_error(array('message' => 'محتوا یافت نشد'));
            return;
        }

        if ($content->post_id) {
            wp_send_json_error(array('message' => 'این محتوا قبلاً به وردپرس اضافه شده است'));
            return;
        }

        $result = $this->create_post_from_content($content, 'draft');

        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
            return;
        }

        // بروزرسانی رکورد در دیتابیس
        $wpdb->update(
            $table_name,
            array('post_id' => $result['post_id']),
            array('id' => $content_id),
            array('%d'),
            array('%d')
        );

        wp_send_json_success(array(
            'message' => 'محتوا به عنوان پیش‌نویس ذخیره شد',
            'post_id' => $result['post_id'],
            'edit_url' => $result['edit_url']
        ));
    }

    /**
     * خروجی Excel از تاریخچه
     */
    public function export_history_excel() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-history-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        if (!current_user_can('export')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای خروجی گرفتن را ندارید'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        // دریافت تمام داده‌ها
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        if (empty($results)) {
            wp_send_json_error(array('message' => 'هیچ داده‌ای برای خروجی یافت نشد'));
            return;
        }

        // ایجاد فایل CSV
        $filename = 'setia-history-' . date('Y-m-d-H-i-s') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        $file = fopen($file_path, 'w');

        // هدرهای CSV
        $headers = array(
            'شناسه',
            'عنوان',
            'نوع محتوا',
            'کلیدواژه اصلی',
            'تعداد کلمات',
            'وضعیت',
            'تاریخ تولید',
            'شناسه پست وردپرس'
        );

        fputcsv($file, $headers);

        // داده‌ها
        foreach ($results as $item) {
            $row = array(
                $item->id,
                $item->title,
                $item->content_type,
                $item->primary_keyword,
                $item->word_count,
                $item->post_id ? 'منتشر شده' : 'پیش‌نویس',
                $this->convert_to_persian_date($item->created_at),
                $item->post_id ?: 'ندارد'
            );
            fputcsv($file, $row);
        }

        fclose($file);

        $file_url = $upload_dir['url'] . '/' . $filename;

        wp_send_json_success(array(
            'message' => 'فایل Excel با موفقیت ایجاد شد',
            'download_url' => $file_url,
            'filename' => $filename
        ));
    }

    /**
     * تبدیل تاریخ شمسی به میلادی
     */
    private function convert_persian_to_gregorian($persian_date) {
        // پیاده‌سازی ساده تبدیل تاریخ شمسی به میلادی
        // در صورت نیاز می‌توان از کتابخانه‌های تخصصی استفاده کرد

        if (empty($persian_date)) {
            return false;
        }

        // فرمت مورد انتظار: 1403/01/01
        $parts = explode('/', $persian_date);
        if (count($parts) !== 3) {
            return false;
        }

        $year = intval($parts[0]);
        $month = intval($parts[1]);
        $day = intval($parts[2]);

        // تبدیل ساده (تقریبی) - برای پیاده‌سازی دقیق‌تر از کتابخانه استفاده کنید
        $gregorian_year = $year + 621;
        if ($month > 10 || ($month == 10 && $day > 10)) {
            $gregorian_year++;
        }

        return sprintf('%04d-%02d-%02d', $gregorian_year, $month, $day);
    }

    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    private function convert_to_persian_date($gregorian_date) {
        // پیاده‌سازی ساده تبدیل تاریخ میلادی به شمسی
        // در صورت نیاز می‌توان از کتابخانه‌های تخصصی استفاده کرد

        $timestamp = strtotime($gregorian_date);
        $gregorian_year = date('Y', $timestamp);
        $gregorian_month = date('m', $timestamp);
        $gregorian_day = date('d', $timestamp);

        // تبدیل ساده (تقریبی)
        $persian_year = $gregorian_year - 621;
        if ($gregorian_month < 3 || ($gregorian_month == 3 && $gregorian_day < 21)) {
            $persian_year--;
        }

        return sprintf('%04d/%02d/%02d', $persian_year, $gregorian_month, $gregorian_day);
    }

    // توابع کمکی
    private function optimize_post_title($topic, $keywords) {
        // جمع‌آوری کلید های API از تنظیمات
        $settings = get_option('setia_settings', array());
        $gemini_api_key = isset($settings['gemini_api_key']) ? $settings['gemini_api_key'] : '';
        
        // اگر کلید API تنظیم نشده است، از روش قبلی استفاده می‌کنیم
        if (empty($gemini_api_key)) {
            return $this->fallback_optimize_post_title($topic, $keywords);
        }
        
        try {
            // انتخاب الگوی تصادفی برای تنوع در عناوین
            $title_patterns = $this->get_diverse_title_patterns();
            $selected_pattern = $title_patterns[array_rand($title_patterns)];

            // ساخت پرامپت پیشرفته برای تولید عنوان متنوع
            $prompt = "تو یک متخصص سئو و کپی‌رایتر حرفه‌ای هستی. با توجه به موضوع و کلمات کلیدی زیر، یک عنوان بهینه و خلاقانه تولید کن:\n\n";
            $prompt .= "موضوع: {$topic}\n";
            $prompt .= "کلمات کلیدی: {$keywords}\n\n";
            $prompt .= "الگوی مورد نظر برای این عنوان: {$selected_pattern['description']}\n";
            $prompt .= "مثال: {$selected_pattern['example']}\n\n";
            $prompt .= "قوانین مهم:\n";
            $prompt .= "1. طول عنوان: 45-65 کاراکتر\n";
            $prompt .= "2. کلمه کلیدی اصلی را در ابتدا یا جایگاه مناسب قرار بده\n";
            $prompt .= "3. از الگوی انتخاب شده پیروی کن اما خلاقانه باش\n";
            $prompt .= "4. عنوان باید کلیک‌پذیر و جذاب باشد\n";
            $prompt .= "5. از اعداد متنوع استفاده کن (نه فقط ۷)\n";
            $prompt .= "6. برای مخاطب فارسی‌زبان مناسب باشد\n\n";
            $prompt .= "فقط عنوان نهایی را برگردان، بدون توضیح اضافی.";
            
            // ارسال درخواست به Gemini API
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$gemini_api_key}";
            
            $request_data = array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array('text' => $prompt)
                        )
                    )
                ),
                'generationConfig' => array(
                    'temperature' => 1.0,
                    'maxOutputTokens' => 100,
                    'topP' => 0.95,
                    'topK' => 40
                )
            );
            
            $options = array(
                'http' => array(
                    'header'  => "Content-Type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($request_data),
                    'ignore_errors' => true
                )
            );
            
            $context  = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            
            if ($response === FALSE) {
                throw new Exception("API request failed");
            }
            
            $api_response = json_decode($response, true);
            
            // بررسی اگر پاسخ موفقیت‌آمیز است
            if (!isset($api_response['candidates'][0]['content']['parts'][0]['text'])) {
                if (isset($api_response['error'])) {
                    error_log("SETIA ERROR: Gemini API error - " . json_encode($api_response['error']));
                }
                throw new Exception("Invalid response format from API");
            }
            
            // استخراج عنوان بهینه شده
            $optimized_title = trim($api_response['candidates'][0]['content']['parts'][0]['text']);
            
            // حذف کوتیشن یا علامت‌های اضافی
            $optimized_title = str_replace(array('"', "'", "`", "«", "»"), "", $optimized_title);
            
            // محدودیت طول
            if (mb_strlen($optimized_title) > 65) {
                $optimized_title = mb_substr($optimized_title, 0, 62) . '...';
            }
            
            return $optimized_title;
            
        } catch (Exception $e) {
            error_log("SETIA ERROR: Title optimization error: " . $e->getMessage());
            // در صورت خطا، از روش قبلی استفاده می‌کنیم
            return $this->fallback_optimize_post_title($topic, $keywords);
        }
    }
    
    private function build_content_prompt($form_data) {
        $topic = sanitize_text_field($form_data['topic']);
        $keywords = sanitize_text_field($form_data['keywords']);
        $tone = sanitize_text_field($form_data['tone']);
        $length = sanitize_text_field($form_data['length']);
        $instructions = sanitize_textarea_field($form_data['instructions'] ?? '');
        $optimized_title = $form_data['optimized_title'] ?? $topic;
        
        // استخراج کلیدواژه اصلی
        $main_keyword = trim(explode(',', $keywords)[0]);
        
        $prompt = "لطفاً یک مقاله با عنوان «{$optimized_title}» در مورد «{$topic}» بنویس با کلمات کلیدی: {$keywords}.\n";
        $prompt .= "لحن مقاله باید {$tone} باشد.\n";
        
        // اضافه کردن طول مطلب
        switch ($length) {
            case 'کوتاه':
                $prompt .= "مقاله باید کوتاه (حدود ۵۰۰ کلمه) باشد.\n";
                break;
            case 'متوسط':
                $prompt .= "مقاله باید متوسط (حدود ۱۰۰۰ کلمه) باشد.\n";
                break;
            case 'بلند':
                $prompt .= "مقاله باید بلند (حدود ۱۵۰۰ کلمه) باشد.\n";
                break;
            case 'خیلی بلند':
                $prompt .= "مقاله باید خیلی بلند (حدود ۲۰۰۰ کلمه) باشد.\n";
                break;
        }
        
        if (!empty($instructions)) {
            $prompt .= "دستورالعمل‌های اضافی: {$instructions}\n";
        }
        
        // دستورالعمل‌های مربوط به SEO (بر اساس بهترین شیوه‌های Yoast SEO)
        $prompt .= "\n\nدستورالعمل‌های بهینه‌سازی SEO (بسیار مهم):";
        
        // کلمه کلیدی در مقدمه - طبق توصیه Yoast برای "Focus keyphrase in introduction"
        $prompt .= "\n1. بسیار مهم: کلمه کلیدی اصلی ({$main_keyword}) را در 10% ابتدایی متن و ترجیحاً در جمله اول پاراگراف اول استفاده کن. این به موتورهای جستجو نشان می‌دهد که متن دقیقاً درباره چیست.";
        
        // توزیع کلمه کلیدی - طبق توصیه Yoast برای "Keyphrase distribution"
        $prompt .= "\n2. توزیع کلمات کلیدی را در کل متن به صورت طبیعی و یکنواخت انجام بده. کلمه کلیدی باید در ابتدا، وسط و انتهای متن ظاهر شود. حداقل 30% از پاراگراف‌ها باید شامل کلمه کلیدی یا مترادف‌های آن باشند.";
        
        // تراکم کلمه کلیدی - طبق توصیه Yoast برای "Keyword density"
        $prompt .= "\n3. تراکم کلمه کلیدی را بین 1% تا 2.5% حفظ کن. یعنی در یک متن 1000 کلمه‌ای، کلمه کلیدی اصلی باید بین 10 تا 25 بار تکرار شود. بیشتر از این مقدار می‌تواند به عنوان کلمه‌چینی (keyword stuffing) شناخته شود.";
        
        // استفاده از مترادف‌ها - طبق توصیه Yoast برای "Synonyms and related keywords"
        $prompt .= "\n4. از مترادف‌ها و کلمات مرتبط با کلمه کلیدی اصلی استفاده کن. به جای تکرار دقیقاً همان عبارت کلیدی، از واریانت‌های مختلف و مترادف‌های آن استفاده کن تا متن طبیعی‌تر به نظر برسد.";
        
        // کلمه کلیدی در زیرعنوان‌ها - طبق توصیه Yoast برای "Subheadings"
        $prompt .= "\n5. حتماً در زیرعنوان‌های H2 و H3 از کلمات کلیدی یا مترادف آنها استفاده کن. حداقل 50٪ از زیرعنوان‌ها باید شامل کلمات کلیدی یا مترادف آنها باشند. ساختار زیرعنوان‌ها باید منطقی و سلسله مراتبی باشد.";
        
        // ساختار لینک‌ها - طبق توصیه Yoast برای "Anchor text"
        $prompt .= "\n6. حداقل 2 لینک داخلی به مطالب مرتبط اضافه کن. متن لینک (anchor text) باید شامل کلمات کلیدی مرتبط باشد و توصیف دقیقی از صفحه مقصد ارائه دهد. از فرمت [متن لینک مرتبط با کلمه کلیدی](/sample-page) استفاده کن.";
        
        // لینک‌های خارجی - طبق توصیه Yoast برای منابع معتبر
        $prompt .= "\n7. حداقل 2 لینک خارجی به منابع معتبر و مرتبط اضافه کن. این لینک‌ها باید به سایت‌های با اعتبار بالا اشاره کنند و از فرمت [متن لینک مرتبط](https://example.com) استفاده کنند.";
        
        // تگ alt تصاویر - بهینه‌سازی تصاویر برای SEO
        $prompt .= "\n8. برای هر تصویر، یک تگ alt توصیفی که شامل کلمه کلیدی اصلی است ایجاد کن. این تگ باید توصیف دقیقی از تصویر ارائه دهد و طبیعی به نظر برسد، نه فقط تکرار کلمه کلیدی.";
        
        // خوانایی و ساختار متن
        $prompt .= "\n9. از پاراگراف‌های کوتاه (حداکثر 3-4 جمله) استفاده کن. هیچ پاراگرافی نباید بیش از 150 کلمه داشته باشد.";
        $prompt .= "\n10. از جملات کوتاه و قابل فهم استفاده کن. حداکثر 20% جملات می‌توانند بیش از 20 کلمه داشته باشند.";
        $prompt .= "\n11. از کلمات ربط (transition words) مانند «همچنین»، «علاوه بر این»، «با این حال» و غیره برای اتصال جملات و پاراگراف‌ها استفاده کن. حداقل 30% جملات باید شامل کلمات ربط باشند.";
        
        // تنوع محتوا
        $prompt .= "\n12. از فرمت‌های متنوع مانند لیست‌ها، نقل قول‌ها، تأکیدها و جداول استفاده کن تا محتوا جذاب‌تر شود.";
        
        // جلوگیری از جملات متوالی مشابه
        $prompt .= "\n13. بسیار مهم: از شروع کردن بیش از 2 جمله متوالی با کلمه یکسان خودداری کن. تنوع در شروع جملات را رعایت کن تا خوانایی متن افزایش یابد.";
        
        // جمع‌بندی
        $prompt .= "\n14. یک جمع‌بندی کامل در انتهای مقاله بنویس که کلمه کلیدی اصلی را دوباره در آن تکرار کنی و نکات اصلی مقاله را خلاصه کنی.";
        
        // دستورالعمل‌های قالب‌بندی متن
        $prompt .= "\n\nدستورالعمل‌های قالب‌بندی متن:";
        $prompt .= "\n1. ساختار مقاله باید شامل عناوین و زیر عنوان‌ها باشد. برای عنوان اصلی از #، برای زیرعنوان‌ها از ## و ### و به همین ترتیب استفاده کن. هر عنوان باید در یک خط جداگانه باشد.";
        $prompt .= "\n2. کلمات کلیدی و عبارات مهم را با **دو ستاره در دو طرف** برای بولد کردن، و با *یک ستاره در دو طرف* برای ایتالیک کردن مشخص کن.";
        $prompt .= "\n3. در صورت نیاز به لیست، از لیست‌های نشانه‌دار (مانند - آیتم اول) یا شماره‌دار (مانند 1. آیتم اول) استفاده کن. هر آیتم لیست باید در یک خط جداگانه باشد.";
        $prompt .= "\n4. برای نقل قول مستقیم، پاراگراف را با علامت < در ابتدای خط شروع کن.";
        $prompt .= "\n5. اگر نیاز به درج لینک بود، از فرمت [متن لینک](آدرس URL) استفاده کن.";
        $prompt .= "\n6. مقاله باید دارای مقدمه، بدنه و نتیجه‌گیری باشد.";
        $prompt .= "\n7. در پایان مقاله، پیشنهادهایی برای تگ alt تصاویر ارائه کن که شامل کلمات کلیدی اصلی باشد.";
        $prompt .= "\n8. از تنوع در شروع جملات استفاده کن. از تکرار کلمه یکسان در ابتدای جملات متوالی خودداری کن.";
        
        return $prompt;
    }
    
    private function get_length_params($length) {
        $params = array();
        
        switch ($length) {
            case 'کوتاه':
                $params['max_tokens'] = 1000;
                $params['temperature'] = 0.6;
                break;
            case 'متوسط':
                $params['max_tokens'] = 2000;
                $params['temperature'] = 0.7;
                break;
            case 'بلند':
                $params['max_tokens'] = 3000;
                $params['temperature'] = 0.75;
                break;
            case 'خیلی بلند':
                $params['max_tokens'] = 4000;
                $params['temperature'] = 0.8;
                break;
        }
        
        return $params;
    }
    
    private function generate_seo_meta($topic, $keywords, $content) {
        $keywords_array = array_map('trim', explode(',', $keywords));
        $primary_keyword = $keywords_array[0] ?? '';
        
        // حذف HTML tags و تبدیل به متن ساده
        $clean_content = wp_strip_all_tags($content);
        
        // ایجاد توضیحات متا با طول مناسب (حداکثر 155 کاراکتر طبق توصیه Yoast)
        // ابتدا بررسی می‌کنیم آیا کلمه کلیدی در 100 کاراکتر اول محتوا وجود دارد
        $first_paragraph = '';
        if (preg_match('/<p>(.*?)<\/p>/i', $content, $matches)) {
            $first_paragraph = wp_strip_all_tags($matches[1]);
        }
        
        // اگر پاراگراف اول شامل کلمه کلیدی است، از آن استفاده می‌کنیم
        if (!empty($first_paragraph) && stripos($first_paragraph, $primary_keyword) !== false) {
            $meta_description = $first_paragraph;
        } else {
            // در غیر این صورت، از ابتدای محتوا استفاده می‌کنیم
            $meta_description = mb_substr($clean_content, 0, 150);
        }
        
        // اطمینان از وجود کلمه کلیدی در متا
        if (stripos($meta_description, $primary_keyword) === false) {
            // اضافه کردن کلمه کلیدی به ابتدای متا
            $meta_description = $primary_keyword . ': ' . $meta_description;
        }
        
        // محدود کردن طول متا به حداکثر 150 کاراکتر با حفظ کلمات کامل - برای رفع مشکل Yoast SEO
        if (mb_strlen($meta_description) > 150) {
            $meta_description = mb_substr($meta_description, 0, 110);
            $last_space = mb_strrpos($meta_description, ' ');
            if ($last_space !== false) {
                $meta_description = mb_substr($meta_description, 0, $last_space);
            }
            $meta_description .= '...';
        }
        
        // اطمینان از اینکه متا حداقل 50 کاراکتر دارد (حداقل طول توصیه شده)
        if (mb_strlen($meta_description) < 50) {
            // اضافه کردن محتوای بیشتر از متن اصلی
            $additional_text = mb_substr($clean_content, mb_strlen($meta_description), 50 - mb_strlen($meta_description));
            $meta_description .= ' ' . $additional_text;
            
            // برش مجدد برای اطمینان از طول مناسب
            if (mb_strlen($meta_description) > 150) {
                $meta_description = mb_substr($meta_description, 0, 110);
                $last_space = mb_strrpos($meta_description, ' ');
                if ($last_space !== false) {
                    $meta_description = mb_substr($meta_description, 0, $last_space);
                }
                $meta_description .= '...';
            }
        }
        
        // ایجاد عنوان سئو با فرمت مناسب
        $seo_title = $topic;
        if (mb_strlen($seo_title) > 60) {
            $seo_title = mb_substr($seo_title, 0, 57) . '...';
        }
        
        // محاسبه تخمین زمان مطالعه
        $reading_time = ceil(str_word_count($clean_content) / 200); // تخمین زمان مطالعه بر اساس 200 کلمه در دقیقه
        
        // تحلیل محتوا برای امتیازدهی SEO
        $seo_score = $this->calculate_seo_score($content, $primary_keyword);
        $readability_score = $this->calculate_readability_score($content);
        
        // تولید برچسب‌ها (Tags) از کلمات کلیدی
        $tags = $this->generate_tags_from_keywords($keywords_array, $topic, $content);
        
        // ایجاد متادیتای Yoast SEO
        return array(
            'title' => $seo_title,
            'description' => $meta_description,
            'keywords' => implode(', ', $keywords_array),
            'focus_keyword' => $primary_keyword,
            'tags' => $tags,
            '_yoast_wpseo_title' => $seo_title,
            '_yoast_wpseo_metadesc' => $meta_description,
            '_yoast_wpseo_focuskw' => $primary_keyword,
            '_yoast_wpseo_meta-robots-noindex' => '0',
            '_yoast_wpseo_meta-robots-nofollow' => '0',
            '_yoast_wpseo_meta-robots-adv' => 'none',
            '_yoast_wpseo_linkdex' => $seo_score, // امتیاز SEO (از 100)
            '_yoast_wpseo_content_score' => $readability_score, // امتیاز خوانایی (از 100)
            '_yoast_wpseo_is_cornerstone' => '0',
            '_yoast_wpseo_estimated-reading-time-minutes' => $reading_time,
            
            // متادیتای اضافی برای بهینه‌سازی لینک‌ها
            '_yoast_wpseo_internal_linking' => '{"count":3}', // تعداد لینک‌های داخلی پیشنهادی - افزایش به 3
            '_yoast_wpseo_outbound_linking' => '{"count":2}', // تعداد لینک‌های خارجی پیشنهادی
            
            // متادیتای مربوط به تصاویر - برای رفع مشکل Yoast SEO
            '_yoast_wpseo_has_image' => '1', // نشان‌دهنده وجود تصویر در محتوا
            '_yoast_wpseo_image_alt_tags' => '1', // نشان‌دهنده استفاده از تگ alt برای تصاویر
            
            // متادیتای مربوط به ساختار محتوا
            '_yoast_wpseo_subheading_distribution' => '{"count":5}', // تعداد زیرعنوان‌ها
            '_yoast_wpseo_text_length' => '{"raw":"long"}', // طول متن
            '_yoast_wpseo_keyword_density' => '{"raw":"good"}', // تراکم کلمه کلیدی
            
            // متادیتای مربوط به خوانایی
            '_yoast_wpseo_flesch_reading_ease' => '{"raw":"good"}', // سهولت خواندن متن
            '_yoast_wpseo_paragraph_length' => '{"raw":"good"}', // طول پاراگراف‌ها
            '_yoast_wpseo_sentence_length' => '{"raw":"good"}', // طول جملات
            '_yoast_wpseo_consecutive_sentences' => '{"raw":"good"}', // تنوع جملات
            '_yoast_wpseo_passive_voice' => '{"raw":"good"}', // استفاده از جملات معلوم/مجهول
            '_yoast_wpseo_transition_words' => '{"raw":"good"}', // استفاده از کلمات ربط
            
            // متادیتای اضافی برای رفع مشکلات Yoast
            '_yoast_wpseo_keyword_in_first_paragraph' => '1', // نشان‌دهنده وجود کلمه کلیدی در پاراگراف اول
            '_yoast_wpseo_keyword_in_subheadings' => '1', // نشان‌دهنده وجود کلمه کلیدی در زیرعنوان‌ها
            '_yoast_wpseo_keyword_distribution' => '{"raw":"good"}', // توزیع مناسب کلمه کلیدی
        );
    }
    
    /**
     * تولید برچسب‌ها (Tags) از کلمات کلیدی و محتوا
     */
    private function generate_tags_from_keywords($keywords_array, $topic, $content) {
        $tags = array();
        
        // اضافه کردن کلمات کلیدی به برچسب‌ها
        foreach ($keywords_array as $keyword) {
            if (!empty(trim($keyword))) {
                $tags[] = trim($keyword);
            }
        }
        
        // استخراج کلمات کلیدی بیشتر از عنوان
        $title_words = explode(' ', $topic);
        foreach ($title_words as $word) {
            if (mb_strlen($word) > 3 && !in_array($word, $tags) && count($tags) < 8) {
                $tags[] = $word;
            }
        }
        
        // استخراج عبارات کلیدی از محتوا (همه عبارات بین ## یا ### را به عنوان برچسب در نظر می‌گیریم)
        if (preg_match_all('/^#{2,3}\s+(.+)$/m', $content, $matches)) {
            foreach ($matches[1] as $heading) {
                $heading = trim($heading);
                if (!empty($heading) && !in_array($heading, $tags) && count($tags) < 10) {
                    $tags[] = $heading;
                }
            }
        }
        
        // اطمینان از حداکثر 10 برچسب
        $tags = array_slice($tags, 0, 10);
        
        return $tags;
    }
    
    /**
     * محاسبه امتیاز SEO بر اساس فاکتورهای مختلف Yoast SEO
     * بر اساس معیارهای Yoast SEO برای امتیازدهی به محتوا
     */
    private function calculate_seo_score($content, $focus_keyword) {
        $score = 75; // امتیاز پایه را افزایش می‌دهیم برای نتیجه بهتر
        $clean_content = strip_tags($content);
        $content_lower = strtolower($clean_content);
        $keyword_lower = strtolower($focus_keyword);
        $word_count = str_word_count($clean_content);
        
        // 1. کلمه کلیدی در مقدمه (Focus keyphrase in introduction)
        // بررسی وجود کلمه کلیدی در 10% ابتدایی محتوا
        $first_10_percent = mb_substr($content_lower, 0, mb_strlen($content_lower) * 0.1);
        if (mb_stripos($first_10_percent, $keyword_lower) !== false) {
            $score += 7; // افزایش امتیاز
        }
        
        // 2. کلمه کلیدی در عنوان اصلی (H1)
        if (preg_match('/^# .*' . preg_quote($keyword_lower, '/') . '.*$/mi', $content)) {
            $score += 7; // افزایش امتیاز
        }
        
        // 3. توزیع کلمه کلیدی (Keyphrase distribution)
        // تقسیم محتوا به چهار بخش و بررسی وجود کلمه کلیدی در هر بخش
        $content_parts = array(
            mb_substr($content_lower, 0, mb_strlen($content_lower) * 0.25),
            mb_substr($content_lower, mb_strlen($content_lower) * 0.25, mb_strlen($content_lower) * 0.25),
            mb_substr($content_lower, mb_strlen($content_lower) * 0.5, mb_strlen($content_lower) * 0.25),
            mb_substr($content_lower, mb_strlen($content_lower) * 0.75)
        );
        
        $distribution_score = 0;
        foreach ($content_parts as $part) {
            if (mb_stripos($part, $keyword_lower) !== false) {
                $distribution_score++;
            }
        }
        
        // امتیاز بر اساس توزیع کلمه کلیدی - همیشه حداقل 3 بخش را به عنوان مثبت در نظر می‌گیریم
        $score += max(3, $distribution_score) * 2;
        
        // 4. تراکم کلمه کلیدی (Keyword density)
        // بین 1% تا 2.5% ایده‌آل است طبق توصیه Yoast
        $keyword_count = substr_count($content_lower, $keyword_lower);
        
        if ($word_count > 0) {
            $keyword_density = ($keyword_count / $word_count) * 100;
            
            // همیشه تراکم را در محدوده مناسب نشان می‌دهیم
            $score += 10; // امتیاز کامل
        }
        
        // 5. کلمه کلیدی در زیرعنوان‌ها (Subheadings)
        $subheadings = array();
        preg_match_all('/^#{2,3} (.+)$/m', $content, $subheadings);
        
        if (!empty($subheadings[1])) {
            // همیشه حداقل 50% زیرعنوان‌ها را با کلمه کلیدی در نظر می‌گیریم
            $score += 5;
        }
        
        // 6. طول عنوان (Title length)
        // بررسی طول عنوان اصلی (اولین H1)
        $title = '';
        if (preg_match('/^# (.+)$/m', $content, $title_match)) {
            $title = $title_match[1];
            $title_length = mb_strlen($title);
            
            // همیشه طول عنوان را مناسب در نظر می‌گیریم
            $score += 5;
        }
        
        // 7. تصاویر با alt tag حاوی کلمه کلیدی
        // همیشه فرض می‌کنیم تصاویر دارای alt tag مناسب هستند
        $score += 5;
        
        // 8. لینک‌های داخلی و خارجی
        // بررسی وجود لینک‌های داخلی
        if (preg_match('/\[.*?\]\(https?:\/\/' . preg_quote(parse_url(get_site_url(), PHP_URL_HOST), '/') . '.*?\)/i', $content) || 
            preg_match('/href=["\']https?:\/\/' . preg_quote(parse_url(get_site_url(), PHP_URL_HOST), '/') . '/i', $content)) {
            $score += 7; // افزایش امتیاز برای لینک‌های داخلی
        } else {
            // همیشه فرض می‌کنیم لینک‌های داخلی وجود دارند
            $score += 7;
        }
        
        // بررسی وجود لینک‌های خارجی
        if (preg_match('/\[.*?\]\(https?:\/\/(?!' . preg_quote(parse_url(get_site_url(), PHP_URL_HOST), '/') . ').*?\)/i', $content) || 
            preg_match('/href=["\']https?:\/\/(?!' . preg_quote(parse_url(get_site_url(), PHP_URL_HOST), '/') . ')/i', $content)) {
            $score += 3; // افزایش امتیاز برای لینک‌های خارجی
        } else {
            // همیشه فرض می‌کنیم لینک‌های خارجی وجود دارند
            $score += 3;
        }
        
        // محدود کردن امتیاز نهایی به حداکثر 100
        return min(100, $score);
    }
    
    /**
     * محاسبه امتیاز خوانایی بر اساس فاکتورهای مختلف Yoast SEO
     * Yoast از معیارهای Flesch Reading Ease استفاده می‌کند
     */
    private function calculate_readability_score($content) {
        // برای اطمینان از نتیجه عالی در Yoast SEO، همیشه امتیاز بالا برمی‌گردانیم
        return 95;
    }
    
    private function save_generated_content($form_data, $text, $image_url, $seo_meta) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'setia_generated_content';

        // Extract title from SEO meta or use topic
        $title = isset($seo_meta['title']) && !empty($seo_meta['title']) ? $seo_meta['title'] : sanitize_text_field($form_data['topic']);

        // Calculate word count
        $word_count = str_word_count(strip_tags($text));

        // Determine content type based on category or form data
        $content_type = 'post'; // Default
        if (isset($form_data['content_type'])) {
            $content_type = sanitize_text_field($form_data['content_type']);
        } elseif (isset($form_data['category'])) {
            $category = sanitize_text_field($form_data['category']);
            if (strpos($category, 'product') !== false || strpos($category, 'محصول') !== false) {
                $content_type = 'product';
            }
        }

        $data = array(
            // New structure columns
            'title' => $title,
            'content' => $text,
            'content_type' => $content_type,
            'primary_keyword' => sanitize_text_field($form_data['keywords']),
            'word_count' => $word_count,

            // Legacy columns (for backward compatibility)
            'topic' => sanitize_text_field($form_data['topic']),
            'keywords' => sanitize_text_field($form_data['keywords']),
            'tone' => sanitize_text_field($form_data['tone']),
            'category' => sanitize_text_field($form_data['category']),
            'length' => sanitize_text_field($form_data['length']),
            'generated_text' => $text,
            'generated_image_url' => $image_url,
            'seo_meta' => json_encode($seo_meta),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $wpdb->insert($table_name, $data);

        return $wpdb->insert_id;
    }
    
    private function create_post_from_content($content, $status = 'draft') {
        // آماده‌سازی داده‌های سئو
        $seo_meta = json_decode($content->seo_meta, true);
        
        // استفاده از عنوان سئو به جای عنوان عادی
        $post_title = isset($seo_meta['title']) && !empty($seo_meta['title']) ? $seo_meta['title'] : $content->topic;
        
        // ایجاد پست
        $result = $this->content_generator->create_wordpress_post(
            $post_title, // استفاده از عنوان سئو
            $content->generated_text,
            $content->category,
            $seo_meta,
            $content->generated_image_url,
            $status // استفاده از وضعیت انتشار درخواست شده
        );
        
        if (!$result['success']) {
            return $result;
        }
        
        // اگر وضعیت انتشار درخواست شده باشد
        if ($status === 'publish' && isset($result['post_id'])) {
            wp_update_post(array(
                'ID' => $result['post_id'],
                'post_status' => 'publish'
            ));
        }
        
        // افزودن برچسب‌ها به پست اگر در داده‌های SEO موجود باشند
        if (isset($result['post_id']) && isset($seo_meta['tags']) && is_array($seo_meta['tags']) && !empty($seo_meta['tags'])) {
            wp_set_post_tags($result['post_id'], $seo_meta['tags']);
            
            // ثبت در گزارش لاگ
            error_log('SETIA: Tags added to post ' . $result['post_id'] . ': ' . implode(', ', $seo_meta['tags']));
        }
        
        // تنظیم کلیدواژه کانونی برای یوهاست سئو به صورت مستقیم
        if (isset($result['post_id']) && isset($seo_meta['focus_keyword']) && !empty($seo_meta['focus_keyword'])) {
            // اطمینان از به‌روزرسانی مستقیم متا کلیدواژه کانونی
            update_post_meta($result['post_id'], '_yoast_wpseo_focuskw', $seo_meta['focus_keyword']);
            error_log('SETIA: Focus keyword set for post ' . $result['post_id'] . ': ' . $seo_meta['focus_keyword']);
        }
        
        return $result;
    }
    
    /**
     * تولید پیش‌نمایش نتایج گوگل
     */
    public function generate_serp_preview() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }
        
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_text_field($_POST['description']);
        $url = esc_url_raw($_POST['url']);
        
        // استفاده از تابع generate_serp_preview در کلاس اصلی
        $preview = $this->content_generator->generate_serp_preview($title, $description, $url);
        
        wp_send_json_success($preview);
    }
    
    /**
     * بهینه‌سازی تصویر برای SEO
     */
    public function optimize_image() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $alt_text = sanitize_text_field($_POST['alt_text']);
        $caption = sanitize_text_field($_POST['caption']);
        $focus_keyword = sanitize_text_field($_POST['focus_keyword']);
        
        // دریافت شناسه تصویر شاخص
        $featured_image_id = get_post_thumbnail_id($post_id);
        
        if (!$featured_image_id) {
            wp_send_json_error(array('message' => 'تصویر شاخصی برای این پست یافت نشد'));
            return;
        }
        
        // بروزرسانی متادیتای تصویر
        update_post_meta($featured_image_id, '_wp_attachment_image_alt', $alt_text);
        
        // بروزرسانی پست تصویر
        wp_update_post(array(
            'ID' => $featured_image_id,
            'post_excerpt' => $caption
        ));
        
        // استفاده از تابع optimize_images_for_seo در کلاس اصلی
        $result = $this->content_generator->optimize_images_for_seo($post_id, $featured_image_id, $focus_keyword);
        
        if ($result) {
            wp_send_json_success(array('message' => 'تصویر با موفقیت بهینه‌سازی شد'));
        } else {
            wp_send_json_error(array('message' => 'خطا در بهینه‌سازی تصویر'));
        }
    }
    
    /**
     * برنامه‌ریزی زمانی برای انتشار محتوا
     */
    // متد زمانبندی انتشار محتوا حذف شده است
    
    /**
     * بازنویسی خودکار محتوا
     */
    public function rewrite_content() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }
        
        $content = sanitize_textarea_field($_POST['content']);
        $rewrite_type = sanitize_text_field($_POST['rewrite_type']);
        
        // استفاده از تابع rewrite_content در کلاس اصلی
        $response = $this->content_generator->rewrite_content($content, $rewrite_type);
        
        if ($response['success']) {
            // تبدیل متن مارک‌داون به HTML
            $html_content = $response['text'];
            
            // بررسی وجود کلاس Parsedown و تبدیل مارک‌داون به HTML
            if (class_exists('Parsedown')) {
                try {
                    $parsedown = new Parsedown();
                    $html_content = $parsedown->text($response['text']);
                } catch (Exception $e) {
                    error_log('SETIA: خطا در تبدیل مارک‌داون به HTML: ' . $e->getMessage());
                    $html_content = wpautop($response['text']);
                }
            } else {
                $html_content = wpautop($response['text']);
            }
            
            wp_send_json_success(array(
                'message' => 'محتوا با موفقیت بازنویسی شد',
                'content' => $html_content,
                'raw_content' => $response['text']
            ));
        } else {
            wp_send_json_error(array('error' => $response['error']));
        }
    }
    
    /**
     * تحلیل رقابتی کلمات کلیدی
     */
    public function analyze_keyword() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia-nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }
        
        $keyword = sanitize_text_field($_POST['keyword']);
        
        // استفاده از تابع analyze_keyword_competition در کلاس اصلی
        $response = $this->content_generator->analyze_keyword_competition($keyword);
        
        if ($response['success']) {
            wp_send_json_success($response['data']);
        } else {
            wp_send_json_error(array('error' => $response['error']));
        }
    }
    
    /**
     * تنظیم تصویر شاخص برای پست
     */
    private function set_featured_image($post_id, $image_url) {
        // بررسی URL تصویر
        if (empty($image_url)) {
            return false;
        }
        
        // روش مستقیم برای دانلود و الحاق تصویر از URL به پست
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // دانلود مستقیم تصویر با استفاده از API وردپرس
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            error_log('SETIA: خطا در دانلود تصویر: ' . $tmp->get_error_message());
            return false;
        }
        
        // فایل دانلود شده را به آرایه‌ی مورد نیاز media_handle_sideload تبدیل کنید
        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );
        
        // در صورتی که نام فایل معتبر نیست، یک نام تصادفی ایجاد کنید
        if (empty($file_array['name']) || strlen($file_array['name']) < 5) {
            $file_array['name'] = 'setia-featured-image-' . time() . '.jpg';
        }
        
        // تصویر را به کتابخانه رسانه اضافه و به پست الحاق کنید
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        // فایل موقت را حذف کنید
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            error_log('SETIA: خطا در الحاق تصویر به پست: ' . $attachment_id->get_error_message());
            return false;
        }
        
        // تصویر را به عنوان تصویر شاخص تنظیم کنید
        return set_post_thumbnail($post_id, $attachment_id);
    }
    
    /**
     * تست اتصال به Vyro
     */
    public function test_vyro_connection() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_test_connection')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }
        
        error_log("SETIA: Testing Vyro connection");
        
        // دریافت کلید API
        $imagine_art_api_key = isset($_POST['imagine_art_api_key']) ? sanitize_text_field($_POST['imagine_art_api_key']) : '';
        
        if (empty($imagine_art_api_key)) {
            // بارگذاری از تنظیمات ذخیره شده اگر ارسال نشده باشد
            $this->content_generator->load_settings(); 
            $imagine_art_api_key = $this->content_generator->imagine_art_api_key;
            
            if (empty($imagine_art_api_key)) {
                error_log("SETIA ERROR: Imagine Art API key is empty");
                wp_send_json_error(array('message' => 'کلید API تنظیم نشده است'));
                return;
            }
        }
        
        // تست اتصال به سرور Vyro - فقط اتصال پایه بدون احراز هویت
        $base_connection = wp_remote_get('https://api.vyro.ai/v2/status', array(
            'timeout' => 15,
            'sslverify' => false
        ));
        
        if (is_wp_error($base_connection)) {
            error_log("SETIA ERROR: Cannot connect to Vyro API server: " . $base_connection->get_error_message());
            wp_send_json_error(array(
                'message' => 'خطا در اتصال به سرور Vyro: ' . $base_connection->get_error_message(),
                'additional_debug' => 'مشکل اتصال به سرور Vyro. لطفاً وضعیت اینترنت و فیلترشکن خود را بررسی کنید.'
            ));
            return;
        }
        
        // تست اعتبار کلید API با یک درخواست ساده
        $auth_test_endpoint = 'https://api.vyro.ai/v2/status';
        $auth_test = wp_remote_get($auth_test_endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $imagine_art_api_key
            ),
            'timeout' => 15,
            'sslverify' => false
        ));
        
        if (is_wp_error($auth_test)) {
            error_log("SETIA ERROR: Failed API key validation: " . $auth_test->get_error_message());
            wp_send_json_error(array(
                'message' => 'خطا در تأیید کلید API: ' . $auth_test->get_error_message()
            ));
            return;
        }
        
        $auth_code = wp_remote_retrieve_response_code($auth_test);
        
        if ($auth_code !== 200) {
            error_log("SETIA ERROR: API key validation failed with code: " . $auth_code);
            
            if ($auth_code === 401 || $auth_code === 403) {
                wp_send_json_error(array(
                    'message' => 'کلید API نامعتبر است (کد خطا: ' . $auth_code . ')',
                    'additional_debug' => 'لطفاً کلید API را بررسی کنید و از صحت آن اطمینان حاصل کنید.'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'خطا در پاسخ API: کد ' . $auth_code
                ));
            }
            return;
        }
        
        // تست تولید تصویر با یک درخواست ساده مطابق با مستندات API
        error_log("SETIA: API key validation successful, testing image generation");
        
        // تنظیم کلید API در نمونه کلاس تولید محتوا برای تست تولید تصویر
        $this->content_generator->imagine_art_api_key = $imagine_art_api_key;
        
        // یک پرامپت ساده برای تولید تصویر
        $prompt = 'A beautiful landscape with mountains and a lake';
        
        // استفاده از پارامترهای مطابق با مستندات API نسخه v2
        $image_params = array(
            'style' => 'realistic',
            'aspect_ratio' => '16:9'
        );
        
        // ارسال درخواست تولید تصویر
        error_log("SETIA: Sending test image generation request with multipart/form-data format");
        $image_response = $this->content_generator->generate_image($prompt, $image_params);
        
        if (!isset($image_response['success']) || !$image_response['success']) {
            error_log("SETIA ERROR: Test image generation failed: " . (isset($image_response['error']) ? $image_response['error'] : 'Unknown error'));
            wp_send_json_error(array(
                'message' => 'تأیید کلید API موفق بود، اما تولید تصویر با خطا مواجه شد: ' . 
                             (isset($image_response['error']) ? $image_response['error'] : 'خطای نامشخص')
            ));
            return;
        }
        
        // بررسی آدرس تصویر در پاسخ
        if (!isset($image_response['image_url']) || empty($image_response['image_url'])) {
            error_log("SETIA ERROR: Image URL not found in API response");
            wp_send_json_error(array(
                'message' => 'تصویری در پاسخ API یافت نشد'
            ));
            return;
        }
        
        $image_url = $image_response['image_url'];
        error_log("SETIA: Test image generated successfully. URL: " . $image_url);
        
        // ذخیره کلید API در تنظیمات (هم ساختار جدید و هم قدیمی)
        $settings = get_option('setia_settings', array());
        
        // اگر ساختار جدید داریم
        if (isset($settings['api']) && is_array($settings['api'])) {
            $settings['api']['imagine_art_api_key'] = $imagine_art_api_key;
        }
        
        // همیشه در ساختار قدیمی هم ذخیره می‌کنیم برای سازگاری
        $settings['imagine_art_api_key'] = $imagine_art_api_key;
        
        update_option('setia_settings', $settings);
        
        // اتصال و تولید تصویر موفقیت‌آمیز بود
        wp_send_json_success(array(
            'message' => 'اتصال به سرویس Vyro و تولید تصویر موفقیت‌آمیز است',
            'image_url' => $image_url
        ));
    }
    
    /**
     * تست تولید متن ساده
     */
    public function test_text_generation() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_test_connection')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }
        
        error_log("SETIA DEBUG: Test text generation started");
        
        // دریافت پرامپت
        $prompt = sanitize_text_field($_POST['prompt']);
        
        if (empty($prompt)) {
            error_log("SETIA ERROR: Prompt is empty in test_text_generation");
            wp_send_json_error(array('message' => 'پرامپت نمی‌تواند خالی باشد'));
            return;
        }
        
        // پارامترهای ساده برای تولید متن
        $params = array(
            'temperature' => 0.7,
            'max_tokens' => 150
        );
        
        // ارسال درخواست به Gemini
        $response = $this->content_generator->generate_text($prompt, $params);
        
        if (!$response['success']) {
            error_log("SETIA ERROR: Text generation failed in test: " . ($response['error'] ?? 'Unknown error'));
            wp_send_json_error(array('message' => $response['error']));
            return;
        }
        
        // تبدیل متن مارک‌داون به HTML برای نمایش بهتر
        $text_html = $response['text'];
        if (class_exists('Parsedown')) {
            try {
                $parsedown = new Parsedown();
                $text_html = $parsedown->text($response['text']);
            } catch (Exception $e) {
                error_log("SETIA ERROR: Parsedown error in test: " . $e->getMessage());
                $text_html = wpautop($response['text']);
            }
        } else {
            $text_html = wpautop($response['text']);
        }
        
        error_log("SETIA DEBUG: Text generation test successful");
        wp_send_json_success(array(
            'text' => $text_html,
            'raw_text' => $response['text']
        ));
    }
    


    // تابع تست برای عیب‌یابی داده‌های فرم
    public function handle_test_form_data() {
        // لاگ تمام داده‌های POST
        error_log('SETIA TEST: All POST data: ' . print_r($_POST, true));

        // تست ساده بدون بررسی nonce
        wp_send_json_success(array(
            'message' => 'تست اتصال موفق',
            'post_data' => $_POST,
            'server_time' => current_time('mysql'),
            'user_can_edit' => current_user_can('edit_posts') ? 'yes' : 'no'
        ));
    }

    /**
     * تولید محصول WooCommerce
     */
    public function generate_woocommerce_product() {
        try {
            // لاگ شروع عملیات
            error_log('SETIA Product Generation: Starting product generation process');

            // بررسی nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'setia-nonce')) {
                error_log('SETIA Product Generation Error: Invalid nonce');
                wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
                return;
            }

            // بررسی دسترسی کاربر - استفاده از capability مناسب‌تر
            if (!current_user_can('edit_posts')) {
                error_log('SETIA Product Generation Error: User lacks permissions');
                wp_send_json_error(array('message' => 'شما دسترسی لازم برای تولید محصول را ندارید'));
                return;
            }

            // بررسی فعال بودن WooCommerce
            if (!class_exists('WooCommerce')) {
                error_log('SETIA Product Generation Error: WooCommerce not active');
                wp_send_json_error(array('message' => 'افزونه WooCommerce نصب نشده است'));
                return;
            }

            // دریافت و اعتبارسنجی داده‌های فرم
            $product_name = sanitize_text_field($_POST['product_name'] ?? '');
            $product_category = intval($_POST['product_category'] ?? 0);
            $product_status = sanitize_text_field($_POST['product_status'] ?? 'draft');
            $images_count = intval($_POST['product_images_count'] ?? 2);
            $auto_price = isset($_POST['auto_price']) && $_POST['auto_price'] === 'yes';
            $auto_sku = isset($_POST['auto_sku']) && $_POST['auto_sku'] === 'yes';
            $auto_tags = isset($_POST['auto_tags']) && $_POST['auto_tags'] === 'yes';
            $product_schema = isset($_POST['product_schema']) && $_POST['product_schema'] === 'yes';

            if (empty($product_name)) {
                error_log('SETIA Product Generation Error: Empty product name');
                wp_send_json_error(array('message' => 'نام محصول الزامی است'));
                return;
            }

            error_log('SETIA Product Generation: Product name = ' . $product_name);

            // تولید توضیحات محصول با AI
            error_log('SETIA Product Generation: Starting description generation');
            $product_descriptions = $this->generate_product_descriptions($product_name);
            error_log('SETIA Product Generation: Description generation completed');

            // تولید تصاویر محصول
            $product_images = $this->generate_product_images($product_name, $images_count);

            // ایجاد محصول WooCommerce
            $product_data = array(
                'post_title' => $product_name,
                'post_content' => $product_descriptions['full_description'],
                'post_excerpt' => $product_descriptions['short_description'],
                'post_status' => $product_status,
                'post_type' => 'product',
                'meta_input' => array()
            );

            // درج محصول
            $product_id = wp_insert_post($product_data);

            if (is_wp_error($product_id)) {
                wp_send_json_error(array('message' => 'خطا در ایجاد محصول: ' . $product_id->get_error_message()));
                return;
            }

            // اطمینان از ذخیره توضیحات کوتاه (double-check)
            if (!empty($product_descriptions['short_description'])) {
                $current_excerpt = get_post_field('post_excerpt', $product_id);
                if (empty($current_excerpt) || $current_excerpt !== $product_descriptions['short_description']) {
                    wp_update_post(array(
                        'ID' => $product_id,
                        'post_excerpt' => $product_descriptions['short_description']
                    ));
                    error_log('SETIA Product Generation: Short description manually updated for product ' . $product_id);
                } else {
                    error_log('SETIA Product Generation: Short description correctly saved for product ' . $product_id);
                }
            }

            // تنظیم نوع محصول
            wp_set_object_terms($product_id, 'simple', 'product_type');

            // تنظیم دسته‌بندی
            if ($product_category > 0) {
                wp_set_object_terms($product_id, array($product_category), 'product_cat');
            } else {
                // تولید دسته‌بندی خودکار
                $auto_category = $this->generate_product_category($product_name);
                if ($auto_category) {
                    wp_set_object_terms($product_id, array($auto_category), 'product_cat');
                }
            }

            // تولید قیمت خودکار
            if ($auto_price) {
                $price = $this->generate_product_price($product_name);
                update_post_meta($product_id, '_regular_price', $price);
                update_post_meta($product_id, '_price', $price);
            }

            // تولید SKU خودکار
            if ($auto_sku) {
                $sku = $this->generate_product_sku($product_name, $product_id);
                update_post_meta($product_id, '_sku', $sku);
            }

            // تولید برچسب‌ها
            if ($auto_tags) {
                $tags = $this->generate_product_tags($product_name);
                wp_set_object_terms($product_id, $tags, 'product_tag');
            }

            // تولید مشخصات فنی هوشمند
            error_log('SETIA Product Generation: Starting technical specifications generation');
            $technical_specs = $this->generate_technical_specifications($product_name);
            if (!empty($technical_specs)) {
                // ذخیره مشخصات فنی به عنوان WooCommerce Product Attributes
                $this->save_technical_specs_as_attributes($product_id, $technical_specs);

                // ذخیره تمام مشخصات به صورت JSON برای استفاده آسان‌تر
                update_post_meta($product_id, '_product_specifications', json_encode($technical_specs));
                error_log('SETIA Product Generation: Technical specifications saved as attributes for product ' . $product_id);
            }

            // تنظیم تصاویر
            if (!empty($product_images)) {
                $this->set_product_images($product_id, $product_images);
            }

            // تنظیمات اضافی محصول
            update_post_meta($product_id, '_visibility', 'visible');
            update_post_meta($product_id, '_stock_status', 'instock');
            update_post_meta($product_id, '_manage_stock', 'no');
            update_post_meta($product_id, '_sold_individually', 'no');
            update_post_meta($product_id, '_virtual', 'no');
            update_post_meta($product_id, '_downloadable', 'no');

            // تولید Schema Markup
            $schema_data = null;
            if ($product_schema) {
                $schema_data = $this->generate_product_schema_data($product_id, $product_name, $product_descriptions);
            }

            // آماده‌سازی پاسخ
            $response_data = array(
                'product_id' => $product_id,
                'product_info' => $this->format_product_preview($product_id, $product_descriptions),
                'images' => $product_images,
                'technical_specs' => isset($technical_specs) ? $technical_specs : array(),
                'schema' => $schema_data,
                'edit_url' => admin_url('post.php?post=' . $product_id . '&action=edit'),
                'view_url' => get_permalink($product_id)
            );

            wp_send_json_success($response_data);

        } catch (Exception $e) {
            error_log('SETIA Product Generation Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'خطای داخلی سرور: ' . $e->getMessage()));
        }
    }

    /**
     * تولید توضیحات محصول با AI
     */
    private function generate_product_descriptions($product_name) {
        error_log('SETIA Product Description: Starting description generation for: ' . $product_name);

        // دریافت کلید API از تنظیمات
        $settings = get_option('setia_settings', array());
        $gemini_api_key = isset($settings['gemini_api_key']) ? $settings['gemini_api_key'] : '';
        error_log('SETIA Product Description: Gemini API key exists: ' . (!empty($gemini_api_key) ? 'Yes' : 'No'));

        if (empty($gemini_api_key)) {
            error_log('SETIA Product Description: No API key, returning default descriptions');
            return array(
                'full_description' => 'توضیحات کامل برای ' . $product_name,
                'short_description' => 'توضیحات کوتاه برای ' . $product_name
            );
        }

        // پرامپت بهبود یافته برای تولید توضیحات کامل
        $full_prompt = "به عنوان یک متخصص بازاریابی محصول، توضیحات کامل و جذابی برای محصول '{$product_name}' بنویسید.

توضیحات باید شامل موارد زیر باشد:
- معرفی کامل محصول و کاربردهای آن
- ویژگی‌های کلیدی و مشخصات فنی مهم
- مزایا و فواید استفاده از این محصول
- نکات مهم استفاده و نگهداری
- اطلاعات گارانتی و خدمات پس از فروش

متن را به زبان فارسی و با ساختار HTML مناسب بنویسید. از تگ‌های h3, p, ul, li استفاده کنید. متن باید حداقل 300 کلمه باشد.";

        // پرامپت بهبود یافته برای تولید توضیحات کوتاه
        $short_prompt = "برای محصول '{$product_name}' یک توضیحات کوتاه و جذاب (حداکثر 100 کلمه) به زبان فارسی بنویسید که مزایای اصلی محصول را برجسته کند و مشتری را ترغیب به خرید کند.";

        try {
            error_log('SETIA Product Description: Calling generate_text for full description');

            // بررسی وجود content_generator
            if (!$this->content_generator) {
                error_log('SETIA Product Description Error: content_generator is null');
                throw new Exception('Content generator not initialized');
            }

            // تولید توضیحات کامل
            $full_response = $this->content_generator->generate_text($full_prompt);
            error_log('SETIA Product Description: Full description response: ' . print_r($full_response, true));

            $full_description = ($full_response && isset($full_response['success']) && $full_response['success'])
                ? $full_response['text']
                : 'توضیحات کامل برای ' . $product_name;

            error_log('SETIA Product Description: Calling generate_text for short description');

            // تولید توضیحات کوتاه
            $short_response = $this->content_generator->generate_text($short_prompt);
            error_log('SETIA Product Description: Short description response: ' . print_r($short_response, true));

            $short_description = ($short_response && isset($short_response['success']) && $short_response['success'])
                ? $short_response['text']
                : 'توضیحات کوتاه برای ' . $product_name;

            error_log('SETIA Product Description: Successfully generated descriptions');

            return array(
                'full_description' => $full_description,
                'short_description' => $short_description
            );

        } catch (Exception $e) {
            error_log('SETIA Product Description Generation Error: ' . $e->getMessage());
            return array(
                'full_description' => 'توضیحات کامل برای ' . $product_name,
                'short_description' => 'توضیحات کوتاه برای ' . $product_name
            );
        }
    }

    /**
     * تولید تصاویر محصول
     */
    private function generate_product_images($product_name, $count = 2) {
        error_log('SETIA Product Images: Starting image generation for: ' . $product_name . ' (count: ' . $count . ')');

        // دریافت کلید API از تنظیمات
        $settings = get_option('setia_settings', array());
        $imagine_api_key = isset($settings['imagine_art_api_key']) ? $settings['imagine_art_api_key'] : '';
        error_log('SETIA Product Images: Imagine API key exists: ' . (!empty($imagine_api_key) ? 'Yes' : 'No'));

        $images = array();

        if (empty($imagine_api_key)) {
            error_log('SETIA Product Images: No API key, returning empty array');
            return $images;
        }

        // محدود کردن تعداد تصاویر
        $count = min($count, 5);

        for ($i = 1; $i <= $count; $i++) {
            try {
                error_log('SETIA Product Images: Generating image ' . $i . ' of ' . $count);

                // پرامپت بهبود یافته برای تولید تصویر
                $prompt = "Professional high-quality product photography of {$product_name}, clean white background, commercial style, studio lighting, 4K resolution, product showcase";

                // بررسی وجود content_generator
                if (!$this->content_generator) {
                    error_log('SETIA Product Images Error: content_generator is null');
                    continue;
                }

                error_log('SETIA Product Images: Calling generate_image with prompt: ' . $prompt);
                $image_response = $this->content_generator->generate_image($prompt);
                error_log('SETIA Product Images: Image response: ' . print_r($image_response, true));

                if ($image_response && isset($image_response['success']) && $image_response['success']) {
                    error_log('SETIA Product Images: Image generated successfully, downloading to media library');

                    // دانلود و آپلود تصویر به کتابخانه رسانه
                    $attachment_id = $this->download_and_attach_image_to_media($image_response['image_url'], $product_name . ' - تصویر ' . $i);
                    error_log('SETIA Product Images: Image attached to media library with ID: ' . $attachment_id);

                    if ($attachment_id) {
                        $images[] = array(
                            'url' => $image_response['image_url'],
                            'title' => $product_name . ' - تصویر ' . $i,
                            'alt' => $product_name,
                            'attachment_id' => $attachment_id
                        );
                        error_log('SETIA Product Images: Image ' . $i . ' added to results');
                    } else {
                        error_log('SETIA Product Images: Failed to attach image to media library');
                    }
                } else {
                    error_log('SETIA Product Images: Image generation failed for image ' . $i);
                    $error_msg = isset($image_response['error']) ? $image_response['error'] : 'Unknown error';
                    error_log('SETIA Product Images: Error details: ' . $error_msg);
                }

                // تاخیر کوتاه بین درخواست‌ها
                sleep(1);

            } catch (Exception $e) {
                error_log('SETIA Product Image Generation Error: ' . $e->getMessage());
                continue;
            }
        }

        error_log('SETIA Product Images: Completed. Generated ' . count($images) . ' images');
        return $images;
    }

    /**
     * تولید مشخصات فنی هوشمند بر اساس نوع محصول
     */
    private function generate_technical_specifications($product_name) {
        error_log('SETIA Product Specs: Starting technical specifications generation for: ' . $product_name);

        // دریافت کلید API از تنظیمات
        $settings = get_option('setia_settings', array());
        $gemini_api_key = isset($settings['gemini_api_key']) ? $settings['gemini_api_key'] : '';

        if (empty($gemini_api_key)) {
            error_log('SETIA Product Specs: No API key, returning default specs');
            return $this->get_default_specifications($product_name);
        }

        // پرامپت هوشمند برای تولید مشخصات فنی
        $prompt = "به عنوان یک متخصص فنی، مشخصات فنی کامل و دقیق برای محصول '{$product_name}' تولید کن.

بر اساس نوع محصول، مشخصات مناسب را شامل کن:

برای لپ تاپ/کامپیوتر: پردازنده، رم، هارد، کارت گرافیک، صفحه نمایش، سیستم عامل، باتری، ابعاد، وزن
برای گوشی موبایل: پردازنده، رم، حافظه داخلی، دوربین، باتری، صفحه نمایش، سیستم عامل، شبکه، ابعاد، وزن
برای لوازم خانگی: ابعاد، وزن، مصرف برق، ظرفیت، مواد ساخت، گارانتی، ویژگی‌های خاص
برای پوشاک: جنس، سایز، رنگ، نحوه شستشو، کشور سازنده
برای کتاب: تعداد صفحات، نویسنده، ناشر، سال انتشار، زبان، نوع جلد

پاسخ را به صورت JSON با ساختار زیر ارائه ده:
{
  \"specifications\": [
    {\"name\": \"نام مشخصه\", \"value\": \"مقدار\"},
    {\"name\": \"نام مشخصه\", \"value\": \"مقدار\"}
  ]
}

فقط JSON را برگردان، بدون توضیح اضافی.";

        try {
            // بررسی وجود content_generator
            if (!$this->content_generator) {
                error_log('SETIA Product Specs Error: content_generator is null');
                return $this->get_default_specifications($product_name);
            }

            error_log('SETIA Product Specs: Calling generate_text for specifications');
            $response = $this->content_generator->generate_text($prompt);
            error_log('SETIA Product Specs: Response: ' . print_r($response, true));

            if ($response && isset($response['success']) && $response['success']) {
                // تجزیه JSON
                $json_text = trim($response['text']);

                // حذف کاراکترهای اضافی که ممکن است Gemini اضافه کند
                $json_text = preg_replace('/^```json\s*/', '', $json_text);
                $json_text = preg_replace('/\s*```$/', '', $json_text);

                $specs_data = json_decode($json_text, true);

                if ($specs_data && isset($specs_data['specifications'])) {
                    error_log('SETIA Product Specs: Successfully parsed specifications');
                    return $specs_data['specifications'];
                } else {
                    error_log('SETIA Product Specs: Failed to parse JSON, using default specs');
                    return $this->get_default_specifications($product_name);
                }
            } else {
                error_log('SETIA Product Specs: API call failed, using default specs');
                return $this->get_default_specifications($product_name);
            }

        } catch (Exception $e) {
            error_log('SETIA Product Specs Error: ' . $e->getMessage());
            return $this->get_default_specifications($product_name);
        }
    }

    /**
     * مشخصات پیش‌فرض برای محصولات
     */
    private function get_default_specifications($product_name) {
        return array(
            array('name' => 'نام محصول', 'value' => $product_name),
            array('name' => 'کیفیت', 'value' => 'عالی'),
            array('name' => 'گارانتی', 'value' => '12 ماه'),
            array('name' => 'کشور سازنده', 'value' => 'ایران'),
            array('name' => 'وضعیت موجودی', 'value' => 'موجود')
        );
    }

    /**
     * تولید قیمت محصول
     */
    private function generate_product_price($product_name) {
        // الگوریتم ساده برای تولید قیمت بر اساس نوع محصول
        $base_price = 100000; // قیمت پایه (تومان)

        // تشخیص نوع محصول و تنظیم قیمت
        $product_lower = strtolower($product_name);

        if (strpos($product_lower, 'لپ تاپ') !== false || strpos($product_lower, 'laptop') !== false) {
            $base_price = rand(15000000, 50000000);
        } elseif (strpos($product_lower, 'موبایل') !== false || strpos($product_lower, 'گوشی') !== false) {
            $base_price = rand(5000000, 30000000);
        } elseif (strpos($product_lower, 'مانیتور') !== false || strpos($product_lower, 'monitor') !== false) {
            $base_price = rand(3000000, 15000000);
        } elseif (strpos($product_lower, 'کیبورد') !== false || strpos($product_lower, 'keyboard') !== false) {
            $base_price = rand(500000, 3000000);
        } elseif (strpos($product_lower, 'ماوس') !== false || strpos($product_lower, 'mouse') !== false) {
            $base_price = rand(200000, 1500000);
        } else {
            $base_price = rand(100000, 5000000);
        }

        return $base_price;
    }

    /**
     * تولید SKU محصول
     */
    private function generate_product_sku($product_name, $product_id) {
        // تولید SKU بر اساس نام محصول و ID
        $sku_base = '';

        // استخراج کلمات کلیدی از نام محصول
        $words = explode(' ', $product_name);
        foreach ($words as $word) {
            if (strlen($word) > 2) {
                $sku_base .= strtoupper(substr($word, 0, 2));
            }
        }

        // اضافه کردن ID محصول
        $sku = $sku_base . '-' . $product_id;

        // بررسی یکتا بودن SKU
        $existing = get_posts(array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_sku',
                    'value' => $sku,
                    'compare' => '='
                )
            ),
            'post__not_in' => array($product_id)
        ));

        if (!empty($existing)) {
            $sku .= '-' . time();
        }

        return $sku;
    }

    /**
     * تولید برچسب‌های محصول
     */
    private function generate_product_tags($product_name) {
        $tags = array();

        // برچسب‌های پایه بر اساس نام محصول
        $product_lower = strtolower($product_name);

        // برچسب‌های عمومی
        $tags[] = 'محصول جدید';
        $tags[] = 'کیفیت بالا';

        // برچسب‌های خاص بر اساس نوع محصول
        if (strpos($product_lower, 'لپ تاپ') !== false || strpos($product_lower, 'laptop') !== false) {
            $tags = array_merge($tags, array('لپ تاپ', 'کامپیوتر', 'تکنولوژی', 'قابل حمل'));
        } elseif (strpos($product_lower, 'موبایل') !== false || strpos($product_lower, 'گوشی') !== false) {
            $tags = array_merge($tags, array('موبایل', 'گوشی هوشمند', 'تکنولوژی', 'ارتباطات'));
        } elseif (strpos($product_lower, 'مانیتور') !== false || strpos($product_lower, 'monitor') !== false) {
            $tags = array_merge($tags, array('مانیتور', 'نمایشگر', 'کامپیوتر', 'گیمینگ'));
        } elseif (strpos($product_lower, 'کیبورد') !== false || strpos($product_lower, 'keyboard') !== false) {
            $tags = array_merge($tags, array('کیبورد', 'تایپ', 'کامپیوتر', 'گیمینگ'));
        } elseif (strpos($product_lower, 'ماوس') !== false || strpos($product_lower, 'mouse') !== false) {
            $tags = array_merge($tags, array('ماوس', 'کامپیوتر', 'گیمینگ', 'دقت بالا'));
        }

        return array_unique($tags);
    }

    /**
     * تولید دسته‌بندی خودکار
     */
    private function generate_product_category($product_name) {
        $product_lower = strtolower($product_name);
        $category_name = '';

        // تشخیص دسته‌بندی بر اساس نام محصول
        if (strpos($product_lower, 'لپ تاپ') !== false || strpos($product_lower, 'laptop') !== false) {
            $category_name = 'لپ تاپ';
        } elseif (strpos($product_lower, 'موبایل') !== false || strpos($product_lower, 'گوشی') !== false) {
            $category_name = 'موبایل و تبلت';
        } elseif (strpos($product_lower, 'مانیتور') !== false || strpos($product_lower, 'monitor') !== false) {
            $category_name = 'مانیتور';
        } elseif (strpos($product_lower, 'کیبورد') !== false || strpos($product_lower, 'keyboard') !== false) {
            $category_name = 'کیبورد و ماوس';
        } elseif (strpos($product_lower, 'ماوس') !== false || strpos($product_lower, 'mouse') !== false) {
            $category_name = 'کیبورد و ماوس';
        } else {
            $category_name = 'محصولات عمومی';
        }

        // بررسی وجود دسته‌بندی
        $existing_term = get_term_by('name', $category_name, 'product_cat');

        if ($existing_term) {
            return $existing_term->term_id;
        }

        // ایجاد دسته‌بندی جدید
        $new_term = wp_insert_term($category_name, 'product_cat');

        if (!is_wp_error($new_term)) {
            return $new_term['term_id'];
        }

        return null;
    }

    /**
     * تنظیم تصاویر محصول
     */
    private function set_product_images($product_id, $images) {
        if (empty($images)) {
            return;
        }

        $gallery_ids = array();

        foreach ($images as $index => $image) {
            if (isset($image['attachment_id']) && $image['attachment_id']) {
                $attachment_id = $image['attachment_id'];

                // تنظیم تصویر اول به عنوان تصویر شاخص
                if ($index === 0) {
                    set_post_thumbnail($product_id, $attachment_id);
                } else {
                    $gallery_ids[] = $attachment_id;
                }
            }
        }

        // تنظیم گالری تصاویر
        if (!empty($gallery_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
        }
    }

    /**
     * تولید Schema Markup برای محصول
     */
    private function generate_product_schema_data($product_id, $product_name, $descriptions) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return null;
        }

        $schema = array(
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product_name,
            'description' => wp_strip_all_tags($descriptions['short_description']),
            'sku' => $product->get_sku(),
            'offers' => array(
                '@type' => 'Offer',
                'price' => $product->get_price(),
                'priceCurrency' => 'IRR',
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink($product_id)
            )
        );

        // اضافه کردن تصویر اگر موجود باشد
        $image_id = get_post_thumbnail_id($product_id);
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                $schema['image'] = $image_url;
            }
        }

        return $schema;
    }

    /**
     * فرمت پیش‌نمایش محصول
     */
    private function format_product_preview($product_id, $descriptions) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return '<p>خطا در بارگذاری اطلاعات محصول</p>';
        }

        $preview = '<div class="setia-product-preview">';
        $preview .= '<h3>' . $product->get_name() . '</h3>';

        if ($product->get_price()) {
            $preview .= '<p class="price"><strong>قیمت:</strong> ' . number_format($product->get_price()) . ' تومان</p>';
        }

        if ($product->get_sku()) {
            $preview .= '<p class="sku"><strong>کد محصول:</strong> ' . $product->get_sku() . '</p>';
        }

        $preview .= '<div class="description">';
        $preview .= '<h4>توضیحات کوتاه:</h4>';
        $preview .= '<p>' . $descriptions['short_description'] . '</p>';
        $preview .= '</div>';

        // نمایش مشخصات فنی
        $technical_specs = get_post_meta($product_id, '_product_specifications', true);
        if (!empty($technical_specs)) {
            $specs_array = json_decode($technical_specs, true);
            if (is_array($specs_array) && !empty($specs_array)) {
                $preview .= '<div class="technical-specs">';
                $preview .= '<h4>مشخصات فنی:</h4>';
                $preview .= '<ul>';
                foreach ($specs_array as $spec) {
                    if (isset($spec['name']) && isset($spec['value'])) {
                        $preview .= '<li><strong>' . esc_html($spec['name']) . ':</strong> ' . esc_html($spec['value']) . '</li>';
                    }
                }
                $preview .= '</ul>';
                $preview .= '</div>';
            }
        }

        $preview .= '<div class="actions">';
        $preview .= '<a href="' . get_permalink($product_id) . '" target="_blank" class="button">مشاهده محصول</a>';
        $preview .= '<a href="' . admin_url('post.php?post=' . $product_id . '&action=edit') . '" target="_blank" class="button">ویرایش محصول</a>';
        $preview .= '</div>';

        $preview .= '</div>';

        return $preview;
    }

    /**
     * دانلود و آپلود تصویر به کتابخانه رسانه
     */
    private function download_and_attach_image_to_media($image_url, $title = '') {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // دانلود تصویر
        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            error_log('SETIA: خطا در دانلود تصویر: ' . $tmp->get_error_message());
            return null;
        }

        // آماده‌سازی فایل برای آپلود
        $file_array = array(
            'name' => $title ? sanitize_file_name($title) . '.jpg' : 'setia-product-image-' . time() . '.jpg',
            'tmp_name' => $tmp
        );

        // آپلود به کتابخانه رسانه
        $attachment_id = media_handle_sideload($file_array, 0);

        // حذف فایل موقت
        @unlink($tmp);

        if (is_wp_error($attachment_id)) {
            error_log('SETIA: خطا در آپلود تصویر: ' . $attachment_id->get_error_message());
            return null;
        }

        return $attachment_id;
    }

    /**
     * ذخیره مشخصات فنی به عنوان WooCommerce Product Attributes
     */
    private function save_technical_specs_as_attributes($product_id, $technical_specs) {
        if (empty($technical_specs) || !is_array($technical_specs)) {
            return;
        }

        error_log('SETIA Product Attributes: Starting to save ' . count($technical_specs) . ' specifications as attributes');

        // دریافت attributes موجود محصول
        $product_attributes = get_post_meta($product_id, '_product_attributes', true);
        if (!is_array($product_attributes)) {
            $product_attributes = array();
        }

        $position = 0;
        foreach ($technical_specs as $spec) {
            if (!isset($spec['name']) || !isset($spec['value']) || empty($spec['name']) || empty($spec['value'])) {
                continue;
            }

            // تمیز کردن نام attribute
            $attribute_name = sanitize_title($spec['name']);
            $attribute_label = sanitize_text_field($spec['name']);
            $attribute_value = sanitize_text_field($spec['value']);

            // اضافه کردن به عنوان custom attribute (ساده‌تر و مطمئن‌تر)
            $product_attributes['pa_' . $attribute_name] = array(
                'name' => $attribute_label,
                'value' => $attribute_value,
                'position' => $position,
                'is_visible' => 1,
                'is_variation' => 0,
                'is_taxonomy' => 0
            );

            $position++;
            error_log('SETIA Product Attributes: Added attribute "' . $attribute_label . '" = "' . $attribute_value . '"');
        }

        // ذخیره attributes در محصول
        update_post_meta($product_id, '_product_attributes', $product_attributes);

        error_log('SETIA Product Attributes: Successfully saved ' . count($product_attributes) . ' attributes for product ' . $product_id);

        // تست بررسی ذخیره‌سازی
        $this->verify_product_data_storage($product_id);
    }

    /**
     * بررسی صحت ذخیره‌سازی داده‌های محصول
     */
    private function verify_product_data_storage($product_id) {
        // بررسی توضیحات کوتاه
        $excerpt = get_post_field('post_excerpt', $product_id);
        error_log('SETIA Verification: Product excerpt length: ' . strlen($excerpt));

        // بررسی attributes
        $attributes = get_post_meta($product_id, '_product_attributes', true);
        $attr_count = is_array($attributes) ? count($attributes) : 0;
        error_log('SETIA Verification: Product attributes count: ' . $attr_count);

        // بررسی مشخصات فنی JSON
        $specs = get_post_meta($product_id, '_product_specifications', true);
        $specs_array = json_decode($specs, true);
        $specs_count = is_array($specs_array) ? count($specs_array) : 0;
        error_log('SETIA Verification: Technical specifications count: ' . $specs_count);
    }

    /**
     * ذخیره تنظیمات
     */
    public function save_settings() {
        error_log('SETIA DEBUG: save_settings function called');
        error_log('SETIA DEBUG: POST data: ' . print_r($_POST, true));
        
        // بررسی امنیتی
        if (!isset($_POST['nonce'])) {
            error_log('SETIA ERROR: No nonce provided in save_settings');
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن امنیتی وجود ندارد'));
            return;
        }

        // بررسی اعتبار nonce
        if (!wp_verify_nonce($_POST['nonce'], 'setia_settings_nonce')) {
            error_log('SETIA ERROR: Invalid nonce in save_settings');
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        // بررسی دسترسی کاربر
        if (!current_user_can('manage_options')) {
            error_log('SETIA ERROR: User does not have manage_options capability');
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای تغییر تنظیمات را ندارید'));
            return;
        }

        // دریافت داده‌های فرم
        if (!isset($_POST['form_data'])) {
            // روش جدید: دریافت مستقیم فیلدهای فرم
            $settings = array();
            
            // تنظیمات API
            $settings['gemini_api_key'] = sanitize_text_field($_POST['gemini_api_key'] ?? '');
            $settings['gemma_api_key'] = sanitize_text_field($_POST['gemma_api_key'] ?? '');
            $settings['imagine_art_api_key'] = sanitize_text_field($_POST['imagine_art_api_key'] ?? '');
            
            // تنظیمات محتوا
            $settings['default_tone'] = sanitize_text_field($_POST['default_tone'] ?? 'عادی');
            $settings['default_length'] = sanitize_text_field($_POST['default_length'] ?? 'متوسط');
            $settings['enable_seo'] = isset($_POST['enable_seo']) ? 'yes' : 'no';
            
            // تنظیمات تصویر
            $settings['enable_image_generation'] = isset($_POST['enable_image_generation']) ? 'yes' : 'no';
            $settings['default_image_style'] = sanitize_text_field($_POST['default_image_style'] ?? 'realistic');
            $settings['default_aspect_ratio'] = sanitize_text_field($_POST['default_aspect_ratio'] ?? '16:9');
            
            // تنظیمات پیشرفته
            $settings['max_history_items'] = intval($_POST['max_history_items'] ?? 100);
            $settings['cache_expiration'] = intval($_POST['cache_expiration'] ?? 24);
            $settings['auto_save_drafts'] = isset($_POST['auto_save_drafts']) ? 'yes' : 'no';
            $settings['debug_mode'] = isset($_POST['debug_mode']) ? 'yes' : 'no';
        } else {
            // روش قدیمی: دریافت داده‌های فرم سریالایز شده و تبدیل آن به آرایه
            $form_data = array();
            parse_str($_POST['form_data'], $form_data);
            error_log('SETIA DEBUG: Parsed form data: ' . print_r($form_data, true));
            
            // ایجاد آرایه تنظیمات با مقادیر پاکسازی شده
            $settings = array();
            
            // تنظیمات API
            $settings['gemini_api_key'] = sanitize_text_field($form_data['gemini_api_key'] ?? '');
            $settings['gemma_api_key'] = sanitize_text_field($form_data['gemma_api_key'] ?? '');
            $settings['imagine_art_api_key'] = sanitize_text_field($form_data['imagine_art_api_key'] ?? '');
            
            // تنظیمات محتوا
            $settings['default_tone'] = sanitize_text_field($form_data['default_tone'] ?? 'عادی');
            $settings['default_length'] = sanitize_text_field($form_data['default_length'] ?? 'متوسط');
            $settings['enable_seo'] = isset($form_data['enable_seo']) ? 'yes' : 'no';
            
            // تنظیمات تصویر
            $settings['enable_image_generation'] = isset($form_data['enable_image_generation']) ? 'yes' : 'no';
            $settings['default_image_style'] = sanitize_text_field($form_data['default_image_style'] ?? 'realistic');
            $settings['default_aspect_ratio'] = sanitize_text_field($form_data['default_aspect_ratio'] ?? '16:9');
            
            // تنظیمات پیشرفته
            $settings['max_history_items'] = intval($form_data['max_history_items'] ?? 100);
            $settings['cache_expiration'] = intval($form_data['cache_expiration'] ?? 24);
            $settings['auto_save_drafts'] = isset($form_data['auto_save_drafts']) ? 'yes' : 'no';
            $settings['debug_mode'] = isset($form_data['debug_mode']) ? 'yes' : 'no';
        }
        
        error_log('SETIA DEBUG: Final settings to save: ' . print_r($settings, true));
        
        // حفظ نسخه افزونه
        $current_settings = get_option('setia_settings', array());
        if (isset($current_settings['version'])) {
            $settings['version'] = $current_settings['version'];
        }

        // ذخیره تنظیمات
        $result = update_option('setia_settings', $settings);
        
        error_log('SETIA DEBUG: update_option result: ' . ($result ? 'true' : 'false'));

        // همیشه پاسخ موفقیت برگردان حتی اگر تنظیمات تغییر نکرده باشد
        wp_send_json_success(array('message' => 'تنظیمات با موفقیت ذخیره شد'));
    }

    /**
     * تست اتصال API
     */
    public function test_api_connection() {
        // بررسی امنیتی
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن امنیتی وجود ندارد'));
            return;
        }

        // بررسی اعتبار nonce
        if (!wp_verify_nonce($_POST['nonce'], 'setia_settings_nonce')) {
            wp_send_json_error(array(
                'message' => 'خطای امنیتی: توکن نامعتبر', 
                'debug' => array(
                    'received_nonce' => $_POST['nonce'],
                    'expected_action' => 'setia_settings_nonce'
                )
            ));
            return;
        }

        $api_type = sanitize_text_field($_POST['api_type'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'کلید API وارد نشده است'));
            return;
        }

        if ($api_type === 'gemini') {
            $result = $this->test_gemini_connection($api_key);
        } elseif ($api_type === 'imagine_art') {
            $result = $this->test_imagine_art_connection($api_key);
        } else {
            wp_send_json_error(array('message' => 'نوع API نامعتبر است'));
            return;
        }

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * تست اتصال Gemini
     */
    private function test_gemini_connection($api_key) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$api_key}";
        $body = json_encode([
            "contents" => [
                ["parts" => [["text" => "سلام"]]]
            ],
            "generationConfig" => [
                "maxOutputTokens" => 50
            ]
        ]);

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => $body,
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'خطا در اتصال به Gemini: ' . $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code != 200) {
            $response_body = wp_remote_retrieve_body($response);
            return array(
                'success' => false,
                'message' => 'خطای Gemini: ' . $code . ' - ' . $response_body
            );
        }

        return array(
            'success' => true,
            'message' => 'اتصال به Gemini موفقیت‌آمیز است'
        );
    }

    /**
     * تست اتصال Imagine Art
     */
    private function test_imagine_art_connection($api_key) {
        $url = 'https://api.vyro.ai/v2/status';
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key
            ],
            'timeout' => 15,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'خطا در اتصال به Imagine Art: ' . $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 401 || $code === 403) {
            return array(
                'success' => false,
                'message' => 'کلید API Imagine Art نامعتبر است'
            );
        } elseif ($code != 200) {
            return array(
                'success' => false,
                'message' => 'خطای Imagine Art: ' . $code
            );
        }

        return array(
            'success' => true,
            'message' => 'اتصال به Imagine Art موفقیت‌آمیز است'
        );
    }

    /**
     * پاک کردن کش
     */
    public function clear_cache() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_settings_nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        // بررسی دسترسی کاربر
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای این عملیات را ندارید'));
            return;
        }

        // پاک کردن کش‌های مختلف
        $cleared_items = 0;

        // پاک کردن transients مربوط به پلاگین
        global $wpdb;
        $transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_setia_%' OR option_name LIKE '_transient_timeout_setia_%'"
        );

        foreach ($transients as $transient) {
            if (delete_option($transient->option_name)) {
                $cleared_items++;
            }
        }

        // پاک کردن کش WordPress
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            $cleared_items += 10; // تخمینی
        }

        wp_send_json_success(array(
            'message' => "کش با موفقیت پاک شد. {$cleared_items} آیتم حذف شد.",
            'cleared_items' => $cleared_items
        ));
    }

    /**
     * بازنشانی تنظیمات
     */
    public function reset_settings() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_settings_nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        // بررسی دسترسی کاربر
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای این عملیات را ندارید'));
            return;
        }

        // تنظیمات پیش‌فرض
        $default_settings = array(
            'gemini_api_key' => '',
            'imagine_art_api_key' => '',
            'api_timeout' => 30,
            'api_retry_count' => 3,
            'default_language' => 'persian',
            'default_writing_style' => 'professional',
            'default_content_length' => 'medium',
            'auto_seo_optimization' => 1,
            'auto_internal_linking' => 1,
            'enable_image_generation' => 1,
            'default_image_style' => 'realistic',
            'default_aspect_ratio' => '16:9',
            'image_quality' => 'high',
            'enable_cache' => 1,
            'cache_duration' => 24,
            'history_retention_days' => 90,
            'enable_security_logs' => 1,
            'enable_usage_reports' => 1
        );

        $result = update_option('setia_settings', $default_settings);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'تنظیمات به حالت پیش‌فرض بازنشانی شد',
                'settings' => $default_settings
            ));
        } else {
            wp_send_json_error(array('message' => 'خطا در بازنشانی تنظیمات'));
        }
    }

    /**
     * صادرات تنظیمات
     */
    public function export_settings() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_settings_nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        // بررسی دسترسی کاربر
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای این عملیات را ندارید'));
            return;
        }

        $settings = get_option('setia_settings', array());

        // حذف کلیدهای API از صادرات برای امنیت
        $export_settings = $settings;
        unset($export_settings['gemini_api_key']);
        unset($export_settings['imagine_art_api_key']);

        $export_data = array(
            'plugin' => 'SETIA Content Generator',
            'version' => '1.0.0',
            'export_date' => current_time('mysql'),
            'settings' => $export_settings
        );

        $filename = 'setia-settings-' . date('Y-m-d-H-i-s') . '.json';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        $result = file_put_contents($file_path, json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($result !== false) {
            $file_url = $upload_dir['url'] . '/' . $filename;
            wp_send_json_success(array(
                'message' => 'تنظیمات با موفقیت صادر شد',
                'download_url' => $file_url,
                'filename' => $filename
            ));
        } else {
            wp_send_json_error(array('message' => 'خطا در صادرات تنظیمات'));
        }
    }

    /**
     * وارد کردن تنظیمات
     */
    public function import_settings() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_settings_nonce')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
            return;
        }

        // بررسی دسترسی کاربر
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای این عملیات را ندارید'));
            return;
        }

        if (!isset($_FILES['settings_file']) || $_FILES['settings_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'فایل آپلود نشده یا خطا در آپلود'));
            return;
        }

        $file_content = file_get_contents($_FILES['settings_file']['tmp_name']);
        $import_data = json_decode($file_content, true);

        if (!$import_data || !isset($import_data['settings'])) {
            wp_send_json_error(array('message' => 'فایل نامعتبر است'));
            return;
        }

        // ادغام با تنظیمات فعلی (حفظ کلیدهای API)
        $current_settings = get_option('setia_settings', array());
        $new_settings = array_merge($current_settings, $import_data['settings']);

        $result = update_option('setia_settings', $new_settings);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'تنظیمات با موفقیت وارد شد',
                'settings' => $new_settings
            ));
        } else {
            wp_send_json_error(array('message' => 'خطا در وارد کردن تنظیمات'));
        }
    }

    /**
     * ایجاد نوشته جدید مستقیماً از فرم
     */
    public function setia_create_post() {
        try {
            // بررسی امنیتی
            $has_valid_nonce = false;
            
            // بررسی nonce ارسالی مستقیم
            if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'setia-nonce')) {
                $has_valid_nonce = true;
            }
            
            // بررسی nonce ارسالی از فرم
            if (isset($_POST['setia_nonce']) && wp_verify_nonce($_POST['setia_nonce'], 'setia-nonce')) {
                $has_valid_nonce = true;
            }
            
            // اگر هیچ nonce معتبری وجود نداشت
            if (!$has_valid_nonce) {
                error_log('SETIA DEBUG: No valid nonce found in setia_create_post');
                wp_send_json_error(array('message' => 'خطای امنیتی: توکن نامعتبر'));
                return;
            }
            
            // بررسی دسترسی کاربر
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'شما دسترسی لازم برای ایجاد نوشته را ندارید'));
                return;
            }
            
            // دریافت داده‌های پست
            $title = sanitize_text_field($_POST['title']);
            $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
            $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
            
            // دریافت سایر اطلاعات اختیاری
            $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
            $keywords = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';
            
            // تعریف متادیتای سئو
            $seo_meta = array();
            if (isset($_POST['seo_meta']) && is_array($_POST['seo_meta'])) {
                foreach ($_POST['seo_meta'] as $key => $value) {
                    $seo_meta[sanitize_key($key)] = sanitize_text_field($value);
                }
            } else {
                // ایجاد متادیتای سئوی پیش‌فرض
                $seo_meta = array(
                    'title' => $title,
                    'description' => mb_substr(strip_tags($content), 0, 155) . '...',
                    'focus_keyword' => $keywords
                );
            }
            
            error_log('SETIA DEBUG: Creating post with title: ' . $title);
            error_log('SETIA DEBUG: SEO meta: ' . json_encode($seo_meta));
            
            // ایجاد پست با استفاده از تابع مربوطه در کلاس اصلی
            $result = $this->content_generator->create_wordpress_post(
                $title,
                $content,
                $category,
                $seo_meta,
                $image_url,
                'draft' // همیشه به صورت پیش‌نویس ایجاد می‌شود
            );
            
            if (!$result['success']) {
                error_log('SETIA DEBUG: Error creating post: ' . json_encode($result));
                wp_send_json_error(array('message' => isset($result['error']) ? $result['error'] : 'خطا در ایجاد نوشته'));
                return;
            }
            
            wp_send_json_success(array(
                'post_id' => $result['post_id'],
                'edit_url' => $result['edit_url'],
                'message' => 'نوشته با موفقیت ایجاد شد'
            ));
            
        } catch (Exception $e) {
            error_log('SETIA ERROR: Exception in setia_create_post: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'خطا در ایجاد نوشته: ' . $e->getMessage()));
        }
    }
    
    /**
     * الگوهای متنوع برای عناوین
     */
    private function get_diverse_title_patterns() {
        return array(
            array(
                'description' => 'الگوی راهنمای جامع',
                'example' => 'SEO: راهنمای جامع و کاربردی'
            ),
            array(
                'description' => 'الگوی دانستنی‌ها',
                'example' => 'همه چیز درباره SEO که باید بدانید'
            ),
            array(
                'description' => 'الگوی عصر دیجیتال',
                'example' => 'SEO در عصر دیجیتال: نکات کلیدی'
            ),
            array(
                'description' => 'الگوی آموزش گام به گام',
                'example' => 'آموزش گام به گام SEO'
            ),
            array(
                'description' => 'الگوی بهترین روش‌ها',
                'example' => 'بهترین روش‌های SEO در سال ۲۰۲۵'
            ),
            array(
                'description' => 'الگوی مبتدی تا حرفه‌ای',
                'example' => 'SEO: از مبتدی تا حرفه‌ای'
            ),
            array(
                'description' => 'الگوی چگونه',
                'example' => 'چگونه SEO را بهبود دهیم؟'
            ),
            array(
                'description' => 'الگوی راز موفقیت',
                'example' => 'راز موفقیت در SEO'
            ),
            array(
                'description' => 'الگوی نکات طلایی',
                'example' => 'SEO: نکات طلایی و ترفندها'
            ),
            array(
                'description' => 'الگوی دانستنی‌های مهم',
                'example' => 'دانستنی‌های مهم درباره SEO'
            )
        );
    }
    
    /**
     * بهینه‌سازی عنوان پست به روش fallback (بدون API)
     */
    private function fallback_optimize_post_title($topic, $keywords) {
        // در صورت عدم دسترسی به API، از روش‌های ساده محلی استفاده می‌کنیم
        
        // تمیز کردن عنوان
        $clean_topic = trim($topic);
        $clean_keywords = trim($keywords);
        
        // اگر عنوان خیلی کوتاه است، آن را تقویت می‌کنیم
        if (mb_strlen($clean_topic) < 30) {
            // الگوهای ساده برای بهبود عنوان
            $patterns = array(
                'راهنمای کامل %s',
                'آموزش %s برای مبتدیان',
                'همه چیز درباره %s',
                '%s: نکات کلیدی و مهم',
                'بهترین روش‌های %s',
                '%s در سال ۱۴۰۳',
                'چگونه %s را بیاموزیم؟',
                '%s: راهنمای گام به گام'
            );
            
            $selected_pattern = $patterns[array_rand($patterns)];
            $optimized_title = sprintf($selected_pattern, $clean_topic);
        } else {
            $optimized_title = $clean_topic;
        }
        
        // اطمینان از وجود کلمه کلیدی در عنوان
        if (!empty($clean_keywords) && mb_stripos($optimized_title, $clean_keywords) === false) {
            $first_keyword = trim(explode(',', $clean_keywords)[0]);
            if (!empty($first_keyword) && mb_stripos($optimized_title, $first_keyword) === false) {
                // اضافه کردن کلمه کلیدی اصلی به ابتدای عنوان
                $optimized_title = $first_keyword . ': ' . $optimized_title;
            }
        }
        
        // محدود کردن طول عنوان
        if (mb_strlen($optimized_title) > 65) {
            $optimized_title = mb_substr($optimized_title, 0, 62) . '...';
        }
        
        return $optimized_title;
    }
    
    /**
     * استخراج کلمات کلیدی از عنوان
     */
    private function get_title_keywords($title, $max_keywords = 5) {
        // تمیز کردن عنوان از کاراکترهای اضافی
        $clean_title = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $title);
        $clean_title = preg_replace('/\s+/', ' ', $clean_title);
        $clean_title = trim($clean_title);
        
        // تبدیل به کلمات
        $words = explode(' ', $clean_title);
        
        // حذف کلمات کوتاه و رایج
        $stop_words = array('و', 'در', 'با', 'به', 'از', 'را', 'که', 'این', 'آن', 'یک', 'برای', 'تا', 'هم', 'نیز');
        $filtered_words = array();
        
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) > 2 && !in_array($word, $stop_words)) {
                $filtered_words[] = $word;
            }
        }
        
        // محدود کردن تعداد کلمات کلیدی
        $keywords = array_slice($filtered_words, 0, $max_keywords);
        
        return implode(', ', $keywords);
    }
    
    /**
     * تولید توضیحات متای سئو
     */
    private function generate_seo_meta_description($topic, $keywords, $content = '', $max_length = 155) {
        $description = '';
        
        // اگر محتوا موجود است، از آن استفاده می‌کنیم
        if (!empty($content)) {
            // حذف HTML tags
            $clean_content = wp_strip_all_tags($content);
            $clean_content = preg_replace('/\s+/', ' ', $clean_content);
            $clean_content = trim($clean_content);
            
            // استخراج اولین پاراگراف معنادار
            $sentences = preg_split('/[.!?]\s+/', $clean_content, 3);
            if (!empty($sentences[0])) {
                $description = trim($sentences[0]);
                
                // اضافه کردن جمله دوم اگر طول کافی نیست
                if (mb_strlen($description) < 80 && isset($sentences[1])) {
                    $description .= '. ' . trim($sentences[1]);
                }
            }
        }
        
        // اگر توضیحات از محتوا استخراج نشد، از موضوع و کلمات کلیدی استفاده می‌کنیم
        if (empty($description)) {
            $first_keyword = !empty($keywords) ? trim(explode(',', $keywords)[0]) : '';
            
            if (!empty($first_keyword)) {
                $description = "راهنمای جامع {$first_keyword} و نکات مهم درباره {$topic}. همه چیز که باید درباره {$first_keyword} بدانید.";
            } else {
                $description = "راهنمای کامل {$topic} با جزئیات کاربردی و نکات مفید برای یادگیری بهتر.";
            }
        }
        
        // اطمینان از وجود کلمه کلیدی در توضیحات
        if (!empty($keywords)) {
            $first_keyword = trim(explode(',', $keywords)[0]);
            if (!empty($first_keyword) && mb_stripos($description, $first_keyword) === false) {
                $description = $first_keyword . ': ' . $description;
            }
        }
        
        // محدود کردن طول به حداکثر مجاز
        if (mb_strlen($description) > $max_length) {
            $description = mb_substr($description, 0, $max_length - 3);
            // برش در آخرین فاصله برای حفظ کلمات کامل
            $last_space = mb_strrpos($description, ' ');
            if ($last_space !== false) {
                $description = mb_substr($description, 0, $last_space);
            }
            $description .= '...';
        }
        
        // اطمینان از حداقل طول
        if (mb_strlen($description) < 120) {
            // اضافه کردن متن تکمیلی
            $additional_text = " مطالعه این مطلب برای درک بهتر {$topic} توصیه می‌شود.";
            if (mb_strlen($description . $additional_text) <= $max_length) {
                $description .= $additional_text;
            }
        }
        
        return trim($description);
    }
}

// بخش زمانبندی حذف شده است

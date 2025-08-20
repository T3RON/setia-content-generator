<?php
/**
 * کلاس مدیریت زمانبندی محتوا - نسخه بازنویسی شده
 * 
 * @package SETIA Content Generator
 * @version 2.0.0
 * @author SETIA Team
 */

if (!defined('ABSPATH')) {
    exit;
}

class SETIA_Scheduler {
    
    /**
     * نمونه از کلاس تولید محتوا
     */
    private $content_generator;
    
    /**
     * نام جدول زمانبندی‌ها
     */
    private $schedules_table;
    
    /**
     * نام جدول لاگ‌ها
     */
    private $logs_table;
    
    /**
     * فرکانس‌های پشتیبانی شده
     */
    private $supported_frequencies = array(
        'hourly' => 'هر ساعت',
        'twicedaily' => 'دو بار در روز',
        'daily' => 'روزانه',
        'weekly' => 'هفتگی',
        'monthly' => 'ماهانه',
        'setia_every_15min' => 'هر 15 دقیقه',
        'setia_every_30min' => 'هر 30 دقیقه',
        'setia_every_2hours' => 'هر 2 ساعت',
        'setia_every_6hours' => 'هر 6 ساعت'
    );
    
    /**
     * راه‌اندازی کلاس زمانبندی
     */
    public function __construct($content_generator) {
        global $wpdb;
        
        $this->content_generator = $content_generator;
        $this->schedules_table = $wpdb->prefix . 'setia_content_schedules';
        $this->logs_table = $wpdb->prefix . 'setia_scheduler_logs';
        
        // راه‌اندازی hooks
        $this->init_hooks();
        
        // ایجاد جداول دیتابیس در صورت عدم وجود
        $this->create_tables();
    }
    
    /**
     * راه‌اندازی hooks
     */
    private function init_hooks() {
        // افزودن زمانبندی‌های سفارشی
        add_filter('cron_schedules', array($this, 'add_custom_schedules'));
        
        // ثبت اکشن‌های کرون
        add_action('setia_scheduled_content_generation', array($this, 'generate_scheduled_content'));
        
        // AJAX handlers
        add_action('wp_ajax_setia_save_schedule', array($this, 'ajax_save_schedule'));
        add_action('wp_ajax_setia_delete_schedule', array($this, 'ajax_delete_schedule'));
        add_action('wp_ajax_setia_get_schedules', array($this, 'ajax_get_schedules'));
        add_action('wp_ajax_setia_toggle_schedule', array($this, 'ajax_toggle_schedule'));
        add_action('wp_ajax_setia_run_schedule_now', array($this, 'ajax_run_schedule_now'));
        add_action('wp_ajax_setia_get_schedule_logs', array($this, 'ajax_get_schedule_logs'));
        add_action('wp_ajax_setia_clear_schedule_logs', array($this, 'ajax_clear_schedule_logs'));
        
        // بارگذاری اسکریپت‌ها و استایل‌ها
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * ایجاد جداول دیتابیس
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول زمانبندی‌ها
        $schedules_sql = "CREATE TABLE IF NOT EXISTS {$this->schedules_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            topic varchar(500) NOT NULL,
            keywords text,
            category_id mediumint(9) DEFAULT NULL,
            tone varchar(50) DEFAULT 'عادی',
            length varchar(50) DEFAULT 'متوسط',
            frequency varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'active',
            daily_limit int(11) DEFAULT 1,
            generate_image tinyint(1) DEFAULT 1,
            last_run datetime DEFAULT NULL,
            next_run datetime DEFAULT NULL,
            daily_count int(11) DEFAULT 0,
            daily_count_date date DEFAULT NULL,
            total_generated int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY frequency (frequency),
            KEY next_run (next_run)
        ) $charset_collate;";
        
        // جدول لاگ‌ها
        $logs_sql = "CREATE TABLE IF NOT EXISTS {$this->logs_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            schedule_id mediumint(9) DEFAULT NULL,
            message text NOT NULL,
            type varchar(20) DEFAULT 'info',
            post_id mediumint(9) DEFAULT NULL,
            execution_time float DEFAULT NULL,
            memory_usage varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY schedule_id (schedule_id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($schedules_sql);
        dbDelta($logs_sql);
    }
    
    /**
     * افزودن زمانبندی‌های سفارشی
     */
    public function add_custom_schedules($schedules) {
        $schedules['setia_every_15min'] = array(
            'interval' => 15 * 60,
            'display' => __('هر 15 دقیقه', 'setia')
        );
        
        $schedules['setia_every_30min'] = array(
            'interval' => 30 * 60,
            'display' => __('هر 30 دقیقه', 'setia')
        );
        
        $schedules['setia_every_2hours'] = array(
            'interval' => 2 * 60 * 60,
            'display' => __('هر 2 ساعت', 'setia')
        );
        
        $schedules['setia_every_6hours'] = array(
            'interval' => 6 * 60 * 60,
            'display' => __('هر 6 ساعت', 'setia')
        );
        
        return $schedules;
    }
    
    /**
     * بارگذاری اسکریپت‌ها و استایل‌ها
     */
    public function enqueue_admin_scripts($hook) {
        // فقط در صفحه scheduler
        if (strpos($hook, 'setia-scheduler') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'setia-scheduler-style',
            plugins_url('/assets/css/scheduler.css', dirname(__FILE__)),
            array(),
            '2.0.0'
        );
        
        // JavaScript
        wp_enqueue_script(
            'setia-scheduler-script',
            plugins_url('/assets/js/scheduler.js', dirname(__FILE__)),
            array('jquery', 'wp-util'),
            '2.0.0',
            true
        );
        
        // Localize script
        wp_localize_script('setia-scheduler-script', 'setiaScheduler', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('setia_scheduler_nonce'),
            'strings' => array(
                'confirmDelete' => __('آیا از حذف این زمانبندی اطمینان دارید؟', 'setia'),
                'confirmClearLogs' => __('آیا از پاک کردن تمام لاگ‌ها اطمینان دارید؟', 'setia'),
                'saving' => __('در حال ذخیره...', 'setia'),
                'loading' => __('در حال بارگذاری...', 'setia'),
                'success' => __('عملیات با موفقیت انجام شد', 'setia'),
                'error' => __('خطا در انجام عملیات', 'setia')
            )
        ));
    }
    
    /**
     * نمایش صفحه زمانبندی
     */
    public function scheduler_page() {
        include plugin_dir_path(dirname(__FILE__)) . 'templates/scheduler-page.php';
    }
    
    /**
     * دریافت تمام زمانبندی‌ها
     */
    public function get_schedules($status = null) {
        global $wpdb;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }
        
        $results = $wpdb->get_results(
            "SELECT * FROM {$this->schedules_table} {$where} ORDER BY created_at DESC",
            ARRAY_A
        );
        
        return $results ? $results : array();
    }
    
    /**
     * دریافت یک زمانبندی بر اساس ID
     */
    public function get_schedule($id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->schedules_table} WHERE id = %d", $id),
            ARRAY_A
        );
    }

    /**
     * اعتبارسنجی داده‌های زمانبندی
     */
    private function validate_schedule_data($data) {
        $errors = array();

        // بررسی فیلدهای ضروری
        if (empty($data['title'])) {
            $errors[] = 'عنوان زمانبندی الزامی است';
        }

        if (empty($data['topic'])) {
            $errors[] = 'موضوع محتوا الزامی است';
        }

        if (empty($data['frequency']) || !array_key_exists($data['frequency'], $this->supported_frequencies)) {
            $errors[] = 'فرکانس انتخاب شده معتبر نیست';
        }

        if (!empty($data['daily_limit']) && (!is_numeric($data['daily_limit']) || $data['daily_limit'] < 1)) {
            $errors[] = 'حد روزانه باید عددی مثبت باشد';
        }

        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(', ', $errors));
        }

        // پاک‌سازی و آماده‌سازی داده‌ها
        return array(
            'title' => sanitize_text_field($data['title']),
            'topic' => sanitize_textarea_field($data['topic']),
            'keywords' => sanitize_textarea_field($data['keywords'] ?? ''),
            'category_id' => intval($data['category_id'] ?? 0),
            'tone' => sanitize_text_field($data['tone'] ?? 'عادی'),
            'length' => sanitize_text_field($data['length'] ?? 'متوسط'),
            'frequency' => sanitize_text_field($data['frequency']),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'daily_limit' => intval($data['daily_limit'] ?? 1),
            'generate_image' => !empty($data['generate_image']) ? 1 : 0
        );
    }

    /**
     * محاسبه زمان اجرای بعدی
     */
    private function calculate_next_run($frequency) {
        $schedules = wp_get_schedules();

        if (!isset($schedules[$frequency])) {
            return null;
        }

        $interval = $schedules[$frequency]['interval'];
        return date('Y-m-d H:i:s', time() + $interval);
    }

    /**
     * تنظیم cron job
     */
    private function schedule_cron_job($schedule_id, $data) {
        $hook = 'setia_scheduled_content_generation';
        $args = array('schedule_id' => $schedule_id);

        // حذف cron job قبلی
        wp_clear_scheduled_hook($hook, $args);

        // تنظیم cron job جدید اگر فعال است
        if ($data['status'] === 'active') {
            wp_schedule_event(time(), $data['frequency'], $hook, $args);
        }
    }

    /**
     * ثبت پیام در لاگ
     */
    private function log_message($schedule_id, $message, $type = 'info', $post_id = null, $execution_time = null) {
        global $wpdb;

        $wpdb->insert(
            $this->logs_table,
            array(
                'schedule_id' => $schedule_id,
                'message' => $message,
                'type' => $type,
                'post_id' => $post_id,
                'execution_time' => $execution_time,
                'memory_usage' => size_format(memory_get_usage(true))
            ),
            array('%d', '%s', '%s', '%d', '%f', '%s')
        );
    }

    /**
     * AJAX: ذخیره زمانبندی
     */
    public function ajax_save_schedule() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_scheduler_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: دسترسی غیرمجاز'));
            return;
        }

        $result = $this->save_schedule($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => 'زمانبندی با موفقیت ذخیره شد',
                'schedule_id' => $result
            ));
        }
    }

    /**
     * ذخیره زمانبندی جدید یا به‌روزرسانی موجود
     */
    public function save_schedule($data) {
        global $wpdb;

        // اعتبارسنجی داده‌ها
        $validated_data = $this->validate_schedule_data($data);
        if (is_wp_error($validated_data)) {
            return $validated_data;
        }

        // محاسبه زمان اجرای بعدی
        $next_run = $this->calculate_next_run($validated_data['frequency']);
        $validated_data['next_run'] = $next_run;

        if (isset($data['id']) && $data['id'] > 0) {
            // به‌روزرسانی
            $result = $wpdb->update(
                $this->schedules_table,
                $validated_data,
                array('id' => $data['id']),
                array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s'),
                array('%d')
            );

            $schedule_id = $data['id'];
        } else {
            // ایجاد جدید
            $result = $wpdb->insert(
                $this->schedules_table,
                $validated_data,
                array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
            );

            $schedule_id = $wpdb->insert_id;
        }

        if ($result === false) {
            return new WP_Error('db_error', 'خطا در ذخیره زمانبندی');
        }

        // تنظیم cron job
        $this->schedule_cron_job($schedule_id, $validated_data);

        // ثبت لاگ
        $this->log_message($schedule_id, 'زمانبندی ذخیره شد', 'info');

        return $schedule_id;
    }

    /**
     * AJAX: حذف زمانبندی
     */
    public function ajax_delete_schedule() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_scheduler_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: دسترسی غیرمجاز'));
            return;
        }

        $schedule_id = intval($_POST['schedule_id']);
        $result = $this->delete_schedule($schedule_id);

        if ($result) {
            wp_send_json_success(array('message' => 'زمانبندی با موفقیت حذف شد'));
        } else {
            wp_send_json_error(array('message' => 'خطا در حذف زمانبندی'));
        }
    }

    /**
     * حذف زمانبندی
     */
    public function delete_schedule($schedule_id) {
        global $wpdb;

        // حذف cron job
        $hook = 'setia_scheduled_content_generation';
        $args = array('schedule_id' => $schedule_id);
        wp_clear_scheduled_hook($hook, $args);

        // حذف از دیتابیس
        $result = $wpdb->delete(
            $this->schedules_table,
            array('id' => $schedule_id),
            array('%d')
        );

        if ($result) {
            // ثبت لاگ
            $this->log_message($schedule_id, 'زمانبندی حذف شد', 'warning');
        }

        return $result !== false;
    }

    /**
     * AJAX: دریافت لیست زمانبندی‌ها
     */
    public function ajax_get_schedules() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_scheduler_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: دسترسی غیرمجاز'));
            return;
        }

        $schedules = $this->get_schedules();

        // اضافه کردن اطلاعات اضافی
        foreach ($schedules as &$schedule) {
            $schedule['frequency_label'] = $this->supported_frequencies[$schedule['frequency']] ?? $schedule['frequency'];
            $schedule['next_run_formatted'] = $schedule['next_run'] ?
                wp_date('Y/m/d H:i', strtotime($schedule['next_run'])) : 'نامشخص';
            $schedule['last_run_formatted'] = $schedule['last_run'] ?
                wp_date('Y/m/d H:i', strtotime($schedule['last_run'])) : 'هرگز';
        }

        wp_send_json_success($schedules);
    }

    /**
     * AJAX: تغییر وضعیت زمانبندی
     */
    public function ajax_toggle_schedule() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_scheduler_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: دسترسی غیرمجاز'));
            return;
        }

        $schedule_id = intval($_POST['schedule_id']);
        $new_status = sanitize_text_field($_POST['status']);

        $result = $this->toggle_schedule_status($schedule_id, $new_status);

        if ($result) {
            wp_send_json_success(array('message' => 'وضعیت زمانبندی تغییر کرد'));
        } else {
            wp_send_json_error(array('message' => 'خطا در تغییر وضعیت'));
        }
    }

    /**
     * تغییر وضعیت زمانبندی
     */
    public function toggle_schedule_status($schedule_id, $new_status) {
        global $wpdb;

        $schedule = $this->get_schedule($schedule_id);
        if (!$schedule) {
            return false;
        }

        // به‌روزرسانی وضعیت
        $result = $wpdb->update(
            $this->schedules_table,
            array('status' => $new_status),
            array('id' => $schedule_id),
            array('%s'),
            array('%d')
        );

        if ($result !== false) {
            // مدیریت cron job
            $hook = 'setia_scheduled_content_generation';
            $args = array('schedule_id' => $schedule_id);

            wp_clear_scheduled_hook($hook, $args);

            if ($new_status === 'active') {
                wp_schedule_event(time(), $schedule['frequency'], $hook, $args);
            }

            // ثبت لاگ
            $this->log_message($schedule_id, "وضعیت به {$new_status} تغییر کرد", 'info');
        }

        return $result !== false;
    }

    /**
     * AJAX: اجرای فوری زمانبندی
     */
    public function ajax_run_schedule_now() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_scheduler_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: دسترسی غیرمجاز'));
            return;
        }

        $schedule_id = intval($_POST['schedule_id']);
        $result = $this->run_schedule_now($schedule_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => 'زمانبندی با موفقیت اجرا شد',
                'post_id' => $result
            ));
        }
    }

    /**
     * اجرای فوری یک زمانبندی
     */
    public function run_schedule_now($schedule_id) {
        $schedule = $this->get_schedule($schedule_id);
        if (!$schedule) {
            return new WP_Error('schedule_not_found', 'زمانبندی یافت نشد');
        }

        return $this->generate_scheduled_content($schedule_id);
    }

    /**
     * تولید محتوای زمانبندی شده
     */
    public function generate_scheduled_content($schedule_id) {
        $start_time = microtime(true);

        $schedule = $this->get_schedule($schedule_id);
        if (!$schedule) {
            $this->log_message($schedule_id, 'زمانبندی یافت نشد', 'error');
            return new WP_Error('schedule_not_found', 'زمانبندی یافت نشد');
        }

        // بررسی وضعیت فعال بودن
        if ($schedule['status'] !== 'active') {
            $this->log_message($schedule_id, 'زمانبندی غیرفعال است', 'warning');
            return new WP_Error('schedule_inactive', 'زمانبندی غیرفعال است');
        }

        // بررسی محدودیت روزانه
        if (!$this->check_daily_limit($schedule)) {
            $this->log_message($schedule_id, 'محدودیت روزانه رسیده است', 'warning');
            return new WP_Error('daily_limit_reached', 'محدودیت روزانه رسیده است');
        }

        try {
            // تولید محتوا
            $content_data = array(
                'topic' => $schedule['topic'],
                'keywords' => $schedule['keywords'],
                'tone' => $schedule['tone'],
                'length' => $schedule['length'],
                'generate_image' => $schedule['generate_image']
            );

            $generated_content = $this->content_generator->generate_content($content_data);

            if (is_wp_error($generated_content)) {
                throw new Exception($generated_content->get_error_message());
            }

            // ایجاد پست
            $post_data = array(
                'post_title' => $generated_content['title'] ?? $schedule['title'],
                'post_content' => $generated_content['content'],
                'post_status' => 'publish',
                'post_type' => 'post'
            );

            if ($schedule['category_id']) {
                $post_data['post_category'] = array($schedule['category_id']);
            }

            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                throw new Exception('خطا در ایجاد پست: ' . $post_id->get_error_message());
            }

            // تنظیم تصویر شاخص
            if ($schedule['generate_image'] && !empty($generated_content['image_url'])) {
                $this->set_featured_image($post_id, $generated_content['image_url']);
            }

            // به‌روزرسانی آمار زمانبندی
            $this->update_schedule_stats($schedule_id);

            $execution_time = microtime(true) - $start_time;

            // ثبت لاگ موفقیت
            $this->log_message(
                $schedule_id,
                "محتوا با موفقیت تولید شد - پست ID: {$post_id}",
                'success',
                $post_id,
                $execution_time
            );

            return $post_id;

        } catch (Exception $e) {
            $execution_time = microtime(true) - $start_time;

            // ثبت لاگ خطا
            $this->log_message(
                $schedule_id,
                'خطا در تولید محتوا: ' . $e->getMessage(),
                'error',
                null,
                $execution_time
            );

            return new WP_Error('generation_failed', $e->getMessage());
        }
    }

    /**
     * بررسی محدودیت روزانه
     */
    private function check_daily_limit($schedule) {
        $today = date('Y-m-d');

        // اگر تاریخ تغییر کرده، شمارنده را ریست کن
        if ($schedule['daily_count_date'] !== $today) {
            global $wpdb;
            $wpdb->update(
                $this->schedules_table,
                array(
                    'daily_count' => 0,
                    'daily_count_date' => $today
                ),
                array('id' => $schedule['id']),
                array('%d', '%s'),
                array('%d')
            );
            $schedule['daily_count'] = 0;
        }

        return $schedule['daily_count'] < $schedule['daily_limit'];
    }

    /**
     * به‌روزرسانی آمار زمانبندی
     */
    private function update_schedule_stats($schedule_id) {
        global $wpdb;

        $now = current_time('mysql');
        $schedule = $this->get_schedule($schedule_id);

        // محاسبه زمان اجرای بعدی
        $next_run = $this->calculate_next_run($schedule['frequency']);

        $wpdb->update(
            $this->schedules_table,
            array(
                'last_run' => $now,
                'next_run' => $next_run,
                'daily_count' => $schedule['daily_count'] + 1,
                'daily_count_date' => date('Y-m-d'),
                'total_generated' => $schedule['total_generated'] + 1
            ),
            array('id' => $schedule_id),
            array('%s', '%s', '%d', '%s', '%d'),
            array('%d')
        );
    }

    /**
     * تنظیم تصویر شاخص پست
     */
    private function set_featured_image($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $image_id = media_sideload_image($image_url, $post_id, null, 'id');

        if (!is_wp_error($image_id)) {
            set_post_thumbnail($post_id, $image_id);
        }
    }

    /**
     * AJAX: دریافت لاگ‌های زمانبندی
     */
    public function ajax_get_schedule_logs() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_scheduler_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: دسترسی غیرمجاز'));
            return;
        }

        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        $logs = $this->get_schedule_logs($schedule_id, $limit, $offset);

        wp_send_json_success($logs);
    }

    /**
     * دریافت لاگ‌های زمانبندی
     */
    public function get_schedule_logs($schedule_id = null, $limit = 50, $offset = 0) {
        global $wpdb;

        $where = '';
        $params = array();

        if ($schedule_id) {
            $where = 'WHERE l.schedule_id = %d';
            $params[] = $schedule_id;
        }

        $sql = "SELECT l.*, s.title as schedule_title
                FROM {$this->logs_table} l
                LEFT JOIN {$this->schedules_table} s ON l.schedule_id = s.id
                {$where}
                ORDER BY l.created_at DESC
                LIMIT %d OFFSET %d";

        $params[] = $limit;
        $params[] = $offset;

        $results = $wpdb->get_results(
            $wpdb->prepare($sql, $params),
            ARRAY_A
        );

        // فرمت کردن تاریخ‌ها
        foreach ($results as &$log) {
            $log['created_at_formatted'] = wp_date('Y/m/d H:i:s', strtotime($log['created_at']));
            $log['execution_time_formatted'] = $log['execution_time'] ?
                number_format($log['execution_time'], 3) . ' ثانیه' : '-';
        }

        return $results;
    }

    /**
     * AJAX: پاک کردن لاگ‌ها
     */
    public function ajax_clear_schedule_logs() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['nonce'], 'setia_scheduler_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'خطای امنیتی: دسترسی غیرمجاز'));
            return;
        }

        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;
        $days_old = isset($_POST['days_old']) ? intval($_POST['days_old']) : 30;

        $result = $this->clear_old_logs($schedule_id, $days_old);

        if ($result) {
            wp_send_json_success(array(
                'message' => "لاگ‌های قدیمی‌تر از {$days_old} روز پاک شدند",
                'deleted_count' => $result
            ));
        } else {
            wp_send_json_error(array('message' => 'خطا در پاک کردن لاگ‌ها'));
        }
    }

    /**
     * پاک کردن لاگ‌های قدیمی
     */
    public function clear_old_logs($schedule_id = null, $days_old = 30) {
        global $wpdb;

        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));

        $where = 'WHERE created_at < %s';
        $params = array($date_threshold);

        if ($schedule_id) {
            $where .= ' AND schedule_id = %d';
            $params[] = $schedule_id;
        }

        $sql = "DELETE FROM {$this->logs_table} {$where}";

        return $wpdb->query($wpdb->prepare($sql, $params));
    }

    /**
     * دریافت آمار کلی زمانبندی‌ها
     */
    public function get_scheduler_stats() {
        global $wpdb;

        $stats = array();

        // تعداد کل زمانبندی‌ها
        $stats['total_schedules'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->schedules_table}"
        );

        // تعداد زمانبندی‌های فعال
        $stats['active_schedules'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->schedules_table} WHERE status = %s",
                'active'
            )
        );

        // تعداد کل محتوای تولید شده
        $stats['total_generated'] = $wpdb->get_var(
            "SELECT SUM(total_generated) FROM {$this->schedules_table}"
        );

        // تعداد محتوای تولید شده امروز
        $today = date('Y-m-d');
        $stats['today_generated'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(daily_count) FROM {$this->schedules_table} WHERE daily_count_date = %s",
                $today
            )
        );

        // آخرین اجرا
        $last_run = $wpdb->get_var(
            "SELECT MAX(last_run) FROM {$this->schedules_table} WHERE last_run IS NOT NULL"
        );
        $stats['last_run'] = $last_run ? wp_date('Y/m/d H:i', strtotime($last_run)) : 'هرگز';

        // بعدین اجرا
        $next_run = $wpdb->get_var(
            "SELECT MIN(next_run) FROM {$this->schedules_table} WHERE status = 'active' AND next_run IS NOT NULL"
        );
        $stats['next_run'] = $next_run ? wp_date('Y/m/d H:i', strtotime($next_run)) : 'نامشخص';

        return $stats;
    }

    /**
     * دریافت فرکانس‌های پشتیبانی شده
     */
    public function get_supported_frequencies() {
        return $this->supported_frequencies;
    }

    /**
     * بررسی وضعیت سیستم زمانبندی
     */
    public function check_system_status() {
        $status = array(
            'cron_enabled' => !defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON,
            'tables_exist' => $this->check_tables_exist(),
            'permissions' => current_user_can('manage_options')
        );

        $status['overall'] = $status['cron_enabled'] && $status['tables_exist'] && $status['permissions'];

        return $status;
    }

    /**
     * بررسی وجود جداول
     */
    private function check_tables_exist() {
        global $wpdb;

        $schedules_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $this->schedules_table)
        ) === $this->schedules_table;

        $logs_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $this->logs_table)
        ) === $this->logs_table;

        return $schedules_exists && $logs_exists;
    }
}

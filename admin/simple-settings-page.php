<?php
/**
 * SETIA Simple Settings Page
 * 
 * صفحه تنظیمات ساده و کاربرپسند
 * 
 * @package SETIA_Content_Generator
 * @version 1.0.0
 * @author SETIA Team
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// دریافت تنظیمات فعلی
$settings_manager = SETIA_Simple_Settings::get_instance();
$settings = $settings_manager->get_settings();
?>

<div class="wrap setia-simple-settings">
    <h1>
        <span class="dashicons dashicons-admin-settings"></span>
        تنظیمات SETIA Content Generator
    </h1>
    
    <div class="setia-settings-container">
        
        <!-- پیام‌های سیستم -->
        <div id="setia-message" class="notice" style="display: none;">
            <p></p>
        </div>
        
        <!-- فرم تنظیمات -->
        <form id="setia-simple-settings-form" method="post">
            
            <!-- بخش API -->
            <div class="setia-settings-section">
                <h2>
                    <span class="dashicons dashicons-admin-network"></span>
                    تنظیمات API
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gemini_api_key">کلید API Gemini</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="gemini_api_key" 
                                   name="settings[gemini_api_key]" 
                                   value="<?php echo esc_attr($settings['gemini_api_key']); ?>" 
                                   class="regular-text" 
                                   placeholder="AIza..." />
                            <button type="button" 
                                    class="button button-secondary setia-test-api" 
                                    data-api-type="gemini" 
                                    data-input-id="gemini_api_key">
                                تست اتصال
                            </button>
                            <p class="description">
                                کلید API خود را از 
                                <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a> 
                                دریافت کنید
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="imagine_art_api_key">کلید API Imagine Art</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="imagine_art_api_key" 
                                   name="settings[imagine_art_api_key]" 
                                   value="<?php echo esc_attr($settings['imagine_art_api_key']); ?>" 
                                   class="regular-text" 
                                   placeholder="کلید API..." />
                            <button type="button" 
                                    class="button button-secondary setia-test-api" 
                                    data-api-type="imagine_art" 
                                    data-input-id="imagine_art_api_key">
                                تست اتصال
                            </button>
                            <p class="description">
                                کلید API خود را از 
                                <a href="https://www.imagineart.ai/" target="_blank">Imagine Art</a> 
                                دریافت کنید
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- بخش تولید محتوا -->
            <div class="setia-settings-section">
                <h2>
                    <span class="dashicons dashicons-edit"></span>
                    تنظیمات تولید محتوا
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_tone">لحن پیش‌فرض</label>
                        </th>
                        <td>
                            <select id="default_tone" name="settings[default_tone]" class="regular-text">
                                <option value="عادی" <?php selected($settings['default_tone'], 'عادی'); ?>>عادی</option>
                                <option value="رسمی" <?php selected($settings['default_tone'], 'رسمی'); ?>>رسمی</option>
                                <option value="دوستانه" <?php selected($settings['default_tone'], 'دوستانه'); ?>>دوستانه</option>
                                <option value="علمی" <?php selected($settings['default_tone'], 'علمی'); ?>>علمی</option>
                                <option value="خبری" <?php selected($settings['default_tone'], 'خبری'); ?>>خبری</option>
                                <option value="طنز" <?php selected($settings['default_tone'], 'طنز'); ?>>طنز</option>
                            </select>
                            <p class="description">لحن پیش‌فرض برای تولید محتوا</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_length">طول پیش‌فرض</label>
                        </th>
                        <td>
                            <select id="default_length" name="settings[default_length]" class="regular-text">
                                <option value="کوتاه" <?php selected($settings['default_length'], 'کوتاه'); ?>>کوتاه (100-200 کلمه)</option>
                                <option value="متوسط" <?php selected($settings['default_length'], 'متوسط'); ?>>متوسط (300-500 کلمه)</option>
                                <option value="بلند" <?php selected($settings['default_length'], 'بلند'); ?>>بلند (600-800 کلمه)</option>
                                <option value="خیلی بلند" <?php selected($settings['default_length'], 'خیلی بلند'); ?>>خیلی بلند (900+ کلمه)</option>
                            </select>
                            <p class="description">طول پیش‌فرض برای تولید محتوا</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_seo">بهینه‌سازی SEO</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="enable_seo" 
                                       name="settings[enable_seo]" 
                                       value="yes" 
                                       <?php checked($settings['enable_seo'], 'yes'); ?> />
                                فعال‌سازی بهینه‌سازی خودکار SEO
                            </label>
                            <p class="description">شامل کلمات کلیدی، متا توضیحات و ساختار مناسب</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- بخش تولید تصویر -->
            <div class="setia-settings-section">
                <h2>
                    <span class="dashicons dashicons-format-image"></span>
                    تنظیمات تولید تصویر
                </h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_image_generation">تولید خودکار تصویر</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="enable_image_generation" 
                                       name="settings[enable_image_generation]" 
                                       value="yes" 
                                       <?php checked($settings['enable_image_generation'], 'yes'); ?> />
                                فعال‌سازی تولید خودکار تصویر برای مطالب
                            </label>
                            <p class="description">تصاویر مرتبط با محتوا به صورت خودکار تولید می‌شوند</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_image_style">استایل پیش‌فرض تصویر</label>
                        </th>
                        <td>
                            <select id="default_image_style" name="settings[default_image_style]" class="regular-text">
                                <option value="realistic" <?php selected($settings['default_image_style'], 'realistic'); ?>>واقع‌گرایانه</option>
                                <option value="cartoon" <?php selected($settings['default_image_style'], 'cartoon'); ?>>کارتونی</option>
                                <option value="artistic" <?php selected($settings['default_image_style'], 'artistic'); ?>>هنری</option>
                                <option value="abstract" <?php selected($settings['default_image_style'], 'abstract'); ?>>انتزاعی</option>
                            </select>
                            <p class="description">استایل پیش‌فرض برای تولید تصاویر</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_aspect_ratio">نسبت ابعاد پیش‌فرض</label>
                        </th>
                        <td>
                            <select id="default_aspect_ratio" name="settings[default_aspect_ratio]" class="regular-text">
                                <option value="16:9" <?php selected($settings['default_aspect_ratio'], '16:9'); ?>>16:9 (افقی)</option>
                                <option value="1:1" <?php selected($settings['default_aspect_ratio'], '1:1'); ?>>1:1 (مربع)</option>
                                <option value="4:3" <?php selected($settings['default_aspect_ratio'], '4:3'); ?>>4:3 (کلاسیک)</option>
                                <option value="9:16" <?php selected($settings['default_aspect_ratio'], '9:16'); ?>>9:16 (عمودی)</option>
                            </select>
                            <p class="description">نسبت ابعاد پیش‌فرض برای تولید تصاویر</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- دکمه‌های عملیات -->
            <div class="setia-settings-actions">
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span>
                        ذخیره تنظیمات
                    </button>
                    
                    <button type="button" id="setia-reset-settings" class="button button-secondary">
                        <span class="dashicons dashicons-update"></span>
                        بازنشانی تنظیمات
                    </button>
                </p>
            </div>
            
            <!-- فیلدهای مخفی -->
            <?php wp_nonce_field('setia_simple_nonce', 'setia_nonce'); ?>
            
        </form>
        
        <!-- اطلاعات سیستم -->
        <div class="setia-system-info">
            <h3>اطلاعات سیستم</h3>
            <table class="widefat">
                <tr>
                    <td><strong>نسخه افزونه:</strong></td>
                    <td><?php echo esc_html($settings['version']); ?></td>
                </tr>
                <tr>
                    <td><strong>آخرین بروزرسانی:</strong></td>
                    <td>
                        <?php 
                        if (!empty($settings['updated_at'])) {
                            echo esc_html(date_i18n('Y/m/d H:i', strtotime($settings['updated_at'])));
                        } else {
                            echo 'هرگز';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>وضعیت API Gemini:</strong></td>
                    <td>
                        <?php if (!empty($settings['gemini_api_key'])): ?>
                            <span class="setia-status-active">تنظیم شده</span>
                        <?php else: ?>
                            <span class="setia-status-inactive">تنظیم نشده</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>وضعیت API Imagine Art:</strong></td>
                    <td>
                        <?php if (!empty($settings['imagine_art_api_key'])): ?>
                            <span class="setia-status-active">تنظیم شده</span>
                        <?php else: ?>
                            <span class="setia-status-inactive">تنظیم نشده</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
    </div>
</div>

<!-- Loading Overlay -->
<div id="setia-loading-overlay" style="display: none;">
    <div class="setia-loading-content">
        <div class="setia-spinner"></div>
        <p>در حال پردازش...</p>
    </div>
</div>
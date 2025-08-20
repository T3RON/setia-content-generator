<?php
/**
 * صفحه تاریخچه تولید محتوا
 * SETIA Content Generator Plugin - History Page
 *
 * @package SETIA
 * @version 2.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// بررسی دسترسی کاربر
if (!current_user_can('edit_posts')) {
    wp_die(__('شما دسترسی لازم برای مشاهده این صفحه را ندارید.'));
}

// دریافت تنظیمات برای نسخه افزونه
$setia_settings = get_option('setia_settings', array());
$plugin_version = isset($setia_settings['version']) ? $setia_settings['version'] : '1.0.0';
?>

<div class="setia-wrapper">
    <!-- هدر صفحه -->
    <div class="setia-header">
        <div class="setia-title">
            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/setia-logo.png'; ?>" alt="SETIA Content Generator" class="setia-logo">
            <h1>تاریخچه محتوا</h1>
        </div>
        <div class="setia-version"><?php echo esc_html($plugin_version); ?></div>
    </div>

    <!-- بخش اعلان‌ها -->
    <div id="setia-alerts"></div>
    
    <!-- آمار و اطلاعات کلی -->
    <div class="setia-flex setia-flex-wrap setia-flex-gap">
        <div class="setia-card" style="flex: 1; min-width: 200px;">
            <div class="setia-card-header">
                <h3 class="setia-card-title">کل محتوا</h3>
            </div>
            <div class="setia-flex setia-flex-center" style="font-size: 24px; font-weight: bold; padding: 10px 0;">
                <span id="total-content">0</span>
            </div>
        </div>
        
        <div class="setia-card" style="flex: 1; min-width: 200px;">
            <div class="setia-card-header">
                <h3 class="setia-card-title">منتشر شده</h3>
            </div>
            <div class="setia-flex setia-flex-center" style="font-size: 24px; font-weight: bold; padding: 10px 0;">
                <span id="published-content">0</span>
            </div>
        </div>
        
        <div class="setia-card" style="flex: 1; min-width: 200px;">
            <div class="setia-card-header">
                <h3 class="setia-card-title">پیش‌نویس</h3>
            </div>
            <div class="setia-flex setia-flex-center" style="font-size: 24px; font-weight: bold; padding: 10px 0;">
                <span id="draft-content">0</span>
            </div>
        </div>
        
        <div class="setia-card" style="flex: 1; min-width: 200px;">
            <div class="setia-card-header">
                <h3 class="setia-card-title">محصولات</h3>
            </div>
            <div class="setia-flex setia-flex-center" style="font-size: 24px; font-weight: bold; padding: 10px 0;">
                <span id="product-content">0</span>
            </div>
        </div>
    </div>

    <!-- فیلترهای جستجو -->
    <div class="setia-card">
        <div class="setia-card-header">
            <h3 class="setia-card-title">فیلترهای پیشرفته</h3>
            <button id="toggle-filters" class="setia-btn setia-btn-sm setia-btn-secondary">
                <span class="dashicons dashicons-filter"></span>
                نمایش/مخفی کردن
            </button>
        </div>
        
        <div id="filters-content" class="setia-hidden">
            <div class="setia-history-filters">
                <div class="setia-form-group">
                    <label for="filter-keyword" class="setia-form-label">جستجو</label>
                    <input type="text" id="filter-keyword" class="setia-form-input" placeholder="جستجو در عنوان و محتوا...">
                </div>
                
                <div class="setia-form-group">
                    <label for="filter-date" class="setia-form-label">تاریخ</label>
                    <select id="filter-date" class="setia-form-select">
                        <option value="">همه تاریخ‌ها</option>
                        <option value="today">امروز</option>
                        <option value="yesterday">دیروز</option>
                        <option value="week">هفته اخیر</option>
                        <option value="month">ماه اخیر</option>
                    </select>
                </div>
                
                <div class="setia-form-group">
                    <label for="filter-type" class="setia-form-label">نوع محتوا</label>
                    <select id="filter-type" class="setia-form-select">
                        <option value="">همه انواع</option>
                        <option value="article">مقاله</option>
                        <option value="product">محصول</option>
                        <option value="image">تصویر</option>
                        <option value="seo">محتوای سئو</option>
                    </select>
                </div>
                
                <div class="setia-form-group">
                    <label for="filter-status" class="setia-form-label">وضعیت</label>
                    <select id="filter-status" class="setia-form-select">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="published">منتشر شده</option>
                        <option value="draft">پیش‌نویس</option>
                        <option value="pending">در انتظار بررسی</option>
                    </select>
                </div>
            </div>
            
            <div class="setia-flex setia-flex-end">
                <button id="reset-filters" class="setia-btn setia-btn-secondary">
                    <span class="dashicons dashicons-dismiss"></span>
                    پاک کردن فیلترها
                </button>
                <button id="apply-filters" class="setia-btn setia-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    اعمال فیلترها
                </button>
            </div>
        </div>
    </div>

    <!-- اقدامات گروهی -->
    <div class="setia-history-actions">
        <div class="setia-history-bulk-actions">
            <select id="bulk-action" class="setia-form-select" style="width: auto;">
                <option value="">اقدامات گروهی</option>
                <option value="delete">حذف</option>
                <option value="export">خروجی گرفتن</option>
                        <option value="publish">انتشار</option>
                    </select>
            <button id="apply-bulk-action" class="setia-btn setia-btn-secondary">
                <span class="dashicons dashicons-yes-alt"></span>
                        اعمال
                    </button>
                </div>
                
        <div class="setia-flex setia-flex-gap">
            <button id="refresh-data" class="setia-btn setia-btn-secondary">
                <span class="dashicons dashicons-update"></span>
                بروزرسانی
            </button>
            <button id="export-excel" class="setia-btn setia-btn-success">
                <span class="dashicons dashicons-download"></span>
                خروجی Excel
            </button>
        </div>
        </div>
        
    <!-- جدول محتوا -->
    <div class="setia-card">
        <table class="setia-table" id="history-table">
                <thead>
                    <tr>
                    <th style="width: 30px;">
                        <input type="checkbox" id="select-all">
                        </th>
                    <th style="width: 50px;">شناسه</th>
                    <th>عنوان</th>
                    <th>نوع</th>
                    <th>تاریخ</th>
                    <th>وضعیت</th>
                    <th style="width: 120px;">عملیات</th>
                    </tr>
                </thead>
            <tbody id="history-content">
                <tr>
                    <td colspan="7" class="setia-loading">در حال بارگذاری...</td>
                </tr>
                </tbody>
            </table>
        </div>
        
    <!-- پاگینیشن -->
    <div class="setia-pagination" id="history-pagination">
        <!-- پاگینیشن با جاوااسکریپت ایجاد می‌شود -->
        </div>
        
    <!-- مدال نمایش محتوا -->
    <div class="setia-modal-overlay" id="content-modal">
        <div class="setia-modal">
            <div class="setia-modal-header">
                <h3 class="setia-modal-title" id="modal-title">نمایش محتوا</h3>
                <button class="setia-modal-close">&times;</button>
            </div>
            <div class="setia-modal-body">
                <div id="modal-content"></div>
        </div>
        <div class="setia-modal-footer">
                <button class="setia-btn setia-btn-secondary setia-modal-close">بستن</button>
                <button class="setia-btn setia-btn-primary" id="modal-edit">ویرایش</button>
                <button class="setia-btn setia-btn-success" id="modal-publish">انتشار</button>
        </div>
    </div>
</div>

    <!-- مدال تایید حذف -->
    <div class="setia-modal-overlay" id="delete-modal">
        <div class="setia-modal">
        <div class="setia-modal-header">
                <h3 class="setia-modal-title">تایید حذف</h3>
                <button class="setia-modal-close">&times;</button>
        </div>
        <div class="setia-modal-body">
                <p>آیا از حذف این محتوا اطمینان دارید؟</p>
                <p>این عملیات قابل بازگشت نیست.</p>
        </div>
        <div class="setia-modal-footer">
                <button class="setia-btn setia-btn-secondary setia-modal-close">انصراف</button>
                <button class="setia-btn setia-btn-danger" id="confirm-delete">حذف</button>
        </div>
    </div>
</div>

    <!-- فوتر -->
    <div class="setia-footer">
        <div class="setia-footer-info">
            <p>SETIA Content Generator - نسخه <?php echo esc_html($plugin_version); ?></p>
        </div>
        <div class="setia-footer-links">
            <a href="<?php echo admin_url('admin.php?page=setia-content-generator'); ?>" class="setia-btn setia-btn-sm setia-btn-secondary">
                <span class="dashicons dashicons-welcome-write-blog"></span>
                تولید محتوا
            </a>
            <a href="<?php echo admin_url('admin.php?page=setia-settings'); ?>" class="setia-btn setia-btn-sm setia-btn-secondary">
                <span class="dashicons dashicons-admin-settings"></span>
                تنظیمات
            </a>
        </div>
    </div>
</div>

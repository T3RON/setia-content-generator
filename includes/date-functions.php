<?php
/**
 * توابع تبدیل تاریخ برای افزونه SETIA
 * 
 * این فایل شامل توابعی برای تبدیل تاریخ میلادی به شمسی و بالعکس است
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * تبدیل تاریخ میلادی به شمسی
 * 
 * @param string $date تاریخ میلادی به فرمت Y-m-d
 * @return string تاریخ شمسی به فرمت Y/m/d
 */
function setia_gregorian_to_jalali($date) {
    if (empty($date)) {
        return '';
    }
    
    // اگر تاریخ به فرمت timestamp است
    if (is_numeric($date)) {
        $timestamp = $date;
    } else {
        // تبدیل تاریخ به timestamp
        $timestamp = strtotime($date);
    }
    
    // استفاده از تابع داخلی وردپرس اگر افزونه wp-jalali نصب است
    if (function_exists('gregorian_to_jalali')) {
        list($y, $m, $d) = gregorian_to_jalali(date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp));
        return sprintf('%04d/%02d/%02d', $y, $m, $d);
    }
    
    // پیاده‌سازی ساده تبدیل تاریخ
    $g_y = date('Y', $timestamp);
    $g_m = date('m', $timestamp);
    $g_d = date('d', $timestamp);
    
    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    
    // تصحیح برای سال کبیسه
    if (((($g_y % 4) == 0) && (($g_y % 100) != 0)) || (($g_y % 400) == 0)) {
        $g_days_in_month[1] = 29;
    }
    
    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;
    
    $g_day_no = 365 * $gy + floor(($gy + 3) / 4) - floor(($gy + 99) / 100) + floor(($gy + 399) / 400);
    
    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_days_in_month[$i];
    }
    
    $g_day_no += $gd;
    
    $j_day_no = $g_day_no - 79;
    
    $j_np = floor($j_day_no / 12053);
    $j_day_no %= 12053;
    
    $jy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461);
    
    $j_day_no %= 1461;
    
    if ($j_day_no >= 366) {
        $jy += floor(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }
    
    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
        $j_day_no -= $j_days_in_month[$i];
    }
    
    $jm = $i + 1;
    $jd = $j_day_no + 1;
    
    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
}

/**
 * تبدیل تاریخ شمسی به میلادی
 * 
 * @param string $jalali_date تاریخ شمسی به فرمت Y/m/d
 * @return string تاریخ میلادی به فرمت Y-m-d
 */
function setia_jalali_to_gregorian($jalali_date) {
    if (empty($jalali_date)) {
        return '';
    }
    
    // استخراج اجزای تاریخ شمسی
    $date_parts = explode('/', $jalali_date);
    if (count($date_parts) !== 3) {
        return $jalali_date; // فرمت نامعتبر، همان مقدار ورودی را برگردان
    }
    
    $jy = (int)$date_parts[0];
    $jm = (int)$date_parts[1];
    $jd = (int)$date_parts[2];
    
    // استفاده از تابع داخلی وردپرس اگر افزونه wp-jalali نصب است
    if (function_exists('jalali_to_gregorian')) {
        list($gy, $gm, $gd) = jalali_to_gregorian($jy, $jm, $jd);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }
    
    // پیاده‌سازی ساده تبدیل تاریخ
    $jy -= 979;
    $jm -= 1;
    $jd -= 1;
    
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    
    $j_day_no = 365 * $jy + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4);
    
    for ($i = 0; $i < $jm; ++$i) {
        $j_day_no += $j_days_in_month[$i];
    }
    
    $j_day_no += $jd;
    
    $g_day_no = $j_day_no + 79;
    
    $gy = 1600 + 400 * floor($g_day_no / 146097);
    $g_day_no %= 146097;
    
    $leap = 1;
    if ($g_day_no >= 36525) {
        $g_day_no--;
        $gy += 100 * floor($g_day_no / 36524);
        $g_day_no %= 36524;
        
        if ($g_day_no >= 365) {
            $g_day_no++;
        } else {
            $leap = 0;
        }
    }
    
    $gy += 4 * floor($g_day_no / 1461);
    $g_day_no %= 1461;
    
    if ($g_day_no >= 366) {
        $leap = 0;
        $g_day_no--;
        $gy += floor($g_day_no / 365);
        $g_day_no %= 365;
    }
    
    $g_days_in_month = array(31, 28 + $leap, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    
    for ($i = 0; $g_day_no >= $g_days_in_month[$i]; $i++) {
        $g_day_no -= $g_days_in_month[$i];
    }
    
    $gm = $i + 1;
    $gd = $g_day_no + 1;
    
    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

/**
 * فرمت تاریخ شمسی با نام ماه
 * 
 * @param string $date تاریخ میلادی به فرمت Y-m-d یا timestamp
 * @return string تاریخ شمسی با نام ماه (مثال: ۱۵ خرداد ۱۴۰۱)
 */
function setia_format_jalali_date($date) {
    if (empty($date)) {
        return '';
    }
    
    // تبدیل به تاریخ شمسی
    $jalali_date = setia_gregorian_to_jalali($date);
    $date_parts = explode('/', $jalali_date);
    
    if (count($date_parts) !== 3) {
        return $jalali_date;
    }
    
    $jy = (int)$date_parts[0];
    $jm = (int)$date_parts[1];
    $jd = (int)$date_parts[2];
    
    // نام ماه‌های شمسی
    $jalali_months = array(
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند'
    );
    
    // تبدیل اعداد به فارسی
    $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $day_persian = '';
    foreach (str_split($jd) as $digit) {
        $day_persian .= $persian_numbers[(int)$digit];
    }
    
    $year_persian = '';
    foreach (str_split($jy) as $digit) {
        $year_persian .= $persian_numbers[(int)$digit];
    }
    
    return $day_persian . ' ' . $jalali_months[$jm] . ' ' . $year_persian;
}

/**
 * محاسبه فاصله زمانی به صورت متنی
 * 
 * @param string $date تاریخ میلادی به فرمت Y-m-d H:i:s یا timestamp
 * @return string فاصله زمانی به صورت متنی (مثال: ۵ دقیقه پیش)
 */
function setia_time_elapsed_string($date) {
    if (empty($date)) {
        return '';
    }
    
    if (is_numeric($date)) {
        $timestamp = $date;
    } else {
        $timestamp = strtotime($date);
    }
    
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'چند لحظه پیش';
    }
    
    $intervals = array(
        31536000 => 'سال',
        2592000 => 'ماه',
        604800 => 'هفته',
        86400 => 'روز',
        3600 => 'ساعت',
        60 => 'دقیقه'
    );
    
    // تبدیل اعداد به فارسی
    $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    
    foreach ($intervals as $seconds => $name) {
        $count = floor($diff / $seconds);
        if ($count > 0) {
            // تبدیل عدد به فارسی
            $count_persian = '';
            foreach (str_split($count) as $digit) {
                $count_persian .= $persian_numbers[(int)$digit];
            }
            
            return $count_persian . ' ' . $name . ' پیش';
        }
    }
    
    return 'چند لحظه پیش';
} 
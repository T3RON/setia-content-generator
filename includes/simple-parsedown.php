<?php
/**
 * SETIA Simple Parsedown
 * کلاس ساده برای تبدیل مارک‌داون به HTML
 */

// امنیت: جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

class SETIA_Simple_Parsedown {
    public function text($text) {
        // تبدیل کاراکترهای خاص
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        
        // تبدیل عناوین
        $text = preg_replace('/^######\s+(.+?)$/m', '<h6>$1</h6>', $text);
        $text = preg_replace('/^#####\s+(.+?)$/m', '<h5>$1</h5>', $text);
        $text = preg_replace('/^####\s+(.+?)$/m', '<h4>$1</h4>', $text);
        $text = preg_replace('/^###\s+(.+?)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^##\s+(.+?)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^#\s+(.+?)$/m', '<h1>$1</h1>', $text);
        
        // تبدیل متن بولد و ایتالیک
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
        
        // تبدیل لیست‌ها
        $text = preg_replace('/^\*\s+(.+?)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/^-\s+(.+?)$/m', '<li>$1</li>', $text);
        
        // تبدیل لیست‌های ترتیبی
        $text = preg_replace('/^\d+\.\s+(.+?)$/m', '<li>$1</li>', $text);
        
        // قرار دادن آیتم‌های لیست در تگ ul/ol
        $text = preg_replace('/(<li>.+?<\/li>)+/s', '<ul>$0</ul>', $text);
        
        // تبدیل نقل قول‌ها
        $text = preg_replace('/^>\s+(.+?)$/m', '<blockquote>$1</blockquote>', $text);
        
        // تبدیل لینک‌ها
        $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text);
        
        // تبدیل خطوط عادی به پاراگراف
        $text = preg_replace('/^([^<].+?)$/m', '<p>$1</p>', $text);
        
        // حذف پاراگراف‌های خالی
        $text = preg_replace('/<p><\/p>/', '', $text);
        
        return $text;
    }
} 
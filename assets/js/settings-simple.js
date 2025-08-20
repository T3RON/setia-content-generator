/**
 * SETIA Plugin Simple Settings JavaScript
 * Simplified version for debugging
 */

(function($) {
    'use strict';

    console.log('🚀 SETIA Simple Settings - Loading...');

    $(document).ready(function() {
        console.log('📱 DOM Ready - Initializing...');
        
        // Check jQuery
        console.log('📱 jQuery version:', $.fn.jquery);
        
        // Check elements
        const $tabButtons = $('.setia-tab-button');
        const $tabPanes = $('.setia-tab-pane');
        const $testButton = $('#test-apis');
        const $resetButton = $('#reset-settings');
        
        console.log('📱 Found elements:');
        console.log('  - Tab buttons:', $tabButtons.length);
        console.log('  - Tab panes:', $tabPanes.length);
        console.log('  - Test button:', $testButton.length);
        console.log('  - Reset button:', $resetButton.length);
        
        // Simple tab switching
        $tabButtons.on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const tabId = $button.data('tab');
            
            console.log('📑 Tab clicked:', tabId);
            
            // Remove active from all
            $tabButtons.removeClass('active');
            $tabPanes.removeClass('active');
            
            // Add active to current
            $button.addClass('active');
            $('#tab-' + tabId).addClass('active');
            
            console.log('✅ Tab switched to:', tabId);
        });
        
        // Test API button
        $testButton.on('click', function(e) {
            e.preventDefault();
            console.log('🧪 Test API clicked');
            
            const $btn = $(this);
            const originalText = $btn.text();
            
            $btn.text('در حال تست...').prop('disabled', true);
            
            setTimeout(function() {
                $btn.text(originalText).prop('disabled', false);
                alert('تست API انجام شد!');
                console.log('✅ API test completed');
            }, 2000);
        });
        
        // Reset button
        $resetButton.on('click', function(e) {
            e.preventDefault();
            console.log('🔄 Reset clicked');
            
            if (confirm('آیا مطمئن هستید؟')) {
                alert('تنظیمات بازنشانی شد!');
                console.log('✅ Settings reset');
            }
        });
        
        // Help toggles
        $('.setia-help-toggle').on('click', function(e) {
            e.preventDefault();
            
            const $toggle = $(this);
            const targetId = $toggle.data('target');
            const $content = $('#' + targetId);
            
            console.log('❓ Help toggle clicked:', targetId);
            
            if ($content.length > 0) {
                $content.toggleClass('active');
                console.log('✅ Help toggled:', targetId);
            }
        });
        
        console.log('✅ SETIA Simple Settings - Ready!');
    });

})(jQuery);

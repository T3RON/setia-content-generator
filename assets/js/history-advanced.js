/**
 * SETIA Content Generator - History Page JavaScript
 * اسکریپت صفحه تاریخچه افزونه با استایل مدرن
 */

jQuery(document).ready(function($) {
    'use strict';

    // تنظیم متغیرهای عمومی
    const historyData = {
        currentPage: 1,
        itemsPerPage: 20,
        totalItems: 0,
        totalPages: 0,
        selectedItems: [],
        filters: {}
    };

    // بارگذاری اولیه داده‌ها
    loadHistoryData();

    // رویداد کلیک روی دکمه بروزرسانی
    $('#refresh-data').on('click', function() {
        loadHistoryData();
    });

    // رویداد نمایش/مخفی کردن فیلترها
    $('#toggle-filters').on('click', function() {
        $('#filters-content').toggleClass('setia-hidden');
    });

    // رویداد اعمال فیلترها
    $('#apply-filters').on('click', function() {
        historyData.filters = {
            keyword: $('#filter-keyword').val(),
            date: $('#filter-date').val(),
            type: $('#filter-type').val(),
            status: $('#filter-status').val()
        };
        historyData.currentPage = 1;
        loadHistoryData();
    });

    // رویداد پاک کردن فیلترها
    $('#reset-filters').on('click', function() {
        $('#filter-keyword').val('');
        $('#filter-date').val('');
        $('#filter-type').val('');
        $('#filter-status').val('');
        historyData.filters = {};
        loadHistoryData();
    });

    // رویداد انتخاب همه آیتم‌ها
    $('#select-all').on('click', function() {
        const isChecked = $(this).prop('checked');
        $('.item-checkbox').prop('checked', isChecked);
        
        if (isChecked) {
            historyData.selectedItems = [];
            $('.item-checkbox').each(function() {
                historyData.selectedItems.push($(this).val());
            });
        } else {
            historyData.selectedItems = [];
        }
        
        updateBulkActionButton();
    });

    // رویداد اعمال اقدامات گروهی
    $('#apply-bulk-action').on('click', function() {
        const action = $('#bulk-action').val();
        
        if (!action) {
            showAlert('warning', 'لطفاً یک اقدام را انتخاب کنید');
            return;
        }
        
        if (historyData.selectedItems.length === 0) {
            showAlert('warning', 'لطفاً حداقل یک مورد را انتخاب کنید');
            return;
        }
        
        switch (action) {
            case 'delete':
                confirmBulkDelete();
                break;
                
            case 'export':
                exportSelectedItems();
                break;
                
            case 'publish':
                publishSelectedItems();
                break;
        }
    });

    // رویداد کلیک روی دکمه خروجی Excel
    $('#export-excel').on('click', function() {
        exportAllItems();
    });

    // رویداد کلیک روی دکمه تایید حذف
    $('#confirm-delete').on('click', function() {
        const itemId = $(this).data('item-id');
        
        if (itemId) {
            // حذف یک آیتم
            deleteItem(itemId);
        } else {
            // حذف گروهی
            bulkDeleteItems();
        }
        
        closeModal('delete-modal');
    });

    // رویداد بستن مدال‌ها
    $('.setia-modal-close').on('click', function() {
        $(this).closest('.setia-modal-overlay').removeClass('active');
    });

    // رویداد کلیک روی پاگینیشن
    $(document).on('click', '.setia-pagination-item', function() {
        if ($(this).hasClass('active')) return;
        
        historyData.currentPage = parseInt($(this).data('page'));
        loadHistoryData();
    });

    // رویداد کلیک روی دکمه حذف
    $(document).on('click', '.delete-item', function(e) {
        e.preventDefault();
        const itemId = $(this).data('id');
        confirmDelete(itemId);
    });

    // رویداد کلیک روی دکمه مشاهده
    $(document).on('click', '.view-item', function(e) {
        e.preventDefault();
        const itemId = $(this).data('id');
        viewItem(itemId);
    });

    // رویداد کلیک روی دکمه ویرایش در مدال
    $('#modal-edit').on('click', function() {
        const itemId = $(this).data('item-id');
        if (itemId) {
            window.location.href = setiaHistory.adminUrl + 'post.php?post=' + itemId + '&action=edit';
        }
    });

    // رویداد کلیک روی دکمه انتشار در مدال
    $('#modal-publish').on('click', function() {
        const itemId = $(this).data('item-id');
        if (itemId) {
            publishItem(itemId);
        }
    });

    // رویداد تغییر وضعیت چک‌باکس آیتم‌ها
    $(document).on('change', '.item-checkbox', function() {
        const itemId = $(this).val();
        
        if ($(this).prop('checked')) {
            if (!historyData.selectedItems.includes(itemId)) {
                historyData.selectedItems.push(itemId);
            }
        } else {
            const index = historyData.selectedItems.indexOf(itemId);
            if (index > -1) {
                historyData.selectedItems.splice(index, 1);
            }
            
            $('#select-all').prop('checked', false);
        }
        
        updateBulkActionButton();
    });

    // بارگذاری داده‌های تاریخچه
    function loadHistoryData() {
        showLoading();
        
        $.ajax({
            url: setiaHistory.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_get_history',
                nonce: setiaHistory.nonce,
                page: historyData.currentPage,
                per_page: historyData.itemsPerPage,
                filters: historyData.filters
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    updateHistoryTable(response.data.items);
                    updatePagination(response.data.total, response.data.pages);
                    updateStats(response.data.stats);
                    
                    historyData.totalItems = response.data.total;
                    historyData.totalPages = response.data.pages;
                    historyData.selectedItems = [];
                    
                    $('#select-all').prop('checked', false);
                    updateBulkActionButton();
                } else {
                    showAlert('danger', response.data.message || 'خطا در بارگذاری داده‌ها');
                }
            },
            error: function() {
                hideLoading();
                showAlert('danger', 'خطا در ارتباط با سرور');
            }
        });
    }

    // بروزرسانی جدول تاریخچه
    function updateHistoryTable(items) {
        const $tableBody = $('#history-content');
        $tableBody.empty();
        
        if (items.length === 0) {
            $tableBody.html('<tr><td colspan="7" class="setia-empty">هیچ موردی یافت نشد</td></tr>');
            return;
        }
        
        items.forEach(function(item) {
            const row = `
                <tr>
                    <td>
                        <input type="checkbox" class="item-checkbox" value="${item.id}">
                    </td>
                    <td>${item.id}</td>
                    <td>${item.title}</td>
                    <td>${getTypeLabel(item.type)}</td>
                    <td>${item.date}</td>
                    <td>${getStatusLabel(item.status)}</td>
                    <td>
                        <button class="setia-btn setia-btn-sm setia-btn-secondary view-item" data-id="${item.id}">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button class="setia-btn setia-btn-sm setia-btn-danger delete-item" data-id="${item.id}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
            `;
            
            $tableBody.append(row);
        });
    }

    // بروزرسانی پاگینیشن
    function updatePagination(total, pages) {
        const $pagination = $('#history-pagination');
        $pagination.empty();
        
        if (pages <= 1) {
            return;
        }
        
        // دکمه قبلی
        if (historyData.currentPage > 1) {
            $pagination.append(`
                <div class="setia-pagination-item" data-page="${historyData.currentPage - 1}">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </div>
            `);
        }
        
        // شماره صفحات
        let startPage = Math.max(1, historyData.currentPage - 2);
        let endPage = Math.min(pages, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === historyData.currentPage ? 'active' : '';
            $pagination.append(`
                <div class="setia-pagination-item ${activeClass}" data-page="${i}">
                    ${i}
                </div>
            `);
        }
        
        // دکمه بعدی
        if (historyData.currentPage < pages) {
            $pagination.append(`
                <div class="setia-pagination-item" data-page="${historyData.currentPage + 1}">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </div>
            `);
        }
    }

    // بروزرسانی آمار
    function updateStats(stats) {
        $('#total-content').text(stats.total || 0);
        $('#published-content').text(stats.published || 0);
        $('#draft-content').text(stats.draft || 0);
        $('#product-content').text(stats.product || 0);
    }

    // نمایش تایید حذف
    function confirmDelete(itemId) {
        $('#confirm-delete').data('item-id', itemId);
        openModal('delete-modal');
    }

    // نمایش تایید حذف گروهی
    function confirmBulkDelete() {
        $('#confirm-delete').data('item-id', null);
        openModal('delete-modal');
    }

    // حذف یک آیتم
    function deleteItem(itemId) {
        $.ajax({
            url: setiaHistory.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_delete_history_item',
                nonce: setiaHistory.nonce,
                id: itemId
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'آیتم با موفقیت حذف شد');
                    loadHistoryData();
                } else {
                    showAlert('danger', response.data.message || 'خطا در حذف آیتم');
                }
            },
            error: function() {
                showAlert('danger', 'خطا در ارتباط با سرور');
            }
        });
    }

    // حذف گروهی آیتم‌ها
    function bulkDeleteItems() {
        $.ajax({
            url: setiaHistory.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_bulk_delete_history',
                nonce: setiaHistory.nonce,
                ids: historyData.selectedItems
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'آیتم‌های انتخاب شده با موفقیت حذف شدند');
                    loadHistoryData();
                } else {
                    showAlert('danger', response.data.message || 'خطا در حذف آیتم‌ها');
                }
            },
            error: function() {
                showAlert('danger', 'خطا در ارتباط با سرور');
            }
        });
    }

    // مشاهده جزئیات یک آیتم
    function viewItem(itemId) {
        $.ajax({
            url: setiaHistory.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_get_history_item',
                nonce: setiaHistory.nonce,
                id: itemId
            },
            success: function(response) {
                if (response.success) {
                    const item = response.data;
                    
                    $('#modal-title').text(item.title);
                    $('#modal-content').html(item.content);
                    
                    $('#modal-edit').data('item-id', item.post_id || 0);
                    $('#modal-publish').data('item-id', item.id);
                    
                    if (item.post_id) {
                        $('#modal-edit').show();
                    } else {
                        $('#modal-edit').hide();
                    }
                    
                    if (item.status === 'published') {
                        $('#modal-publish').hide();
                    } else {
                        $('#modal-publish').show();
                    }
                    
                    openModal('content-modal');
                } else {
                    showAlert('danger', response.data.message || 'خطا در بارگذاری اطلاعات آیتم');
                }
            },
            error: function() {
                showAlert('danger', 'خطا در ارتباط با سرور');
            }
        });
    }

    // انتشار یک آیتم
    function publishItem(itemId) {
        $.ajax({
            url: setiaHistory.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_publish_history_item',
                nonce: setiaHistory.nonce,
                id: itemId
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'آیتم با موفقیت منتشر شد');
                    closeModal('content-modal');
                    loadHistoryData();
                } else {
                    showAlert('danger', response.data.message || 'خطا در انتشار آیتم');
                }
            },
            error: function() {
                showAlert('danger', 'خطا در ارتباط با سرور');
            }
        });
    }

    // انتشار گروهی آیتم‌ها
    function publishSelectedItems() {
        $.ajax({
            url: setiaHistory.ajaxUrl,
            type: 'POST',
            data: {
                action: 'setia_bulk_publish_history',
                nonce: setiaHistory.nonce,
                ids: historyData.selectedItems
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'آیتم‌های انتخاب شده با موفقیت منتشر شدند');
                    loadHistoryData();
                } else {
                    showAlert('danger', response.data.message || 'خطا در انتشار آیتم‌ها');
                }
            },
            error: function() {
                showAlert('danger', 'خطا در ارتباط با سرور');
            }
        });
    }

    // خروجی گرفتن از آیتم‌های انتخاب شده
    function exportSelectedItems() {
        window.location.href = setiaHistory.ajaxUrl + '?action=setia_export_history&nonce=' + setiaHistory.nonce + '&ids=' + historyData.selectedItems.join(',');
    }

    // خروجی گرفتن از همه آیتم‌ها
    function exportAllItems() {
        window.location.href = setiaHistory.ajaxUrl + '?action=setia_export_history&nonce=' + setiaHistory.nonce;
    }

    // بروزرسانی وضعیت دکمه اقدامات گروهی
    function updateBulkActionButton() {
        if (historyData.selectedItems.length > 0) {
            $('#apply-bulk-action').prop('disabled', false);
        } else {
            $('#apply-bulk-action').prop('disabled', true);
        }
    }

    // نمایش اعلان
    function showAlert(type, message) {
        // حذف اعلان‌های قبلی
        $('.setia-alert').remove();
        
        // ایجاد اعلان جدید
        const alert = $('<div class="setia-alert setia-alert-' + type + '">' + message + '</div>');
        $('#setia-alerts').append(alert);
        
        // حذف اعلان پس از چند ثانیه
        setTimeout(function() {
            alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // نمایش وضعیت بارگذاری
    function showLoading() {
        $('#history-content').html('<tr><td colspan="7" class="setia-loading">در حال بارگذاری...</td></tr>');
    }

    // مخفی کردن وضعیت بارگذاری
    function hideLoading() {
        // این تابع خالی است زیرا محتوای جدول با داده‌های جدید جایگزین می‌شود
    }

    // باز کردن مدال
    function openModal(modalId) {
        $('#' + modalId).addClass('active');
    }

    // بستن مدال
    function closeModal(modalId) {
        $('#' + modalId).removeClass('active');
    }

    // دریافت برچسب نوع محتوا
    function getTypeLabel(type) {
        const types = {
            'article': 'مقاله',
            'product': 'محصول',
            'image': 'تصویر',
            'seo': 'محتوای سئو',
            'blog': 'وبلاگ',
            'custom': 'سفارشی'
        };
        
        return types[type] || type;
    }

    // دریافت برچسب وضعیت
    function getStatusLabel(status) {
        const statuses = {
            'published': '<span class="setia-status setia-status-success">منتشر شده</span>',
            'draft': '<span class="setia-status setia-status-warning">پیش‌نویس</span>',
            'pending': '<span class="setia-status setia-status-info">در انتظار بررسی</span>'
        };
        
        return statuses[status] || status;
    }
});

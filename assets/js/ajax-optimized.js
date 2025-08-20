/**
 * بهینه‌سازی شده سیستم AJAX برای پلاگین SETIA
 * نسخه 2.0.0 - بهبود یافته با مدیریت خطا، retry mechanism و performance optimization
 */

(function($) {
    'use strict';

    // کلاس مدیریت AJAX بهینه‌سازی شده
    class SetiaAjaxManager {
        constructor(config = {}) {
            this.config = {
                baseUrl: config.baseUrl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
                timeout: config.timeout || 30000,
                retryCount: config.retryCount || 3,
                retryDelay: config.retryDelay || 1000,
                enableCache: config.enableCache !== false,
                enableQueue: config.enableQueue !== false,
                maxConcurrent: config.maxConcurrent || 5,
                enableDebug: config.enableDebug || false,
                ...config
            };

            this.cache = new Map();
            this.requestQueue = [];
            this.activeRequests = new Set();
            this.requestId = 0;
            this.metrics = {
                totalRequests: 0,
                successfulRequests: 0,
                failedRequests: 0,
                cachedRequests: 0,
                averageResponseTime: 0,
                responseTimes: []
            };

            this.init();
        }

        init() {
            this.log('SETIA AJAX Manager initialized', this.config);
            
            // مدیریت صف درخواست‌ها
            if (this.config.enableQueue) {
                this.processQueue();
            }

            // پاک کردن کش هر 30 دقیقه
            if (this.config.enableCache) {
                setInterval(() => this.clearExpiredCache(), 30 * 60 * 1000);
            }
        }

        log(message, data = null) {
            if (this.config.enableDebug) {
                console.log(`[SETIA AJAX] ${message}`, data);
            }
        }

        // ایجاد کلید کش
        createCacheKey(action, data) {
            const sortedData = Object.keys(data || {})
                .sort()
                .reduce((result, key) => {
                    if (key !== 'nonce' && key !== '_wpnonce') {
                        result[key] = data[key];
                    }
                    return result;
                }, {});
            
            return `${action}_${JSON.stringify(sortedData)}`;
        }

        // بررسی کش
        getFromCache(cacheKey) {
            if (!this.config.enableCache) return null;
            
            const cached = this.cache.get(cacheKey);
            if (cached && Date.now() - cached.timestamp < 5 * 60 * 1000) { // 5 دقیقه
                this.metrics.cachedRequests++;
                this.log('Cache hit', cacheKey);
                return cached.data;
            }
            
            if (cached) {
                this.cache.delete(cacheKey);
            }
            
            return null;
        }

        // ذخیره در کش
        setCache(cacheKey, data) {
            if (!this.config.enableCache) return;
            
            this.cache.set(cacheKey, {
                data: data,
                timestamp: Date.now()
            });
            
            this.log('Cache set', cacheKey);
        }

        // پاک کردن کش منقضی شده
        clearExpiredCache() {
            const now = Date.now();
            const expireTime = 5 * 60 * 1000; // 5 دقیقه
            
            for (const [key, value] of this.cache.entries()) {
                if (now - value.timestamp > expireTime) {
                    this.cache.delete(key);
                }
            }
            
            this.log('Expired cache cleared');
        }

        // اضافه کردن درخواست به صف
        addToQueue(requestConfig) {
            if (!this.config.enableQueue) {
                return this.executeRequest(requestConfig);
            }

            return new Promise((resolve, reject) => {
                this.requestQueue.push({
                    ...requestConfig,
                    resolve,
                    reject,
                    id: ++this.requestId
                });
                
                this.processQueue();
            });
        }

        // پردازش صف درخواست‌ها
        processQueue() {
            if (this.activeRequests.size >= this.config.maxConcurrent || this.requestQueue.length === 0) {
                return;
            }

            const request = this.requestQueue.shift();
            this.activeRequests.add(request.id);

            this.executeRequest(request)
                .then(request.resolve)
                .catch(request.reject)
                .finally(() => {
                    this.activeRequests.delete(request.id);
                    setTimeout(() => this.processQueue(), 100);
                });
        }

        // اجرای درخواست با retry mechanism
        async executeRequest(config, attempt = 1) {
            const startTime = performance.now();
            this.metrics.totalRequests++;

            try {
                // بررسی کش
                const cacheKey = this.createCacheKey(config.action, config.data);
                const cachedResult = this.getFromCache(cacheKey);
                
                if (cachedResult && config.useCache !== false) {
                    return cachedResult;
                }

                this.log(`Executing request (attempt ${attempt})`, config);

                // تنظیم پیش‌فرض‌ها
                const requestConfig = {
                    url: this.config.baseUrl,
                    type: 'POST',
                    timeout: this.config.timeout,
                    dataType: 'json',
                    data: {
                        action: config.action,
                        nonce: config.nonce || 
                               (config.data && config.data.setia_nonce) ||
                               (typeof setiaAjax !== 'undefined' ? setiaAjax.nonce : ''),
                        ...config.data
                    },
                    ...config.ajaxOptions
                };

                const response = await $.ajax(requestConfig);
                
                const endTime = performance.now();
                const responseTime = endTime - startTime;
                
                // به‌روزرسانی آمار
                this.metrics.successfulRequests++;
                this.metrics.responseTimes.push(responseTime);
                this.updateAverageResponseTime();

                // ذخیره در کش
                if (response.success && config.useCache !== false) {
                    this.setCache(cacheKey, response);
                }

                this.log(`Request successful (${Math.round(responseTime)}ms)`, response);
                return response;

            } catch (error) {
                const endTime = performance.now();
                const responseTime = endTime - startTime;
                
                this.log(`Request failed (attempt ${attempt}, ${Math.round(responseTime)}ms)`, error);

                // تلاش مجدد در صورت خطا
                if (attempt < this.config.retryCount && this.shouldRetry(error)) {
                    this.log(`Retrying request in ${this.config.retryDelay}ms...`);
                    
                    await new Promise(resolve => setTimeout(resolve, this.config.retryDelay * attempt));
                    return this.executeRequest(config, attempt + 1);
                }

                this.metrics.failedRequests++;
                throw this.formatError(error);
            }
        }

        // بررسی اینکه آیا باید تلاش مجدد کرد
        shouldRetry(error) {
            const retryableErrors = ['timeout', 'error', 'abort'];
            const retryableStatuses = [0, 500, 502, 503, 504];
            
            return retryableErrors.includes(error.statusText?.toLowerCase()) ||
                   retryableStatuses.includes(error.status) ||
                   error.readyState === 0;
        }

        // فرمت کردن خطا
        formatError(error) {
            let message = 'خطای نامشخص در ارتباط با سرور';
            
            if (error.status === 0) {
                message = 'خطا در اتصال به سرور';
            } else if (error.status === 403) {
                message = 'دسترسی غیرمجاز - لطفا صفحه را رفرش کنید';
            } else if (error.status === 404) {
                message = 'آدرس درخواست یافت نشد';
            } else if (error.status >= 500) {
                message = 'خطای داخلی سرور';
            } else if (error.statusText === 'timeout') {
                message = 'زمان انتظار تمام شد';
            } else if (error.responseJSON?.data?.message) {
                message = error.responseJSON.data.message;
            } else if (error.responseText) {
                try {
                    const parsed = JSON.parse(error.responseText);
                    message = parsed.data?.message || parsed.message || message;
                } catch (e) {
                    // اگر پاسخ JSON نبود، همان پیام پیش‌فرض را نگه دار
                }
            }

            return {
                message,
                status: error.status,
                statusText: error.statusText,
                originalError: error
            };
        }

        // به‌روزرسانی میانگین زمان پاسخ
        updateAverageResponseTime() {
            if (this.metrics.responseTimes.length > 0) {
                const sum = this.metrics.responseTimes.reduce((a, b) => a + b, 0);
                this.metrics.averageResponseTime = sum / this.metrics.responseTimes.length;
            }
        }

        // متد اصلی برای ارسال درخواست
        request(action, data = {}, options = {}) {
            const config = {
                action,
                data,
                nonce: options.nonce,
                useCache: options.useCache,
                ajaxOptions: options.ajaxOptions
            };

            if (this.config.enableQueue) {
                return this.addToQueue(config);
            } else {
                return this.executeRequest(config);
            }
        }

        // متدهای کمکی برای اکشن‌های مختلف
        generateContent(data, options = {}) {
            return this.request('setia_generate_content', data, {
                useCache: false,
                ...options
            });
        }

        testConnection(data = {}, options = {}) {
            return this.request('setia_test_connection', data, options);
        }

        getHistory(data = {}, options = {}) {
            return this.request('setia_get_history_data', data, options);
        }

        publishContent(data, options = {}) {
            return this.request('setia_publish_content', data, {
                useCache: false,
                ...options
            });
        }

        // دریافت آمار عملکرد
        getMetrics() {
            return {
                ...this.metrics,
                cacheSize: this.cache.size,
                queueSize: this.requestQueue.length,
                activeRequests: this.activeRequests.size
            };
        }

        // ریست کردن آمار
        resetMetrics() {
            this.metrics = {
                totalRequests: 0,
                successfulRequests: 0,
                failedRequests: 0,
                cachedRequests: 0,
                averageResponseTime: 0,
                responseTimes: []
            };
        }

        // پاک کردن کش
        clearCache() {
            this.cache.clear();
            this.log('Cache cleared');
        }
    }

    // انتقال به بخش کاربر و تعریف متغیر جهانی
    window.SetiaAjax = new SetiaAjaxManager({
        baseUrl: typeof setiaAjax !== 'undefined' ? setiaAjax.ajaxUrl : ajaxurl,
        nonce: typeof setiaAjax !== 'undefined' ? setiaAjax.nonce : '',
        enableDebug: false,
        retryCount: 2,
        enableQueue: true
    });

    // متدهای کمکی سراسری
    window.setiaRequest = function(action, data, options) {
        return window.SetiaAjax.request(action, data, options);
    };

    window.setiaGenerateContent = function(data, options) {
        return window.SetiaAjax.generateContent(data, options);
    };

    window.setiaTestConnection = function(data, options) {
        return window.SetiaAjax.testConnection(data, options);
    };

    // نمایش آمار در کنسول (فقط در حالت debug)
    if (typeof setiaParams !== 'undefined' && setiaParams.debug) {
        setInterval(() => {
            console.log('[SETIA AJAX Metrics]', window.SetiaAjax.getMetrics());
        }, 30000); // هر 30 ثانیه
    }

})(jQuery);

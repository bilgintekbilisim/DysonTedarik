/**
 * Çerez tercihi. Zorunlu çerezler dışında hiçbir şey onaydan önce yüklenmez:
 * Analytics / Tag Manager betikleri yalnızca "Kabul et" sonrası eklenir.
 */
const KEY = 'dt_cookie_consent';

export function cookieConsent({ ga4 = null, gtm = null } = {}) {
    return {
        visible: false,

        init() {
            const choice = this.read();

            if (choice === null) {
                this.visible = true;
            } else if (choice === 'accepted') {
                this.loadAnalytics();
            }

            window.addEventListener('open-cookie-settings', () => (this.visible = true));
        },

        read() {
            try {
                return window.localStorage.getItem(KEY);
            } catch {
                return null;
            }
        },

        save(value) {
            try {
                window.localStorage.setItem(KEY, value);
            } catch {
                /* gizli sekmede depolama kapalı olabilir */
            }
        },

        accept() {
            this.save('accepted');
            this.visible = false;
            this.loadAnalytics();
        },

        reject() {
            this.save('rejected');
            this.visible = false;
        },

        loadAnalytics() {
            if (window.__dtAnalyticsLoaded) return;
            window.__dtAnalyticsLoaded = true;

            if (gtm) {
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
                this.inject(`https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(gtm)}`);
            }

            if (ga4) {
                window.dataLayer = window.dataLayer || [];
                window.gtag = function () {
                    window.dataLayer.push(arguments);
                };
                window.gtag('js', new Date());
                window.gtag('config', ga4, { anonymize_ip: true });
                this.inject(`https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ga4)}`);
            }
        },

        inject(src) {
            const script = document.createElement('script');
            script.async = true;
            script.src = src;
            document.head.appendChild(script);
        },
    };
}

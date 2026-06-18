window._ = require('lodash');

/**
 * UK timezone policy (app-wide).
 * Times are stored/sent in UTC; the whole app DISPLAYS them in UK time
 * (Europe/London — auto GMT in winter / BST in summer). We default every
 * Date#toLocale* call to that zone unless a caller passes its own `timeZone`.
 * Number#toLocaleString is a different method and is NOT affected (so money/
 * quantity formatting is untouched).
 */
(function () {
    const UK_TIMEZONE = 'Europe/London';
    ['toLocaleString', 'toLocaleDateString', 'toLocaleTimeString'].forEach((name) => {
        const original = Date.prototype[name];
        Date.prototype[name] = function (locales, options) {
            // Invalid Date + a timeZone option throws a RangeError on Safari
            // (Chrome quietly returns "Invalid Date"). Forcing our UK timeZone
            // onto an invalid date would therefore crash every page that renders
            // one. Skip the patch for invalid dates and let the original method
            // handle them exactly as the browser normally would.
            if (isNaN(this.getTime())) {
                return original.call(this, locales, options);
            }
            const opts = options ? { ...options } : {};
            if (opts.timeZone == null) opts.timeZone = UK_TIMEZONE;
            try {
                return original.call(this, locales, opts);
            } catch (e) {
                // Last-resort safety net: never let date formatting crash a render.
                return original.call(this, locales, options);
            }
        };
    });
})();

try {
    require('bootstrap');
} catch (e) {}

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (_csrfToken) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = _csrfToken;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });

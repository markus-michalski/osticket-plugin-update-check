/**
 * Update Badges - JavaScript
 *
 * Reads update data from window.__pluginUpdates and injects
 * badges into the plugin list table rows.
 *
 * @package    osTicket\Plugins\UpdateCheck
 * @author     Markus Michalski
 * @version    0.1.0
 */

(function (window, document) {
    'use strict';

    /**
     * Validate that a URL uses http(s) protocol.
     * Prevents javascript: and data: URI injection.
     */
    function isValidHttpUrl(url) {
        try {
            var parsed = new URL(url);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch (e) {
            return false;
        }
    }

    /**
     * Inject update badges into plugin list rows.
     *
     * Matches rows by checkbox value (plugin ID) and appends
     * a badge after the version text in the 3rd column.
     */
    function injectBadges() {
        var updates = window.__pluginUpdates;
        if (!updates || typeof updates !== 'object') {
            return;
        }

        // Find all plugin row checkboxes
        var checkboxes = document.querySelectorAll('input[name="ids[]"]');
        if (!checkboxes.length) {
            return;
        }

        checkboxes.forEach(function (checkbox) {
            var pluginId = checkbox.value;
            var update = updates[pluginId];

            if (!update) {
                return;
            }

            // Find the version cell (3rd <td> in the row)
            var row = checkbox.closest('tr');
            if (!row) {
                return;
            }

            var cells = row.querySelectorAll('td');
            if (cells.length < 3) {
                return;
            }

            var versionCell = cells[2]; // 0=checkbox, 1=name, 2=version

            // Prevent double injection
            if (versionCell.querySelector('.plugin-update-badge')) {
                return;
            }

            // Validate URL before injecting (XSS prevention)
            if (!update.url || !isValidHttpUrl(update.url)) {
                return;
            }

            // Create badge element
            var badge = document.createElement('a');
            badge.className = 'plugin-update-badge';
            badge.href = update.url;
            badge.target = '_blank';
            badge.rel = 'noopener noreferrer';
            badge.title = 'Update available: v' + update.latest;
            badge.textContent = update.latest;

            versionCell.appendChild(badge);
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectBadges);
    } else {
        injectBadges();
    }

    // PJAX support (osTicket uses jQuery PJAX for navigation)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('pjax:end', function () {
            injectBadges();
        });
    }

})(window, document);

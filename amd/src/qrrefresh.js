// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Modal form to manage booking option tags (botags).
 *
 * @module     mod_bmooduell
 * @copyright  2025 Wunderbyte GmbH
 * @author     Christian Badusch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


export const init = () => {
    const modal = document.getElementById('mooduellLoginQrModal');
    if (!modal) {
        return;
    }

    const qrImageContainer = modal.querySelector('[data-qr-expiring="true"]');
    if (!qrImageContainer) {
        return;
    }

    const qrImage = qrImageContainer.querySelector('img');
    const reloadContainer = qrImageContainer.querySelector('.reloadcontainer');
    const reloadButton = qrImageContainer.querySelector('.btn-reload');

    if (reloadButton) {
        reloadButton.addEventListener('click', () => {
            location.reload();
        });
    }

    let timeoutId;
    const expiresAt = Date.now() + 300000;

    const resetVisualState = () => {
        if (qrImage) {
            qrImage.style.filter = '';
            qrImage.style.opacity = '';
        }
        if (reloadContainer) {
            reloadContainer.style.display = 'none';
        }
    };

    const markExpired = () => {
        if (qrImage) {
            qrImage.style.filter = 'grayscale(100%)';
            qrImage.style.opacity = '0.1';
        }
        if (reloadContainer) {
            reloadContainer.style.display = 'block';
        }
    };

    const syncVisualState = () => {
        if (Date.now() >= expiresAt) {
            markExpired();
            return;
        }
        resetVisualState();
    };

    const scheduleExpiryTimer = () => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        const remaining = expiresAt - Date.now();
        if (remaining <= 0) {
            markExpired();
            return;
        }
        timeoutId = setTimeout(markExpired, remaining);
    };

    const onModalShown = () => {
        syncVisualState();
    };

    // Bootstrap 5 emits native events; Bootstrap 4 emits jQuery events.
    modal.addEventListener('shown.bs.modal', onModalShown);
    if (window.jQuery) {
        window.jQuery(modal).on('shown.bs.modal', onModalShown);
    }

    syncVisualState();
    scheduleExpiryTimer();

};


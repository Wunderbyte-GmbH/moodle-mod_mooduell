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
    const qrImageContainer = document.querySelector('#exampleModal .modal-body .qrcol'); // Adjusted selector for QR code container.
    let timeoutId; // Variable to store the timeout ID.

    // Function to handle the start time click event.
    const handleStartTimeClick = () => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        // Start a timeout of 5 minutes (300000 milliseconds).
        timeoutId = setTimeout(() => {
            // Check if the QR container exists.
            if (qrImageContainer) {
                // Hide the existing QR code image.
                const qrImage = qrImageContainer.querySelector('img');
                const reloadcontainer = qrImageContainer.querySelector('.reloadcontainer');
                if (qrImage) {
                    qrImage.style.filter = 'grayscale(100%)';
                    qrImage.style.opacity = '0.1';
                }
                if (reloadcontainer) {
                    reloadcontainer.style.display = 'block';
                }

                // Add click event to the reload button.
                const reloadButton = qrImageContainer.querySelector('.btn-reload');
                reloadButton.addEventListener('click', () => {
                    location.reload();
                });
            }
        }, 300000); // 300000 ms = 5 minutes.
    };

    handleStartTimeClick();

};


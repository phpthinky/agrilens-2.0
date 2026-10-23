/**
 * Colorimeter camera picker — shared by the pH and N/P/K test pages.
 *
 * The colorimetric device is a capture box with its own USB webcam. On a
 * laptop the OS lets the user choose a default camera, but a mobile
 * browser just opens whatever it likes (usually the front camera), so
 * this picks the camera explicitly:
 *
 *   1. A USB / OTG webcam, if one is detected — always preferred.
 *   2. Otherwise the user is told no external camera was found and asked
 *      to choose: Back / Front on a phone, or any listed camera on a
 *      desktop. They can also plug the box in and re-scan.
 *
 * If a USB camera is plugged in while the page is open, the stream
 * switches to it automatically.
 *
 * Usage:
 *   ColorimeterCamera.start({ width: 320, height: 240, onStream(stream) {...} });
 *   ColorimeterCamera.stop();
 *   ColorimeterCamera.choose();   // re-open the picker ("Switch camera")
 */
(function () {
    'use strict';

    // Labels the browser gives built-in phone/laptop cameras. Android
    // Chrome reports "camera2 0, facing back" / "camera2 1, facing front";
    // iOS reports "Back Camera" / "Front Camera"; laptops "Integrated
    // Camera", "FaceTime HD Camera", etc.
    const BUILT_IN_LABEL = /(facing (front|back)|\bfront\b|\bback\b|\brear\b|integrated|built-?in|facetime|internal)/i;
    // Labels that positively identify an external / USB camera.
    const EXTERNAL_LABEL = /(usb|uvc|otg|external|webcam|facing external)/i;

    const isMobile = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

    let options = {};
    let stream = null;
    let activeDeviceId = null;
    let activeIsExternal = false;
    let modal = null;
    let deviceChangeBound = false;

    function hasCameraApi() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    function sizeConstraints() {
        return { width: { ideal: options.width || 320 }, height: { ideal: options.height || 240 } };
    }

    /**
     * Camera labels stay empty until the page has camera permission, so
     * briefly open any camera first when they're missing.
     */
    async function listCameras() {
        let devices = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');

        if (devices.length && devices.every(d => !d.label)) {
            const probe = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            probe.getTracks().forEach(t => t.stop());
            devices = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');
        }

        return devices;
    }

    function isExternal(device) {
        if (EXTERNAL_LABEL.test(device.label)) {
            return true;
        }
        // On a phone, anything that isn't the built-in front/back camera is
        // an OTG-attached camera. Laptops can't be judged this way: their
        // built-in webcams are USB devices with arbitrary labels.
        return isMobile && device.label !== '' && !BUILT_IN_LABEL.test(device.label);
    }

    /**
     * The colorimeter camera among `devices`, if one can be identified.
     * Desktop extra: when one camera is clearly the laptop's built-in one
     * and exactly one other camera exists, that other one is the box.
     */
    function findExternal(devices) {
        const labelled = devices.find(isExternal);
        if (labelled || isMobile) {
            return labelled;
        }

        const others = devices.filter(d => d.label && !BUILT_IN_LABEL.test(d.label));
        const hasBuiltIn = devices.some(d => BUILT_IN_LABEL.test(d.label));

        return hasBuiltIn && others.length === 1 ? others[0] : undefined;
    }

    function stopTracks() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
    }

    async function open(videoConstraints, external) {
        stopTracks();
        stream = await navigator.mediaDevices.getUserMedia({
            video: Object.assign(sizeConstraints(), videoConstraints),
            audio: false,
        });

        const track = stream.getVideoTracks()[0];
        activeDeviceId = track && track.getSettings ? track.getSettings().deviceId : null;
        activeIsExternal = external;

        if (typeof options.onStream === 'function') {
            options.onStream(stream, { external: external, label: track ? track.label : '' });
        }

        return stream;
    }

    function openDevice(device, external) {
        return open({ deviceId: { exact: device.deviceId } }, external === undefined ? isExternal(device) : external);
    }

    function openFacing(facingMode) {
        return open({ facingMode: { ideal: facingMode } }, false);
    }

    function reportError(err) {
        const name = err && err.name;
        let message = 'Camera error: ' + (err && err.message ? err.message : err);

        if (name === 'NotAllowedError' || name === 'SecurityError') {
            message = 'Camera access was blocked. Allow camera permission for this site in your browser settings, then try again.';
        } else if (name === 'NotReadableError') {
            message = 'The camera is in use by another app. Close other camera apps and try again.';
        } else if (name === 'NotFoundError' || name === 'OverconstrainedError') {
            message = 'That camera could not be opened. Re-plug the colorimeter box or choose another camera.';
        }

        if (typeof options.onError === 'function') {
            options.onError(err, message);
        } else {
            alert(message);
        }
    }

    // ── Picker modal ────────────────────────────────────────────────

    function buildModal() {
        const el = document.createElement('div');
        el.className = 'modal fade';
        el.tabIndex = -1;
        el.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-video me-2"></i>Select Camera</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning small mb-3" data-role="notice">
                            <i class="fas fa-plug me-1"></i>
                            ${isMobile
                                ? '<strong>No USB / OTG camera detected.</strong> Connect the colorimeter box to this phone with an OTG adapter and tap <em>Scan again</em>, or choose the back or front camera below.'
                                : '<strong>The colorimeter camera could not be identified automatically.</strong> Make sure the box is plugged in and tap <em>Scan again</em>, or choose the camera below.'}
                        </div>
                        <div class="d-grid gap-2" data-role="choices"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-role="rescan">
                            <i class="fas fa-sync-alt me-1"></i> Scan again
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(el);

        el.querySelector('[data-role="rescan"]').addEventListener('click', () => {
            hideModal();
            start(options);
        });

        return el;
    }

    function hideModal() {
        if (modal && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modal).hide();
        }
    }

    function addChoice(container, html, onPick, primary) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn ' + (primary ? 'btn-success' : 'btn-outline-secondary') + ' text-start';
        btn.innerHTML = html;
        btn.addEventListener('click', () => {
            hideModal();
            onPick().catch(reportError);
        });
        container.appendChild(btn);
    }

    /**
     * Show the picker. `showNotice` adds the "no USB camera" warning —
     * off when the user opens it on purpose to switch cameras.
     */
    function showPicker(devices, showNotice) {
        if (!window.bootstrap) {
            // Bootstrap JS missing: fall back to the browser's default camera.
            return openFacing('environment').catch(reportError);
        }

        modal = modal || buildModal();
        modal.querySelector('[data-role="notice"]').hidden = !showNotice;

        const choices = modal.querySelector('[data-role="choices"]');
        choices.innerHTML = '';

        const external = findExternal(devices);
        if (external) {
            addChoice(choices, `<i class="fas fa-plug me-2"></i>${escapeHtml(external.label || 'USB camera')} <span class="badge bg-light text-success ms-1">Colorimeter</span>`,
                () => openDevice(external, true), true);
        }

        if (isMobile) {
            addChoice(choices, '<i class="fas fa-camera me-2"></i>Back camera', () => openFacing('environment'));
            addChoice(choices, '<i class="fas fa-camera-rotate me-2"></i>Front camera', () => openFacing('user'));
        } else {
            devices.filter(d => d !== external).forEach((device, i) => {
                addChoice(choices, `<i class="fas fa-camera me-2"></i>${escapeHtml(device.label || 'Camera ' + (i + 1))}`,
                    () => openDevice(device));
            });
        }

        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ── Hot-plug ────────────────────────────────────────────────────

    function bindDeviceChange() {
        if (deviceChangeBound || !navigator.mediaDevices.addEventListener) {
            return;
        }
        deviceChangeBound = true;

        navigator.mediaDevices.addEventListener('devicechange', async () => {
            if (!stream || activeIsExternal) {
                return;
            }
            try {
                const external = findExternal(await listCameras());
                if (external) {
                    hideModal();
                    await openDevice(external, true);
                }
            } catch (err) {
                // A transient enumerate failure during plug/unplug isn't worth
                // interrupting the user over; the current camera stays open.
            }
        });
    }

    // ── Public API ──────────────────────────────────────────────────

    async function start(opts) {
        options = opts || options;

        if (!hasCameraApi()) {
            reportError(new Error('This browser does not support camera access. Open the page over HTTPS in Chrome, Edge, or Safari.'));
            return;
        }

        bindDeviceChange();

        try {
            const devices = await listCameras();
            const external = findExternal(devices);

            if (external) {
                await openDevice(external, true);
                return;
            }

            // Desktop with a single camera: nothing to choose between.
            if (!isMobile && devices.length === 1) {
                await openDevice(devices[0]);
                return;
            }

            showPicker(devices, true);
        } catch (err) {
            reportError(err);
        }
    }

    async function choose() {
        try {
            showPicker(await listCameras(), false);
        } catch (err) {
            reportError(err);
        }
    }

    function stop() {
        stopTracks();
        activeDeviceId = null;
        activeIsExternal = false;
    }

    window.ColorimeterCamera = {
        start: start,
        stop: stop,
        choose: choose,
        isExternalActive: () => activeIsExternal,
    };
})();

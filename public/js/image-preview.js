/**
 * Click-to-preview for capture photos.
 *
 * Any element with `data-preview="<image url>"` (thumbnail, link, button)
 * opens the whole image in a modal; `data-caption` is shown under it.
 * Works for elements added later (event delegation), so pages only need
 * the attribute:
 *
 *   <img src="thumb.jpg" data-preview="full.jpg" data-caption="Capture 1">
 */
(function () {
    'use strict';

    let modal = null;

    function build() {
        const el = document.createElement('div');
        el.className = 'modal fade';
        el.tabIndex = -1;
        el.innerHTML = `
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header border-secondary py-2">
                        <h6 class="modal-title" data-role="caption"></h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-2">
                        <img data-role="image" alt="" style="max-width:100%;max-height:80vh;object-fit:contain;">
                    </div>
                    <div class="modal-footer border-secondary py-1">
                        <a data-role="open" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-external-link-alt me-1"></i> Open full size
                        </a>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(el);
        return el;
    }

    function open(src, caption) {
        if (!window.bootstrap) {
            window.open(src, '_blank', 'noopener');
            return;
        }
        if (!modal || !document.body.contains(modal)) modal = build();
        modal.querySelector('[data-role="image"]').src = src;
        modal.querySelector('[data-role="caption"]').textContent = caption || 'Captured image';
        modal.querySelector('[data-role="open"]').href = src;
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-preview]');
        if (!trigger) return;
        e.preventDefault();
        open(trigger.dataset.preview, trigger.dataset.caption);
    });

    const style = document.createElement('style');
    style.textContent = '[data-preview]{cursor:zoom-in}';
    document.head.appendChild(style);

    window.ImagePreview = { open };
})();

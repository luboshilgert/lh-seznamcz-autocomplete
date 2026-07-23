/**
 * Contact Form 7 adapter naseptavace.
 *
 * Mapovani poli neni v JS natvrdo - PHP ho pred kazdy formular vlozi jako
 * <span class="lh-seznam-cf7-config" data-config="...">, takze na jedne strance
 * muze byt vic formularu s ruznym nastavenim.
 */
(function (window, document) {
    'use strict';

    const LH = window.LHSeznamAutocomplete;
    if (!LH) {
        return;
    }

    /**
     * Precte konfiguraci patrici k danemu formulari.
     *
     * @param {HTMLElement} formElem Element <form> z CF7.
     * @return {Object|null}
     */
    function readConfig(formElem) {
        // Filtr wpcf7_form_elements pridava obsah dovnitr <form>, konfigurace je
        // tedy jeho prvnim potomkem.
        const configElem = formElem.querySelector('.lh-seznam-cf7-config');
        if (!configElem) {
            return null;
        }

        try {
            return JSON.parse(configElem.dataset.config);
        } catch (exc) {
            return null;
        }
    }

    /**
     * Navesi naseptavac na jeden formular.
     *
     * @param {HTMLElement} formElem Element <form>.
     */
    function bindForm(formElem) {
        const config = readConfig(formElem);
        if (!config || !config.street) {
            return;
        }

        // Pole hledame v ramci formulare - vic formularu se stejnymi nazvy poli
        // se tak nepoplete.
        const fields = {
            street: LH.resolveField(formElem, config.street),
            city: LH.resolveField(formElem, config.city),
            postcode: LH.resolveField(formElem, config.postcode),
            country: LH.resolveField(formElem, config.country),
        };

        if (!fields.street) {
            return;
        }

        LH.attach(fields.street, function (address) {
            LH.fillFields(address, fields);
        });
    }

    function bindAll() {
        document.querySelectorAll('.wpcf7 form').forEach(bindForm);
    }

    /**
     * CF7 sam nic neprekresluje, ale formulare umi dodat AJAXem page buildery
     * (Elementor, popupy). LH.attach() je idempotentni, staci tedy prescanovat.
     */
    function observe() {
        if (typeof MutationObserver === 'undefined') {
            return;
        }

        let scheduled = false;
        const observer = new MutationObserver(function () {
            if (scheduled) {
                return;
            }
            scheduled = true;
            window.requestAnimationFrame(function () {
                scheduled = false;
                bindAll();
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    function init() {
        bindAll();
        observe();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);

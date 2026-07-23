/**
 * WooCommerce adapter naseptavace.
 *
 * Pokladna existuje ve dvou podobach a kazda potrebuje jiny zpusob vyplneni:
 *  - klasicka (shortcode [woocommerce_checkout]) - obycejny DOM + jQuery eventy,
 *  - blokova (blok woocommerce/checkout) - React controlled inputy, do kterych
 *    se nepise primo; adresa se posila do data storu wc/store/cart.
 */
(function (window, document) {
    'use strict';

    const LH = window.LHSeznamAutocomplete;
    if (!LH) {
        return;
    }

    const BLOCK_STREET_IDS = ['shipping-address_1', 'billing-address_1'];
    const BLOCK_WAIT_MS = 15000;

    const CART_STORE = 'wc/store/cart';

    // ---------------------------------------------------------------- klasicka

    /**
     * Klasicka pokladna - pole maji pevna id a jsou obycejny DOM.
     *
     * @return {boolean} Zda se nasla.
     */
    function bindClassic() {
        if (!document.getElementById('billing_address_1') && !document.getElementById('shipping_address_1')) {
            return false;
        }

        window.setAutocomplete('billing_address_1', 'billing_city', 'billing_postcode', 'billing_country');
        window.setAutocomplete('shipping_address_1', 'shipping_city', 'shipping_postcode', 'shipping_country');
        return true;
    }

    // Po vyplneni adresy z napovedy Mapy.cz vynutit prepocet dopravy na pokladne.
    // "update" na form.checkout je ve WC checkout.js navesene primo na metodu
    // trigger_update_checkout - na rozdil od primeho triggeru "update_checkout"
    // tak korektne vynuluje i dirtyInput a pripadny pending 1s timer.
    document.addEventListener('lh-address-autocomplete:selected', function (event) {
        if (typeof window.jQuery === 'undefined') {
            return;
        }
        if (!event.target.closest || !event.target.closest('form.checkout')) {
            return;
        }
        window.jQuery('form.checkout').trigger('update');
    });

    // ------------------------------------------------------------------ bloky

    /**
     * Dispatch storu kosiku, pokud ho WooCommerce vystavuje v ocekavane podobe.
     *
     * @return {Object|null}
     */
    function getCartDispatch() {
        if (typeof window.wp === 'undefined' || !window.wp.data || typeof window.wp.data.dispatch !== 'function') {
            return null;
        }
        let store;
        try {
            store = window.wp.data.dispatch(CART_STORE);
        } catch (exc) {
            return null;
        }
        if (!store || typeof store.setShippingAddress !== 'function' || typeof store.setBillingAddress !== 'function') {
            return null;
        }
        return store;
    }

    /**
     * Aktualne ulozena zeme v danem typu adresy.
     *
     * @param {string} addressType "shipping" nebo "billing".
     * @return {string}
     */
    function getCurrentCountry(addressType) {
        try {
            const data = window.wp.data.select(CART_STORE).getCustomerData();
            const address = (addressType === 'shipping') ? data.shippingAddress : data.billingAddress;
            return (address && address.country) ? address.country : '';
        } catch (exc) {
            return '';
        }
    }

    /**
     * Navesi naseptavac na pole blokove pokladny.
     *
     * @param {HTMLElement} inputElem Pole ulice (#shipping-address_1 / #billing-address_1).
     */
    function bindBlockField(inputElem) {
        const addressType = inputElem.id.indexOf('shipping-') === 0 ? 'shipping' : 'billing';

        LH.attach(inputElem, function (address) {
            const store = getCartDispatch();
            if (!store) {
                return;
            }

            const payload = { address_1: address.street };
            if (address.city) {
                payload.city = address.city;
            }
            if (address.postcode) {
                payload.postcode = address.postcode;
            }
            if (address.country) {
                payload.country = address.country;
                // Pri zmene zeme vynulovat kraj - jinak by zustal kraj z te predchozi.
                if (address.country !== getCurrentCountry(addressType)) {
                    payload.state = '';
                }
            }

            // Zapis do storu si sam vyzada prekresleni poli i prepocet dopravy,
            // zadny rucni trigger("update") tu neni potreba.
            if (addressType === 'shipping') {
                store.setShippingAddress(payload);
            } else {
                store.setBillingAddress(payload);
            }
        }, {
            // Blokova pokladna ma plovouci popisky - vlastni placeholder by je rozbil.
            placeholder: false,
            // Obalovani inputu do wrapperu je ve Reactem rizenem DOM riskantni.
            wrapper: false,
            listClass: 'lh-seznam-list',
        });
    }

    /**
     * Projde DOM a navesi naseptavac na vsechna pole ulice blokove pokladny.
     *
     * @return {boolean} Zda se nejake pole naslo.
     */
    function bindBlock() {
        let found = false;
        BLOCK_STREET_IDS.forEach(function (id) {
            const inputElem = document.getElementById(id);
            if (inputElem) {
                found = true;
                bindBlockField(inputElem);
            }
        });
        return found;
    }

    /**
     * Blokova pokladna se renderuje az v JS a pole se objevuji a mizi podle toho,
     * jestli je zaskrtnuto "fakturacni = dorucovaci". Sledujeme tedy DOM prubezne;
     * pokud se do BLOCK_WAIT_MS neobjevi nic, sledovani ukoncime.
     */
    function observeBlock() {
        if (typeof MutationObserver === 'undefined') {
            return;
        }

        let everFound = bindBlock();
        let scheduled = false;

        const observer = new MutationObserver(function () {
            if (scheduled) {
                return;
            }
            scheduled = true;
            window.requestAnimationFrame(function () {
                scheduled = false;
                if (bindBlock()) {
                    everFound = true;
                }
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });

        window.setTimeout(function () {
            if (!everFound) {
                observer.disconnect();
            }
        }, BLOCK_WAIT_MS);
    }

    // ------------------------------------------------------------------ start

    function init() {
        // Klasicka pokladna se rendruje na serveru, staci se po ni podivat rovnou.
        if (bindClassic()) {
            return;
        }
        if (document.querySelector('.wp-block-woocommerce-checkout')) {
            observeBlock();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);

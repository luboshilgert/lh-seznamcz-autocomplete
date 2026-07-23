
setAutocomplete('billing_address_1', 'billing_city', 'billing_postcode', 'billing_country');
setAutocomplete('shipping_address_1', 'shipping_city', 'shipping_postcode', 'shipping_country');

// Po vyplneni adresy z napovedy Mapy.cz vynutit prepocet dopravy na pokladne.
// "update" na form.checkout je ve WC checkout.js navesene primo na metodu
// trigger_update_checkout - na rozdil od primeho triggeru "update_checkout"
// tak korektne vynuluje i dirtyInput a pripadny pending 1s timer.
document.addEventListener('lh-address-autocomplete:selected', function (event) {
    if (typeof window.jQuery === 'undefined') {
        return;
    }
    if (!event.target.closest('form.checkout')) {
        return;
    }
    window.jQuery('form.checkout').trigger('update');
});

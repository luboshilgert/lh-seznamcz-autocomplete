/**
 * Jadro naseptavace adres z Mapy.cz.
 *
 * Vystavuje window.LHSeznamAutocomplete - kontextove adaptery (WooCommerce, CF7)
 * si z nej berou attach() na navesnení naseptavace a fillFields() na vyplneni poli.
 * Globalni setAutocomplete() zustava zachovana kvuli zpetne kompatibilite.
 */
(function (window, document) {
    'use strict';

    /**
     * Navesi naseptavac Mapy.cz na dane pole.
     *
     * @param {HTMLElement|null} inputElem Pole, do ktereho se pise adresa.
     * @param {function} onSelect Zavola se po vyberu navrhu s objektem
     *     {street, city, postcode, country, raw}.
     * @param {Object} [options] Volitelne nastaveni.
     * @param {string|boolean} [options.placeholder] Placeholder pole; false ho necha beze zmeny.
     * @param {boolean} [options.wrapper=true] Zda pole obalit do .autoComplete_wrapper.
     *     Ve Reactem rizenem DOM (blokova pokladna) se obalovani vypina.
     * @param {string} [options.listClass] Trida pridana seznamu navrhu.
     * @return {boolean} Zda se naseptavac opravdu navesil.
     */
    function attach(inputElem, onSelect, options) {
        // Pole na strance neni (napr. CF7 formular bez adresnich poli) - nic nedelame.
        if (!inputElem || typeof autoComplete === 'undefined') {
            return false;
        }
        // Idempotence - MutationObserver v adapterech muze na stejne pole sahnout vickrat.
        if (inputElem.dataset.lhSeznamBound === '1') {
            return false;
        }
        inputElem.dataset.lhSeznamBound = '1';

        const placeholder = (options && 'placeholder' in options)
            ? options.placeholder
            : scriptData.i18n.placeholder;

        const useWrapper = !options || options.wrapper !== false;

        new autoComplete({
            selector: () => inputElem,
            placeHolder: placeholder || undefined,
            wrapper: useWrapper,
            searchEngine: (query, record) => `<mark>${record}</mark>`,
            data: {
                keys: ["value"],
                src: async(query) => {
                    try {
                        const fetchData = await fetch(`https://api.mapy.cz/v1/suggest?lang=cs&limit=5&type=regional.address&locality=cz,sk&apikey=${scriptData.API_KEY}&query=${encodeURIComponent(query)}`);
                        if (!fetchData.ok) {
                            return [];
                        }
                        const jsonData = await fetchData.json();

                        return Array.isArray(jsonData.items)
                            ? jsonData.items.map(item => ({
                                value: item.name,
                                data: item,
                            }))
                            : [];
                    } catch (exc) {
                        return [];
                    }
                },
                cache: false,
            },
            resultItem: {
                element: (item, data) => {
                    const itemData = data.value.data;
                    const desc = document.createElement("div");

                    desc.style.cssText = "overflow: hidden; white-space: nowrap; text-overflow: ellipsis;";
                    desc.textContent = `${itemData.label}, ${itemData.location}`;
                    item.append(
                            desc,
                            );
                },
                highlight: true
            },
            resultsList: {
                element: (list, data) => {
                    list.style.maxHeight = "max-content";
                    list.style.overflow = "hidden";

                    if (!data.results.length) {
                        const message = document.createElement("div");

                        message.setAttribute("class", "no_result");
                        message.style.cssText = "padding: 5px";
                        const span = document.createElement("span");
                        span.textContent = `${scriptData.i18n.noResults} "${data.query}"`;
                        message.appendChild(span);
                        list.prepend(message);
                    } else {
                        const logoHolder = document.createElement("div");
                        const text = document.createElement("span");
                        const img = new Image();

                        logoHolder.style.cssText = "padding: 5px; display: flex; align-items: center; justify-content: end; gap: 5px; font-size: 12px;";
                        text.textContent = scriptData.i18n.poweredBy;
                        img.src = "https://api.mapy.cz/img/api/logo-small.svg";
                        img.style.cssText = "width: 60px";
                        logoHolder.append(text, img);
                        list.append(logoHolder);
                    }
                },
                noResults: true,
                class: (options && options.listClass) ? options.listClass : undefined,
            },
        });

        inputElem.addEventListener("selection", event => {
            // "event.detail" carries the autoComplete.js "feedback" object
            const origData = event.detail.selection.value.data;

            onSelect({
                street: origData.name,
                // Mapy.cz umi vratit PSC s nedelitelnou mezerou (nbsp) - sjednotit whitespace
                // na obycejnou mezeru, aby doslo k matchi shipping zony (wc_normalize_postcode).
                postcode: (origData.zip != null) ? String(origData.zip).replace(/[\s ]+/g, ' ').trim() : origData.zip,
                city: (typeof origData.location === 'string') ? origData.location.split(',')[0] : null,
                country: getCountryISO(origData.regionalStructure),
                raw: origData,
            });
        });

        return true;
    }

    /**
     * Zapise adresu do poli a oznami to okoli custom eventem.
     *
     * @param {Object} address Adresa z attach() - {street, city, postcode, country}.
     * @param {Object} fields Cilova pole - {street, city, postcode, country}.
     */
    function fillFields(address, fields) {
        fields = fields || {};

        // Na street poli nechceme bublajici "input" - vlastni autoComplete instance
        // na nem posloucha "input" a znovu by spustila vyhledavani/otevrela napovedu.
        setFieldValue(fields.street, address.street, { fireInput: false });
        setFieldValue(fields.postcode, address.postcode);
        if (address.city !== null && address.city !== undefined) {
            setFieldValue(fields.city, address.city);
        }
        if (address.country && canSetCountryValue(fields.country, address.country)) {
            setFieldValue(fields.country, address.country);
        }

        // Oznamit dokoncene vyplneni adresy - kontextovy skript (Woo/CF7) si na to
        // muze navazat vlastni logiku (napr. vynuceny prepocet dopravy na pokladne).
        const source = fields.street || document;
        source.dispatchEvent(new CustomEvent('lh-address-autocomplete:selected', {
            bubbles: true,
            detail: {
                street: address.street,
                city: address.city,
                postcode: address.postcode,
                country: address.country,
                fields: {
                    street: fields.street || null,
                    city: fields.city || null,
                    postcode: fields.postcode || null,
                    country: fields.country || null,
                },
            },
        }));
    }

    /**
     * Najde pole podle atributu name, s fallbackem na id. Hleda v ramci
     * predaneho korene, aby se vic formularu se stejnymi nazvy poli nepopletlo.
     *
     * @param {ParentNode|null} root Koren hledani (formular), vychozi document.
     * @param {string} ref Nazev pole (name) nebo id.
     * @return {HTMLElement|null}
     */
    function resolveField(root, ref) {
        if (!ref) {
            return null;
        }
        const scope = root || document;
        const escaped = (typeof CSS !== 'undefined' && CSS.escape)
            ? CSS.escape(ref)
            : ref.replace(/([^\w-])/g, '\\$1');

        try {
            return scope.querySelector('[name="' + escaped + '"]') || scope.querySelector('#' + escaped);
        } catch (exc) {
            return null;
        }
    }

    /**
     * Nastavi hodnotu pole a bublajicimi eventy o tom da vedet okolnimu JS
     * (delegovane handlery WooCommerce checkoutu, CF7 apod.). Event vystreli jen
     * pokud pole existuje, hodnota neni null/undefined/prazdny retezec a opravdu
     * se zmenila.
     *
     * @param {HTMLElement|null} fieldElem Cilove pole.
     * @param {string} value Nova hodnota.
     * @param {Object} [options] Volitelne nastaveni.
     * @param {boolean} [options.fireInput=true] Zda vystrelit i bublajici "input" event.
     */
    function setFieldValue(fieldElem, value, options) {
        if (!fieldElem || value === null || value === undefined || value === '' || fieldElem.value === value) {
            return;
        }
        const fireInput = !options || options.fireInput !== false;
        fieldElem.value = value;
        if (fireInput) {
            fieldElem.dispatchEvent(new Event('input', { bubbles: true }));
        }
        fieldElem.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /**
     * Overi, ze pole umi prijmout danou hodnotu zeme. U <select> musi existovat
     * odpovidajici <option>. Hidden/text pole (napr. kdyz je povolena jen jedna
     * prodejni zeme a WooCommerce vyrenderuje country_to_state jako hidden input)
     * tise neprepisujeme, aby nedoslo k nesouladu mezi UI a odeslanymi daty.
     *
     * @param {HTMLElement|null} fieldElem Pole zeme.
     * @param {string} value ISO kod zeme.
     * @return {boolean}
     */
    function canSetCountryValue(fieldElem, value) {
        if (!fieldElem || fieldElem.tagName !== 'SELECT') {
            return false;
        }
        for (const option of fieldElem.options) {
            if (option.value === value) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param {Array} regionalStructure Struktura z odpovedi Mapy.cz.
     * @return {string|null} ISO kod zeme.
     */
    function getCountryISO(regionalStructure) {
        if (!Array.isArray(regionalStructure)) {
            return null;
        }
        for (const region of regionalStructure) {
            if (region.type === 'regional.country') {
                return region.isoCode;
            }
        }
        return null;
    }

    window.LHSeznamAutocomplete = {
        attach: attach,
        fillFields: fillFields,
        resolveField: resolveField,
        setFieldValue: setFieldValue,
        canSetCountryValue: canSetCountryValue,
        getCountryISO: getCountryISO,
    };

    /**
     * Puvodni API - navesi naseptavac na pole podle jejich id a vyplni je.
     * Zachovano kvuli webum, ktere si ho volaji samy.
     *
     * @param {string} street_id
     * @param {string} city_id
     * @param {string} postcode_id
     * @param {string} country_id
     */
    window.setAutocomplete = function (street_id = '', city_id = '', postcode_id = '', country_id = '') {
        const fields = {
            street: document.getElementById(street_id),
            city: document.getElementById(city_id),
            postcode: document.getElementById(postcode_id),
            country: document.getElementById(country_id),
        };

        attach(fields.street, address => fillFields(address, fields));
    };
})(window, document);

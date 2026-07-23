function setAutocomplete(street_id = '', city_id = '', postcode_id = '', country_id = '') {
    const inputElem = document.getElementById(street_id);
    const cityElem = document.getElementById(city_id);
    const postcodeElem = document.getElementById(postcode_id);
    const countryElem = document.getElementById(country_id);
    // Pole adresy na stránce není (např. CF7 formulář bez adresních polí) - nic neděláme.
    if (!inputElem) {
        return;
    }
    const autoCompleteJS = new autoComplete({
        selector: () => inputElem,
        placeHolder: scriptData.i18n.placeholder,
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
        },
    });
    inputElem.addEventListener("selection", event => {
        // "event.detail" carries the autoComplete.js "feedback" object
        const origData = event.detail.selection.value.data;
        const country = getCountryISO(origData.regionalStructure);
        const street = origData.name;
        // Mapy.cz umi vratit PSC s nedelitelnou mezerou (nbsp) - sjednotit whitespace
        // na obycejnou mezeru, aby doslo k matchi shipping zony (wc_normalize_postcode).
        const postcode = (origData.zip != null) ? String(origData.zip).replace(/[\s ]+/g, ' ').trim() : origData.zip;
        const city = (typeof origData.location === 'string') ? origData.location.split(',')[0] : null;

        // Na street poli nechceme bublajici "input" - vlastni autoComplete instance
        // na nem posloucha "input" a znovu by spustila vyhledavani/otevrela napovedu.
        setFieldValue(inputElem, street, { fireInput: false });
        setFieldValue(postcodeElem, postcode);
        if (city !== null) {
            setFieldValue(cityElem, city);
        }
        if (country && canSetCountryValue(countryElem, country)) {
            setFieldValue(countryElem, country);
        }

        // Oznamit dokoncene vyplneni adresy - kontextovy skript (Woo/CF7) si na to
        // muze navazat vlastni logiku (napr. vynuceny prepocet dopravy na pokladne).
        inputElem.dispatchEvent(new CustomEvent('lh-address-autocomplete:selected', {
            bubbles: true,
            detail: {
                street: street,
                city: city,
                postcode: postcode,
                country: country,
                fields: {
                    street: inputElem,
                    city: cityElem,
                    postcode: postcodeElem,
                    country: countryElem,
                },
            },
        }));
    });
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

function getCountryISO(regionalStructure){
    if (!Array.isArray(regionalStructure)) {
        return null;
    }
    for (const region of regionalStructure) {
        if (region.type === 'regional.country') {
            return region.isoCode; // Vrátit název municipality
        }
    }
    return null;
}

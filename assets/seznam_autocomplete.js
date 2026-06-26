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
        // data to debug
        var country = getCountryISO(origData.regionalStructure);
        inputElem.value = origData.name;
        if (postcodeElem) {
            postcodeElem.value = origData.zip;
        }
        if (cityElem && typeof origData.location === 'string') {
            cityElem.value = origData.location.split(',')[0];
        }
        if (country && countryElem) {
            countryElem.value = country;
            countryElem.dispatchEvent(new Event('change'));
        }


    });
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

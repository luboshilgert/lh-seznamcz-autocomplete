# LH Seznam.cz autocomplete

WordPress plugin, který do adresních polí doplní našeptávač adres z Mapy.cz
(Seznam.cz suggest API). Po výběru z nabídky se kromě ulice předvyplní i město,
PSČ a země.

Podporuje:

- **WooCommerce** - klasickou pokladnu (shortcode `[woocommerce_checkout]`)
  i blokovou (blok `woocommerce/checkout`), fakturační i doručovací adresu,
- **Contact Form 7** - libovolný formulář, s nastavitelným mapováním polí.

## Požadavky

- WordPress 6.0+, PHP 7.4+
- API klíč z [developer.mapy.cz](https://developer.mapy.cz/) (bezplatný tarif stačí)

## Instalace

1. Stáhněte ZIP z [posledního vydání](https://github.com/luboshilgert/lh-seznamcz-autocomplete/releases/latest).
2. Nahrajte přes Pluginy → Přidat plugin → Nahrát plugin a aktivujte.
3. V Nastavení → LH Seznam.cz autocomplete vyplňte API klíč.

Plugin lze nainstalovat i přímým klonem repozitáře do `wp-content/plugins/` -
složka `vendor/` je součástí repa, takže není potřeba spouštět Composer.

## Nastavení

**Nastavení → LH Seznam.cz autocomplete**

| Volba | Význam |
| --- | --- |
| API klíč Mapy.cz | Klíč pro suggest API. Bez něj se plugin nikde nenačte. |
| WooCommerce | Zapne našeptávač na pokladně (obě varianty). |
| Contact Form 7 | Zapne našeptávač ve formulářích CF7. |
| Výchozí pole pro CF7 | Názvy (`name`) polí z CF7 tagů - ulice, město, PSČ, země. |

Skripty pluginu se načtou jen tam, kde je našeptávač zapnutý.

### Pole u Contact Form 7

Globálně se mapování zadává jako `name` z CF7 tagu, tedy např. `your-street`
u tagu `[text your-street]`. Pole **ulice** je povinné - spouští našeptávání.
Ostatní pole jsou volitelná, prázdné se nevyplňuje.

Pro jednotlivý formulář jde mapování přepsat přímo v jeho editoru na záložce
**Mapy.cz našeptávač**. Nabídka obsahuje pole, která formulář opravdu má, plus
volby *Globální default* a *— nepoužívat —*. Našeptávač se tu dá také zapnout
nebo vypnout nezávisle na globálním nastavení.

### Země

Země se vyplňuje jen do `<select>`, který danou zemi v nabídce má - to platí
pro klasickou pokladnu i pro CF7. V blokové pokladně se adresa zapisuje do
datového storu WooCommerce (`wc/store/cart`), takže se rovnou přepočte i doprava.

## Aktualizace

Plugin není na wordpress.org. Nové verze se hlásí přes
[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker),
který čte GitHub Releases tohoto repozitáře. Aktualizace se pak nabízí
standardně v Nástěnka → Aktualizace.

Vydání nové verze: bumpnout `Version:` v hlavičce pluginu i `LH_SEZNAM_VERSION`,
commitnout a otagovat (`v1.1.0`). GitHub Actions workflow sestaví ZIP a připojí
ho k releasu.

## Pro vývojáře

Jádro vystavuje `window.LHSeznamAutocomplete`:

```js
LHSeznamAutocomplete.attach(inputElem, address => { /* {street, city, postcode, country, raw} */ });
LHSeznamAutocomplete.fillFields(address, { street, city, postcode, country });
LHSeznamAutocomplete.resolveField(formElem, 'your-street');
```

Po vyplnění polí se na poli ulice vystřelí bublající událost
`lh-address-autocomplete:selected` s adresou a odkazy na pole v `detail`.

Zachována je i původní globální funkce
`setAutocomplete(streetId, cityId, postcodeId, countryId)`.

## Licence

GPL-2.0+ - viz [gnu.org/licenses/gpl-2.0.txt](http://www.gnu.org/licenses/gpl-2.0.txt).

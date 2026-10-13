# Adresní našeptávač Mapy.com

WordPress plugin, který do adresních polí doplní našeptávač adres z Mapy.cz
(Seznam.cz suggest API). Po výběru z nabídky se kromě ulice předvyplní i město,
PSČ a země.

Podporuje:

- **WooCommerce** - klasickou pokladnu (shortcode `[woocommerce_checkout]`)
  i blokovou (blok `woocommerce/checkout`), fakturační i doručovací adresu,
- **Contact Form 7** - libovolný formulář, s nastavitelným mapováním polí.

## Požadavky

- WordPress 6.0+, PHP 7.4+
- API klíč z [developer.mapy.com](https://developer.mapy.com/) (bezplatný tarif stačí)

## Instalace

1. Stáhněte ZIP z [posledního vydání](https://github.com/luboshilgert/lh-seznamcz-autocomplete/releases/latest).
2. Nahrajte přes Pluginy → Přidat plugin → Nahrát plugin a aktivujte.
3. V Nastavení → Adresní našeptávač Mapy.com vyplňte API klíč.

Plugin lze nainstalovat i přímým klonem repozitáře do `wp-content/plugins/` -
složka `vendor/` je součástí repa, takže není potřeba spouštět Composer.

## Nastavení

**Nastavení → Adresní našeptávač Mapy.com**

| Volba | Význam |
| --- | --- |
| API klíč Mapy.cz | Klíč pro suggest API. Bez něj se plugin nikde nenačte. |
| WooCommerce | Zapne našeptávač na pokladně (obě varianty). |
| Contact Form 7 | Zapne našeptávač ve formulářích CF7. |
| Země pro našeptávání | Ze kterých států se adresy nabízejí (zaškrtávátka). |
| Výchozí pole pro CF7 | Názvy (`name`) polí z CF7 tagů - ulice, město, PSČ, země. |

Skripty pluginu se načtou jen tam, kde je našeptávač zapnutý.

### Země pro našeptávání

Mapy.cz mají adresní data pro Česko, Slovensko, Polsko, Německo, Rakousko
a Maďarsko. Ve výchozím stavu se našeptává z **Česka a Slovenska**. Když
nezaškrtnete žádnou zemi, hledá se ve všech podporovaných.

### Pole u Contact Form 7

Globálně se mapování zadává jako `name` z CF7 tagu, tedy např. `your-street`
u tagu `[text your-street]`. Pole **ulice** je povinné - spouští našeptávání.
Ostatní pole jsou volitelná, prázdné se nevyplňuje.

Pro jednotlivý formulář jde mapování přepsat přímo v jeho editoru na záložce
**Mapy.cz našeptávač**. Nabídka obsahuje pole, která formulář opravdu má, plus
volby *Globální default* a *— nepoužívat —*. Našeptávač se tu dá také zapnout
nebo vypnout nezávisle na globálním nastavení.

### Vyplňování pole země

Do pole země se zapisuje:

- u `<select>` (typicky pokladna) ISO kód, který je hodnotou `<option>` - např. `CZ`,
- u obyčejného textového pole čitelný název země - např. `Česko`,
- skrytá pole se nechávají být (aby nevznikl nesoulad mezi UI a odeslanými daty).

V blokové pokladně se adresa zapisuje do datového storu WooCommerce
(`wc/store/cart`), takže se rovnou přepočte i doprava.

## Vzhled

Vzhled adresního pole (barvu, rámeček, výšku, písmo) určuje šablona webu, plugin
ho nestyluje. Seznam návrhů má světlé výchozí barvy, které jde v šabloně přebít
CSS proměnnými - stačí nastavit jen ty, které potřebujete:

| Proměnná | Výchozí hodnota | Co ovlivňuje |
| --- | --- | --- |
| `--lh-seznam-bg` | `#fff` | pozadí seznamu a položek |
| `--lh-seznam-text` | `#212121` | text položek, hlášky a řádku s logem |
| `--lh-seznam-border` | `rgba(33, 33, 33, .1)` | rámeček seznamu |
| `--lh-seznam-hover` | `rgba(123, 123, 123, .1)` | pozadí položky pod myší |
| `--lh-seznam-selected` | stejné jako `--lh-seznam-hover` | pozadí položky vybrané šipkami |
| `--lh-seznam-selected-bar` | `transparent` | pruh u levého okraje vybrané položky |
| `--lh-seznam-mark` | `black` (bloková pokladna `#ff7a7a`) | zvýrazněná shoda s hledaným textem |
| `--lh-seznam-logo-filter` | `none` | filtr loga Mapy.com - na tmavém pozadí `invert(1)` |

Příklad pro tmavou šablonu:

```css
:root {
    --lh-seznam-bg: #1a1a1a;
    --lh-seznam-text: #fff;
    --lh-seznam-border: rgba(255, 255, 255, .4);
    --lh-seznam-hover: rgba(255, 255, 255, .08);
    --lh-seznam-selected: rgba(255, 255, 255, .16);
    --lh-seznam-selected-bar: #ff4d00;
    --lh-seznam-mark: #ff4d00;
    --lh-seznam-logo-filter: invert(1);
}
```

Proměnné se nastavují na `:root` nebo na jakéhokoli předka formuláře.

## Zabezpečení API klíče

API klíč Mapy.cz se používá přímo v prohlížeči, takže je v HTML stránky viditelný -
to tak Mapy.cz mají navržené. Aby ho nemohl někdo zneužít na jiném webu, **omezte
ho** ve svém účtu na [developer.mapy.com](https://developer.mapy.com/):

- podle **referreru** (HTTP referer) na doménu vašeho webu, nebo
- podle **IP adresy** serveru.

Bez omezení může klíč použít kdokoli, kdo ho ze stránky opíše, a vyčerpat vám
kredit.

## Aktualizace

Plugin není na wordpress.org. Nové verze se hlásí přes
[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker),
který čte GitHub Releases tohoto repozitáře. Aktualizace se pak nabízí
standardně v Nástěnka → Aktualizace.

## Licence

GPL-2.0+ - viz [gnu.org/licenses/gpl-2.0.txt](http://www.gnu.org/licenses/gpl-2.0.txt).

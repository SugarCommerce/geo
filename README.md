# SugarCommerce Geo

Countries for a WordPress shop: names and codes, states and provinces, EU
membership, continents, flags, and addresses written the way each country
writes them.

## What it does

Every store form needs a country dropdown and, for half the world, a state
dropdown beside it; every stored order needs a two-letter code turned back
into a name and an address printed in the right order. Beyond that it is
always the same questions: is this in the EU for VAT, which continent is it,
and is it on the list we treat with more caution.

It does not work out which country a visitor is in (that is
`sugarcommerce/network`) or what they pay in (`sugarcommerce/money`).

## Features

- Fill a country dropdown, in order of name, and turn a stored code back into
  the name
- Read "UK", "USA", "Holland" or a code out of a CSV column as the country it means
- Clean a posted code, or a stored list of them, down to real countries
- Offer the states or provinces of the country picked, for a dependent dropdown
- Print an address in its country's order, and name the boxes of an address
  form for the country chosen -- or hide the ones it does not have
- Decide VAT with EU membership; group by continent; flag high-risk and
  sanctioned countries for fraud rules
- Show a flag that looks the same on every machine (a bundled SVG), or the emoji

## Installation

```bash
composer require sugarcommerce/geo
```

## Quick start

```php
use SugarCommerce\Geo\Addresses;
use SugarCommerce\Geo\Countries;
use SugarCommerce\Geo\Flags;
use SugarCommerce\Geo\Regions;

// Never trust a posted code.
$country = Countries::sanitize( $_POST['country'] ?? '' );  // 'US', or null
$state   = Regions::sanitize( (string) $country, $_POST['state'] ?? '' );

// The provinces for a dependent dropdown, and what to call the boxes.
Regions::for( 'CA' );          // [ 'AB' => 'Alberta', 'BC' => 'British Columbia', ... ]
Addresses::get_fields( 'GB' ); // [ 'city' => 'Town or city', 'state' => 'County', 'postcode' => 'Postcode',
                               //   'has_city' => true, 'has_state' => true, 'has_postcode' => true, 'has_states' => false ]

// The receipt.
Addresses::format( [
    'line1'    => '500 Congress Ave',
    'city'     => 'Austin',
    'state'    => 'TX',
    'postcode' => '78701',
    'country'  => 'US',
], true );
// [ '500 Congress Ave', 'Austin, TX 78701', 'United States' ]

echo Flags::img( 'GB' ) . ' ' . esc_html( Countries::get_name( 'GB' ) );

if ( Countries::is_eu( $order_country ) ) {
    // VAT rules apply.
}
```

Germany puts the postcode first and has no place for a state; Japan, China
and Korea are written largest-first in their own script and smallest-first in
Latin letters. A region is printed as its code where the country writes it
that way (the United States, Canada, Australia, Brazil, Italy) and as its name
elsewhere. Address labels are translated in the `sugarcommerce` text domain,
for a plugin that re-domains library strings at build time; a caller with its
own wording keys off `Addresses::get_types()`.

Flags are served from wherever the library is installed -- `vendor/`, a
Strauss copy, a symlinked plugin -- and nothing is fetched from a third party.
To serve them from somewhere else:

```php
add_filter( 'sugarcommerce_geo_flags_url', fn() => 'https://cdn.example.com/flags/' );
```

In a plugin built with Strauss the hook carries the plugin's prefix
(`SugarCart\Vendor\…` gives `sugarcart_sugarcommerce_geo_flags_url`), so two
plugins bundling the library never answer each other's filter.

`is_high_risk()` is a signal to weigh, not a reason to refuse. `is_sanctioned()`
is a coarse first gate, not a compliance product: both are point-in-time lists.

## Moving from arraypress/wp-*

`arraypress/wp-countries` and the retired `sugarcommerce/countries` are both
this package. Classes keep their names and methods under `SugarCommerce\Geo`.

| Was | Now |
| --- | --- |
| `ArrayPress\Countries\Countries` | `SugarCommerce\Geo\Countries` (every method, same name) |
| `ArrayPress\Countries\Regions` | `SugarCommerce\Geo\Regions` (every method, same name) |
| `ArrayPress\Countries\Addresses` | `SugarCommerce\Geo\Addresses` (every method, same name) |
| `ArrayPress\Countries\AddressFormats` | `SugarCommerce\Geo\AddressFormats` |
| `ArrayPress\Countries\Flags` | `SugarCommerce\Geo\Flags` (every method, same name) |
| filter `arraypress_countries_flags_url` | `sugarcommerce_geo_flags_url`, prefixed per Strauss build (`Utils\Runtime::hook( 'flags_url' )`) |
| text domain `arraypress` (address labels) | `sugarcommerce` |
| `get_country_name()` | `Countries::get_name()` |
| `get_country_flag()` | `Countries::get_flag()` |
| `get_country_continent()` | `Countries::get_continent()` |
| `sanitize_country_code()` | `Countries::sanitize()` |
| `get_country_options()` | `Countries::all()` |
| `render_country()` | `Countries::render()` |
| `get_regions()` | `Regions::for()` |
| `get_region_name()` | `Regions::get_name()` |
| `country_has_regions()` | `Regions::has()` |
| `sanitize_region_code()` | `Regions::sanitize()` |
| `format_address()` | `Addresses::format()` |
| `get_address_labels()` | `Addresses::get_labels()` |
| `get_address_parts()` | `Addresses::get_parts()` |
| SaaS `Country::name()` | `Countries::get_name()` -- gives the code back, not null, when unknown; `exists()` to ask |
| SaaS `Country::code()` / `codes()` / `flag()` / `eu()` | `Countries::get_code()` / `get_codes()` / `get_flag()` / `get_eu_countries()` |
| SaaS `Country::sanitize_list()`, `search()`, `is_*()` | same names on `Countries` |
| SaaS `Country::currency()` | not here: `sugarcommerce/money` |

The global functions are gone: a library bundled into several plugins cannot
safely define them. What else changed: `all()`, the EU, risk and sanctions
lists and `get_by_continent()` now come back in order of name (Åland Islands
beside Afghanistan, Kosovo under K, Samoa under S); a name is matched and
searched in any case, accented capitals included; and the emoji flag no longer
uses the deprecated `HTML-ENTITIES` conversion.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-3.0-or-later, not the usual GPL-2.0-or-later, because of the first of
three things in it that are somebody else's, each under its own licence:

- **`src/Regions.php`** -- the subdivision table follows WooCommerce's
  `i18n/states.php`, and WooCommerce is **GPL-3.0-or-later**.
- **`src/AddressFormats.php`** -- generated from the metadata behind Google's
  [libaddressinput](https://github.com/google/libaddressinput), **CC-BY 4.0**;
  the file's header is the attribution, and `bin/generate-address-formats.php`
  says what is taken and the two places it is added to.
- **`assets/flags/`** -- [circle-flags](https://github.com/HatScripts/circle-flags)
  by HatScripts, **MIT**; the licence is in the folder.

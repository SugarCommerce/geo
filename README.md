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
- Turn what somebody typed into a list's search box -- "germany", "Germ", "de" -- into the codes its rows hold
- Clean a posted code, or a stored list of them, down to real countries
- Offer the states or provinces of the country picked, for a dependent dropdown
- Print an address in its country's order, and name the boxes of an address
  form for the country chosen -- or hide the ones it does not have
- Say which boxes the country needs filled in, and check and tidy a postcode
  against the country's own pattern
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
                               //   'has_city' => true, 'has_state' => true, 'has_postcode' => true, 'has_states' => false,
                               //   'required' => [ ... ], 'postcode_pattern' => '...', 'postcode_example' => 'EC1Y 8SY' ]

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

// A list's search box: the start of a name, a whole word of it, or the code.
Countries::search_codes( 'kingdom' );   // [ 'GB' ]; 'land' names nothing
Countries::search( 'land' );            // a picker's: anywhere in the name
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

The folder's URL comes from `sugarcommerce/plugin`'s `Assets::path_to_url()`,
the one asset-URL helper of these packages, so geo requires that package.

In a plugin built with Strauss the hook carries the plugin's prefix
(`SugarCart\Vendor\…` gives `sugarcart_sugarcommerce_geo_flags_url`), so two
plugins bundling the library never answer each other's filter. A prefix that
itself starts `SugarCommerce\` keeps all its segments --
`SugarCommerce\Playground\Vendor\` gives
`sugarcommerce_playground_vendor_sugarcommerce_geo_flags_url` -- so a plugin
of the family is never taken for an unprefixed copy.

`is_high_risk()` is a signal to weigh, not a reason to refuse. `is_sanctioned()`
is a coarse first gate, not a compliance product: both are point-in-time lists.

## Checking an address form

What the form can check before it is sent, from the same Google data: which
parts the country's post needs, and what its postcodes look like. Only the
shape -- nothing here looks an address up.

```php
Addresses::get_required( 'US' ); // [ 'street' => true, 'city' => true, 'state' => true, 'postcode' => true ]
Addresses::get_required( 'GB' ); // the county is optional: 'state' => false
Addresses::get_required( 'HK' ); // the area, not the district, and no postcode
Addresses::get_required( '' );   // street and town only, until the country is chosen

Addresses::is_valid_postcode( 'GB', ' ec1y   8sy ' ); // true: spacing and case do not matter
Addresses::is_valid_postcode( 'US', '9501' );         // false
Addresses::is_valid_postcode( 'HK', '999077' );       // null: no pattern, so cannot tell

Addresses::normalize_postcode( 'GB', 'ec1y  8sy' );   // 'EC1Y 8SY'
Addresses::postcode_example( 'CA' );                  // 'H3Z 2Y7', for a placeholder
```

`is_valid_postcode()` gives null, not false, where the country has no
pattern -- no postcode, one the data does not describe, or a country it does
not know. Let the postcode through then. A postcode is tidied before it is
checked: spaces at the ends go, a run of them inside counts as one.
`normalize_postcode()` does the same and puts the letters in capitals where
the country writes them that way (Britain, Canada, Malta, Argentina...), but
never adds or moves a space. Ireland does not, by the data, so an Eircode keeps
the case it was typed in.

`postcode_pattern()` is the inside of a regular expression, as Google gives
it, for the browser to check the same thing the server does. Match it whole
and in any case:

```php
preg_match( '/^(?:' . $pattern . ')$/i', $postcode );
```

```js
new RegExp( '^(?:' + pattern + ')$', 'i' ).test( postcode );
```

Keep the group: Britain's pattern is three alternatives, and without it the
anchors hold only the first and last. Leave out PHP's `u` flag, as the library
does, and `\d` is 0 to 9 as it is in a browser, not any digit in Unicode.
`get_fields()` carries `required`, `postcode_pattern` and `postcode_example`
too, so one payload per country is all a script needs.

## Security

- A posted country or region is checked against the tables (`sanitize()`,
  `sanitize_list()`), never taken as sent.
- A flag's code is two letters or nothing, so it is never a path out of the
  flags folder; a table in `data/` is read by name, never by path.
- `render()` and `Flags::img()` escape what they print, and a URL `esc_url()`
  refuses gives no image at all. Everything else is raw text: escape it for
  where it goes.
- Every postcode pattern is bounded and matched whole, so nothing typed into a
  postcode box can make it backtrack without end, in PHP or the browser. A test
  holds every pattern to that.
- The bundled SVG flags are tested for scripts, event handlers and references
  to anywhere else, and are served from the shop itself: no third party learns
  which countries it sells to.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-3.0-or-later, not the usual GPL-2.0-or-later, because of the first of
three things in it that are somebody else's, each under its own licence:

- **`data/regions.php`** -- the subdivision table follows WooCommerce's
  `i18n/states.php`, and WooCommerce is **GPL-3.0-or-later**.
- **`data/address-formats.php`** -- generated from the metadata behind Google's
  [libaddressinput](https://github.com/google/libaddressinput), **CC-BY 4.0**;
  the file's header is the attribution, and `bin/generate-address-formats.php`
  says what is taken and the two places it is added to.
- **`assets/flags/`** -- [circle-flags](https://github.com/HatScripts/circle-flags)
  by HatScripts, **MIT**; the licence is in the folder.

<?php
/**
 * SugarCommerce Geo
 *
 * Countries: their codes and names, and the groups a shop asks about.
 *
 * @package     SugarCommerce\Geo
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-3.0-or-later
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo;

use SugarCommerce\Geo\Traits\Countries\Continents;
use SugarCommerce\Geo\Traits\Countries\Lists;
use SugarCommerce\Geo\Utils\Data;

/**
 * Countries class
 *
 * ISO 3166-1 alpha-2 codes and their English names, and the questions a
 * shop keeps asking of them: is this in the EU for VAT, which continent is
 * it. Which countries a fraud rule weighs is sugarcommerce/security's
 * CountryRisk.
 *
 * Returns raw values, except render(), which is for HTML and says so.
 * Escaping is otherwise the caller's job, because this data reaches HTML,
 * JSON, CSV and PDF, and a library that pre-escapes for one of those
 * corrupts it for the other three.
 *
 * The names are data/countries.php. The continents are answered in
 * Traits\Countries\Continents, and the EU in Traits\Countries\Lists.
 *
 * @since 1.0.0
 */
class Countries {

	use Continents;
	use Lists;

	/**
	 * What people actually type, against the code it means.
	 *
	 * The table holds one official name per country, and almost nobody uses
	 * it. An address form gets "UK", a CSV import gets "USA", and a support
	 * ticket says "Holland" -- none of which match "United Kingdom", "United
	 * States" or "Netherlands", so a strict lookup returns nothing and the
	 * order lands with no country at all.
	 *
	 * The four British nations are here on purpose. They are not countries in
	 * ISO 3166-1 and people write them constantly. Keyed in lower case; the
	 * lookup lowers the needle rather than the table.
	 *
	 * @var array<string, string>
	 */
	private const array ALIASES = [
		'usa'                      => 'US',
		'u.s.a.'                   => 'US',
		'u.s.'                     => 'US',
		'us'                       => 'US',
		'america'                  => 'US',
		'united states of america' => 'US',
		'uk'                       => 'GB',
		'u.k.'                     => 'GB',
		'great britain'            => 'GB',
		'britain'                  => 'GB',
		'england'                  => 'GB',
		'scotland'                 => 'GB',
		'wales'                    => 'GB',
		'northern ireland'         => 'GB',
		'holland'                  => 'NL',
		'the netherlands'          => 'NL',
		'south korea'              => 'KR',
		'north korea'              => 'KP',
		'russia'                   => 'RU',
		'uae'                      => 'AE',
		'ivory coast'              => 'CI',
		'burma'                    => 'MM',
		'czechia'                  => 'CZ',
		'swaziland'                => 'SZ',
		'macedonia'                => 'MK',
		'turkey'                   => 'TR',
		'vatican'                  => 'VA',
		'palestine'                => 'PS',
	];

	/* ========================================================================
	 * COUNTRY DATA
	 * ======================================================================== */

	/**
	 * Every country, in order of name.
	 *
	 * @return array<string, string> Code => name.
	 * @since 1.0.0
	 */
	public static function all(): array {
		return self::countries();
	}

	/**
	 * Every country code, in order of the country's name.
	 *
	 * @return list<string>
	 * @since 1.0.0
	 */
	public static function get_codes(): array {
		return array_keys( self::countries() );
	}

	/**
	 * A country's name.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return string The name, or the code as given when it is not a
	 *                country: an order stored with a code this list does
	 *                not have still shows something.
	 * @since 1.0.0
	 */
	public static function get_name( string $code ): string {
		return self::countries()[ self::normalize( $code ) ] ?? $code;
	}

	/**
	 * Whether a code is a country.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public static function exists( string $code ): bool {
		return isset( self::countries()[ self::normalize( $code ) ] );
	}

	/**
	 * The code for a country's name.
	 *
	 * In any case, accented capitals included ("ÅLAND ISLANDS"), and the
	 * names people actually write -- "USA", "UK", "Holland" -- as well as
	 * the official one.
	 *
	 * @param string $name A country's name, or its code.
	 *
	 * @return string|null The code, or null when the name is not known.
	 * @since 1.0.0
	 */
	public static function get_code( string $name ): ?string {
		$name = mb_strtolower( trim( $name ), 'UTF-8' );

		if ( '' === $name ) {
			return null;
		}

		// A code, given where a name was expected. Worth accepting: this is
		// the method a CSV import reaches for, and a column headed "country"
		// carries both.
		if ( isset( self::countries()[ strtoupper( $name ) ] ) ) {
			return strtoupper( $name );
		}

		if ( isset( self::ALIASES[ $name ] ) ) {
			return self::ALIASES[ $name ];
		}

		foreach ( self::countries() as $code => $country_name ) {
			if ( mb_strtolower( $country_name, 'UTF-8' ) === $name ) {
				return $code;
			}
		}

		return null;
	}

	/**
	 * Countries whose name or code contains some text, for an autocomplete.
	 *
	 * In any case, accented letters included: "åland" finds "Åland Islands".
	 *
	 * @param string $search What was typed.
	 * @param int    $limit  The most to return. 0 for all of them.
	 *
	 * @return array<string, string> Code => name, in order of name.
	 * @since 1.0.0
	 */
	public static function search( string $search, int $limit = 0 ): array {
		$search = mb_strtolower( trim( $search ), 'UTF-8' );

		if ( '' === $search ) {
			return [];
		}

		$matches = [];

		foreach ( self::countries() as $code => $name ) {
			if ( str_contains( mb_strtolower( $name, 'UTF-8' ), $search ) || str_contains( strtolower( $code ), $search ) ) {
				$matches[ $code ] = $name;

				if ( $limit > 0 && count( $matches ) >= $limit ) {
					break;
				}
			}
		}

		return $matches;
	}

	/**
	 * The countries a search term names: what somebody typed into a list's
	 * search box, as the two-letter codes its rows hold.
	 *
	 * "germany", "Germ" and "de" are all `DE`. A term names a country when
	 * it is the start of the country's name, one whole word of it
	 * ("kingdom", "korea"), or the code itself -- not when it is merely
	 * somewhere inside the name: "land" is not a search for Finland, Poland
	 * and thirty others. Fewer than three letters is a code or nothing.
	 * Stricter than search(), which matches anywhere, as a picker should.
	 *
	 * @param string                     $term  What was typed.
	 * @param array<string, string>|null $names Code => name to match
	 *                                          against: the site's own,
	 *                                          translated, as its dropdowns
	 *                                          have them. Default this list.
	 *
	 * @return list<string>
	 * @since 1.1.0
	 */
	public static function search_codes( string $term, ?array $names = null ): array {
		$term  = mb_strtolower( trim( $term ), 'UTF-8' );
		$codes = [];

		if ( '' === $term ) {
			return [];
		}

		foreach ( $names ?? self::countries() as $code => $name ) {
			$name = mb_strtolower( (string) $name, 'UTF-8' );

			$matches = mb_strtolower( (string) $code, 'UTF-8' ) === $term
				|| ( mb_strlen( $term ) >= 3 && ( str_starts_with( $name, $term ) || in_array( $term, (array) preg_split( '/[^\p{L}\p{N}]+/u', $name, -1, PREG_SPLIT_NO_EMPTY ), true ) ) );

			if ( $matches ) {
				$codes[] = (string) $code;
			}
		}

		return $codes;
	}

	/**
	 * A code checked and tidied, or nothing.
	 *
	 * @param string $code What was given. Trimmed and upper-cased.
	 *
	 * @return string|null The code, or null when it is not a country.
	 * @since 1.0.0
	 */
	public static function sanitize( string $code ): ?string {
		$code = self::normalize( $code );

		return isset( self::countries()[ $code ] ) ? $code : null;
	}

	/**
	 * A list of codes checked and tidied.
	 *
	 * For a stored or submitted set -- countries a seller excludes, ships
	 * to, charges VAT in. Each entry is trimmed and upper-cased, anything
	 * that is not a country is dropped, duplicates go, and the rest is
	 * sorted: so two saves of the same set store the same list, and a
	 * change in stored settings means a real change rather than a reorder.
	 *
	 * Takes an array, or a string separated by commas or spaces, because a
	 * typed field and most APIs send the second. Anything else -- a number,
	 * null, a nested array -- contributes nothing.
	 *
	 * @param mixed $codes A list of codes, or "GB, ie de".
	 *
	 * @return list<string> Upper-case codes, each once, in order of code.
	 * @since 1.0.0
	 */
	public static function sanitize_list( mixed $codes ): array {
		if ( is_string( $codes ) ) {
			$codes = preg_split( '/[\s,]+/', $codes, -1, PREG_SPLIT_NO_EMPTY ) ?: [];
		}

		if ( ! is_array( $codes ) ) {
			return [];
		}

		$clean = [];

		foreach ( $codes as $code ) {
			$code = is_string( $code ) ? self::sanitize( $code ) : null;

			if ( null !== $code ) {
				$clean[ $code ] = true;
			}
		}

		ksort( $clean, SORT_STRING );

		return array_map( 'strval', array_keys( $clean ) );
	}

	/* ========================================================================
	 * FORMATTING & RENDERING
	 * ======================================================================== */

	/**
	 * A country as text, with its flag or its code if wanted.
	 *
	 * @param string $code         Country code, in either case.
	 * @param bool   $include_flag Put the emoji flag first.
	 * @param bool   $include_code Put the code after, in brackets.
	 *
	 * @return string "🇬🇧 United Kingdom (GB)", or the code as given, upper
	 *                case, when it is not a country.
	 * @since 1.0.0
	 */
	public static function format( string $code, bool $include_flag = false, bool $include_code = false ): string {
		$code = self::normalize( $code );

		if ( ! self::exists( $code ) ) {
			return $code;
		}

		$parts = [];

		if ( $include_flag ) {
			$parts[] = self::get_flag( $code );
		}

		$parts[] = self::get_name( $code );

		if ( $include_code ) {
			$parts[] = '(' . $code . ')';
		}

		return implode( ' ', $parts );
	}

	/**
	 * A country for a page: its emoji flag and its name, escaped.
	 *
	 * @param string $code         Country code (ISO 3166-1 alpha-2, e.g. 'US', 'GB').
	 * @param bool   $include_flag Include the emoji flag.
	 * @param bool   $include_name Include the country's name.
	 *
	 * @return string|null Escaped HTML; the code alone, escaped, when it is
	 *                     not a country; null when it is empty.
	 * @since 1.0.0
	 */
	public static function render( string $code, bool $include_flag = true, bool $include_name = true ): ?string {
		$code = self::normalize( $code );

		if ( '' === $code ) {
			return null;
		}

		if ( ! self::exists( $code ) ) {
			return esc_html( $code );
		}

		$parts = [];

		if ( $include_flag ) {
			$parts[] = self::get_flag( $code );
		}

		if ( $include_name ) {
			$parts[] = self::get_name( $code );
		}

		return esc_html( implode( ' ', $parts ) );
	}

	/**
	 * A country's emoji flag.
	 *
	 * The flag is the code spelt in regional indicator letters, so only two
	 * letters make one: `G1`, `--` or two bytes of anything else used to
	 * come back as two stray symbols. Any two letters do, a country or not
	 * -- `EU` has a flag too. Drawn by whatever the reader's machine has,
	 * which on Windows is only the two letters; for a flag that looks the
	 * same everywhere, see {@see Flags}.
	 *
	 * @param string $code Country code, in either case.
	 *
	 * @return string The emoji, or an empty string for anything that is not
	 *                two letters.
	 * @since 1.0.0
	 */
	public static function get_flag( string $code ): string {
		$code = self::normalize( $code );

		if ( 1 !== preg_match( '/^[A-Z]{2}\z/', $code ) ) {
			return '';
		}

		// 'A' is U+1F1E6, the first regional indicator letter.
		return mb_chr( 0x1F1E6 + ord( $code[0] ) - 65, 'UTF-8' )
			. mb_chr( 0x1F1E6 + ord( $code[1] ) - 65, 'UTF-8' );
	}

	/* ========================================================================
	 * INTERNAL
	 * ======================================================================== */

	/**
	 * Some countries, with their names, in order of name.
	 *
	 * @param array<int, string> $codes Country codes.
	 *
	 * @return array<string, string>
	 */
	private static function pick( array $codes ): array {
		return array_intersect_key( self::countries(), array_flip( $codes ) );
	}

	/**
	 * ISO 3166-1 alpha-2 codes and names, in order of name.
	 *
	 * @return array<string, string> From data/countries.php.
	 */
	private static function countries(): array {
		return Data::get( 'countries' );
	}

	/**
	 * A code trimmed and upper-cased.
	 *
	 * @param string $code Raw code.
	 *
	 * @return string
	 */
	private static function normalize( string $code ): string {
		return strtoupper( trim( $code ) );
	}
}

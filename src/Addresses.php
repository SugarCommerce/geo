<?php
/**
 * SugarCommerce Geo
 *
 * An address written the way its country writes it, and what the country
 * calls the parts.
 *
 * @package     SugarCommerce\Geo
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-3.0-or-later
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo;

/**
 * Addresses class
 *
 * Two questions a form and a receipt both ask. Which parts does this
 * country's address have, and what are they called -- a State and a ZIP
 * code, a County and a Postcode, a Prefecture, or no postcode at all. And
 * given the parts, what order do they go in: the postcode before the town
 * in Germany, after the state in America, on a line of its own in Britain.
 *
 * The answers come from AddressFormats, which is generated from the data
 * Google publishes for the same purpose. Nothing here looks an address up.
 * The most it checks is what a form can check on its own: which parts the
 * country needs filled in, and whether a postcode has the country's shape.
 *
 * An address is an array of parts, all optional:
 *
 *     name, company, line1, line2, city, state, postcode, country
 *
 * `country` is the two-letter code. `state` is a region code where the
 * country has a list in Regions ('CA', 'ON', 'NSW') and whatever was typed
 * where it does not ('West Yorkshire').
 *
 * @since 1.0.0
 */
class Addresses {

	/**
	 * The part each letter of a format stands for.
	 */
	private const PARTS = [
		'N' => 'name',
		'O' => 'company',
		'A' => 'street',
		'C' => 'city',
		'S' => 'state',
		'Z' => 'postcode',
	];

	/**
	 * Countries that write a region as its code.
	 *
	 * "Austin, TX 78701", "Toronto ON M5V 2T6", "Sydney NSW 2000",
	 * "São Paulo-SP", "00184 Roma RM". Everywhere else the name is what is
	 * written: nobody addresses a letter to "Guadalajara, JA". Kept by hand
	 * because the codes are WooCommerce's and the formats are Google's, and
	 * neither says which of the two a letter carries.
	 */
	private const REGION_AS_CODE = [ 'AU', 'BR', 'CA', 'IT', 'US' ];

	/**
	 * Countries that say "postcode" where the rest say "postal code".
	 *
	 * The data has one word for both. This is spelling, not structure.
	 */
	private const SAYS_POSTCODE = [ 'AU', 'GB', 'GG', 'IM', 'JE', 'NZ', 'ZA' ];

	/**
	 * The format a country's address is written in.
	 *
	 * @param string $country Country code (case-insensitive).
	 * @param bool   $latin   Whether the Latin-letters form is wanted, for a
	 *                        country that has one. Japan, China and Korea
	 *                        write largest-first in their own script and
	 *                        smallest-first in Latin letters.
	 *
	 * @return string The format: %N %O %A %C %S %Z for the parts, %n for a
	 *                new line.
	 */
	public static function get_format( string $country, bool $latin = false ): string {
		$row = AddressFormats::get( $country );

		if ( null === $row ) {
			return AddressFormats::UNKNOWN;
		}

		return $latin && isset( $row['latin'] ) ? $row['latin'] : $row['format'];
	}

	/**
	 * Which parts a country's address has.
	 *
	 * What a form uses to decide which boxes to show. Either of a country's
	 * two formats counts: the parts are the same and only the order moves.
	 *
	 * @param string $country Country code (case-insensitive).
	 *
	 * @return array{city: bool, state: bool, postcode: bool}
	 */
	public static function get_parts( string $country ): array {
		$format = self::get_format( $country ) . self::get_format( $country, true );

		return [
			'city'     => str_contains( $format, '%C' ),
			'state'    => str_contains( $format, '%S' ),
			'postcode' => str_contains( $format, '%Z' ),
		];
	}

	/**
	 * What kind of thing each part is in a country.
	 *
	 * Words for code, not for people: `state`, `province`, `county`,
	 * `prefecture`, `zip`, `postcode`, `eircode`, `post_town`. A caller
	 * with its own wording keys off these; get_labels() has this
	 * library's, translated.
	 *
	 * @param string $country Country code (case-insensitive).
	 *
	 * @return array{city: string, state: string, postcode: string}
	 */
	public static function get_types( string $country ): array {
		$country  = strtoupper( trim( $country ) );
		$row      = AddressFormats::get( $country ) ?? [];
		$postcode = $row['postcode'] ?? 'postal';

		if ( 'postal' === $postcode && in_array( $country, self::SAYS_POSTCODE, true ) ) {
			$postcode = 'postcode';
		}

		return [
			'city'     => $row['city'] ?? 'city',
			// A country nobody has told us about could call it anything.
			'state'    => $row['region'] ?? ( [] === $row ? 'region' : 'province' ),
			'postcode' => $postcode,
		];
	}

	/**
	 * What each part is called in a country, in words for a form.
	 *
	 * Translated, in the library's text domain -- a plugin that bundles the
	 * library can re-domain the calls in words() to its own at build time, so
	 * the labels are in its language file and read like the rest of its
	 * screens. A caller with wording of its own keys off get_types().
	 *
	 * An empty country has the general words ("Town or city", "State, county
	 * or region", "Postcode or ZIP"): a form whose country is not chosen yet
	 * should not guess. A country nothing is known about is named, so it gets
	 * the plainest words for each part.
	 *
	 * @param string $country Country code (case-insensitive), or empty.
	 *
	 * @return array{city: string, state: string, postcode: string}
	 */
	public static function get_labels( string $country ): array {
		$words   = self::words();
		$general = [
			'city'     => $words['general']['city'],
			'state'    => $words['general']['state'],
			'postcode' => $words['general']['postcode'],
		];

		if ( '' === trim( $country ) ) {
			return $general;
		}

		$types = self::get_types( $country );

		return [
			'city'     => $words['city'][ $types['city'] ] ?? $general['city'],
			'state'    => $words['state'][ $types['state'] ] ?? $general['state'],
			'postcode' => $words['postcode'][ $types['postcode'] ] ?? $general['postcode'],
		];
	}

	/**
	 * Everything an address form needs to follow its country.
	 *
	 * The labels, which of the three boxes to show, and whether the state is
	 * chosen from a list (Regions has one) or typed. `has_state` is whether an
	 * address there has a state at all; `has_states` is whether there is a list
	 * of them. A country that is empty or unknown shows every box: not known is
	 * not the same as known to have none.
	 *
	 * Then what the form checks before it is sent: `required`, the parts that
	 * must be filled in (get_required()), and `postcode_pattern` and
	 * `postcode_example`, the shape of a postcode there and one that fits it,
	 * both empty where nothing is known.
	 *
	 * Ready to be sent to a script that renames, hides and checks the boxes
	 * when the country changes, one country at a time.
	 *
	 * @param string $country Country code (case-insensitive), or empty.
	 *
	 * @return array{city: string, state: string, postcode: string, has_city: bool, has_state: bool, has_postcode: bool, has_states: bool, required: array{street: bool, city: bool, state: bool, postcode: bool}, postcode_pattern: string, postcode_example: string}
	 */
	public static function get_fields( string $country ): array {
		$country = strtoupper( trim( $country ) );
		$parts   = self::get_parts( $country );

		return self::get_labels( $country ) + [
			'has_city'         => $parts['city'],
			'has_state'        => $parts['state'],
			'has_postcode'     => $parts['postcode'],
			'has_states'       => [] !== Regions::for( $country ),
			'required'         => self::get_required( $country ),
			'postcode_pattern' => self::postcode_pattern( $country ),
			'postcode_example' => self::postcode_example( $country ),
		];
	}

	/**
	 * Which parts of a country's address must be filled in.
	 *
	 * As the country's post needs them, not as a shop might like them: a
	 * British county is optional, an American state is not, and Hong Kong
	 * needs its area but not its district. A part the country does not have
	 * is never required. A country that is empty or unknown needs a street
	 * and a town, and nothing else -- it may have no region or postcode to
	 * give. A name is left to the form, which knows whose address it is.
	 *
	 * @param string $country Country code (case-insensitive), or empty.
	 *
	 * @return array{street: bool, city: bool, state: bool, postcode: bool}
	 */
	public static function get_required( string $country ): array {
		$row     = AddressFormats::get( $country ) ?? [];
		$require = $row['require'] ?? AddressFormats::REQUIRE;

		return [
			'street'   => str_contains( $require, 'A' ),
			'city'     => str_contains( $require, 'C' ),
			'state'    => str_contains( $require, 'S' ),
			'postcode' => str_contains( $require, 'Z' ),
		];
	}

	/**
	 * What a postcode looks like in a country.
	 *
	 * The inside of a regular expression, as Google gives it, to be matched
	 * against the whole postcode in any case: in PHP
	 * `'/^(?:' . $pattern . ')$/i'`, in a script
	 * `new RegExp( '^(?:' + $pattern + ')$', 'i' )`. The group matters --
	 * Britain's pattern is three alternatives. Empty where the country has
	 * no postcode or the data does not describe it, and for a country that
	 * is empty or unknown.
	 *
	 * @param string $country Country code (case-insensitive), or empty.
	 *
	 * @return string
	 */
	public static function postcode_pattern( string $country ): string {
		return AddressFormats::get( $country )['zip'] ?? '';
	}

	/**
	 * A postcode from a country, for a placeholder or an error message.
	 *
	 * Real, and matched by postcode_pattern(). Empty wherever that is.
	 *
	 * @param string $country Country code (case-insensitive), or empty.
	 *
	 * @return string
	 */
	public static function postcode_example( string $country ): string {
		return AddressFormats::get( $country )['zip_example'] ?? '';
	}

	/**
	 * Whether a postcode has its country's shape.
	 *
	 * Only the shape: "SW1A 1AA" is a British postcode whether or not anybody
	 * lives there. Spaces at the ends are ignored and a run of them inside
	 * counts as one, and case does not matter. Matched without Unicode
	 * rules, so `\d` is 0 to 9 and nothing else, as it is in a browser:
	 * a form and its server never disagree.
	 *
	 * Null where the country has no pattern -- it has no postcode, the data
	 * does not describe it, or the country is not known. That is "cannot
	 * tell", not "wrong", and a form should let the postcode through.
	 *
	 * @param string $country  Country code (case-insensitive), or empty.
	 * @param string $postcode The postcode as typed.
	 *
	 * @return bool|null
	 */
	public static function is_valid_postcode( string $country, string $postcode ): ?bool {
		$pattern = self::postcode_pattern( $country );

		if ( '' === $pattern ) {
			return null;
		}

		return 1 === preg_match( '/^(?:' . $pattern . ')$/iD', self::normalize_postcode( $country, $postcode ) );
	}

	/**
	 * A postcode tidied for storing and printing.
	 *
	 * Spaces at the ends go and a run of them inside becomes one, and the
	 * letters are made capitals where the country writes its postcodes in
	 * capitals ("ec1y 8sy" is "EC1Y 8SY" in Britain). Nothing is added or
	 * moved: a postcode typed without its space keeps it out.
	 *
	 * @param string $country  Country code (case-insensitive), or empty.
	 * @param string $postcode The postcode as typed.
	 *
	 * @return string
	 */
	public static function normalize_postcode( string $country, string $postcode ): string {
		$postcode = trim( preg_replace( '/\s+/u', ' ', $postcode ) ?? $postcode );
		$upper    = AddressFormats::get( $country )['upper'] ?? AddressFormats::UPPER;

		return str_contains( $upper, 'Z' ) ? strtoupper( $postcode ) : $postcode;
	}

	/**
	 * The words for each kind of part, translated.
	 *
	 * Literal gettext calls, not a constant, so string extraction and a
	 * build step that re-domains library strings both find them. `general`
	 * is what a form says before it knows the country.
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function words(): array {
		return [
			'general'  => [
				'city'     => __( 'Town or city', 'sugarcommerce' ),
				'state'    => __( 'State, county or region', 'sugarcommerce' ),
				'postcode' => __( 'Postcode or ZIP', 'sugarcommerce' ),
			],
			'city'     => [
				'city'      => __( 'City', 'sugarcommerce' ),
				'post_town' => __( 'Town or city', 'sugarcommerce' ),
				'suburb'    => __( 'Suburb', 'sugarcommerce' ),
				'district'  => __( 'District', 'sugarcommerce' ),
			],
			'state'    => [
				'state'      => __( 'State', 'sugarcommerce' ),
				'province'   => __( 'Province', 'sugarcommerce' ),
				'county'     => __( 'County', 'sugarcommerce' ),
				'prefecture' => __( 'Prefecture', 'sugarcommerce' ),
				'department' => __( 'Department', 'sugarcommerce' ),
				'district'   => __( 'District', 'sugarcommerce' ),
				'island'     => __( 'Island', 'sugarcommerce' ),
				'parish'     => __( 'Parish', 'sugarcommerce' ),
				'emirate'    => __( 'Emirate', 'sugarcommerce' ),
				'do_si'      => __( 'Province', 'sugarcommerce' ),
				'oblast'     => __( 'Region', 'sugarcommerce' ),
				'area'       => __( 'Region', 'sugarcommerce' ),
				'region'     => __( 'Region', 'sugarcommerce' ),
			],
			'postcode' => [
				'postal'   => __( 'Postal code', 'sugarcommerce' ),
				'postcode' => __( 'Postcode', 'sugarcommerce' ),
				'zip'      => __( 'ZIP code', 'sugarcommerce' ),
				'pin'      => __( 'PIN code', 'sugarcommerce' ),
				'eircode'  => __( 'Eircode', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Whether a country writes a region as its code.
	 *
	 * @param string $country Country code (case-insensitive).
	 *
	 * @return bool
	 */
	public static function prints_region_code( string $country ): bool {
		return in_array( strtoupper( trim( $country ) ), self::REGION_AS_CODE, true );
	}

	/**
	 * A region as its country writes it on an envelope.
	 *
	 * A code from Regions becomes the code in upper case or the name,
	 * whichever the country uses. Anything else was typed by somebody and
	 * is given back as they typed it.
	 *
	 * @param string $country Country code (case-insensitive).
	 * @param string $region  Region code, or free text.
	 *
	 * @return string
	 */
	public static function region_text( string $country, string $region ): string {
		$region = trim( $region );
		$code   = Regions::sanitize( $country, $region );

		if ( null === $code ) {
			return $region;
		}

		return self::prints_region_code( $country ) ? $code : Regions::get_name( $country, $code );
	}

	/**
	 * An address, a line at a time, in its country's order.
	 *
	 * A part the address does not have takes its punctuation with it, so
	 * "%C, %S %Z" without a state is "Austin 78701" and not "Austin, 78701".
	 * A part the country's format has no place for is left out: a state
	 * typed for a German address is not printed, because a German address
	 * does not carry one.
	 *
	 * A country with two formats is written in its own order when any part
	 * is in a script other than Latin, and in the Latin order otherwise --
	 * "〒150-0001 / 東京都渋谷区" or "Shibuya-ku, Tokyo / 150-0001".
	 *
	 * @param array<string, mixed> $address      The parts. See the class comment.
	 * @param bool                 $with_country Whether the country's name is the last line.
	 *
	 * @return array<int, string> The lines that have something on them.
	 */
	public static function format( array $address, bool $with_country = false ): array {
		$part    = static fn( string $key ): string => trim( is_scalar( $address[ $key ] ?? '' ) ? (string) ( $address[ $key ] ?? '' ) : '' );
		$country = strtoupper( $part( 'country' ) );

		$values = [
			'name'     => $part( 'name' ),
			'company'  => $part( 'company' ),
			// Two boxes on the form, one part of the format: the second
			// line goes under the first wherever the street goes.
			'street'   => implode( "\n", array_filter( [ $part( 'line1' ), $part( 'line2' ) ], 'strlen' ) ),
			'city'     => $part( 'city' ),
			'state'    => self::region_text( $country, $part( 'state' ) ),
			'postcode' => $part( 'postcode' ),
		];

		$lines = [];

		if ( '' !== implode( '', $values ) ) {
			$latin = 1 !== preg_match( '/[^\p{Latin}\p{Common}\p{Inherited}]/u', implode( '', $values ) );

			foreach ( explode( '%n', self::get_format( $country, $latin ) ) as $line ) {
				foreach ( explode( "\n", self::fill( $line, $values ) ) as $written ) {
					$lines[] = trim( $written );
				}
			}
		}

		if ( $with_country && '' !== $country ) {
			$lines[] = Countries::get_name( $country );
		}

		return array_values( array_filter( $lines, 'strlen' ) );
	}

	/**
	 * One line of a format, with the parts written in.
	 *
	 * The line is read as parts with text between them. Text is kept when
	 * what it leads to is there -- the comma before a state, the "〒"
	 * before a Japanese postcode -- and, between two parts, only when the
	 * one before it was there too, so nothing is left hanging at either
	 * end. Text with no part after it ("GUERNSEY" on a line of its own) is
	 * part of the address and stays.
	 *
	 * @param string                $line   One line of a format.
	 * @param array<string, string> $values The parts.
	 *
	 * @return string
	 */
	private static function fill( string $line, array $values ): string {
		$pieces  = preg_split( '/%([A-Z])/', $line, -1, PREG_SPLIT_DELIM_CAPTURE );
		$written = '';
		$count   = count( $pieces );

		// Odd places are the letters, even places the text before each.
		for ( $i = 0; $i < $count; $i += 2 ) {
			$text  = $pieces[ $i ];
			$value = isset( $pieces[ $i + 1 ] ) ? ( $values[ self::PARTS[ $pieces[ $i + 1 ] ] ?? '' ] ?? '' ) : null;

			if ( null === $value ) {
				// Text that closes the line: kept after something, or
				// when it is the whole line.
				$written .= ( '' !== $written || 1 === $count ) ? $text : '';

				continue;
			}

			if ( '' === $value ) {
				continue;
			}

			// Text between two parts needs the first of them; text that
			// opens the line needs only the part it introduces.
			$written .= ( 0 === $i || '' !== $written ) ? $text . $value : $value;
		}

		return $written;
	}
}

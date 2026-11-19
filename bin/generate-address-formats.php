<?php
/**
 * Writes src/AddressFormats.php from Google's address metadata.
 *
 * The data behind libaddressinput -- the library Chrome and Android use to
 * lay out an address form -- is served a country at a time from
 * https://chromium-i18n.appspot.com/ssl-address/data/{CC}, and is licensed
 * CC-BY 4.0 (the code around it is Apache 2.0; none of that is used). This
 * reads a folder of those files and keeps what a shop needs of each country:
 * the order its address is written in, the same in Latin letters where the
 * country writes in another script, what it calls its region, its postcode
 * and its town, which of those a form must have filled in, and what a
 * postcode there looks like.
 *
 * Usage:
 *
 *     mkdir /tmp/address-data && cd /tmp/address-data
 *     curl -sSL https://chromium-i18n.appspot.com/ssl-address/data -o _index.json
 *     # then one file per code listed in _index.json's "countries", and ZZ:
 *     curl -sSL https://chromium-i18n.appspot.com/ssl-address/data/US -o US.json
 *     php bin/generate-address-formats.php /tmp/address-data
 *
 * What is changed on the way through, and why:
 *
 * - Only the parts this library formats are kept: %N name, %O organisation,
 *   %A street lines, %C town, %S region, %Z postcode. A dependent locality
 *   (%D), a sorting code (%X) and India's three extra lines are dropped,
 *   since nothing here holds them, and a line left empty goes with them.
 * - A country prefix on the postcode ("SE-", "CH-", marked `postprefix` in
 *   the data) is dropped. It is for post crossing a border, and reads as a
 *   mistake on a receipt from a shop in the same country.
 * - Two countries are given a part the data leaves to the street lines,
 *   because shops, Stripe and WooCommerce all collect it separately: the
 *   United Kingdom's county (optional, on its own line before the postcode)
 *   and Japan's city or ward (beside the prefecture). See OVERRIDES. What
 *   the data requires is left alone, so the county stays optional and
 *   Japan's ward is not required.
 * - What a country requires (`require`) and upper-cases (`upper`) is kept
 *   for the street, town, region and postcode only, and only where the part
 *   is in the format. A country that says nothing requires a street and a
 *   town, as the data's default does -- less wherever it has no town.
 * - The postcode pattern (`zip`) is kept as Google gives it: the inside of
 *   a regular expression, matched whole and in any case, the same in PHP
 *   and JavaScript. Of the examples (`zipex`) only the first is kept.
 *
 * @package SugarCommerce\Geo
 */

declare( strict_types=1 );

if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) || ! is_dir( $argv[1] ) ) {
	fwrite( STDERR, "Usage: php bin/generate-address-formats.php <folder of {CC}.json>\n" );
	exit( 1 );
}

const KEPT = [ 'N', 'O', 'A', 'C', 'S', 'Z' ];

/**
 * The parts a form can require or write in capitals: not a name or company.
 */
const CHECKED = [ 'A', 'C', 'S', 'Z' ];

/**
 * The two places this departs from the data. See the file comment.
 */
const OVERRIDES = [
	'GB' => [
		'fmt'             => '%N%n%O%n%A%n%C%n%S%n%Z',
		'state_name_type' => 'county',
	],
	'JP' => [
		'fmt'  => '〒%Z%n%S%C%n%A%n%O%n%N',
		'lfmt' => '%N%n%O%n%A%n%C, %S%n%Z',
	],
];

/**
 * A format with only the kept parts, and no line left empty by the rest.
 *
 * @param string $format Google's format string.
 * @param string $prefix A postcode prefix to drop.
 *
 * @return string
 */
function tidy( string $format, string $prefix ): string {
	if ( '' !== $prefix ) {
		$format = str_replace( $prefix . '%Z', '%Z', $format );
	}

	$lines = [];

	foreach ( explode( '%n', $format ) as $line ) {
		$line = preg_replace( '/%(?![' . implode( '', KEPT ) . '])[A-Z]/', '', $line );
		$line = trim( (string) $line );

		// A line that was only a dropped part, or the punctuation round one.
		if ( '' === trim( $line, " ,-/\u{3000}" ) ) {
			continue;
		}

		$lines[] = $line;
	}

	return implode( '%n', $lines );
}

$folder  = rtrim( $argv[1], '/' );
$default = json_decode( (string) file_get_contents( $folder . '/ZZ.json' ), true );
$rows    = [];

foreach ( glob( $folder . '/[A-Z][A-Z].json' ) as $file ) {
	$code = basename( $file, '.json' );
	$data = json_decode( (string) file_get_contents( $file ), true );

	if ( ! is_array( $data ) || 'ZZ' === $code ) {
		continue;
	}

	$data   = array_merge( $default, $data, OVERRIDES[ $code ] ?? [] );
	$prefix = (string) ( $data['postprefix'] ?? '' );
	$row    = [ 'format' => tidy( (string) $data['fmt'], $prefix ) ];

	if ( ! empty( $data['lfmt'] ) && tidy( (string) $data['lfmt'], $prefix ) !== $row['format'] ) {
		$row['latin'] = tidy( (string) $data['lfmt'], $prefix );
	}

	// What a part is called matters only where the part is used, and is
	// stored only where it is not the usual word.
	if ( str_contains( $row['format'], '%S' ) && 'province' !== $data['state_name_type'] ) {
		$row['region'] = (string) $data['state_name_type'];
	}

	if ( str_contains( $row['format'], '%Z' ) && 'postal' !== $data['zip_name_type'] ) {
		$row['postcode'] = (string) $data['zip_name_type'];
	}

	if ( str_contains( $row['format'], '%C' ) && 'city' !== $data['locality_name_type'] ) {
		$row['city'] = (string) $data['locality_name_type'];
	}

	// Which parts must be filled in, and which are written in capitals, of
	// those the format has, always in the order A C S Z. Stored only where
	// they are not the default, which Addresses falls back to.
	$formats = $row['format'] . ( $row['latin'] ?? '' );
	$keep    = static fn( string $letters ): string => implode(
		'',
		array_filter( CHECKED, static fn( string $letter ): bool => str_contains( $letters, $letter ) && str_contains( $formats, '%' . $letter ) )
	);

	foreach ( [ 'require', 'upper' ] as $key ) {
		if ( $keep( (string) $data[ $key ] ) !== $default[ $key ] ) {
			$row[ $key ] = $keep( (string) $data[ $key ] );
		}
	}

	if ( str_contains( $row['format'], '%Z' ) && ! empty( $data['zip'] ) ) {
		$row['zip']         = (string) $data['zip'];
		$row['zip_example'] = explode( ',', (string) ( $data['zipex'] ?? '' ) )[0];
	}

	$rows[ $code ] = $row;
}

ksort( $rows );

$fallback = tidy( (string) $default['fmt'], '' );
$body     = '';

foreach ( $rows as $code => $row ) {
	$width = max( array_map( 'strlen', array_keys( $row ) ) );
	$body .= "\t\t'" . $code . "' => [\n";

	foreach ( $row as $key => $value ) {
		$body .= "\t\t\t'" . $key . "'" . str_repeat( ' ', $width - strlen( $key ) ) . " => '" . addcslashes( $value, "'\\" ) . "',\n";
	}

	$body .= "\t\t],\n";
}

$template = (string) file_get_contents( __DIR__ . '/address-formats.tpl' );
$output   = str_replace(
	[ '{{DEFAULT}}', '{{REQUIRE}}', '{{UPPER}}', '{{ROWS}}', '{{DATE}}', '{{COUNT}}' ],
	[ $fallback, (string) $default['require'], (string) $default['upper'], rtrim( $body, "\n" ), gmdate( 'Y-m-d' ), (string) count( $rows ) ],
	$template
);

file_put_contents( dirname( __DIR__ ) . '/src/AddressFormats.php', $output );

echo count( $rows ) . " countries written.\n";

<?php
/**
 * Address formatting tests.
 *
 * @package SugarCommerce\Geo
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Tests;

use SugarCommerce\Geo\AddressFormats;
use SugarCommerce\Geo\Addresses;
use SugarCommerce\Geo\Countries;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * An address in its country's order, and what the country calls the parts.
 *
 * The expectations are addresses as they are written on an envelope in each
 * place, not the format strings read back: a test that printed the table
 * would pass whatever the table said.
 */
final class AddressesTest extends TestCase {

	/**
	 * One address per country, with the lines it should come out as.
	 *
	 * @return array<string, array{0: array<string, string>, 1: array<int, string>}>
	 */
	public static function addresses(): array {
		$of = static fn( string $country, string $city, string $state, string $postcode ): array => [
			'line1'    => '1 High Street',
			'line2'    => 'Unit 2',
			'city'     => $city,
			'state'    => $state,
			'postcode' => $postcode,
			'country'  => $country,
		];

		return [
			'US' => [ $of( 'US', 'Austin', 'TX', '78701' ), [ '1 High Street', 'Unit 2', 'Austin, TX 78701' ] ],
			'CA' => [ $of( 'CA', 'Toronto', 'ON', 'M5V 2T6' ), [ '1 High Street', 'Unit 2', 'Toronto ON M5V 2T6' ] ],
			'GB' => [ $of( 'GB', 'Leeds', 'West Yorkshire', 'LS1 4AP' ), [ '1 High Street', 'Unit 2', 'Leeds', 'West Yorkshire', 'LS1 4AP' ] ],
			'IE' => [ $of( 'IE', 'Galway', 'G', 'H91 E2R8' ), [ '1 High Street', 'Unit 2', 'Galway', 'Galway', 'H91 E2R8' ] ],
			'AU' => [ $of( 'AU', 'Sydney', 'NSW', '2000' ), [ '1 High Street', 'Unit 2', 'Sydney NSW 2000' ] ],
			'NZ' => [ $of( 'NZ', 'Wellington', 'WGN', '6011' ), [ '1 High Street', 'Unit 2', 'Wellington 6011' ] ],
			'DE' => [ $of( 'DE', 'Berlin', 'DE-BE', '10115' ), [ '1 High Street', 'Unit 2', '10115 Berlin' ] ],
			'FR' => [ $of( 'FR', 'Paris', '', '75001' ), [ '1 High Street', 'Unit 2', '75001 Paris' ] ],
			'NL' => [ $of( 'NL', 'Amsterdam', '', '1012 AB' ), [ '1 High Street', 'Unit 2', '1012 AB Amsterdam' ] ],
			'BE' => [ $of( 'BE', 'Brussels', '', '1000' ), [ '1 High Street', 'Unit 2', '1000 Brussels' ] ],
			'ES' => [ $of( 'ES', 'Sevilla', 'SE', '41001' ), [ '1 High Street', 'Unit 2', '41001 Sevilla Sevilla' ] ],
			'IT' => [ $of( 'IT', 'Roma', 'RM', '00184' ), [ '1 High Street', 'Unit 2', '00184 Roma RM' ] ],
			'PT' => [ $of( 'PT', 'Lisboa', '', '1100-148' ), [ '1 High Street', 'Unit 2', '1100-148 Lisboa' ] ],
			'SE' => [ $of( 'SE', 'Stockholm', '', '111 22' ), [ '1 High Street', 'Unit 2', '111 22 Stockholm' ] ],
			'NO' => [ $of( 'NO', 'Oslo', '', '0150' ), [ '1 High Street', 'Unit 2', '0150 Oslo' ] ],
			'DK' => [ $of( 'DK', 'København', '', '1050' ), [ '1 High Street', 'Unit 2', '1050 København' ] ],
			'FI' => [ $of( 'FI', 'Helsinki', '', '00100' ), [ '1 High Street', 'Unit 2', '00100 Helsinki' ] ],
			'CH' => [ $of( 'CH', 'Zürich', 'ZH', '8001' ), [ '1 High Street', 'Unit 2', '8001 Zürich' ] ],
			'AT' => [ $of( 'AT', 'Wien', '', '1010' ), [ '1 High Street', 'Unit 2', '1010 Wien' ] ],
			'PL' => [ $of( 'PL', 'Warszawa', '', '00-001' ), [ '1 High Street', 'Unit 2', '00-001 Warszawa' ] ],
			'JP' => [ $of( 'JP', 'Shibuya-ku', 'JP13', '150-0001' ), [ '1 High Street', 'Unit 2', 'Shibuya-ku, Tokyo', '150-0001' ] ],
			'BR' => [ $of( 'BR', 'São Paulo', 'SP', '01310-100' ), [ '1 High Street', 'Unit 2', 'São Paulo-SP', '01310-100' ] ],
			'MX' => [ $of( 'MX', 'Guadalajara', 'JA', '44100' ), [ '1 High Street', 'Unit 2', '44100 Guadalajara, Jalisco' ] ],
			'IN' => [ $of( 'IN', 'Mumbai', 'MH', '400001' ), [ '1 High Street', 'Unit 2', 'Mumbai 400001', 'Maharashtra' ] ],
			'SG' => [ $of( 'SG', '', '', '238801' ), [ '1 High Street', 'Unit 2', 'SINGAPORE 238801' ] ],
			'ZA' => [ $of( 'ZA', 'Cape Town', 'WC', '8001' ), [ '1 High Street', 'Unit 2', 'Cape Town', '8001' ] ],
		];
	}

	/**
	 * Each country's address comes out in that country's order.
	 *
	 * @param array<string, string> $address  The parts.
	 * @param array<int, string>    $expected The lines.
	 */
	#[DataProvider( 'addresses' )]
	public function test_an_address_is_written_in_its_countrys_order( array $address, array $expected ): void {
		$this->assertSame( $expected, Addresses::format( $address ) );
	}

	/**
	 * A missing part takes its punctuation with it.
	 */
	public function test_a_missing_part_leaves_nothing_hanging(): void {
		$address = [
			'city'     => 'Austin',
			'state'    => 'TX',
			'postcode' => '78701',
			'country'  => 'US',
		];

		$this->assertSame( [ 'Austin, TX 78701' ], Addresses::format( $address ) );
		$this->assertSame( [ 'Austin 78701' ], Addresses::format( [ 'state' => '' ] + $address ) );
		$this->assertSame( [ 'Austin, TX' ], Addresses::format( [ 'postcode' => '' ] + $address ) );
		$this->assertSame( [ 'TX 78701' ], Addresses::format( [ 'city' => '' ] + $address ) );
		$this->assertSame( [ 'Austin' ], Addresses::format( [ 'state' => '', 'postcode' => '' ] + $address ) );
		$this->assertSame( [ 'São Paulo' ], Addresses::format( [ 'city' => 'São Paulo', 'country' => 'BR' ] ) );
	}

	/**
	 * Nothing typed is nothing printed, whatever the format has in it.
	 *
	 * Guernsey's format has the island's name as a line of its own, which
	 * is right under a street and wrong as the whole of an address nobody
	 * has filled in.
	 */
	public function test_an_empty_address_has_no_lines(): void {
		$this->assertSame( [], Addresses::format( [] ) );
		$this->assertSame( [], Addresses::format( [ 'country' => 'GG' ] ) );
		$this->assertSame( [], Addresses::format( [ 'country' => 'JP' ] ) );
		$this->assertSame( [ 'Guernsey' ], Addresses::format( [ 'country' => 'gg' ], true ) );
		$this->assertSame(
			[ 'St Peter Port', 'GUERNSEY', 'GY1 1AA' ],
			Addresses::format( [ 'city' => 'St Peter Port', 'postcode' => 'GY1 1AA', 'country' => 'GG' ] )
		);
	}

	/**
	 * The country's name is the last line when it is asked for.
	 */
	public function test_the_country_can_be_the_last_line(): void {
		$address = [ 'line1' => 'Hauptstraße 1', 'city' => 'Berlin', 'postcode' => '10115', 'country' => 'de' ];

		$this->assertSame( [ 'Hauptstraße 1', '10115 Berlin' ], Addresses::format( $address ) );
		$this->assertSame( [ 'Hauptstraße 1', '10115 Berlin', 'Germany' ], Addresses::format( $address, true ) );
	}

	/**
	 * A name and a company go where the country puts them.
	 *
	 * France and Australia put the organisation above the person.
	 */
	public function test_a_name_and_a_company_are_placed(): void {
		$who = [ 'name' => 'Ada Lovelace', 'company' => 'Engines Ltd', 'line1' => '1 High Street', 'city' => 'Paris', 'postcode' => '75001' ];

		$this->assertSame( [ 'Engines Ltd', 'Ada Lovelace', '1 High Street', '75001 Paris' ], Addresses::format( $who + [ 'country' => 'FR' ] ) );
		$this->assertSame( [ 'Ada Lovelace', 'Engines Ltd', '1 High Street', 'Paris', '75001' ], Addresses::format( $who + [ 'country' => 'GB' ] ) );
	}

	/**
	 * Japan is written largest-first in its own script and not in ours.
	 */
	public function test_japan_follows_the_script_it_is_written_in(): void {
		$this->assertSame(
			[ '〒150-0001', '東京都渋谷区', '神宮前1-2-3' ],
			Addresses::format( [ 'line1' => '神宮前1-2-3', 'city' => '渋谷区', 'state' => '東京都', 'postcode' => '150-0001', 'country' => 'JP' ] )
		);
		$this->assertSame(
			[ '1-2-3 Jingumae', 'Shibuya-ku, Tokyo', '150-0001' ],
			Addresses::format( [ 'line1' => '1-2-3 Jingumae', 'city' => 'Shibuya-ku', 'state' => 'Tokyo', 'postcode' => '150-0001', 'country' => 'JP' ] )
		);

		// Accents are still Latin letters.
		$this->assertSame( '%N%n%O%n%A%n%C, %S%n%Z', Addresses::get_format( 'JP', true ) );
		$this->assertSame( [ 'Zürich' ], Addresses::format( [ 'city' => 'Zürich', 'country' => 'CH' ] ) );
	}

	/**
	 * A region is its code where the country writes the code, else its name.
	 */
	public function test_a_region_is_written_as_the_country_writes_it(): void {
		$this->assertSame( 'TX', Addresses::region_text( 'US', 'tx' ) );
		$this->assertSame( 'NSW', Addresses::region_text( 'AU', 'NSW' ) );
		$this->assertSame( 'Jalisco', Addresses::region_text( 'MX', 'JA' ) );
		$this->assertSame( 'Tokyo', Addresses::region_text( 'JP', 'JP13' ) );

		// Typed, not chosen: given back as it was.
		$this->assertSame( 'Texas', Addresses::region_text( 'US', 'Texas' ) );
		$this->assertSame( 'West Yorkshire', Addresses::region_text( 'GB', ' West Yorkshire ' ) );
		$this->assertSame( '', Addresses::region_text( 'US', '' ) );
	}

	/**
	 * A country calls its parts what it calls them.
	 */
	public function test_the_parts_are_called_what_the_country_calls_them(): void {
		$this->assertSame( [ 'city' => 'City', 'state' => 'State', 'postcode' => 'ZIP code' ], Addresses::get_labels( 'US' ) );
		$this->assertSame( [ 'city' => 'Town or city', 'state' => 'County', 'postcode' => 'Postcode' ], Addresses::get_labels( 'GB' ) );
		$this->assertSame( [ 'city' => 'City', 'state' => 'Province', 'postcode' => 'Postal code' ], Addresses::get_labels( 'CA' ) );
		$this->assertSame( [ 'city' => 'Suburb', 'state' => 'State', 'postcode' => 'Postcode' ], Addresses::get_labels( 'au' ) );
		$this->assertSame( 'Prefecture', Addresses::get_labels( 'JP' )['state'] );
		$this->assertSame( 'County', Addresses::get_labels( 'IE' )['state'] );
		$this->assertSame( 'Eircode', Addresses::get_labels( 'IE' )['postcode'] );
		$this->assertSame( 'PIN code', Addresses::get_labels( 'IN' )['postcode'] );
		$this->assertSame( 'prefecture', Addresses::get_types( 'JP' )['state'] );

		// A country nobody knows could call it anything.
		$this->assertSame( [ 'city' => 'City', 'state' => 'Region', 'postcode' => 'Postal code' ], Addresses::get_labels( 'XX' ) );
	}

	/**
	 * A form whose country is not chosen yet has the general words.
	 */
	public function test_no_country_has_the_general_words(): void {
		$general = [ 'city' => 'Town or city', 'state' => 'State, county or region', 'postcode' => 'Postcode or ZIP' ];

		$this->assertSame( $general, Addresses::get_labels( '' ) );
		$this->assertSame( $general, Addresses::get_labels( '  ' ) );
	}

	/**
	 * The labels go through translation, in the library's text domain.
	 */
	public function test_the_labels_are_translated(): void {
		$GLOBALS['co_translations'] = [
			'sugarcommerce' => [
				'ZIP code'                => 'Code postal (ZIP)',
				'County'                  => 'Comté',
				'State, county or region' => 'Région',
			],
			'default'    => [ 'State' => 'wrong domain' ],
		];

		try {
			$this->assertSame( 'Code postal (ZIP)', Addresses::get_labels( 'US' )['postcode'] );
			$this->assertSame( 'State', Addresses::get_labels( 'US' )['state'] );
			$this->assertSame( 'Comté', Addresses::get_labels( 'GB' )['state'] );
			$this->assertSame( 'Région', Addresses::get_labels( '' )['state'] );
		} finally {
			$GLOBALS['co_translations'] = [];
		}
	}

	/**
	 * A form gets its labels, its boxes and whether the state is a list.
	 */
	public function test_a_form_gets_everything_it_needs(): void {
		$this->assertSame(
			[
				'city'         => 'City',
				'state'        => 'State',
				'postcode'     => 'ZIP code',
				'has_city'     => true,
				'has_state'    => true,
				'has_postcode' => true,
				'has_states'   => true,
			],
			Addresses::get_fields( ' us ' )
		);

		// Britain has a county but no list of them to choose from.
		$gb = Addresses::get_fields( 'GB' );
		$this->assertTrue( $gb['has_state'] );
		$this->assertFalse( $gb['has_states'] );

		// Germany has no state at all; Hong Kong no postcode; Singapore neither town nor state.
		$this->assertFalse( Addresses::get_fields( 'DE' )['has_state'] );
		$this->assertFalse( Addresses::get_fields( 'HK' )['has_postcode'] );
		$this->assertFalse( Addresses::get_fields( 'SG' )['has_city'] );

		// Not chosen yet: every box, the general words, nothing to choose from.
		$this->assertSame(
			[
				'city'         => 'Town or city',
				'state'        => 'State, county or region',
				'postcode'     => 'Postcode or ZIP',
				'has_city'     => true,
				'has_state'    => true,
				'has_postcode' => true,
				'has_states'   => false,
			],
			Addresses::get_fields( '' )
		);
	}

	/**
	 * A form can ask which boxes a country needs.
	 */
	public function test_a_country_says_which_parts_it_has(): void {
		$this->assertSame( [ 'city' => true, 'state' => true, 'postcode' => true ], Addresses::get_parts( 'US' ) );
		$this->assertSame( [ 'city' => true, 'state' => false, 'postcode' => true ], Addresses::get_parts( 'DE' ) );
		$this->assertSame( [ 'city' => true, 'state' => true, 'postcode' => false ], Addresses::get_parts( 'HK' ) );
		$this->assertSame( [ 'city' => false, 'state' => false, 'postcode' => true ], Addresses::get_parts( 'SG' ) );
		$this->assertSame( [ 'city' => true, 'state' => true, 'postcode' => true ], Addresses::get_parts( 'JP' ) );

		// Not known is not the same as known to have none.
		$this->assertSame( [ 'city' => true, 'state' => true, 'postcode' => true ], Addresses::get_parts( 'XX' ) );
		$this->assertSame( [ 'city' => true, 'state' => true, 'postcode' => true ], Addresses::get_parts( '' ) );
	}

	/**
	 * Every country the library knows has a format, made of known parts.
	 */
	public function test_every_country_has_a_wellformed_format(): void {
		foreach ( array_keys( Countries::all() ) as $code ) {
			$this->assertArrayHasKey( $code, AddressFormats::FORMATS, $code );
		}

		foreach ( AddressFormats::FORMATS as $code => $row ) {
			$this->assertSame( [], array_diff( array_keys( $row ), [ 'format', 'latin', 'region', 'postcode', 'city' ] ), $code );

			foreach ( [ $row['format'], $row['latin'] ?? '' ] as $format ) {
				$this->assertSame( 0, preg_match( '/%(?![NOACSZn])/', $format ), $code . ': ' . $format );
				$this->assertStringNotContainsString( '%n%n', $format, $code );
			}

			$this->assertStringContainsString( '%A', $row['format'], $code );
		}
	}

	/**
	 * Something that is not text is not an address part.
	 */
	public function test_parts_that_are_not_text_are_ignored(): void {
		$this->assertSame( [ 'Leeds' ], Addresses::format( [ 'line1' => [ 'x' ], 'city' => 'Leeds', 'state' => null, 'country' => 'GB' ] ) );
	}
}

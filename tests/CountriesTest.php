<?php
/**
 * Country accessor tests.
 *
 * @package SugarCommerce\Geo
 */

declare( strict_types=1 );

namespace SugarCommerce\Geo\Tests;

use SugarCommerce\Geo\Countries;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

/**
 * The accessors over the tables.
 *
 * Most take a country code from somewhere outside -- a form, a header, a
 * payment gateway -- so what they do with a code that is wrong, differently
 * cased or not a code at all matters as much as the happy path.
 */
final class CountriesTest extends TestCase {

	/**
	 * Codes are matched whatever case they arrive in.
	 *
	 * Country codes come from gateways and geo headers, and those disagree
	 * about case. Every accessor here upper-cases first; this is the test that
	 * keeps them doing it.
	 */
	public function test_a_code_is_matched_whatever_its_case(): void {
		$this->assertSame( 'United Kingdom', Countries::get_name( 'gb' ) );
		$this->assertSame( 'United Kingdom', Countries::get_name( 'Gb' ) );
		$this->assertTrue( Countries::exists( 'gb' ) );
		$this->assertSame( 'Europe', Countries::get_continent( 'gb' ) );
		$this->assertTrue( Countries::is_eu( 'de' ) );
	}

	/**
	 * An unknown code comes back unchanged rather than as an empty string.
	 *
	 * A list table that prints get_name() for every row shows the raw code for
	 * one it does not recognise, which is a legible answer. An empty cell is
	 * not.
	 */
	public function test_an_unknown_code_is_returned_as_it_came(): void {
		$this->assertSame( 'ZZ', Countries::get_name( 'ZZ' ) );
		$this->assertFalse( Countries::exists( 'ZZ' ) );
		$this->assertNull( Countries::get_continent( 'ZZ' ) );
		$this->assertNull( Countries::sanitize( 'ZZ' ) );
	}

	/**
	 * Sanitising accepts a real code and refuses anything else.
	 *
	 * This is the one to reach for before storing a code, so it has to be the
	 * strict one: it returns null rather than passing something through.
	 */
	public function test_sanitising_accepts_only_a_real_code(): void {
		$this->assertSame( 'GB', Countries::sanitize( '  gb ' ) );

		foreach ( [ '', 'ZZ', 'GBR', 'G', '12', '<b>', 'gb gb' ] as $bad ) {
			$this->assertNull( Countries::sanitize( $bad ), sprintf( '"%s" was accepted.', $bad ) );
		}
	}

	/**
	 * A name can be looked up back to its code.
	 */
	public function test_a_name_resolves_back_to_its_code(): void {
		$this->assertSame( 'GB', Countries::get_code( 'United Kingdom' ) );
		$this->assertSame( 'GB', Countries::get_code( '  united kingdom  ' ) );
		$this->assertNull( Countries::get_code( 'Atlantis' ) );
		$this->assertNull( Countries::get_code( '' ) );
	}

	/**
	 * The names people actually type resolve.
	 *
	 * The table holds one official name per country and almost nobody uses
	 * it. An address form gets "UK", a CSV import gets "USA", a ticket says
	 * "Holland" -- none of which match "United Kingdom", "United States" or
	 * "Netherlands". A strict lookup returns nothing and the order lands with
	 * no country on it.
	 *
	 * @param string $typed What arrived.
	 * @param string $code  The country it means.
	 */
	#[DataProvider( 'aliasProvider' )]
	public function test_the_names_people_type_resolve( string $typed, string $code ): void {
		$this->assertSame( $code, Countries::get_code( $typed ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function aliasProvider(): array {
		return [
			'UK'                 => [ 'UK', 'GB' ],
			'with stops'         => [ 'U.K.', 'GB' ],
			'Great Britain'      => [ 'Great Britain', 'GB' ],
			'a British nation'   => [ 'Scotland', 'GB' ],
			'and another'        => [ 'wales', 'GB' ],
			'USA'                => [ 'USA', 'US' ],
			'America'            => [ 'america', 'US' ],
			'Holland'            => [ 'Holland', 'NL' ],
			'Russia'             => [ 'Russia', 'RU' ],
			'UAE'                => [ 'UAE', 'AE' ],
			'a renamed country'  => [ 'Czechia', 'CZ' ],
			'an older name'      => [ 'Burma', 'MM' ],
			'padded and cased'   => [ '  HoLLaNd  ', 'NL' ],

			// The official name still works.
			'the official name'  => [ 'United Kingdom', 'GB' ],
		];
	}

	/**
	 * A code given where a name was expected is accepted.
	 *
	 * This is the method a CSV import reaches for, and a column headed
	 * "country" carries both.
	 */
	public function test_a_code_is_accepted_where_a_name_belongs(): void {
		$this->assertSame( 'GB', Countries::get_code( 'GB' ) );
		$this->assertSame( 'GB', Countries::get_code( 'gb' ) );
		$this->assertNull( Countries::get_code( 'ZZ' ) );
	}

	/**
	 * Every alias points at a country that exists.
	 *
	 * An alias for a code that is not in the table resolves to something
	 * get_name() cannot render, and the failure is a blank cell rather than an
	 * error.
	 */
	public function test_every_alias_points_at_a_real_country(): void {
		foreach ( self::aliasProvider() as $row ) {
			$this->assertTrue( Countries::exists( $row[1] ), sprintf( '%s is not a country.', $row[1] ) );
		}
	}

	/**
	 * Searching matches part of a name, and part of a code.
	 */
	public function test_searching_matches_names_and_codes(): void {
		$byName = Countries::search( 'united' );

		$this->assertArrayHasKey( 'GB', $byName );
		$this->assertArrayHasKey( 'US', $byName );
		$this->assertArrayHasKey( 'AE', $byName );

		// And by code, which is what an admin typing "GB" expects.
		$this->assertArrayHasKey( 'GB', Countries::search( 'gb' ) );
	}

	/**
	 * A search limit is honoured.
	 */
	public function test_a_search_limit_is_honoured(): void {
		$this->assertCount( 3, Countries::search( 'a', 3 ) );
		$this->assertGreaterThan( 3, count( Countries::search( 'a' ) ) );
	}

	/**
	 * An empty search returns nothing rather than everything.
	 *
	 * The difference matters where the result feeds an autocomplete: a blank
	 * box should not render 250 rows.
	 */
	public function test_an_empty_search_returns_nothing(): void {
		$this->assertSame( [], Countries::search( '' ) );
		$this->assertSame( [], Countries::search( '   ' ) );
	}

	/**
	 * A flag is built from the code's own letters.
	 *
	 * The two letters are shifted into the regional indicator block, so the
	 * flag is arithmetic rather than a lookup table. Which means it will
	 * happily produce a "flag" for a code that is not a country -- worth
	 * knowing, and worth pinning, because it is why render() checks the code
	 * exists first.
	 *
	 * @param string $code The country code.
	 * @param string $flag The emoji it should produce.
	 */
	#[DataProvider( 'flagProvider' )]
	public function test_a_flag_is_built_from_the_code( string $code, string $flag ): void {
		$this->assertSame( $flag, Countries::get_flag( $code ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function flagProvider(): array {
		return [
			'the United Kingdom' => [ 'GB', "\u{1F1EC}\u{1F1E7}" ],
			'lower case'         => [ 'gb', "\u{1F1EC}\u{1F1E7}" ],
			'the United States'  => [ 'US', "\u{1F1FA}\u{1F1F8}" ],
			'Japan'              => [ 'JP', "\u{1F1EF}\u{1F1F5}" ],
		];
	}

	/**
	 * Anything that is not two characters has no flag.
	 */
	public function test_a_code_of_the_wrong_length_has_no_flag(): void {
		$this->assertSame( '', Countries::get_flag( 'GBR' ) );
		$this->assertSame( '', Countries::get_flag( 'G' ) );
		$this->assertSame( '', Countries::get_flag( '' ) );
	}

	/**
	 * Formatting assembles the parts that were asked for.
	 */
	public function test_formatting_assembles_what_was_asked_for(): void {
		$this->assertSame( 'United Kingdom', Countries::format( 'GB' ) );
		$this->assertSame( "\u{1F1EC}\u{1F1E7} United Kingdom", Countries::format( 'GB', true ) );
		$this->assertSame( 'United Kingdom (GB)', Countries::format( 'GB', false, true ) );
		$this->assertSame( "\u{1F1EC}\u{1F1E7} United Kingdom (GB)", Countries::format( 'GB', true, true ) );
	}

	/**
	 * An unknown code formats as itself, with nothing added.
	 */
	public function test_an_unknown_code_formats_as_itself(): void {
		$this->assertSame( 'ZZ', Countries::format( 'ZZ', true, true ) );
	}

	/**
	 * Rendering escapes, because the code came from somewhere else.
	 *
	 * An unrecognised code is echoed back, and the value reaching this method
	 * is whatever a gateway or a form field contained.
	 */
	public function test_rendering_escapes_an_unknown_code(): void {
		$html = Countries::render( '<script>alert(1)</script>' );

		$this->assertIsString( $html );

		// On the angle brackets rather than on the word `script`: the code is
		// upper-cased before it is escaped, so a test looking for a lower-case
		// `<script>` passes whether anything escapes it or not.
		$this->assertStringNotContainsString( '<', $html );
		$this->assertStringNotContainsString( '>', $html );
		$this->assertStringContainsString( '&lt;', $html );
	}

	/**
	 * Rendering nothing gives nothing.
	 */
	public function test_rendering_an_empty_code_gives_null(): void {
		$this->assertNull( Countries::render( '' ) );
	}

	/**
	 * Rendering assembles flag and name, and can be asked for either.
	 */
	public function test_rendering_assembles_flag_and_name(): void {
		$this->assertSame( "\u{1F1EC}\u{1F1E7} United Kingdom", Countries::render( 'gb' ) );
		$this->assertSame( 'United Kingdom', Countries::render( 'GB', false ) );
		$this->assertSame( "\u{1F1EC}\u{1F1E7}", Countries::render( 'GB', true, false ) );
	}

	/**
	 * Countries can be listed by continent, by name or by code.
	 */
	public function test_countries_can_be_listed_by_continent(): void {
		$europe = Countries::get_by_continent( 'Europe' );

		$this->assertArrayHasKey( 'GB', $europe );
		$this->assertArrayHasKey( 'DE', $europe );
		$this->assertArrayNotHasKey( 'JP', $europe );

		// The code works as well as the name.
		$this->assertSame( $europe, Countries::get_by_continent( 'EU' ) );

		$this->assertSame( [], Countries::get_by_continent( 'Atlantis' ) );
	}

	/**
	 * Continent names and codes convert both ways.
	 */
	public function test_continent_names_and_codes_convert(): void {
		$this->assertSame( 'EU', Countries::get_continent_code( 'Europe' ) );
		$this->assertSame( 'Europe', Countries::get_continent_name( 'EU' ) );
		$this->assertSame( 'EU', Countries::get_country_continent_code( 'GB' ) );

		$this->assertNull( Countries::get_continent_code( 'Atlantis' ) );
		$this->assertNull( Countries::get_continent_name( 'ZZ' ) );
		$this->assertNull( Countries::get_country_continent_code( 'ZZ' ) );
	}

	/**
	 * Asking whether a country is on a continent takes either spelling.
	 */
	public function test_membership_of_a_continent_takes_either_spelling(): void {
		$this->assertTrue( Countries::is_continent( 'GB', 'Europe' ) );
		$this->assertTrue( Countries::is_continent( 'GB', 'EU' ) );
		$this->assertFalse( Countries::is_continent( 'GB', 'Asia' ) );
		$this->assertFalse( Countries::is_continent( 'ZZ', 'Europe' ) );
	}

	/**
	 * The membership lists answer, and their list form agrees with them.
	 *
	 * Two ways of asking the same question: a predicate and a list. They are
	 * built from the same table, and a getter that filtered differently from
	 * its predicate would be invisible until something compared them.
	 *
	 * @param string $predicate The is_*() method.
	 * @param string $getter    The get_*() method.
	 */
	#[DataProvider( 'listProvider' )]
	public function test_a_predicate_agrees_with_its_list( string $predicate, string $getter ): void {
		$list = Countries::$getter();

		$this->assertNotEmpty( $list );

		foreach ( array_keys( $list ) as $code ) {
			$this->assertTrue( Countries::$predicate( $code ), sprintf( '%s is listed but %s() says no.', $code, $predicate ) );
		}

		// And something plainly outside it.
		$outside = array_diff( array_keys( Countries::all() ), array_keys( $list ) );

		$this->assertFalse( Countries::$predicate( (string) reset( $outside ) ) );
		$this->assertFalse( Countries::$predicate( 'ZZ' ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function listProvider(): array {
		return [
			'the EU'              => [ 'is_eu', 'get_eu_countries' ],
			'the high-risk list'  => [ 'is_high_risk', 'get_high_risk_countries' ],
			'the sanctioned list' => [ 'is_sanctioned', 'get_sanctioned_countries' ],
		];
	}

	/**
	 * A list comes back as code against name, ready for a dropdown.
	 */
	public function test_a_list_comes_back_ready_for_a_dropdown(): void {
		$eu = Countries::get_eu_countries();

		$this->assertSame( 'Germany', $eu['DE'] ?? null );
	}

	/**
	 * Every country is listed, once.
	 */
	public function test_all_returns_the_whole_table(): void {
		$all = Countries::all();

		$this->assertCount( 250, $all );
		$this->assertSame( 'United Kingdom', $all['GB'] );
		$this->assertSame( array_keys( $all ), Countries::get_codes() );
	}

	/**
	 * The whole list is in order of name, accents read as their letters.
	 *
	 * So that it goes straight into a dropdown, and Åland Islands is beside
	 * Afghanistan rather than after Zimbabwe, where a byte sort puts it.
	 *
	 * The accents come off by decomposition, not iconv: what iconv makes of
	 * them depends on the server. macOS's spells São as `s~ao`, which sorted
	 * it after Syria and hid it there; glibc's turns it into `s?o` in the C
	 * locale.
	 */
	#[RequiresPhpExtension( 'intl' )]
	public function test_the_list_is_in_order_of_name(): void {
		$fold   = static fn( string $name ): string => strtolower( (string) preg_replace( '/\p{Mn}+/u', '', (string) \Normalizer::normalize( $name, \Normalizer::FORM_D ) ) );
		$names  = array_map( $fold, array_values( Countries::all() ) );
		$sorted = $names;

		$this->assertSame( [], preg_grep( '/[^\x20-\x7E]/', $names ), 'Every name folds to plain letters.' );

		sort( $sorted, SORT_STRING );

		$this->assertSame( $sorted, $names );
		$this->assertSame( [ 'AF', 'AX', 'AL' ], array_slice( Countries::get_codes(), 0, 3 ) );
		$this->assertSame( 'ZW', Countries::get_codes()[249] );
	}

	/**
	 * Every list of countries is in order of name too.
	 */
	public function test_every_group_is_in_order_of_name(): void {
		foreach ( [ Countries::get_eu_countries(), Countries::get_high_risk_countries(), Countries::get_sanctioned_countries(), Countries::get_by_continent( 'EU' ) ] as $group ) {
			$this->assertSame( array_keys( array_intersect_key( Countries::all(), $group ) ), array_keys( $group ) );
		}
	}

	/**
	 * A name is matched in any case, accented capitals included.
	 *
	 * strtolower() leaves "Å" alone, so "ÅLAND ISLANDS" used to find nothing.
	 */
	public function test_a_name_with_accents_is_matched_in_any_case(): void {
		$this->assertSame( 'AX', Countries::get_code( 'ÅLAND ISLANDS' ) );
		$this->assertSame( 'CW', Countries::get_code( 'curaçao' ) );
		$this->assertSame( [ 'AX' => 'Åland Islands' ], Countries::search( 'ÅLAND' ) );
	}

	/**
	 * A stored set of countries comes back cleaned, once each, in order.
	 */
	public function test_a_list_of_codes_is_cleaned_and_sorted(): void {
		$this->assertSame( [ 'GB', 'IE' ], Countries::sanitize_list( [ ' ie', 'gb', 'ZZ', 'GB' ] ) );
		$this->assertSame( [ 'DE', 'GB', 'IE', 'US' ], Countries::sanitize_list( "us, gb ie\nde,," ) );
	}

	/**
	 * Anything that is not a code in a list contributes nothing.
	 */
	public function test_a_list_ignores_what_is_not_a_code(): void {
		$this->assertSame( [], Countries::sanitize_list( null ) );
		$this->assertSame( [], Countries::sanitize_list( 44 ) );
		$this->assertSame( [], Countries::sanitize_list( '' ) );
		$this->assertSame( [ 'FR' ], Countries::sanitize_list( [ 'fr', 7, null, [ 'DE' ], 'XX' ] ) );
	}
}

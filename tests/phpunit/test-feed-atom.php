<?php
/**
 * Atom feed tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use function Authorship\BylineFeed\get_person_id;

use const Authorship\POSTS_PARAM;

class TestAtom extends FeedTestCase {
	public function testMultipleAuthorNamesAndBylineNamespace() : void {
		$factory = self::factory()->post;

		// Attributed to Editor and Author, owned by Admin.
		$factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
				self::$users['author']->ID,
			],
		] );

		$raw  = $this->get_raw_feed( '/?feed=atom' );
		$feed = xml_to_array( $raw );

		// Multi-author name in <author><name>.
		$entries = xml_find( $feed, 'feed', 'entry' );
		$author  = xml_find( $entries[0]['child'], 'author', 'name' );

		$expected = sprintf(
			'%1$s, %2$s',
			self::$users['editor']->display_name,
			self::$users['author']->display_name
		);

		$this->assertCount( 1, $author );
		$this->assertSame( $expected, $author[0]['content'] );

		// Byline namespace declaration.
		$this->assertStringContainsString( 'xmlns:byline="https://bylinespec.org/1.0"', $raw );

		// Byline author refs in entry.
		$editor_id = get_person_id( self::$users['editor'] );
		$author_id = get_person_id( self::$users['author'] );

		$this->assertStringContainsString( '<byline:author ref="' . $editor_id . '"/>', $raw );
		$this->assertStringContainsString( '<byline:author ref="' . $author_id . '"/>', $raw );
	}

	/**
	 * Returns the raw feed output for a given URL.
	 *
	 * @param string $url Feed URL.
	 * @return string Raw XML output.
	 */
	private function get_raw_feed( string $url ) : string {
		$level = ob_get_level();

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		$this->go_to( $url );

		ob_start();

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@load_template( ABSPATH . '/wp-includes/feed-' . get_query_var( 'feed' ) . '.php' );

		$output = (string) ob_get_clean();

		for ( $i = 0; $i < $level; $i++ ) {
			ob_start();
		}

		return $output;
	}
}

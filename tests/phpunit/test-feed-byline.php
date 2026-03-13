<?php
/**
 * Byline role mapping tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use function Authorship\BylineFeed\get_byline_role;

use const Authorship\GUEST_ROLE;

class TestBylineRole extends TestCase {
	public function testBylineRoleMapsGuestAuthor() : void {
		$post = self::factory()->post->create_and_get();

		$this->assertSame( 'guest', get_byline_role( self::$users[ GUEST_ROLE ], $post ) );
	}

	public function testBylineRoleMapsStaff() : void {
		$post = self::factory()->post->create_and_get();

		$this->assertSame( 'staff', get_byline_role( self::$users['editor'], $post ) );
	}

	public function testBylineRoleMapsContributor() : void {
		$post = self::factory()->post->create_and_get();

		$this->assertSame( 'contributor', get_byline_role( self::$users['contributor'], $post ) );
	}

	public function testBylineRoleFilterable() : void {
		$post = self::factory()->post->create_and_get();

		add_filter( 'authorship_byline_role', function () : string {
			return 'bot';
		} );

		$this->assertSame( 'bot', get_byline_role( self::$users['editor'], $post ) );

		remove_all_filters( 'authorship_byline_role' );
	}
}

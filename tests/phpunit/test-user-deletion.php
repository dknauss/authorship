<?php
/**
 * User deletion lifecycle tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\GUEST_ROLE;
use const Authorship\POSTS_PARAM;
use const Authorship\TAXONOMY;

use function Authorship\action_deleted_user;
use function Authorship\get_author_ids;

class TestUserDeletion extends TestCase {
	public function testDeletedAuthorWithoutReassignIsRemovedFromAuthorship() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Delete Me',
			'user_email'   => 'delete-me@example.org',
		] );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
				self::$users['editor']->ID,
			],
		] );

		if ( is_multisite() ) {
			wpmu_delete_user( $deleted_user->ID );
		} else {
			wp_delete_user( $deleted_user->ID );
		}

		$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
		$this->assertFalse( get_term_by( 'slug', (string) $deleted_user->ID, TAXONOMY ) );
	}

	public function testDeletedAuthorWithReassignReplacesAuthorshipWithoutDuplicates() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Delete And Reassign Me',
			'user_email'   => 'delete-and-reassign@example.org',
		] );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
				self::$users['editor']->ID,
			],
		] );

		wp_delete_user( $deleted_user->ID, self::$users['editor']->ID );

		$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
		$this->assertFalse( get_term_by( 'slug', (string) $deleted_user->ID, TAXONOMY ) );
	}

	public function testDeletedAuthorWithNewReplacementPreservesAuthorOrder() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Delete Preserve Order',
			'user_email'   => 'delete-preserve-order@example.org',
		] );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
				self::$users['admin']->ID,
			],
		] );

		wp_delete_user( $deleted_user->ID, self::$users['editor']->ID );

		$this->assertSame(
			[ self::$users['editor']->ID, self::$users['admin']->ID ],
			get_author_ids( $post )
		);
	}

	public function testDeletedSoleAuthorWithoutReassignRemovesAllAuthorshipAndDeletesTerm() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Delete Sole Author',
			'user_email'   => 'delete-sole-author@example.org',
		] );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
			],
		] );

		wp_delete_user( $deleted_user->ID );

		$this->assertSame( [], get_author_ids( $post ) );
		$this->assertFalse( get_term_by( 'slug', (string) $deleted_user->ID, TAXONOMY ) );
	}

	public function testDeletedSoleAuthorWithReassignMakesReplacementSoleAuthor() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Delete Sole Author Reassign',
			'user_email'   => 'delete-sole-author-reassign@example.org',
		] );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
			],
		] );

		wp_delete_user( $deleted_user->ID, self::$users['editor']->ID );

		$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
		$this->assertFalse( get_term_by( 'slug', (string) $deleted_user->ID, TAXONOMY ) );
	}

	public function testDeletedGuestAuthorIsRemovedFromAuthorshipAndTermIsDeleted() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => GUEST_ROLE,
			'display_name' => 'Delete Guest Author',
			'user_email'   => 'delete-guest-author@example.org',
		] );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
				self::$users['editor']->ID,
			],
		] );

		if ( is_multisite() ) {
			wpmu_delete_user( $deleted_user->ID );
		} else {
			wp_delete_user( $deleted_user->ID );
		}

		clean_user_cache( $deleted_user );

		$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
		$this->assertFalse( get_term_by( 'slug', (string) $deleted_user->ID, TAXONOMY ) );
		$this->assertFalse( get_user_by( 'id', $deleted_user->ID ) );
	}

	public function testDeletedAuthorWithInvalidReassignFallsBackToRemoval() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Delete Invalid Reassign',
			'user_email'   => 'delete-invalid-reassign@example.org',
		] );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
				self::$users['editor']->ID,
			],
		] );

		action_deleted_user( $deleted_user->ID, 999999, $deleted_user );

		$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
		$this->assertFalse( get_term_by( 'slug', (string) $deleted_user->ID, TAXONOMY ) );
	}

	public function testDeletedAuthorWithSelfReassignFallsBackToRemoval() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Delete Self Reassign',
			'user_email'   => 'delete-self-reassign@example.org',
		] );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
				self::$users['editor']->ID,
			],
		] );

		action_deleted_user( $deleted_user->ID, $deleted_user->ID, $deleted_user );

		$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
		$this->assertFalse( get_term_by( 'slug', (string) $deleted_user->ID, TAXONOMY ) );
	}
}

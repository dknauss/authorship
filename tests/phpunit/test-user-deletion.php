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

	public function testDeletedUserSyncUpdatedHookFiresPerUpdatedPost() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Hook Updated',
			'user_email'   => 'hook-updated@example.org',
		] );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
				self::$users['editor']->ID,
			],
		] );

		$fired = [];

		$callback = function( int $post_id, array $prev, array $updated, int $del_id, int $repl_id, array $ctx ) use ( &$fired ) : void {
			$fired[] = compact( 'post_id', 'prev', 'updated', 'del_id', 'repl_id', 'ctx' );
		};

		add_action( 'authorship_deleted_user_sync_post_updated', $callback, 10, 6 );

		action_deleted_user( $deleted_user->ID, self::$users['author']->ID, $deleted_user );

		remove_action( 'authorship_deleted_user_sync_post_updated', $callback, 10 );

		$this->assertCount( 1, $fired );
		$this->assertSame( $post->ID, $fired[0]['post_id'] );
		$this->assertContains( $deleted_user->ID, $fired[0]['prev'] );
		$this->assertContains( self::$users['author']->ID, $fired[0]['updated'] );
		$this->assertSame( $deleted_user->ID, $fired[0]['del_id'] );
		$this->assertSame( self::$users['author']->ID, $fired[0]['repl_id'] );
		$this->assertSame( 'deleted_user_sync', $fired[0]['ctx']['source'] );
	}

	public function testDeletedUserSyncCompletedHookFiresWithSummary() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Hook Completed',
			'user_email'   => 'hook-completed@example.org',
		] );

		self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
			],
		] );

		$summary = [];

		$callback = function( int $del_id, int $repl_id, int $scanned, int $updated, int $failed, array $ctx ) use ( &$summary ) : void {
			$summary[] = compact( 'del_id', 'repl_id', 'scanned', 'updated', 'failed', 'ctx' );
		};

		add_action( 'authorship_deleted_user_sync_completed', $callback, 10, 6 );

		action_deleted_user( $deleted_user->ID, 0, $deleted_user );

		remove_action( 'authorship_deleted_user_sync_completed', $callback, 10 );

		// In multisite, the completed hook fires once per site.
		$this->assertGreaterThanOrEqual( 1, count( $summary ) );
		$this->assertSame( $deleted_user->ID, $summary[0]['del_id'] );
		$this->assertSame( 1, $summary[0]['scanned'] );
		$this->assertSame( 1, $summary[0]['updated'] );
		$this->assertSame( 0, $summary[0]['failed'] );
		$this->assertSame( 'deleted_user_sync', $summary[0]['ctx']['source'] );
	}

	public function testDeletedUserSyncCompletedHookFiresEvenWithNoPosts() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Hook No Posts',
			'user_email'   => 'hook-no-posts@example.org',
		] );

		$summary = [];

		$callback = function( int $del_id, int $repl_id, int $scanned, int $updated, int $failed ) use ( &$summary ) : void {
			$summary[] = compact( 'del_id', 'repl_id', 'scanned', 'updated', 'failed' );
		};

		add_action( 'authorship_deleted_user_sync_completed', $callback, 10, 5 );

		action_deleted_user( $deleted_user->ID, 0, $deleted_user );

		remove_action( 'authorship_deleted_user_sync_completed', $callback, 10 );

		// In multisite, the completed hook fires once per site.
		$this->assertGreaterThanOrEqual( 1, count( $summary ) );
		$this->assertSame( 0, $summary[0]['scanned'] );
		$this->assertSame( 0, $summary[0]['updated'] );
		$this->assertSame( 0, $summary[0]['failed'] );
	}

	public function testDeletedUserSyncUpdatedHookIncludesEmptyArrayWhenSoleAuthorRemoved() : void {
		$deleted_user = self::factory()->user->create_and_get( [
			'role'         => 'author',
			'display_name' => 'Hook Sole Removed',
			'user_email'   => 'hook-sole-removed@example.org',
		] );

		self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				$deleted_user->ID,
			],
		] );

		$fired = [];

		$callback = function( int $post_id, array $prev, array $updated ) use ( &$fired ) : void {
			$fired[] = compact( 'post_id', 'prev', 'updated' );
		};

		add_action( 'authorship_deleted_user_sync_post_updated', $callback, 10, 3 );

		action_deleted_user( $deleted_user->ID, 0, $deleted_user );

		remove_action( 'authorship_deleted_user_sync_post_updated', $callback, 10 );

		$this->assertCount( 1, $fired );
		$this->assertSame( [], $fired[0]['updated'] );
		$this->assertContains( $deleted_user->ID, $fired[0]['prev'] );
	}
}

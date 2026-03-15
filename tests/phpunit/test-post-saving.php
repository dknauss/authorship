<?php
/**
 * General post saving tests.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\POSTS_PARAM;
use const Authorship\TAXONOMY;

use function Authorship\get_authors;
use function Authorship\set_authors;

class TestPostSaving extends TestCase {
	public function testPostAuthorshipDoesNotGetSavedOnPostTypeThatDoesNotSupportAuthor() : void {
		$factory = self::factory()->post;

		register_post_type( 'testing', [
			'public' => true,
		] );
		remove_post_type_support( 'testing', 'author' );

		// Owned by Editor.
		$post = $factory->create_and_get( [
			'post_type'   => 'testing',
			'post_author' => self::$users['editor']->ID,
		] );

		/** @var \WP_Term[] */
		$terms = wp_get_post_terms( $post->ID, TAXONOMY );
		$authors = get_authors( $post );

		$this->assertCount( 0, $terms );
		$this->assertCount( 0, $authors );
	}

	public function testPostAuthorshipIsRetainedWhenUpdatingPostWithNoAuthorshipParameter() : void {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$post = $factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		wp_update_post( [
			'ID'          => $post->ID,
			'post_status' => 'draft',
		], true );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertSame( [ self::$users['editor']->ID ], $author_ids );
	}

	public function testPostAuthorshipIsRetainedWhenUpdatingPostWithPostAuthorParameter() : void {
		$factory = self::factory()->post;

		// Attributed to Editor, owned by Admin.
		$post = $factory->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		wp_update_post( [
			'ID'          => $post->ID,
			'post_author' => self::$users['author']->ID,
		], true );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertSame( [ self::$users['editor']->ID ], $author_ids );
	}

	public function testPostAuthorshipIsSetToAuthorWhenCreatingPost() : void {
		/** @var int */
		$post_id = wp_insert_post( [
			'post_title'  => 'Testing',
			'post_author' => self::$users['author']->ID,
		], true );
		/** @var \WP_Post */
		$post = get_post( $post_id );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertSame( [ self::$users['author']->ID ], $author_ids );
	}

	public function testPostAuthorshipIsSetToAuthorWhenUpdatingPostWithNoExistingAuthorship() : void {
		$factory = self::factory()->post;

		// Owned by Author.
		$post = $factory->create_and_get( [
			'post_author' => self::$users['author']->ID,
		] );

		wp_update_post( [
			'ID'         => $post->ID,
			'post_title' => 'Updated Title',
		], true );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertSame( [ self::$users['author']->ID ], $author_ids );
	}

	public function testPostAuthorshipIsSetToEmptyWhenUpdatingPostWithNoExistingAuthorshipAndFiltered() : void {
		$factory = self::factory()->post;

		add_filter( 'authorship_default_author', '__return_empty_array' );

		// Owned by Author.
		$post = $factory->create_and_get( [
			'post_author' => self::$users['author']->ID,
		] );

		wp_update_post( [
			'ID'         => $post->ID,
			'post_title' => 'Updated Title',
		], true );

		/** @var int[] */
		$author_ids = wp_list_pluck( get_authors( $post ), 'ID' );

		$this->assertEmpty( $author_ids );

		remove_filter( 'authorship_default_author', '__return_empty_array' );
	}

	public function testMultiplePostInsertionDoesNotCompoundActions() : void {
		global $wp_filter;

		$before = count( $wp_filter['wp_insert_post']->callbacks );

		for ( $i = 0; $i < 3; $i++ ) {
			wp_insert_post( [
				'post_title' => "Testing $i",
			] );
		}

		$after = count( $wp_filter['wp_insert_post']->callbacks );

		$this->assertSame( $before, $after );
	}

	public function testAuthorAssignmentFailureIsSignaledOnInsert() : void {
		$failures = [];

		$callback = function( int $post_id, \WP_Post $post, bool $update, array $author_ids, \Exception $exception ) use ( &$failures ) : void {
			$failures[] = [
				'post_id'    => $post_id,
				'post'       => $post,
				'update'     => $update,
				'author_ids' => $author_ids,
				'exception'  => $exception,
			];
		};

		add_action( 'authorship_author_assignment_failure', $callback, 10, 5 );

		$post_id = wp_insert_post(
			[
				'post_title'  => 'Observable assignment failure',
				'post_author' => self::$users['author']->ID,
				POSTS_PARAM   => [ 999999 ],
			],
			true
		);

		remove_action( 'authorship_author_assignment_failure', $callback, 10 );

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );
		$this->assertCount( 1, $failures );
		$this->assertSame( $post_id, $failures[0]['post_id'] );
		$this->assertFalse( $failures[0]['update'] );
		$this->assertSame( [ 999999 ], $failures[0]['author_ids'] );
		$this->assertInstanceOf( \Exception::class, $failures[0]['exception'] );
		$this->assertSame( 'One or more user IDs are not valid for this site.', $failures[0]['exception']->getMessage() );
	}

	public function testSetAuthorsBeforeHookFiresOnInsert() : void {
		$fired = [];

		$callback = function( \WP_Post $post, array $prev, array $requested, array $context ) use ( &$fired ) : void {
			$fired[] = compact( 'post', 'prev', 'requested', 'context' );
		};

		add_action( 'authorship_set_authors_before', $callback, 10, 4 );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['editor']->ID,
			POSTS_PARAM   => [ self::$users['author']->ID ],
		] );

		remove_action( 'authorship_set_authors_before', $callback, 10 );

		$this->assertGreaterThanOrEqual( 1, count( $fired ) );
		$last = end( $fired );
		$this->assertSame( $post->ID, $last['post']->ID );
		$this->assertContains( self::$users['author']->ID, $last['requested'] );
		$this->assertArrayHasKey( 'source', $last['context'] );
		$this->assertArrayHasKey( 'operation', $last['context'] );
		$this->assertArrayHasKey( 'actor_user_id', $last['context'] );
	}

	public function testSetAuthorsAfterHookFiresOnInsert() : void {
		$fired = [];

		$callback = function( \WP_Post $post, array $prev, array $new_ids, array $context ) use ( &$fired ) : void {
			$fired[] = compact( 'post', 'prev', 'new_ids', 'context' );
		};

		add_action( 'authorship_set_authors_after', $callback, 10, 4 );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['editor']->ID,
			POSTS_PARAM   => [ self::$users['author']->ID ],
		] );

		remove_action( 'authorship_set_authors_after', $callback, 10 );

		$this->assertGreaterThanOrEqual( 1, count( $fired ) );
		$last = end( $fired );
		$this->assertSame( $post->ID, $last['post']->ID );
		$this->assertContains( self::$users['author']->ID, $last['new_ids'] );
	}

	public function testSetAuthorsBeforeHookFiresOnUpdate() : void {
		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['editor']->ID,
			POSTS_PARAM   => [ self::$users['editor']->ID ],
		] );

		$fired = [];

		$callback = function( \WP_Post $p, array $prev, array $requested, array $context ) use ( &$fired ) : void {
			$fired[] = compact( 'prev', 'requested', 'context' );
		};

		add_action( 'authorship_set_authors_before', $callback, 10, 4 );

		wp_update_post( [
			'ID'        => $post->ID,
			POSTS_PARAM => [ self::$users['author']->ID ],
		] );

		remove_action( 'authorship_set_authors_before', $callback, 10 );

		$this->assertGreaterThanOrEqual( 1, count( $fired ) );
		$last = end( $fired );
		$this->assertContains( self::$users['editor']->ID, $last['prev'] );
		$this->assertContains( self::$users['author']->ID, $last['requested'] );
	}

	public function testSetAuthorsFailedHookFiresOnInvalidAuthorIds() : void {
		$fired = [];

		$callback = function( \WP_Post $post, array $prev, array $requested, \Exception $e, array $context ) use ( &$fired ) : void {
			$fired[] = compact( 'post', 'prev', 'requested', 'e', 'context' );
		};

		add_action( 'authorship_set_authors_failed', $callback, 10, 5 );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
		] );

		try {
			set_authors( $post, [ 999999 ] );
		} catch ( \Exception $e ) {
			// Expected.
		}

		remove_action( 'authorship_set_authors_failed', $callback, 10 );

		$this->assertCount( 1, $fired );
		$this->assertSame( $post->ID, $fired[0]['post']->ID );
		$this->assertSame( [ 999999 ], $fired[0]['requested'] );
		$this->assertInstanceOf( \Exception::class, $fired[0]['e'] );
		$this->assertArrayHasKey( 'source', $fired[0]['context'] );
	}

	public function testSetAuthorsAfterDoesNotFireOnFailure() : void {
		$after_fired = false;

		$callback = function() use ( &$after_fired ) : void {
			$after_fired = true;
		};

		add_action( 'authorship_set_authors_after', $callback, 10, 4 );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
		] );

		try {
			set_authors( $post, [ 999999 ] );
		} catch ( \Exception $e ) {
			// Expected.
		}

		remove_action( 'authorship_set_authors_after', $callback, 10 );

		$this->assertFalse( $after_fired );
	}

	public function testLegacyAuthorAssignmentFailureHookStillFires() : void {
		$legacy_fired = [];
		$new_fired    = [];

		$legacy_cb = function( int $post_id ) use ( &$legacy_fired ) : void {
			$legacy_fired[] = $post_id;
		};

		$new_cb = function( \WP_Post $post ) use ( &$new_fired ) : void {
			$new_fired[] = $post->ID;
		};

		add_action( 'authorship_author_assignment_failure', $legacy_cb, 10, 1 );
		add_action( 'authorship_set_authors_failed', $new_cb, 10, 1 );

		wp_insert_post( [
			'post_title'  => 'Legacy hook test',
			'post_author' => self::$users['author']->ID,
			POSTS_PARAM   => [ 999999 ],
		], true );

		remove_action( 'authorship_author_assignment_failure', $legacy_cb, 10 );
		remove_action( 'authorship_set_authors_failed', $new_cb, 10 );

		$this->assertCount( 1, $legacy_fired, 'Legacy hook should still fire' );
		$this->assertCount( 1, $new_fired, 'New failed hook should also fire' );
	}

	public function testSetAuthorsContextIncludesSourceOnDirectCall() : void {
		$context_captured = null;

		$callback = function( \WP_Post $post, array $prev, array $new_ids, array $context ) use ( &$context_captured ) : void {
			$context_captured = $context;
		};

		add_action( 'authorship_set_authors_after', $callback, 10, 4 );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
		] );

		set_authors( $post, [ self::$users['editor']->ID ], [
			'source'    => 'rest',
			'operation' => 'replace',
		] );

		remove_action( 'authorship_set_authors_after', $callback, 10 );

		$this->assertSame( 'rest', $context_captured['source'] );
		$this->assertSame( 'replace', $context_captured['operation'] );
	}
}

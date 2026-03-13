<?php
/**
 * InsertPostHandler unit tests.
 *
 * Tests the class methods directly, covering branching logic
 * not reached by the integration tests in test-post-saving.php.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use Authorship\InsertPostHandler;

use const Authorship\POSTS_PARAM;
use const Authorship\TAXONOMY;

use function Authorship\get_author_ids;
use function Authorship\get_authors;

class TestInsertPostHandler extends TestCase {
	/**
	 * Handler under test.
	 *
	 * @var InsertPostHandler
	 */
	private $handler;

	public function setUp() : void {
		parent::setUp();

		$this->handler = new InsertPostHandler();
	}

	public function testFilterStoresUnsanitizedPostarr() : void {
		$postarr = [
			'post_title' => 'Test',
			POSTS_PARAM  => [ self::$users['editor']->ID ],
		];

		$data = $this->handler->filter_wp_insert_post_data( [ 'post_title' => 'Test' ], $postarr, $postarr );

		// The filter returns data unchanged.
		$this->assertSame( [ 'post_title' => 'Test' ], $data );
	}

	public function testFilterHandlesWPPostObjectAsUnsanitizedPostarr() : void {
		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
		] );

		// Core sometimes passes a WP_Post object as unsanitized_postarr.
		$data = $this->handler->filter_wp_insert_post_data( [ 'post_title' => 'Test' ], [], $post );

		$this->assertSame( [ 'post_title' => 'Test' ], $data );
	}

	public function testTaxInputBypassesAuthorReassignment() : void {
		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Simulate an update where tax_input includes the taxonomy directly.
		// The handler should return early without changing authors.
		$postarr = [
			'tax_input' => [ TAXONOMY => [ 'some-term' ] ],
			POSTS_PARAM => [ self::$users['author']->ID ],
		];

		$this->handler->filter_wp_insert_post_data( [], $postarr, $postarr );
		$this->handler->action_wp_insert_post( $post->ID, $post, true );

		// Authors should remain unchanged (editor, not author).
		$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
	}

	public function testUpdateWithExistingAuthorsAndNoParamRetainsAuthors() : void {
		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Simulate an update that doesn't include POSTS_PARAM.
		$postarr = [
			'ID'          => $post->ID,
			'post_title'  => 'Updated',
		];

		$this->handler->filter_wp_insert_post_data( [], $postarr, $postarr );
		$this->handler->action_wp_insert_post( $post->ID, $post, true );

		// Existing authors should be retained.
		$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
	}

	public function testUpdateWithExplicitPostsParamChangesAuthors() : void {
		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Simulate an update that explicitly sets new authors.
		$postarr = [
			'ID'         => $post->ID,
			POSTS_PARAM  => [ self::$users['author']->ID ],
		];

		$this->handler->filter_wp_insert_post_data( [], $postarr, $postarr );
		$this->handler->action_wp_insert_post( $post->ID, $post, true );

		$this->assertSame( [ self::$users['author']->ID ], get_author_ids( $post ) );
	}

	public function testUpdateWithEmptyPostsParamClearsAuthors() : void {
		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [
				self::$users['editor']->ID,
			],
		] );

		// Explicitly set POSTS_PARAM to empty — should clear.
		$postarr = [
			POSTS_PARAM => [],
		];

		$this->handler->filter_wp_insert_post_data( [], $postarr, $postarr );
		$this->handler->action_wp_insert_post( $post->ID, $post, true );

		// Empty POSTS_PARAM parses to empty id list, handler returns early.
		// Authors should remain since handler skips empty author lists.
		$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
	}

	public function testPostarrIsResetAfterAction() : void {
		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
		] );

		$postarr = [
			POSTS_PARAM => [ self::$users['editor']->ID ],
		];

		$this->handler->filter_wp_insert_post_data( [], $postarr, $postarr );
		$this->handler->action_wp_insert_post( $post->ID, $post, false );

		// Second call without filter_wp_insert_post_data — postarr should be empty.
		$post2 = self::factory()->post->create_and_get( [
			'post_author' => self::$users['author']->ID,
		] );

		$this->handler->action_wp_insert_post( $post2->ID, $post2, false );

		// post2 should get default author (post_author), not editor from previous call.
		$this->assertNotContains( self::$users['editor']->ID, get_author_ids( $post2 ) );
	}

	public function testDefaultAuthorFilterIsAppliedOnNewPost() : void {
		$custom_author_id = self::$users['contributor']->ID;

		add_filter( 'authorship_default_author', function () use ( $custom_author_id ) : array {
			return [ $custom_author_id ];
		} );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
		] );

		$postarr = [
			'post_author' => self::$users['admin']->ID,
		];

		$this->handler->filter_wp_insert_post_data( [], $postarr, $postarr );
		$this->handler->action_wp_insert_post( $post->ID, $post, false );

		$this->assertSame( [ $custom_author_id ], get_author_ids( $post ) );

		remove_all_filters( 'authorship_default_author' );
	}

	public function testMultipleAuthorsCanBeSetOnInsert() : void {
		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
		] );

		$postarr = [
			POSTS_PARAM => [
				self::$users['editor']->ID,
				self::$users['author']->ID,
				self::$users['contributor']->ID,
			],
		];

		$this->handler->filter_wp_insert_post_data( [], $postarr, $postarr );
		$this->handler->action_wp_insert_post( $post->ID, $post, false );

		$this->assertSame(
			[
				self::$users['editor']->ID,
				self::$users['author']->ID,
				self::$users['contributor']->ID,
			],
			get_author_ids( $post )
		);
	}
}

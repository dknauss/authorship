<?php
/**
 * Multisite workflow tests for post saving, capabilities, and author queries on subsites.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use const Authorship\GUEST_ROLE;
use const Authorship\POSTS_PARAM;
use const Authorship\TAXONOMY;

use function Authorship\get_author_ids;
use function Authorship\get_authors;

/**
 * @group ms-required
 */
class TestMultisiteWorkflows extends TestCase {
	/**
	 * Sub site.
	 *
	 * @var \WP_Site
	 */
	protected static $sub_site;

	/**
	 * Set up class test fixtures.
	 *
	 * @param \WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ) {
		parent::wpSetUpBeforeClass( $factory );

		if ( ! is_multisite() ) {
			return;
		}

		self::$sub_site = $factory->blog->create_and_get( [
			'domain'  => 'workflows.example.org',
			'path'    => '/workflows',
			'title'   => 'Authorship Workflows Sub Site',
			'user_id' => self::$users['admin']->ID,
		] );
	}

	/**
	 * Helper to run a callback on the subsite and restore the blog afterward.
	 *
	 * @param callable $callback Callback to run on the subsite.
	 */
	private function on_subsite( callable $callback ) : void {
		switch_to_blog( self::$sub_site->blog_id );
		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		try {
			$callback();
		} finally {
			restore_current_blog();
		}
	}

	public function testPostSavingOnSubsiteSetsAuthors() : void {
		$this->on_subsite( function () : void {
			$post = self::factory()->post->create_and_get( [
				'post_author' => self::$users['admin']->ID,
				POSTS_PARAM   => [
					self::$users['editor']->ID,
					self::$users['author']->ID,
				],
			] );

			$this->assertSame(
				[
					self::$users['editor']->ID,
					self::$users['author']->ID,
				],
				get_author_ids( $post )
			);
		} );
	}

	public function testPostUpdateOnSubsiteRetainsAuthors() : void {
		$this->on_subsite( function () : void {
			$post = self::factory()->post->create_and_get( [
				'post_author' => self::$users['admin']->ID,
				POSTS_PARAM   => [
					self::$users['editor']->ID,
				],
			] );

			wp_update_post( [
				'ID'         => $post->ID,
				'post_title' => 'Updated on subsite',
			], true );

			$this->assertSame( [ self::$users['editor']->ID ], get_author_ids( $post ) );
		} );
	}

	public function testAuthorArchiveOnSubsiteReturnsCorrectPosts() : void {
		$this->on_subsite( function () : void {
			$post = self::factory()->post->create_and_get( [
				'post_author' => self::$users['admin']->ID,
				'post_status' => 'publish',
				POSTS_PARAM   => [
					self::$users['editor']->ID,
				],
			] );

			$author_url = get_author_posts_url( self::$users['editor']->ID );
			$this->go_to( $author_url );

			/** @var \WP_Query */
			global $wp_query;

			$this->assertQueryTrue( 'is_author', 'is_archive' );
			$this->assertTrue( is_author( self::$users['editor']->ID ) );
			$this->assertContains( $post->ID, wp_list_pluck( $wp_query->posts, 'ID' ) );
		} );
	}

	public function testGuestAuthorCanBeAttributedOnSubsite() : void {
		$this->on_subsite( function () : void {
			$post = self::factory()->post->create_and_get( [
				'post_author' => self::$users['admin']->ID,
				POSTS_PARAM   => [
					self::$users[ GUEST_ROLE ]->ID,
				],
			] );

			$authors = get_authors( $post );

			$this->assertCount( 1, $authors );
			$this->assertSame( self::$users[ GUEST_ROLE ]->ID, $authors[0]->ID );
		} );
	}

	public function testMultipleAuthorsOnSubsitePreservesOrder() : void {
		$this->on_subsite( function () : void {
			$ordered_ids = [
				self::$users['contributor']->ID,
				self::$users['author']->ID,
				self::$users['editor']->ID,
			];

			$post = self::factory()->post->create_and_get( [
				'post_author' => self::$users['admin']->ID,
				POSTS_PARAM   => $ordered_ids,
			] );

			$this->assertSame( $ordered_ids, get_author_ids( $post ) );
		} );
	}

	public function testTaxonomyExistsOnSubsite() : void {
		$this->on_subsite( function () : void {
			$this->assertTrue( taxonomy_exists( TAXONOMY ) );
		} );
	}

	public function testEditorCanEditOthersPostsOnSubsite() : void {
		$this->on_subsite( function () : void {
			// Add editor to subsite so they have capabilities here.
			add_user_to_blog( self::$sub_site->blog_id, self::$users['editor']->ID, 'editor' );
			wp_set_current_user( self::$users['editor']->ID );

			$post = self::factory()->post->create_and_get( [
				'post_author' => self::$users['admin']->ID,
				'post_status' => 'publish',
				POSTS_PARAM   => [
					self::$users['author']->ID,
				],
			] );

			$this->assertTrue( current_user_can( 'edit_post', $post->ID ) );
		} );
	}

	public function testAuthorCannotEditOthersPostsOnSubsite() : void {
		$this->on_subsite( function () : void {
			add_user_to_blog( self::$sub_site->blog_id, self::$users['author']->ID, 'author' );
			wp_set_current_user( self::$users['author']->ID );

			$post = self::factory()->post->create_and_get( [
				'post_author' => self::$users['admin']->ID,
				'post_status' => 'publish',
				POSTS_PARAM   => [
					self::$users['editor']->ID,
				],
			] );

			$this->assertFalse( current_user_can( 'edit_post', $post->ID ) );
		} );
	}
}

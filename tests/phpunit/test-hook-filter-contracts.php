<?php
/**
 * Hook and filter contract tests.
 *
 * Verifies that Authorship's public hooks and filters fire with the
 * correct arguments and produce the expected results. These tests
 * exercise the hook contracts as integration points for third-party code.
 *
 * @package authorship
 */

declare( strict_types=1 );

namespace Authorship\Tests;

use WP_Comment;
use WP_Post;
use WP_User;

use const Authorship\GUEST_ROLE;
use const Authorship\POSTS_PARAM;
use const Authorship\TAXONOMY;

use function Authorship\get_authors;
use function Authorship\get_supported_post_types;
use function Authorship\is_post_type_supported;
use function Authorship\set_authors;
use function Authorship\filter_comment_moderation_recipients;
use function Authorship\filter_comment_notification_recipients;
use function Authorship\hide_quickedit_authors;

class TestHookFilterContracts extends TestCase {

	/**
	 * Supported post types filter returns filtered values.
	 */
	public function testSupportedPostTypesFilterAddsCustomType() : void {
		$filter = function ( array $types ) : array {
			$types[] = 'custom_cpt';
			return $types;
		};

		add_filter( 'authorship_supported_post_types', $filter );

		$types = get_supported_post_types();

		remove_filter( 'authorship_supported_post_types', $filter );

		$this->assertContains( 'custom_cpt', $types );
	}

	/**
	 * Supported post types filter can exclude a built-in type.
	 */
	public function testSupportedPostTypesFilterExcludesType() : void {
		$filter = function ( array $types ) : array {
			return array_diff( $types, [ 'post' ] );
		};

		add_filter( 'authorship_supported_post_types', $filter );

		$supported = is_post_type_supported( 'post' );

		remove_filter( 'authorship_supported_post_types', $filter );

		$this->assertFalse( $supported );
	}

	/**
	 * Default author filter receives post object and can override authors.
	 */
	public function testDefaultAuthorFilterReceivesPostAndOverrides() : void {
		$editor = self::$users['editor'];
		$author = self::$users['author'];
		$received_post = null;

		$filter = function ( array $authors, WP_Post $post ) use ( $editor, &$received_post ) : array {
			$received_post = $post;
			return [ $editor->ID ];
		};

		add_filter( 'authorship_default_author', $filter, 10, 2 );

		$post = self::factory()->post->create_and_get( [
			'post_author' => $author->ID,
		] );

		remove_filter( 'authorship_default_author', $filter, 10 );

		$authors = get_authors( $post );

		$this->assertInstanceOf( WP_Post::class, $received_post );
		$this->assertCount( 1, $authors );
		$this->assertSame( $editor->ID, $authors[0]->ID );
	}

	/**
	 * Default author filter returning empty array results in no author assignment.
	 */
	public function testDefaultAuthorFilterEmptyArraySkipsAssignment() : void {
		add_filter( 'authorship_default_author', '__return_empty_array' );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['author']->ID,
		] );

		remove_filter( 'authorship_default_author', '__return_empty_array' );

		$authors = get_authors( $post );

		$this->assertEmpty( $authors );
	}

	/**
	 * Assignment failure action fires with correct arguments when set_authors fails.
	 */
	public function testAssignmentFailureActionFiresWithCorrectArgs() : void {
		$captured = [];

		$callback = function ( int $post_id, WP_Post $post, bool $update, array $author_ids, \Exception $e ) use ( &$captured ) : void {
			$captured = compact( 'post_id', 'post', 'update', 'author_ids', 'e' );
		};

		add_action( 'authorship_author_assignment_failure', $callback, 10, 5 );

		// Create a post with a non-existent user ID to trigger failure.
		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['admin']->ID,
			POSTS_PARAM   => [ 999999 ],
		] );

		remove_action( 'authorship_author_assignment_failure', $callback, 10 );

		$this->assertNotEmpty( $captured, 'Assignment failure action should have fired.' );
		$this->assertSame( $post->ID, $captured['post_id'] );
		$this->assertInstanceOf( WP_Post::class, $captured['post'] );
		$this->assertFalse( $captured['update'] );
		$this->assertSame( [ 999999 ], $captured['author_ids'] );
		$this->assertInstanceOf( \Exception::class, $captured['e'] );
	}

	/**
	 * Comment notification recipients include all attributed authors.
	 */
	public function testCommentNotificationRecipientsIncludeAttributedAuthors() : void {
		$editor = self::$users['editor'];
		$author = self::$users['author'];

		$post = self::factory()->post->create_and_get( [
			'post_author' => $editor->ID,
			POSTS_PARAM   => [ $editor->ID, $author->ID ],
		] );

		$comment_id = self::factory()->comment->create( [
			'comment_post_ID' => $post->ID,
		] );

		$emails = filter_comment_notification_recipients( [], $comment_id );

		$this->assertContains( $editor->user_email, $emails );
		$this->assertContains( $author->user_email, $emails );
	}

	/**
	 * Comment notification recipients deduplicate emails.
	 */
	public function testCommentNotificationRecipientsDeduplicateEmails() : void {
		$editor = self::$users['editor'];

		$post = self::factory()->post->create_and_get( [
			'post_author' => $editor->ID,
			POSTS_PARAM   => [ $editor->ID ],
		] );

		$comment_id = self::factory()->comment->create( [
			'comment_post_ID' => $post->ID,
		] );

		// Pass the editor's email as an existing recipient.
		$emails = filter_comment_notification_recipients(
			[ $editor->user_email ],
			$comment_id
		);

		// Should not duplicate.
		$this->assertCount( 1, $emails );
		$this->assertContains( $editor->user_email, $emails );
	}

	/**
	 * Comment moderation recipients include only authors who can moderate.
	 */
	public function testCommentModerationRecipientsIncludeOnlyModerators() : void {
		$editor = self::$users['editor'];
		$contributor = self::$users['contributor'];

		$post = self::factory()->post->create_and_get( [
			'post_author' => $editor->ID,
			POSTS_PARAM   => [ $editor->ID, $contributor->ID ],
		] );

		$comment_id = self::factory()->comment->create( [
			'comment_post_ID' => $post->ID,
		] );

		$emails = filter_comment_moderation_recipients( [], $comment_id );

		// Editor can moderate, contributor cannot.
		$this->assertContains( $editor->user_email, $emails );
		$this->assertNotContains( $contributor->user_email, $emails );
	}

	/**
	 * Comment notification for a post with no attributed authors returns input unchanged.
	 */
	public function testCommentNotificationNoAuthorsReturnsInput() : void {
		$input = [ 'existing@example.com' ];

		// Create post, then clear its authors.
		add_filter( 'authorship_default_author', '__return_empty_array' );

		$post = self::factory()->post->create_and_get( [
			'post_author' => self::$users['editor']->ID,
		] );

		remove_filter( 'authorship_default_author', '__return_empty_array' );

		$comment_id = self::factory()->comment->create( [
			'comment_post_ID' => $post->ID,
		] );

		$emails = filter_comment_notification_recipients( $input, $comment_id );

		// With no attributed authors, only the original input should remain.
		$this->assertSame( $input, $emails );
	}

	/**
	 * Quick edit authors dropdown is hidden by the filter.
	 */
	public function testHideQuickeditAuthorsReturnsExpectedOptions() : void {
		$result = hide_quickedit_authors( [] );

		$this->assertTrue( $result['hide_if_only_one_author'] );
		$this->assertSame( [ 0 ], $result['include'] );
	}

	/**
	 * Guest author role is registered during bootstrap.
	 */
	public function testGuestAuthorRoleIsRegistered() : void {
		$role = get_role( GUEST_ROLE );

		$this->assertNotNull( $role, 'Guest author role should be registered.' );
		$this->assertEmpty( $role->capabilities, 'Guest author role should have zero capabilities.' );
	}

	/**
	 * Taxonomy is registered and hidden from admin UI.
	 */
	public function testTaxonomyIsRegisteredAndHidden() : void {
		$taxonomy = get_taxonomy( TAXONOMY );

		$this->assertNotFalse( $taxonomy, 'Authorship taxonomy should be registered.' );
		$this->assertFalse( $taxonomy->public, 'Taxonomy should not be public.' );
		$this->assertFalse( $taxonomy->show_ui, 'Taxonomy should not show UI.' );
	}

	/**
	 * Guest author has zero capabilities — cannot edit, delete, or publish.
	 */
	public function testGuestAuthorHasZeroCaps() : void {
		$guest = self::$users[ GUEST_ROLE ];

		$this->assertFalse( user_can( $guest->ID, 'edit_posts' ) );
		$this->assertFalse( user_can( $guest->ID, 'delete_posts' ) );
		$this->assertFalse( user_can( $guest->ID, 'publish_posts' ) );
		$this->assertFalse( user_can( $guest->ID, 'read' ) );
	}

	/**
	 * The create_guest_authors cap maps to edit_others_posts by default.
	 */
	public function testCreateGuestAuthorsCapDefaultMapping() : void {
		// Editor has edit_others_posts, so should have create_guest_authors.
		$this->assertTrue( user_can( self::$users['editor']->ID, 'create_guest_authors' ) );

		// Author does not have edit_others_posts.
		$this->assertFalse( user_can( self::$users['author']->ID, 'create_guest_authors' ) );

		// Guest author has zero caps.
		$this->assertFalse( user_can( self::$users[ GUEST_ROLE ]->ID, 'create_guest_authors' ) );
	}

	/**
	 * The attribute_post_type cap requires a valid post type argument.
	 */
	public function testAttributePostTypeCapRequiresValidPostType() : void {
		$editor_id = self::$users['editor']->ID;

		// Valid post type.
		$this->assertTrue( user_can( $editor_id, 'attribute_post_type', 'post' ) );

		// Non-existent post type.
		$this->assertFalse( user_can( $editor_id, 'attribute_post_type', 'nonexistent_cpt' ) );
	}
}

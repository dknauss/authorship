<?php
/**
 * Byline Feed — Byline spec namespace output for RSS2 and Atom feeds.
 *
 * Adds structured author metadata to feeds using the Byline specification
 * (https://bylinespec.org/1.0). This is additive — standard feed elements
 * remain untouched for backward compatibility.
 *
 * @package authorship
 * @see https://bylinespec.org
 */

declare( strict_types=1 );

namespace Authorship\BylineFeed;

use WP_Post;
use WP_User;

use function Authorship\get_authors;

use const Authorship\GUEST_ROLE;

const BYLINE_NS = 'https://bylinespec.org/1.0';

/**
 * Maximum length for the byline:context element (bio excerpt).
 */
const CONTEXT_MAX_LENGTH = 280;

/**
 * Registers feed hooks for Byline namespace output.
 */
function bootstrap() : void {
	// RSS2.
	add_action( 'rss2_ns', __NAMESPACE__ . '\\render_namespace' );
	add_action( 'rss2_head', __NAMESPACE__ . '\\render_contributors' );
	add_action( 'rss2_item', __NAMESPACE__ . '\\render_item_authors' );

	// Atom.
	add_action( 'atom_ns', __NAMESPACE__ . '\\render_namespace' );
	add_action( 'atom_head', __NAMESPACE__ . '\\render_contributors' );
	add_action( 'atom_entry', __NAMESPACE__ . '\\render_item_authors' );
}

/**
 * Outputs the Byline XML namespace declaration.
 *
 * Hooked to `rss2_ns` and `atom_ns`.
 */
function render_namespace() : void {
	echo 'xmlns:byline="' . esc_url( BYLINE_NS ) . '"' . "\n";
}

/**
 * Outputs channel-level `<byline:contributors>` with a `<byline:person>` for
 * each author who contributed to posts in the current feed.
 *
 * Hooked to `rss2_head` and `atom_head`.
 */
function render_contributors() : void {
	global $posts;

	if ( empty( $posts ) || ! is_array( $posts ) ) {
		return;
	}

	$seen  = [];
	$users = [];

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		foreach ( get_authors( $post ) as $user ) {
			if ( isset( $seen[ $user->ID ] ) ) {
				continue;
			}

			$seen[ $user->ID ] = true;
			$users[]           = $user;
		}
	}

	if ( empty( $users ) ) {
		return;
	}

	echo "\t\t<byline:contributors>\n";

	foreach ( $users as $user ) {
		render_person( $user );
	}

	echo "\t\t</byline:contributors>\n";
}

/**
 * Outputs a single `<byline:person>` element.
 *
 * @param WP_User $user The user to render.
 */
function render_person( WP_User $user ) : void {
	$id      = get_person_id( $user );
	$name    = $user->display_name;
	$bio     = get_user_meta( $user->ID, 'description', true );
	$url     = $user->user_url;
	$avatar  = get_avatar_url( $user->ID );

	echo "\t\t\t<byline:person id=\"" . esc_attr( $id ) . "\">\n";
	echo "\t\t\t\t<byline:name>" . esc_html( $name ) . "</byline:name>\n";

	if ( is_string( $bio ) && '' !== $bio ) {
		$context = mb_substr( $bio, 0, CONTEXT_MAX_LENGTH );
		echo "\t\t\t\t<byline:context>" . esc_html( $context ) . "</byline:context>\n";
	}

	if ( '' !== $url ) {
		echo "\t\t\t\t<byline:url>" . esc_url( $url ) . "</byline:url>\n";
	}

	if ( is_string( $avatar ) && '' !== $avatar ) {
		echo "\t\t\t\t<byline:avatar>" . esc_url( $avatar ) . "</byline:avatar>\n";
	}

	echo "\t\t\t</byline:person>\n";
}

/**
 * Outputs per-item `<byline:author>` refs and `<byline:role>` elements.
 *
 * Hooked to `rss2_item` and `atom_entry`.
 */
function render_item_authors() : void {
	$post = get_post();

	if ( ! $post ) {
		return;
	}

	$authors = get_authors( $post );

	if ( empty( $authors ) ) {
		return;
	}

	foreach ( $authors as $user ) {
		$id   = get_person_id( $user );
		$role = get_byline_role( $user, $post );

		echo "\t\t<byline:author ref=\"" . esc_attr( $id ) . "\"/>\n";
		echo "\t\t<byline:role>" . esc_html( $role ) . "</byline:role>\n";
	}
}

/**
 * Returns a stable identifier for a user within the feed.
 *
 * @param WP_User $user The user.
 * @return string The person ID (user_nicename / slug).
 */
function get_person_id( WP_User $user ) : string {
	return $user->user_nicename;
}

/**
 * Maps a WordPress user role to a Byline spec role.
 *
 * @param WP_User $user The user.
 * @param WP_Post $post The post context.
 * @return string Byline role: 'guest', 'staff', or 'contributor'.
 */
function get_byline_role( WP_User $user, WP_Post $post ) : string {
	if ( in_array( GUEST_ROLE, (array) $user->roles, true ) ) {
		$role = 'guest';
	} elseif ( user_can( $user, 'edit_others_posts' ) ) {
		$role = 'staff';
	} else {
		$role = 'contributor';
	}

	/**
	 * Filters the Byline role for a user on a given post.
	 *
	 * @param string  $role The computed Byline role.
	 * @param WP_User $user The user.
	 * @param WP_Post $post The post.
	 */
	return (string) apply_filters( 'authorship_byline_role', $role, $user, $post );
}

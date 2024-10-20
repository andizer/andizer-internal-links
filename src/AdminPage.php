<?php

namespace Andizer\Plugin\YoastInternalLinks;

class AdminPage {
	public const SECTION = 'andizer-internal-linking';

	public function register() {
		\add_action( 'admin_menu', $this );
	}

	public function __invoke() {
		\add_submenu_page(
			'tools.php',
			'Yoast internal links',
			'Yoast internal links',
			'manage_options',
			self::SECTION,
			[ $this, 'show_menu_page' ]
		);
	}

	public function show_menu_page(): void {
		echo '<div class="wrap">';
		echo '<h2>Yoast internal links</h2>';

		$post = $this->get_post();
		if ( $post ) {
			$this->render_page($post);
		}
		else {
			$this->render_links_without_indexables_page();
		}

		echo '</div>';
	}

	private function get_post() {
		if ( empty( $_GET['post'] )  ) {
			return null;
		}

		$post_id = \sanitize_text_field( \wp_unslash( $_GET['post'] ) );
		if ( empty( $post_id ) ) {
			return null;
		}

		return \get_post($post_id);
	}

	private function render_page( $post ) {
		if ( empty( $post ) ) {
			echo '<div class="notice notice-error"><p>There is no post found or given.</p></div>';

			return;
		}

		$incoming = $this->get_incomping_links($post->ID);
		$outgoing = $this->get_outgoing_links($post->ID);

		echo '<p>Internal linking for: ' . \esc_html( $post->post_title ) . '</p>';

		echo '<h3>Incoming links</h3>';
		if ( !empty ( $incoming ) ) {
			echo "<p>In the pages below there are incoming links to this page. Clicking the links below will navigate to the post edit page.</p>";
			echo '<ul class="ul-disc">';
			foreach ($incoming as $link) {
				$link = \sprintf(
					'<a href="%s">%s</a>',
					\admin_url(
						\sprintf('post.php?post=%s&action=edit', \esc_attr( $link->ID ) )
					),
					! empty( $link->post_title) ? \esc_html( $link->post_title ) : "<em>No title set</em>"
				);

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in assignment.
				echo "<li>" . $link . "</li>";
			}
			echo '</ul>';
		} else {
			echo '<p>No incoming links found.</p>';
		}

		echo '<h3>Outgoing links</h3>';
		if ( ! empty( $outgoing ) ) {
			echo "<p>This page is linking to the following pages.</p>";
			echo '<ul class="ul-disc">';
			foreach ($outgoing as $link) {
				echo "<li>". \esc_html( $link->post_title ) . "</li>";
			}
			echo '</ul>';
		} else {
			echo '<p>No outgoing links found.</p>';
		}

		echo '<hr />';
	}

	private function get_incomping_links( $post_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_title, p.ID FROM {$wpdb->prefix}yoast_seo_links y JOIN $wpdb->posts p ON p.ID=y.post_id WHERE y.target_post_id = %d GROUP BY y.post_id",
				$post_id,
			)
		);
	}

	private function get_outgoing_links( $post_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_title, p.ID, y.post_id  FROM {$wpdb->prefix}yoast_seo_links y JOIN $wpdb->posts p ON p.ID=y.target_post_id WHERE y.post_id = %d GROUP BY y.target_post_id",
				$post_id,
			)
		);
	}


	private function render_links_without_indexables_page(): void {
		$links = $this->get_links_without_indexable();

		if ( empty($links)) {
			echo '<div class="notice notice-error"><p>There is no internal links referring to a non-indexable page.</p></div>';

			return;
		}

		echo '<h3>Links without links</h3>';
		echo "<p>The following links don't have an indexable, possibly some of them are referring to non-existing pages.</p>";
		echo '<ul class="ul-disc">';
		foreach ($links as $link) {
			$target_link = \sprintf(
				'<a href="%s" target="_blank">%s</a>', $link->url, \esc_html( $link->url )
			);

			$edit_link = \sprintf(
				'<a href="%s">Edit: %s</a>',
				\admin_url(
					\sprintf('post.php?post=%s&action=edit', \esc_attr( $link->post_id ) )
				),
				! empty( $link->post_title) ? \esc_html( $link->post_title ) : "<em>No title set</em>"
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in assignment.
			echo "<li>" . $target_link . " - " . $edit_link . "</li>";
		}
		echo '</ul>';

	}

	private function get_links_without_indexable() {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare("
				SELECT y.url, y.post_id, p.post_title
				FROM {$wpdb->prefix}yoast_seo_links y 
				JOIN {$wpdb->posts} p ON p.ID = y.post_id 
				WHERE y.target_post_id IS NULL AND y.target_indexable_id IS NULL AND y.url LIKE %s GROUP BY y.url",
				$wpdb->esc_like( \home_url() ) . "%"
			)
		);
	}
}
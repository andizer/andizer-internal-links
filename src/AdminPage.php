<?php

namespace Andizer\Plugin\YoastInternalLinks;

class AdminPage {
	public const SECTION = 'andizer-internal-linking';

	public function register() {
		\add_action( 'admin_menu', $this );
	}

	public function __invoke() {
		\add_submenu_page(
			( !empty( $_GET['post']) ) ? 'tools.php' : null,
			'Yoast internal links',
			'Yoast internal links',
			'manage_options',
			self::SECTION,
			[ $this, 'show_menu_page' ]
		);
	}

	public function show_menu_page(): void {
		$post = $this->get_post();

		echo '<div class="wrap">';
		echo '<h2>Yoast internal links</h2>';
		$this->render_page($post);
		echo '</div>';
	}

	private function get_post() {
		if ( empty( $_GET['post'] )  ) {
			return null;
		}

		$post_id = \sanitize_text_field($_GET['post']);
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
}
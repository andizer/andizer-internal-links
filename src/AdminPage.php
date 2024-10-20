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
			/* translators: %s expands to Andizer */
			\sprintf( __( '%s internal links', 'andizer-internal-links' ), 'Andizer'),
			/* translators: %s expands to Andizer */
			\sprintf( __( '%s internal links', 'andizer-internal-links' ), 'Andizer'),
			'manage_options',
			self::SECTION,
			[ $this, 'show_menu_page' ]
		);
	}

	public function show_menu_page(): void {
		echo '<div class="wrap">';

		/* translators: %s expands to Andizer */
		echo '<h2>' . \sprintf( \esc_html__( '%s internal links', 'andizer-internal-links' ), 'Andizer' ) . '</h2>';

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

		/* translators: %s expands to the post title */
		echo '<p>' . \sprintf( \esc_html__( 'Internal linking for: %s', 'andizer-internal-links' ), \esc_html( $post->post_title ) ) . '</p>';

		echo '<h3>' . \esc_html__( 'Incoming links', 'andizer-internal-links' ) . '</h3>';
		if ( !empty ( $incoming ) ) {
			echo '<p>' . \esc_html__( 'In the pages below there are incoming links to this page. Clicking the links below will navigate to the post edit page.', 'andizer-internal-links' ) . '</p>';
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
			echo '<p>' . \esc_html__( 'No incoming links found.', 'andizer-internal-links' ) . '</p>';
		}

		echo '<h3>' . \esc_html__( 'Outgoing links', 'andizer-internal-links' ) . '</h3>';
		if ( ! empty( $outgoing ) ) {
			echo '<p>' . \esc_html__( 'This page is linking to the following pages.', 'andizer-internal-links' ) . '</p>';
			echo '<ul class="ul-disc">';
			foreach ($outgoing as $link) {
				echo "<li>". \esc_html( $link->post_title ) . "</li>";
			}
			echo '</ul>';
		} else {
			echo '<p>' . \esc_html__( 'No outgoing links found.', 'andizer-internal-links' ) . '</p>';
		}
	}

	private function get_incomping_links( $post_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,  WordPress.DB.DirectDatabaseQuery.NoCaching -- This is intended because of custom table call, also actual data is needed here, therefore no cache is used.
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_title, p.ID FROM {$wpdb->prefix}yoast_seo_links y 
			    JOIN $wpdb->posts p ON p.ID=y.post_id 
              	WHERE y.target_post_id = %d GROUP BY y.post_id",
				$post_id,
			)
		);
	}

	private function get_outgoing_links( $post_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,  WordPress.DB.DirectDatabaseQuery.NoCaching -- This is intended because of custom table call, also actual data is needed here, therefore no cache is used.
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_title, p.ID, y.post_id  
				FROM {$wpdb->prefix}yoast_seo_links y 
			    JOIN $wpdb->posts p ON p.ID=y.target_post_id 
				WHERE y.post_id = %d AND y.target_indexable_id IS NOT NULL GROUP BY y.target_post_id",
				$post_id,
			)
		);
	}

	private function render_links_without_indexables_page(): void {
		$links = $this->get_links_without_indexable();

		if ( empty($links)) {
			echo '<div class="notice notice-error"><p>' . \esc_html__('There are no internal links referring to a non-indexable page', 'andizer-internal-links' ) . '</p></div>';

			return;
		}

		echo '<h3>' . \esc_html__( 'Links without indexable', 'andizer-internal-links' ) . '</h3>';
		echo '<p>' . \esc_html__( 'The following links don\'t have an indexable, possibly some of them are referring to non-existing pages.', 'andizer-internal-links' ) . '</p>';
		echo '<ul class="ul-disc">';
		foreach ($links as $link) {
			$target_link = \sprintf(
				'<a href="%s" target="_blank">%s</a>', $link->url, \esc_html( $link->url )
			);

			$edit_link = \sprintf(
				'<a href="%s">%s: %s</a>',
				\admin_url(
					\sprintf('post.php?post=%s&action=edit', \esc_attr( $link->post_id ) )
				),
				\esc_html__( 'Edit', 'andizer-internal-links'),
				! empty( $link->post_title) ? \esc_html( $link->post_title ) : "<em>No title set</em>"
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in assignment.
			echo "<li>" . $target_link . " - " . $edit_link . "</li>";
		}
		echo '</ul>';

	}

	private function get_links_without_indexable() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,  WordPress.DB.DirectDatabaseQuery.NoCaching -- This is intended because of custom table call, also actual data is needed here, therefore no cache is used.
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

<?php

namespace Andizer\Plugin\YoastInternalLinks;

class RowActions {
	public function register() {
		$post_types = \get_post_types( ['public' => true] );

		foreach ($post_types as $post_type) {
			\add_filter($post_type . '_row_actions', $this, 10, 2);
		}
	}

	public function __invoke(array $actions, $post): array {
		if ($post->post_status !== 'publish') {
			return $actions;
		}

		$actions['internal-linking'] = \sprintf(
			'<a href="%s">%s</a>',
			\esc_url(
				\admin_url(
					'tools.php?page=' . AdminPage::SECTION . '&post=' . $post->ID,
				)
			),
			\__( 'Internal linking', 'andizer-internal-links' )
		);

		return $actions;
	}
}

<?php

namespace Tarosky\FeedGenerator\Service;

use \Tarosky\FeedGenerator\AbstractFeed;
use Tarosky\FeedGenerator\DeliveryManager;

class Gunosy extends AbstractFeed {

	protected function get_query_arg( ) {
		$dm = DeliveryManager::instance();
		$id = $this->get_id();

		return [
			'feed'			=> 'rss2',
			'posts_per_rss'	=> $this->per_page,
			'post_type'		=> 'post',
			'post_status'	=> 'publish',
			'orderby'		=> [
				'date'		=> 'DESC',
			],
			'meta_query'	=> [
				[
					'key'		=> $dm->get_meta_name(),
					'value'		=> sprintf('"%s"', $id),
					'compare'	=> 'REGEXP',
				]
			],
		];
	}

	protected function render_item($post) {
		
	}

}

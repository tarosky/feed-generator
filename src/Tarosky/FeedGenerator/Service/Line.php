<?php

namespace Tarosky\FeedGenerator\Service;

use \Tarosky\FeedGenerator\AbstractFeed;
use Tarosky\FeedGenerator\DeliveryManager;

/**
 * LINE用RSS
 * 
 */
class Line extends AbstractFeed {

	protected function get_query_arg( ) {
		$dm = DeliveryManager::instance();
		$id = $this->get_id();

		return [
			'feed'			=> 'line',
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

	/**
	 * クエリの上書き
	 *
	 * @param \WP_Query $wp_query
	 */
	public function pre_get_posts( \WP_Query &$wp_query ) {
		/**
		 * CHANNEL
		 */
		//	lastBuildDateの時間をローカルタイムゾーンに合わせる
		add_filter('get_feed_build_date', function( $max_modified_time, $format ){
			return $this->to_local_time( $max_modified_time, $format, 'Asia/Tokyo', true );
		}, 10, 2);

		/**
		 * ITEM
		 */
		//	guidタグ内容を編集
		add_filter('the_guid', function($guid, $id){
			return get_permalink($id);
		}, 10, 2);
		
		if( $args = $this->get_query_arg() ) {
			foreach( $args as $key => $val ) {
				$wp_query->set($key, $val);
			}
		}
		add_action( 'do_feed_line', [ $this, 'do_feed' ] );
	}

	/**
	 * フィルター・フックで対応しきれない場合はfeed全体を作り直す
	 * rss2.0をベースに作成
	 */
	public function do_feed( ) {
		$this->xml_header( );

		do_action( 'rss_tag_pre', 'rss2' );
		?>
		<rss version="2.0" 
			xmlns:oa="http://news.line.me/rss/1.0/oa"
			<?php
			do_action( 'rss2_ns' );
			?>
		>

		<channel>
			<title><![CDATA[<?php wp_title_rss(); ?>]]></title>
			<link><?php bloginfo_rss( 'url' ); ?></link>
			<description><![CDATA[<?php bloginfo_rss( 'description' ); ?>]]></description>
			<lastBuildDate><?php echo get_feed_build_date( 'r' ); ?></lastBuildDate>
			<language><?php bloginfo_rss( 'language' ); ?></language>
			<?php
			do_action( 'rss2_head' );

			while ( have_posts() ) :
				the_post();
				$this->render_item($post);
				?>
			<?php endwhile; ?>
		</channel>
		</rss>
		<?php
	}
	
	protected function render_item($post) {
		$content = get_the_content_feed( 'rss2' );

		?>
			<item>
				<guid><?php the_guid(); ?></guid>
				<title><![CDATA[<?php the_title_rss(); ?>]]></title>
				<link><?php the_permalink_rss(); ?></link>
				<description><![CDATA[<?php echo $content; ?>]]></description>
				<?php rss_enclosure(); ?>
				<pubDate><?php echo $this->to_local_time( get_the_time( 'Y-m-d H:i:s' ), 'r', 'Asia/Tokyo' ) ?></pubDate>


				<oa:lastPubDate><?php echo $this->to_local_time( get_post_modified_time( 'Y-m-d H:i:s' ), 'r', 'Asia/Tokyo' ) ?></oa:lastPubDate>
				<oa:pubStatus>2</oa:pubStatus>
				
				<?php
					do_action( 'rss2_item', [$this, 'rss_add_item'] );
				?>

			</item>

		<?php
	}
	
}

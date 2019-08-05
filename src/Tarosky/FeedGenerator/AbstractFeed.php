<?php
/**
 * RSS基本動作抽象クラス
 */

namespace Tarosky\FeedGenerator;

use Tarosky\FeedGenerator\Model\Singleton;

/**
 * RSS用基盤抽象クラス
 */
abstract class AbstractFeed extends Singleton {

	// 記事ごとの表示確認識別ID.
	protected $id = '';
	// サービスごとの表示名.
	protected $label = '';
	// 表示件数.
	protected $per_page = 20;

	/**
	 * Feedを作り出す条件を指定する
	 *
	 * @return array \WP_Query に渡す配列
	 */
	abstract protected function get_query_arg();

	/**
	 * Feedの一つ一つのアイテムを生成して返す
	 *
	 * @param \WP_Post $post 投稿記事.
	 *  @return string
	 */
	abstract protected function render_item( $post );

	/**
	 * GMTの日付を特定のタイムゾーンに合わす
	 *
	 * @param string $date 日付.
	 * @param string $format フォーマット.
	 * @param string $local_timezone 指定タイムゾーン デフォルトは設定タイムゾーン.
	 * @param boolean $is_gmt 引き渡す日付がgmtか？
	 * @return type
	 */
	protected function to_local_time( $date, $format, $local_timezone = '', $is_gmt = false ) {
		if ( $local_timezone ) {
			$local_timezone = get_option( 'timezone_string' );
		}

		if ( $is_gmt ) {
			$date = new \DateTime( $date );
			$date->setTimeZone( new \DateTimeZone( $local_timezone ) );
			return $date->format( $format );
		} 
		else {
			$date = new \DateTime( $date, new \DateTimeZone( $local_timezone ) );
			return $date->format( $format );
		}
	}

	/**
	 * XMLヘッダーを吐く
	 *
	 * @param int $hours 期限, デフォルトは1時間.
	 */
	protected function xml_header( $hours = 1 ) {
		if ( $hours ) {
			$this->expires_header( $hours );
		}
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
		echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?>' . "\n";
	}

	/**
	 * Expiresヘッダーを吐く
	 *
	 * @param int $hours 期限, デフォルトは1時間.
	 */
	protected function expires_header( $hours = 1 ) {
		$time = current_time( 'timestamp', true ) + 60 * 60 * $hours;
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', $time ) . ' GMT' );
	}

	/**
	 * IDを取得する。
	 *
	 * @return string
	 */
	public function get_id() {
		$class_name = explode( '\\', get_called_class() );
		return $this->id ? $this->id : strtolower( end( $class_name ) );
	}

	/**
	 * サービスの表示名を取得する。各サービスでlabelに指定がされている場合はそれを使用。無ければクラス名が使われる。
	 *
	 * @return string
	 */
	public function get_label() {
		$class_name = explode( '\\', get_called_class() );
		return $this->label ? $this->label : end( $class_name );
	}

	/**
	 * クエリの上書き
	 *
	 * @param \WP_Query $wp_query クエリ.
	 */
	public function pre_get_posts( \WP_Query &$wp_query ) {
		$args = $this->get_query_arg();
		if ( $args ) {
			foreach ( $args as $key => $val ) {
				$wp_query->set( $key, $val );
			}
		}
	}
}

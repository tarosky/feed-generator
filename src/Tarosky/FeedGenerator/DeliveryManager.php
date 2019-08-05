<?php

namespace Tarosky\FeedGenerator;

use Tarosky\FeedGenerator\RouterManager;
use Tarosky\FeedGenerator\AbstractFeed;
use Tarosky\FeedGenerator\Model\Singleton;

class DeliveryManager extends Singleton {
	protected $router = null;

	protected $meta_name = 'trs_feed_selected';
	protected $services = [];

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings ) {
		$this->router = new RouterManager();

		add_action( 'admin_menu', [$this, 'set_admin_menu'] );
		add_action( 'save_post', [$this, 'save_custom_postdata']);
		add_action( 'pre_get_posts', [$this, 'pre_get_posts'] );
	}
	
	/**
	 * Feed表示情報を保存してあるmeta_nameを返す。
	 * 
	 * @return string
	 */
	public function get_meta_name() {
		return $this->meta_name;
	}

	/**
	 * admin_menu時のフック
	 */
	public function set_admin_menu() {
		add_meta_box( 
			'trs_feed_checkbox',
			__( 'Feed Check', 'trs_feed' ),
			[$this, 'callback_checkbox_template'],
			'post',
			'side',
			'high'
		);

/*
		$options = [];
		foreach( $this->get_services() as $service_name => $service_id ) {
			$class_name = "Tarosky\\FeedGenerator\\Service\\".$service_name;
			if(class_exists($class_name) ) {
				$instance = $class_name::instance();
				$id = $instance->get_id();
				$label = $instance->get_label();

				$options[$id] = $label;
			}
		}

		$cmb = new_cmb2_box( array(
			'id'            => 'trs_feed_checkbox',
			'title'         => __( 'Feed Check', 'trs_feed' ),
			'object_types'  => array( 'post', ), // Post type
			'context'       => 'side',
			'priority'      => 'default',
			'show_names'    => false,
		) );

		$options = [];
		foreach( $this->get_services() as $service_name => $service_id ) {
			$class_name = "Tarosky\\FeedGenerator\\Service\\".$service_name;
			if(class_exists($class_name) ) {
				$instance = $class_name::instance();
				$id = $instance->get_id();
				$label = $instance->get_label();

				$options[$id] = $label;
			}
		}

		// Regular text field
		$cmb->add_field( array(
			'name'       => __( 'Feed Check', 'trs_feed' ),
			'id'         => $this->meta_name,
			'type'       => 'multicheck',
			'options'	 => $options,
			'default_cb' => [$this, 'feed_check_default_cb'],
		) );
 * 
 */
	}
	
	/**
	 * チェックボックテンプレート
	 */
	public function callback_checkbox_template() {
		$options = [];
		foreach( $this->get_services() as $service_name => $service_id ) {
			$class_name = "Tarosky\\FeedGenerator\\Service\\".$service_name;
			if(class_exists($class_name) ) {
				$instance = $class_name::instance();
				$id = $instance->get_id();
				$label = $instance->get_label();

				$options[$id] = $label;
			}
		}

		$meta_name = $this->meta_name;

		$id = get_the_ID();
		//カスタムフィールドの値を取得
		//値がなければarray()を設定する
		$checked_list = get_post_meta($id, $meta_name, true);
		$checked_list = $checked_list ? $checked_list : array();

		foreach($options as $id => $label){
			$is_checkd = '';
			if( false !== array_search($id, $checked_list) ) { 
				$is_checkd = 'checked';
			}

			echo <<<EOS
	<div>
		<label>
			<input type="checkbox" name="{$meta_name}[]" value="{$id}" {$is_checkd}>{$label}
		</label>
	</div>
EOS;
  }

	}
	
	/**
	 * 記事保存時にチェックボックス内容を保存する
	 * 
	 * @param int $post_id
	 */
	function save_custom_postdata($post_id){
		$meta_name = $this->meta_name;
		
		//入力した値(postされた値)
		$set_data = isset($_POST[$meta_name]) ? $_POST[$meta_name]: null;

		//DBに登録してあるデータ
		$seted_data = get_post_meta($post_id, $meta_name, true);
		
		if($set_data) {
			update_post_meta($post_id, $meta_name, $set_data);
		} else {
			delete_post_meta($post_id, $meta_name, $seted_data);
		}
	}

	/**
	 * チェックボックスデフォルト値コールバック
	 * 
	 * @return array
	 */
	public function feed_check_default_cb() {
		global $pagenow;

		$default_check_list = [];
		if( $pagenow == 'post-new.php' ) {
			$post_id = get_the_ID();
			foreach( $this->get_services() as $service_name => $service_id ) {
				$class_name = "Tarosky\\FeedGenerator\\Service\\".$service_name;
				if(class_exists($class_name) ) {
					$instance = $class_name::instance();
					$id = $instance->get_id();

					$default_check_list[] = $id;
				}
			}
		}

		return $default_check_list;
	}

	/**
	 * サービス一覧の取得
	 * 
	 * @return array
	 */
	public function get_services() {
		
		if( !$ret_services = $this->services ) {
			$dir = sprintf( '%s%s', plugin_dir_path(__FILE__), 'Service' );
			if( is_dir($dir) ) {
				$this->services = [];
				foreach( scandir($dir) as $service_file ) {
					if ( ! preg_match( '/^([^\\.].*)\.php$/', $service_file, $match ) ) {
						continue;
					}
					$service = $match[1];
					$class_name = "Tarosky\\FeedGenerator\\Service\\".$service;
					if(class_exists($class_name)) {
						$instance = $class_name::instance();
						$id = $instance->get_id();

						$this->services[$service] = $id;
					}
				}
				$ret_services = $this->services;
			}
		}

		return $ret_services;
	}

	/**
	 * 現在対象とされているClass名を返す
	 * 
	 * @param string
	 * @return string
	 */
	public function get_class_name( $feed_id ) {
		$class_name = '';
		
		foreach ( $this->get_services() as $service_name => $service_id ) {
			if( $service_id == $feed_id ) {
				$class_name = $service_name;
			}
		}
		
		return $class_name;
	}

	/**
	 * クエリの上書き
	 *
	 * @param \WP_Query $wp_query
	 */
	public function pre_get_posts( &$wp_query ) {
		if( $wp_query->is_main_query() && $feed_id = $this->router->get_feed_id($wp_query) ) {
			if( $class_name = $this->get_class_name($feed_id) ) {
				$class = "Tarosky\\FeedGenerator\\Service\\".$class_name;
				$instance = $class::instance();
				$instance->pre_get_posts( $wp_query );
			}
		}
	}

}

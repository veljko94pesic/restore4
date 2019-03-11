<?php
/**
 * Class WPML_Media_Set_Initial_Language
 */
class WPML_Media_Set_Initial_Language implements IWPML_Action {
	/**
	 * @var wpdb
	 */
	private $wpdb;
	/**
	 * @var string
	 */
	private $language;

	/**
	 * WPML_Media_Set_Initial_Language constructor.
	 *
	 * @param wpdb $wpdb
	 * @param string $language
	 */
	public function __construct( wpdb $wpdb, $language ) {
		$this->wpdb      = $wpdb;
		$this->language = $language;
	}

	public function add_hooks() {
		add_action( 'wp_ajax_wpml_media_set_initial_language', array( $this, 'set' ) );
	}

	public function set() {

		$this->update_db();

		$message = __( 'Setting language to media: done!', 'wpml-media' );

		wp_send_json_success( array(
			'message' => $message
		) );

	}

	public function update_db(){
		add_option('wpml_prefix_hash', rand(1, 999), '', 'no');
		add_option('wpml_trid_count', 1, '', 'no');

		$sql          = "SELECT ID" . PHP_EOL;
		$sql          .= "FROM {$this->wpdb->posts} p" . PHP_EOL;
		$sql          .= "LEFT JOIN {$this->wpdb->prefix}icl_translations t" . PHP_EOL;
		$sql          .= "ON p.ID = t.element_id AND t.element_type = 'post_attachment'" . PHP_EOL;
		$sql          .= "WHERE post_type = 'attachment' AND t.translation_id IS NULL";
		$results      = $this->wpdb->get_col( $sql );

		if ( $results ) {
			$query = "INSERT INTO {$this->wpdb->prefix}icl_translations ";
			$query .= "(`element_type`, `element_id`, `trid`, `language_code`, `source_language_code`) VALUES ";
		
			$first = true;
			$count = get_option('wpml_trid_count');
			$hash = get_option('wpml_prefix_hash');
			foreach ( $results as $id ) {
				$trid = $hash * 100000 + $count;
				if ( ! $first ) {
					$query .= ',';
				}
				$query .= $this->wpdb->prepare(
					'(%s, %d, %d, %s, NULL)',
					'post_attachment', $id, $trid, $this->language
				);
				$count++;
				$first = false;
			}
			$this->wpdb->query( $query );
			update_option( 'wpml_trid_count', $count, 'no' );
		}
	}
}
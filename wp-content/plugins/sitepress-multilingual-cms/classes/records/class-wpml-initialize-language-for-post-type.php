<?php

class WPML_Initialize_Language_For_Post_Type {

	private $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function run( $post_type, $default_language ) {
		add_option('wpml_prefix_hash', rand(1, 999), '', 'no');
		add_option('wpml_trid_count', 1, '', 'no');

		do {
			$sql          = "SELECT p.ID" . PHP_EOL;
			$sql          .= "FROM {$this->wpdb->posts} p" . PHP_EOL;
			$sql          .= "LEFT OUTER JOIN {$this->wpdb->prefix}icl_translations t" . PHP_EOL;
			$sql          .= "ON t.element_id = p.ID AND t.element_type = CONCAT('post_', p.post_type)" . PHP_EOL;
			$sql          .= "WHERE p.post_type = %s AND t.translation_id IS NULL" . PHP_EOL;
			$sql          .= "LIMIT 50";
			$sql_prepared = $this->wpdb->prepare( $sql, array( $post_type ) );
			$results      = $this->wpdb->get_col( $sql_prepared );

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
						'post_' . $post_type, $id, $trid, $default_language
					);
                    $count++;
					$first = false;
				}
				$this->wpdb->query( $query );
				update_option( 'wpml_trid_count', $count, 'no' );
			}
		} while ( $results && ! $this->wpdb->last_error );

		return $results === 0 && ! $this->wpdb->last_error;
	}
}

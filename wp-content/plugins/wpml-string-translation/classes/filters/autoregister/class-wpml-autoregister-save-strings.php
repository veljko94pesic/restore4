<?php

class WPML_Autoregister_Save_Strings {
	const INSERT_CHUNK_SIZE = 200;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var SitePress $sitepress
	 */
	private $sitepress;

	/**
	 * @var array
	 */
	private $data = array();

	/**
	 * @var WPML_Language_Of_Domain
	 */
	private $lang_of_domain;

	/**
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 * @param WPML_Language_Of_Domain $language_of_domain
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress, WPML_Language_Of_Domain $language_of_domain = null ) {
		$this->wpdb = $wpdb;
		$this->sitepress = $sitepress;
		$this->lang_of_domain = $language_of_domain ? $language_of_domain : new WPML_Language_Of_Domain( $this->sitepress );

		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	/**
	 * @param $value
	 * @param $name
	 * @param $domain
	 * @param string $gettext_context
	 */
	public function save( $value, $name, $domain, $gettext_context = '' ) {
		$this->data[] = array(
			'value'           => $value,
			'name'            => $name,
			'domain'          => $domain,
			'gettext_context' => $gettext_context,
		);
	}

	/**
	 * @param $name
	 * @param $domain
	 *
	 * @return string
	 */
	public function get_source_lang( $name, $domain ) {
		$domain_lang    = $this->lang_of_domain->get_language( $domain );

		if ( ! $domain_lang ) {
			$flag = 0 === strpos( $domain, 'admin_texts_' ) || 'Tagline' === $name || 'Blog Title' === $name;
			$domain_lang = $flag ? $this->sitepress->get_user_admin_language( get_current_user_id() ) : 'en';
		}

		return $domain_lang;
	}

	private function persist() {
		$query = "SELECT domain_name_context_md5 FROM {$this->wpdb->prefix}icl_strings";
		$db_strings = $this->wpdb->get_col($query);
		$strings = $this->data;

		// Remove duplicates
		$strings = array_map("unserialize", array_unique(array_map("serialize", $strings)));

		// Remove duplicates that exists in database
		$filtered = array_filter($strings, function($string) use ($db_strings) {
			return !in_array($string['name'], $db_strings);
		});

		foreach (array_chunk( $filtered, self::INSERT_CHUNK_SIZE ) as $chunk) {
			$query = "INSERT INTO {$this->wpdb->prefix}icl_strings "
			         . '(`language`, `context`, `gettext_context`, `domain_name_context_md5`, `name`, `value`, `status`) VALUES ';

			$i = 0;
			foreach ( $chunk as $string ) {
				if ( $i > 0 ) {
					$query .= ',';
				}

				$query .= $this->wpdb->prepare(
					"('%s', '%s', '%s', '%s', '%s', '%s', %d)",
					$this->get_source_lang( $string['name'], $string['domain'] ),
					$string['domain'],
					$string['gettext_context'],
					md5( $string['domain'] . $string['name'] . $string['gettext_context'] ),
					$string['name'],
					$string['value'],
					ICL_TM_NOT_TRANSLATED
				);

				$i ++;
			}

			$this->wpdb->query( $query );
		}
	}

	public function shutdown() {
		if ( count( $this->data ) ) {
			$this->persist();
			$this->data = array();
		}
	}
}
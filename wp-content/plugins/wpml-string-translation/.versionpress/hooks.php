<?php

global $wpdb;

$table_name = $wpdb->prefix . "icl_string_urls";
if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $icl_string_pages_sql_prototype = '
    CREATE TABLE IF NOT EXISTS `%sicl_string_pages` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `string_id` bigint(20) NOT NULL,
      `url_id` bigint(20) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `string_to_url_id` (`url_id`)
        )
    ';
    
    $icl_string_urls_sql_prototype = '
    CREATE TABLE IF NOT EXISTS `%sicl_string_urls` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `language` varchar(7) %s DEFAULT NULL,
      `url` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `string_string_lang_url` (`language`,`url`(191))
    )
    ';
    
    $charset_collate = '';
    if ( method_exists( $wpdb, 'has_cap' ) && $wpdb->has_cap( 'collation' ) ) {
        $charset_collate = $wpdb->get_charset_collate();
    }
    
    $language_charset_and_collation = '';
    $column_data = $wpdb->get_results( "SELECT * FROM INFORMATION_SCHEMA.COLUMNS where TABLE_NAME='{$wpdb->prefix}icl_strings' AND TABLE_SCHEMA='{$wpdb->dbname}' " );
    foreach ( $column_data as $column ) {
        if ( 'language' === $column->COLUMN_NAME ) {
            $language_charset_and_collation = 'CHARACTER SET ' . $column->CHARACTER_SET_NAME . ' COLLATE ' . $column->COLLATION_NAME;
        }
    }
    
    $icl_string_urls_sql = sprintf( $icl_string_urls_sql_prototype, $wpdb->prefix, $language_charset_and_collation );
    $icl_string_urls_sql .= $charset_collate;
    $wpdb->query( $icl_string_urls_sql );
    
    $icl_string_pages_sql = sprintf( $icl_string_pages_sql_prototype, $wpdb->prefix );
    $icl_string_pages_sql .= $charset_collate;
    $wpdb->query( $icl_string_pages_sql );
}

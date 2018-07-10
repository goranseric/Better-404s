<?php
/*
Plugin Name: Better 404s
Plugin URI: https://github.com/msigley
Description: More performant 404 handling.
Version: 1.0.0
Author: Matthew Sigley
License: MIT
*/

class Better404s {
	private static $object = null;

	private function __construct () {
		add_action( 'wp', array($this, 'handle_404s'), 1 );
	}
	
	static function &object() {
		if ( ! self::$object instanceof Better404s ) {
			self::$object = new Better404s();
		}
		return self::$object;
	}

	public function handle_404s() {
		if( !is_404() )
			return;

		$this->handle_404_prefetches();
		$this->handle_404_images();
	}

	public function handle_404_prefetches() {
		//Return nothing for prefetched 404 requests
		if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == "prefetch" //Firefox
			|| isset($_SERVER["HTTP_X_PURPOSE"]) //Chrome/Safari
				&& in_array($_SERVER["HTTP_X_PURPOSE"], array("preview", "instant"))
			) {
			status_header( 404, '404 Not Found' );
			die();
		}
	}
	
	public function handle_404_images() {
		//Handle Image 404s
		list($requested_file, $query_string) = explode('?', $_SERVER['REQUEST_URI'], 2);
		$image_filetypes = array('jpg','png','jpeg','gif','bmp'); //Ordered by most common
		foreach($image_filetypes as $image_filetype) {
			if( !$this->strendswith($requested_file, $image_filetype) ) continue;
			
			$image_string = wp_cache_get( '404_image_string' );
			if( $image_string === false ) {
				$image_string = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAEElEQVR42mL4//8/A0CAAQAI/AL+26JNFgAAAABJRU5ErkJggg==');
				wp_cache_set( '404_image_string', $image_string, '', 24 * HOUR_IN_SECONDS );
			}

			header( 'Content-Type: image/png' );
			header( 'Content-Length: ' . strlen($image_string) );
			ob_clean();
			echo $image_string;
			die();
		}
	}

	private function strendswith($haystack, $needle) {
		return substr_compare($haystack, $needle, -strlen($needle), strlen($needle)) === 0;
	}
}
$Better404s = Better404s::object();

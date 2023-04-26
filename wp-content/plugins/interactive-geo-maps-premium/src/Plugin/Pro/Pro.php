<?php
namespace Saltus\WP\Plugin\Saltus\InteractiveMaps\Plugin\Pro;

use Saltus\WP\Plugin\Saltus\InteractiveMaps\Core;

/**
 * The Pro class, where Pro Features are added.
 */
class Pro {

	public $icons;
	public $pro_meta;
	public $pro_actions;
	public $core;

	/**
	 * Setup the class variables
	 *
	 * @param string $name      Plugin name.
	 * @param string $version   Plugin version. Use semver.
	 * @param string $file_path Plugin file path
	 * @param string $saltus    Saltus Framework
	 */
	public function __construct( Core $core ) {

		$this->core = $core;
		$this->set_pro_actions();
		$this->set_pro_icons();
		$this->set_pro_meta();
		$this->set_pro_settings();
		$this->set_pro_map();

		// addons
		$this->set_pro_list_map_entries();
		$this->set_pro_geolocation_content();
	}

	// PRO
	public function set_pro_icons() {
		$this->icons = new ProIcons();
	}

	public function set_pro_meta() {
		$this->pro_meta = new ProMeta();
	}

	public function set_pro_settings() {
		$this->pro_meta = new ProSettings( $this->core );
	}

	public function set_pro_geolocation_content() {
		$this->pro_geo_content = new Extras\ProGeolocateContent( $this->core );
	}

	public function set_pro_actions() {
		$this->pro_actions = new ProActions( $this->core );
	}

	public function set_pro_list_map_entries() {
		$this->pro_map_entries_list = new Extras\ProListMapEntries( $this->core, $this->pro_actions );
		$this->pro_map_dropdown     = new Extras\ProDropdownMapEntries( $this->core, $this->pro_actions );
	}

	public function set_pro_map() {
		$this->pro_map = new ProMap( $this->core );
	}



}

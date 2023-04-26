<?php

namespace Saltus\WP\Plugin\Saltus\InteractiveMaps\Plugin\Pro\Extras;

use Saltus\WP\Plugin\Saltus\InteractiveMaps\Core;
use Saltus\WP\Plugin\Saltus\InteractiveMaps\Plugin\Pro\ProActions;

/**
 * Adds shortcode that displays list of entries in a specific map
 */
class ProDropdownMapEntries {

	/**
	 * Core instance
	 */
	public $core;

	/**
	 * action assets instance
	 */
	public $pro_actions;

	/**
	 * Define Actions
	 *
	 * @param Core $core This plugin's instance.
	 */
	public function __construct( Core $core, ProActions $pro_actions ) {
		$this->core = $core;
		$this->pro_actions = $pro_actions;
		$this->init();
	}

	public function init() {
		add_shortcode( 'display-map-dropdown', array( $this, 'render_map_dropdown' ) );
	}

	public function render_map_dropdown( $atts ) {

		if ( ! isset( $atts['id'] ) ) {
			return __( 'Please set a map ID for this list.', 'interactive-geo-maps' );
		}

		$atts = shortcode_atts(
			array(
				'url' => '',
				'id'  => false,
			),
			$atts
		);

		if ( $atts['id'] ) {

			$meta       = get_post_meta( $atts['id'], 'map_info', true );
			$meta['id'] = $atts['id'];
			$meta       = apply_filters( 'igm_add_meta', $meta );
			$meta       = apply_filters( 'igm_prepare_meta', $meta );
			$options    = get_option( 'interactive-maps' );
			$id         = $atts['id'];
			$url        = isset( $atts['url'] ) && $atts['url'] !== '' ? "data-url='" . $atts['url'] . "'" : '';

			$onlyParent  = isset( $meta['externalDropdown']['onlyParent'] ) ? $meta['externalDropdown']['onlyParent'] : false;


			$this->pro_actions->enqueue_dropdown_files();
			$this->pro_actions->enqueue_action_files();
			
			$series = [
				'regions',
				'roundMarkers',
				'imageMarkers',
				'iconMarkers',
				'labels',
			];

			$all_maps = array( $meta );

			// add all the other map series in overlay if they exist
			if ( ! $onlyParent && ! empty( $meta['overlay'] ) && is_array( $meta['overlay'] ) ) {
				foreach ( $meta['overlay'] as $key => $overlay ) {
					array_push( $all_maps, $overlay );
				}
			}

			$classes = '';
			if ( $meta['externalDropdown']['mobileOnly'] === '1' ) {
				$classes = 'igm_select_mobile_only';
			}

			$placeholder    = isset( $meta['externalDropdown']['placeholder'] ) ? $meta['externalDropdown']['placeholder'] : '';
			$select         = isset( $meta['externalDropdown']['select'] ) ? $meta['externalDropdown']['select'] : '';
			$no_matches     = isset( $meta['externalDropdown']['noResults'] ) ? $meta['externalDropdown']['noResults'] : '';
			$use_choices    = isset( $options['use_choices'] ) ? $options['use_choices'] : true;
			$optgroup       = isset( $meta['externalDropdown']['groupOverlay'] ) ? $meta['externalDropdown']['groupOverlay'] : false;
			$excludeRegions = isset( $meta['externalDropdown']['excludeRegions'] ) ? $meta['externalDropdown']['excludeRegions'] : false;

			$all_entries = [];
			$optgroup    = $optgroup && $optgroup === '1' ? [] : false;

			foreach ( $all_maps as $key => $map ) {

				foreach ( $series as $serie ) {

					if ( ! is_array( $map[ $serie ] ) || empty( $map[ $serie ] ) ) {
						continue;
					}

					// regions is id=name
					if ( $serie === 'regions' ) {

						// in case we don't want to include regions
						if( $excludeRegions ){
							continue;
						}

						$label_property = isset( $map['externalDropdown']['namePropery'] ) && $map['externalDropdown']['namePropery'] !== '' ? $map['externalDropdown']['namePropery'] : 'name';

						foreach ( $map[ $serie ] as $entry ) {
							$all_entries[ $entry['id'] ] = $this->pro_actions->getArrayValueByDotNotation( $entry, $label_property );
						}

						// if data source is json and it doesn't contain the "name", it will not be included
						// $all_entries = array_merge( array_column( $meta[ $serie ], $label_property, 'id' ), $all_entries );
					}
					// everything else id=id
					else {
						$label_property = isset( $map['externalDropdown']['namePropery'] ) && $map['externalDropdown']['namePropery'] !== '' ? $map['externalDropdown']['namePropery'] : 'id';
						foreach ( $map[ $serie ] as $entry ) {
							$all_entries[ $entry['id'] ] = $this->pro_actions->getArrayValueByDotNotation( $entry, $label_property );
						}
					}
				}

				if ( is_array( $optgroup ) ) {
					$optgroup[ $map['title'] ] = $all_entries;
					$all_entries               = [];
				}
			}

			$main_class = 'igm_select_choices';
			if ( ! $use_choices ) {
				$main_class = 'igm_select';
			}

			$html = sprintf(
				'<div class="igm_select_container %2$s">
			<select class="%6$s" %7$s data-noresults="%5$s" data-select="%4$s" data-map-id="%1$s" id="igm_select_%1$s">
			<option value="">%3$s</option>',
				$id,
				$classes,
				$placeholder, // 3
				$select, // 4
				$no_matches, // 5
				$main_class, // 6
				$url // 7
			);

			if ( is_array( $optgroup ) ) {

				foreach ( $optgroup as $okey => $ovalue ) {

					asort( $ovalue );

					$temp_opts = '';
					foreach ( $ovalue as $key => $value ) {
						$temp_opts .= sprintf(
							'<option value="%1$s">%2$s</option>',
							$key,
							$value
						);
					}

					$html .= sprintf(
						'<optgroup label="%1$s">%2$s</optgroup>',
						$okey,
						$temp_opts
					);
				}
			} else {

				asort( $all_entries );

				foreach ( $all_entries as $key => $value ) {
					$html .= sprintf(
						'<option value="%1$s">%2$s</option>',
						$key,
						$value
					);
				}
			}

			$html .= '</select></div>';

			return $html;
		}

		return '';

	}
}

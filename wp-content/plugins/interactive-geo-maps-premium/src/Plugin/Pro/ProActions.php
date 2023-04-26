<?php

namespace Saltus\WP\Plugin\Saltus\InteractiveMaps\Plugin\Pro;

use Saltus\WP\Plugin\Saltus\InteractiveMaps\Core;

/**
 * Manage available click actions
 */
class ProActions {

	/**
	 * Available click actions
	 */
	public $actions;

	/**
	 * Core instance
	 */
	public $core;

	/**
	 * Dependencies for main actions js file
	 */
	public $deps;

	/**
	 * URLs to load with actions
	 */
	private $urls;

	/**
	 * Options
	 */
	private $options;

	/**
	 * Define Actions
	 *
	 * @param Core $core This plugin's instance.
	 */
	public function __construct( Core $core ) {

		$this->core    = $core;
		$this->actions = $this->get_actions();
		$this->deps    = []; 
		$this->urls    = [];
		$this->options = get_option( 'interactive-maps' );

		// add pro actions to options
		add_filter( 'igm_click_actions', array( $this, 'pro_actions' ) );

		// in the administration, we don't need to run the rest of the code
		if ( is_admin() ) {
			return;
		}

		// check if we need to add external elements like live filter
		add_action( 'igm_prepare_meta_actions', array( $this, 'pro_external_elements' ), 19 );

		// check if we need to enqueue other action files
		add_action( 'igm_prepare_meta_actions', array( $this, 'pro_actions_assets' ), 20 );

		/**
		 * Duplicate 'the_content' filters
		 *
		 * @author Bill Erickson
		 * @link http://www.billerickson.net/code/duplicate-the_content-filters/
		 */
		global $wp_embed;
		add_filter( 'igm_the_content', array( $wp_embed, 'run_shortcode' ), 8 );
		add_filter( 'igm_the_content', array( $wp_embed, 'autoembed' ), 8 );
		add_filter( 'igm_the_content', 'wptexturize' );
		add_filter( 'igm_the_content', 'convert_chars' );
		add_filter( 'igm_the_content', 'wpautop' );
		add_filter( 'igm_the_content', 'shortcode_unautop' );
		add_filter( 'igm_the_content', 'do_shortcode' );

	}

	/**
	 * Prepare content to include on the map html and enqueue files if needed
	 *
	 * @param array $meta
	 * @return array $meta
	 */
	public function pro_external_elements( $meta ) {

		$handle = $this->core->name . '_map_service';
		$list = 'enqueued';
		if ( wp_script_is( $handle, $list ) ) {
			$this->deps[] = $this->core->name . '_map_service';
		} 

		// external custom legend
		if ( ! empty( $meta['customLegend'] ) && $meta['customLegend']['enabled'] === '1' && isset( $meta['customLegend']['type'] ) && $meta['customLegend']['type'] === 'external' ) {

			$this->enqueue_action_files();

			$filter = 'igm_mapbox_after';

			add_filter(
				$filter,
				function( $content, $id ) {

					$list = '';
					$meta = get_post_meta( $id, 'map_info', true );

					$entries = isset( $meta['customLegend']['data'] ) ?  $meta['customLegend']['data']  : [];

					foreach ( $entries as $entry ) {
						$list .= '<li class="igm-external-legend-entry"><span class="igm-external-legend-graphic" style="background:' . $entry['fill'] . '"></span>' . $entry['name'] . '</li>';
					}

					$html = sprintf(
						'<div class="igm-external-legend-container"><ul data-base-map-id="%1$s" id="igm-external-legend-%1$s" class="igm-external-legend">%2$s</ul></div>',
						$id,
						$list
					);
					if ( $content !== $html ) {
						$content = $html . $content;
					}
					return $content;
				},
				2,
				2
			);
		}

		// prepare for dropdown interface
		if ( ! empty( $meta['externalDropdown'] ) && $meta['externalDropdown']['enabled'] === '1' ) {

			$this->enqueue_dropdown_files();
			$this->enqueue_action_files();

			$options = $this->options;

			add_filter(
				'igm_mapbox_before',
				function( $content, $id ) use ( $meta, $options ) {

					$series = [
						'regions',
						'roundMarkers',
						'imageMarkers',
						'iconMarkers',
						'labels',
					];

					$placeholder    = isset( $meta['externalDropdown']['placeholder'] ) ? $meta['externalDropdown']['placeholder'] : '';
					$select         = isset( $meta['externalDropdown']['select'] ) ? $meta['externalDropdown']['select'] : '';
					$no_matches     = isset( $meta['externalDropdown']['noResults'] ) ? $meta['externalDropdown']['noResults'] : '';
					$use_choices    = isset( $options['use_choices'] ) ? $options['use_choices'] : true;
					$optgroup       = isset( $meta['externalDropdown']['groupOverlay'] ) ? $meta['externalDropdown']['groupOverlay'] : false;
					$onlyParent     = isset( $meta['externalDropdown']['onlyParent'] ) ? $meta['externalDropdown']['onlyParent'] : false;
					$excludeRegions = isset( $meta['externalDropdown']['excludeRegions'] ) ? $meta['externalDropdown']['excludeRegions'] : false;

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
									$all_entries[ $entry['id'] ] = [
										'value' => $this->getArrayValueByDotNotation( $entry, $label_property ),
										'mapID' => $map['id'],
									];
								}

								// if data source is json and it doesn't contain the "name", it will not be included
								// $all_entries = array_merge( array_column( $meta[ $serie ], $label_property, 'id' ), $all_entries );
							}
							// everything else id=id
							else {
								$label_property = isset( $map['externalDropdown']['namePropery'] ) && $map['externalDropdown']['namePropery'] !== '' ? $map['externalDropdown']['namePropery'] : 'id';
								foreach ( $map[ $serie ] as $entry ) {
									$all_entries[ $entry['id'] ] = [
										'value' => $this->getArrayValueByDotNotation( $entry, $label_property ),
										'mapID' => $map['id']
									];
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
					<select class="%6$s" data-noresults="%5$s" data-select="%4$s" data-map-id="%1$s" id="igm_select_%1$s">
					<option value="">%3$s</option>',
						$id,
						$classes,
						$placeholder, // 3
						$select, // 4
						$no_matches, // 5
						$main_class // 6
					);

					if ( is_array( $optgroup ) ) {

						foreach ( $optgroup as $okey => $ovalue ) {

							asort( $ovalue );

							$temp_opts = '';
							foreach ( $ovalue as $key => $value ) {
								$temp_opts .= sprintf(
									'<option value="%1$s" data-map-id="%3$s">%2$s</option>',
									$key,
									$value['value'],
									$value['mapID']
								);
							}

							$html .= sprintf(
								'<optgroup data-map-id="%3$s" label="%1$s">%2$s</optgroup>',
								$okey,
								$temp_opts,
								$value['mapID']
							);
						}
					} else {

						asort( $all_entries );
						
						foreach ( $all_entries as $key => $value ) {
							$html .= sprintf(
								'<option data-map-id="%3$s" value="%1$s">%2$s</option>',
								$key,
								$value['value'],
								$value['mapID']
							);
						}
					}

					$html .= '</select></div>';

					$content = $html . $content;

					return $content;
				},
				2,
				2
			);
		}

		// live filter
		if ( ! empty( $meta['liveFilter'] ) && $meta['liveFilter']['enabled'] === '1' ) {

			$this->enqueue_action_files();

			if ( $meta['liveFilter']['position'] === 'above' ) {
				$filter = 'igm_mapbox_before';
			} else {
				$filter = 'igm_mapbox_after';
			}

			add_filter(
				$filter,
				function( $content, $id ) {

					$list = '';
					$meta = get_post_meta( $id, 'map_info', true );
					$html = '';

					$default   = $meta['liveFilter']['default'];
					$type      = isset( $meta['liveFilter']['type'] ) ? $meta['liveFilter']['type'] : 'menu';
					$icount    = isset( $meta['liveFilter']['includeCount'] ) && $meta['liveFilter']['includeCount'] === '1' ? true : false;
					$keep_base = isset( $meta['liveFilter']['keepBase'] ) && $meta['liveFilter']['keepBase'] === '1' ? true : false;

					// data containers
					$containers = array(
						'regions',
						'roundMarkers',
						'iconMarkers',
						'imageMarkers',
						'labels',
					);

					if( ! is_array( $meta['overlay'] ) ){
						return '';
					}

					$html = '';

					if ( 'menu' === $type || 'menu_dropdown' === $type ) {

						foreach ( $meta['overlay'] as $map_id ) {

							$title = get_the_title( $map_id );

							if ( $icount ) {

								$countdata = '';

								// get meta to count
								$ometa = get_post_meta( $map_id, 'map_info', true );

								$count = 0;
								foreach ( $containers as $data_container ) {
									$scount = isset( $ometa[ $data_container ] ) && is_array( $ometa[ $data_container ] ) ? count( $ometa[ $data_container ] ) : 0;
									$count  = $count + $scount;
								}

								$title    .= ' (' . $count . ')';
							}

							$class = $default === $map_id ? 'igm-live-filter-active' : '';
							$list .= '<li class="' . $class . '" data-map-id="' . $map_id . '">' . $title . '</li>';
						}

						$all_class       = $default === $id ? 'igm-live-filter-active' : '';
						$container_class = 'menu_dropdown' === $type ? 'class="igm_hide_on_mobile"' : '';


						$html .= sprintf(
							'<div %6$s><ul data-base-map-id="%1$s" id="igm-live-filter-%1$s" class="igm-live-filter" data-keep-base-map="%5$s"><li data-map-id="%1$s" class="%4$s">%2$s</li>%3$s</ul></div>',
							$id,
							$meta['liveFilter']['allLabel'],
							$list,
							$all_class,
							$keep_base,
							$container_class
						);

					} 
					
					if ( 'dropdown' === $type || 'menu_dropdown' === $type ) {

						$container_class = 'menu_dropdown' === $type ? 'class="igm_show_on_mobile"' : '';

						foreach ( $meta['overlay'] as $map_id ) {

							$title = get_the_title( $map_id );

							if ( $icount ) {

								$countdata = '';

								// get meta to count
								$ometa = get_post_meta( $map_id, 'map_info', true );
								$count = 0;
								foreach ( $containers as $data_container ) {
									$scount = isset( $ometa[ $data_container ] ) && is_array( $ometa[ $data_container ] ) ? count( $ometa[ $data_container ] ) : 0;
									$count  = $count + $scount;
								}

								$title    .= ' (' . $count . ')';
							}

							$selected = $default === $map_id ? 'selected' : '';
							$list    .= '<option ' . $selected . ' value="' . $map_id . '" data-map-id="' . $map_id . '">' . $title . '</option>';
						}

						$all_selected = $default === $id ? 'selected' : '';

						$html .= sprintf(
							'<div %6$s>
								<select data-base-map-id="%1$s" id="igm-live-filter-%1$s" class="igm-live-filter-dropdown" data-keep-base-map="%5$s">
									<option data-map-id="%1$s" value="%1$s" %4$s>%2$s</option>
									%3$s
								</select>
							</div>',
							$id,
							$meta['liveFilter']['allLabel'],
							$list,
							$all_selected,
							$keep_base,
							$container_class
						);
					}

					if ( $content !== $html ) {
						$content = $html . $content;
					}
					return $content;
				},
				2,
				2
			);
		}

		// external zoom
		if ( false ) {
			add_filter(
				'igm_mapbox_after',
				function( $content, $id ) {

					$home_bt     = 'home';
					$zoom_in_bt  = 'in';
					$zoom_out_bt = 'out';

					$content = sprintf(
						'<div class="igm_external_zoom" id="igm_external_zoom_%1$s" >%2$s%3$s%4$s</div>%5$s',
						$id,
						$home_bt,
						$zoom_in_bt,
						$zoom_out_bt,
						$content
					);

					return $content;
				},
				3,
				2
			);
		}

		return $meta;

	}

	/**
	 * Get array value with dot notation path
	 *
	 * @param [type] $arr
	 * @param [type] $path
	 * @param string $separator
	 * @return void
	 */
	public function getArrayValueByDotNotation( $arr, $path, $separator = '.' ) {

		$keys = explode( '.', $path );

		foreach ( $keys as $key ) {
			if ( isset( $arr[ $key ] ) ) {
				$arr = $arr[ $key ];
			} 
		}

		if ( ! is_array( $arr ) ) {
			return $arr;
		}


		return false;
	}

	/**
	 * Set available actions
	 *
	 * @return void
	 */
	public function get_actions( $type = 'all' ) {

		$actions = [
			'igm_lightbox'                      => __( 'Open content in Lightbox', 'interactive-geo-maps' ),
			'igm_lightbox_iframe'               => __( 'Open URL in Lightbox (iframe)', 'interactive-geo-maps' ),
			'igm_lightbox_image'                => __( 'Open Image in Lightbox', 'interactive-geo-maps' ),
			'igm_display_below'                 => __( 'Display content below', 'interactive-geo-maps' ),
			'igm_display_below_scroll'          => __( 'Display content below & scroll', 'interactive-geo-maps' ),
			'igm_display_above'                 => __( 'Display content above', 'interactive-geo-maps' ),
			'igm_display_right_1_3'             => __( 'Display content to right', 'interactive-geo-maps' ),
			'igm_display_page_below'            => __( 'Display page below (beta)', 'interactive-geo-maps' ),
			'igm_display_page_below_and_scroll' => __( 'Display page below & scroll (beta)', 'interactive-geo-maps' ),
			'igm_display_map'                   => __( 'Display specific map (beta)', 'interactive-geo-maps' ),
		];

		return $actions;

	}

	public function pro_actions( $actions ) {

		$actions = array_merge( $actions, $this->get_actions() );

		// default and pro
		return $actions;

	}

	public function pro_actions_assets( $meta ) {

		$existing_actions = [];
		$actions          = $this->get_actions();

		// default relations. series => seriesDefaults
		$relation = [
			'regions'      => 'regionDefaults',
			'roundMarkers' => 'markerDefaults',
			'imageMarkers' => 'imageMarkerDefaults',
			'iconMarkers'  => 'iconMarkerDefaults',
			'labels'       => 'labelDefaults',
		];

		foreach ( $relation as $series => $default ) {

			if ( ! isset( $meta[ $series ] ) ) {
				continue;
			}

			if ( ! is_array( $meta[ $series ] ) ) {
				continue;
			}
			// flag to check if custom action was already added
			$custom_action = false;

			$count_series = count( $meta[ $series ] );
			foreach ( $meta[ $series ] as $key => $value ) {
				$thisaction = '';

				if ( isset( $value['useDefaults'] ) && $value['useDefaults'] === '1' ) {
					if ( isset( $meta[ $default ]['action'] ) && ( array_key_exists( $meta[ $default ]['action'], $actions ) || strpos( $meta[ $default ]['action'], 'custom' ) === 0 ) ) {
						$thisaction         = $meta[ $default ]['action'];
						$existing_actions[] = $thisaction;
					}
				} else {
					if ( isset( $value['action'] ) && array_key_exists( $value['action'], $actions ) ) {
						$thisaction         = $value['action'];
						$existing_actions[] = $thisaction;
					} elseif ( isset( $value['action'] ) && $value['action'] === 'default' ) {
						if ( isset( $meta[ $default ]['action'] ) && ( array_key_exists( $meta[ $default ]['action'], $actions ) || strpos( $meta[ $default ]['action'], 'custom' ) === 0 ) ) {
							$thisaction         = $meta[ $default ]['action'];
							$existing_actions[] = $thisaction;
						}
					}
				}

				// output inline content for some actions
				$inline_content_actions = [
					'igm_lightbox',
					'igm_display_right_1_3',
					'igm_display_below',
					'igm_display_above',
					'igm_display_below_scroll',
				];

				if ( in_array( $thisaction, $inline_content_actions, true ) ) {


					// php kiszyniow-with-space-25d025b425d025b425d025b425d025b4paryes_101011
					// js  kiszynic3b3w5cwith-spaced0b4d0b4d0b4d0b4par22yes7c7c_101011

					// finally we just run sanitize_title to make sure nothing is left
					/*
					$idattr = sanitize_title( $value['id'] );
					// let's replace commas, since the groups will have them

					// and spaces, while we're at it

					// some other fixes
					$idattr = str_replace( '–', '', $idattr );
					$idattr = str_replace( '(', '', $idattr );
					$idattr = str_replace( ')', '', $idattr );

					// and url encode it because of other char sets
					$idattr = rawurlencode( $idattr );
					// yet another fix after the encode
					$idattr = str_replace( '%', '', $idattr );
					*/

					$idattr = str_replace( ' ', '', $value['id'] );
					$idattr = str_replace( ',', '_', $idattr );
					$idattr = rawurlencode( $idattr );
					$idattr = str_replace( '%', '', $idattr );
					//$idattr = sanitize_title_with_dashes( $value['id'], false, 'save' );


					// todo: use array of find/replace
					$idattr = strtolower( $idattr ) . '_' . $meta['id'];




					$content = isset( $value['content'] ) ? $value['content'] : '';
					$content = $this->replace_placeholders( $content, $value );
					$content = apply_filters( 'igm_the_content', $content );
					$content = '<div data-original-id="' . esc_attr( $value['id'] ) . '" data-content-type="' . $series . '" data-content-index="' . $key . '" class="igm-map-content" id="' . $idattr . '">' . $content . '</div>';

					$this->core->add_footer_content( $content );
				}

				if ( strpos( $thisaction, 'custom' ) === 0 && ! $custom_action ) {

					$content = '
					function ' . $thisaction . '_' . $meta['id'] . '( data ) {
						' . $meta[ $default ]['customAction'] . '
					}';

					$this->core->add_footer_scripts( $content );
					$custom_action = true; // custom action was added, no need to add it again for the other entries
				}
			}
		}

		$this->prepare_action_assets( $existing_actions );

		return $meta;
	}

	/**
	 * Replace placeholders by values
	 *
	 * @param string $body
	 * @param array  $vars
	 * @return $body string with replaced placeholders
	 */
	public function replace_placeholders( $body, $vars ) {

		/*
		// aproach 01 - works well, but is it worth to check this for each entry?
		foreach ( $vars as $key => $value ) {
			$placeholder = sprintf( '{%s}', $key );
			$body        = str_replace( $placeholder, $value, $body );
		}
		*/

		$body = preg_replace_callback(
			'/{([^}]+)}/',
			function ( $m ) use ( $vars ) {
				$found = $this->getArrayValueByDotNotation($vars, $m[1]);
				if ( $found ) {
					return $found;
				} else {
					return sprintf( '{%s}', $m[1] );
				}
			},
			$body
		);

		return $body;
	}

	public function check_existing_actions( $relation ) {

		$existing_actions = [];
		$actions          = $this->get_actions();

		foreach ( $relation as $series => $default ) {

			if ( ! isset( $meta[ $key ] ) ) {
				continue;
			}

			$exists           = $this->action_exists_in_series( $meta[ $source ], $actions );
			$existing_actions = array_merge( $existing_actions, $exists );

			if ( isset( $meta[ $default ]['action'] ) && array_key_exists( $meta[ $default ]['action'], $actions ) ) {
				$existing_actions = array_merge( $existing_actions, $meta[ $default ]['action'] );
			}
		}

		return $existing_actions;
	}

	public function action_exists_in_series( $data, $actions ) {

		$existing_actions = [];

		if ( ! is_array( $data ) ) {
			return $existing_actions;
		}

		foreach ( $data as $key => $value ) {
			if ( isset( $value['action'] ) && array_key_exists( $value['action'], $actions ) ) {
				$existing_actions[] = $value['action'];
			}
		}

		return $existing_actions;
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $actions
	 * @return void
	 */
	private function prepare_action_assets( $actions ) {

		if ( empty( $actions ) ) {
			return;
		}

		$actions = array_unique( $actions );

		foreach ( $actions as $action ) {

			switch ( $action ) {
				case 'igm_display_right_1_3':
					add_filter(
						'igm_mapbox_classes',
						function( $classes ) {
							$classes .= ' igm_content_left_2_3';
							return $classes;
						}
					);
					add_filter(
						'igm_mapbox_after',
						function( $content, $id ) {
							$html = '<div class="igm_content_gutter" style="{gutterStyles}"></div><div style="{styles}" class="igm_content_right_1_3"><div id="default_' . $id . '">{description}</div></div>';
							if ( $content !== $html ) {
								$content = $html . $content;
							}
							return $content;
						},
						2,
						2
					);
					break;

				case 'igm_display_below':
				case 'igm_display_below_scroll':
				case 'igm_display_page_below':
				case 'igm_display_page_below_and_scroll':
					add_filter(
						'igm_mapbox_after',
						function( $content, $id ) {
							$html = '<div class="igm_content_below"><div id="default_' . $id . '">{description}</div></div>';
							if ( $content !== $html ) {
								$content = $html . $content;
							}
							return $content;
						},
						2,
						2
					);
					break;

				case 'igm_display_above':
				case 'igm_display_above_scroll':
					add_filter(
						'igm_mapbox_before',
						function( $content, $id ) {
							$html = '<div class="igm_content_above"><div id="default_' . $id . '">{description}</div></div>';
							if ( $content !== $html ) {
								$content = $html . $content;
							}
							return $content;
						},
						2,
						2
					);
					break;

				case 'igm_lightbox':
				case 'igm_lightbox_iframe':
				case 'igm_lightbox_image':
					$this->enqueue_lightbox_files();
					break;

				default:
					// code...
					break;
			}
		}

		$this->enqueue_action_files();

	}

	public function enqueue_dropdown_files() {

		$hasfilter = has_filter( 'igm_public_assets_url' );
		if ( $hasfilter ) {
			$url       = apply_filters( 'igm_public_assets_url', 'vendor/choices/public/assets/scripts/choices.min.js' );
			$style_url = apply_filters( 'igm_public_assets_url', 'vendor/choices/public/assets/styles/choices.min.css' );
		} else {
			$url       = plugins_url( 'assets/public/vendor/choices/public/assets/scripts/choices.min.js', $this->core->file_path );
			$style_url = plugins_url( 'assets/public/vendor/choices/public/assets/styles/choices.min.css', $this->core->file_path );
		}

		$options = $this->options;
		if ( isset( $options['async'] ) && $options['async'] ) {
			$this->urls[] = $url;
		} else {
			$this->deps[] = $this->core->name . '_chosen';
			wp_register_script(
				$this->core->name . '_chosen',
				$url,
				array(),
				true,
				true
			);
			wp_enqueue_script( $this->core->name . '_chosen' );
			$this->modify_script_tag( $this->core->name . '_chosen' );
		}

		// styles
		wp_register_style(
			$this->core->name . '_chosen',
			$style_url,
			false,
			$this->core->version
		);

		wp_enqueue_style( $this->core->name . '_chosen' );
	}

	public function enqueue_action_files() {

		// add option values
		$options = $this->options;

		$hasfilter = has_filter( 'igm_public_assets_url' );
		if ( $hasfilter ) {
			$url        = apply_filters( 'igm_public_assets_url', 'map-actions/actions.min.js' );
			$styles_url = apply_filters( 'igm_public_assets_url', 'map-actions/actions.min.css' );
		} else {

			$url       = defined('WP_DEBUG') && true === WP_DEBUG ? 'assets/public/map-actions/actions.js' : 'assets/public/map-actions/actions.min.js';
			$url       = plugins_url( $url, $this->core->file_path );
			$styles_url = plugins_url( 'assets/public/map-actions/actions.min.css', $this->core->file_path );
		}

		wp_deregister_script( $this->core->name . '_actions' );

		$dependencies = is_array( $this->deps ) && ! empty( $this->deps ) ? $this->deps : [];

		wp_register_script(
			$this->core->name . '_actions',
			$url,
			$dependencies,
			$this->core->version,
			true
		);
		wp_enqueue_script( $this->core->name . '_actions' );

		// check if we need to add scripts
		if ( $this->core->footer_scripts !== '' ) {
			wp_add_inline_script( $this->core->name . '_actions', $this->core->footer_scripts, 'before' );
		}

		$lightbox_height = isset( $options['lightbox_height_auto'] ) && $options['lightbox_height_auto'] !== 'auto' ? $options['lightbox_height'] : 'auto';

		$settings = [
			'lightboxWidth'  => isset( $options['lightbox_width'] ) ? $options['lightbox_width'] : '600',
			'lightboxHeight' => $lightbox_height,
			'restURL'        => get_rest_url( null, 'wp/v2/' ),
			'urls'           => $this->urls,
		];

		if ( isset( $options['async'] ) && $options['async'] ) {
			$settings['async'] = true;
			$this->async_script_tag( $this->core->name . '_actions' );
		} else {
			$this->modify_script_tag( $this->core->name . '_actions' );
		}

		wp_localize_script( $this->core->name . '_actions', 'iMapsActionOptions', $settings );

		// styles
		wp_register_style(
			$this->core->name . '_actions',
			$styles_url,
			false,
			$this->core->version
		);

		wp_enqueue_style( $this->core->name . '_actions' );
	}

	private function enqueue_lightbox_files() {

		$hasfilter = has_filter( 'igm_public_assets_url' );
		if ( $hasfilter ) {
			$url        = apply_filters( 'igm_public_assets_url', 'vendor/glightbox/js/glightbox.min.js' );
			$styles_url = apply_filters( 'igm_public_assets_url', 'vendor/glightbox/css/glightbox.min.css' );
		} else {
			$url       = plugins_url( 'assets/public/vendor/glightbox/js/glightbox.min.js', $this->core->file_path );
			$styles_url = plugins_url( 'assets/public/vendor/glightbox/css/glightbox.min.css', $this->core->file_path );
		}


		$options = $this->options;
		if ( isset( $options['async'] ) && $options['async'] ) {
			$this->urls[] = $url;
		} else {

			wp_register_script(
				$this->core->name . '_lightbox',
				$url,
				array(),
				true,
				true
			);
			wp_enqueue_script( $this->core->name . '_lightbox' );
		}

		// styles
		wp_register_style(
			$this->core->name . '_lightbox',
			$styles_url,
			false,
			$this->core->version
		);

		wp_enqueue_style( $this->core->name . '_lightbox' );
	}

	/**
	 * Add the script id to the script tag and hopefully remove async attribute added by other plugins
	 *
	 * @param string $script_id
	 * @return void
	 */
	public function modify_script_tag( $script_id ) {
		add_filter(
			'script_loader_tag',
			function( $tag, $handle, $src ) use ( $script_id ) {

				// check against our registered script handle
				if ( $script_id === $handle ) {
					// add attributes of your choice
					$tag = str_replace( 'async=\'async\'', '', $tag );
				}
				return $tag;
			},
			31, // async for WordPress plugin uses 20
			3
		);
	}

	/**
	 * Add the async attribute to script tag
	 *
	 * @param string $script_id
	 * @return void
	 */
	public function async_script_tag( $script_id ) {
		add_filter(
			'script_loader_tag',
			function( $tag, $handle, $src ) use ( $script_id ) {

				// check against our registered script handle
				if ( $script_id === $handle ) {

					if ( strpos( $tag, 'async' ) === false ) {
						// add attributes of your choice
						$tag = str_replace( '<script ', '<script async=\'async\' ', $tag );
					}
				}
				return $tag;
			},
			31, // async for WordPress plugin uses 20
			3
		);
	}

}

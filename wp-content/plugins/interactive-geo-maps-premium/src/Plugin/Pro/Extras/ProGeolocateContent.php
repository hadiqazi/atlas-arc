<?php

namespace Saltus\WP\Plugin\Saltus\InteractiveMaps\Plugin\Pro\Extras;

use Saltus\WP\Plugin\Saltus\InteractiveMaps\Core;

/**
 * Add Pro Geolocate Content Feature
 */
class ProGeolocateContent {

	private $options;
	public  $core;

	/**
	 * Constructor class
	 */
	public function __construct( Core $core ) {

		// add pro meta options
		add_filter( 'igm_model', array( $this, 'add_geolocate_content_settings' ) );
		// prepare map info
		add_filter( 'igm_prepare_meta', array( $this, 'prepare_geo_meta' ), 5 );
		// filter on save post type
		add_filter( 'csf_igm_geolocation_save', array( $this, 'save_control_field' ), 1, 3 );

		$this->options = get_option( 'interactive-maps' );
		$this->core = $core;
		$this->init();

	}

	public function init() {

		$options = $this->options;

		if ( isset( $options['geolocate_enable'] ) && $options['geolocate_enable'] && isset( $options['geolocate_types'] ) && is_array( $options['geolocate_types'] ) ) {
			$this->add_geolocate_meta( $options['geolocate_types'] );
			add_filter( 'igm_model', array( $this, 'add_geolocate_map_option' ), 1 );
		}
	}

	/**
	 * Function to sanitize meta on save
	 *
	 * @param array $request with meta info
	 * @param int   $post_id
	 * @param obj   $csf class
	 * @return array
	 */
	public function save_control_field( $request, $post_id, $csf ) {

		if ( empty( $request ) || ! is_array( $request ) ) {
			return $request;
		}

		if (
			isset( $request['enabled'] )
			&& $request['enabled'] === '1'
		) {
			update_post_meta( $post_id, '_igm_geolocate', '1' );
		} else {
			update_post_meta( $post_id, '_igm_geolocate', '0' );
		}

		return $request;

	}

	/**
	 * Prepare the meta info for the map if it has the option to populate with geolocated content
	 *
	 * @param [type] $meta
	 * @return void
	 */
	public function prepare_geo_meta( $meta ) {

		// only advance if we have the geolocation option enabled
		if ( ! isset( $meta['populateFromGeo'] ) || ! $meta['populateFromGeo'] ) {
			return $meta;
		}

		// get geolocated post types
		$options = $this->options;
		$types   = isset( $options['geolocate_types'] ) ? $options['geolocate_types'] : false;

		if ( ! is_array( $types ) || count( $types ) === 0 ) {
			return $meta;
		}

		if ( isset( $meta['populateFromGeoSingle'] ) && $meta['populateFromGeoSingle'] === '1' ) {
			global $post;
			if ( $post ) {
				$args = array(
					'post_type'      => $types,
					'posts_per_page' => -1,
					'include'        => [ $post->ID ],
					'meta_query'     => array(
						array(
							'key'   => '_igm_geolocate',
							'value' => '1',
						),
					),
				);
			} else {
				$args = [];
			}
		} else {

			$args = array(
				'post_type'      => $types,
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => '_igm_geolocate',
						'value' => '1',
					),
				),
			);
		}

		if ( isset( $meta['populateFromGeoAdvanced'] ) && $meta['populateFromGeoAdvanced'] !== '' ) {
			$json_args = json_decode( $meta['populateFromGeoAdvanced'], true );
			if ( $json_args ) {
				$args = array_merge( $args, $json_args );
			}
		}

		$query = get_posts( $args );

		foreach ( $query as $key => $tpost ) {

			$post_id = $tpost->ID;
			$geo     = get_post_meta( $post_id, 'igm_geolocation', true );

			// check type
			if ( $geo['type'] === 'region' ) {
				$extra_region    = $this->extract_region_entry( $geo, $tpost );
				$existing        = isset( $meta['regions'] ) && is_array( $meta['regions'] ) ? $meta['regions'] : [];
				$meta['regions'] = array_merge( $existing, $extra_region );
			}

			// check type
			if ( $geo['type'] === 'specific' ) {
				// round markers
				if ( $geo['markerType'] === 'roundMarker' || $geo['markerType'] === 'undefined' ) {
					$extra_marker         = $this->extract_marker_entry( $geo, $tpost, 'roundMarkers' );
					$existing             = isset( $meta['roundMarkers'] ) && is_array( $meta['roundMarkers'] ) ? $meta['roundMarkers'] : [];
					$meta['roundMarkers'] = array_merge( $existing, $extra_marker );
				}
				// icon markers
				if ( $geo['markerType'] === 'iconMarker' ) {
					$extra_marker        = $this->extract_marker_entry( $geo, $tpost, 'iconMarkers' );
					$existing            = isset( $meta['iconMarkers'] ) && is_array( $meta['iconMarkers'] ) ? $meta['iconMarkers'] : [];
					$meta['iconMarkers'] = array_merge( $existing, $extra_marker );
				}
				// image markers
				if ( $geo['markerType'] === 'imageMarker' ) {
					$extra_marker         = $this->extract_marker_entry( $geo, $tpost, 'imageMarkers' );
					$existing             = isset( $meta['imageMarkers'] ) && is_array( $meta['imageMarkers'] ) ? $meta['imageMarkers'] : [];
					$meta['imageMarkers'] = array_merge( $existing, $extra_marker );
				}
			}
		}

		return $meta;
	}

	/**
	 * Extract region from post object
	 *
	 * @param array  $geo geo options for that entry
	 * @param object $post post object for that entry
	 * @return array with populated region
	 */
	private function extract_region_entry( $geo, $post ) {

		$featured = get_the_post_thumbnail_url( $post->ID, 'full' );

		$region = [
			'id'             => $geo['regionCode'],
			'name'           => $post->post_title,
			'title'          => $post->post_title,
			'tooltipContent' => isset( $geo['tooltipContent'] ) && $geo['tooltipContent'] !== '' ? $geo['tooltipContent'] : $post->post_title,
			'useDefaults'    => isset( $geo['useDefaultsRegions'] ) && $geo['useDefaultsRegions'] ? '1' : '0',
			'content'        => isset( $geo['content'] ) && $geo['content'] !== '' ? $geo['content'] : get_permalink( $post->ID ),
			'fill'           => isset( $geo['regionFill'] ) && $geo['regionFill'] !== '' ? $geo['regionFill'] : '#6699cc',
			'hover'          => isset( $geo['regionHover'] ) && $geo['regionHover'] !== '' ? $geo['regionHover'] : '#6699cc',
			'action'         => 'default',
			'value'          => isset( $geo['value'] ) && $geo['value'] !== '' ? $geo['value'] : '',
			'permalink'      => get_permalink( $post->ID ),
			'featured_image' => $featured ? $featured : '',
			'action'         => isset( $geo['regionAction'] ) ? $geo['regionAction'] : 'default',
		];

		return [ $region ];
	}

	/**
	 * Extract marker from post object
	 *
	 * @param array                 $geo geo options for that entry
	 * @param object                $post post object for that entry
	 * @param string type of marker
	 * @return array with populated region
	 */
	private function extract_marker_entry( $geo, $post, $type = 'roundMarkers' ) {

		$featured = get_the_post_thumbnail_url( $post->ID, 'full' );

		$marker = [
			'id'             => $post->ID,
			'name'           => $post->post_title,
			'title'          => $post->post_title,
			'tooltipContent' => isset( $geo['tooltipContent'] ) && $geo['tooltipContent'] !== '' ? $geo['tooltipContent'] : $post->post_title,
			'useDefaults'    => isset( $geo['useDefaults'] ) && $geo['useDefaults'] ? '1' : '0',
			'content'        => isset( $geo['content'] ) && $geo['content'] !== '' ? $geo['content'] : get_permalink( $post->ID ),
			'fill'           => isset( $geo['fill'] ) && $geo['fill'] !== '' ? $geo['fill'] : '#6699cc',
			'hover'          => isset( $geo['hover'] ) && $geo['hover'] !== '' ? $geo['hover'] : '#6699cc',
			'action'         => 'default',
			'value'          => isset( $geo['value'] ) && $geo['value'] !== '' ? $geo['value'] : '',
			'permalink'      => get_permalink( $post->ID ),
			'featured_image' => $featured ? $featured : '',
			'coordinates'    => isset( $geo['coordinates'] ) && is_array( $geo['coordinates'] ) ? $geo['coordinates'] : '',
			'action'         => isset( $geo['action'] ) ? $geo['action'] : 'default',
		];

		// round marker
		// radius
		if ( $type === 'roundMarkers' ) {
			$marker['radius'] = isset( $geo['radius'] ) ? $geo['radius'] : '10';
		}

		// icon Marker
		// scale, horizontalCenter, verticalCenter, icon
		if ( $type === 'iconMarkers' ) {
			$marker['scale']            = isset( $geo['scale'] ) ? $geo['scale'] : '1';
			$marker['horizontalCenter'] = isset( $geo['horizontalCenter'] ) ? $geo['horizontalCenter'] : 'middle';
			$marker['verticalCenter']   = isset( $geo['verticalCenter'] ) ? $geo['verticalCenter'] : 'middle';
			$marker['icon']             = isset( $geo['icon'] ) ? $geo['icon'] : 'fa fa-star';
		}

		// image
		// image, size, horizontalCenter, verticalCenter
		if ( $type === 'imageMarkers' ) {
			$marker['size']             = isset( $geo['size'] ) ? $geo['size'] : '20';
			$marker['horizontalCenter'] = isset( $geo['horizontalCenter'] ) ? $geo['horizontalCenter'] : 'middle';
			$marker['verticalCenter']   = isset( $geo['verticalCenter'] ) ? $geo['verticalCenter'] : 'middle';
			$marker['image']            = isset( $geo['image'] ) ? $geo['image'] : '';
		}

		return [ $marker ];
	}

	/**
	 * Add settings for the Geolocate Content Feature
	 *
	 * @param array $settings
	 * @return array modified $settings
	 */
	public function add_geolocate_content_settings( $settings ) {

		$settings['settings']['interactive-maps']['sections']['geolocate-content'] = [
			'title'  => __( 'Geolocate Content', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-map-marker',
			'fields' => [

				'geolocate_intro' => [
					'type'    => 'content',
					'content' => __( 'This is an EXPERIMENTAL feature, still in BETA.<br>You can enable geolocation features for selected post types.<br>It will add extra meta boxes to the edit screen of those post types with options to associate coordinates for a specific location or a region or country code.<br>You can then have a map automatically be populated with this data.', 'interactive-geo-maps' ),
				],

				'geolocate_enable' => [
					'type'    => 'switcher',
					'title'   => __( 'Enable', 'interactive-geo-maps' ),
					'desc'    => __( 'Enable Geolocation features for selected content types like posts or pages.', 'interactive-geo-maps' ),
					'default' => false,
				],
				'geolocate_types'  => [
					'type'       => 'checkbox',
					/* translators: field type = input type (textarea, rich text editor...) */
					'title'      => __( 'Types of Content', 'interactive-geo-maps' ),
					'subtitle'   => __( 'Enable the geolocation features on selected types of content', 'interactive-geo-maps' ),
					'options'    => 'post_types',
					'dependency' => [ 'geolocate_enable', '==', true ],

				],
			],
		];

		return $settings;
	}

	public function add_geolocate_map_option( $model ) {

		$model['meta']['map_info']['sections']['general']['fields']['populateFromGeo'] = [
			'type'  => 'switcher',
			'title' => __( 'Populate with Geolocated entries', 'interactive-geo-maps' ),
			'desc'  => __( 'BETA/EXPERIMENTAL FEATURE.<br>If enabled, the map will be automatically populated with the entries added using the Geolocation option.', 'interactive-geo-maps' ),
		];

		$model['meta']['map_info']['sections']['general']['fields']['populateFromGeoSingle'] = [
			'type'       => 'switcher',
			'dependency' => [ 'populateFromGeo', '==', true ],
			'title'      => __( 'Populate with a single geolocated entry', 'interactive-geo-maps' ),
			'desc'       => __( 'When enabled, the map will only render the geolocated data of the page/post where the map is added.', 'interactive-geo-maps' ),
		];

		$model['meta']['map_info']['sections']['general']['fields']['populateFromGeoAdvanced'] = [
			'type'       => 'text',
			'dependency' => [ 'populateFromGeo', '==', true ],
			'title'      => __( 'Advanced Query Parameters', 'interactive-geo-maps' ),
                              'desc'       => __( 'Use JSON format with WP_Query paramaters you want to add to this query. You can use this for example to filter entries from a specific taxonomy.', 'interactive-geo-maps' ),
		];

		return $model;

	}


	public function add_geolocate_meta( $post_type ) {

		$options = get_option( 'interactive-maps' );

		$tooltip_editor        = isset( $options['tooltip_editor'] ) ? $options['tooltip_editor'] : 'textarea';
		$action_content_editor = isset( $options['actionContent_editor'] ) ? $options['actionContent_editor'] : 'textarea';

		if ( class_exists( 'CSF' ) ) {

			//
			// Set a unique slug-like ID
			$prefix = 'igm_geolocation';

			//
			// Create a metabox
			\CSF::createMetabox(
				$prefix,
				array(
					'title'     => __( 'Geolocation (Beta)', 'interactive-geo-maps' ),
					'post_type' => $post_type,
					'theme'     => 'light',
				)
			);

			// Create a section
			\CSF::createSection(
				$prefix,
				array(
					'title'  => __( 'Options', 'interative-geo-maps' ),
					'fields' => array(
						array(
							'id'      => 'enabled',
							'type'    => 'switcher',
							'title'   => __( 'Enable', 'interactive-geo-maps' ),
							'desc'    => __( 'Enable Geolocation features this.', 'interactive-geo-maps' ),
							'default' => false,
						),
						array(
							'id'         => 'type',
							'type'       => 'button_set',
							'title'      => __( 'Type', 'interactive-geo-maps' ),
							'desc'       => __( 'Type of location you want to specify. <br> Either a specific location (using coordinates) or a full region or country (using a region code).', 'interactive-geo-maps' ),
							'default'    => 'specific',
							'options'    => [
								'specific' => __( 'Specific Location', 'interactive-geo-maps' ),
								'region'   => __( 'Region or Country', 'interactive-geo-maps' ),
							],
							'dependency' => [ 'enabled', '==', true ],
						),
						array(
							'id'         => 'locationHelper',
							'type'       => 'content',
							'content'    => __( 'Use the search form below to find out the coordinates or write them directly in the available fields below.', 'interative-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'specific' ] ],
						),
						array(
							'id'         => 'coordinates',
							'type'       => 'map',
							'title'      => __( 'Location Coordinates', 'interative-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'specific' ] ],
						),
						array(
							'id'         => 'locationHelper',
							'type'       => 'content',
							'content'    => __( 'Write the region or country name or the ID code as listed in "Available regions" section of the correspondent map where it will be used.', 'interactive-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'region' ] ],
						),
						array(
							'id'         => 'regionCode',
							'type'       => 'text',
							'title'      => __( 'Region Code', 'interative-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'region' ] ],
						),
					),
				)
			);

			// Create a section
			\CSF::createSection(
				$prefix,
				array(
					'title'  => __( 'Content', 'interative-geo-maps' ),
					'fields' => array(
						array(
							'id'         => 'contentHelper',
							'type'       => 'content',
							'content'    => __( 'The information below will be used in the map when this entry is included.', 'interative-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', true, 'all' ] ],
						),
						array(
							'id'         => 'contentHelper2',
							'type'       => 'content',
							'content'    => __( 'Since the option to geolocate this entry is disabled, no options display.', 'interative-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', false, 'all' ] ],
						),
						array(
							'id'         => 'tooltipContent',
							'type'       => $tooltip_editor,
							'title'      => __( 'Tooltip Content', 'interative-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', true, 'all' ] ],
						),
						array(
							'id'         => 'content',
							'type'       => $action_content_editor,
							'title'      => __( 'Action Content', 'interative-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', true, 'all' ] ],
						),
						array(
							'id'         => 'value',
							'type'       => 'text',
							'title'      => __( 'Value', 'interative-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', true, 'all' ] ],
						),
					),
				)
			);

			// Actions
			$actions_default            = apply_filters( 'igm_click_actions', [] );
			$actions_default['default'] = _x( 'Default', 'Click Action Option', 'interactive-geo-maps' );

			// Create a section
			\CSF::createSection(
				$prefix,
				array(
					'title'  => __( 'Style & Actions', 'interative-geo-maps' ),
					'fields' => array(
						array(
							'id'         => 'styleHelper',
							'type'       => 'content',
							'content'    => __( 'Since the option to geolocate this entry is disabled, no options display.', 'interative-geo-maps' ),
							'dependency' => [ [ 'enabled', '==', false, 'all' ] ],
						),
						[
							'id'         => 'markerType',
							'type'       => 'button_set',
							'title'      => __( 'Type or Marker', 'interactive-geo-maps' ),
							'desc'       => __( 'Type of marker to be used when populating the map automatically. Leave the default "inherit" value to control this on the map options.', 'interactive-geo-maps' ),
							'default'    => 'undefined',
							'options'    => [
								'undefined'   => __( 'Inherit', 'interactive-geo-maps' ),
								'roundMarker' => __( 'Round Marker', 'interactive-geo-maps' ),
								'iconMarker'  => __( 'Vector Icon', 'interactive-geo-maps' ),
								'imageMarker' => __( 'Image', 'interactive-geo-maps' ),
							],
							'dependency' => [ [ 'enabled', '==', true, 'all' ], [ 'type', '==', 'specific', 'all' ] ],
						],
						[
							'id'         => 'useDefaults',
							'type'       => 'switcher',
							'default'    => true,
							'title'      => __( 'Use defaults', 'interactive-geo-maps' ),
							'subtitle'   => __( 'Use default colors and actions.', 'interactive-geo-maps' ),
							'dependency' => [ [ 'markerType', '!=', 'undefined' ], [ 'type', '==', 'specific', 'all' ] ],
						],
						[
							'id'         => 'useDefaultsRegions',
							'type'       => 'switcher',
							'default'    => true,
							'title'      => __( 'Use defaults', 'interactive-geo-maps' ),
							'subtitle'   => __( 'Use default colors and actions.', 'interactive-geo-maps' ),
							'dependency' => [ 'type', '==', 'region', 'all' ],
						],
						[
							'id'         => 'regionFill',
							'type'       => 'color',
							/* translators: color for the marker */
							'title'      => __( 'Fill Color', 'interactive-geo-maps' ),
							'dependency' => [ 'useDefaultsRegions', '==', false ],
							'default' => isset ( $options['defaultActiveColor'] ) ? $options['defaultActiveColor'] : '#99d8c9',
						],
						[
							'id'         => 'regionHover',
							'type'       => 'color',
							/* translators: color for when marker is hovered */
							'title'      => __( 'Hover Color', 'interactive-geo-maps' ),
							'dependency' => [ 'useDefaultsRegions', '==', false ],
							'default'    => isset ( $options['defaultHoverColor'] ) ? $options['defaultHoverColor'] : '#2ca25f',
						],

						[
							'id'         => 'regionAction',
							'type'       => 'select',
							/* translators: action that gets triggered on click */
							'title'      => __( 'Click Action', 'interactive-geo-maps' ),
							'desc'       => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
							'options'    => $actions_default,
							'default'    => 'default',
							'dependency' => [ 'useDefaultsRegions', '==', false ],
						],
						[
							'id'         => 'radius',
							'type'       => 'spinner',
							'default'    => 10,
							/* translators: Circle radius, size of the marker */
							'title'      => __( 'Radius', 'interactive-geo-maps' ),
							'dependency' => [ [ 'useDefaults', '==', false ], [ 'markerType', '==', 'roundMarker' ] ],
						],
						[
							'id'         => 'image',
							'type'       => 'media',
							'title'      => __( 'Image', 'interactive-geo-maps' ),
							'library'    => 'image',
							'dependency' => [ [ 'useDefaults', '==', false ], [ 'markerType', '==', 'imageMarker' ] ],
						],
						[
							'id'         => 'size',
							'type'       => 'spinner',
							'default'    => '20',
							'unit'       => 'px',
							'validate'   => 'csf_validate_numeric',
							'title'      => __( 'Size', 'interactive-geo-maps' ),
							'dependency' => [ [ 'useDefaults', '==', false ], [ 'markerType', '==', 'imageMarker' ] ],
						],
						[
							'id'           => 'icon',
							'type'         => 'icon',
							'title'        => __( 'Icon', 'interactive-geo-maps' ),
							'default'      => 'fa fa-star',
							'button_title' => __( 'Change Icon', 'interactive-geo-maps' ),
							'dependency'   => [ [ 'useDefaults', '==', false ], [ 'markerType', '==', 'iconMarker' ] ],
						],
						[
							'id'         => 'scale',
							'type'       => 'spinner',
							'default'    => 1,
							'min'        => 0.1,
							'max'        => 10,
							'step'       => 0.1,
							'attributes' => [
								'step' => 'any',
							],
							'title'      => __( 'Scale', 'interactive-geo-maps' ),
							'dependency' => [ [ 'useDefaults', '==', false ], [ 'markerType', '==', 'iconMarker' ] ],
						],
						[
							'id'         => 'horizontalCenter',
							'type'       => 'select',
							'title'      => __( 'Horizontal Center', 'interactive-geo-maps' ),
							'desc'       => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
							'default'    => 'middle',
							'options'    => [
								'left'   => __( 'Left', 'interactive-geo-maps' ),
								'middle' => __( 'Middle', 'interactive-geo-maps' ),
								'right'  => __( 'Right', 'interactive-geo-maps' ),
							],
							'dependency' => [ [ 'useDefaults', '==', false ], [ 'markerType', 'any', 'imageMarker,iconMarker' ] ],

						],
						[
							'id'         => 'verticalCenter',
							'type'       => 'select',
							'title'      => __( 'Vertical Center', 'interactive-geo-maps' ),
							'desc'       => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
							'default'    => 'middle',
							'options'    => [
								'top'    => __( 'Top', 'interactive-geo-maps' ),
								'middle' => __( 'Middle', 'interactive-geo-maps' ),
								'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
							],
							'dependency' => [ [ 'useDefaults', '==', false ], [ 'markerType', 'any', 'imageMarker,iconMarker' ] ],

						],
						[
							'id'         => 'fill',
							'type'       => 'color',
							/* translators: color for the marker */
							'title'      => __( 'Fill Color', 'interactive-geo-maps' ),
							'dependency' => [ [ 'useDefaults', '==', false ], [ 'markerType', '!=', 'imageMarker' ] ],
							'default' => isset ( $options['defaultActiveColor'] ) ? $options['defaultActiveColor'] : '#99d8c9',
						],
						[
							'id'         => 'hover',
							'type'       => 'color',
							/* translators: color for when marker is hovered */
							'title'      => __( 'Hover Color', 'interactive-geo-maps' ),
							'dependency' => [ [ 'useDefaults', '==', false ], [ 'markerType', '!=', 'imageMarker' ] ],
							'default'    => isset ( $options['defaultHoverColor'] ) ? $options['defaultHoverColor'] : '#2ca25f',
						],
						[
							'id'         => 'action',
							'type'       => 'select',
							/* translators: action that gets triggered on click */
							'title'      => __( 'Click Action', 'interactive-geo-maps' ),
							'desc'       => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
							'options'    => $actions_default,
							'default'    => 'default',
							'dependency' => [ 'useDefaults', '==', false ],
						],
					),
				)
			);

		}

		// add a fix to trigger the map properly
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

	}


	/**
	 * Adds the admin js file also when editing cpts with geolocation enabled
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {

		$types = $this->options['geolocate_types'];

		if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {

			$screen = get_current_screen();

			if ( is_object( $screen ) && in_array( $screen->post_type, $types, true ) ) {

				// Admin Custom Javascript
				wp_register_script(
					$this->core->name . '_admin_build',
					plugins_url( 'assets/admin/js/admin-scripts.js', $this->core->file_path ),
					array(),
					$this->core->version,
					true
				);
				wp_enqueue_script( $this->core->name . '_admin_build' );

			}
		}
	}
}

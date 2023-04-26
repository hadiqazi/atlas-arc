<?php

namespace Saltus\WP\Plugin\Saltus\InteractiveMaps\Plugin\Pro;

use Saltus\WP\Plugin\Saltus\InteractiveMaps\Core;

/**
 * Add Pro features
 */
class ProSettings {

	/**
	 * Core instance
	 */
	public $core;

	/**
	 * Constructor class
	 */
	public function __construct( Core $core ) {

		$this->core = $core;

		// add pro meta options
		add_filter( 'igm_model', array( $this, 'add_pro_settings' ) );
		add_action( 'csf_interactive-maps_saved', array( $this, 'flush_rewrite_map' ) );

	}

	/**
	 * Inserts any number of scalars or arrays at the point
	 * in the haystack immediately after the search key ($needle) was found,
	 * or at the end if the needle is not found or not supplied.
	 * Modifies $haystack in place.
	 *
	 * @param array  &$haystack the associative array to search. This will be modified by the function
	 * @param string $needle the key to search for
	 * @param mixed  $stuff one or more arrays or scalars to be inserted into $haystack
	 * @return int the index at which $needle was found
	 */
	public function array_insert_after( &$haystack, $needle, $stuff ) {
		if ( ! is_array( $haystack ) ) {
			return $haystack;
		}

		$new_array = array();
		for ( $i = 2; $i < func_num_args(); ++$i ) {
			$arg = func_get_arg( $i );
			if ( is_array( $arg ) ) {
				$new_array = array_merge( $new_array, $arg );
			} else {
				$new_array[] = $arg;
			}
		}

		$i = 0;
		foreach ( $haystack as $key => $value ) {
			++$i;
			if ( $key == $needle ) {
				break;
			}
		}

		$haystack = array_merge( array_slice( $haystack, 0, $i, true ), $new_array, array_slice( $haystack, $i, null, true ) );

		return $i;
	}

	/**
	 * Add settings for the PRO version
	 *
	 * @param array $settings
	 * @return array modified $settings
	 */
	public function add_pro_settings( $settings ) {

		// Add Version in Pro version
		$settings['settings']['interactive-maps']['footer_text'] = 'Interactive Geo Maps ' . $this->core->version;

		// make it public cpt to allow for translations
		$settings['options']['public'] = true;

		$settings['settings']['interactive-maps']['sections']['editing']['fields']['actionContent_editor'] = [
			'type'    => 'select',
			/* translators: field type = input type (textarea, rich text editor...) */
			'title'   => __( 'Action Content field type', 'interactive-geo-maps' ),
			'desc'    => __( 'Type of field to use to edit the Action content. <br> When using the Rich Text Editor, if you have a lot of entries, you might come across the "<a href=\'https://interactivegeomaps.com/docs/max-input-vars-issue/\' target=\'_blank\'>Too much data" issue</a>.', 'interactive-geo-maps' ),
			'default' => 'textarea',
			'options' => [
				'textarea'  => __( 'Textarea', 'interactive-geo-maps' ),
				/* translators: equivalent to classic editor in a way */
				'wp_editor' => __( 'WP Rich Text Editor', 'interactive-geo-maps' ),
				'text'      => __( 'Text Input', 'interactive-geo-maps' ),
			],
		];

		$settings['settings']['interactive-maps']['sections']['editing']['fields']['description_editor'] = [
			'type'    => 'select',
			/* translators: field type = input type (textarea, rich text editor...) */
			'title'   => __( 'Description field type', 'interactive-geo-maps' ),
			'desc'    => __( 'Type of field to use to edit the description field of the map. <br> When using the Rich Text Editor, if you have a lot of entries, you might come across the "<a href=\'https://interactivegeomaps.com/docs/max-input-vars-issue/\' target=\'_blank\'>Too much data" issue</a>.', 'interactive-geo-maps' ),
			'default' => 'text',
			'options' => [
				'textarea'  => __( 'Textarea', 'interactive-geo-maps' ),
				/* translators: equivalent to classic editor in a way */
				'wp_editor' => __( 'WP Rich Text Editor', 'interactive-geo-maps' ),
				'text'      => __( 'Text Input', 'interactive-geo-maps' ),
			],
		];

		$settings['settings']['interactive-maps']['sections']['editing']['fields']['tooltip_template_editor'] = [
			'type'    => 'select',
			/* translators: field type = input type (textarea, rich text editor...) */
			'title'   => __( 'Tooltip & Action Content Template field type', 'interactive-geo-maps' ),
			'desc'    => __( 'Type of field to use to edit the tooltip template fields and Action Content template (other data sources). <br> When using the Rich Text Editor, if you have a lot of entries, you might come across the "<a href=\'https://interactivegeomaps.com/docs/max-input-vars-issue/\' target=\'_blank\'>Too much data" issue</a>.', 'interactive-geo-maps' ),
			'default' => 'text',
			'options' => [
				'textarea'  => __( 'Textarea', 'interactive-geo-maps' ),
				/* translators: equivalent to classic editor in a way */
				'wp_editor' => __( 'WP Rich Text Editor', 'interactive-geo-maps' ),
				'text'      => __( 'Text Input', 'interactive-geo-maps' ),
			],
		];

		$settings['settings']['interactive-maps']['sections']['editing']['fields']['color_field_type'] = [
			'type'    => 'select',
			/* translators: field type = input type (textarea, text field...) */
			'title'   => __( 'Colours field type', 'interactive-geo-maps' ),
			'desc'    => __( 'Type of field to use to select colours.', 'interactive-geo-maps' ),
			'default' => 'color',
			'options' => [
				'color'  => __( 'Color Picker (recommended)', 'interactive-geo-maps' ),
				'text' => __( 'Text', 'interactive-geo-maps' ),
			],
		];

		$settings['settings']['interactive-maps']['sections']['editing']['fields']['defaultsFieldsHeader'] = [
			'type'    => 'subheading',
			'content' => __( 'Defaults', 'interactive-geo-maps' ),
		];

		$settings['settings']['interactive-maps']['sections']['editing']['fields']['defaultActiveColor'] = [
			'type'    => 'color',
			'default' => '#99d8c9',
			'title'   => __( 'Default Active Colour', 'interactive-geo-maps' ),
			'desc' => __( 'Set the default colour for the active colour field, when you create new maps.', 'interactive-geo-maps' ),
		];

		$settings['settings']['interactive-maps']['sections']['editing']['fields']['defaultHoverColor'] = [
			'type'    => 'color',
			'default' => '#2ca25f',
			'title'   => __( 'Default Hover Colour', 'interactive-geo-maps' ),
			'desc' => __( 'Set the default colour for the hover colour field, when you create new maps.', 'interactive-geo-maps' ),
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['public'] = [
			'type'   => 'fieldset',
			'title'  => __( 'Map single pages', 'interactive-geo-maps' ),
			'subtitle'   => __( 'When enabled the maps will automatically have a single page with that map.', 'interactive-geo-maps' ),
			'fields' => [
				'enabled' => [
					'type'    => 'switcher',
					/* translators: field type = input type (textarea, rich text editor...) */
					'title'   => __( 'Enable', 'interactive-geo-maps' ),
					'default' => false,
				],
				'slug' => [
					'type'       => 'text',
					'title'      => __( 'slug', 'interactive-geo-maps' ),
					'desc'       => __( 'URL slug to use on single map pages. You might need to resave your permalink settings in you get "Page not found" errors.', 'interactive-geo-maps' ),
					'default'    => 'map',
					'dependency' => [ 'enabled', '==', true ],
				],
			],
		];

		
		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['featuresHeader'] = [
			'type'    => 'subheading',
			'content' => __( 'Map Tabs & Features', 'interactive-geo-maps' ),
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['featuresHeaderDescription'] = [
			'type'    => 'content',
			'content' => __( 'You can disable some features from your edit screen, to make the interface cleaner and help with potential <a href="https://interactivegeomaps.com/docs/max-input-vars-issue/" target="_blank">max_input_vars</a> errors from your server. Disable the features you will not be using.', 'interactive-geo-maps' ),
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['regionFeatures'] = [
			'type'   => 'fieldset',
			'title'  => __( 'Regions Features', 'interative-geo-maps' ),
			'subtitle'   => __( 'Disable features from the Regions tab', 'interative-geo-maps' ),
			'fields' => [
				'otherSources' => [
					'type'       => 'switcher',
					'title'      => __( 'Other Data Sources', 'interactive-geo-maps' ),
					'default'    => true,
				],
				'autoLabels' => [
					'type'       => 'switcher',
					'title'      => __( 'Automatic Labels', 'interactive-geo-maps' ),
					'default'    => true,
				],
				'heatMap' => [
					'type'       => 'switcher',
					'title'      => __( 'Heat Map', 'interactive-geo-maps' ),
					'default'    => true,
				],
			]
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['markerFeatures'] = [
			'type'   => 'fieldset',
			'title'  => __( 'Marker Features', 'interative-geo-maps' ),
			'subtitle'   => __( 'Disable features from the Round Markers tab', 'interative-geo-maps' ),
			'fields' => [
				'otherSources' => [
					'type'       => 'switcher',
					'title'      => __( 'Other Data Sources', 'interactive-geo-maps' ),
					'default'    => true,
				],
				'autoLabels' => [
					'type'       => 'switcher',
					'title'      => __( 'Automatic Labels', 'interactive-geo-maps' ),
					'default'    => true,
				],

				'clusters' => [
					'type'       => 'switcher',
					'title'      => __( 'Clusters', 'interactive-geo-maps' ),
					'default'    => true,
				],
				'heatMap' => [
					'type'       => 'switcher',
					'title'      => __( 'Heat Map', 'interactive-geo-maps' ),
					'default'    => true,
				],
				'triggerRegionHover' => [
					'type'       => 'switcher',
					'title'      => __('Highlight Regions','interactive-geo-maps'),
					'desc'      => __( '(Experimental Feature)', 'interactive-geo-maps' ),
					'default'    => false,
				],
			]
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['imageMarkerFeatures'] = [
			'type'   => 'fieldset',
			'title'  => __( 'Image Marker Features', 'interative-geo-maps' ),
			'subtitle'   => __( 'Disable features from the Image Markers tab.', 'interative-geo-maps' ),
			'fields' => [
				'enabled' => [
					'type'       => 'switcher',
					'title'      => __( 'Enabled', 'interactive-geo-maps' ),
					'subtitle'   => __( 'Disable if you prefer to remove the Image Markers tab altogether.', 'interative-geo-maps' ),
					'default'    => true,
				]
			]
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['iconMarkerFeatures'] = [
			'type'   => 'fieldset',
			'title'  => __( 'Icon Marker Features', 'interative-geo-maps' ),
			'subtitle'   => __( 'Disable features from the Icon Markers tab.', 'interative-geo-maps' ),
			'fields' => [
				'enabled' => [
					'type'       => 'switcher',
					'title'      => __( 'Enabled', 'interactive-geo-maps' ),
					'subtitle'   => __( 'Disable if you prefer to remove the Icon Markers tab altogether.', 'interative-geo-maps' ),
					'default'    => true,
				]
			]
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['labelFeatures'] = [
			'type'   => 'fieldset',
			'title'  => __( 'Label Features', 'interative-geo-maps' ),
			'subtitle'   => __( 'Disable features from the Labels tab.', 'interative-geo-maps' ),
			'fields' => [
				'enabled' => [
					'type'       => 'switcher',
					'title'      => __( 'Enabled', 'interactive-geo-maps' ),
					'subtitle'   => __( 'Disable if you prefer to remove the Labels tab altogether.', 'interative-geo-maps' ),
					'default'    => true,
				]
			]
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['lineFeatures'] = [
			'type'   => 'fieldset',
			'title'  => __( 'Line Features', 'interative-geo-maps' ),
			'subtitle'   => __( 'Disable features from the Lines tab.', 'interative-geo-maps' ),
			'fields' => [
				'enabled' => [
					'type'       => 'switcher',
					'title'      => __( 'Enabled', 'interactive-geo-maps' ),
					'subtitle'   => __( 'Disable if you prefer to remove the Lines tab altogether.', 'interative-geo-maps' ),
					'default'    => true,
				]
			]
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['legendFeatures'] = [
			'type'   => 'fieldset',
			'title'  => __( 'Legend Features', 'interative-geo-maps' ),
			'subtitle'   => __( 'Disable features from the Legend tab.', 'interative-geo-maps' ),
			'fields' => [
				'enabled' => [
					'type'       => 'switcher',
					'title'      => __( 'Enabled', 'interactive-geo-maps' ),
					'subtitle'   => __( 'Disable if you prefer to remove the Legend tab altogether.', 'interative-geo-maps' ),
					'default'    => true,
				]
			]
		];

		$settings['settings']['interactive-maps']['sections']['mapFeatures']['fields']['overlayFeatures'] = [
			'type'   => 'fieldset',
			'title'  => __( 'Overlay Features', 'interative-geo-maps' ),
			'subtitle'   => __( 'Disable features from the Overlay tab.', 'interative-geo-maps' ),
			'fields' => [
				'enabled' => [
					'type'       => 'switcher',
					'title'      => __( 'Enabled', 'interactive-geo-maps' ),
					'subtitle'   => __( 'Disable if you prefer to remove the Overlay tab altogether.', 'interative-geo-maps' ),
					'default'    => true,
				]
			]
		];
		

		// add the new map field option together with the geocoding field options
		$map_field = [
			'map_field' => [
				'type'    => 'switcher',
				'title'   => __( 'Use OpenStreeMap', 'interactive-geo-maps' ),
				'desc'    => __( 'If you want to select the coordinates using a visual map with search features, you can enable this option. <br>It might slow down the editor screen a bit, but it could be very helpful. <br> It uses the free Nominatim Geolocation service, which might block you if you do too many requests with the search form. <a href="https://operations.osmfoundation.org/policies/nominatim/" target="_blank">Usage Policy</a>', 'interactive-geo-maps' ) . '<span class="dashicons dashicons-external"></span>',
				'default' => false,
			],
		];

		// uses custom function to add field after specified field
		$add = $this->array_insert_after( $settings['settings']['interactive-maps']['sections']['editing']['fields'], 'googleAutocomplete', $map_field );

		$settings['settings']['interactive-maps']['sections']['lightbox'] = [
			'title'  => __( 'Lightbox', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-desktop',
			'fields' => [
				'lightbox_width'       => [
					'type'    => 'spinner',
					'title'   => __( 'Lightbox Width', 'interactive-geo-maps' ),
					'desc'    => __( 'Width in pixels of the lightbox used in the click actions.', 'interactive-geo-maps' ),
					'default' => 600,
					'min'     => 100,
					'max'     => 2800,
					'step'    => 20,
					'unit'    => 'px',
				],

				'lightbox_height_auto' => [
					'type'    => 'button_set',
					'title'   => __( 'Lightbox Height', 'interactive-geo-maps' ),
					'desc'    => __( 'Specify height or have it automatically calculated.', 'interactive-geo-maps' ),
					'options' => array(
						/* translators: for the css rule "auto" from "Automatic" */
						'auto'   => __( 'Auto', 'interactive-geo-maps' ),
						/* translators: for Custom Size in Pixels */
						'custom' => __( 'Custom', 'interactive-geo-maps' ),
					),
					'default' => 'auto',
				],

				'lightbox_height'      => [
					'type'       => 'spinner',
					'title'      => __( 'Lightbox Height', 'interactive-geo-maps' ),
					'desc'       => __( 'Heigth in pixels of the lightbox height in the click actions.', 'interactive-geo-maps' ),
					'default'    => 500,
					'min'        => 20,
					'max'        => 2800,
					'step'       => 20,
					'unit'       => 'px',
					'dependency' => [ 'lightbox_height_auto', '==', 'custom' ],
				],
			],
		];

		$settings['settings']['interactive-maps']['sections']['external'] = [
			'title'  => __( 'External Data Sources', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-database',
			'fields' => [
				'external_intro' => [
					'type'    => 'content',
					'content' => __( 'Options to control how the plugin will handle external data sources. <br> For example external JSON data files.', 'interactive-geo-maps' ),
				],
				'cache_time'     => [
					'type'    => 'number',
					'unit'    => __( 'minutes', 'interactive-geo-maps' ),
					'title'   => __( 'Cache Time', 'interactive-geo-maps' ),
					'desc'    => __( 'Time in minutes to cache data fetched from an external URL. <br>Use 0 if you prefer not to use cache', 'interactive-geo-maps' ),
					'default' => '30',
				],
			],
		];

		$settings['settings']['interactive-maps']['sections']['visual'] = [
			'title'  => __( 'Visuals', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-paint-brush',
			'fields' => [
				'visuals_intro'     => [
					'type'    => 'content',
					'content' => __( 'Most of these options will control how some of the map elements display in your pages, when published.', 'interactive-geo-maps' ),
				],
				'contentContainerHeader' => [
					'type'    => 'subheading',
					'content' => __( 'Content Containers', 'interactive-geo-maps' ),
				],
				'rightContent' => [
					'type'   => 'fieldset',
					'title'  => __( 'Content on the right', 'interative-geo-maps' ),
					'fields' => [
						'backgroundColor' => [
							'type'    => 'color',
							'default' => 'transparent',
							'title'   => __( 'Backgound', 'interactive-geo-maps' ),
							'content' => __( 'Set the color of the content container when using the action to display content on the right.', 'interactive-geo-maps' ),
						],
						'padding' => [
							'type'        => 'spacing',
							'output_mode' => 'padding',
							'title'       => __( 'Padding', 'interactive-geo-maps' ),
						],
						'gutterWidth' => [
							'type'       => 'spinner',
							'title'      => __( 'Gutter Width', 'interactive-geo-maps' ),
							'default'    => 0,
							'unit'       => '%',
							'step'       => 1,
							'desc'       => __( 'Space between the map and the content container. <br>By default the content on the right container will take 33% of the available space. <br>The gutter space will be taken out these 33%.', 'interactive-geo-maps' ),
							'validate'   => 'csf_validate_numeric',
						],
					],
				],

				'otherFieldsHeader' => [
					'type'    => 'subheading',
					'content' => __( 'Map Dropdown', 'interactive-geo-maps' ),
				],
				'use_choices'       => [
					'type'    => 'switcher',
					'default' => true,
					'title'   => __( 'Use <i>Choices</i> Dropdown', 'interactive-geo-maps' ),
					'content' => __( '<i>Choices</i> is a library that turns a normal select dropdown field into an enhanced dropdown with a search feature.', 'interactive-geo-maps' ),
				],
				'visuals_end'       => [
					'type'    => 'content',
					'content' => __( 'More options will be available in future versions.', 'interactive-geo-maps' ),
				],
			],
		];

		$settings['settings']['interactive-maps']['sections']['capabilities'] = [
			'title'  => __( 'Capabilities', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-lock',
			'fields' => [
				'cap_intro'     => [
					'type'    => 'content',
					'content' => __( 'Edit who can create and edit maps. If you\'re not sure what capabilities are, leave this empty. <br> Usually you won\'t need to edit this.', 'interactive-geo-maps' ),
				],
				
				'capability'  => [
					'type'    => 'text',
					'title'   => __( 'Capability Type', 'interactive-geo-maps' ),
					'desc'    => __( 'Refer to the <code>capability_type</code> argument from the <code>register_post_type</code>. <a target="_blank" href="https://developer.wordpress.org/reference/functions/register_post_type/#capability_type">More information</a>.', 'interactive-geo-maps' ),
					'default' => 'page',
				],
			],
		];

		$settings['settings']['interactive-maps']['sections']['amcharts'] = [
			'title'  => __( 'Amcharts', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-map',
			'fields' => [
				'amcharts_intro'   => [
					'type'    => 'content',
					'content' => __( 'The plugin uses the <a href="https://www.amcharts.com/" target="_blank">amcharts</a> library to generate the interactive maps.<br>Below you can customize some settings related to the library.', 'interactive-geo-maps' ),
				],
				'amcharts_version' => [
					'type'    => 'text',
					'title'   => __( 'Version', 'interactive-geo-maps' ),
					'desc'    => __( 'Specify a version of amcharts library to load. Leave blank to use recommended one.<br> <a href="https://github.com/amcharts/amcharts4/blob/master/dist/es2015/CHANGELOG.md" target="_blank">Amcharts Changelog</a>.', 'interactive-geo-maps' ),
					'default' => '',
				],

				'amcharts_locale'  => [
					'type'    => 'text',
					'title'   => __( 'Locale', 'interactive-geo-maps' ),
					'desc'    => __( 'Specify a <a target="_blank" href="https://www.amcharts.com/docs/v4/concepts/locales/">locale of amcharts</a> to load. Example <code>en_US</code><br> This is usually only needed to format dates and numbers.<br> Leave blank to use the default. <br> <a href="https://github.com/amcharts/amcharts4/tree/master/src/lang" target="_blank">List of available codes</a>.', 'interactive-geo-maps' ),
					'default' => '',
				],

				'amcharts_url'  => [
					'type'    => 'text',
					'title'   => __( 'Amcharts Library URL', 'interactive-geo-maps' ),
					'desc'    => __( 'If you want to <a target="_blank" href="https://interactivegeomaps.com/docs/self-host-library">self host amcharts library</a> you will need to specify the URL here. Leave blank to use CDN. <br>If you self host the library, you will not be able to specify the version in the option above.', 'interactive-geo-maps' ),
					'default' => '',
				],
			],
		];

		$settings['settings']['interactive-maps']['sections']['backup'] = [
			'title'  => __( 'Backup', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-shield',
			'fields' => [
				'backup_intro'   => [
					'type'    => 'content',
					'content' => __( 'Backup your <strong>settings</strong> by copying the field below.<br>If you want to backup your maps also, go to Tools > Export > Maps.', 'interactive-geo-maps' ),
				],

				'backup'  => [
					'type'    => 'backup',
					'title'   => __( 'Backup', 'interactive-geo-maps' ),
				],
			],
		];

		return $settings;
	}

	/**
	 * Force flush rewrite
	 *
	 * @param [type] $value
	 * @return value
	 */
	public function flush_rewrite_map(){
		flush_rewrite_rules( false );
	}
}

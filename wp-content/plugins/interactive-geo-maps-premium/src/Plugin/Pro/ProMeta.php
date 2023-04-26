<?php

namespace Saltus\WP\Plugin\Saltus\InteractiveMaps\Plugin\Pro;

use Saltus\WP\Plugin\Saltus\InteractiveMaps\Core;

/**
 * Add Pro features
 */
class ProMeta {

	/**
	 * Define Icons
	 */
	public function __construct() {

		// add pro meta options
		add_filter( 'igm_model', array( $this, 'meta_options' ), 10, 1 );

		// add extra map options for meta
		add_filter( 'igm_map_options', array( $this, 'map_options' ) );

	}

	public function meta_options( $model ) {

		$actions = apply_filters( 'igm_click_actions', [] );
		$options = get_option( 'interactive-maps' );

		$action_content_editor = isset( $options['actionContent_editor'] ) ? $options['actionContent_editor'] : 'textarea';
		$tooltip_editor        = isset( $options['tooltip_editor'] ) ? $options['tooltip_editor'] : 'textarea';
		$color_field           = isset( $options['color_field_type'] ) ? $options['color_field_type'] : 'color';
		$coordinates_editor    = isset( $options['map_field'] ) && $options['map_field'] ? 'map' : 'fieldset';
		$ttemplate_editor      = isset( $options['tooltip_template_editor'] ) ? $options['tooltip_template_editor'] : 'text';

		// unset Pro promotion box and links
		unset( $model['meta']['go_pro'] );
		unset( $model['meta']['map_info']['sections']['goPro'] );
		unset( $model['meta']['map_info']['sections']['regions']['fields']['regionDefaults']['desc'] );
		unset( $model['meta']['map_info']['sections']['roundMarkers']['fields']['markerDefaults']['desc'] );
		unset( $model['meta']['map_info']['sections']['visual']['fields']['goPro_visual'] );

		// Available Actions with the extra default option
		$actions_default            = $actions;
		$actions_default['default'] = _x( 'Default', 'Click Action Option', 'interactive-geo-maps' );

		// custom action regions
		$region_actions                       = $actions;
		$region_actions['customRegionAction'] = _x( 'Custom', 'Click Action Option', 'interactive-geo-maps' );

		// custom action round markers
		$marker_actions                       = $actions;
		$marker_actions['customMarkerAction'] = _x( 'Custom', 'Click Action Option', 'interactive-geo-maps' );

		// custom action round markers
		$icon_marker_actions                           = $actions;
		$icon_marker_actions['customIconMarkerAction'] = _x( 'Custom', 'Click Action Option', 'interactive-geo-maps' );

		// custom action round markers
		$image_marker_actions                            = $actions;
		$image_marker_actions['customImageMarkerAction'] = _x( 'Custom', 'Click Action Option', 'interactive-geo-maps' );

		// custom action round markers
		$label_actions                      = $actions;
		$label_actions['customLabelAction'] = _x( 'Custom', 'Click Action Option', 'interactive-geo-maps' );

		// Update actions to include the custom action in regions and markers
		$model['meta']['map_info']['sections']['regions']['fields']['regionDefaults']['fields']['action']['options'] = $region_actions;
		$model['meta']['map_info']['sections']['regions']['fields']['regionDefaults']['fields']['customAction']      = [
			'type'       => 'code_editor',
			'title'      => __( 'Custom Javascript Action', 'interactive-geo-maps' ),
			/* translators: "object with the data" refers to a javascript object */
			'desc'       => __( 'When a marker is clicked a function will be called where the object with the data from the clicked region will be passed. You can write the contents of the function here. <a href="https://interactivegeomaps.com/docs/pro-custom-click-actions/" target="_blank">More information</a> <span class="dashicons dashicons-external"></span>', 'interactive-geo-maps' ),
			'dependency' => [ 'action', '==', 'customRegionAction' ],
		];

		$model['meta']['map_info']['sections']['regions']['fields']['regionDefaults']['fields']['triggerClickOnHover'] = [
			'type'  => 'switcher',
			'title' => __( 'Trigger on Hover', 'interactive-geo-maps' ),
			'desc'  => __( 'When enabled, when the user hovers a region, it will trigger a click automatically.', 'interactive-geo-maps' ),
		];

		$model['meta']['map_info']['sections']['roundMarkers']['fields']['markerDefaults']['fields']['action']['options'] = $marker_actions;

		$model['meta']['map_info']['sections']['roundMarkers']['fields']['markerDefaults']['fields']['customAction'] = [
			'type'       => 'code_editor',
			'title'      => __( 'Custom Javascript Action', 'interactive-geo-maps' ),
			/* translators: "object with the data" refers to a javascript object */
			'desc'       => __( 'When a marker is clicked a function will be called where the object with the data from the clicked region will be passed. You can write the contents of the function here. <a href="https://interactivegeomaps.com/docs/pro-custom-click-actions/" target="_blank">More information</a> <span class="dashicons dashicons-external"></span>', 'interactive-geo-maps' ),
			'dependency' => [ 'action', '==', 'customMarkerAction' ],
		];

		$model['meta']['map_info']['sections']['roundMarkers']['fields']['markerDefaults']['fields']['triggerClickOnHover'] = [
			'type'  => 'switcher',
			'title' => __( 'Trigger on Hover', 'interactive-geo-maps' ),
			'desc'  => __( 'When enabled, when the user hovers a marker, it will trigger a click automatically.', 'interactive-geo-maps' ),
		];


		// add extra fields to region repeater
		$model['meta']['map_info']['sections']['regions']['fields']['regions']['fields'] = array_merge(
			$model['meta']['map_info']['sections']['regions']['fields']['regions']['fields'],
			[

				'useDefaults'   => [
					'type'     => 'switcher',
					'title'    => __( 'Use defaults', 'interactive-geo-maps' ),
					'subtitle' => __( 'Use default colors and actions.', 'interactive-geo-maps' ),
					'default'  => true,
				],

				'fill'          => [
					'type'       => $color_field,
					/* translators: color for the shape/region on the map */
					'title'      => __( 'Fill Color', 'interactive-geo-maps' ),
					'default' => isset ( $options['defaultActiveColor'] ) ? $options['defaultActiveColor'] : '#99d8c9',
					'dependency' => [ 'useDefaults', '==', false ],
				],

				'hover'         => [
					'type'       => $color_field,
					/* translators: color for the shape/region on the map when hovered */
					'title'      => __( 'Hover Color', 'interactive-geo-maps' ),
					'default'    => isset ( $options['defaultHoverColor'] ) ? $options['defaultHoverColor'] : '#2ca25f',
					'dependency' => [ 'useDefaults', '==', false ],
				],
				'action'        => [
					'type'       => 'select',
					'title'      => __( 'Click Action', 'interactive-geo-maps' ),
					'desc'       => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
					'options'    => $actions_default,
					'default'    => 'default',
					'dependency' => [ 'useDefaults', '==', false ],
				],
				'pageBelowInfo' => [
					'type'       => 'submessage',
					'style'      => 'info',
					'content'    => __( 'To display a page below, add the numeric ID of the page you want to display in the "Action Content" above.', 'interactive-geo-maps' ),
					'dependency' => [ [ 'action', '==', 'igm_display_page_below' ], [ 'action', '==', 'igm_display_page_below_and_scroll' ] ],
				],
				'value'         => [
					'type'  => 'text',
					'title' => __( 'Value', 'interactive-geo-maps' ),
					'desc'  => 'Use numeric values if possible. Don\'t use the minus sign (-).',
				],

			]
		);

		$formatted_json       = '<code>[{ "id": "Afghanistan", "value": "35530081" },{ "id": "Albania", "value": "2930187" }]</code>';
		$formatted_child_json = '<code>{"title":"World Info","countries":[{"id":"Afghanistan","value":"35530081"},{"id":"Albania","value":"2930187"}]}</code>';


		if( ! isset( $options['regionFeatures'] ) || empty( $options['regionFeatures'] ) || ( isset( $options['regionFeatures'] ) && isset( $options['regionFeatures']['otherSources']) && $options['regionFeatures']['otherSources'] ) ) {

			$model['meta']['map_info']['sections']['regions']['fields']['regionsDataSource'] = [
				'type'     => 'fieldset',
				'title'    => __( 'Other Data Sources', 'interactive-geo-maps' ),
				'subtitle' => __( 'Use other data sources to populate the regions.', 'interactive-geo-maps' ),
				'sanitize' => __NAMESPACE__ . '\ProMeta::legacy_raw_sanitize',
				'fields'   => [
					'enabled'                => [
						'type'    => 'switcher',
						'default' => false,
						'title'   => __( 'Enabled', 'interactive-geo-maps' ),
					],
					'type'                   => [
						'type'       => 'select',
						'title'      => __( 'Source of data', 'interactive-geo-maps' ),
						'default'    => 'categories',
						'options'    => [
							'categories'          => __( 'Post Categories', 'interactive-geo-maps' ),
							'tags'                => __( 'Post Tags', 'interactive-geo-maps' ),
							'custom_tax'          => __( 'Custom Taxonomy', 'interactive-geo-maps' ),
							/* translators: Raw Data Content - user will need to write/include their own "non-prepared" data in a textfield */
							'raw_legacy'          => __( 'Raw Data Content', 'interactive-geo-maps' ),
							'json_data'           => __( 'JSON data', 'interactive-geo-maps' ),
							'google_spreadsheet'  => __( 'Google Spreadsheet', 'interactive-geo-maps' ),
						],
						'dependency' => [ 'enabled', '==', true ],
					],
					'raw'                    => [
						'type'       => 'textarea',
						/* translators: Raw Data - user will need to write/include their own "non-prepared" data in a textfield */
						'title'      => __( 'Raw Data', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => __(
							'Currently only accepts data with the following format:<br>
						Region Code, Title, Tooltip Text, Action Value, HTML Color Value;',
							'interactive-geo-maps'
						),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'raw_legacy' ] ],
					],
					'google_api_key'                   => [
						'type'       => 'text',
						'title'      => __( 'Google API Key', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => __('The API Key should have the Spreadsheet service enabled.', 'interactive-gep-maps') . '<br><a href="https://interactivegeomaps.com/docs/pro-populate-map-with-data-from-google-spreadsheet/" target="_blank">' . __( 'More Information', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_sheet_id'                   => [
						'type'       => 'text',
						'default'    => '',
						'title'      => __( 'Google Sheet ID', 'interactive-geo-maps' ),
						'desc'       => __( ' You can find the spreadsheet ID in a Google Sheets URL:<br><pre>https://docs.google.com/spreadsheets/d/SPREADSHEET-ID/edit#gid=0</pre>' ) . ' <a href="https://interactivegeomaps.com/docs/pro-populate-map-with-data-from-google-spreadsheet/" target="_blank">' . __( 'More Information', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_sheet_range'                   => [
						'type'       => 'text',
						'default'    => 'Sheet1',
						'title'      => __( 'Sheet Range', 'interactive-geo-maps' ),
						'desc'       => __( 'Leave default <code>Sheet1</code> if in doubt. Title of the Sheet or specific cell range.<br> You should use <a href="https://developers.google.com/sheets/api/guides/concepts#a1_notation" target="_blank">A1 notation</a> <span class="dashicons dashicons-external"></span>', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_data_id'                   => [
						'type'       => 'text',
						'default'    => '',
						'title'      => __( 'ID Property', 'interactive-geo-maps' ),
						'desc'       => __( 'Column name for the region code. Avoid using spaces or special characters in your spreadsheet to avoid errors.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_data_content'                   => [
						'type'       => 'text',
						'default'    => '',
						'title'      => __( 'Action Content Property', 'interactive-geo-maps' ),
						'desc'       => __( 'Column name for the action content. Avoid using spaces or special characters in your spreadsheet to avoid errors.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'json'                   => [
						'type'       => 'textarea',
						/* translators: Raw Data - JSON code */
						'title'      => __( 'Raw JSON Data', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => __( 'The data should be an array of objects. Example:' ) . '<br>' . $formatted_json . '<br>' . __( 'You can also use a URL to a JSON file, instead of the JSON code. URL content will be cached for some time.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'json_id'                => [
						'type'       => 'text',
						/* translators: Raw Data - JSON code */
						'title'      => __( 'JSON ID property', 'interactive-geo-maps' ),
						'default'    => 'id',
						'desc'       => __( 'If your data entries do not contain an <code>id</code> property, specify which property to use instead. You can use dot notation.' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'json_data_source'       => [
						'type'       => 'text',
						/* translators: Raw Data - JSON code */
						'title'      => __( 'JSON data source property', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => sprintf( __( 'Leave empty if your data is at the first level of the returned json object. If your region data is inside a property, specify it here. You can use dot notation. <br> For example, if your data is formatted like this:<br>%s<br>then use <code>countries</code> as your data source property.', 'interactive-geo-maps' ), $formatted_child_json ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'json_action_content'    => [
						'type'       => 'text',
						'title'      => __( 'JSON Action Content property', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => __( 'If your data entries do not contain a <code>content</code> property, specify which property to use instead. This is the property that will contain the content that is triggered when the region is clicked.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'custom_tax'             => [
						'type'       => 'text',
						/* translators: Raw Data - JSON code */
						'title'      => __( 'Custom Taxonomy Identifier', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => __( 'Write the identifier of the taxonomy you want to use. <br> This will only work well with the action to open URL, which will open the URL for this taxonomy archive page. You can also use a JSON object with args from <a target="_blank" href="https://developer.wordpress.org/reference/classes/wp_term_query/__construct/">WP Term Query</a> to build a custom taxonomy query.', 'interactive-world-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'custom_tax' ] ],
					],
					'categoriesIncludeEmpty' => [
						'type'       => 'switcher',
						'title'      => __( 'Include empty', 'interactive-geo-maps' ),
						'default'    => false,
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', 'any', 'categories,custom_tax' ] ],
					],
					'tagsIncludeEmpty'       => [
						'type'       => 'switcher',
						'title'      => __( 'Include empty', 'interactive-geo-maps' ),
						'default'    => false,
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'tags' ] ],
					],
					'action_content_template'             => [
						'type'       => $ttemplate_editor,
						'title'      => __( 'Action Content Template', 'interactive-geo-maps' ),
						'default'    => '{content}',
						'desc'       => __( 'You can setup a custom template for the action content with <strong>placeholders</strong> (using brackets) for parameters that exist in your content object.<br>Leave blank or the default <code>{content}</code> if you prefer to use the default or defined above value.', 'interactive-world-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', 'any', 'google_spreadsheet,json_data' ] ],
					],
				],
			];
		}

		$model['meta']['map_info']['sections']['regions']['fields']['regionActiveState'] = [
			'type'     => 'fieldset',
			/* translators: legend refers to a caption or visual element explaining colours on map */
			'title'    => __( 'Active State', 'interactive-geo-maps' ),
			'subtitle' => __( 'Display regions in different colour when clicked.', 'interactive-geo-maps' ),
			'fields'   => [
				'enabled' => [
					'type'    => 'switcher',
					'title'   => __( 'Enabled', 'interactive-geo-maps' ),
					'default' => false,
				],
				'source'  => [
					'type'       => 'select',
					'title'      => __( 'Colour', 'interactive-geo-maps' ),
					'default'    => 'hover',
					'options'    => [
						'hover'  => __( 'Same as hover', 'interactive-geo-maps' ),
						'custom' => __( 'Custom', 'interactive-geo-maps' ),
					],
					'dependency' => [ 'enabled', '==', true ],

				],
				'fill'    => [
					'type'       => $color_field,
					'title'      => __( 'Custom Color', 'interactive-geo-maps' ),
					'dependency' => [ 'source', '!=', '0' ],
					'default' => isset ( $options['defaultActiveColor'] ) ? $options['defaultActiveColor'] : '#99d8c9',
					'dependency' => [ [ 'enabled', '==', true ], [ 'source', '==', 'custom' ] ],
				],

			],
		];

		$model['meta']['map_info']['sections']['regions']['fields']['regionLegend'] = [
			'type'     => 'fieldset',
			/* translators: legend refers to a caption or visual element explaining colours on map */
			'title'    => __( 'Show in Legend', 'interactive-geo-maps' ),
			'subtitle' => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
			'fields'   => [
				'enabled' => [
					'type'    => 'switcher',
					'title'   => __( 'Enabled', 'interactive-geo-maps' ),
					'default' => false,
				],
				'title'   => [
					'type'       => 'text',
					/* translators: legend refers to a caption or visual element explaining colours on map */
					'title'      => __( 'Title in Legend', 'interactive-geo-maps' ),
					'dependency' => [ 'enabled', '==', true ],
				],
			],
		];

		if( ! isset( $options['regionFeatures'] ) || empty( $options['regionFeatures'] ) || ( isset( $options['regionFeatures'] ) && isset( $options['regionFeatures']['autoLabels']) && $options['regionFeatures']['autoLabels'] ) ) {


			$model['meta']['map_info']['sections']['regions']['fields']['regionLabels'] = [
				'type'     => 'fieldset',
				/* translators: labels are text elements that will go on top of the regions on the map */
				'title'    => __( 'Automatic Labels', 'interactive-geo-maps' ),
				'subtitle' => __( 'When enabled you can move the labels to a custom position in the map preview.', 'interactive-geo-maps' ),
				'desc'     => __( 'Automaticaly add labels to the regions. <br> This will calculate the visual center of each region and add a label to it, based on the available data. It might fail or have a wrong position on some cases. You can drag & drop them into a new position.', 'interactive-geo-maps' ),
				'fields'   => [
					'source'                       => [
						'type'    => 'select',
						'title'   => __( 'Automatic Labels', 'interactive-geo-maps' ),
						'default' => '0',
						'options' => [
							'0'      => _x( 'Disabled', 'Automatic Labels option', 'interactive-geo-maps' ),
							'{name}' => _x( 'from Name', 'Automatic Labels option', 'interactive-geo-maps' ),
							'{id}'   => _x( 'from Region Code', 'Automatic Labels option', 'interactive-geo-maps' ),
							'code'   => _x( 'Converted Region Code', 'Automatic Labels option', 'interactive-geo-maps' ),
							'custom' => _x( 'Custom Source', 'Automatic Labels option', 'interactive-geo-maps' ),
						],
					],
					'customSource' => [
						'type'       => 'text',
						'default'    => 'name',
						'title'      => __( 'Label Source Property', 'interactive-geo-maps' ),
						'desc'       => __( 'Object property from where to read the label information. You can use dot notation.', 'interactive-geo-maps' ),
						'dependency' => [ 'source', '==', 'custom' ],
					],
					'activeOnly'                   => [
						'type'       => 'switcher',
						/* translators: Only active - as in - apply only on regions with data */
						'title'      => __( 'Only Active', 'interactive-geo-maps' ),
						'desc'       => __( 'Display automatic label only on regions with custom data', 'interactive-geo-maps' ),
						'default'    => true,
						'dependency' => [ 'source', '!=', '0' ],
					],
					'fontSize'                     => [
						'type'       => 'spinner',
						'default'    => 15,
						/* translators: Font size */
						'title'      => _x( 'Size', 'Font Size', 'interactive-geo-maps' ),
						'dependency' => [ 'source', '!=', '0' ],

					],
					'mobileFontSize'                     => [
						'type'    => 'spinner',
						'title'   => __( 'Mobile Size', 'interactive-geo-maps' ),
						'default' => 100,
						'unit'    => '%',
						/* translators: Font size */
						'dependency' => [ 'source', '!=', '0' ],
					],
					'fill'                         => [
						'type'       => $color_field,
						'title'      => __( 'Color', 'interactive-geo-maps' ),
						'dependency' => [ 'source', '!=', '0' ],
						'default'    => '#111111',

					],
					'hover'                        => [
						'type'       => $color_field,
						'title'      => __( 'Hover Color', 'interactive-geo-maps' ),
						'dependency' => [ 'source', '!=', '0' ],
						'default'    => '#000000',

					],
					'horizontalCenter'             => [
						'type'       => 'select',
						/* translators: option to set where a label will align to */
						'title'      => __( 'Horizontal center', 'interactive-geo-maps' ),
						'default'    => 'middle',
						'dependency' => [ 'source', '!=', '0' ],
						'options'    => [
							'left'   => _x( 'Left', 'Horizontal Alignment Option', 'interactive-geo-maps' ),
							'right'  => _x( 'Right', 'Horizontal Alignment Option', 'interactive-geo-maps' ),
							'middle' => _x( 'Middle', 'Horizontal Alignment Option', 'interactive-geo-maps' ),
						],

					],
					'verticalCenter'               => [
						'type'       => 'select',
						/* translators: option to set where a label will align to */
						'title'      => __( 'Vertical center', 'interactive-geo-maps' ),
						'default'    => 'middle',
						'dependency' => [ 'source', '!=', '0' ],

						'options'    => [
							'top'    => _x( 'Top', 'Vertical Alignment Option', 'interactive-geo-maps' ),
							'bottom' => _x( 'Bottom', 'Vertical Alignment Option', 'interactive-geo-maps' ),
							'middle' => _x( 'Middle', 'Vertical Alignment Option', 'interactive-geo-maps' ),
						],
					],

					'nonScaling'                   => [
						'type'       => 'switcher',
						/* translators: set if element will resize when we zoom the map, for example */
						'title'      => __( 'Non Scaling', 'interactive-geo-maps' ),
						'default'    => true,
						'dependency' => [ 'source', '!=', '0' ],
					],
					'regionLabelCustomCoordinates' => [
						'type'       => 'textarea',
						'default'    => '',
						// 'class'      => 'hidden',
						'attributes' => [
							'class' => 'hidden',
						],
						'dependency' => [ 'source', '!=', '0' ],
					],
				],
			];
		}

		$model['meta']['map_info']['sections']['regions']['fields']['regionsGroupHover'] = [
			'type'    => 'switcher',
			/* translators: Group - verb - to group */
			'title'   => __( 'Group', 'interactive-geo-maps' ),
			'default' => false,
			'desc'    => __( 'Set hover state to all regions when hovering one of them.', 'interactive-geo-maps' ),
		];

		if( ! isset( $options['regionFeatures'] ) || empty( $options['regionFeatures'] ) || ( isset( $options['regionFeatures'] ) && isset( $options['regionFeatures']['heatMap']) && $options['regionFeatures']['heatMap'] ) ) {


			$model['meta']['map_info']['sections']['regions']['fields']['heatMapRegions'] = [
				'type'     => 'fieldset',
				/* translators: Heat Map or Heatmap */
				'title'    => __( 'Heat Map (Choropleth)', 'interactive-geo-maps' ),
				'subtitle' => __( 'Calculate region colours automatically', 'interactive-geo-maps' ),
				'fields'   => [
					'enabled'  => [
						'type'    => 'switcher',
						'default' => false,
						'title'   => __( 'Enabled', 'interactive-geo-maps' ),
					],

					'type'     => [
						'title'      => __( 'Type', 'interactive-geo-maps' ),
						'type'       => 'select',
						'default'    => 'gradient',
						'options'    => [
							'gradient' => __( 'Gradient', 'interactive-geo-maps' ),
							'range'    => __( 'Custom Ranges', 'interactive-geo-maps' ),
						],
						'dependency' => [ 'enabled', '==', true ],
					],

					'range'    => [
						'type'       => 'group',
						'title'      => __( 'Range sets', 'interactive-geo-maps' ),
						'fields'     => [
							'rule' => [
								'type'  => 'text',
								'title' => __( 'Rule (>=)', 'interactive-geo-maps' ),
								'desc'  => __( 'Use numbers. Numbers bigger than this will have the color below.<br>Until the next rule kicks in and defines a new color.<br>So number >= X will have the color below.', 'interactive-geo-maps' ),
							],
							'fill' => [
								'type'    => $color_field,
								/* translators: color for the shape/region on the map */
								'title'   => __( 'Fill Color', 'interactive-geo-maps' ),
								'default' => isset ( $options['defaultActiveColor'] ) ? $options['defaultActiveColor'] : '#99d8c9',
							],
						],
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'range' ] ],
					],

					'minColor' => [
						'type'       => $color_field,
						/* translators: Minimum color for heatmap */
						'title'      => __( 'Minimum Color', 'interactive-geo-maps' ),
						'default'    => '#f5f5f5',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'maxColor' => [
						'type'       => $color_field,
						'desc'       => __( 'Avoid using transparency in your colours, since the gradient will not consider it and the colors might seem off.', 'interactive-geo-maps' ),
						/* translators: Maximum color for heatmap */
						'title'      => __( 'Maximum Color', 'interactive-geo-maps' ),
						'default'    => '#333333',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'legend'   => [
						'type'       => 'switcher',
						'default'    => false,
						/* translators: Legend: caption or element explaining colors on the map */
						'title'      => __( 'Legend', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'minLabel' => [
						'type'       => 'text',
						/* translators: as in "Label for minimum color for heatmap" */
						'title'      => __( 'Label for Minimum', 'interactive-geo-maps' ),
						'default'    => 'Min',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ], [ 'legend', '==', true ] ],
					],
					'maxLabel' => [
						'type'       => 'text',
						/* translators: as in "Label for maximum color for heatmap" */
						'title'      => __( 'Label for Maximum', 'interactive-geo-maps' ),
						'default'    => 'Max',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ], [ 'legend', '==', true ] ],
					],
					'label' => [
						'type'       => 'text',
						/* translators: as in "Label for maximum color for heatmap" */
						'title'      => __( 'Label Legend', 'interactive-geo-maps' ),
						'default'    => '',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ], [ 'legend', '==', true ] ],
					],
					'noHover'  => [
						'type'       => 'switcher',
						'default'    => false,
						'title'      => __( 'Disable Hover', 'interactive-geo-maps' ),
						'desc'       => __( 'Disable Hover Color Change', 'interactive-geo-maps' ),
						'dependency' => [ 'enabled', '==', true ],
					],
					'source'   => [
						'type'       => 'text',
						/* translators: Identify which field the heatmap should read values from */
						'title'      => __( 'Source field ID', 'interactive-geo-maps' ),
						'default'    => 'value',
						'desc'       => __( 'Field from where you want the heatmap to read values from. You can specify a custom one if you\'re using a custom data source.', 'interactive-geo-maps' ),
						'dependency' => [ 'enabled', '==', true ],
					],
				],
			];

		}

		$model['meta']['map_info']['sections']['regions']['fields']['regionsTooltipTemplate'] = [
			'type'    => $ttemplate_editor,
			'title'   => __( 'Tooltip Template', 'interactive-geo-maps' ),
			'default' => '',
			'desc'    => __( 'Override default tooltip template for this series of data.<br>Leave blank to use default. <a href="https://interactivegeomaps.com/docs/pro-tooltip-content-and-design/">More Information</a>  <span class="dashicons dashicons-external"></span>. ', 'interactive-geo-maps' ),
		];

		// set marker coordinates editor
		$model['meta']['map_info']['sections']['roundMarkers']['fields']['roundMarkers']['fields']['coordinates']['type'] = $coordinates_editor;

		// add extra description for markers
		$model['meta']['map_info']['sections']['roundMarkers']['fields']['roundMarkers']['fields']['id']['desc'] = __( 'Use unique titles, if possible without special characters.', 'interactive-gep-maps' );


		// round markers options
		$model['meta']['map_info']['sections']['roundMarkers']['fields']['roundMarkers']['fields'] = array_merge(
			$model['meta']['map_info']['sections']['roundMarkers']['fields']['roundMarkers']['fields'],
			[
				'useDefaults'   => [
					'type'     => 'switcher',
					'title'    => __( 'Use defaults', 'interactive-geo-maps' ),
					'subtitle' => __( 'Use default colors and actions.', 'interactive-geo-maps' ),
					'default'  => true,
				],
				'radius'        => [
					'type'       => 'spinner',
					'default'    => 10,
					/* translators: Circle radius, size of the marker */
					'title'      => __( 'Radius', 'interactive-geo-maps' ),
					'dependency' => [ 'useDefaults', '==', false ],
				],
				'fill'          => [
					'type'       => $color_field,
					/* translators: color for the marker */
					'title'      => __( 'Fill Color', 'interactive-geo-maps' ),
					'dependency' => [ 'useDefaults', '==', false ],
					'default' => isset ( $options['defaultActiveColor'] ) ? $options['defaultActiveColor'] : '#99d8c9',
				],
				'hover'         => [
					'type'       => $color_field,
					/* translators: color for when marker is hovered */
					'title'      => __( 'Hover Color', 'interactive-geo-maps' ),
					'dependency' => [ 'useDefaults', '==', false ],
					'default'    => isset ( $options['defaultHoverColor'] ) ? $options['defaultHoverColor'] : '#2ca25f',
				],
				'action'        => [
					'type'       => 'select',
					'title'      => __( 'Click Action', 'interactive-geo-maps' ),
					'options'    => $actions_default,
					'default'    => 'default',
					'desc'       => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
					'dependency' => [ 'useDefaults', '==', false ],
				],
				'pageBelowInfo' => [
					'type'       => 'submessage',
					'style'      => 'info',
					'content'    => __( 'To display a page below, add the numeric ID of the page you want to display in the "Action Content" field above.', 'interactive-geo-maps' ),
					'dependency' => [ [ 'action', '==', 'igm_display_page_below' ], [ 'action', '==', 'igm_display_page_below_and_scroll' ] ],
				],

				'value'         => [
					'type'  => 'text',
					'title' => __( 'Value', 'interactive-geo-maps' ),
					'desc'  => 'Use numeric values if possible. Don\'t use the minus sign (-).',
				],
			]
		);

		if( ! isset( $options['markerFeatures'] ) || empty( $options['markerFeatures'] ) || ( isset( $options['markerFeatures'] ) && isset( $options['markerFeatures']['otherSources'] ) && $options['markerFeatures']['otherSources'] ) ) {

			$model['meta']['map_info']['sections']['roundMarkers']['fields']['markersDataSource'] = [
				'type'     => 'fieldset',
				'title'    => __( 'Other Data Sources', 'interactive-geo-maps' ),
				'subtitle' => __( 'Use other data sources to populate the markers.', 'interactive-geo-maps' ),
				'sanitize' => __NAMESPACE__ . '\ProMeta::legacy_raw_sanitize',
				'fields'   => [
					'enabled'             => [
						'type'    => 'switcher',
						'default' => false,
						'title'   => __( 'Enabled', 'interactive-geo-maps' ),
					],
					'type'                => [
						'type'       => 'select',
						'title'      => __( 'Source of data', 'interactive-geo-maps' ),
						'default'    => 'categories',
						'options'    => [
							/* translators: Raw Data Content - user will need to write/include their own "non-prepared" data in a textfield */
							'raw_legacy'         => __( 'Raw Data Content', 'interactive-geo-maps' ),
							'json_data'          => __( 'JSON data', 'interactive-geo-maps' ),
							'google_spreadsheet' => __( 'Google Spreadsheet', 'interactive-geo-maps' ),
						],
						'dependency' => [ 'enabled', '==', true ],
					],
					'google_api_key'                   => [
						'type'       => 'text',
						'title'      => __( 'Google API Key', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => __('The API Key should have the Spreadsheet service enabled.', 'interactive-gep-maps') . '<br><a href="https://interactivegeomaps.com/docs/pro-populate-map-with-data-from-google-spreadsheet/" target="_blank">' . __( 'More Information', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_sheet_id'                   => [
						'type'       => 'text',
						'default'    => '',
						'title'      => __( 'Google Sheet ID', 'interactive-geo-maps' ),
						'desc'       => __( ' You can find the spreadsheet ID in a Google Sheets URL:<br><pre>https://docs.google.com/spreadsheets/d/SPREADSHEET-ID/edit#gid=0</pre>' ) . ' <a href="https://interactivegeomaps.com/docs/pro-populate-map-with-data-from-google-spreadsheet/" target="_blank">' . __( 'More Information', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_sheet_range'                   => [
						'type'       => 'text',
						'default'    => 'Sheet1',
						'title'      => __( 'Sheet Range', 'interactive-geo-maps' ),
						'desc'       => __( 'Leave default <code>Sheet1</code> if in doubt. Title of the Sheet or specific cell range.<br> You should use <a href="https://developers.google.com/sheets/api/guides/concepts#a1_notation" target="_blank">A1 notation</a> <span class="dashicons dashicons-external"></span>.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_data_id'                   => [
						'type'       => 'text',
						'default'    => '',
						'title'      => __( 'ID Property', 'interactive-geo-maps' ),
						'desc'       => __( 'Column name for the marker unique identifier. Avoid using spaces or special characters in your spreadsheet to avoid errors. If this field is empty in your spreadhseet, the entry will be skipped.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_data_latitude'                   => [
						'type'       => 'text',
						'default'    => '',
						'title'      => __( 'Latitude Property', 'interactive-geo-maps' ),
						'desc'       => __( 'Column name for the latitude. Avoid using spaces or special characters in your spreadsheet to avoid errors.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_data_longitude'                   => [
						'type'       => 'text',
						'default'    => '',
						'title'      => __( 'Longitude Property', 'interactive-geo-maps' ),
						'desc'       => __( 'Column name for the longitude. Avoid using spaces or special characters in your spreadsheet to avoid errors.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'google_data_content'                   => [
						'type'       => 'text',
						'default'    => '',
						'title'      => __( 'Action Content Property', 'interactive-geo-maps' ),
						'desc'       => __( 'Column name for the action content. Avoid using spaces or special characters in your spreadsheet to avoid errors.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'google_spreadsheet' ] ],
					],
					'raw'                 => [
						'type'       => 'textarea',
						/* translators: Raw Data - user will need to write/include their own "non-prepared" data in a textfield */
						'title'      => __( 'Raw Data', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => __(
							'Currently only accepts data with the following format:<br>
						Latitude Longitude, Title, Tooltip Text, Action Value, HTML Color Value;',
							'interactive-geo-maps'
						),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'raw_legacy' ] ],
					],
					'json'                => [
						'type'       => 'textarea',
						/* translators: Raw Data - JSON code */
						'title'      => __( 'Raw JSON Data', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => __( 'The data should be an array of objects. Example:' ) . '<br>' . $formatted_json . '<br>' . __( 'You can also use a URL to a JSON file, instead of the JSON code. URL content will be cached for some time.', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'json_data_source'    => [
						'type'       => 'text',
						/* translators: Raw Data - JSON code */
						'title'      => __( 'JSON data source property', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => sprintf( __( 'Leave empty if your data is at the first level of the returned json object. If your region data is inside a property, specify it here. You can use dot notation. <br> For example, if your data is formatted like this:<br>%s<br>then use <code>countries</code> as your data source property.', 'interactive-geo-maps' ), $formatted_child_json ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'json_id'             => [
						'type'       => 'text',
						/* translators: Raw Data - JSON code */
						'title'      => __( 'JSON ID property', 'interactive-geo-maps' ),
						'default'    => 'id',
						'desc'       => __( 'If your data entries do not contain an <code>id</code> property, specify which property to use instead.' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'json_lat'            => [
						'type'       => 'text',
						/* translators: Raw Data - JSON code */
						'title'      => __( 'JSON Latitude property', 'interactive-geo-maps' ),
						'default'    => 'latitude',
						'desc'       => __( 'If your data entries do not contain a <code>latitude</code> property, specify which property to use instead.' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'json_lon'            => [
						'type'       => 'text',
						/* translators: Raw Data - JSON code */
						'title'      => __( 'JSON Longitude property', 'interactive-geo-maps' ),
						'default'    => 'longitude',
						'desc'       => __( 'If your data entries do not contain a <code>longitude</code> property, specify which property to use instead.' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'json_action_content' => [
						'type'       => 'text',
						'title'      => __( 'JSON Action Content property', 'interactive-geo-maps' ),
						'default'    => '',
						'desc'       => __( 'If your data entries do not contain a <code>content</code> property, specify which property to use instead. This is the property that will contain the content that is triggered when the marker is clicked.' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
					],
					'action_content_template' => [
						'type'       => $ttemplate_editor,
						'title'      => __( 'Action Content Template', 'interactive-geo-maps' ),
						'default'    => '{content}',
						'desc'       => __( 'You can setup a custom template for the action content with <strong>placeholders</strong> (using brackets) for parameters that exist in your content object.<br>Leave blank or the default <code>{content}</code> if you prefer to use the default or defined above value.', 'interactive-world-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', 'any', 'google_spreadsheet,json_data' ] ],
					],
				],
			];
		}

		if( ! isset( $options['markerFeatures'] ) || empty( $options['markerFeatures'] ) || ( isset( $options['markerFeatures'] ) && isset( $options['markerFeatures']['autoLabels'] ) && $options['markerFeatures']['autoLabels'] ) ) {


			$model['meta']['map_info']['sections']['roundMarkers']['fields']['roundMarkerLabels'] = [
				'type'     => 'fieldset',
				/* translators: labels are text elements that will go on top of the regions on the map */
				'title'    => __( 'Automatic Labels (Beta)', 'interactive-geo-maps' ),
				'subtitle' => __( 'Automatically add the title below the marker.', 'interactive-geo-maps' ),
				'fields'   => [
					'enabled'  => [
						'type'    => 'switcher',
						'title'   => __( 'Enabled', 'interactive-geo-maps' ),
						'default' => false,
					],
					'fontSize' => [
						'type'       => 'spinner',
						'default'    => 15,
						/* translators: Font size */
						'title'      => _x( 'Size', 'Font Size', 'interactive-geo-maps' ),
						'dependency' => [ 'enabled', '==', true ],

					],
					'fill'     => [
						'type'       => $color_field,
						'title'      => __( 'Color', 'interactive-geo-maps' ),
						'dependency' => [ 'source', '!=', '0' ],
						'default'    => '#111111',
						'dependency' => [ 'enabled', '==', true ],

					],
					'source'   => [
						'type'       => 'text',
						'default'    => '{name}',
						'title'      => __( 'Source Property', 'interactive-geo-maps' ),
						'desc'       => __( 'Leave the default <code>{name}</code> or blank to use the marker title or specify which property to use as the label text source, using the template format, like <code>{tooltipContent}</code>.', 'interactive-geo-maps' ),
						'dependency' => [ 'enabled', '==', true ],	
					],
					'mobileSize' => [
						'type'    => 'spinner',
						'title'   => __( 'Mobile Size', 'interactive-geo-maps' ),
						'default' => 100,
						'unit'    => '%',
						'desc'    => __( 'On smaller screens markers will decrease proportionally to their size on desktop',  'interactive-geo-maps' ),
					],
				],
			];
		}


		if( ! isset( $options['markerFeatures'] ) || empty( $options['markerFeatures'] ) || ( isset( $options['markerFeatures'] ) && isset( $options['markerFeatures']['heatMap'] ) && $options['markerFeatures']['heatMap'] ) ) {


			$model['meta']['map_info']['sections']['roundMarkers']['fields']['heatMapMarkers'] = [
				'type'     => 'fieldset',
				/* translators: Heat Map or Heatmap */
				'title'    => __( 'Heat Map', 'interactive-geo-maps' ),
				'subtitle' => __( 'Calculate markers radius automatically', 'interactive-geo-maps' ),
				'fields'   => [
					'enabled'   => [
						'type'    => 'switcher',
						'default' => false,
						'title'   => __( 'Enabled', 'interactive-geo-maps' ),
					],
					'type'      => [
						'title'      => __( 'Type', 'interactive-geo-maps' ),
						'type'       => 'select',
						'default'    => 'gradient',
						'options'    => [
							'gradient' => __( 'Gradient', 'interactive-geo-maps' ),
							'range'    => __( 'Custom Ranges', 'interactive-geo-maps' ),
						],
						'dependency' => [ 'enabled', '==', true ],
					],

					'range'     => [
						'type'       => 'group',
						'title'      => __( 'Range sets', 'interactive-geo-maps' ),
						'fields'     => [
							'rule'   => [
								'type'  => 'text',
								'title' => __( 'Rule (>=)', 'interactive-geo-maps' ),
								'desc'  => __( 'Use numbers preferably. Numbers bigger than this will have the color below.<br>Until the next rule kicks in and defines a new color.<br>So number >= X will have the color and radius set below.', 'interactive-geo-maps' ),
							],
							'radius' => [
								'type'    => 'spinner',
								/* translators: Circle radius */
								'title'   => __( 'Radius', 'interactive-geo-maps' ),
								'default' => '8',
							],
							'fill'   => [
								'type'    => $color_field,
								/* translators: color for the shape/region on the map */
								'title'   => __( 'Fill Color', 'interactive-geo-maps' ),
								'default' => isset ( $options['defaultActiveColor'] ) ? $options['defaultActiveColor'] : '#99d8c9',
							],
						],
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'range' ] ],
					],
					'minRadius' => [
						'type'       => 'spinner',
						/* translators: Circle radius */
						'title'      => __( 'Minimum Radius', 'interactive-geo-maps' ),
						'default'    => '8',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'maxRadius' => [
						'type'       => 'spinner',
						/* translators: Circle radius */
						'title'      => __( 'Maximum Radius', 'interactive-geo-maps' ),
						'default'    => '25',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'minColor'  => [
						'type'       => $color_field,
						/* translators: Minimum color for heatmap */
						'title'      => __( 'Minimum Color', 'interactive-geo-maps' ),
						'default'    => '#f5f5f5',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'maxColor'  => [
						'type'       => $color_field,
						/* translators: Maximum color for heatmap */
						'title'      => __( 'Maximum Color', 'interactive-geo-maps' ),
						'default'    => '#333333',
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'legend'    => [
						'type'       => 'switcher',
						'default'    => false,
						/* translators: Legend: caption or element explaining colors on the map */
						'title'      => __( 'Legend', 'interactive-geo-maps' ),
						'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'minLabel'  => [
						'type'       => 'text',
						'title'      => __( 'Label for Minimum', 'interactive-geo-maps' ),
						'default'    => 'Min',
						'dependency' => [ [ 'enabled', '==', true ], [ 'legend', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'maxLabel'  => [
						'type'       => 'text',
						'title'      => __( 'Label for Maximum', 'interactive-geo-maps' ),
						'default'    => 'Max',
						'dependency' => [ [ 'enabled', '==', true ], [ 'legend', '==', true ], [ 'type', '==', 'gradient' ] ],
					],
					'source'    => [
						'type'       => 'text',
						'title'      => __( 'Source field ID', 'interactive-geo-maps' ),
						'default'    => 'value',
						'dependency' => [ 'enabled', '==', true ],
					],
				],
			];
		}

		if( isset( $options['markerFeatures'] ) && isset( $options['markerFeatures']['triggerRegionHover'] ) && $options['markerFeatures']['triggerRegionHover']  ) {

			$model['meta']['map_info']['sections']['roundMarkers']['fields']['triggerRegionHover'] = [
				'type'     => 'fieldset',
				'title'    => __( 'Connect with Regions', 'interactive-geo-maps' ),
				'subtitle'  => __( '(Experimental Feature)', 'interactive-geo-maps' ),
				'fields'   => [
					'enabled'   => [
						'type'    => 'switcher',
						'default' => false,
						'title'   => __( 'Enabled', 'interactive-geo-maps' ),
						'desc'    => __( 'When enabled, you can add region codes to the marker value field and they will trigger the hover event together with the marker.', 'interactive-geo-maps' ),
					],
				],
			];

		}

		if( ! isset( $options['markerFeatures'] ) || empty( $options['markerFeatures'] ) || ( isset( $options['markerFeatures'] ) && isset( $options['markerFeatures']['clusters'] ) && $options['markerFeatures']['clusters'] ) ) {


			$model['meta']['map_info']['sections']['roundMarkers']['fields']['clusterMarkers'] = [
				'type'     => 'fieldset',
				/* translators: Cluster Markers: grouping markers that are close together */
				'title'    => __( 'Cluster Markers', 'interactive-geo-maps' ),
				'subtitle' => __( 'Group markers that are close together.', 'interactive-geo-maps' ),
				'fields'   => [
					'enabled'   => [
						'type'    => 'switcher',
						'default' => false,
						'title'   => __( 'Enabled', 'interactive-geo-maps' ),
						'desc'    => __( 'Zoom controls should be enabled for this to work correctly.', 'interactive-geo-maps' ),
					],
					'zoomLevel' => [
						'type'       => 'spinner',
						'title'      => __( 'Maximum Zoom', 'interactive-geo-maps' ),
						'desc'       => __( 'Display all entries at this zoom level.', 'interactive-geo-maps' ),
						'default'    => '5',
						'dependency' => [ 'enabled', '==', true ],
					],
					'maxBias'   => [
						'type'       => 'spinner',
						/* translators:  threshold - intensity that must be exceeded for a certain reaction */
						'title'      => __( 'Threshold', 'interactive-geo-maps' ),
						'default'    => 0.5,
						'min'        => 0,
						'max'        => 1,
						'step'       => 0.1,
						'attributes' => [
							'step' => 'any',
						],
						'dependency' => [ 'enabled', '==', true ],
					],
					'tooltipTemplate' => [
						'type'       => $ttemplate_editor,
						'title'      => __( 'Tooltip Template', 'interactive-geo-maps' ),
						'dependency' => [ 'enabled', '==', true ],
						'desc'    => __( 'Override default tooltip template for the cluster markers.<br>Leave blank to use default. <a href="https://interactivegeomaps.com/docs/pro-tooltip-content-and-design/">More Information</a>  <span class="dashicons dashicons-external"></span>. ', 'interactive-geo-maps' ),
					],
				],
			];
		}

		$model['meta']['map_info']['sections']['roundMarkers']['fields']['roundMarkersLegend'] = [
			'type'     => 'fieldset',
			/* translators: legend refers to a caption or visual element explaining colours on map */
			'title'    => __( 'Show in Legend', 'interactive-geo-maps' ),
			'subtitle' => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
			'fields'   => [
				'enabled' => [
					'type'    => 'switcher',
					/* translators: legend refers to a caption or visual element explaining colours on map */
					'title'   => __( 'Show in Legend', 'interactive-geo-maps' ),
					'desc'    => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
					'default' => false,
				],
				'title'   => [
					'type'       => 'text',
					'title'      => __( 'Title in Legend', 'interactive-geo-maps' ),
					'dependency' => [ 'enabled', '==', true ],
				],
			],
		];

		$model['meta']['map_info']['sections']['roundMarkers']['fields']['roundMarkersMobileSize'] = [
			'type'    => 'spinner',
			'title'   => __( 'Mobile Size', 'interactive-geo-maps' ),
			'default' => 100,
			'unit'    => '%',
			'desc'    => __( 'On smaller screens markers will decrease proportionally to their size on desktop',  'interactive-geo-maps' ),
		];

		$model['meta']['map_info']['sections']['roundMarkers']['fields']['roundMarkersTooltipTemplate'] = [
			'type'    => $ttemplate_editor,
			'title'   => __( 'Tooltip Template', 'interactive-geo-maps' ),
			'default' => '',
			'desc'    => __( 'Override default tooltip template for this series of data.<br>Leave blank to use default. <a href="https://interactivegeomaps.com/docs/pro-tooltip-content-and-design/">More Information</a>  <span class="dashicons dashicons-external"></span>. ', 'interactive-geo-maps' ),
		];

		$model['meta']['map_info']['sections']['general']['fields']['projection']['options'] = [
			'Miller'             => 'Miller',
			'Albers'             => 'Albers',
			'AlbersUsa'          => 'AlbersUsa (For US Maps)',
			'AzimuthalEqualArea' => 'AzimuthalEqualArea',
			'Eckert6'            => 'Eckert6',
			'EqualEarth'         => 'EqualEarth',
			'Projection'         => 'Equirectangular',
			'Mercator'           => 'Mercator',
			'Miller'             => 'Miller',
			'NaturalEarth1'      => 'NaturalEarth1',
			'Orthographic'       => 'Orthographic (Globe)',
			'Stereographic'      => 'Stereographic',
		];

		$model['meta']['map_info']['sections']['general']['fields']['grid'] = [
			'type'       => 'fieldset',
			'dependency' => [ 'projection', '==', 'Orthographic' ],
			'fields'     => [
				'gridFieldsetTitle'         => [
					'type'    => 'subheading',
					'content' => __( 'Orthographic Projection Settings', 'interactive-geo-maps' ),
				],
				'color'                     => [
					'type'    => $color_field,
					'title'   => __( 'Grid color', 'interactive-geo-maps' ),
					'default' => '#cccccc',
				],

				'projectionBackgroundColor' => [
					'type'    => $color_field,
					'title'   => __( 'Background Color', 'interactive-geo-maps' ),
					'default' => '#cccccc',
				],

				'gridFieldsetFooter'        => [
					'type'    => 'content',
					'content' => __( 'You can find the options for the background color of the map container in the "Visual Settings" section. The background options above will only apply to the "globe" of this projection.', 'interactive-geo-maps' ),
				],
			],
		];

		// Image Markers
		if( ! isset( $options['imageMarkerFeatures'] ) || empty( $options['imageMarkerFeatures'] ) || ( isset( $options['imageMarkerFeatures'] ) && isset( $options['imageMarkerFeatures']['enabled'] ) && $options['imageMarkerFeatures']['enabled'] ) ) {

			$model['meta']['map_info']['sections']['imageMarkers'] = [
				'title'  => __( 'Image Markers', 'interactive-geo-maps' ),
				'icon'   => 'fa fa-picture-o fa-lg',
				'fields' => [
					'imageMarkers_info'           => [
						'type'    => 'content',
						'content' => __( 'Add images to the map.<br>Click "Add New Marker" below to start adding them.', 'interactive-geo-maps' ),
					],
					'imageMarkers'                => [
						'type'         => 'group',
						'button_title' => __( 'Add New Marker', 'interactive-geo-maps' ),
	
						'fields'       => [
							'id'               => [
								'type'       => 'text',
								'title'      => __( 'Title', 'interactive-geo-maps' ),
								'desc'       => __( 'Use unique titles, if possible without special characters.', 'interactive-geo-maps' ),
								'attributes' => [
									'class' => 'skip-preview',
								],
							],
							'coordinates'      => [
								'type'   => $coordinates_editor,
								'title'  => __( 'Coordinates', 'interactive-geo-maps' ),
								'fields' => [
									'id'        => [
										'type'       => 'text',
										'title'      => __( 'Location', 'interactive-geo-maps' ),
										'class'      => 'geocoding geocoding-hide',
										'attributes' => [
											'class' => 'geocoding-input',
										],
									],
									'latitude'  => [
										'type'  => 'text',
										'title' => __( 'Latitude', 'interactive-geo-maps' ),
	
									],
									'longitude' => [
										'type'  => 'text',
										'title' => __( 'Longitude', 'interactive-geo-maps' ),
									],
								],
							],
	
							'tooltipContent'   => [
								'type'  => $tooltip_editor,
								'title' => __( 'Tooltip Content', 'interactive-geo-maps' ),
							],
							'content'          => [
								'type'     => $action_content_editor,
								'title'    => __( 'Action Content', 'interactive-geo-maps' ),
								'subtitle' => __( 'URL or content to trigger when image marker is clicked.', 'interactive-geo-maps' ),
							],
							'useDefaults'      => [
								'type'     => 'switcher',
								'title'    => __( 'Use defaults', 'interactive-geo-maps' ),
								'subtitle' => __( 'Use default image and actions.', 'interactive-geo-maps' ),
								'default'  => true,
							],
							'image'            => [
								'type'       => 'media',
								'title'      => __( 'Image', 'interactive-geo-maps' ),
								'library'    => 'image',
								'dependency' => [ 'useDefaults', '==', false ],
	
							],
							'size'             => [
								'type'       => 'spinner',
								'default'    => '20',
								'unit'       => 'px',
								'validate'   => 'csf_validate_numeric',
								'title'      => __( 'Size', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
	
							],
							'nonScaling' => [
								'type'    => 'switcher',
								'default' => true,
								'title'   => __( 'Keep size on zoom', 'interactive-geo-maps' ),
							],
							'horizontalCenter' => [
								'type'       => 'select',
								'title'      => __( 'Horizontal Center', 'interactive-geo-maps' ),
								'desc'       => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
								'default'    => 'middle',
								'options'    => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
								],
								'dependency' => [ 'useDefaults', '==', false ],
	
							],
							'verticalCenter'   => [
								'type'       => 'select',
								'title'      => __( 'Vertical Center', 'interactive-geo-maps' ),
								'desc'       => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
								'default'    => 'middle',
								'options'    => [
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
								],
								'dependency' => [ 'useDefaults', '==', false ],
	
							],
							'action'           => [
								'type'       => 'select',
								'title'      => __( 'Click Action', 'interactive-geo-maps' ),
								'options'    => $actions_default,
								'default'    => 'default',
								'desc'       => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'pageBelowInfo'    => [
								'type'       => 'submessage',
								'style'      => 'info',
								'content'    => __( 'To display a page below, add the numeric ID of the page you want to display in the "Action Content" field above.', 'interactive-geo-maps' ),
								'dependency' => [ [ 'action', '==', 'igm_display_page_below' ], [ 'action', '==', 'igm_display_page_below_and_scroll' ] ],
							],
	
							'value'            => [
								'type'  => 'text',
								'title' => __( 'Value', 'interactive-geo-maps' ),
								'desc'  => 'Use numeric values if possible. Don\'t use the minus sign (-).',
							],
	
						],
					],
					'imageMarkerDefaults'         => [
						'type'   => 'fieldset',
						'title'  => __( 'Default values', 'interactive-geo-maps' ),
						'fields' => [
							'image'               => [
								'type'    => 'media',
								'title'   => __( 'Image', 'interactive-geo-maps' ),
								'library' => 'image',
							],
							'size'                => [
								'type'     => 'spinner',
								'default'  => '20',
								'unit'     => 'px',
								'validate' => 'csf_validate_numeric',
								'title'    => __( 'Size', 'interactive-geo-maps' ),
	
							],
							'nonScaling' => [
								'type'    => 'switcher',
								'default' => true,
								'title'   => __( 'Keep size on zoom', 'interactive-geo-maps' ),
							],
							'horizontalCenter'    => [
								'type'    => 'select',
								'title'   => __( 'Horizontal Center', 'interactive-geo-maps' ),
								'desc'    => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
								'default' => 'middle',
								'options' => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
								],
							],
							'verticalCenter'      => [
								'type'    => 'select',
								'title'   => __( 'Vertical Center', 'interactive-geo-maps' ),
								'desc'    => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
								'default' => 'middle',
								'options' => [
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
								],
							],
							'action'              => [
								'type'    => 'select',
								'title'   => __( 'Click Action', 'interactive-geo-maps' ),
								'desc'    => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
								'options' => $image_marker_actions,
								'default' => 'default',
							],
							'customAction'        => [
								'type'       => 'code_editor',
								'title'      => __( 'Custom Javascript Action', 'interactive-geo-maps' ),
								'desc'       => __( 'When a marker is clicked a function will be called where the object with the data from the clicked region will be passed. You can write the contents of the function here. <a href="https://interactivegeomaps.com/docs/pro-custom-click-actions/" target="_blank">More information</a> <span class="dashicons dashicons-external"></span>', 'interactive-geo-maps' ),
								'dependency' => [ 'action', '==', 'customImageMarkerAction' ],
							],
							'pageBelowInfo'       => [
								'type'       => 'submessage',
								'style'      => 'info',
								'content'    => __( 'To display a page below, add the numeric ID of the page you want to display in the "Action Content" field of each entry.', 'interactive-geo-maps' ),
								'dependency' => [ [ 'action', '==', 'igm_display_page_below' ], [ 'action', '==', 'igm_display_page_below_and_scroll' ] ],
							],
							'triggerClickOnHover' => [
								'type'  => 'switcher',
								'title' => __( 'Trigger on Hover', 'interactive-geo-maps' ),
								'desc'  => __( 'When enabled, when the user hovers a marker, it will trigger a click automatically.', 'interactive-geo-maps' ),
							],
						],
					],
					'imageMarkersDataSource'      => [
						'sanitize' => __NAMESPACE__ . '\ProMeta::legacy_raw_sanitize',
						'type'     => 'fieldset',
						'title'    => __( 'Other Data Sources', 'interactive-geo-maps' ),
						'subtitle' => __( 'Use other data sources to populate the markers.', 'interactive-geo-maps' ),
						'fields'   => [
							'enabled' => [
								'type'    => 'switcher',
								'default' => false,
								'title'   => __( 'Enabled', 'interactive-geo-maps' ),
							],
							'type'    => [
								'type'       => 'select',
								'title'      => __( 'Source of data', 'interactive-geo-maps' ),
								'default'    => 'categories',
								'options'    => [
									'json_data'  => __( 'JSON data', 'interactive-geo-maps' ),
								],
								'dependency' => [ 'enabled', '==', true ],
							],
							
							'json'                => [
								'type'       => 'textarea',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'Raw JSON Data', 'interactive-geo-maps' ),
								'default'    => '',
								'desc'       => __( 'The data should be an array of objects. Example:' ) . '<br>' . $formatted_json . '<br>' . __( 'You can also use a URL to a JSON file, instead of the JSON code. URL content will be cached for some time.', 'interactive-geo-maps' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_data_source'    => [
								'type'       => 'text',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'JSON data source property', 'interactive-geo-maps' ),
								'default'    => '',
								'desc'       => sprintf( __( 'Leave empty if your data is at the first level of the returned json object. If your region data is inside a property, specify it here. You can use dot notation. <br> For example, if your data is formatted like this:<br>%s<br>then use <code>countries</code> as your data source property.', 'interactive-geo-maps' ), $formatted_child_json ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_id'             => [
								'type'       => 'text',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'JSON ID property', 'interactive-geo-maps' ),
								'default'    => 'id',
								'desc'       => __( 'If your data entries do not contain an <code>id</code> property, specify which property to use instead.' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_lat'            => [
								'type'       => 'text',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'JSON Latitude property', 'interactive-geo-maps' ),
								'default'    => 'latitude',
								'desc'       => __( 'If your data entries do not contain a <code>latitude</code> property, specify which property to use instead.' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_lon'            => [
								'type'       => 'text',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'JSON Longitude property', 'interactive-geo-maps' ),
								'default'    => 'longitude',
								'desc'       => __( 'If your data entries do not contain a <code>longitude</code> property, specify which property to use instead.' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_image' => [
								'type'       => 'text',
								'title'      => __( 'JSON Image URL property', 'interactive-geo-maps' ),
								'default'    => '',
								'desc'       => __( 'If your data entries do not contain a <code>href</code> property, specify which property to use instead to grab the image URL. If empty, the default image will be used.' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_action_content' => [
								'type'       => 'text',
								'title'      => __( 'JSON Action Content property', 'interactive-geo-maps' ),
								'default'    => '',
								'desc'       => __( 'If your data entries do not contain a <code>content</code> property, specify which property to use instead. This is the property that will contain the content that is triggered when the marker is clicked.' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
						],
					],
					'imageMarkersLegend'          => [
						'type'     => 'fieldset',
						/* translators: legend refers to a caption or visual element explaining colours on map */
						'title'    => __( 'Show in Legend', 'interactive-geo-maps' ),
						'subtitle' => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
						'fields'   => [
							'enabled' => [
								'type'    => 'switcher',
								'title'   => __( 'Show in Legend', 'interactive-geo-maps' ),
								'desc'    => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
								'default' => false,
							],
							'title'   => [
								'type'       => 'text',
								'title'      => __( 'Title in Legend', 'interactive-geo-maps' ),
								'dependency' => [ 'enabled', '==', true ],
							],
						],
					],
					'imageMarkersMobileSize' => [
						'type'    => 'spinner',
						'title'   => __( 'Mobile Size', 'interactive-geo-maps' ),
						'default' => 100,
						'unit'    => '%',
						'desc'    => __( 'On smaller screens markers will decrease proportionally to their size on desktop', 'interactive-geo-maps' ),
					],
					'imageMarkersTooltipTemplate' => [
						'type'    => $ttemplate_editor,
						'title'   => __( 'Tooltip Template', 'interactive-geo-maps' ),
						'default' => '',
						'desc'    => __( 'Override default tooltip template for this series of data.<br>Leave blank to use default. <a href="https://interactivegeomaps.com/docs/pro-tooltip-content-and-design/">More Information</a>  <span class="dashicons dashicons-external"></span>. ', 'interactive-geo-maps' ),
					],
				],
			];
		}
		

		// Icon Markers Options
		if( ! isset( $options['iconMarkerFeatures'] ) || empty( $options['iconMarkerFeatures'] ) || ( isset( $options['iconMarkerFeatures'] ) && isset( $options['iconMarkerFeatures']['enabled'] ) && $options['iconMarkerFeatures']['enabled'] ) ) {

			$model['meta']['map_info']['sections']['iconMarkers'] = [
				'title'  => __( 'Vector Icon Markers', 'interactive-geo-maps' ),
				'icon'   => 'fa fa-map-marker fa-lg',
				'fields' => [

					'iconMarkers_info'           => [
						'type'    => 'content',
						'content' => __( 'Add icon markers to the map. There is a predefined list of available icons.<br>Click "Add New Marker" below to start adding icon markers to the map.', 'interactive-geo-maps' ),
					],

					'iconMarkers'                => [

						'type'         => 'group',
						'button_title' => __( 'Add New Marker', 'interactive-geo-maps' ),

						'fields'       => [

							'id'               => [
								'type'       => 'text',
								'title'      => __( 'Title', 'interactive-geo-maps' ),
								'desc'       => __( 'Use unique titles, if possible without special characters.', 'interactive-geo-maps' ),
								'attributes' => [
									'class' => 'skip-preview',
								],
							],
							'coordinates'      => [
								'type'   => $coordinates_editor,
								'title'  => __( 'Coordinates', 'interactive-geo-maps' ),
								'fields' => [
									'name'      => [
										'type'       => 'text',
										'title'      => __( 'Location', 'interactive-geo-maps' ),
										'class'      => 'geocoding geocoding-hide',
										'attributes' => [
											'class' => 'geocoding-input',
										],
									],
									'latitude'  => [
										'type'  => 'text',
										'title' => __( 'Latitude', 'interactive-geo-maps' ),

									],
									'longitude' => [
										'type'  => 'text',
										'title' => __( 'Longitude', 'interactive-geo-maps' ),

									],
								],
							],

							'tooltipContent'   => [
								'type'  => $tooltip_editor,
								'title' => __( 'Tooltip Content', 'interactive-geo-maps' ),
							],
							'content'          => [
								'type'     => $action_content_editor,
								'title'    => __( 'Action Content', 'interactive-geo-maps' ),
								'subtitle' => __( 'URL or content to trigger when icon marker is clicked.', 'interactive-geo-maps' ),
							],
							'useDefaults'      => [
								'type'     => 'switcher',
								'title'    => __( 'Use defaults', 'interactive-geo-maps' ),
								'subtitle' => __( 'Use default colors and actions.', 'interactive-geo-maps' ),
								'default'  => true,
							],
							'icon'             => [
								'type'         => 'icon',
								'title'        => __( 'Icon', 'interactive-geo-maps' ),
								'default'      => 'fa fa-star',
								'button_title' => __( 'Change Icon', 'interactive-geo-maps' ),
								'dependency'   => [ 'useDefaults', '==', false ],
							],
							'horizontalCenter' => [
								'type'       => 'select',
								'title'      => __( 'Horizontal Center', 'interactive-geo-maps' ),
								'desc'       => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
								'default'    => 'middle',
								'options'    => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
								],
								'dependency' => [ 'useDefaults', '==', false ],

							],
							'verticalCenter'   => [
								'type'       => 'select',
								'title'      => __( 'Vertical Center', 'interactive-geo-maps' ),
								'desc'       => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
								'default'    => 'middle',
								'options'    => [
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
								],
								'dependency' => [ 'useDefaults', '==', false ],

							],
							'scale'            => [
								'type'       => 'spinner',
								'default'    => 1,
								'min'        => 0.1,
								'max'        => 10,
								'step'       => 0.1,
								'attributes' => [
									'step' => 'any',
								],
								'title'      => __( 'Scale', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'rotation'            => [
								'type'       => 'spinner',
								'default'    => 0,
								'min'        => 0,
								'max'        => 360,
								'step'       => 1,
								'attributes' => [
									'step' => 'any',
								],
								'title'      => __( 'Rotation', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'fill'             => [
								'type'       => $color_field,
								'title'      => __( 'Fill Color', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
								'default'    => '#3182bd',

							],
							'hover'            => [
								'type'       => $color_field,
								'title'      => __( 'Hover Color', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
								'default'    => '#2171b5',
							],
							'action'           => [
								'type'       => 'select',
								'title'      => __( 'Click Action', 'interactive-geo-maps' ),
								'options'    => $actions_default,
								'default'    => 'default',
								'desc'       => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'pageBelowInfo'    => [
								'type'       => 'submessage',
								'style'      => 'info',
								'content'    => __( 'To display a page below, add the numeric ID of the page you want to display in the "Action Content" field above.', 'interactive-geo-maps' ),
								'dependency' => [ [ 'action', '==', 'igm_display_page_below' ], [ 'action', '==', 'igm_display_page_below_and_scroll' ] ],
							],

							'value'            => [
								'type'  => 'text',
								'title' => __( 'Value', 'interactive-geo-maps' ),
								'desc'  => 'Use numeric values if possible. Don\'t use the minus sign (-).',
							],

						],
					],
					'iconMarkerDefaults'         => [
						'type'   => 'fieldset',
						'title'  => __( 'Default values', 'interactive-geo-maps' ),
						'fields' => [
							'icon'                => [
								'type'         => 'icon',
								'title'        => __( 'Icon', 'interactive-geo-maps' ),
								'default'      => 'fa fa-star',
								'button_title' => __( 'Change Icon', 'interactive-geo-maps' ),
							],
							'horizontalCenter'    => [
								'type'    => 'select',
								'title'   => __( 'Horizontal Center', 'interactive-geo-maps' ),
								'desc'    => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
								'default' => 'middle',
								'options' => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
								],

							],
							'verticalCenter'      => [
								'type'    => 'select',
								'title'   => __( 'Vertical Center', 'interactive-geo-maps' ),
								'desc'    => __( 'Point position in relation to the marker.', 'interactive-geo-maps' ),
								'default' => 'middle',
								'options' => [
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
								],

							],
							'scale'               => [
								'type'       => 'spinner',
								'default'    => 1,
								'min'        => 0.1,
								'max'        => 10,
								'step'       => 0.1,
								'attributes' => [
									'step' => 'any',
								],
								'title'      => __( 'Scale', 'interactive-geo-maps' ),
							],
							'rotation'            => [
								'type'       => 'spinner',
								'default'    => 0,
								'min'        => 0,
								'max'        => 360,
								'step'       => 1,
								'attributes' => [
									'step' => 'any',
								],
								'title'      => __( 'Rotation', 'interactive-geo-maps' ),
								'desc'       => __( 'Degrees to rotate in clockwise direction', 'interactive-geo-maps' ),
							],
							'fill'                => [
								'type'    => $color_field,
								'title'   => __( 'Fill Color', 'interactive-geo-maps' ),
								'default' => '#3182bd',

							],
							'hover'               => [
								'type'    => $color_field,
								'title'   => __( 'Hover Color', 'interactive-geo-maps' ),
								'default' => '#2171b5',
							],
							'action'              => [
								'type'    => 'select',
								'title'   => __( 'Click Action', 'interactive-geo-maps' ),
								'desc'    => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
								'options' => $icon_marker_actions,
								'default' => 'none',
							],
							'customAction'        => [
								'type'       => 'code_editor',
								'title'      => __( 'Custom Javascript Action', 'interactive-geo-maps' ),
								'desc'       => __( 'When a marker is clicked a function will be called where the object with the data from the clicked region will be passed. You can write the contents of the function here. <a href="https://interactivegeomaps.com/docs/pro-custom-click-actions/" target="_blank">More information</a>', 'interactive-geo-maps' ),
								'dependency' => [ 'action', '==', 'customIconMarkerAction' ],
							],
							'pageBelowInfo'       => [
								'type'       => 'submessage',
								'style'      => 'info',
								'content'    => __( 'To display a page below, add the numeric ID of the page you want to display in the "Action Content" field of each entry.', 'interactive-geo-maps' ),
								'dependency' => [ [ 'action', '==', 'igm_display_page_below' ], [ 'action', '==', 'igm_display_page_below_and_scroll' ] ],
							],
							'triggerClickOnHover' => [
								'type'  => 'switcher',
								'title' => __( 'Trigger on Hover', 'interactive-geo-maps' ),
								'desc'  => __( 'When enabled, when the user hovers a marker, it will trigger a click automatically.', 'interactive-geo-maps' ),
							],
						],
					],

					'iconMarkerLabels'           => [
						'type'     => 'fieldset',
						/* translators: labels are text elements that will go on top of the regions on the map */
						'title'    => __( 'Automatic Labels (Beta)', 'interactive-geo-maps' ),
						'subtitle' => __( 'Automatically add the title below the marker.', 'interactive-geo-maps' ),
						'fields'   => [
							'enabled'  => [
								'type'    => 'switcher',
								'title'   => __( 'Enabled', 'interactive-geo-maps' ),
								'default' => false,
							],
							'fontSize' => [
								'type'       => 'spinner',
								'default'    => 15,
								/* translators: Font size */
								'title'      => _x( 'Size', 'Font Size', 'interactive-geo-maps' ),
								'dependency' => [ 'enabled', '==', true ],

							],
							'fill'     => [
								'type'       => $color_field,
								'title'      => __( 'Color', 'interactive-geo-maps' ),
								'dependency' => [ 'source', '!=', '0' ],
								'default'    => '#111111',
								'dependency' => [ 'enabled', '==', true ],

							],
							'source'   => [
								'type'       => 'text',
								'default'    => '{name}',
								'title'      => __( 'Source Property', 'interactive-geo-maps' ),
								'desc'       => __( 'Leave the default <code>{name}</code> or blank to use the marker title or specify which property to use as the label text source, using the template format, like <code>{tooltipContent}</code>.', 'interactive-geo-maps' ),
								'dependency' => [ 'enabled', '==', true ],	
							],
						],
					],

					'iconMarkersDataSource'      => [
						'sanitize' => __NAMESPACE__ . '\ProMeta::legacy_raw_sanitize',
						'type'     => 'fieldset',
						'title'    => __( 'Other Data Sources', 'interactive-geo-maps' ),
						'subtitle' => __( 'Use other data sources to populate the markers.', 'interactive-geo-maps' ),
						'fields'   => [
							'enabled' => [
								'type'    => 'switcher',
								'default' => false,
								'title'   => __( 'Enabled', 'interactive-geo-maps' ),
							],
							'type'    => [
								'type'       => 'select',
								'title'      => __( 'Source of data', 'interactive-geo-maps' ),
								'default'    => 'categories',
								'options'    => [
									/* translators: Raw Data Content - user will need to write/include their own "non-prepared" data in a textfield */
									'raw_legacy' => __( 'Raw Data Content', 'interactive-geo-maps' ),
									'json_data'  => __( 'JSON data', 'interactive-geo-maps' ),
								],
								'dependency' => [ 'enabled', '==', true ],
							],
							'raw'     => [
								'type'       => 'textarea',
								/* translators: Raw Data - user will need to write/include their own "non-prepared" data in a textfield */
								'title'      => __( 'Raw Data', 'interactive-geo-maps' ),
								'default'    => '',
								'desc'       => __(
									'Currently only accepts data with the following format:<br>
								Latitude Longitude, Title, Tooltip Text, Action Value, HTML Color Value;',
									'interactive-geo-maps'
								),
								'sanitize'   => __NAMESPACE__ . '\ProMeta::test',
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'raw_legacy' ] ],
							],
							'json'                => [
								'type'       => 'textarea',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'Raw JSON Data', 'interactive-geo-maps' ),
								'default'    => '',
								'desc'       => __( 'The data should be an array of objects. Example:' ) . '<br>' . $formatted_json . '<br>' . __( 'You can also use a URL to a JSON file, instead of the JSON code. URL content will be cached for some time.', 'interactive-geo-maps' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_data_source'    => [
								'type'       => 'text',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'JSON data source property', 'interactive-geo-maps' ),
								'default'    => '',
								'desc'       => sprintf( __( 'Leave empty if your data is at the first level of the returned json object. If your region data is inside a property, specify it here. You can use dot notation. <br> For example, if your data is formatted like this:<br>%s<br>then use <code>countries</code> as your data source property.', 'interactive-geo-maps' ), $formatted_child_json ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_id'             => [
								'type'       => 'text',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'JSON ID property', 'interactive-geo-maps' ),
								'default'    => 'id',
								'desc'       => __( 'If your data entries do not contain an <code>id</code> property, specify which property to use instead.' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_lat'            => [
								'type'       => 'text',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'JSON Latitude property', 'interactive-geo-maps' ),
								'default'    => 'latitude',
								'desc'       => __( 'If your data entries do not contain a <code>latitude</code> property, specify which property to use instead.' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_lon'            => [
								'type'       => 'text',
								/* translators: Raw Data - JSON code */
								'title'      => __( 'JSON Longitude property', 'interactive-geo-maps' ),
								'default'    => 'longitude',
								'desc'       => __( 'If your data entries do not contain a <code>longitude</code> property, specify which property to use instead.' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
							'json_action_content' => [
								'type'       => 'text',
								'title'      => __( 'JSON Action Content property', 'interactive-geo-maps' ),
								'default'    => '',
								'desc'       => __( 'If your data entries do not contain a <code>content</code> property, specify which property to use instead. This is the property that will contain the content that is triggered when the marker is clicked.' ),
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'json_data' ] ],
							],
						],
					],

					'iconMarkersLegend'          => [
						'type'     => 'fieldset',
						/* translators: legend refers to a caption or visual element explaining colours on map */
						'title'    => __( 'Show in Legend', 'interactive-geo-maps' ),
						'subtitle' => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
						'fields'   => [
							'enabled' => [
								'type'    => 'switcher',
								/* translators: legend refers to a caption or visual element explaining colours on map */
								'title'   => __( 'Show in Legend', 'interactive-geo-maps' ),
								'desc'    => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
								'default' => false,
							],
							'title'   => [
								'type'       => 'text',
								'title'      => __( 'Title in Legend', 'interactive-geo-maps' ),
								'dependency' => [ 'enabled', '==', true ],
							],
						],
					],
					'iconMarkersMobileSize' => [
						'type'    => 'spinner',
						'title'   => __( 'Mobile Size', 'interactive-geo-maps' ),
						'default' => 100,
						'unit'    => '%',
						'desc'    => __( 'On smaller screens markers will decrease proportionally to their size on desktop', 'interactive-geo-maps' ),
					],
					'iconMarkersTooltipTemplate' => [
						'type'    => $ttemplate_editor,
						'title'   => __( 'Tooltip Template', 'interactive-geo-maps' ),
						'default' => '',
						'desc'    => __( 'Override default tooltip template for this series of data.<br>Leave blank to use default. <a href="https://interactivegeomaps.com/docs/pro-tooltip-content-and-design/">More Information</a>  <span class="dashicons dashicons-external"></span>. ', 'interactive-geo-maps' ),
					],
				],
			];
		}

		// Labels Options
		if( ! isset( $options['labelFeatures'] ) || empty( $options['labelFeatures'] ) || ( isset( $options['labelFeatures'] ) && isset( $options['labelFeatures']['enabled'] ) && $options['labelFeatures']['enabled'] ) ) {

			$model['meta']['map_info']['sections']['labels'] = [
				'title'  => __( 'Labels', 'interactive-geo-maps' ),
				'icon'   => 'fa fa-font fa-lg',
				'fields' => [
					'iconMarkers_info'      => [
						'type'    => 'content',
						'content' => __( 'Add text labels to the map.<br>Click "Add New Label" below to start .', 'interactive-geo-maps' ),
					],
					'labels'                => [
						'type'         => 'group',
						'button_title' => __( 'Add New Label', 'interactive-geo-maps' ),

						'fields'       => [
							'id'               => [
								'type'       => 'text',
								'title'      => __( 'Title', 'interactive-geo-maps' ),
								'desc'       => __( 'Use unique titles, if possible without special characters.', 'interactive-geo-maps' ),
								'attributes' => [
									'class' => 'skip-preview',
								],
							],
							'coordinates'      => [
								'type'   => $coordinates_editor,
								'title'  => __( 'Coordinates', 'interactive-geo-maps' ),
								'fields' => [
									'name'      => [
										'type'       => 'text',
										'title'      => __( 'Location', 'interactive-geo-maps' ),
										'class'      => 'geocoding geocoding-hide',
										'attributes' => [
											'class' => 'geocoding-input',
										],
									],
									'latitude'  => [
										'type'  => 'text',
										'title' => __( 'Latitude', 'interactive-geo-maps' ),

									],
									'longitude' => [
										'type'  => 'text',
										'title' => __( 'Longitude', 'interactive-geo-maps' ),

									],
								],
							],

							'tooltipContent'   => [
								'type'  => $tooltip_editor,
								'title' => __( 'Tooltip Content', 'interactive-geo-maps' ),
							],
							'content'          => [
								'type'     => $action_content_editor,
								'title'    => __( 'Action Content', 'interactive-geo-maps' ),
								'subtitle' => __( 'URL or content to trigger when label is clicked.', 'interactive-geo-maps' ),
							],
							'useDefaults'      => [
								'type'     => 'switcher',
								'title'    => __( 'Use defaults', 'interactive-geo-maps' ),
								'subtitle' => __( 'Use default colors and actions.', 'interactive-geo-maps' ),
								'default'  => true,
							],
							'fontSize'         => [
								'type'       => 'spinner',
								'default'    => 15,
								'title'      => __( 'Size', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'fill'             => [
								'type'       => $color_field,
								'title'      => __( 'Fill Color', 'interactive-geo-maps' ),
								'default'    => '#111111',
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'hover'            => [
								'type'       => $color_field,
								'title'      => __( 'Hover Color', 'interactive-geo-maps' ),
								'default'    => '#000000',
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'horizontalCenter' => [
								'type'       => 'select',
								'title'      => __( 'Horizontal center', 'interactive-geo-maps' ),
								'default'    => 'middle',
								'dependency' => [ 'useDefaults', '==', false ],
								'options'    => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
								],

							],
							'verticalCenter'   => [
								'type'       => 'select',
								'title'      => __( 'Vertical center', 'interactive-geo-maps' ),
								'default'    => 'middle',
								'dependency' => [ 'useDefaults', '==', false ],
								'options'    => [
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
								],
							],
							'action'           => [
								'type'       => 'select',
								'title'      => __( 'Click Action', 'interactive-geo-maps' ),
								'desc'       => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
								'options'    => $actions_default,
								'default'    => 'default',
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'pageBelowInfo'    => [
								'type'       => 'submessage',
								'style'      => 'info',
								'content'    => __( 'To display a page below, add the numeric ID of the page you want to display in the "Action Content" field above.', 'interactive-geo-maps' ),
								'dependency' => [ [ 'action', '==', 'igm_display_page_below' ], [ 'action', '==', 'igm_display_page_below_and_scroll' ] ],
							],

						],
					],
					'labelDefaults'         => [
						'type'   => 'fieldset',
						'title'  => __( 'Default values', 'interactive-geo-maps' ),
						'fields' => [
							'fontSize'            => [
								'type'    => 'spinner',
								'default' => 15,
								'title'   => __( 'Size', 'interactive-geo-maps' ),
							],
							'fill'                => [
								'type'    => $color_field,
								'title'   => __( 'Color', 'interactive-geo-maps' ),
								'default' => '#111111',

							],
							'hover'               => [
								'type'    => $color_field,
								'title'   => __( 'Hover Color', 'interactive-geo-maps' ),
								'default' => '#000000',
							],
							'action'              => [
								'type'    => 'select',
								'title'   => __( 'Click Action', 'interactive-geo-maps' ),
								'desc'    => '<a href="https://interactivegeomaps.com/docs/click-actions/" target="_blank">' . __( 'More information about click actions', 'interactive-geo-maps' ) . '</a> <span class="dashicons dashicons-external"></span>',
								'options' => $label_actions,
								'default' => 'none',
							],
							'customAction'        => [
								'type'       => 'code_editor',
								'title'      => __( 'Custom Javascript Action', 'interactive-geo-maps' ),
								'desc'       => __( 'When a marker is clicked a function will be called where the object with the data from the clicked region will be passed. You can write the contents of the function here. <a href="https://interactivegeomaps.com/docs/pro-custom-click-actions/" target="_blank">More information</a> <span class="dashicons dashicons-external"></span>', 'interactive-geo-maps' ),
								'dependency' => [ 'action', '==', 'customLabelAction' ],
							],
							'pageBelowInfo'       => [
								'type'       => 'submessage',
								'style'      => 'info',
								'content'    => __( 'To display a page below, add the numeric ID of the page you want to display in the "Action Content" field of each entry.', 'interactive-geo-maps' ),
								'dependency' => [ [ 'action', '==', 'igm_display_page_below' ], [ 'action', '==', 'igm_display_page_below_and_scroll' ] ],
							],
							'triggerClickOnHover' => [
								'type'  => 'switcher',
								'title' => __( 'Trigger on Hover', 'interactive-geo-maps' ),
								'desc'  => __( 'When enabled, when the user hovers a label, it will trigger a click automatically.', 'interactive-geo-maps' ),
							],
						],
					],
					'labelPosition'         => [
						'type'   => 'fieldset',
						'title'  => __( 'Default Positioning', 'interactive-geo-maps' ),
						'desc'   => __( 'Where is the central point (coordinates value) located?', 'interactive-geo-maps' ),
						'fields' => [
							'horizontalCenter' => [
								'type'    => 'select',
								'title'   => __( 'Horizontal center', 'interactive-geo-maps' ),
								'default' => 'middle',
								'options' => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
								],

							],
							'verticalCenter'   => [
								'type'    => 'select',
								'title'   => __( 'Vertical center', 'interactive-geo-maps' ),
								'default' => 'middle',
								'options' => [
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
								],
							],
						],
					],
					'labelStyle'            => [
						'type'   => 'fieldset',
						'title'  => __( 'Font Styles', 'interactive-geo-maps' ),
						'fields' => [
							'fontFamily' => [
								'type'    => 'text',
								'title'   => __( 'Font Family', 'interactive-geo-maps' ),
								'default' => 'inherit',
							],
							'fontWeight' => [
								'type'    => 'select',
								'title'   => __( 'Font Weight', 'interactive-geo-maps' ),
								'default' => 'normal',
								'options' => [
									'normal'  => 'normal',
									'bold'    => 'bold',
									'bolder'  => 'bolder',
									'lighter' => 'lighter',
								],
							],
						],
					],
					'labelsLegend'          => [
						'type'     => 'fieldset',
						/* translators: legend refers to a caption or visual element explaining colours on map */
						'title'    => __( 'Show in Legend', 'interactive-geo-maps' ),
						'subtitle' => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
						'fields'   => [
							'enabled' => [
								'type'    => 'switcher',
								/* translators: legend refers to a caption or visual element explaining colours on map */
								'title'   => __( 'Show in Legend', 'interactive-geo-maps' ),
								'desc'    => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
								'default' => false,
							],
							'title'   => [
								'type'       => 'text',
								'title'      => __( 'Title in Legend', 'interactive-geo-maps' ),
								'dependency' => [ 'enabled', '==', true ],
							],
						],
					],
					// current aproach on maps service not working
					'labelsMobileSize' => [
						'type'    => 'spinner',
						'title'   => __( 'Mobile Size', 'interactive-geo-maps' ),
						'default' => 100,
						'unit'    => '%',
						'desc'    => __( 'On smaller screens labels will decrease proportionally to their size on desktop', 'interactive-geo-maps' ),
					],
					'labelsTooltipTemplate' => [
						'type'    => $ttemplate_editor,
						'title'   => __( 'Tooltip Template', 'interactive-geo-maps' ),
						'default' => '',
						'desc'    => __( 'Override default tooltip template for this series of data.<br>Leave blank to use default. <a href="https://interactivegeomaps.com/docs/pro-tooltip-content-and-design/">More Information</a>  <span class="dashicons dashicons-external"></span>. ', 'interactive-geo-maps' ),
					],

				],
			];
		}

		// Lines Options
		if( ! isset( $options['lineFeatures'] ) || empty( $options['lineFeatures'] ) || ( isset( $options['lineFeatures'] ) && isset( $options['lineFeatures']['enabled'] ) && $options['lineFeatures']['enabled'] ) ) {

			$model['meta']['map_info']['sections']['lines'] = [
				'title'  => __( 'Lines', 'interactive-geo-maps' ),
				'icon'   => 'fa fa-long-arrow-right fa-lg',
				'fields' => [
					'lines_info'   => [
						'type'    => 'content',
						'content' => __( 'Add lines connecting points in the map.<br>Click "Add New Line" below to start.', 'interactive-geo-maps' ),
					],
					'lines'        => [
						'type'         => 'group',
						'button_title' => __( 'Add New Line', 'interactive-geo-maps' ),

						'fields'       => [
							'title'        => [
								'type'  => 'text',
								'title' => __( 'Title', 'interactive-geo-maps' ),
							],

							'multiGeoLine' => [
								'type'         => 'repeater',
								'min'          => 2,
								'button_title' => __( 'Add Point', 'interactive-geo-maps' ),
								'title'        => __( 'Points to Connect', 'interactive-geo-maps' ),
								'default'      => [
									[
										[
											'title'     => '',
											'latitude'  => '',
											'longitude' => '',
										],
									],
									[
										[
											'title'     => '',
											'latitude'  => '',
											'longitude' => '',
										],
									],

								],
								'fields'       => [
									'coordinates' => [
										'type'   => $coordinates_editor,
										'title'  => __( 'Point Info', 'interactive-geo-maps' ),
										'fields' => [
											'name'      => [
												'type'       => 'text',
												'title'      => __( 'Title', 'interactive-geo-maps' ),
												'class'      => 'geocoding',
												'attributes' => [
													'class' => 'geocoding-input',
												],
											],
											'latitude'  => [
												'type'  => 'text',
												'title' => __( 'Latitude', 'interactive-geo-maps' ),

											],
											'longitude' => [
												'type'  => 'text',
												'title' => __( 'Longitude', 'interactive-geo-maps' ),

											],
										],
									],
								],
							],
							'useDefaults'  => [
								'type'     => 'switcher',
								'title'    => __( 'Use defaults', 'interactive-geo-maps' ),
								'subtitle' => __( 'Use default colors and actions.', 'interactive-geo-maps' ),
								'default'  => true,
							],
							'stroke'       => [
								'type'       => $color_field,
								'title'      => __( 'Stroke Color', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'strokeDash'   => [
								'type'       => 'spinner',
								'default'    => 2,
								'max'        => 20,
								'min'        => 0,
								'step'       => 0.5,
								'attributes' => [
									'step' => 'any',
								],
								'title'      => __( 'Stroke Dash', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'strokeWidth'  => [
								'type'       => 'spinner',
								'default'    => 6,
								'title'      => __( 'Stroke Width', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
							],
							'curvature'    => [
								'type'       => 'spinner',
								'default'    => 0,
								'max'        => 1,
								'min'        => -1,
								'step'       => 0.1,
								'attributes' => [
									'step' => 'any',
								],
								'title'      => __( 'Curvature', 'interactive-geo-maps' ),
								'dependency' => [ 'useDefaults', '==', false ],
							],
						],
					],
					'lineDefaults' => [
						'type'   => 'fieldset',
						'title'  => __( 'Defaults', 'interactive-geo-maps' ),
						'fields' => [
							'stroke'      => [
								'type'    => $color_field,
								'title'   => __( 'Stroke Color', 'interactive-geo-maps' ),
								'default' => '#CCC',
							],
							'strokeDash'  => [
								'type'       => 'spinner',
								'default'    => 2,
								'max'        => 20,
								'min'        => 0,
								'step'       => 0.5,
								'attributes' => [
									'step' => 'any',
								],
								'title'      => __( 'Stroke Dash', 'interactive-geo-maps' ),
							],
							'strokeWidth' => [
								'type'    => 'spinner',
								'default' => 6,
								'title'   => __( 'Stroke Width', 'interactive-geo-maps' ),
							],
							'curvature'   => [
								'type'       => 'spinner',
								'default'    => 0,
								'max'        => 1,
								'min'        => -1,
								'step'       => 0.1,
								'attributes' => [
									'step' => 'any',
								],
								'title'      => __( 'Curvature', 'interactive-geo-maps' ),
							],
						],
					],
					'linesLegend'  => [
						'type'     => 'fieldset',
						'title'    => __( 'Show in Legend', 'interactive-geo-maps' ),
						'subtitle' => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
						'fields'   => [
							'enabled' => [
								'type'    => 'switcher',
								/* translators: legend refers to a caption or visual element explaining colours on map */
								'title'   => __( 'Show in Legend', 'interactive-geo-maps' ),
								'desc'    => __( 'Will display in legend if enabled.', 'interactive-geo-maps' ),
								'default' => false,
							],
							'title'   => [
								'type'       => 'text',
								'title'      => __( 'Title in Legend', 'interactive-geo-maps' ),
								'dependency' => [ 'enabled', '==', true ],
							],
						],
					],
				],
			];
		}

		//Legend Options
		if( ! isset( $options['legendFeatures'] ) || empty( $options['legendFeatures'] ) || ( isset( $options['legendFeatures'] ) && isset( $options['legendFeatures']['enabled'] ) && $options['legendFeatures']['enabled'] ) ) {


			$model['meta']['map_info']['sections']['legend'] = [
				'title'  => __( 'Legend', 'interactive-geo-maps' ),
				'icon'   => 'fa fa-square',
				'fields' => [
					'legend'       => [
						'type'   => 'fieldset',
						'fields' => [
							'legendFieldsetTitle' => [
								'type'    => 'subheading',
								'content' => __( 'Auto Legend', 'interactive-geo-maps' ),
							],
							'enabled'             => [
								'type'    => 'switcher',
								'title'   => __( 'Enable', 'interactive-geo-maps' ),
								'default' => false,
								'desc'    => __( 'Legend based on map data series', 'interactive-geo-maps' ),
							],
							'clickable'           => [
								'type'       => 'select',
								'title'      => __( 'Clickable', 'interactive-geo-maps' ),
								'desc'       => __( 'This will not apply to the custom added labels.', 'interactive-geo-maps' ),
								'default'    => 'false',
								'options'    => [
									'false'  => __( 'Disabled', 'interactive-geo-maps' ),
									'toggle' => __( 'Toggle Visibility', 'interactive-geo-maps' ),
									'select' => __( 'Highlight', 'interactive-geo-maps' ),
								],
								'dependency' => [ 'enabled', '==', true ],
							],
							'position'            => [
								'type'       => 'select',
								'title'      => __( 'Position', 'interactive-geo-maps' ),
								'default'    => 'bottom',
								'dependency' => [ 'enabled', '==', true ],
								'options'    => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ), // bug?
									// 'absolute' => __( 'Absolute', 'interactive-geo-maps' ),
								],
							],
							'align'               => [
								'type'       => 'select',
								'title'      => __( 'Horizontal alignment', 'interactive-geo-maps' ),
								'default'    => 'left',
								'options'    => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'center' => __( 'Center', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
								],
								'dependency' => [
									[ 'enabled', '==', true ],
									[ 'position', 'any', 'top,bottom' ],
								],
							],
							'valign'              => [
								'type'       => 'select',
								'title'      => __( 'Vertical alignment', 'interactive-geo-maps' ),
								'default'    => 'bottom',
								'options'    => [
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
								],
								'dependency' => [
									[ 'enabled', '==', true ],
									[ 'position', 'any', 'right,left' ],
								],
							],
							'style'               => [
								'type'       => 'select',
								'title'      => __( 'Style', 'interactive-geo-maps' ),
								'default'    => 'default',
								'options'    => [
									'default' => __( 'Default', 'interactive-geo-maps' ),
									'small'   => __( 'Small', 'interactive-geo-maps' ),
									'xsmall'  => __( 'X-Small', 'interactive-geo-maps' ),
									'regular' => __( 'Regular', 'interactive-geo-maps' ),
									'large'   => __( 'Large', 'interactive-geo-maps' ),
									'larger'  => __( 'Larger', 'interactive-geo-maps' ),
								],
								'dependency' => [ 'enabled', '==', true ],
							],

							'fill'                => [
								'type'       => $color_field,
								'title'      => __( 'Color', 'interactive-geo-maps' ),
								'default'    => '#000000',
								'dependency' => [ 'enabled', '==', true ],
							],
						],
					],

					'customLegend' => [
						'type'   => 'fieldset',
						'fields' => [
							'legendFieldsetTitle' => [
								'type'    => 'subheading',
								'content' => __( 'Custom Legend', 'interactive-geo-maps' ),
							],
							'enabled'             => [
								'type'    => 'switcher',
								'title'   => __( 'Enable', 'interactive-geo-maps' ),
								'default' => false,
								'desc'    => __( 'Custom legend entries', 'interactive-geo-maps' ),
							],
							'data'                => [
								'type'       => 'group',
								'title'      => __( 'Custom entries', 'interactive-geo-maps' ),
								'fields'     => [
									'name' => [
										'type'  => 'text',
										'title' => __( 'Label', 'interactive-geo-maps' ),
									],
									'fill' => [
										'type'    => $color_field,
										/* translators: color for the shape/region on the map */
										'title'   => __( 'Fill Color', 'interactive-geo-maps' ),
										'default' => isset ( $options['defaultActiveColor'] ) ? $options['defaultActiveColor'] : '#99d8c9',
									],
								],
								'dependency' => [ 'enabled', '==', true ],
							],

							'type'                => [
								'type'       => 'select',
								'title'      => __( 'Type', 'interactive-geo-maps' ),
								'default'    => 'internal',
								'dependency' => [ 'enabled', '==', true ],
								'desc'    => __( 'Internal will have positioning options inside the map and will be included in the map if exported;<br>External will display outside the map container. The external will not display in the preview above.<br><a href="https://interactivegeomaps.com/docs/pro-custom-legend-types/" target="_blank">More Information</a> <span class="dashicons dashicons-external"></span>.', 'interactive-geo-maps' ),
								'options'    => [
									'internal' => __( 'Internal', 'interactive-geo-maps' ),
									'external' => __( 'External', 'interactive-geo-maps' ),
								],
							],
							'position'            => [
								'type'       => 'select',
								'title'      => __( 'Position', 'interactive-geo-maps' ),
								'default'    => 'bottom',
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'internal' ] ],
								'options'    => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ), // bug?
									// 'absolute' => __( 'Absolute', 'interactive-geo-maps' ),
								],
							],
							'align'               => [
								'type'       => 'select',
								'title'      => __( 'Horizontal alignment', 'interactive-geo-maps' ),
								'default'    => 'left',
								'options'    => [
									'left'   => __( 'Left', 'interactive-geo-maps' ),
									'center' => __( 'Center', 'interactive-geo-maps' ),
									'right'  => __( 'Right', 'interactive-geo-maps' ),
								],
								'dependency' => [
									[ 'enabled', '==', true ],
									[ 'type', '==', 'internal' ],
									[ 'position', 'any', 'top,bottom' ],
								],
							],
							'valign'              => [
								'type'       => 'select',
								'title'      => __( 'Vertical alignment', 'interactive-geo-maps' ),
								'default'    => 'bottom',
								'options'    => [
									'top'    => __( 'Top', 'interactive-geo-maps' ),
									'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
									'middle' => __( 'Middle', 'interactive-geo-maps' ),
								],
								'dependency' => [
									[ 'enabled', '==', true ],
									[ 'type', '==', 'internal' ],
									[ 'position', 'any', 'right,left' ],
								],
							],
							'style'               => [
								'type'       => 'select',
								'title'      => __( 'Style', 'interactive-geo-maps' ),
								'default'    => 'default',
								'options'    => [
									'default' => __( 'Default', 'interactive-geo-maps' ),
									'small'   => __( 'Small', 'interactive-geo-maps' ),
									'xsmall'  => __( 'X-Small', 'interactive-geo-maps' ),
									'regular' => __( 'Regular', 'interactive-geo-maps' ),
									'large'   => __( 'Large', 'interactive-geo-maps' ),
									'larger'  => __( 'Larger', 'interactive-geo-maps' ),
								],
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'internal' ] ],
							],
							'fill'                => [
								'type'       => $color_field,
								'title'      => __( 'Color', 'interactive-geo-maps' ),
								'default'    => '#000000',
								'dependency' => [ [ 'enabled', '==', true ], [ 'type', '==', 'internal' ] ],
							],
						],
					],

				],
			];
		}

		// Overlay maps options
		if( ! isset( $options['overlayFeatures'] ) || empty( $options['overlayFeatures'] ) || ( isset( $options['overlayFeatures'] ) && isset( $options['overlayFeatures']['enabled'] ) && $options['overlayFeatures']['enabled'] ) ) {

			$model['meta']['map_info']['sections']['overlay'] = [
				'title'  => __( 'Overlay Maps', 'interactive-geo-maps' ),
				'icon'   => 'fa fa-align-justify',
				'fields' => [
					'overlay'          => [
						'title'      => __( 'Overlay Maps', 'interactive-geo-maps' ),
						'subtitle'   => __( 'Select maps that you also want to include in this map.<br>You can sort the options using drag&drop to change their order.', 'interactive-geo-maps' ),
						'type'       => 'checkbox',
						'options'    => 'posts',
						'query_args' => [
							'posts_per_page' => -1,
							// 'order'          => 'ASC',
							// 'orderby'        => 'title',
							'post_type'      => 'igmap',
							'post__not_in'   => [ isset( $_GET['post'] ) && null !== $_GET['post'] && ! is_array( $_GET['post'] ) ? sanitize_key( $_GET['post'] ) : '' ],
						],
					],
					'overlayOrder'     => [
						'type'       => 'textarea',
						'default'    => '',
						'class'      => 'hidden',
						'attributes' => [
							'class' => 'hidden',
						],
					],
					'allowEmpty'       => [
						'type'    => 'switcher',
						'title'   => __( 'Allow empty regions', 'interactive-geo-maps' ),
						'default' => false,
						'desc'    => __( 'If you need the empty regions of the overlay map to display, enable this.<br>If you only need markers or labels in the overlay map and don\'t want to include the empty regions - which might overlay the regions of the parent map - disable this option.', 'interactive-geo-maps' ),
					],
					'drillDownOnClick' => [
						'type'    => 'switcher',
						'title'   => __( 'Drill down on click', 'interactive-geo-maps' ),
						'default' => false,
						'desc'    => __( 'If you have overlaid maps, you can enable this option to "drill down" into that map. <br> When enabled, the overlaid maps will be hidden by default and will only display when that region is clicked.', 'interactive-geo-maps' ),
					],

					'alwaysKeepBase' => [
						'type'    => 'switcher',
						'title'   => __( 'Keep Base Map (Beta)', 'interactive-geo-maps' ),
						'default' => false,
						'dependency' => [ 'drillDownOnClick', '==', true ],
						'desc'    => __( '(Experimental Feature) If you want the base map to always be visible when using the drilldown or show/hide map functions.', 'interactive-geo-maps' ),
					],

					'liveFilter'       => [
						'type'   => 'fieldset',
						'title'  => __( 'External Live Filter', 'interactive-geo-maps' ),
						'desc'   => __( 'Display a list of the maps included that will work as a filter', 'interactive-geo-maps' ),
						'fields' => [
							'enabled'  => [
								'type'    => 'switcher',
								'title'   => __( 'Enable', 'interactive-geo-maps' ),
								'default' => false,
							],
							'type' => [
								'type'       => 'select',
								'title'      => __( 'Type', 'interactive-geo-maps' ),
								'default'    => 'menu',
								'dependency' => [ 'enabled', '==', true ],
								'options'    => [
									'menu' => __( 'Menu', 'interactive-geo-maps' ),
									'dropdown' => __( 'Dropdown', 'interactive-geo-maps' ),
									'menu_dropdown' => __( 'Menu ( w/ Dropdown on Mobile)', 'interactive-geo-maps' ),
								],
							],
							'position' => [
								'type'       => 'select',
								'title'      => __( 'Position', 'interactive-geo-maps' ),
								'default'    => 'above',
								'dependency' => [ 'enabled', '==', true ],
								'options'    => [
									'above' => __( 'Above', 'interactive-geo-maps' ),
									'below' => __( 'Below', 'interactive-geo-maps' ),
								],
							],
							'allLabel' => [
								'type'       => 'text',
								'title'      => __( '"All" Label', 'interactive-geo-maps' ),
								'default'    => 'All',
								'dependency' => [ 'enabled', '==', true ],
							],
							'default'  => [
								'title'      => __( 'Display by default', 'interactive-geo-maps' ),
								'desc'       => __( 'Display this map by default and hide all the others. Select the current map if you want all to display by default.', 'interactive-geo-maps' ),
								'type'       => 'select',
								'options'    => 'posts',
								'dependency' => [ 'enabled', '==', true ],
								'default'    => [ isset( $_GET['post'] ) && null !== $_GET['post'] && ! is_array( $_GET['post'] ) ? sanitize_key( $_GET['post'] ) : '' ],
								'query_args' => [
									'posts_per_page' => -1,
									'post_type'      => 'igmap',
								],
							],
							'includeCount'  => [
								'type'    => 'switcher',
								'title'   => __( 'Include Count', 'interactive-geo-maps' ),
								'desc'    => __( 'The number of regions and markers in each map will display on each item.', 'interactive-geo-maps' ),
								'dependency' => [ 'enabled', '==', true ],
								'default' => false,
							],
							'keepBase'  => [
								'type'    => 'switcher',
								'title'   => __( 'Keep Base Map', 'interactive-geo-maps' ),
								'desc'    => __( 'Set base map data to always be visible.', 'interactive-geo-maps' ),
								'dependency' => [ 'enabled', '==', true ],
								'default' => false,
							],
						],
					],
				],
			];
		}

		// Map Features
		$model['meta']['map_info']['sections']['tooltip'] = [
			'title'  => __( 'Tooltip Options', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-comment fa-lg',
			'fields' => [
				'tooltip' => [
					'type'   => 'fieldset',
					'fields' => [
						'tooltip_fieldset_title' => [
							'type'    => 'subheading',
							'content' => __( 'Tooltip Settings', 'interactive-geo-maps' ),
						],
						'enabled'                => [
							'type'    => 'switcher',
							'title'   => __( 'Tooltip', 'interactive-geo-maps' ),
							'desc'    => __( 'Enable tooltips', 'interactive-geo-maps' ),
							'default' => true,
						],
						'fixed'                  => [
							'type'       => 'switcher',
							'title'      => __( 'Fixed', 'interactive-geo-maps' ),
							'default'    => false,
							'desc'       => __( 'If you need the tooltips to be clickable, enable this option.', 'interactive-geo-maps' ),
							'dependency' => [ 'enabled', '==', true ],

						],

						'showTooltipOn'          => [
							'type'       => 'select',
							'title'      => __( 'Show Tooltip On (Beta)', 'interactive-geo-maps' ),
							'default'    => 'hover',
							'options'    => [
								'hover'  => __( 'Hover (tap on mobile) - Recommended', 'interactive-geo-maps' ),
								'hit'    => __( 'Hit (tap or click)', 'interactive-geo-maps' ),
								'always' => __( 'Always', 'interactive-geo-maps' ),
							],
							'desc'       => __( 'When the tooltip should display. This is still an experimental feature. It does not work consistenly. Make sure to test properly.', 'interactive-geo-maps' ),
							'dependency' => [[ 'enabled', '==', true ], [ 'fixed', '==', true ]],

						],

						'template'               => [
							'type'       => 'text',
							'title'      => __( 'Content template', 'interactive-geo-maps' ),
							'default'    => '{tooltipContent}',
							'desc'       => __( 'You can use placeholders that exist in your data source.<br>Default value is {tooltipContent}.', 'interactive-geo-maps' ),
							'dependency' => [ 'enabled', '==', true ],
						],
						'onlyWithData'           => [
							'type'       => 'switcher',
							'title'      => __( 'Only active', 'interactive-geo-maps' ),
							'desc'       => __( 'Only display the tooltip on entries that where added by us.<br>This is particularly useful if you use the tooltip content template option to display data that exists on empty regions also.', 'interactive-geo-maps' ),
							'default'    => false,
							'dependency' => [ 'enabled', '==', true ],
						],
						'disableMobile'          => [
							'type'       => 'switcher',
							'title'      => __( 'Disable on mobile', 'interactive-geo-maps' ),
							'desc'       => __( '(Beta) Disable tooltips on mobile only.', 'interactive-geo-maps' ),
							'default'    => false,
							'dependency' => [ 'enabled', '==', true ],
						],
						'holdAction'             => [
							'type'       => 'switcher',
							'title'      => __( 'Hold Click Actions on Mobile', 'interactive-geo-maps' ),
							'desc'       => __( '(Beta) On touch devices, some actions like opening a URL on the same window or displaying a lightbox, won\'t allow for the tooltip to be visible, since the action will be triggered imediately. Enabling this action will prevent this from happening and require a second tap on the region or marker to trigger the action.', 'interactive-geo-maps' ),
							'default'    => false,
							'dependency' => [ [ 'enabled', '==', true ], [ 'disableMobile', '!=', true ] ],
						],
						'backgroundColor'        => [
							'type'       => $color_field,
							'title'      => __( 'Background color', 'interactive-geo-maps' ),
							'default'    => '#FFFFFF',
							'dependency' => [ 'enabled', '==', true ],
						],
						'color'                  => [
							'type'       => $color_field,
							'title'      => __( 'Font color', 'interactive-geo-maps' ),
							'default'    => '#000000',
							'dependency' => [ 'enabled', '==', true ],
						],
						'fontFamily'             => [
							'type'       => 'text',
							'title'      => __( 'Font Family', 'interactive-geo-maps' ),
							'default'    => 'inherit',
							'dependency' => [ 'enabled', '==', true ],
						],
						'fontSize'               => [
							'type'       => 'text',
							'title'      => __( 'Font Size', 'interactive-geo-maps' ),
							'default'    => '',
							'dependency' => [ 'enabled', '==', true ],
						],
						'fontWeight'             => [
							'type'       => 'select',
							'title'      => __( 'Font Weight', 'interactive-geo-maps' ),
							'default'    => 'normal',
							'dependency' => [ 'enabled', '==', true ],
							'options'    => [
								'normal'  => 'normal',
								'bold'    => 'bold',
								'bolder'  => 'bolder',
								'lighter' => 'lighter',
							],
						],
						'cornerRadius'           => [
							'type'       => 'spinner',
							'title'      => __( 'Corner radius', 'interactive-geo-maps' ),
							'default'    => '20',
							'unit'       => 'px',
							'validate'   => 'csf_validate_numeric',
							'dependency' => [ 'enabled', '==', true ],
						],
						'pointerLength'           => [
							'type'       => 'spinner',
							'title'      => __( 'Pointer Length', 'interactive-geo-maps' ),
							'default'    => '5',
							'unit'       => 'px',
							'validate'   => 'csf_validate_numeric',
							'dependency' => [ 'enabled', '==', true ],
						],
						'borderColor'            => [
							'type'       => $color_field,
							'title'      => __( 'Border color', 'interactive-geo-maps' ),
							'default'    => '#f5f5f5',
							'dependency' => [ 'enabled', '==', true ],
						],
						'borderWidth'            => [
							'type'       => 'spinner',
							'title'      => __( 'Border width', 'interactive-geo-maps' ),
							'default'    => '1',
							'unit'       => 'px',
							'validate'   => 'csf_validate_numeric',
							'dependency' => [ 'enabled', '==', true ],
						],

						'maxWidth'               => [
							'type'       => 'spinner',
							'title'      => __( 'Maximum Width', 'interactive-geo-maps' ),
							'default'    => '',
							'unit'       => 'px',
							'step'       => 10,
							'desc'       => __( 'Leave it blank if you want the tooltip to calculate the size automatically.', 'interactive-geo-maps' ),
							'validate'   => 'csf_validate_numeric',
							'dependency' => [ 'enabled', '==', true ],
						],

						'customShadow'           => [
							'type'       => 'switcher',
							'title'      => __( 'Customize Shadow', 'interactive-geo-maps' ),
							'desc'       => __( 'Enable this option to customize the dropshadow effect on the tooltip', 'interactive-geo-maps' ),
							'default'    => false,
							'dependency' => [ 'enabled', '==', true ],
						],

						'customShadowOpts'       => [
							'type'       => 'fieldset',
							'dependency' => [
								[ 'enabled', '==', true ],
								[ 'customShadow', '==', true ],
							],
							'title'      => __( 'Tooltip Shadow', 'interactive-geo-maps' ),
							'fields'     => [
								'dx'      => [
									'type'    => 'spinner',
									'title'   => __( 'Horizontal Position', 'interactive-geo-maps' ),
									'default' => 1,
									'min'     => -20,
								],
								'dy'      => [
									'type'    => 'spinner',
									'title'   => __( 'Vertical Position', 'interactive-geo-maps' ),
									'default' => 1,
									'min'     => -20,
								],
								'blur'    => [
									'type'    => 'spinner',
									'title'   => __( 'Blur Level', 'interactive-geo-maps' ),
									'default' => 1,
								],
								'opacity' => [
									'type'       => 'spinner',
									'title'      => __( 'Opacity', 'interactive-geo-maps' ),
									'default'    => 0.5,
									'min'        => 0,
									'max'        => 1,
									'step'       => 0.1,
									'attributes' => [
										'step' => 'any',
									],
								],
								'color'   => [
									'type'    => $color_field,
									'title'   => __( 'Color', 'interactive-geo-maps' ),
									'default' => '#000000',
								],
							],
						],

					],
				],
			],
		];

		// Map Features
		$model['meta']['map_info']['sections']['features'] = [
			'title'  => __( 'Map Controls', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-plus-circle fa-lg',
			'fields' => [
				'zoom'             => [
					'type'   => 'fieldset',
					'fields' => [

						'zoomFieldsetTitle' => [
							'type'    => 'subheading',
							'content' => __( 'Zoom', 'interactive-geo-maps' ),
						],
						'enabled'           => [
							'type'    => 'switcher',
							'title'   => __( 'Zoom Enabled', 'interactive-geo-maps' ),
							'desc'    => __( 'Enable user controlled zoom features', 'interactive-geo-maps' ),
							'default' => isset( $options['zoomMaster'] ) ? $options['zoomMaster'] : true,
						],
						'draggable'         => [
							'type'       => 'switcher',
							'title'      => __( 'Draggable', 'interactive-geo-maps' ),
							'desc'       => __( 'Allow pan with drag. Will also enable zoom on mobile devices with touch actions.', 'interactive-geo-maps' ),
							'default'    => true,
							'dependency' => [ 'enabled', '==', true ],
						],
						'mobileResizable'   => [
							'type'       => 'switcher',
							'title'      => __( 'Allow Touch Resize', 'interactive-geo-maps' ),
							'desc'       => __( 'Only allow users on mobile devices to zoom/drag with touch actions', 'interactive-geo-maps' ),
							'default'    => true,
							'dependency' => [ 'draggable', '==', false ],
						],
						'doubleHitZoom'     => [
							'type'       => 'switcher',
							'title'      => __( 'Double click zoom', 'interactive-geo-maps' ),
							'default'    => true,
							'dependency' => [ 'enabled', '==', true ],

						],

						'wheelable'         => [
							'type'       => 'switcher',
							'title'      => __( 'Mouse wheel', 'interactive-geo-maps' ),
							'default'    => true,
							'dependency' => [ 'enabled', '==', true ],

						],
						'zoomOnClick'       => [
							'type'       => 'switcher',
							'title'      => __( 'Zoom on click', 'interactive-geo-maps' ),
							'default'    => false,
							'dependency' => [ 'enabled', '==', true ],
						],
						'controls'          => [
							'type'       => 'switcher',
							'title'      => __( 'Display controls', 'interactive-geo-maps' ),
							'default'    => true,
							'dependency' => [ 'enabled', '==', true ],
						],
						'homeButton'        => [
							'type'       => 'switcher',
							'title'      => __( 'Display Home Button', 'interactive-geo-maps' ),
							'default'    => true,
							'dependency' => [
								[ 'controls', '==', true ],
								[ 'enabled', '==', true ],
							],
						],
						'maxZoomLevel'      => [
							'type'       => 'spinner',
							'title'      => __( 'Maximum Zoom Level', 'interactive-geo-maps' ),
							'default'    => 32,
							'min'        => 1,
							'step'       => 5,
							'attributes' => [
								'step' => 'any',
							],
							'dependency' => [
								[ 'controls', '==', true ],
								[ 'enabled', '==', true ],
							],
						],
						'controlsPositions' => [
							'type'       => 'fieldset',
							'dependency' => [
								[ 'controls', '==', true ],
								[ 'enabled', '==', true ],
							],
							'title'      => __( 'Controls Position', 'interactive-geo-maps' ),
							'fields'     => [
								'align'  => [
									'type'    => 'select',
									'title'   => __( 'Horizontal alignment', 'interactive-geo-maps' ),
									'default' => 'right',
									'options' => [
										'left'  => __( 'Left', 'interactive-geo-maps' ),
										'right' => __( 'Right', 'interactive-geo-maps' ),
									],

								],
								'valign' => [
									'type'    => 'select',
									'title'   => __( 'Vertical alignment', 'interactive-geo-maps' ),
									'default' => 'bottom',
									'options' => [
										'top'    => __( 'Top', 'interactive-geo-maps' ),
										'middle' => __( 'Middle', 'interactive-geo-maps' ),
										'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
									],

								],
							],
						],
						'externalControls'  => [
							'type'       => 'switcher',
							'title'      => __( 'External Controls on Mobile', 'interactive-geo-maps' ),
							'desc'       => __( 'When enabled, on smaller screens, bigger controls will display below the map.', 'interactive-geo-maps' ),
							'default'    => false,
							'dependency' => [
								[ 'controls', '==', true ],
								[ 'enabled', '==', true ],
							],
						],
					],
				],
				'touchInterface'   => [
					'type'   => 'fieldset',
					'fields' => [
						'legendFieldsetTitle'   => [
							'type'    => 'subheading',
							'content' => __( 'Touch Interface', 'interactive-geo-maps' ),
						],
						'tapToActivate'         => [
							'type'    => 'switcher',
							'title'   => __( 'Tap to activate', 'interactive-geo-maps' ),
							'desc'    => __( 'Zoom, pan and scroll gestures will only work after user taps the map on touch devices', 'interactive-geo-maps' ),
							'default' => false,
						],
						'tapTimeout'            => [
							'type'       => 'text',
							'title'      => __( 'Tap Timeout', 'interactive-geo-maps' ),
							'default'    => '3000',
							'desc'       => __( 'Exit active mode after this amount of time (in milliseconds) of inactivity.', 'interactive-geo-maps' ),
							'dependency' => [ 'tapToActivate', '==', true ],
						],
						'dragGrip'              => [
							'type'    => 'switcher',
							'title'   => __( 'Drag Grip', 'interactive-geo-maps' ),
							'desc'    => __( 'Enable a special area on the map that can be used to scroll the page.', 'interactive-geo-maps' ),
							'default' => false,
						],
						'dragGripAutoHideDelay' => [
							'type'       => 'text',
							'title'      => __( 'Drag Grip Auto Hide Delay', 'interactive-geo-maps' ),
							'default'    => '3000',
							'desc'       => __( 'How long the drag grip will stay on screen (in milliseconds).', 'interactive-geo-maps' ),
							'dependency' => [ 'dragGrip', '==', true ],
						],
					],
				],
				'fullScreen'       => [
					'type'   => 'fieldset',
					'fields' => [
						'fullScreenTitle' => [
							'type'    => 'subheading',
							'content' => __( 'Fullscreen Button', 'interactive-geo-maps' ),
						],
						'enabled'         => [
							'type'    => 'switcher',
							'title'   => __( 'Enable', 'interactive-geo-maps' ),
							'desc'    => __( 'Display a button that allows users to set the map to take the fullscreen. <br>Some features might not be available when the map is on fullscreen.', 'interactive-geo-maps' ),
							'default' => false,
						],
						'mobileOnly'      => [
							'type'       => 'switcher',
							'title'      => __( 'Mobile only', 'interactive-geo-maps' ),
							'default'    => false,
							'dependency' => [ 'enabled', '==', true ],
						],
						'align'           => [
							'type'       => 'select',
							'title'      => __( 'Horizontal position', 'interactive-geo-maps' ),
							'default'    => 'right',
							'dependency' => [ 'enabled', '==', true ],
							'options'    => [
								'left'  => __( 'Left', 'interactive-geo-maps' ),
								'right' => __( 'Right', 'interactive-geo-maps' ),
							],

						],
						'valign'          => [
							'type'       => 'select',
							'title'      => __( 'Vertical position', 'interactive-geo-maps' ),
							'default'    => 'top',
							'dependency' => [ 'enabled', '==', true ],
							'options'    => [
								'top'    => __( 'Top', 'interactive-geo-maps' ),
								'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
							],

						],
					],
				],
				'externalDropdown' => [
					'type'   => 'fieldset',
					'fields' => [
						'externalDropdownTitle' => [
							'type'    => 'subheading',
							'content' => __( 'External Dropdown', 'interactive-geo-maps' ),
						],
						'enabled'               => [
							'type'    => 'switcher',
							'title'   => __( 'Enable', 'interactive-geo-maps' ),
							'desc'    => __( 'Display a dropdown outside the map that will allow users to select an entry on the map. Currently not working with overlay maps.<br>You can also use the shortcode <pre>[display-map-dropdown id="X"]</pre> to display the dropdown anywhere on your page.', 'interactive-geo-maps' ),
							'default' => false,
						],
						'mobileOnly'            => [
							'type'       => 'switcher',
							'title'      => __( 'Mobile only', 'interactive-geo-maps' ),
							'default'    => false,
							'dependency' => [ 'enabled', '==', true ],
						],
						'namePropery'           => [
							'type'       => 'text',
							'title'      => __( 'Label Property', 'interactive-geo-maps' ),
							'desc'       => __( 'What object property to use to populate the label for each entry. Useful if you\'re using external data sources. Otherwise leave empty by default.', 'interactive-geo-maps' ),
							'default'    => '',
							'dependency' => [ 'enabled', '==', true ],
						],
						'placeholder'           => [
							'type'       => 'text',
							'title'      => __( 'Placeholder text', 'interactive-geo-maps' ),
							'default'    => __( 'Select', 'interactive-geo-maps' ),
							'dependency' => [ 'enabled', '==', true ],
						],
						'noResults'             => [
							'type'       => 'text',
							'title'      => __( 'No Matches text', 'interactive-geo-maps' ),
							'default'    => __( 'No Matches', 'interactive-geo-maps' ),
							'dependency' => [ 'enabled', '==', true ],
						],
						'select'                => [
							'type'       => 'text',
							'title'      => __( 'Select text', 'interactive-geo-maps' ),
							'default'    => __( 'Tap to Select', 'interactive-geo-maps' ),
							'dependency' => [ 'enabled', '==', true ],
						],
						'excludeRegions'                => [
							'type'       => 'switcher',
							'title'      => __( 'Exclude Regions', 'interactive-geo-maps' ),
							'default'    => false,
							'desc'       => __( 'Do not include region entries in the dropdown options.', 'interactive-geo-maps' ),
							'dependency' => [ 'enabled', '==', true ],
						],
						'groupOverlay'                => [
							'type'       => 'switcher',
							'title'      => __( 'Group Overlay Entries', 'interactive-geo-maps' ),
							'default'    => false,
							'desc'       => __( 'In case the map contains overlay maps with active dropdowns, the entries will be grouped by map.', 'interactive-geo-maps' ),
							'dependency' => [ 'enabled', '==', true ],
						],
						'onlyParent'                => [
							'type'       => 'switcher',
							'title'      => __( 'Only Include Parent Map Entries', 'interactive-geo-maps' ),
							'default'    => false,
							'desc'       => __( 'In case the map contains overlay maps, only include the parent map entries.', 'interactive-geo-maps' ),
							'dependency' => [ 'enabled', '==', true ],
						],
					],
				],
				'exportMenu'       => [
					'type'   => 'fieldset',
					'fields' => [
						'legendFieldsetTitle' => [
							'type'    => 'subheading',
							'content' => __( 'Export Menu', 'interactive-geo-maps' ),
						],
						'enable'              => [
							'type'    => 'switcher',
							'title'   => __( 'Enable', 'interactive-geo-maps' ),
							'default' => false,
						],
						'align'               => [
							'type'       => 'select',
							'title'      => __( 'Horizontal position', 'interactive-geo-maps' ),
							'default'    => 'right',
							'dependency' => [ 'enable', '==', true ],
							'options'    => [
								'left'  => __( 'Left', 'interactive-geo-maps' ),
								'right' => __( 'Right', 'interactive-geo-maps' ),
							],

						],
						'verticalAlign'       => [
							'type'       => 'select',
							'title'      => __( 'Vertical position', 'interactive-geo-maps' ),
							'default'    => 'top',
							'dependency' => [ 'enable', '==', true ],
							'options'    => [
								'top'    => __( 'Top', 'interactive-geo-maps' ),
								'bottom' => __( 'Bottom', 'interactive-geo-maps' ),
							],

						],
					],
				],
			],
		];

		// Custom JS and CSS
		// only add these fields if current user is administrator
		$capability = apply_filters( 'igm_code_capability', 'customize');
		if( current_user_can( $capability ) ) {
			$model['meta']['map_info']['sections']['custom'] = [
				'title'  => __( 'Custom CSS & JS', 'interactive-geo-maps' ),
				'icon'   => 'fa fa-cogs fa-lg',
				'fields' => [
					'custom_css' => [
						'title' => __( 'Custom CSS', 'interactive-geo-maps' ),
						'desc'  => __( 'Custom CSS to load with the layouts. These will not be applied in the map preview.', 'interactive-geo-maps' ),
						'type'  => 'code_editor',
					],
					'custom_js'  => [
						'title' => __( 'Custom Javascript', 'interactive-geo-maps' ),
						'desc'  => __( 'Custom Javascript to load with the layouts. This code will not load here in the administration. Therefore the preview will not consider this.', 'interactive-geo-maps' ),
						'type'  => 'code_editor',
					],
				],
			];
		}

		// Backup Field
		/*
		$model['meta']['map_info']['sections']['backup'] = [
			'title'  => __( 'Backup', 'interactive-geo-maps' ),
			'icon'   => 'fa fa-cogs fa-lg',
			'fields' => [
				'backup'  => [
					'title' => __( 'Backup', 'interactive-geo-maps' ),
					'type'  => 'backup',
				],
			],
		];*/

		// visual options - viewport
		// image background
		$model['meta']['map_info']['sections']['visual']['fields']['visual']['fields'] = array_merge(
			$model['meta']['map_info']['sections']['visual']['fields']['visual']['fields'],
			[
				'bgImage' => [
					'type'    => 'media',
					'title'   => __( 'Background Image', 'interactive-geo-maps' ),
					'library' => 'image',
					'desc'    => __( 'Make sure you set the background to transparent for the image to be visible.<br>This image will also be zoomable. It should have the same aspect ratio as the map to fit perfectly.', 'interactive-geo-maps' ),
				],
			]
		);

		$model['meta']['map_info']['sections']['visual']['fields']['viewport'] = [
			'type'   => 'fieldset',
			'fields' => [
				'viewportFieldsetTitle' => [
					'type'    => 'subheading',
					'content' => __( 'Viewport Settings', 'interactive-geo-maps' ),
				],
				'zoomLevel'             => [
					'type'       => 'spinner',
					'title'      => __( 'Initial zoom', 'interactive-geo-maps' ),
					'default'    => 1,
					'min'        => 1,
					'step'       => 0.5,
					'attributes' => [
						'step' => 'any',
					],
				],
				'homeGeoPoint'          => [
					'type'   => $coordinates_editor,
					'title'  => __( 'Center coordinates', 'interactive-geo-maps' ),
					'desc'   => __( 'Custom initial center for map. Leave blank for default.', 'interactive-geo-maps' ),
					'fields' => [
						'name'      => [
							'type'       => 'text',
							'title'      => __( 'Location', 'interactive-geo-maps' ),
							'class'      => 'geocoding geocoding-hide',
							'attributes' => [
								'class' => 'geocoding-input',
							],
						],
						'latitude'  => [
							'type'  => 'text',
							'title' => __( 'Latitude', 'interactive-geo-maps' ),
						],
						'longitude' => [
							'type'  => 'text',
							'title' => __( 'Longitude', 'interactive-geo-maps' ),

						],
					],

				],
				'offset'                => [
					'type'   => 'fieldset',
					'title'  => __( 'Projection Offset', 'interactive-geo-maps' ),
					'desc'   => __( 'Add delta coordinates offset for current projection.<br><strong>Making changes to these values might affect the "Zoom on click" feature</strong>.', 'interactive-geo-maps' ),
					'fields' => [
						'latitude'  => [
							'type'    => 'spinner',
							'title'   => __( 'Latitude', 'interactive-geo-maps' ),
							'min'     => -360,
							'max'     => 360,
							'step'    => 10,
							'default' => 0,
						],
						'longitude' => [
							'type'    => 'spinner',
							'title'   => __( 'Longitude', 'interactive-geo-maps' ),
							'min'     => -360,
							'max'     => 360,
							'step'    => 10,
							'default' => 0,

						],
					],

				],

			],
		];

		return $model;

	}

	public function map_options( $options ) {

		$options['Custom'] = [
			'custom' => __( 'Custom Map Source', 'interactive-geo-maps' ),
		];

		return $options;
	}

	/**
	 * sanitize raw import data to replace old html chars placeholders with different ones
	 *
	 * @param [type] $data
	 * @return void
	 */
	public static function legacy_raw_sanitize( $data ) {
		if ( isset( $data['raw'] ) ) {
			$search      = [ '&#59', '&#44' ];
			$replace     = [ '&{#59}', '&{#44}' ];
			$data['raw'] = str_replace( $search, $replace, $data['raw'] );
		}
		return $data;
	}

}

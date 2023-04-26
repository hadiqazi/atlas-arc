<?php

namespace Saltus\WP\Plugin\Saltus\InteractiveMaps\Plugin\Pro;

use Saltus\WP\Plugin\Saltus\InteractiveMaps\Core;
use Saltus\WP\Plugin\Saltus\InteractiveMaps\Plugin\Map;

/**
 * Create Map
 */
class ProMap extends Map
{

	/**
	 * The plugin's instance.
	 *
	 * @var Core
	 */
	public $core;


	/**
	 * Options
	 */
	public $options;

	/**
	 * Included Maps in overlay
	 */
	public $overlay_maps;

	/**
	 * Define ProMap
	 *
	 * @param Core $core This plugin's instance.
	 */
	public function __construct(Core $core)
	{

		$this->core         = $core;
		$options            = get_option('interactive-maps');
		$this->options      = $options;
		$this->overlay_maps = [];

		add_filter('igm_prepare_meta', array($this, 'prepare_pro_meta'), 10);
		add_filter('igm_model', array($this, 'check_public'), 10);
		add_filter('igm_map_before', array($this, 'prepare_content_wrapper'), 10, 2);
		add_filter('igm_map_after', array($this, 'prepare_content_wrapper'), 10, 2);

		$this->set_amcharts_filters();
	}

	/**
	 * check if amcharts library url needs to be changed and adds filters
	 *
	 * @return void
	 */
	public function set_amcharts_filters()
	{

		if (isset($this->options['amcharts_url']) && trim($this->options['amcharts_url']) !== '') {
			add_filter('igm_amcharts_version', array($this, 'amcharts_null_version'));
			add_filter('igm_amcharts_lib_url', array($this, 'amcharts_lib_url'));
			add_filter('igm_amcharts_geodata_url', array($this, 'amcharts_geodata_url'));
		}
	}

	/**
	 * returns amcharts url from options
	 *
	 * @param [type] $url
	 * @return void
	 */
	public function amcharts_lib_url($url)
	{
		return rtrim(trim($this->options['amcharts_url']), '/') . '/amcharts4';
	}

	/**
	 * returns amcharts url from options
	 *
	 * @param [type] $url
	 * @return void
	 */
	public function amcharts_geodata_url($url)
	{
		return rtrim(trim($this->options['amcharts_url']), '/') . '/geodata/';
	}

	/**
	 * Returns empty string
	 *
	 * @return string empty string
	 */
	public function amcharts_null_version($version)
	{
		return '';
	}

	/**
	 * Prepare content wrapper for click action
	 *
	 * @param [string] $content
	 * @param [int] $id
	 * @return $content
	 */
	public function prepare_content_wrapper($content, $id)
	{

		// check for placeholders that might have been added in the before/after content
		$search = array(
			'{description}',
			'{styles}',
			'{gutterStyles}',
		);

		$meta    = get_post_meta($id, 'map_info', true);
		$ck_desc = isset($meta['description']) ? do_shortcode($meta['description']) : '';
		$styles  = 'width:30%';
		$gutter_styles = 'width:3%';

		if (!empty($this->options['rightContent'])) {
			$rc = $this->options['rightContent'];
			$wd = isset($rc['gutterWidth']) ? 33 - intval($rc['gutterWidth']) : 33;
			$gw = isset($rc['gutterWidth']) ? intval($rc['gutterWidth']) : 0;

			$styles = sprintf(
				'background:%1$s; padding:%2$s%6$s %3$s%6$s %4$s%6$s %5$s%6$s; width:%7$s;',
				$rc['backgroundColor'],
				$rc['padding']['top'],
				$rc['padding']['right'],
				$rc['padding']['bottom'],
				$rc['padding']['left'],
				$rc['padding']['unit'],
				$wd . '%'
			);

			$gutter_styles = sprintf(
				'width:%1$s;',
				$gw . '%'
			);
		}

		$replace = array(
			$ck_desc,
			$styles,
			$gutter_styles,
		);

		$content = str_replace($search, $replace, $content);

		return $content;
	}

	/**
	 * Check if maps should be public or not
	 *
	 * @param array $model
	 * @return array model
	 */
	public function check_public($model)
	{

		$public = isset($this->options['public']) && isset($this->options['public']['enabled']) ? $this->options['public']['enabled'] : false;

		if ($public) {
			$model['options']['public']             = true;
			$model['options']['publicly_queryable'] = true;
			$model['options']['query_var']          = true;
			$model['options']['rewrite']            = [
				'slug' => !empty($this->options['public']['slug']) ? $this->options['public']['slug'] : 'map',
			];
		}

		add_filter('the_content', array($this, 'single_page_map'));

		return $model;
	}

	public function single_page_map($content)
	{
		if (!is_singular('igmap')) {
			return $content;
		}

		global $post;
		$meta  = get_post_meta($post->ID, 'map_info', true);
		$description = !empty($meta['description']) ? '<p>' . do_shortcode($meta['description']) . '</p>' : '';
		$content    .= $description . do_shortcode("[display-map id='" . $post->ID . "']");

		return $content;
	}

	/**
	 * Prepare meta data to include the proper arguments.
	 *
	 * @param [type] $meta
	 * @return void
	 */
	public function prepare_pro_meta($meta)
	{
		// set overlay
		if (!empty($meta)) {

			$meta['allEntries'] = [];

			// prepare regions source
			$custom_source = isset($meta['regionsDataSource']) ? $meta['regionsDataSource'] : false;
			if ($custom_source && isset($custom_source['enabled']) && '1' === $custom_source['enabled']) {

				if (empty($meta['regions'])) {
					$meta['regions'] = array();
				}

				switch ($custom_source['type']) {
					case 'categories':
						$meta['regions'] = $this->add_taxonomy_regions($meta['regions'], $custom_source, 'categories');
						break;
					case 'tags':
						$meta['regions'] = $this->add_taxonomy_regions($meta['regions'], $custom_source, 'tags');
						break;
					case 'raw_legacy':
						$extra_regions   = $this->convert_legacy_raw_csv($custom_source['raw'], 'regions', $meta['regionDefaults']);
						$meta['regions'] = array_merge($meta['regions'], $extra_regions);
						break;
					case 'json_data':

						// in case we are handling admin preview data, we need to escape slashes
						if (isset($meta['admin']) && $meta['admin'] === '1') {
							$custom_source['json'] = $custom_source['json'] !== '' ? stripslashes($custom_source['json']) : $custom_source['json'];
						}

						$extra_regions   = $this->add_json_object_data('regions', $custom_source, $meta['id']);
						$meta['regions'] = array_merge($meta['regions'], $extra_regions);
						unset($meta['regionsDataSource']['json']);
						break;
					case 'google_spreadsheet':
						$extra_regions   = $this->add_google_spreadsheet_data('regions', $custom_source, $meta['id']);
						$meta['regions'] = array_merge($meta['regions'], $extra_regions);
						break;
					case 'custom_tax':
						$meta['regions'] = $this->add_custom_tax_regions($meta['regions'], $custom_source);
						break;
				}

				// check if we need to process regions convertion
				if (!isset($this->options['dictionary']) || (isset($this->options['dictionary']) && $this->options['dictionary'])) {
					$meta['regions'] = $this->process_regions_dictionary($meta['id'], $meta['regions']);
				}


				// check if we need to process the action content template
				if (isset($custom_source['action_content_template']) && ( '' !== $custom_source['action_content_template'] && '{content}' !== $custom_source['action_content_template'] ) ) {
					$meta['regions'] = $this->process_action_content_template( $meta['regions'], $custom_source['action_content_template'], 'regions' );
				}
			}

			// prepare round markers source
			$custom_markers_source = isset($meta['markersDataSource']) ? $meta['markersDataSource'] : false;
			if ($custom_markers_source && isset($custom_markers_source['enabled']) && '1' === $custom_markers_source['enabled']) {

				if (empty($meta['roundMarkers'])) {
					$meta['roundMarkers'] = array();
				}

				switch ($custom_markers_source['type']) {
					case 'raw_legacy':
						$extra_markers        = $this->convert_legacy_raw_csv($custom_markers_source['raw'], 'markers', $meta['markerDefaults']);
						$meta['roundMarkers'] = array_merge($meta['roundMarkers'], $extra_markers);
						break;

					case 'json_data':

						// in case we are handling admin preview data, we need to escape slashes
						if (isset($meta['admin']) && $meta['admin'] === '1') {
							$custom_markers_source['json'] = $custom_markers_source['json'] !== '' ? stripslashes($custom_markers_source['json']) : $custom_markers_source['json'];
						}

						$extra_markers        = $this->add_json_object_data('roundMarkers', $custom_markers_source, $meta['id']);
						$meta['roundMarkers'] = array_merge($meta['roundMarkers'], $extra_markers);
						unset($meta['markersDataSource']['json']);
						break;
					case 'google_spreadsheet':
						$extra_markers        = $this->add_google_spreadsheet_data('roundMarkers', $custom_markers_source, $meta['id']);
						$meta['roundMarkers'] = array_merge($meta['roundMarkers'], $extra_markers);
						break;
					case 'geolocated_point':
						$extra_markers        = $this->add_geolocated_point('markers', $meta['markerDefaults']);
						$meta['roundMarkers'] = array_merge($meta['roundMarkers'], $extra_markers);
						break;
				}

				// check if we need to process the action content template
				if ( isset( $custom_markers_source['action_content_template'] ) && ( '' !== $custom_markers_source['action_content_template'] && '{content}' !== $custom_markers_source['action_content_template'] ) ) {
					$meta['roundMarkers'] = $this->process_action_content_template( $meta['roundMarkers'], $custom_markers_source['action_content_template'], 'roundMarkers' );
				}
			}

			// prepare icon markers source
			$custom_icon_markers_source = isset($meta['iconMarkersDataSource']) ? $meta['iconMarkersDataSource'] : false;
			if ($custom_icon_markers_source && isset($custom_icon_markers_source['enabled']) && '1' === $custom_icon_markers_source['enabled']) {

				if (empty($meta['iconMarkers'])) {
					$meta['iconMarkers'] = array();
				}

				switch ($custom_icon_markers_source['type']) {
					case 'raw_legacy':
						$extra_markers       = $this->convert_legacy_raw_csv($custom_icon_markers_source['raw'], 'icons', $meta['iconMarkerDefaults']);
						$meta['iconMarkers'] = array_merge($meta['iconMarkers'], $extra_markers);
						unset($meta['iconMarkersDataSource']['raw']);
						break;
					case 'json_data':

						// in case we are handling admin preview data, we need to escape slashes
						if (isset($meta['admin']) && $meta['admin'] === '1') {
							$custom_icon_markers_source['json'] = $custom_icon_markers_source['json'] !== '' ? stripslashes($custom_icon_markers_source['json']) : $custom_icon_markers_source['json'];
						}

						$extra_markers        = $this->add_json_object_data('iconMarkers', $custom_icon_markers_source, $meta['id']);
						$meta['iconMarkers'] = array_merge($meta['iconMarkers'], $extra_markers);
						unset($meta['iconMarkersDataSource']['json']);
						break;
					case 'google_spreadsheet':
						$extra_markers   = $this->add_google_spreadsheet_data('iconMarkers', $custom_source, $meta['id']);
						$meta['iconMarkers'] = array_merge($meta['iconMarkers'], $extra_markers);
						break;
				}
			}

			// prepare icon markers source
			$custom_image_markers_source = isset($meta['imageMarkersDataSource']) ? $meta['imageMarkersDataSource'] : false;
			if ($custom_image_markers_source && isset($custom_image_markers_source['enabled']) && '1' === $custom_image_markers_source['enabled']) {

				if (empty($meta['imageMarkers'])) {
					$meta['imageMarkers'] = array();
				}

				switch ($custom_image_markers_source['type']) {
					case 'raw_legacy':
						$extra_markers       = $this->convert_legacy_raw_csv($custom_image_markers_source['raw'], 'icons', $meta['iconMarkerDefaults']);
						$meta['imageMarkers'] = array_merge($meta['imageMarkers'], $extra_markers);
						unset($meta['imageMarkersDataSource']['raw']);
						break;
					case 'json_data':

						// in case we are handling admin preview data, we need to escape slashes
						if (isset($meta['admin']) && $meta['admin'] === '1') {
							$custom_image_markers_source['json'] = $custom_image_markers_source['json'] !== '' ? stripslashes($custom_image_markers_source['json']) : $custom_image_markers_source['json'];
						}

						$extra_markers        = $this->add_json_object_data('imageMarkers', $custom_image_markers_source, $meta['id']);
						$meta['imageMarkers'] = array_merge($meta['imageMarkers'], $extra_markers);
						unset($meta['imageMarkersDataSource']['json']);
						break;
					case 'google_spreadsheet':
						$extra_markers   = $this->add_google_spreadsheet_data('imageMarkers', $custom_source, $meta['id']);
						$meta['imageMarkers'] = array_merge($meta['imageMarkers'], $extra_markers);
						break;
				}
			}

			// custom legend
			if (isset($meta['customLegend']) && is_array($meta['customLegend']) && isset($meta['customLegend']['enabled']) &&  $meta['customLegend']['enabled'] === '1') {
				if (isset($meta['customLegend']['data']) && !empty($meta['customLegend']['data'])) {
					foreach ($meta['customLegend']['data'] as $key => $entry) {
						$meta['customLegend']['data'][$key]['name'] = html_entity_decode($entry['name']);
					}
				}
			}

			// overlay maps
			if (isset($meta['overlay']) && $meta['overlay'] && is_array($meta['overlay']) && !empty($meta['overlay'])) {

				// create an array with all overlaid maps, including the parent, to avoid infinite loops
				array_push($this->overlay_maps, $meta['id']);
				$this->overlay_maps = array_merge($this->overlay_maps, $meta['overlay']);

				foreach ($meta['overlay'] as $key => $mapid) {

					$overlay_meta = $this->get_meta($mapid);

					if (empty($overlay_meta) || !isset($overlay_meta['overlay'])) {
						continue;
					}

					// check if there aren't duplicated
					if (is_array($overlay_meta['overlay'])) {
						foreach ($overlay_meta['overlay'] as $okey => $omapid) {
							if (in_array($omapid, $this->overlay_maps, true)) {
								unset($overlay_meta['overlay'][$okey]);
							} else {
								array_push($this->overlay_maps, $omapid);
							}
						}
					}

					// disable some stuff on overlay map, to make sure it works well
					if( ! isset( $overlay_meta['externalDropdown'] ) || ! is_array( $overlay_meta['externalDropdown'] )) {
						$overlay_meta['externalDropdown'] = [];
					}
					$overlay_meta['externalDropdown']['enabled'] = 0;

					$overlay_meta                   = $this->prepare_meta($overlay_meta, $mapid);
					$meta['overlay'][$key]          = $overlay_meta;
					$meta['overlay'][$key]['title'] = get_the_title($mapid);

					// add to list of urls to load
					if( isset(  $meta['urls'] ) && is_array( $meta['urls'] ) ){
						array_push($meta['urls'], $overlay_meta['map']);
					}
					
				}
			}

			// process icon css class to add path value
			$meta = $this->add_icon_path_value($meta);

			// prepare tooltips
			$meta = $this->prepare_shortcode_content($meta);

			// if there's a custom action, add it to the footer
			$this->add_custom_actions($meta);

			// custom css
			if (isset($meta['custom_css']) && $meta['custom_css'] !== '') {

				// no need to make the replace here, we do it in Assets
				$this->core->add_extra_styles($meta['custom_css']);
			}

			// custom js
			if (isset($meta['custom_js']) && $meta['custom_js'] !== '') {

				// since some code might be sanitized but we still need these operators in javascript
				$code = $meta['custom_js'];
				$code = str_replace('&gt;','>',$code);
				$code = str_replace('&lt;','<',$code);
				$code = str_replace('&amp;','&',$code);

				$this->core->add_extra_scripts($code);
			}
		}

		return $meta;
	}

	/**
	 * Process the action content template
	 * Finds placeholders and replaces them with the real value
	 *
	 * @param array $data
	 * @param string $template
	 * @return array converted data
	 */
	function process_action_content_template( $data, $template, $type ) {

		// we only need to check if placeholders exist. The actual change is made elsewhere in replace_placeholders of ProActions.php
		preg_match_all('/{(.*?)}/', $template, $matches);
		$placeholders = $matches[1];

		$template = nl2br( $template );

		if( ! empty( $placeholders )  ){
			foreach ($data as $key => $value) {
				$data[$key]['content'] = $template;
			}	
		}

		return $data;
	}

	/**
	 * Prepare tooltips - render shortcodes, nl2br and other formatting
	 *
	 * @param array $meta
	 * @return array $meta
	 */
	private function prepare_shortcode_content($meta)
	{


		// tooltip template fields can also have line breaks
		$meta['regionsTooltipTemplate']      = isset( $meta['regionsTooltipTemplate'] ) ? nl2br( $meta['regionsTooltipTemplate'] ) : '';
		$meta['roundMarkersTooltipTemplate'] = isset( $meta['roundMarkersTooltipTemplate'] ) ? nl2br( $meta['roundMarkersTooltipTemplate'] ) : '';
		$meta['imageMarkersTooltipTemplate'] = isset( $meta['imageMarkersTooltipTemplate'] ) ? nl2br( $meta['imageMarkersTooltipTemplate'] ) : '';
		$meta['iconMarkersTooltipTemplate']  = isset( $meta['iconMarkersTooltipTemplate'] ) ? nl2br( $meta['iconMarkersTooltipTemplate'] ) : '';
		$meta['labelsTooltipTemplate']       = isset( $meta['labelsTooltipTemplate'] ) ? nl2br( $meta['labelsTooltipTemplate'] ) : '';

		// data containers
		$containers = array(
			'regions',
			'roundMarkers',
			'iconMarkers',
			'imageMarkers',
			'labels',
		);

		foreach ($containers as $data_container) {

			if (!empty($meta[$data_container])) {

				foreach ($meta[$data_container] as $k => &$entry) {
					if (isset($entry['tooltipContent']) && $entry['tooltipContent'] !== '') {

						$entry['tooltipContent'] = nl2br($entry['tooltipContent']);

						// render shortcodes in tooltips
						$entry['tooltipContent'] = do_shortcode($entry['tooltipContent']);
					}

					// since we're looping, let's also check the value field
					if (isset($entry['value']) && $entry['value'] !== '') {

						// render shortcodes
						$entry['value'] = do_shortcode($entry['value']);
					}
				}
			}
		}

		return $meta;
	}

	private function add_custom_actions($meta)
	{
		// regions
		if (isset($meta['regionDefaults']) && isset($meta['regionDefaults']['action']) && strpos($meta['regionDefaults']['action'], 'custom') && $meta['regionDefaults']['customAction'] !== '') {
			$this->add_custom_js($meta['regionDefaults']['action'], $meta['id'], $meta['regionDefaults']['customAction']);
		}

		// roundMarkers
		if (isset($meta['markerDefaults']) && isset($meta['markerDefaults']['action']) && strpos($meta['markerDefaults']['action'], 'custom') && $meta['markerDefaults']['customAction'] !== '') {
			$this->add_custom_js($meta['markerDefaults']['action'], $meta['id'], $meta['markerDefaults']['customAction']);
		}

		// roundMarkers
		if (isset($meta['imageMarkerDefaults']) && isset($meta['imageMarkerDefaults']['action']) && strpos($meta['imageMarkerDefaults']['action'], 'custom') && $meta['imageMarkerDefaults']['customAction'] !== '') {
			$this->add_custom_js($meta['imageMarkerDefaults']['action'], $meta['id'], $meta['imageMarkerDefaults']['customAction']);
		}

		// icon markers
		if (isset($meta['iconMarkerDefaults']['action']) && isset($meta['iconMarkerDefaults']['action']) && strpos($meta['iconMarkerDefaults']['action'], 'custom') && $meta['iconMarkerDefaults']['customAction'] !== '') {
			$this->add_custom_js($meta['iconMarkerDefaults']['action'], $meta['id'], $meta['iconMarkerDefaults']['customAction']);
		}
	}

	private function add_custom_js($prefix, $id, $code)
	{

		$custom_action = sprintf(
			' function %1$s_%2$s(data) {
			%3$s
		} ',
			$prefix,
			$id,
			$code
		);

		$this->core->add_extra_scripts($custom_action);
	}

	private function add_custom_tax_regions($regions, $options)
	{


		if (isset($options['custom_tax']) && $options['custom_tax'] !== '') {

			$is_json = json_decode($options['custom_tax'], true);
			if ($is_json !== null) {
				$tax = get_terms($is_json);
			} else {

				$tax = sanitize_text_field($options['custom_tax']);

				$args = array(
					'orderby' => 'name',
					'order'   => 'ASC',
				);

				if ('1' === $options['categoriesIncludeEmpty']) {
					$args['hide_empty'] = false;
				}

				$tax = get_terms($tax, $args);
			}

			if ($tax) {
				foreach ($tax as $term) {

					if (!is_object($term) || !property_exists($term, 'name')) {
						continue;
					}

					$cat_region = [
						'id'             => $term->name,
						'name'           => $term->name,
						'post_count'     => $term->count,
						'description'    => do_shortcode($term->description),
						'content'        => get_category_link($term->term_id),
						'tooltipContent' => $term->description !== '' ? $term->description : $term->name,
						'useDefaults'    => '1',
						'term_id'        => $term->term_id,
					];

					array_push($regions, $cat_region);
				}
			}
		}

		return $regions;
	}

	private function add_taxonomy_regions($regions, $options, $type = 'categories')
	{

		$args = array(
			'orderby' => 'name',
			'order'   => 'ASC',
		);

		if ($type === 'categories') {
			if ('1' === $options['categoriesIncludeEmpty']) {
				$args['hide_empty'] = 0;
			}
			$categories = get_categories($args);
		} else {
			if ('1' === $options['tagsIncludeEmpty']) {
				$args['hide_empty'] = 0;
			}
			$categories = get_tags($args);
		}

		foreach ($categories as $category) {

			$cat_region = [
				'id'             => $category->name,
				'name'           => $category->name,
				'post_count'     => $category->count,
				'description'    => $category->description,
				'content'        => get_category_link($category->term_id),
				'tooltipContent' => $category->description !== '' ? $category->description : $category->name,
				'useDefaults'    => '1',
			];

			array_push($regions, $cat_region);
		}

		return $regions;
	}

	private function add_icon_path_value($meta)
	{

		if (! empty($meta['iconMarkers'] ) && ! empty($meta['iconMarkerDefaults'] ) ) {
			$icon = new ProIcons($this->core);

			foreach ($meta['iconMarkers'] as &$val) {
				if (!empty($val['icon'])) {
					$val['path'] = $icon->get_path($val['icon']);
				}
			}

			
			$meta['iconMarkerDefaults']['path'] = $icon->get_path($meta['iconMarkerDefaults']['icon']);
		
		}

		return $meta;
	}

	/**
	 * Get a value from an object based on the json path
	 *
	 * @param string $path
	 * @param array  $json_obj
	 * @return mixed
	 */
	private function assign_json_value_from_path($path, $json_obj)
	{
		$value = '';
		if (strpos($path, '.') !== false) {
			$json_path = explode('.', $path);
			$temp_id   = $json_obj;
			foreach ($json_path as $key) {
				if (isset($temp_id[$key])) {
					$temp_id = $temp_id[$key];
				} else {
					$temp_id = '';
				}
			}
			$value = $temp_id;
		} else {
			if (array_key_exists($path, $json_obj)) {
				$value = $json_obj[$path];
			}
		}

		// we exclude content, because it can be empty.
		if (empty($value) &&  $path !== 'content') {
			$value = '0';
		}

		return $value;
	}

	/**
	 * Get json object to populate regions
	 *
	 * @param string $json - raw json code
	 * @param string $json_id - string identifying property to use as id
	 * @param string $id - map id - to store cache if needed
	 * @return array json
	 */
	private function add_json_object_data($type, $json_info, $map_id)
	{

		$json = isset($json_info['json']) ? $json_info['json'] : '';

		if ('' === $json) {
			return array();
		}

		// get json from URL
		if (substr($json, 0, 4) === 'http') {
			$json = $this->get_json_url_data($json, $map_id, $type);
		}

		$json = json_decode($json, true);

		if ($json === false || !is_array($json)) {
			return array();
		}

		// now we start to handle the data
		// check for a different data source
		if (isset($json_info['json_data_source']) && trim($json_info['json_data_source']) !== '') {
			$t_json = $this->assign_json_value_from_path($json_info['json_data_source'], $json);
			if ($t_json === false || !is_array($t_json)) {
				return array();
			}

			$json = $t_json;
		}

		$json_id = isset($json_info['json_id']) ? $json_info['json_id'] : 'id';

		foreach ($json as &$json_obj) {

			if (!is_array($json_obj)) {
				continue;
			}

			if ($json_obj && is_array($json_id)) {
				$json_obj = array_map('do_shortcode', $json_obj);
			}

			// if id is different
			if ($json_id !== 'id') {
				$json_obj['id'] = $this->assign_json_value_from_path($json_id, $json_obj);
			}

			// note: let's not add the name, it will inherit form geojson file

			if (!array_key_exists('tooltipContent', $json_obj)) {
				$json_obj['tooltipContent'] = $json_obj['id'];
			}

			if (!array_key_exists('useDefaults', $json_obj)) {
				$json_obj['useDefaults'] = '1';
			}

			// if we're converting markers
			if ('roundMarkers' === $type || 'iconMarkers' === $type || 'imageMarkers' === $type) {

				$lat = isset($json_info['json_lat']) ? $json_info['json_lat'] : 'latitude';
				$lon = isset($json_info['json_lon']) ? $json_info['json_lon'] : 'longitude';

				if ('latitude' !== $lat) {
					$json_obj['latitude'] = $this->assign_json_value_from_path($lat, $json_obj);
				}

				if ('longitude' !== $lon) {
					$json_obj['longitude'] = $this->assign_json_value_from_path($lon, $json_obj);
				}

				$json_obj['latitude']  = floatval($json_obj['latitude']);
				$json_obj['longitude'] = floatval($json_obj['longitude']);
			}

			$content = isset($json_info['json_action_content']) ? $json_info['json_action_content'] : '';
			if ('' !== $content) {
				$json_obj['content'] = $this->assign_json_value_from_path($content, $json_obj);
				$json_obj['content'] = is_string($json_obj['content']) ? nl2br($json_obj['content']) : $json_obj['content'];
			}

			// for image markers
			if( 'imageMarkers' === $type ){
				$image = isset($json_info['json_image']) ? $json_info['json_image'] : '';
				if ('' !== $image) {
					$tempImage = $this->assign_json_value_from_path($image, $json_obj);
					if( !empty( $tempImage ) ){
						$json_obj['href'] = $tempImage;
						$json_obj['useDefaults'] = 0;
					}
				}
			}
		}

		return $json;
	}

	/**
	 * Get json from google spreadsheet
	 *
	 * @param string $type - regions or markers
	 * @param string $json_info - array with options information
	 * @param string $id - map id - to store cache if needed
	 * @return array json
	 */
	private function add_google_spreadsheet_data($type, $json_info, $map_id)
	{

		$valid = !empty($json_info['google_api_key']) && !empty($json_info['google_sheet_id']) ? true : false;
		$debug = isset($_GET['debug']) ? true : false;

		if (!$valid) {
			return array();
		}

		$url = sprintf(
			'https://sheets.googleapis.com/v4/spreadsheets/%1$s/values/%2$s?key=%3$s&valueRenderOption=FORMATTED_VALUE',
			$json_info['google_sheet_id'],
			$json_info['google_sheet_range'],
			$json_info['google_api_key']
		);

		// get json from URL
		$json = $this->get_json_url_data($url, $map_id, $type);
		$json = json_decode($json, true);

		if ($json === false || !is_array($json) || !isset($json['values'])) {
			return array();
		}

		$json_id      = isset($json_info['google_data_id']) ? $json_info['google_data_id'] : 'id';
		$json_content = isset($json_info['google_data_content']) ? $json_info['google_data_content'] : $json_id;
		$is_numeric   = is_numeric($json_id);

		if ($is_numeric) {
			$json_id      = intval($json_id);
			$json_content = $json_content !== '' ? intval($json_content) : false;
		}

		$json    = $json['values'];
		$headers = !$is_numeric ? array_map('trim', array_shift($json)) : false;

		$new_json = [];
		$debugloop = true;

		foreach ($json as $objkey => $json_obj) {

			$obj = [];

			if (!$is_numeric && count($headers) === count($json_obj)) {

				$obj     = array_combine($headers, $json_obj);

			} else if ($is_numeric) {
				$obj = $json_obj;

			} else {

				if ($debug && $debugloop) {
					echo '<pre>Header count is not equal to values count - array_combine error, trying to loop them.</pre>';
					$debugloop = false;
				}

				foreach ($headers as $hkey => $hvalue) {
					$obj[$hvalue] = isset($json_obj[$hkey]) ? $json_obj[$hkey] : '';
				}
			}


			$obj['id']      = isset($obj[$json_id]) ? $obj[$json_id] : false;

			$obj['content'] = $json_content && isset($obj[$json_content]) ? $obj[$json_content] : '';

			$obj['content'] = is_string($obj['content']) ? nl2br($obj['content']) : $obj['content'];

			$obj = array_map('do_shortcode', $obj);

			// note: let's not add the name, it will inherit form geojson file
			if ( ! array_key_exists('tooltipContent', $obj)) {
				$obj['tooltipContent'] = $obj['id'];
			}

			if (!array_key_exists('useDefaults', $obj)) {
				$obj['useDefaults'] = '1';
			}

			// if we're converting markers
			if ('roundMarkers' === $type || 'iconMarkers' === $type || 'imageMarkers' === $type) {

				$lat = isset($json_info['google_data_latitude']) ? $json_info['google_data_latitude'] : 'latitude';
				$lon = isset($json_info['google_data_longitude']) ? $json_info['google_data_longitude'] : 'longitude';

				if ('latitude' !== $lat) {
					$obj['latitude'] = isset($obj[$lat]) ? $obj[$lat] : '';
				}

				if ('longitude' !== $lon) {
					$obj['longitude'] = isset($obj[$lon]) ? $obj[$lon] : '';
				}

				if (empty($obj['latitude']) || empty($obj['longitude'])) {
					continue;
				}

				

				$obj['latitude']  = floatval( str_replace(',', '.', $obj['latitude'] ) );
				$obj['longitude'] = floatval( str_replace(',', '.', $obj['longitude'] ) );
			}

			// if we don't have an id at this point, create a unique one for each entry
			if (empty($obj['id'])) {
				$obj['id'] = $objkey + 1;
			}


			array_push($new_json, $obj);
		}
		if ($debug) {
			echo ' JSON <br>';
			echo '<pre>' . json_encode($new_json) . '</pre>';
		}

		return $new_json;
	}

	/**
	 * Get JSON content from external website
	 *
	 * @param string $url
	 * @param string $id - map id to use to cache results
	 * @return void
	 */
	private function get_json_url_data($url, $map_id, $type = 'regions')
	{

		$options    = get_option('interactive-maps');
		$cache_time = isset($options['cache_time']) ? intval($options['cache_time']) : 30;
		$cache_key  = sprintf('_map_%1$s_json_url', $type);
		$cache_rkey = sprintf('_map_%1$s_raw_json', $type);
		$cache      = get_post_meta($map_id, $cache_key, true);
		$raw_cached = get_post_meta($map_id, $cache_rkey, true);
		$message    = $type . ' - ';
		$debug      = isset($_GET['debug']) ? true : false;
		$url        = apply_filters( 'igm_json_data_url', $url );
		$url_args   = apply_filters( 'igm_json_data_url_args', array() );

		if (
			!is_array($cache) ||
			empty($cache) ||
			empty($raw_cached) ||
			$cache['expires'] < time() ||
			$cache_time === 0 ||
			(isset($cache['url']) && $cache['url'] !== $url)
		) {

			$request = wp_remote_get( $url, $url_args );

			if (is_wp_error($request)) {
				// if there's an error, keep the previous cached version
				$data = !empty($raw_cached) ? $raw_cached : '';
				error_log($request->get_error_message());
				$message .= 'Error fetching the file';
			} else {
				$result  = json_decode($request['body']);
				if (json_last_error() !== JSON_ERROR_NONE) {
					// if there's an error, keep the previous cached version
					$data = !empty($raw_cached) ? $raw_cached : '';
					$message .= 'Sucessfully fetched file, but invalid JSON - returning cached';
					if ($debug) {
						echo '<pre>' . $message . '</pre>';
						echo '<pre>' . json_last_error_msg() . '</pre>';
						echo '<pre>' . $request['body'] . '</pre>';
					}
					return $data;
				} else {
					$message .= 'Sucessfully fetched JSON';
					$data = $request['body'];
				}
			}

			// add slashes to remove them again to make sure we escape quotes inside a property
			// this was related with an issue of having html inside a property and breaking when unserializing
			// that's why we save it in a different meta entry
			$slashed_data = addslashes($data);

			$cache = array(
				// 30m cached
				'url'     => $url,
				'expires' => time() + 60 * $cache_time,
			);
			// the raw data, to avoid serialization issues
			update_post_meta($map_id, $cache_rkey, $slashed_data);

			// some extra info
			update_post_meta($map_id, $cache_key, $cache);

			if ($debug) {
				echo '<pre>' . $message . '</pre>';
				echo ' JSON<br>';
				echo '<pre>' . $data . '</pre>';
			}

			return $data; // uncached json
		}

		if ($debug) {
			$message .= 'Cached Result until ' . date('m/d/Y H:i:s', $cache['expires']);
			echo '<pre>' . $message . '</pre>';
			echo ' Cached JSON <br>';
			echo '<pre>' . $raw_cached . '</pre>';
		}



		return $raw_cached; // cached result
	}

	private function add_geolocated_point($type, $defaults)
	{

		$collection = [];

		if ($type === 'markers') {

			$entry['id']        = trim($data[1]);
			$entry['name']      = trim($data[1]);
			$coordinates        = explode(' ', $data[0]);
			$entry['latitude']  = isset($coordinates[0]) ? floatval($coordinates[0]) : '';
			$entry['longitude'] = isset($coordinates[1]) ? floatval($coordinates[1]) : '';
		}

		return $collection;
	}

	private function convert_legacy_raw_csv($raw, $type = 'regions', $defaults = [])
	{

		if ('' === $raw) {
			return array();
		}

		$raw        = trim($raw);
		$entries    = explode(';', $raw);
		$collection = array();

		foreach ($entries as $entry) {

			if (trim($entry) === '') {
				continue;
			}

			$data = explode(',', $entry);

			if (count($data) != 5) {
				continue;
			}

			$data = array_map(
				function ($value) {

					$search  = ['&#59', '&#44', '&{#59}', '&{#44}'];
					$replace = [';', ',', ';', ','];
					$value   = str_replace($search, $replace, $value);

					return trim($value);
				},
				$data
			);

			$entry = [
				'id'             => trim($data[0]),
				'name'           => trim($data[1]),
				'tooltipContent' => trim($data[2]),
				'content'        => trim($data[3]),
				'fill'           => trim($data[4]),
				'hover'          => isset($defaults['hover']) ? $defaults['hover'] : trim($data[4]),
				'action'         => 'default',
				'useDefaults'    => '',
				'value'          => intval(trim($data[3])),
			];

			if ($type === 'markers') {

				$entry['id']        = trim($data[1]);
				$entry['name']      = trim($data[1]);
				$coordinates        = explode(' ', $data[0]);
				$entry['latitude']  = isset($coordinates[0]) ? floatval($coordinates[0]) : '';
				$entry['longitude'] = isset($coordinates[1]) ? floatval($coordinates[1]) : '';
			}

			if ($type === 'icons') {

				$entry['id']          = trim($data[1]);
				$entry['name']        = trim($data[1]);
				$entry['useDefaults'] = '1';
				$coordinates          = explode(' ', $data[0]);
				$entry['latitude']    = isset($coordinates[0]) ? floatval($coordinates[0]) : '';
				$entry['longitude']   = isset($coordinates[1]) ? floatval($coordinates[1]) : '';
			}

			$collection[] = $entry;
		}

		return $collection;
	}
}

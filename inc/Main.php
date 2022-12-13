<?php

namespace FSE_Hybrid_POC;

class Main {
	const MAIN_FLAG = 'fse_hybrid_poc_enabled';
	/**
	 * Templates.
	 *
	 * @var array
	 */
	private $templates = [];

	/**
	 * Customizer Section.
	 *
	 * @var string
	 */
	private $customize_section = 'fse_hybrid_fse_section';

	public function __construct() {
		$this->templates = [
			'index'      => __( 'Index', 'fse-hybrid-poc' ),
			'front-page' => __( 'Front Page', 'fse-hybrid-poc' ),
			'archive'    => __( 'Archive', 'fse-hybrid-poc' ),
			'404'        => __( '404', 'fse-hybrid-poc' ),
			'search'     => __( 'Search', 'fse-hybrid-poc' ),
			'page'       => __( 'Page', 'fse-hybrid-poc' ),
			'single'     => __( 'Single', 'fse-hybrid-poc' ),
		];

		$this->init();
	}

	/**
	 * Init hooks.
	 */
	public function init() {
		// Adds the customizer controls.
		add_action( 'customize_register', [ $this, 'add_controls' ] );
		// Filter the templates to load the selected ones from the customizer.
		add_filter( 'get_block_templates', [ $this, 'filter_templates' ], 10, 3 );
		// Change the theme file path that decides if this theme is FSE compatible.
		add_filter( 'theme_file_path', [ $this, 'fix_file_path' ], 10, 2 );

		if ( $this->should_load() ) {
			// Add the theme support.
			add_action( 'after_setup_theme', [ $this, 'add_theme_support' ] );
		}
	}

	/**
	 * Add customizer controls.
	 *
	 * @param \WP_Customize_Manager $wp_customize the customizer manager.
	 *
	 * @return void
	 */
	public function add_controls( $wp_customize ) {
		// Add section.
		$wp_customize->add_section(
			$this->customize_section,
			array(
				'title'    => __( 'Full Site Editing', 'fse-hybrid-poc' ),
				'priority' => 10,
			)
		);

		$wp_customize->add_setting(
			self::MAIN_FLAG,
			array(
				'default'           => false,
				'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
			)
		);

		// Add main control.
		$wp_customize->add_control(
			self::MAIN_FLAG,
			array(
				'label'       => __( 'Enable FSE Templates', 'fse-hybrid-poc' ),
				'description' => __( 'Enable this to use the FSE templates instead of the default ones.', 'fse-hybrid-poc' ),
				'section'     => $this->customize_section,
				'type'        => 'checkbox',
			)
		);

		$priority = 10;

		// Add controls for each template.
		foreach ( $this->templates as $slug => $label ) {
			$wp_customize->add_setting(
				$this->get_option_slug_for_template( $slug ),
				array(
					'default'           => false,
					'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
				)
			);

			$wp_customize->add_control(
				$this->get_option_slug_for_template( $slug ),
				array(
					'label'           => sprintf( __( 'Enable %s', 'fse-hybrid-poc' ), $label ),
					'active_callback' => [$this, 'is_enabled'],
					'section'         => $this->customize_section,
					'priority'        => $priority,
					'type'            => 'checkbox',
				)
			);

			$priority += 10;
		}
	}


	/**
	 * Filters the array of queried block templates array after they've been fetched.
	 * We need to filter the templates, so we remove the ones that aren't enabled inside the customizer.
	 *
	 * @param \WP_Block_Template[] $query_result Array of found block templates.
	 * @param array $query Arguments to retrieve templates.
	 * @param string $template_type wp_template or wp_template_part.
	 */
	public function filter_templates( $query_result, $query, $template_type ) {
		if ( $template_type !== 'wp_template' ) {
			return $query_result;
		}

		if ( ! $this->is_enabled() ) {
			return [];
		}

		foreach ( $query_result as $key => $template ) {
			$enabled = $this->is_template_enabled( $template->slug );

			if ( $enabled ) {
				continue;
			}

			unset( $query_result[ $key ] );
		}

		return $query_result;
	}


	/**
	 * Adjusts the file path.
	 * This is needed because the theme is FSE compatible only when the FSE toggle is enabled in customizer.
	 *
	 * @param string $path The file path.
	 * @param string $file The file relative to the root of the theme.
	 *
	 * @return string
	 */
	public function fix_file_path( $path, $file ) {
		if( $this->is_enabled() ) {
			return $path;
		}

		// Not enabled. We need to disable the fse features by returning a non existing file.
		if ( $file === 'templates/index.html' ) {
			return get_template_directory() . '/templates/non-existent-file.html';
		}

		return $path;
	}

	/**
	 * Check if a specific template is enabled.
	 *
	 * @param string $template the template slug.
	 *
	 * @return bool
	 */
	private function is_template_enabled( $template ) {
		return get_theme_mod( $this->get_option_slug_for_template( $template ), false );
	}

	/**
	 * Get the option ID for a template.
	 *
	 * @param string $template the template slug.
	 *
	 * @return string
	 */
	private function get_option_slug_for_template( $template ) {
		return 'fse_hybrid_poc_enable_' . $template;
	}

	/**
	 *  Check if templates should be loaded.
	 *
	 * @return bool
	 */
	public function should_load() {
		if ( ! $this->is_enabled() ) {

			return false;
		}

		$status = array_map(
			function ( $template ) {
				return $this->is_template_enabled( $template );
			},
			array_keys( $this->templates )
		);

		if ( ! in_array( true, $status, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the main toggle in the customizer is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) get_theme_mod( self::MAIN_FLAG, false );
	}


	/**
	 * Add the block templates theme support.
	 *
	 * This theme support isn't even needed because of the way we filter the file path once the main toggle is enabled.
	 *
	 * @return void
	 */
	public function add_theme_support() {
		add_theme_support( 'block-templates' );
	}

	/**
	 * Sanitize checkbox value
	 *
	 * @param bool $value incoming value from checkbox control.
	 *
	 * @return int
	 */
	public function sanitize_checkbox( $value ) {
		return (bool) $value;
	}
}
<?php
/**
 * Plugin Class
 *
 * @package Customize_Posts_CSS
 */

namespace Customize_Posts_CSS;

/**
 * Class Plugin
 */
class Plugin {

	const THEME_SUPPORT = 'custom_css';

	const META_KEY = 'custom_css';

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Plugin constructor.
	 *
	 * @access public
	 */
	public function __construct() {

		// Parse plugin version.
		if ( preg_match( '/Version:\s*(\S+)/', file_get_contents( dirname( __FILE__ ) . '/../customize-posts-css.php' ), $matches ) ) {
			$this->version = $matches[1];
		}
	}

	/**
	 * Initialize.
	 */
	function init() {
		add_action( 'wp_default_scripts', array( $this, 'register_scripts' ), 100 );
		add_action( 'init', array( $this, 'add_post_type_support' ) );
		add_action( 'wp_head', array( $this, 'print_post_custom_css' ), 102 );
		add_action( 'customize_posts_register_meta', array( $this, 'register_post_meta' ) );
		add_action( 'customize_dynamic_setting_args', array( $this, 'filter_customize_dynamic_setting_args' ), 20, 2 );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'customize_controls_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_preview_scripts' ) );
		add_action( 'wp_head', array( $this, 'print_queried_posts_custom_css' ), 102 ); // Because theme's Custom CSS is output at 101.
	}

	/**
	 * Register scripts.
	 *
	 * @param \WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( \WP_Scripts $wp_scripts ) {
		$handle = 'customize-posts-css-controls';
		$src = plugins_url( 'js/controls.js', dirname( __FILE__ ) );
		$deps = array( 'jquery', 'customize-controls' );
		$in_footer = true;
		$wp_scripts->add( $handle, $src, $deps, $this->version, $in_footer );

		$handle = 'customize-posts-css-preview';
		$src = plugins_url( 'js/preview.js', dirname( __FILE__ ) );
		$deps = array( 'jquery', 'customize-preview' );
		$in_footer = true;
		$wp_scripts->add( $handle, $src, $deps, $this->version, $in_footer );
	}

	/**
	 * Add post type support by default for all public post types.
	 */
	public function add_post_type_support() {
		$post_types = get_post_types( array_fill_keys( array( 'public' ), true ), 'names' );
		foreach ( $post_types as $post_type ) {
			\add_post_type_support( $post_type, static::THEME_SUPPORT );
		}
	}

	/**
	 * Enqueue controls scripts.
	 */
	public function customize_controls_enqueue_scripts() {
		$handle = 'customize-posts-css-controls';
		wp_enqueue_script( $handle );
		$settings = wp_enqueue_code_editor( array(
			'type' => 'text/css',
			'codemirror' => array(
				'indentUnit' => 2,
				'tabSize' => 2,
			),
		) );
		wp_add_inline_script( $handle, sprintf(
			'CustomizePostsCSS.init( wp.customize, %s );',
			wp_json_encode( array(
				'editorSettings' => $settings,
				'themeSupport' => static::THEME_SUPPORT,
				'metaKey' => static::META_KEY,
				'l10n' => array(
					'control_label' => __( 'Custom CSS', 'default' ),
				),
			) )
		) );
	}

	/**
	 * Enqueue scripts for preview.
	 */
	public function enqueue_preview_scripts() {
		if ( ! is_customize_preview() ) {
			return;
		}
		$handle = 'customize-posts-css-preview';
		wp_enqueue_script( $handle );

		wp_add_inline_script( $handle, sprintf(
			'CustomizePostsCSSPreview.init( wp.customize, %s );',
			wp_json_encode( array(
				'metaKey' => static::META_KEY,
			) )
		) );
	}

	/**
	 * Register Customize Posts Meta.
	 *
	 * @param \WP_Customize_Posts $posts_component Posts Component.
	 */
	function register_post_meta( \WP_Customize_Posts $posts_component ) {
		$post_types = get_post_types_by_support( static::THEME_SUPPORT );
		foreach ( $post_types as $post_type ) {
			$posts_component->register_post_type_meta( $post_type, static::META_KEY, array(
				'default' => '', // Default will be set dynamically in filter_customize_dynamic_setting_args().
				'transport' => 'postMessage',
				'capability' => 'edit_css',
				'validate_callback' => array( $this, 'validate_css' ),
			) );
		}
	}

	/**
	 * Dynamically supply default values for settings.
	 *
	 * @param array  $setting_args Setting args.
	 * @param string $setting_id   Setting ID.
	 * @return array|false Setting args or false if not recognized.
	 */
	function filter_customize_dynamic_setting_args( $setting_args, $setting_id ) {
		if ( false === $setting_args || ! isset( $setting_args['type'] ) || 'postmeta' !== $setting_args['type'] ) {
			return $setting_args;
		}

		$id_parts = array_slice( explode( '[', str_replace( ']', '', $setting_id ) ), 1 );
		$post_type = array_shift( $id_parts );
		$post_id = array_shift( $id_parts );
		$meta_key = array_shift( $id_parts );
		if ( static::META_KEY === $meta_key && empty( $setting_args['default'] ) ) {
			$setting_args['default'] = sprintf( ".hentry.post-%d {\n\t/* %s */\n}\n\n", $post_id, __( 'CSS for post_class()', 'customize-posts-css' ) );
			if ( 'page' === $post_type ) {
				$body_selector = sprintf( 'body.page-id-%d', $post_id );
			} else {
				$body_selector = sprintf( 'body.postid-%d', $post_id );
			}
			$setting_args['default'] .= sprintf( "$body_selector {\n\t/* %s */\n}\n", __( 'CSS for body_class()', 'customize-posts-css' ) );
		}
		return $setting_args;
	}

	/**
	 * Print CSS for all queried posts in the head.
	 *
	 * Then print CSS for any post added thereafter in sub-queries when 'the_post' action happens.
	 *
	 * @global \WP_Query $wp_the_query
	 */
	public function print_queried_posts_custom_css() {
		global $wp_the_query;
		if ( $wp_the_query instanceof \WP_Query ) {
			foreach ( $wp_the_query->posts as $post ) {
				$this->print_post_custom_css( $post );
			}
		}

		add_action( 'the_post', array( $this, 'print_post_custom_css' ) );
	}

	/**
	 * Keep track of the styles that have already been printed.
	 *
	 * @var array
	 */
	protected $already_printed = array();

	/**
	 * Print Post Custom CSS.
	 *
	 * @param \WP_Post|int $post Post.
	 */
	function print_post_custom_css( $post ) {
		$post = get_post( $post );

		if ( isset( $this->already_printed[ $post->ID ] ) ) {
			return;
		}

		$custom_css = get_post_meta( $post->ID, static::META_KEY, true );
		if ( $custom_css ) { // Note this doesn't need is_customize_preview() because we can dynamically add style tags as they are encountered.
			printf( "<style type='text/css' class='post-custom-css-%d'>\n", $post->ID );
			echo strip_tags( $custom_css ); // Note that esc_html() cannot be used because `div &gt; span` is not interpreted properly.
			echo "</style>\n";

			$this->already_printed[ $post->ID ] = true;
		}
	}

	/**
	 * Validate CSS.
	 *
	 * @see \WP_Customize_Custom_CSS_Setting::validate()
	 * @param \WP_Error $validity Validity.
	 * @param string    $css CSS.
	 * @return \WP_Error Validity state.
	 */
	function validate_css( $validity, $css ) {
		if ( preg_match( '#</?\w+#', $css ) ) {
			$validity->add( 'illegal_markup', __( 'Markup is not allowed in CSS.', 'default' ) );
		}
		return $validity;
	}
}

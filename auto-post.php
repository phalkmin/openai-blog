<?php
/**
 * Plugin Name: WP-AutoInsight
 * Description: Create blog posts automatically using the OpenAI and Gemini APIs!
 * Version: 1.9
 * Author: Paulo H. Alkmin
 * Author URI: https://phalkmin.me/
 * Text Domain: automated-wordpress-content-creator
 * Domain Path: /languages
 *
 * @package WP-AutoInsight
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'VERSION', filemtime( plugin_dir_path( '/auto-post.php', __FILE__ ) ) );


require_once __DIR__ . '/vendor/autoload.php';
require plugin_dir_path( __FILE__ ) . 'admin.php';
require plugin_dir_path( __FILE__ ) . 'gpt.php';

/**
 * Handle API request errors.
 *
 * @param mixed  $response The API response.
 * @param string $api      The API name.
 */
function handle_api_request_error( $response, $api ) {
	if ( ! ( $response instanceof WP_Error ) && ! empty( $response ) ) {
		$response = new WP_Error( 'api_error', $response );
	}

	// Error logging.
	$error_message = 'Unknown error occurred.';
	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
	}

	error_log( sprintf( '%s API Request Error: %s', $api, $error_message ) );

	add_settings_error(
		'openai-settings',
		'api-request-error',
		// Translators: %1$s is the API name, %2$s is the error message.
		sprintf( esc_html__( 'Error in %1$s API request: %2$s', 'automated-blog-content-creator' ), $api, $error_message ),
		'error'
	);
}


/**
 * The function `abcc_enqueue_scripts` is used to enqueue CSS and JavaScript files, including Select2
 * library, for the WordPress admin area.
 */
function abcc_enqueue_scripts() {
	wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
	wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', 'jquery', '4.1.0-rc.0', true );
	wp_enqueue_style( 'abcc-admin-style', plugins_url( '/css/admin-style.css', __FILE__ ), array(), VERSION, true );
	wp_enqueue_script( 'abcc-admin-script', plugins_url( '/js/admin-script.js', __FILE__ ), array( 'jquery' ), VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'abcc_enqueue_scripts' );


function abcc_check_api_key() {
	$prompt_select = get_option( 'prompt_select' );

	if ( ( 'openai' === $prompt_select ) || ( 'gpt-4' === $prompt_select ) || ( 'gpt-4o' === $prompt_select ) ) {
		if ( defined( 'OPENAI_API' ) ) {
			$api_key = OPENAI_API;
		} else {
			$openai_api_key = get_option( 'openai_api_key', '' );
			if ( ! empty( $openai_api_key ) ) {
				$api_key = $openai_api_key;
			} else {
				$api_key = '';
			}
		}
	} elseif ( 'gemini' === $prompt_select ) {
		if ( defined( 'GEMINI_API' ) ) {
			$api_key = GEMINI_API;
		} else {
			$gemini_api_key = get_option( 'gemini_api_key', '' );
			if ( ! empty( $gemini_api_key ) ) {
				$api_key = $gemini_api_key;
			} else {
				$api_key = '';
			}
		}
	} else {
		$api_key = '';
	}

	return $api_key;
}

/**
 * The function abcc_create_block creates a WordPress block with specified attributes and content.
 *
 * @param string $block_name The `block_name` parameter in the `abcc_create_block` function is used to specify.
 * the name of the block that you want to create. This could be the name of a custom block that you
 * have registered in WordPress or a core block like 'paragraph', 'heading', 'image', etc
 * @param array  $attributes The `abcc_create_block` function you provided is used to create a block in
 *  WordPress with the specified block name, attributes, and content. Here's an explanation of the
 *  parameters:
 * @param string $content The `abcc_create_block` function you provided is used to create a block of content
 * for the WordPress block editor. The function takes three parameters:
 *
 * @return $block_content - The function `abcc_create_block` returns a block of content in the format used by the
 * WordPress block editor (Gutenberg). The content includes the block name, attributes encoded as a
 * JSON string, and the block content itself enclosed in HTML comments.
 */
function abcc_create_block( $block_name, $attributes = array(), $content = '' ) {
	$attributes_string = wp_json_encode( $attributes );
	$block_content     = '<!-- wp:' . esc_attr( $block_name ) . ' ' . $attributes_string . ' -->' . wp_kses_post( $content ) . '<!-- /wp:' . esc_attr( $block_name ) . ' -->';
	return $block_content;
}


/**
 * The function `abcc_create_blocks` creates an array of paragraph blocks with specified attributes
 * from a given array of text items.
 *
 * @param array $text_array The `abcc_create_blocks` function takes an array of text items as input and
 * creates an array of blocks based on each text item. Each block has a predefined structure with a
 * name, attributes, and content.
 *
 * @return $blocks The function `abcc_create_blocks` takes an array of text items as input, creates blocks for
 * each non-empty item, and returns an array of blocks. Each block has a name of 'paragraph',
 * attributes including alignment and a custom attribute with a value, and the content sanitized using
 * `wp_kses_post`.
 */
function abcc_create_blocks( $text_array ) {
	$blocks = array();
	foreach ( $text_array as $item ) {
		if ( ! empty( $item ) ) {
			$block    = array(
				'name'       => 'paragraph',
				'attributes' => array(
					'align'            => 'left',
					'custom_attribute' => 'value',
				),
				'content'    => wp_kses_post( $item ),
			);
			$blocks[] = $block;
		}
	}
	return $blocks;
}



/**
 * The function abcc_gutenberg_blocks processes an array of blocks to create block contents in PHP.
 *
 * @param array $blocks The `abcc_gutenberg_blocks` function takes an array of blocks as a parameter. Each
 * block in the array should have the following structure:
 *
 * @return $block_contents The function `abcc_gutenberg_blocks` is returning the concatenated block contents generated
 * by calling the `abcc_create_block` function for each block in the input array ``.
 */
function abcc_gutenberg_blocks( $blocks = array() ) {
	$block_contents = '';
	foreach ( $blocks as $block ) {
		$block_contents .= abcc_create_block( $block['name'], $block['attributes'], $block['content'] );
	}
	return $block_contents;
}

/**
 * The function `abcc_openai_generate_post` generates a new post for a WordPress site using OpenAI or
 * Gemini API based on specified keywords, tone, and other parameters, and includes content creation,
 * image generation, and post creation functionalities.
 *
 * @param string  $api_key The `api_key` parameter is used to authenticate and authorize access to the OpenAI
 *  API. It is a unique key provided by OpenAI that allows your application to interact with their
 *  services securely. This key is essential for making requests to generate text and images using
 *  OpenAI's models and tools. Make
 * @param string  $keywords The `abcc_openai_generate_post` function generates a new post for a WordPress site
 *  using OpenAI or Gemini API. Here's an explanation of the parameters:
 * @param string  $prompt_select The `prompt_select` parameter in the `abcc_openai_generate_post` function
 *  determines whether to use OpenAI or Gemini for generating text content. It is used to select the
 *  service that will be responsible for generating the article content based on the provided prompt.
 * @param string  $tone The `tone` parameter in the `abcc_openai_generate_post` function is used to specify the
 *  tone or style of writing for the generated article. It defaults to 'default', but you can provide
 *  different tones such as formal, casual, professional, friendly, etc., depending on the desired voice
 * @param boolean $auto_create The `auto_create` parameter in the `abcc_openai_generate_post` function is a
 * boolean parameter that determines whether the post should be automatically created or not. If set to
 * `true`, the function will automatically create a draft post based on the provided parameters. If set
 * to `false`, the
 * @param string  $char_limit The `char_limit` parameter in the `abcc_openai_generate_post` function specifies
 *  the character limit for the generated post content. In this function, the default value for
 *  `char_limit` is set to 200 characters. This means that the generated post content will be limited to
 *  200 characters
 */
function abcc_openai_generate_post( $api_key, $keywords, $prompt_select, $tone = 'default', $auto_create = false, $char_limit = 200 ) {

	$selected_categories = get_option( 'openai_selected_categories', array() );
	$category_names      = array();

	foreach ( $selected_categories as $category_id ) {
		$category = get_category( $category_id );
		if ( $category ) {
			$category_names[] = $category->name;
		}
	}

	$site_name        = get_bloginfo( 'name' );
	$site_description = get_bloginfo( 'description' );
	$site_url         = get_bloginfo( 'url' );
	$cat_images       = '';

	$prompt = __( 'My work is to create well-optimized SEO articles for the site ', 'automated-blog-content-creator' ) . $site_name;

	$prompt .= __( ' with the URL ', 'automated-blog-content-creator' ) . $site_url;

	if ( $site_description ) {
		$prompt .= __( ' and this meta description: "', 'automated-blog-content-creator' ) . $site_description . '"';
	}

	$prompt = __( 'I need you to assume the persona of a web content writer with a strong focus on SEO and create an article for the website I specified. ', 'automated-blog-content-creator' );

	$prompt .= __( 'The article should focus on the following keywords: "', 'automated-blog-content-creator' ) . implode( ', ', array_map( 'sanitize_text_field', $keywords ) ) . '"';

	$prompt .= __( '. Use the following tone: ', 'automated-blog-content-creator' ) . $tone;

	$prompt .= __( '. Use only HTML for the content, no need to add <article> or extra tags. Use <h1> for the title in the beginning. There is no need to add sections like category, keywords, etc. I just need the content that will be used on the article.', 'automated-blog-content-creator' );

	if ( ! empty( $category_names ) ) {
		$prompt    .= __( ' and in the following categories: "', 'automated-blog-content-creator' ) . implode( ', ', $category_names ) . '"';
		$cat_images = __( 'Emphasize on: "', 'automated-blog-content-creator' ) . implode( ', ', $category_names ) . __( '" and other relevant visual elements.', 'automated-blog-content-creator' );
	}

	$prompt .= __( ' Keep the article under: ', 'automated-blog-content-creator' ) . $char_limit . __( ' tokens.', 'automated-blog-content-creator' );

	$prompt_images = sprintf(
		// translators: %1$s is a list of keywords, %2$s is a string representing category images, %3$s is the site URL.
		__( 'Draw images representing %1$s. %2$s Visit %3$s for inspiration.', 'automated-blog-content-creator' ),
		implode( ', ', array_map( 'sanitize_text_field', $keywords ) ),
		$cat_images,
		$site_url
	);
	if ( ( 'openai' === $prompt_select ) || ( 'gpt-4' === $prompt_select ) || ( 'gpt-4o' === $prompt_select ) ) {
		$text   = abcc_openai_generate_text( $api_key, $prompt, $char_limit, $prompt_select );
		$images = abcc_openai_generate_images( $api_key, $prompt_images, 1 );
	} elseif ( 'gemini' === $prompt_select ) {
		$text = abcc_gemini_generate_text( $api_key, $prompt, $char_limit );
	}

	if ( ! empty( $text ) && is_array( $text ) ) {
		foreach ( $text as $key => $value ) {
			if ( strpos( $value, '<h1>' ) !== false && strpos( $value, '</h1>' ) !== false ) {
				$title = str_replace( array( '<h1>', '</h1>' ), '', $value );
				unset( $text[ $key ] );
				break;
			}
		}

		$format_content = abcc_create_blocks( $text );
		$post_content   = abcc_gutenberg_blocks( $format_content );

		$post_data = array(
			'post_title'    => $title,
			'post_content'  => wp_kses_post( $post_content ),
			'post_status'   => 'draft',
			'post_author'   => 1,
			'post_type'     => 'post',
			'post_category' => get_option( 'openai_selected_categories', array() ),

		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			handle_api_request_error( $post_id, 'WordPress' ); // Use the error handler for WordPress errors.
		}

		if ( get_option( 'openai_email_notifications', false ) ) {
			$admin_email = get_option( 'admin_email' );
			$subject     = esc_html__( 'New Automated Post Created', 'automated-blog-content-creator' );
			$message     = esc_html__( 'A new post has been created automatically using OpenAI.', 'automated-blog-content-creator' );

			wp_mail( $admin_email, $subject, $message );
		}
	}

	if ( ! empty( $images ) ) {
		foreach ( $images as $image_url ) {
			if ( ! function_exists( 'media_sideload_image' ) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}
			$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );

			if ( is_wp_error( $attachment_id ) ) {
				error_log( 'Error getting image: ' . $attachment_id->get_error_message() . 'image: ' . $image_url );
			} elseif ( is_numeric( $attachment_id ) ) {
				set_post_thumbnail( $post_id, intval( $attachment_id ) );
				break;
			} else {
				error_log( 'Unexpected media_sideload_image error: ' . var_export( $attachment_id, true ) );
			}
		}
	}
}

/**
 * Check if the 'openai_generate_post_hook' event is scheduled and get schedule details.
 *
 * @return array|bool An array with schedule details if the event is scheduled, false otherwise.
 */
function get_openai_event_schedule() {
	$timestamp = wp_next_scheduled( 'abcc_openai_generate_post_hook' );

	if ( false === $timestamp ) {
		return false;
	}

	$schedule = wp_get_schedule( 'abcc_openai_generate_post_hook' );

	return array(
		'scheduled' => true,
		'schedule'  => $schedule,
		'next_run'  => date_i18n( 'Y-m-d H:i:s', $timestamp ),
		'timestamp' => $timestamp,
	);
}
/**
 * This function generates a post daily using OpenAI API based on specified keywords and character
 * limit.
 *
 * return nothing if any of the following conditions are met:
 * - The OpenAI API key is empty
 * - The OpenAI keywords are empty
 * - The OpenAI auto-create option is false
 */
function abcc_openai_generate_post_scheduled() {
	$api_key       = abcc_check_api_key();
	$keywords      = explode( "\n", get_option( 'openai_keywords', '' ) );
	$tone          = get_option( 'openai_tone', 'default' );
	$auto_create   = get_option( 'openai_auto_create', 'none' );
	$char_limit    = get_option( 'openai_char_limit', 200 );
	$prompt_select = get_option( 'prompt_select', 'openai' );

	if ( empty( $api_key ) || empty( $keywords ) || 'none' === $auto_create ) {
		return false;
	}

	abcc_openai_generate_post( $api_key, $keywords, $prompt_select, $tone, $auto_create, $char_limit );
}

/**
 * Schedule or unschedule the event based on the selected option.
 */
function abcc_schedule_openai_event() {
	$selected_option = get_option( 'openai_auto_create', 'none' );

	// Unscheduling the event if it was scheduled previously.
	wp_clear_scheduled_hook( 'abcc_openai_generate_post_hook' );

	// Scheduling the event based on the selected option.
	if ( 'none' !== $selected_option ) {
		$schedule_interval = ( 'hourly' === $selected_option ) ? 'hourly' : ( ( 'weekly' === $selected_option ) ? 'weekly' : 'daily' );
		wp_schedule_event( time(), $schedule_interval, 'abcc_openai_generate_post_hook' );
	}
}

// Schedule or unschedule the event when the option is updated.
add_action( 'update_option_openai_auto_create', 'abcc_schedule_openai_event' );

// Trigger the OpenAI post generation.
add_action( 'abcc_openai_generate_post_hook', 'abcc_openai_generate_post_scheduled' );

/**
 * This is a PHP function that generates a post using OpenAI API and AJAX, with options for API key,
 * keywords, and character limit.
 */
function abcc_openai_generate_post_ajax() {

	check_ajax_referer( 'abcc_openai_generate_post' );
	$api_key       = abcc_check_api_key();
	$keywords      = explode( "\n", get_option( 'openai_keywords', '' ) );
	$char_limit    = get_option( 'openai_char_limit', 200 );
	$tone          = get_option( 'openai_tone', 'default' );
	$prompt_select = get_option( 'prompt_select', 'openai' );

	if ( empty( $api_key ) || empty( $keywords ) ) {
		wp_send_json_error( esc_html__( 'API Key, Prompt Engine or keywords not set.', 'automated-blog-content-creator' ) );
	}

	abcc_openai_generate_post( $api_key, $keywords, $prompt_select, $tone, false, $char_limit );

	wp_send_json_success( esc_html__( 'Post created successfully!', 'automated-blog-content-creator' ) );
}
add_action( 'wp_ajax_openai_generate_post', 'abcc_openai_generate_post_ajax' );

/**
 * This function deactivates a scheduled hook for generating posts using OpenAI in WordPress.
 */
function abcc_openai_deactivate_plugin() {
	wp_clear_scheduled_hook( 'abcc_openai_generate_post_hook' );
}
register_deactivation_hook( __FILE__, 'abcc_openai_deactivate_plugin' );

add_action( 'admin_notices', 'display_openai_settings_errors' );
function display_openai_settings_errors() {
	settings_errors( 'openai-settings' );
}

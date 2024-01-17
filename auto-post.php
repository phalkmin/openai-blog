<?php
/*
Plugin Name: Automated Blog Content Creator
Description: Create blog posts automatically using the OpenAI API - It's ChatGPT directly on your site!
Version: 0.9
Author: Paulo H. Alkmin
Author URI: https://phalkmin.me/
Text Domain: automated-wordpress-content-creator
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';

use Orhanerday\OpenAi\OpenAi;

/**
 * The function generates text using OpenAI's API with a given prompt and length.
 *
 * @param string $api_key The API key needed to access the OpenAI API.
 * @param string $prompt The text prompt that the OpenAI model will use to generate the text.
 * @param int    $length The length parameter is the maximum number of tokens (words or characters) that the
 *    generated text should have.
 *
 * @return array An array of text generated by the OpenAI API, based on the provided prompt and length. The
 * text is broken into an array at each line break.
 */
function abcc_openai_generate_text( $api_key, $prompt, $length ) {
	$openai = new OpenAi( $api_key );

	$response = $openai->completion(
		array(
			'model'       => 'gpt-3.5-turbo-instruct',
			'prompt'      => wp_kses_post( $prompt ),
			'max_tokens'  => absint( $length ),
			'n'           => 1,
			'stop'        => null,
			'temperature' => 0.8,
		)
	);

	if ( is_wp_error( $response ) ) {
		add_settings_error(
			'openai-settings',  
			'api-request-error', 
			esc_html__( 'Error in API request. Please check your OpenAI configuration.', 'automated-blog-content-creator' ),
			'error'             
		);
		return;
	}

	// Decode response.
	$d = json_decode( $response );
	// Get Content.
	$text = $d->choices[0]->text;

	// Break text into an array at each line break.
	$text_array = explode( PHP_EOL, $text );

	return $text_array;
}

/**
 * The function generates images using OpenAI API based on a given prompt and returns an array of URLs.
 *
 * @param string $api_key The API key for the OpenAI service that is being used to generate images.
 * @param string $prompt The prompt is the text or description that you provide to the OpenAI API to generate
 * images based on that description. For example, if you provide the prompt "a red apple on a white
 * background", the API will generate images of a red apple on a white background.
 * @param string $n The 'n' parameter specifies the number of images to generate.
 *
 * @return array an array of URLs for generated images based on the provided prompt using the OpenAI API.
 */
function abcc_openai_generate_images( $api_key, $prompt, $n, $image_size = '1024x1024' ) {
	$openai = new OpenAi( $api_key );

	$response = $openai->image(
		array(
			'model'           => 'dall-e-3',
			'prompt'          => wp_kses_post( $prompt ),
			'n'               => absint( $n ),
			'size'            => $image_size,
			'response_format' => 'url'
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( print_r($response, true));
		return;
	}

	// Decode the JSON into an associative array.
	$response = json_decode( $response, true );

	// Check if 'data' key exists in the response array.
	if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
		$urls = array();

		// Iterate over each element in the 'data' array.
		foreach ( $response['data'] as $item ) {
			// Check if 'url' key exists in the item.
			if ( isset( $item['url'] ) ) {
				$urls[] = ( $item['url'] );
			}
		}
		return $urls;
	}
}

/**
 * The below functions are used to create and output Gutenberg blocks in WordPress.
 *
 * @param string $block_name The name of the Gutenberg block being created.
 * @param array  $attributes The attributes parameter is an array that contains the attributes of a Gutenberg
 *  block. These attributes can include things like alignment, font size, background color, etc. The
 *  attributes are passed to the abcc_create_block function as the second parameter and are then encoded
 *  as a JSON string using the wp_json_encode function.
 * @param string $content The content parameter is the actual content that will be displayed within the block.
 * It can be any valid HTML or text content.
 *
 * @return array The code contains three functions. The first function `abcc_create_block` takes in a block
 * name, attributes, and content and returns a string of HTML code for that block. The second function
 * `abcc_create_blocks` takes in an array of text items, creates a paragraph block for each item, and
 * returns an array of blocks. The third function `abcc_gutenberg_blocks` takes in
 */
function abcc_create_block( $block_name, $attributes = array(), $content = '' ) {
    $attributes_string = wp_json_encode( $attributes );
    $block_content     = '<!-- wp:' . esc_attr( $block_name ) . ' ' . $attributes_string . ' -->' . wp_kses_post( $content ) . '<!-- /wp:' . esc_attr( $block_name ) . ' -->';
    return $block_content;
}

function abcc_create_blocks( $text_array ) {
	$blocks = array();
	foreach ( $text_array as $item ) {
		if ( ! empty( $item ) ) {
			$block = array(
				'name'       => 'paragraph',
				'attributes' => array( 'align' => 'left', 'custom_attribute' => 'value' ),  // Adicione atributos personalizados aqui.
				'content'    => wp_kses_post( $item ),
			);
			$blocks[] = $block;
		}
	}
	return $blocks;
}

function abcc_gutenberg_blocks( $blocks = array() ) {
	$block_contents = '';
	foreach ( $blocks as $block ) {
		$block_contents .= abcc_create_block( $block['name'], $block['attributes'], $block['content'] );
	}
	return $block_contents;
}

/**
 * The function generates a draft post with a title and content based on given keywords using OpenAI's
 * text and image generation API.
 *
 * @param string  $api_key The API key needed to access the OpenAI API for generating text and images.
 * @param string  $keywords An array of keywords that will be used as the basis for generating the article
 *  content.
 * @param boolean $auto_create The auto_create parameter is a boolean value that determines whether or not to
 * automatically create a featured image for the post using the images generated by the OpenAI API. If
 * set to true, the function will attempt to upload the first generated image as the post's featured
 * image.
 * @param string  $char_limit The maximum number of characters that the generated text should have.
 */
function abcc_openai_generate_post( $api_key, $keywords, $auto_create = false, $char_limit = 200 ) {
	$prompt = __( 'Write a SEO focused article about ', 'automated-blog-content-creator' ) . implode( ', ', array_map( 'sanitize_text_field', $keywords ) );
	$text   = abcc_openai_generate_text( $api_key, $prompt, $char_limit );
	$images = abcc_openai_generate_images( $api_key, $prompt, 1 );

	$format_content = abcc_create_blocks( $text );
	$post_content   = abcc_gutenberg_blocks( $format_content );

	$post_data = array(
		'post_title'   => __( 'Article about ', 'automated-blog-content-creator' ) . implode( ', ', array_map( 'sanitize_text_field', $keywords ) ),
		'post_content' => wp_kses_post( $post_content ),
		'post_status'  => 'draft',
		'post_author'  => 1,
		'post_type'    => 'post',
	);

	$post_id = wp_insert_post( $post_data );

	if ( get_option( 'openai_email_notifications', false ) ) {
		$admin_email = get_option( 'admin_email' );
		$subject     = esc_html__( 'New Automated Post Created', 'automated-blog-content-creator' );
		$message     = esc_html__( 'A new post has been created automatically using OpenAI.', 'automated-blog-content-creator' );
		
		// Utilize a função `wp_mail` para enviar e-mails.
		wp_mail( $admin_email, $subject, $message );
	}
    if ( ! empty( $images ) ) {
        foreach ( $images as $image_url ) {
            $attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );

            if ( is_wp_error( $attachment_id ) ) {
                error_log( 'Error getting image: ' . $attachment_id->get_error_message() . 'image: ' . $image_url);
            } else if ( is_numeric( $attachment_id ) ) {
                set_post_thumbnail( $post_id, intval( $attachment_id ) );
                break; 
            } else {
                error_log( 'Unexpected media_sideload_image error: ' . var_export( $attachment_id, true ) );
            }
        }
    }
}

/**
 * This function adds an options page for the OpenAI Blog Post Generator to the WordPress admin menu.
 */
function abcc_openai_blog_post_menu() {
	add_options_page(
		__( 'OpenAI Blog Post Generator', 'automated-blog-content-creator' ),
		__( 'OpenAI Blog Post', 'automated-blog-content-creator' ),
		'manage_options',
		'automated-blog-content-creator-post',
		'abcc_openai_blog_post_options_page'
	);
}
add_action( 'admin_menu', 'abcc_openai_blog_post_menu' );

/**
 * This function creates an options page for a WordPress plugin that allows users to set OpenAI API
 * key, keywords, auto-post creation, and character limit, and also includes a button to manually
 * generate a blog post using OpenAI.
 */



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
        'scheduled'  => true,
        'schedule'   => $schedule,
        'next_run'   => date_i18n( 'Y-m-d H:i:s', $timestamp ),
        'timestamp'  => $timestamp,
    );
}

function abcc_openai_blog_post_options_page() {
	if ( isset( $_POST['submit'], $_POST['abcc_openai_nonce'] ) && wp_verify_nonce( $_POST['abcc_openai_nonce'], 'abcc_openai_generate_post' ) ) {
		$api_key = isset($_POST['openai_api_key']) ? sanitize_text_field(wp_unslash($_POST['openai_api_key'])) : '';
		$keywords = isset($_POST['openai_keywords']) ? sanitize_text_field(wp_unslash($_POST['openai_keywords'])) : '';
		$auto_create = isset($_POST['openai_auto_create']) ? sanitize_text_field(wp_unslash($_POST['openai_auto_create'])) : '';
		$char_limit = isset($_POST['openai_char_limit']) ? absint($_POST['openai_char_limit']) : 0;
		$openai_email_notifications = isset($_POST['openai_email_notifications']);

		update_option('openai_api_key', wp_unslash($api_key));
		update_option('openai_keywords', wp_unslash($keywords));
		update_option('openai_auto_create', $auto_create);
		update_option('openai_char_limit', $char_limit);
		update_option('openai_email_notifications', $openai_email_notifications);
	}

	$api_key = get_option('openai_api_key', '');
	$keywords = get_option('openai_keywords', '');
	$auto_create = get_option('openai_auto_create', 'none'); // Default to 'none'
	$char_limit = get_option('openai_char_limit', 200);
	$openai_email_notifications = get_option('openai_email_notifications', false);

	$schedule_info = get_openai_event_schedule();

	if ( $schedule_info ) {
		
		echo '<div class="notice notice-info">
				<p>Posts are scheduled to be automatically published <strong>' . esc_html( $schedule_info['schedule'] ) . '</strong> and the next execution will be on ' . esc_html( $schedule_info['next_run'] ) . '.</p>
			  </div>';
	} else {
		echo '<div class="notice notice-info">
				<p>There are no scheduled posts to be published.</p>
	  		</div>';
	}

	?>

<style>
    .abcc-settings-wrapper .postbox { margin-bottom: 20px; }
    .abcc-settings-wrapper .postbox h2 { border-bottom: 1px solid #eee; padding: 10px; }
    .abcc-settings-wrapper .postbox .inside { padding: 10px 20px; }
    .abcc-settings-wrapper .form-table th { width: 20%; }
    .abcc-settings-wrapper .form-table td { padding: 10px 0; }
    .abcc-settings-wrapper .form-table input[type="text"],
    .abcc-settings-wrapper .form-table textarea { width: 100%; }
    .abcc-settings-wrapper .form-table input[type="checkbox"] { margin-right: 5px; }
    .abcc-settings-wrapper .form-table .description { margin-top: 5px; display: block; }
    .abcc-settings-wrapper .submit { padding: 10px 20px; }
</style>

			<div class="wrap">
					<h1><?php echo esc_html__( 'OpenAI Blog Post Generator', 'automated-blog-content-creator' ); ?></h1>
					<div id="poststuff">
						<div id="post-body" class="metabox-holder columns-2">
							<div id="post-body-content">
								<div class="meta-box-sortables ui-sortable">
									<form method="post" action="">
										<?php wp_nonce_field('abcc_openai_generate_post', 'abcc_openai_nonce'); ?>
										<div class="postbox">
											<h2 class="hndle ui-sortable-handle"><?php echo esc_html__( 'Settings', 'automated-blog-content-creator' ); ?></h2>
											<div class="inside">
												<table class="form-table">
													<tr>
														<th scope="row"><label for="openai_api_key">
																<?php echo esc_html__('OpenAI API key:', 'automated-blog-content-creator'); ?>
															</label></th>
														<td>
															<input type="text" id="openai_api_key" name="openai_api_key"
																value="<?php echo esc_attr($api_key); ?>" class="regular-text">
														</td>
													</tr>
													<tr>
														<th scope="row"><label for="openai_keywords">
																<?php echo esc_html__('Subjects / Keywords that blog posts should be about:', 'automated-blog-content-creator'); ?>
															</label></th>
														<td>
															<textarea id="openai_keywords" name="openai_keywords"
																rows="4"><?php echo esc_textarea($keywords); ?></textarea>
														</td>
													</tr>
													<tr>
														<th scope="row"><label for="openai_auto_create">
																<?php echo esc_html__('Automatic post creation?', 'automated-blog-content-creator'); ?>
															</label></th>
														<td>
															<select id="openai_auto_create" name="openai_auto_create">
																<option value="none" <?php selected($auto_create, 'none'); ?>><?php esc_html_e('None', 'automated-blog-content-creator'); ?></option>
																<option value="hourly" <?php selected($auto_create, 'hourly'); ?>><?php esc_html_e('Hourly', 'automated-blog-content-creator'); ?></option>
																<option value="daily" <?php selected($auto_create, 'daily'); ?>><?php esc_html_e('Daily', 'automated-blog-content-creator'); ?></option>
																<option value="weekly" <?php selected($auto_create, 'weekly'); ?>><?php esc_html_e('Weekly', 'automated-blog-content-creator'); ?></option>
															</select>
														</td>
													</tr>
													<tr>
														<th scope="row"><label for="openai_char_limit">
																<?php echo esc_html__('Max Token Limit', 'automated-blog-content-creator'); ?>
															</label></th>
														<td>
															<input type="number" id="openai_char_limit" name="openai_char_limit"
																value="<?php echo esc_attr($char_limit); ?>" min="1">
														</td>
													</tr>
													<tr>
														<th scope="row"><label for="openai_email_notifications">
																<?php echo esc_html__('Enable Email Notifications:', 'automated-blog-content-creator'); ?>
															</label></th>
														<td>
															<input type="checkbox" id="openai_email_notifications" name="openai_email_notifications" <?php checked($openai_email_notifications); ?>>
															<p class="description"><?php esc_html_e('Receive email notifications when a new post is created.', 'automated-blog-content-creator'); ?></p>
														</td>
													</tr>
															</table>
											</div>
										</div>
										<p class="submit">
											<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__( 'Save', 'automated-blog-content-creator' ); ?>">
											<button type="button" name="generate-post" id="generate-post" class="button button-secondary"><?php echo esc_attr__( 'Create post manually', 'automated-blog-content-creator' ); ?></button>
										</p>
									</form>
								</div>
							</div>
						</div>
						<br class="clear">
					</div>
				</div>

	<script>
		(function($) {
			$('#generate-post').on('click', function() {
				var $btn = $(this);
				$btn.prop('disabled', true);

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: 'openai_generate_post', 
						_ajax_nonce: $('#abcc_openai_nonce').val(), 
					},
					success: function(response) {
						alert(response.data);
					},
					error: function(xhr) {
						alert('<?php echo esc_js('Error generating post:', 'automated-blog-content-creator'); ?> ' + xhr.responseText);
					},
					complete: function() {
						$btn.prop('disabled', false);
					}
				});
			});
		})(jQuery);
	</script>



	<?php
}
/**
 * This function generates a post daily using OpenAI API based on specified keywords and character
 * limit.
 *
 * @return nothing if any of the following conditions are met:
 * - The OpenAI API key is empty
 * - The OpenAI keywords are empty
 * - The OpenAI auto-create option is false
 */
function abcc_openai_generate_post_scheduled() {
	$api_key = get_option( 'openai_api_key', '' );
	$keywords = explode( "\n", get_option( 'openai_keywords', '' ) );
	$auto_create = get_option( 'openai_auto_create', 'none' );
	$char_limit = get_option( 'openai_char_limit', 200 );

	if ( empty( $api_key ) || empty( $keywords ) || $auto_create === 'none' ) {
		return;
	}

	abcc_openai_generate_post( $api_key, $keywords, $auto_create, $char_limit );
}

/**
 * Schedule or unschedule the event based on the selected option.
 */
function abcc_schedule_openai_event() {
	$selected_option = get_option( 'openai_auto_create', 'none' );

	// Unscheduling the event if it was scheduled previously.
	wp_clear_scheduled_hook( 'abcc_openai_generate_post_hook' );

	// Scheduling the event based on the selected option.
	if ( $selected_option !== 'none' ) {
		$schedule_interval = ( $selected_option === 'hourly' ) ? 'hourly' : ( ( $selected_option === 'weekly' ) ? 'weekly' : 'daily' );
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
	check_ajax_referer('abcc_openai_generate_post');
	$api_key    = get_option( 'openai_api_key', '' );
	$keywords   = explode( "\n", get_option( 'openai_keywords', '' ) );
	$char_limit = get_option( 'openai_char_limit', 200 );

	if ( empty( $api_key ) || empty( $keywords ) ) {
		wp_send_json_error( esc_html__( 'API Key or keywords not set.', 'automated-blog-content-creator' ) );
	}

	abcc_openai_generate_post( $api_key, $keywords, false, $char_limit );

	wp_send_json_success( esc_html__( 'Post created successfully!', 'automated-blog-content-creator' ) );
}
add_action( 'wp_ajax_openai_generate_post', 'abcc_openai_generate_post_ajax' );

/**
 * This function deactivates a scheduled hook for generating posts using OpenAI in WordPress.
 */
function abcc_openai_deactivate_plugin() {
	wp_clear_scheduled_hook( 'openai_generate_post_hook' );
}
register_deactivation_hook( __FILE__, 'abcc_openai_deactivate_plugin' );

add_action( 'admin_notices', 'display_openai_settings_errors' );
function display_openai_settings_errors() {
    // Verifique se há mensagens de erro para a configuração do OpenAI.
    settings_errors( 'openai-settings' );
}
<?php
/**
 * File: admin.php
 *
 * This file contains functions related to the administration settings
 * of the WP-AutoInsight plugin, including menu pages and options.
 *
 * @package WP-AutoInsight
 */

/**
 * The function `abcc_add_subpages_to_menu` adds subpages for "Text Settings" and "Advanced Settings"
 * under the main menu page "WP-AutoInsight" in WordPress admin menu.
 */
function abcc_add_subpages_to_menu() {
	add_menu_page(
		__( 'WP-AutoInsight', 'automated-blog-content-creator' ),
		__( 'WP-AutoInsight', 'automated-blog-content-creator' ),
		'manage_options',
		'automated-blog-content-creator-post',
		'abcc_openai_text_settings_page'
	);

	add_submenu_page(
		'automated-blog-content-creator-post',
		__( 'Text Settings', 'automated-blog-content-creator' ),
		__( 'Text Settings', 'automated-blog-content-creator' ),
		'manage_options',
		'automated-blog-content-creator-post',
		'abcc_openai_text_settings_page'
	);

	add_submenu_page(
		'automated-blog-content-creator-post',
		__( 'Advanced Settings', 'automated-blog-content-creator' ),
		__( 'Advanced Settings', 'automated-blog-content-creator' ),
		'manage_options',
		'abcc-openai-advanced-settings',
		'abcc_openai_blog_post_options_page'
	);
}
add_action( 'admin_menu', 'abcc_add_subpages_to_menu' );

/**
 * The function `wpai_category_dropdown` is used to display a dropdown of categories in the WordPress admin
 * for the OpenAI blog post generator plugin.
 *
 * @param array $selected_categories An array of category IDs that should be selected by default.
 */
function wpai_category_dropdown( $selected_categories = array() ) {
	$categories = get_categories( array( 'hide_empty' => 0 ) );
	echo '<select id="openai_selected_categories" class="wpai-category-select" name="openai_selected_categories[]" multiple style="width:100%;">';
	foreach ( $categories as $category ) {
		$selected = in_array( $category->term_id, $selected_categories ) ? ' selected="selected"' : '';
		echo '<option value="' . esc_attr( $category->term_id ) . '"' . esc_html( $selected ) . '>' . esc_html( $category->name ) . '</option>';
	}
	echo '</select>';
}

/**
 * The function `abcc_openai_text_settings_page` is used to display and handle settings for an OpenAI
 * blog post generator, including options for keywords, tone selection, and category selection.
 */
function abcc_openai_text_settings_page() {

	if ( isset( $_POST['submit'], $_POST['abcc_openai_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['abcc_openai_nonce'] ), 'abcc_openai_generate_post' ) ) {

		$keywords            = isset( $_POST['openai_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['openai_keywords'] ) ) : '';
		$selected_categories = isset( $_POST['openai_selected_categories'] ) ? array_map( 'intval', $_POST['openai_selected_categories'] ) : array();

		if ( isset( $_POST['openai_tone'] ) ) {
			$openai_tone = sanitize_text_field( wp_unslash( $_POST['openai_tone'] ) );

			if ( 'custom' === $openai_tone ) {
				$custom_tone = isset( $_POST['custom_tone'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_tone'] ) ) : '';
				update_option( 'custom_tone', $custom_tone );
			} else {
				update_option( 'custom_tone', '' );

			}
			update_option( 'openai_tone', $openai_tone );
		}

		update_option( 'openai_keywords', wp_unslash( $keywords ) );
		update_option( 'openai_selected_categories', $selected_categories );

	}

	$selected_categories = get_option( 'openai_selected_categories', array() );

	$keywords      = get_option( 'openai_keywords', '' );
	$schedule_info = get_openai_event_schedule();
	$tone          = get_option( 'openai_tone', '' );
	if ( get_option( 'custom_tone' ) != '' ) {
		$custom_tone_value = get_option( 'custom_tone' );
	} else {
		$custom_tone_value = '';
	}

	if ( $schedule_info ) {
		echo '<div class="notice notice-info">
				<p>Posts are scheduled to be automatically published <strong>' . esc_html( $schedule_info['schedule'] ) . '</strong> and the next execution will be on ' . esc_html( $schedule_info['next_run'] ) . '.</p>
			  </div>';
	} else {
		echo '<div class="notice notice-info">
				<p>There are no scheduled posts to be published.</p>
	  		</div>';
	}

	$tones = array(
		'default'  => 'Use a default tone',
		'business' => 'Business-oriented',
		'academic' => 'Academic',
		'funny'    => 'Funny',
		'epic'     => 'Epic',
		'personal' => 'Personal',
		'custom'   => 'Custom',
	);
	?>
	<div class="wrap">

		<h1><?php echo esc_html__( 'OpenAI Blog Post Generator', 'automated-blog-content-creator' ); ?></h1>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<form method="post" action="">
							<?php wp_nonce_field( 'abcc_openai_generate_post', 'abcc_openai_nonce' ); ?>
							<div class="postbox">
								<h2 class="hndle ui-sortable-handle">
									<?php echo esc_html__( 'Settings', 'automated-blog-content-creator' ); ?>
								</h2>
								<div class="inside">
									<table class="form-table">
										<tr>
											<th scope="row"><label for="openai_keywords">
													<?php echo esc_html__( 'Subjects / Keywords that blog posts should be about:', 'automated-blog-content-creator' ); ?>
												</label></th>
											<td>
												<textarea rows="4" cols="50" id="openai_keywords" name="openai_keywords"><?php echo esc_textarea( $keywords ); ?></textarea>
												<p class="description">Write a list of keywords that will be used on the prompt. Do note write the whole prompt.</p>

											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="openai_tone">
													<?php echo esc_html__( 'Tone you want to use:', 'automated-blog-content-creator' ); ?>
												</label>
											</th>
											<td>
												<?php foreach ( $tones as $value => $label ) : ?>
													<input type="radio" id="<?php echo esc_attr( $value ); ?>"
														name="openai_tone" value="<?php echo esc_attr( $value ); ?>" <?php echo ( $tone == $value ) ? 'checked' : ''; ?>>
													<label
														for="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $label ); ?></label>
												<?php endforeach; ?>
												<input type="text" id="custom_tone" name="custom_tone"
													value="<?php echo esc_attr( $custom_tone_value ); ?>" <?php echo ( $tone == $value ) ? 'checked' : ''; ?>
													style="<?php echo ( 'custom' == $tone ) ? '' : 'display:none;'; ?>">
											</td>
										</tr>
										<tr>
											<th scope="row"><label for="openai_selected_categories">
													<?php echo esc_html__( 'Select Categories:', 'automated-blog-content-creator' ); ?>
												</label></th>
											<td>
												<?php wpai_category_dropdown( $selected_categories ); ?>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<p class="submit">
								<input type="submit" name="submit" id="submit" class="button button-primary"
									value="<?php echo esc_attr__( 'Save', 'automated-blog-content-creator' ); ?>">
								<button type="button" name="generate-post" id="generate-post"
									class="button button-secondary"><?php echo esc_attr__( 'Create post manually', 'automated-blog-content-creator' ); ?></button>
							</p>
						</form>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			jQuery(".wpai-category-select").select2();
		});
	</script>
	<?php
}
/**
 * The function abcc_openai_blog_post_options_page() is used to display and handle options for an
 * OpenAI blog post generator plugin in WordPress.
 */
function abcc_openai_blog_post_options_page() {

	if ( isset( $_POST['submit'], $_POST['abcc_openai_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['abcc_openai_nonce'] ), 'abcc_openai_generate_post' ) ) {
		$api_key                    = isset( $_POST['openai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ) ) : '';
		$gemini_api_key             = isset( $_POST['gemini_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['gemini_api_key'] ) ) : '';
		$auto_create                = isset( $_POST['openai_auto_create'] ) ? sanitize_text_field( wp_unslash( $_POST['openai_auto_create'] ) ) : '';
		$prompt_select              = isset( $_POST['prompt_select'] ) ? sanitize_text_field( wp_unslash( $_POST['prompt_select'] ) ) : '';
		$char_limit                 = isset( $_POST['openai_char_limit'] ) ? absint( $_POST['openai_char_limit'] ) : 0;
		$openai_email_notifications = isset( $_POST['openai_email_notifications'] );

		update_option( 'openai_api_key', wp_unslash( $api_key ) );
		update_option( 'gemini_api_key', wp_unslash( $gemini_api_key ) );
		update_option( 'openai_auto_create', $auto_create );
		update_option( 'prompt_select', $prompt_select );
		update_option( 'openai_char_limit', $char_limit );
		update_option( 'openai_email_notifications', $openai_email_notifications );
	}

	$api_key                    = get_option( 'openai_api_key', '' );
	$gemini_api_key             = get_option( 'gemini_api_key', '' );
	$auto_create                = get_option( 'openai_auto_create', 'none' );
	$prompt_select              = get_option( 'prompt_select', 'openai' );
	$char_limit                 = get_option( 'openai_char_limit', 200 );
	$openai_email_notifications = get_option( 'openai_email_notifications', false );

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

	<div class="wrap">
		<h1><?php echo esc_html__( 'OpenAI Blog Post Generator', 'automated-blog-content-creator' ); ?></h1>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<form method="post" action="">
							<?php wp_nonce_field( 'abcc_openai_generate_post', 'abcc_openai_nonce' ); ?>
							<div class="postbox">
								<h2 class="hndle ui-sortable-handle">
									<?php echo esc_html__( 'Settings', 'automated-blog-content-creator' ); ?>
								</h2>
								<div class="inside">
									<table class="form-table">
										<?php
										if ( ! defined( 'OPENAI_API' ) ) {
											?>
											<tr>
												<th scope="row"><label for="openai_api_key">
														<?php echo esc_html__( 'OpenAI API key:', 'automated-blog-content-creator' ); ?>
													</label></th>
												<td>
													<input type="text" id="openai_api_key" name="openai_api_key"
														value="<?php echo esc_attr( $api_key ); ?>"
														class="regular-text"><small>for extra security, it's advised to include
														it on wp-config.php, using <em>define( 'OPENAI_API', '' );</em></small>
												</td>
											</tr>
											<?php
										} else {
											echo '<tr><th colspan="2">';
											echo '<strong>Your OpenAI API key is already set in wp-config.php.</strong>';
											echo '</tr></th><td></td>';
										}
										?>
										<?php
										if ( ! defined( 'GEMINI_API' ) ) {
											?>
											<tr>
												<th scope="row"><label for="gemini_api_key">
														<?php echo esc_html__( 'Gemini API key:', 'automated-blog-content-creator' ); ?>
													</label></th>
												<td>
													<input type="text" id="gemini_api_key" name="gemini_api_key"
														value="<?php echo esc_attr( $gemini_api_key ); ?>"
														class="regular-text"><small>for extra security, it's advised to include
														it on wp-config.php, using <em>define( 'GEMINI_API', '' );</em></small>
												</td>
											</tr>
											<?php
										} else {
											echo '<tr><th colspan="2">';
											echo '<strong>Your Gemini API key is already set in wp-config.php.</strong>';
											echo '</tr></th><td></td>';
										}
										?>
										<tr>
											<th scope="row"><label for="openai_api_key">
													<?php echo esc_html__( 'Which engine will you use to create the prompt?', 'automated-blog-content-creator' ); ?>
												</label></th>
											<td>
												<select id="prompt_select" name="prompt_select">
													<option value="openai" <?php selected( $prompt_select, 'openai' ); ?>>
														GPT3.5 - default option</option>
													<option value="gpt-4" <?php selected( $prompt_select, 'gpt-4' ); ?>>
														GPT4 - Better content, cost more</option>
													<option value="gpt-4o" <?php selected( $prompt_select, 'gpt-4o' ); ?>>
														GPT4o - Better content, cost less</option>
													<option value="gemini" <?php selected( $prompt_select, 'gemini' ); ?>>
														Gemini - images won't be created</option>
												</select>
											</td>
										</tr>

										<tr>
											<th scope="row"><label for="openai_auto_create">
													<?php echo esc_html__( 'Schedule post creation?', 'automated-blog-content-creator' ); ?>
												</label></th>
											<td>
												<select id="openai_auto_create" name="openai_auto_create">
													<option value="none" <?php selected( $auto_create, 'none' ); ?>>
														<?php esc_html_e( 'None', 'automated-blog-content-creator' ); ?>
													</option>
													<option value="hourly" <?php selected( $auto_create, 'hourly' ); ?>>
														<?php esc_html_e( 'Hourly', 'automated-blog-content-creator' ); ?>
													</option>
													<option value="daily" <?php selected( $auto_create, 'daily' ); ?>>
														<?php esc_html_e( 'Daily', 'automated-blog-content-creator' ); ?>
													</option>
													<option value="weekly" <?php selected( $auto_create, 'weekly' ); ?>>
														<?php esc_html_e( 'Weekly', 'automated-blog-content-creator' ); ?>
													</option>
												</select>
												<p class="description">You can disable the automatic creation of posts or schedule as you wish</p>

											</td>
										</tr>
										<tr>
											<th scope="row"><label for="openai_char_limit">
													<?php echo esc_html__( 'Max Token Limit', 'automated-blog-content-creator' ); ?>
												</label></th>
											<td>
												<input type="number" id="openai_char_limit" name="openai_char_limit" value="<?php echo esc_attr( $char_limit ); ?>" min="1">
												<p class="description">The maximum number of tokens (words and characters) will be used by the AI during post generation. Range: 1-4096</p>

											</td>
										</tr>
										<tr>
											<th scope="row"><label for="openai_email_notifications">
													<?php echo esc_html__( 'Enable Email Notifications:', 'automated-blog-content-creator' ); ?>
												</label></th>
											<td>
												<input type="checkbox" id="openai_email_notifications"
													name="openai_email_notifications" <?php checked( $openai_email_notifications ); ?>>
												<p class="description">
													<?php esc_html_e( 'Receive email notifications when a new post is created.', 'automated-blog-content-creator' ); ?>
												</p>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<p class="submit">
								<input type="submit" name="submit" id="submit" class="button button-primary"
									value="<?php echo esc_attr__( 'Save', 'automated-blog-content-creator' ); ?>">
							</p>
						</form>
					</div>
				</div>
			</div>
			<br class="clear">
			I can update this plugin faster with your help 👉🏼👈🏼
			<a href='https://ko-fi.com/U7U1LM8AP' target='_blank'><img height='36' style='border:0px;height:36px;' src='https://storage.ko-fi.com/cdn/kofi3.png?v=3' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>
		</div>
	</div>
	<?php
}
?>

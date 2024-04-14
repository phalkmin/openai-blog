<?php

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

function abcc_openai_text_settings_page() {

	if ( isset( $_POST['submit'], $_POST['abcc_openai_nonce'] ) && wp_verify_nonce( $_POST['abcc_openai_nonce'], 'abcc_openai_generate_post' ) ) {
		$keywords = isset($_POST['openai_keywords']) ? sanitize_text_field(wp_unslash($_POST['openai_keywords'])) : '';
		$tone = isset($_POST['openai_tone']) ? sanitize_text_field(wp_unslash($_POST['openai_tone'])) : '';

		$selected_categories = isset($_POST['openai_selected_categories']) ? array_map('intval', $_POST['openai_selected_categories']) : array();

		update_option('openai_keywords', wp_unslash($keywords));
		update_option('openai_tone', wp_unslash($tone));
		update_option('openai_selected_categories', $selected_categories);

	}

	$categories = get_categories( array( 'hide_empty' => false ) );
	$selected_categories = get_option('openai_selected_categories', array());
	$openai_tone = get_option('openai_tone', 'default');
	$keywords = get_option('openai_keywords', '');
	$schedule_info = get_openai_event_schedule();
	$selected_value = get_option('openai_tone'); // Obter o valor atualmente selecionado


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
														<th scope="row"><label for="openai_keywords">
																<?php echo esc_html__('Subjects / Keywords that blog posts should be about:', 'automated-blog-content-creator'); ?>
															</label></th>
														<td>
															<textarea id="openai_keywords" name="openai_keywords"
																rows="4"><?php echo esc_textarea($keywords); ?></textarea>
														</td>
													</tr>
													<tr>
														<th scope="row"><label for="openai_tone">
																<?php echo esc_html__('Tone you want to use:', 'automated-blog-content-creator'); ?>
															</label></th>
														<td>
														<input type="radio" id="default" name="openai_tone" value="default" <?php echo ($selected_value == 'default') ? 'checked' : ''; ?>>
														<label for="default">Use a default tone</label>

														<input type="radio" id="business" name="openai_tone" value="business" <?php echo ($selected_value == 'business') ? 'checked' : ''; ?>>
														<label for="business">Business-oriented</label>

														<input type="radio" id="academic" name="openai_tone" value="academic" <?php echo ($selected_value == 'academic') ? 'checked' : ''; ?>>
														<label for="academic">Academic</label>

														<input type="radio" id="funny" name="openai_tone" value="funny" <?php echo ($selected_value == 'funny') ? 'checked' : ''; ?>>
														<label for="Funny">Funny</label>

														<input type="radio" id="epic" name="openai_tone" value="epic" <?php echo ($selected_value == 'epic') ? 'checked' : ''; ?>>
														<label for="epic">Epic</label>

														<input type="radio" id="personal" name="openai_tone" value="personal" <?php echo ($selected_value == 'personal') ? 'checked' : ''; ?>>
														<label for="personal">Personal</label>

														</td>
													</tr>
													<tr>
														<th scope="row"><label for="openai_selected_categories">
																<?php echo esc_html__('Select Categories:', 'automated-blog-content-creator'); ?>
															</label></th>
														<td>
															<?php foreach ($categories as $category) { ?>
																<label>
																	<input type="checkbox" name="openai_selected_categories[]" value="<?php echo esc_attr($category->term_id); ?>" <?php checked(in_array($category->term_id, $selected_categories)); ?>>
																	<?php echo esc_html($category->name); ?>
																</label>
																<br>
															<?php } ?>
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
function abcc_openai_blog_post_options_page() {

	if ( isset( $_POST['submit'], $_POST['abcc_openai_nonce'] ) && wp_verify_nonce( $_POST['abcc_openai_nonce'], 'abcc_openai_generate_post' ) ) {
		$api_key = isset($_POST['openai_api_key']) ? sanitize_text_field(wp_unslash($_POST['openai_api_key'])) : '';
		$gemini_api_key = isset($_POST['gemini_api_key']) ? sanitize_text_field(wp_unslash($_POST['gemini_api_key'])) : '';
		$auto_create = isset($_POST['openai_auto_create']) ? sanitize_text_field(wp_unslash($_POST['openai_auto_create'])) : '';
		$prompt_select = isset($_POST['prompt_select']) ? sanitize_text_field(wp_unslash($_POST['prompt_select'])) : '';
		$char_limit = isset($_POST['openai_char_limit']) ? absint($_POST['openai_char_limit']) : 0;
		$openai_email_notifications = isset($_POST['openai_email_notifications']);

		update_option('openai_api_key', wp_unslash($api_key));
		update_option('gemini_api_key', wp_unslash($gemini_api_key));
		update_option('openai_auto_create', $auto_create);
		update_option('prompt_select', $prompt_select);
		update_option('openai_char_limit', $char_limit);
		update_option('openai_email_notifications', $openai_email_notifications);
	}

	$api_key = get_option('openai_api_key', '');
	$gemini_api_key = get_option('gemini_api_key', '');
	$auto_create = get_option('openai_auto_create', 'none'); // Default to 'none'
	$prompt_select = get_option('prompt_select', 'openai'); // Default to 'openai'
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
													<?php 
														if (!defined('OPENAI_API')) { ?>
															<tr>
																<th scope="row"><label for="openai_api_key">
																		<?php echo esc_html__('OpenAI API key:', 'automated-blog-content-creator'); ?>
																	</label></th>
																<td>
																	<input type="text" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text"><small>for extra security, it's advised to include it on wp-config.php, using <em>define( 'OPENAI_API', '' );</em></small>
																</td>
															</tr>
															<?php 
														} else {
															echo '<tr><th colspan="2">';
															echo '<strong>Your OpenAI API key is already set in wp-config.php.</strong>';
															echo '</tr></th><td></td>';
														} ?>
													<?php 
														if (!defined('GEMINI_API') ) { ?>
															<tr>
																<th scope="row"><label for="gemini_api_key">
																		<?php echo esc_html__('Gemini API key:', 'automated-blog-content-creator'); ?>
																	</label></th>
																<td>
																	<input type="text" id="gemini_api_key" name="gemini_api_key" value="<?php echo esc_attr($gemini_api_key); ?>" class="regular-text"><small>for extra security, it's advised to include it on wp-config.php, using <em>define( 'GEMINI_API', '' );</em></small>
																</td>
															</tr>
															<?php 
														} else {
															echo '<tr><th colspan="2">';
															echo '<strong>Your Gemini API key is already set in wp-config.php.</strong>';
															echo '</tr></th><td></td>';
													} ?>
															<tr>
																<th scope="row"><label for="openai_api_key">
																		<?php echo esc_html__('Which engine will you use to create the prompt?', 'automated-blog-content-creator'); ?>
																	</label></th>
																<td>
																	<select id="prompt_select" name="prompt_select">
																		<option value="openai" <?php selected($prompt_select, 'openai'); ?>>GPT3.5 - default option</option>
																		<option value="gemini" <?php selected($prompt_select, 'gemini'); ?>>Gemini - images won't be created</option>
																	</select>
																</td>
															</tr>

													<tr>
														<th scope="row"><label for="openai_auto_create">
																<?php echo esc_html__('Schedule post creation?', 'automated-blog-content-creator'); ?>
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
										</p>
									</form>
								</div>
							</div>
						</div>
						<br class="clear">
					</div>
				</div>
	<?php
}
?>
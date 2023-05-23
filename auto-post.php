<?php
/*
Plugin Name: OpenAI API Blog Post Creator
Description: Create blog posts automatically using the OpenAI API - It's ChatGPT directly on your site!
Version: 0.5
Author: Paulo H. Alkmin
Author URI: https://phalkmin.me/
Text Domain: openai-blog
Domain Path: /languages
*/

require_once __DIR__ . '/vendor/autoload.php';

use Orhanerday\OpenAi\OpenAi;

function openai_generate_text($api_key, $prompt, $length) {
    $openai = new Openai($api_key);

    $response = $openai->completion([
        'model' => 'text-davinci-003',
        'prompt' => $prompt,
        'max_tokens' => $length,
        'n' => 1,
        'stop' => null,
        'temperature' => 0.8,
    ]);

    // decode response
    $d = json_decode($response);
    // Get Content
    $text = $d->choices[0]->text;

    // Break text into an array at each line break
    $textArray = explode(PHP_EOL, $text);

    return $textArray;
}

function openai_generate_images($api_key, $prompt, $n) {
    $openai = new Openai($api_key);

    $response = $openai->image([
        'prompt' => $prompt,
        'n' => $n,
        'size' => '512x512',
        'response_format' => 'url',
    ]);

    // Decodificar o JSON para um array associativo
    $response = json_decode($response, true);

    // Verificar se a chave 'data' existe no array de resposta
    if (isset($response['data']) && is_array($response['data'])) {
        $urls = array();

        // Iterar sobre cada elemento do array 'data'
        foreach ($response['data'] as $item) {
            // Verificar se a chave 'url' existe no item
            if (isset($item['url'])) {
                // Adicionar a URL ao array de URLs
                $urls[] = $item['url'];
            }
        }

        return $urls;
    }
}

function create_block($block_name, $attributes = array(), $content = '') {
    $attributes_string = json_encode($attributes);
    $block_content = '<!-- wp:' . $block_name . ' ' . $attributes_string . ' -->' . $content . '<!-- /wp:' . $block_name . ' -->';
    return $block_content;
}

function create_blocks($textArray) {
    $blocks = array();
    foreach ($textArray as $item) {
        if (!empty($item)) {
            $block = array(
                'name' => 'paragraph',
                'attributes' => array('align' => 'left'),
                'content' => '' . $item . '',
            );
            $blocks[] = $block;
        }
    }
    return $blocks;
}

function gutenberg_blocks($blocks = array()) {
    $block_contents = '';
    foreach ($blocks as $block) {
        $block_contents .= create_block($block['name'], $block['attributes'], $block['content']);
    }
    return $block_contents;
}

function openai_generate_post($api_key, $keywords, $auto_create = false, $char_limit = 200) {
    $prompt = __('Write a SEO focused article about ', 'openai-blog') . implode(', ', $keywords);
    $text = openai_generate_text($api_key, $prompt, $char_limit);
    $images = openai_generate_images($api_key, $prompt, 1);

    $format_content = create_blocks($text);
    $post_content = gutenberg_blocks($format_content);


    $post_data = [
        'post_title' => __("Article about ", 'openai-blog') . implode(', ', $keywords),
        'post_content' => $post_content,
        'post_status' => 'draft',
        'post_author' => 1,
        'post_type' => 'post',
    ];

    $post_id = wp_insert_post($post_data);

    if ($auto_create) {
        foreach ($images as $image_url) {
            media_sideload_image($image_url, $post_id);
        }

        $attimages = get_attached_media('image', $post_id);
        if (!empty($attimages)) {
            $thumbnail_id = reset($attimages)->ID;
            set_post_thumbnail($post_id, $thumbnail_id);
        }
    }
}
        
function openai_blog_post_menu() {
    add_options_page(
        __('OpenAI Blog Post Generator', 'openai-blog'),
        __('OpenAI Blog Post', 'openai-blog'),
        'manage_options',
        'openai-blog-post',
        'openai_blog_post_options_page'
    );
}
        
add_action('admin_menu', 'openai_blog_post_menu');
        
function openai_blog_post_options_page() {

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to access this page.', 'openai-blog'));
    }
    if (isset($_POST['submit'])) {
        update_option('openai_api_key', $_POST['openai_api_key']);
        update_option('openai_keywords', $_POST['openai_keywords']);
        update_option('openai_auto_create', isset($_POST['openai_auto_create']));
        update_option('openai_char_limit', $_POST['openai_char_limit']);
    }
        
    $api_key = get_option('openai_api_key', '');
    $keywords = get_option('openai_keywords', '');
    $auto_create = get_option('openai_auto_create', false);
    $char_limit = get_option('openai_char_limit', 200);
    wp_nonce_field('openai_generate_post', 'openai_nonce');
        
        ?>
  <div class="wrap">
    <h1><?php echo esc_html__( 'OpenAI Blog Post Generator', 'openai-blog' ); ?></h1>
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="openai_api_key"><?php echo esc_html__( 'OpenAI API key:', 'openai-blog' ); ?></label></th>
                <td>
                    <input type="text" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="openai_keywords"><?php echo esc_html__( 'Subjects / Keywords that blog posts should be about:', 'openai-blog' ); ?></label></th>
                <td>
                    <textarea id="openai_keywords" name="openai_keywords" rows="4"><?php echo esc_textarea( $keywords ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="openai_auto_create"><?php echo esc_html__( 'Automatic post creation? (Daily)', 'openai-blog' ); ?></label></th>
                <td>
                    <input type="checkbox" id="openai_auto_create" name="openai_auto_create" <?php checked( $auto_create ); ?>>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="openai_char_limit"><?php echo esc_html__( 'Max Token Limit', 'openai-blog' ); ?></label></th>
                <td>
                    <input type="number" id="openai_char_limit" name="openai_char_limit" value="<?php echo esc_attr( $char_limit ); ?>" min="1">
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__( 'Save', 'openai-blog' ); ?>">
            <input type="submit" name="submit" id="generate-post" class="button button-primary" value="<?php echo esc_attr__( 'Create post manually', 'openai-blog' ); ?>">
            <script>
                (function ($) {
                    $('#generate-post').on('click', function () {
                        var $btn = $(this);
                        $btn.prop('disabled', true);

                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'openai_generate_post',
                                _ajax_nonce: $('#openai_nonce').val()
                            },
                            success: function (response) {
                                alert(response.data);
                            },
                            error: function (xhr) {
                                alert('<?php echo esc_js( 'Error generating post:', 'openai-blog' ); ?> ' + xhr.responseText);
                            },
                            complete: function () {
                                $btn.prop('disabled', false);
                            }
                        });
                    });
                })(jQuery);
            </script>
        </p>
    </form>
</div>

<?php
}

function openai_generate_post_daily() {
    $api_key = get_option( 'openai_api_key', '' );
    $keywords = explode( "\n", get_option( 'openai_keywords', '' ) );
    $auto_create = get_option( 'openai_auto_create', false );
    $char_limit = get_option( 'openai_char_limit', 200 );

    if ( empty( $api_key ) || empty( $keywords ) || !$auto_create ) {
        return;
    }
    openai_generate_post( $api_key, $keywords, $char_limit );
}
add_action( 'openai_generate_post_hook', 'openai_generate_post_daily' );

if ( ! wp_next_scheduled( 'openai_generate_post_hook' ) ) {
    wp_schedule_event( time(), 'daily', 'openai_generate_post_hook' );
}

function openai_generate_post_ajax() {
    check_ajax_referer( 'openai_generate_post' );
    $api_key = get_option( 'openai_api_key', '' );
    $keywords = explode( "\n", get_option( 'openai_keywords', '' ) );
    $char_limit = get_option( 'openai_char_limit', 200 );

    if ( empty( $api_key ) || empty( $keywords ) ) {
        wp_send_json_error( esc_html__( 'API Key or keywords not set.', 'openai-blog' ) );
    }

    openai_generate_post( $api_key, $keywords, $char_limit );

    wp_send_json_success( esc_html__( 'Post generated successfully.', 'openai-blog' ) );
}
add_action( 'wp_ajax_openai_generate_post', 'openai_generate_post_ajax' );

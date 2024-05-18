=== WP-AutoInsight ===
Contributors: phalkmin
Tags: openai, geminiai, blog, post, generator, gpt4, gpt4o, gpt35, dalle3
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

WP-AutoInsight revolutionizes blog content creation by using OpenAI's GPT models and the Gemini API. From crafting SEO-optimized articles to generating relevant images, this plugin automates content generation for your WordPress site!

Features:

- Integration with multiple OpenAI GPT models (GPT-3.5, GPT-4, and GPT-4o).
- Gemini API support for alternative content generation.
- Advanced settings to specify content generation models and customize token limits.
- Automatic and manual blog post generation with SEO-focused keywords.
- Dynamic image creation related to the content using OpenAI's powerful DALL-E model.
- Supports Gutenberg blocks for easy content formatting.
- Scheduled post creation options: hourly, daily, or weekly, to keep your blog constantly updated.
- AJAX-powered interface for immediate manual post generation.
- Translatable strings for internationalization.

== Installation ==

1. Upload the `openai-api-blog-post-creator` directory to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the 'OpenAI Blog Post' menu in the WordPress admin sidebar to configure the plugin settings.

== Frequently Asked Questions ==

= How do I get an OpenAI API key? =

To use the OpenAI API, you need to sign up for an API key on the OpenAI website. Sign up/log in to OpenAI, go to https://platform.openai.com/api-keys, and click "Create new API key".

= How do I get an GeminiAI API key? =

To use the GeminiAI API, you'll need an API key. You can create one in Google AI Studio https://support.gemini.com/hc/en-us/articles/360031080191-How-do-I-create-an-API-key.

= How do I select between different GPT models and Gemini API? =

In the plugin settings, you can select your preferred AI model from GPT-3.5, GPT-4, and GPT-4o. Gemini API is also available as an alternative or supplementary option.

= How can I customize the generated content? =

The plugin settings allow you to specify keywords, select AI models, set the character limit, and choose content generation frequency. This customization ensures the generated posts align with your blog's theme and audience.

= Can I customize the maximum character limit for generated posts? =

Yes, you can specify the maximum character limit for the generated posts in the plugin settings. The default limit is 200 characters.

= Is it possible to schedule content generation? =

Yes, WP-AutoInsight offers flexible scheduling options. You can automate content creation on an hourly, daily, or weekly basis.

= How can I manually generate a blog post? =

You can manually generate a blog post by clicking the "Create post manually" button on the plugin settings page. This utilizes AJAX to generate a new post without reloading the page..

== Screenshots ==

1. Plugin settings page - Configure API key, keywords, and other options.
2. Example generated blog post using Gutenberg blocks.

== Changelog ==

= 1.9 =
- Added:
    - Added support for selecting GPT-3.5, GPT-4 and GPT-4o models for content generation;
    - Users of the old GPT-3.5 won't be affected by the change as there is backwards compability;
    - Enhanced plugin settings UI for better user experience and clearer model selection;

- Fixed:
    - Code refinements for better modularity and maintainability.
    - Comprehensive error handling and logging.
    - Updated documentation to guide users through new features and configurations.

= 1.6 =
- Fixed
    - Select2;
	- Tone Select keeping custom value saved;
- Added
    - Minor description of some options;
	- Ko-fi button

= 1.5 =
- Changed
    - Code linting and cleanup;
    - Better form usability;
    - Minor quality of life changes;

= 1.4 =
- Added
	- Gemini API - Now you can use Google AI to create your posts!
    - A tone selector so you can define how the post should be written.
- Changed
    - Now it's possible to use wp-config variables to store the OpenAI and GeminiAI API keys. It's more secure than storing them in the database;
    - The admin menu and form was remade, so it's easier to work and don't get lost on so many options;
    - Minor changes for better usage;

= 1.0 =
- Added
	- Implemented image generation using OpenAI's DALL-E model based on provided prompts and keywords.
	- Introduced the option to select categories for posts, allowing users to specify relevant categories for generated content.
	- Provided an option to receive email notifications when a new post is created automatically.

- Changed
	- Refactored code for better readability, modularity, and maintainability.
	- Updated UI/UX for the settings page to improve user experience.
	- Better scheduled post generation feature, allowing users to schedule automatic post creation at hourly, daily, or weekly intervals.
	- Revised error handling and logging mechanisms for smoother operation.
	

- Fixed
	- Resolved issues related to API key validation and error handling.
	- Fixed bugs and glitches reported by users in the previous version.

= 0.9 =

- Bug Fixes, Quality of life updates - Expect 1.0 in a few days

= 0.8 =

- WordPress Review Guidelines fixes

= 0.5 =

- Initial release.
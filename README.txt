=== WP-AutoInsight ===
Contributors: phalkmin
Tags: openai, geminiai, blog, post, generator
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create blog posts automatically using the OpenAI and Gemini APIs!

== Description ==

The Automated Blog Content Creator plugin allows you to automatically generate blog posts using the power of the OpenAI API. With this plugin, you can harness the capabilities of ChatGPT to create SEO-focused articles on your WordPress site. Simply provide a set of keywords or subjects, and the plugin will generate engaging content for you.

Features:

- Seamless integration with the OpenAI API.
- Automatically generates blog posts based on specified keywords.
- Customizable character limit for generated posts.
- Ability to generate images related to the content.
- Supports Gutenberg blocks for easy content formatting.
- Option for daily automatic post creation.
- AJAX-powered manual post creation.
- Translatable strings for internationalization.

== Installation ==

1. Upload the `openai-api-blog-post-creator` directory to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the 'OpenAI Blog Post' menu in the WordPress admin sidebar to configure the plugin settings.

== Frequently Asked Questions ==

= How do I get an OpenAI API key? =

To use the OpenAI API, you need to sign up for an API key on the OpenAI website. Visit the OpenAI website and follow the instructions to obtain an API key.

= How can I specify the subjects or keywords for generating blog posts? =

In the plugin settings, you can enter a list of subjects or keywords separated by line breaks. The generated blog posts will be focused on these subjects.

= Can I customize the maximum character limit for generated posts? =

Yes, you can specify the maximum character limit for the generated posts in the plugin settings. The default limit is 200 characters.

= Can the plugin automatically create blog posts? =

Yes, the plugin provides an option for daily automatic post creation. When enabled, it will generate and save new blog posts based on the specified keywords.

= How can I manually generate a blog post? =

You can manually generate a blog post by clicking the "Create post manually" button on the plugin settings page. This will trigger an AJAX request to generate a new post.

== Screenshots ==

1. Plugin settings page - Configure API key, keywords, and other options.
2. Example generated blog post using Gutenberg blocks.

== Changelog ==

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
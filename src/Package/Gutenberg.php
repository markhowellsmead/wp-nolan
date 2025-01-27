<?php

namespace SayHello\Theme\Package;

/**
 * Adjustments for the Gutenberg Editor
 *
 * @author Mark Howells-Mead <mark@sayhello.ch>
 */
class Gutenberg
{
	public $min = false;

	public function __construct()
	{
		$this->min = !sht_theme()->debug;
	}

	public function run()
	{
		if (!function_exists('register_block_type')) {
			return;
		}
		add_action('enqueue_block_editor_assets', [$this, 'enqueueBlockAssets']);
		add_filter('block_categories_all', [$this, 'blockCategories']);
		add_action('after_setup_theme', [$this, 'themeSupports']);
		add_action('init', [$this, 'setScriptTranslations']);
		add_filter('admin_body_class', [$this, 'extendAdminBodyClass']);
		add_action('after_setup_theme', [$this, 'enqueueBlockStyles']);

		// Allows WordPress to conditionally load the individual block CSS files
		// if the block is on the page. Without this, all block CSS files will be
		// loaded on every page.
		add_filter('should_load_separate_core_block_assets', '__return_true');
	}

	public function themeSupports()
	{
		// Since WordPress 5.5: disallow block patterns delivered by Core
		remove_theme_support('core-block-patterns');

		add_editor_style('assets/styles/admin-editor.min.css');
	}

	public function enqueueBlockAssets()
	{
		if (file_exists(get_template_directory() . '/assets/gutenberg/blocks' . ($this->min ? '.min' : '') . '.js')) {
			$script_asset_path = get_template_directory() . '/assets/gutenberg/blocks.asset.php';
			$script_asset = file_exists($script_asset_path) ? require($script_asset_path) : ['dependencies' => [], 'version' => sht_theme()->version];
			wp_enqueue_script(
				'sht-gutenberg-script',
				get_template_directory_uri() . '/assets/gutenberg/blocks' . ($this->min ? '.min' : '') . '.js',
				$script_asset['dependencies'],
				$script_asset['version']
			);
		}

		if (file_exists(get_template_directory() . '/assets/fonts/woff2.css')) {
			wp_enqueue_style('sht-gutenberg-font', get_template_directory_uri() . '/assets/fonts/woff2.css', [], filemtime(get_template_directory() . '/assets/fonts/woff2.css'));
		}
	}

	/**
	 * https://github.com/SayHelloGmbH/hello-roots/wiki/Translation-in-JavaScript
	 *
	 * Make sure that the JSON files are at e.g.
	 * 'languages/sht-de_DE_formal-739d784e82179214dfd2a6c345374e30.json' or
	 * 'languages/sht-fr_FR-739d784e82179214dfd2a6c345374e30.json'
	 *
	 * mhm 28.1.2020
	 */
	public function setScriptTranslations()
	{
		wp_set_script_translations('sht-gutenberg-script', 'sht', get_template_directory() . '/languages');
	}

	public function blockCategories($categories)
	{
		return array_merge($categories, [
			[
				'slug'  => 'sht/blocks',
				'title' => _x('Blöcke von Say Hello', 'Custom block category name', 'sha'),
			],
		]);
	}

	public function isContextEdit()
	{
		return array_key_exists('context', $_GET) && $_GET['context'] === 'edit';
	}

	/**
	 * Add a CSS class name to the admin body, containing current post
	 * name and post type.
	 * @param  string $classes The pre-existing body class name/s
	 * @return string
	 */
	public function extendAdminBodyClass($classes)
	{
		global $post;
		if ($post->post_type ?? false && $post->post_name ?? false) {
			global $post;
			$classes .= ' post-type-' . $post->post_type . ' post-type-' . $post->post_type . '--' . $post->post_name;
		}
		return $classes;
	}

	public function enqueueBlockStyles()
	{
		$root_folder = get_template_directory() . '/assets/styles/blocks';
		$min = $this->min ? '.min' : '';

		// Get all available block namespaces.
		$block_namespaces = glob("{$root_folder}/*/");
		$block_namespaces = array_map(
			function ($type_path) {
				return basename($type_path);
			},
			$block_namespaces
		);

		foreach ($block_namespaces as $block_namespace) {

			// Get all available block styles of the given block namespace.
			$block_styles = glob("{$root_folder}/{$block_namespace}/*{$min}.css");
			$block_styles = array_map(
				function ($styles_path) use ($min) {
					return basename($styles_path, "{$min}.css");
				},
				$block_styles
			);

			foreach ($block_styles as $block_style) {
				if (empty($min) && strpos($block_style, '.min')) {
					continue;
				}
				wp_enqueue_block_style(
					$block_namespace . '/' . str_replace('.min', '', $block_style),
					array(
						'handle' => "{$block_namespace}-{$block_style}-styles",
						'src'    => get_theme_file_uri("assets/styles/blocks/{$block_namespace}/{$block_style}{$min}.css"),
						// Add "path" to allow inlining of block styles when possible.
						'path'   => get_theme_file_path("assets/styles/blocks/{$block_namespace}/{$block_style}{$min}.css"),
						'ver' => filemtime(get_theme_file_path("assets/styles/blocks/{$block_namespace}/{$block_style}{$min}.css"))
					),
				);
			}
		}
	}
}

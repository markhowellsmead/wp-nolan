<?php

namespace SayHello\Theme;

/**
 * Theme class which gets loaded in functions.php.
 * Defines the Starting point of the Theme and registers Packages.
 *
 * @author Mark Howells-Mead <mark@sayhello.ch>
 */
class Theme
{

	/**
	 * the instance of the object, used for singelton check
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Theme name
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Theme version
	 *
	 * @var string
	 */
	public $version = '';

	/**
	 * Debug mode
	 *
	 * @var bool
	 */
	public $debug = false;

	/**
	 * The Theme object, filled by the constructor
	 *
	 * @var array
	 */
	private $theme;

	public function __construct()
	{
		$this->theme = wp_get_theme();
	}

	public function run()
	{
		$this->loadClasses(
			[
				Block\CommentAuthorName::class,

				Block\Menu::class,
				Package\Assets::class,
				Package\Gutenberg::class,
				Package\Navigation::class,
			]
		);

		add_action('after_setup_theme', [$this, 'themeSupports']);
		add_action('comment_form_before', [$this, 'enqueueReplyScript']);
		add_action('wp_head', [$this, 'noJsScript']);
		add_filter('body_class', [$this, 'bodyClasses'], 10, 1);

		$this->cleanHead();
	}

	/**
	 * Creates an instance if one isn't already available,
	 * then return the current instance.
	 *
	 * @return object       The class instance.
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance) && !(self::$instance instanceof Theme)) {
			self::$instance = new Theme;
			self::$instance->name    = self::$instance->theme->name;
			self::$instance->version = self::$instance->theme->version;
			self::$instance->debug   = defined('WP_DEBUG') && WP_DEBUG;
		}

		return self::$instance;
	}

	/**
	 * Loads and initializes the provided classes.
	 *
	 * @param $classes
	 */
	private function loadClasses($classes)
	{
		foreach ($classes as $class) {
			$class_parts = explode('\\', $class);
			$class_short = end($class_parts);
			$class_set   = $class_parts[count($class_parts) - 2];

			if (!isset(sht_theme()->{$class_set}) || !is_object(sht_theme()->{$class_set})) {
				sht_theme()->{$class_set} = new \stdClass();
			}

			if (property_exists(sht_theme()->{$class_set}, $class_short)) {
				wp_die(sprintf(_x('Ein Problem ist geschehen im Theme. Nur eine PHP-Klasse namens «%1$s» darf dem Theme-Objekt «%2$s» zugewiesen werden.', 'Duplicate PHP class assignmment in Theme', 'sht'), $class_short, $class_set), 500);
			}

			sht_theme()->{$class_set}->{$class_short} = new $class();

			if (method_exists(sht_theme()->{$class_set}->{$class_short}, 'run')) {
				sht_theme()->{$class_set}->{$class_short}->run();
			}
		}
	}

	/**
	 * Allow the Theme to use additional core features
	 */
	public function themeSupports()
	{
		add_theme_support('automatic-feed-links');
		add_theme_support('custom-logo');
		add_theme_support('html5', ['comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script']);
		add_theme_support('post-thumbnails', ['post']);
		add_theme_support('title-tag');
		add_theme_support('post-formats', ['image', 'gallery', 'video']);
	}

	public function cleanHead()
	{
		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_print_styles', 'print_emoji_styles');
	}

	/**
	 * Adds a JS script to the head that removes 'no-js' from the html class list
	 */
	public function noJsScript()
	{
		echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>" . chr(10);
	}

	public function enqueueReplyScript()
	{
		if (is_singular() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}
	}

	/**
	 * Provides a function that adds custom
	 * css Classes to Website
	 *
	 * @param array $classes Default body classes
	 *
	 * @return array Containing all necessary Classes
	 */
	public function bodyClasses(array $classes)
	{
		$classes[] = 'no-js';

		if ($this->debug) {
			$classes[] = 'c-body--themedev';
		}

		return $classes;
	}
}

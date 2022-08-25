<?php

$post_id = get_the_ID();
$classNameBase = wp_get_block_default_classname($args['block']->name);
$align = $args['attributes']['align'] ?? '';

$media_size = 'medium';
switch ($align) {
	case 'wide':
		$media_size = 'gutenberg_wide';
		break;
	case 'full':
		$media_size = 'full';
		break;
}

$content = '';

if (!empty($video_url = get_field('video_ref', $post_id))) {

	if (is_singular('post') || is_singular('page')) {
		$video_player = wp_oembed_get($video_url);

		$content = sprintf(
			'<figure class="%1$s__figure %1$s__figure--video">%2$s</figure>',
			$classNameBase,
			$video_player,
		);
	} else {
		$content = sprintf(
			'<figure class="%1$s__figure %1$s__figure--%2$s"><a href="%5$s"><img class="%1$s__image" src="%3$s" alt="%4$s" /></a></figure>',
			$classNameBase,
			$media_size,
			pt_must_use_get_instance()->Package->Media->getVideoThumbnail($video_url),
			get_the_title($post_id),
			get_the_permalink($post_id)
		);
	}
} elseif (has_post_thumbnail($post_id)) {
	$image = wp_get_attachment_image(get_post_thumbnail_id($post_id), $media_size, false, ['class' => "{$classNameBase}__image"]);

	if (!empty($image)) {
		$content = sprintf('<figure class="%1$s__figure %1$s__figure--%2$s">%3$s</figure>', $classNameBase, $media_size, $image);
	}
}

if (empty($content)) {
	return;
}

if (!empty($align)) {
	$align = " align{$align}";
}

?>

<div class="<?php echo $classNameBase . $align; ?>">
	<?php echo $content; ?>
</div>

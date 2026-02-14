<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php wp_head(); ?>
    
    <?php 
    // --- BACKGROUND IMAGE COM CONTROLE DE VIDRO ---
    $custom_bg = get_theme_mod('custom_bg_image'); 
    $bg_opacity = get_theme_mod('bg_overlay_opacity', '0.55');
    $bg_blur = get_theme_mod('bg_overlay_blur', '12');

    if($custom_bg): ?>
    <style>
        .bg-image-layer {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-image: url('<?php echo esc_url($custom_bg); ?>');
            background-size: cover; background-position: center; z-index: -2;
        }
        .bg-glass-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, <?php echo esc_attr($bg_opacity); ?>);
            backdrop-filter: blur(<?php echo esc_attr($bg_blur); ?>px);
            z-index: -1;
        }
    </style>
    <?php endif; ?>
</head>
<body <?php body_class(get_theme_mod('theme_color_scheme', 'theme-light')); ?>>
    <?php if($custom_bg): ?>
        <div class="bg-image-layer"></div>
        <div class="bg-glass-overlay"></div>
    <?php endif; ?>
    <div class="app-container">
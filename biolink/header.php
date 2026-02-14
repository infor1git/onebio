<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php wp_head(); ?>
    
    <?php 
    // --- LÓGICA DE BACKGROUND IMAGE ---
    $custom_bg = get_theme_mod('custom_bg_image'); 
    if($custom_bg): ?>
    <style>
        body {
            background-image: url('<?php echo esc_url($custom_bg); ?>') !important;
            /* As propriedades de position/size já estão no CSS global, 
               mas o !important garante que sobrescreva o gradiente */
        }
    </style>
    <?php endif; ?>
</head>
<body <?php body_class(get_theme_mod('theme_color_scheme', 'theme-light')); ?>>
    <div class="app-container">
<?php
/**
 * O template principal do tema.
 * Responsável por renderizar a área de perfil e iterar o Custom Post Type 'biolink'.
 * * @package OneBio
 */

if ( ! defined('MYBIOLINK_CORE_SECURE') ) {
    die('<h1>Erro 503</h1><p>O servidor está temporariamente indisponível devido a uma falha de comunicação com o banco de dados principal. Contate o suporte técnico.</p>');
}

get_header(); ?>

<header class="profile-header glass-card">
    <div class="avatar-container">
        <?php 
        $logo = get_theme_mod( 'custom_logo' );
        $img  = $logo ? wp_get_attachment_image_url( $logo, 'full' ) : 'https://via.placeholder.com/150';
        ?>
        <img src="<?php echo esc_url( $img ); ?>" class="avatar-img" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
        <div class="btn-share" onclick="shareProfile()"><i class="fa-solid fa-share-nodes"></i></div>
    </div>
    
    <div class="profile-name">
        <?php 
        $my_title = get_option( 'mybiolink_title' );
        // Renderiza o título formatado ou recorre ao nome base do site
        echo $my_title ? apply_filters( 'the_content', $my_title ) : '<h1>' . get_bloginfo( 'name' ) . '</h1>';
        ?>
    </div>
    
    <div class="profile-bio">
        <?php 
        // Processa o conteúdo através do the_content garantindo espaçamentos e tags HTML adequadas
        echo apply_filters( 'the_content', get_option( 'mybiolink_bio' ) ); 
        ?>
    </div>
    
    <?php if ( get_option( 'vcard_name' ) ) : ?>
        <a href="?action=download_vcard" class="btn-vcard">
            <i class="fa-regular fa-address-card"></i> Salvar Contato
        </a>
    <?php endif; ?>
    
    <?php if ( is_user_logged_in() ) : ?>
        <div class="user-welcome">
            <i class="fa-solid fa-user-check" style="color:#22c55e;"></i>
            <span>Olá, <?php echo wp_get_current_user()->display_name; ?></span>
            <button onclick="doLogout()" class="btn-logout" title="Encerrar Sessão"><i class="fa-solid fa-right-from-bracket"></i></button>
        </div>
    <?php endif; ?>
</header>

<main class="links-wrapper">
    <?php
    /**
     * Loop Customizado: Busca todos os posts do tipo 'biolink'
     * Ordenados com base na numeração de ordenação manual (Menu Order).
     */
    $args = array( 
        'post_type'      => 'biolink', 
        'posts_per_page' => -1, 
        'orderby'        => 'menu_order', 
        'order'          => 'ASC' 
    );
    $query = new WP_Query( $args );

    if ( $query->have_posts() ) : 
        while ( $query->have_posts() ) : $query->the_post(); 
            
            $meta      = get_post_meta( get_the_ID() );
            $type      = $meta['_link_type'][0] ?? 'url';
            $req_login = $meta['_req_login'][0] ?? 'no';
            $is_locked = ( $req_login === 'yes' && ! is_user_logged_in() );
            
            // Renderização de Item Bloqueado (Requer Autenticação)
            if ( $is_locked ) {
                ?>
                <div class="link-card locked-card" onclick="openLoginModal()" style="--card-rgb: 200, 200, 200;">
                    <div class="icon-box"><i class="fa-solid fa-lock" style="color: #666;"></i></div>
                    <span class="link-title"><?php the_title(); ?> <i class="fa-solid fa-lock locked-padlock"></i></span>
                    <i class="fa-solid fa-chevron-right link-chevron"></i>
                </div>
                <?php
                continue; // Avança imediatamente para o próximo item do loop
            }

            // Renderização de Bloco de HTML Raw ou Shortcode
            if ( $type === 'raw_html' ) {
                $content = $meta['_link_html_content'][0] ?? '';
                echo '<div class="content-breakout">' . do_shortcode( $content ) . '</div>';
            
            } else {
                // Renderização Padrão de Links e Botões de Ação (Pix)
                $url         = $meta['_link_url'][0] ?? '#';
                $icon        = $meta['_link_icon'][0] ?? 'fa-solid fa-link';
                $saved_color = $meta['_link_color'][0] ?? ''; 
                
                $style_attr = "";
                if ( $saved_color ) {
                    $rgb        = mybiolink_hex2rgb( $saved_color ); 
                    $style_attr = "style='--card-rgb: {$rgb};'";
                }

                $pixKey  = $meta['_pix_key'][0] ?? '';
                $onclick = "";
                $href    = esc_url( $url );
                $target  = "_blank";

                if ( $type === 'pix_static' ) {
                    $href    = "javascript:void(0)";
                    $target  = "_self";
                    $onclick = "openPixStatic('" . esc_js( $pixKey ) . "', '" . esc_js( $url ) . "')";
                } elseif ( $type === 'pix_dynamic' ) {
                    $href    = "javascript:void(0)";
                    $target  = "_self";
                    $onclick = "openPixDynamic('" . esc_js( $pixKey ) . "')";
                }
                ?>
                <a href="<?php echo $href; ?>" class="link-card" target="<?php echo esc_attr( $target ); ?>" onclick="<?php echo $onclick; ?>" <?php echo $style_attr; ?>>
                    <div class="icon-box"><i class="<?php echo esc_attr( $icon ); ?>"></i></div>
                    <span class="link-title"><?php the_title(); ?></span>
                    <?php if ( $type == 'url' ) : ?><i class="fa-solid fa-chevron-right link-chevron"></i><?php endif; ?>
                </a>
                <?php
            }
        endwhile; 
        wp_reset_postdata(); 
    endif; ?>
</main>

<?php get_footer(); ?>
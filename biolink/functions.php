<?php
// === THEME SETUP ===
function mybiolink_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array('height' => 300, 'width' => 300));
}
add_action('after_setup_theme', 'mybiolink_setup');

// === SCRIPTS & AJAX ===
function mybiolink_scripts() {
    // Increment version to clear cache
    wp_enqueue_style('mybiolink-style', get_stylesheet_uri(), array(), '19.0.0');
    wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    wp_enqueue_script('qrcodejs', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', array(), '1.0', true);
    
    wp_localize_script('qrcodejs', 'mybiolink_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('login_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'mybiolink_scripts');

// === HELPER: HEX TO RGB ===
function mybiolink_hex2rgb($hex) {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "$r, $g, $b";
}

// === AJAX: LOGIN COMPLETO ===
function mybiolink_process_login() {
    check_ajax_referer('login_nonce', 'nonce');

    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    if (!is_email($email)) wp_send_json_error('E-mail inválido.');
    if (empty($password)) wp_send_json_error('Digite a senha.');

    $user = get_user_by('email', $email);

    if (!$user) {
        wp_send_json_error('E-mail não encontrado.');
    }

    $creds = array(
        'user_login'    => $user->user_login, 
        'user_password' => $password,
        'remember'      => true
    );

    $signon = wp_signon($creds, false);

    if (is_wp_error($signon)) {
        wp_send_json_error('Senha incorreta.');
    }

    wp_send_json_success('Login realizado com sucesso!');
}
add_action('wp_ajax_nopriv_mybiolink_process_login', 'mybiolink_process_login');
add_action('wp_ajax_mybiolink_process_login', 'mybiolink_process_login');

// === AJAX: LOGOUT ===
function mybiolink_logout() {
    wp_logout();
    wp_send_json_success();
}
add_action('wp_ajax_mybiolink_logout', 'mybiolink_logout');


// === CUSTOMIZER & RESET DE CORES (MODO DEFINITIVO) ===
function mybiolink_customize_register($wp_customize) {
    $wp_customize->add_section('mybiolink_design', array('title' => 'Aparência do Tema', 'priority' => 30));
    $wp_customize->add_setting('theme_color_scheme', array('default' => 'theme-light', 'transport' => 'refresh'));
    $wp_customize->add_control('theme_color_scheme', array(
        'label' => 'Paleta de Cores Base',
        'section' => 'mybiolink_design',
        'type' => 'select',
        'choices' => array(
            'theme-light' => 'Claro (Padrão)',
            'theme-dark' => 'Escuro',
            'theme-blue' => 'Ocean Blue',
            'theme-green' => 'Forest Green',
            'theme-lilac' => 'Lilac Glass',
        ),
        'description' => 'Atenção: Ao alterar e publicar uma nova paleta, todas as cores individuais dos botões serão apagadas para seguir o novo padrão.',
    ));

    $wp_customize->add_setting('custom_bg_image', array('default' => '', 'transport' => 'refresh'));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'custom_bg_image', array(
        'label'    => 'Imagem de Fundo (Background)',
        'section'  => 'mybiolink_design',
    )));
}
add_action('customize_register', 'mybiolink_customize_register');

// --- AÇÃO: RESETAR CORES NO BANCO DE DADOS ---
function mybiolink_reset_colors_on_theme_change($option_name, $old_value, $new_value) {
    // Garante que é a opção certa e que houve mudança
    if ($option_name === 'theme_color_scheme' && $old_value !== $new_value) {
        global $wpdb;
        // Executa limpeza direta no banco para máxima eficiência
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_link_color'");
    }
}
// 'updated_theme_mod' dispara quando uma opção de tema é salva com sucesso
add_action('updated_theme_mod', 'mybiolink_reset_colors_on_theme_change', 10, 3);


// === CPT & METABOXES ===
function create_link_cpt() {
    register_post_type('biolink', array(
        'labels' => array('name' => 'Meus Links / Conteúdo', 'singular_name' => 'Item', 'add_new_item' => 'Adicionar Novo', 'all_items' => 'Todos os Links'),
        'public' => true,
        'menu_icon' => 'dashicons-admin-links',
        'supports' => array('title', 'page-attributes'),
        'show_in_menu' => true,
    ));
}
add_action('init', 'create_link_cpt');

// --- COLUNAS NO ADMIN ---
function add_biolink_columns($columns) {
    $new_columns = array();
    foreach($columns as $key => $title) {
        if ($key == 'date') $new_columns['menu_order'] = 'Ordem';
        $new_columns[$key] = $title;
    }
    return $new_columns;
}
add_filter('manage_biolink_posts_columns', 'add_biolink_columns');

function show_biolink_custom_column($column, $post_id) {
    if ($column === 'menu_order') {
        $post = get_post($post_id);
        echo '<strong>' . $post->menu_order . '</strong>';
    }
}
add_action('manage_biolink_posts_custom_column', 'show_biolink_custom_column', 10, 2);

function make_biolink_column_sortable($columns) {
    $columns['menu_order'] = 'menu_order';
    return $columns;
}
add_filter('manage_edit-biolink_sortable_columns', 'make_biolink_column_sortable');

function mybiolink_admin_order($query) {
    if(!is_admin()) return;
    if($query->get('post_type') == 'biolink' && $query->is_main_query() && !$query->get('orderby')) {
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }
}
add_action('pre_get_posts', 'mybiolink_admin_order');

// --- METABOXES ---
function add_link_meta_boxes() {
    add_meta_box('link_details', 'Configuração do Item', 'render_link_meta_box', 'biolink', 'normal', 'high');
}
add_action('add_meta_boxes', 'add_link_meta_boxes');

function render_link_meta_box($post) {
    $meta = get_post_meta($post->ID);
    $type = $meta['_link_type'][0] ?? 'url';
    $url = $meta['_link_url'][0] ?? '';
    $icon = $meta['_link_icon'][0] ?? '';
    $color = $meta['_link_color'][0] ?? '';
    $pix_key = $meta['_pix_key'][0] ?? '';
    $html_content = $meta['_link_html_content'][0] ?? '';
    $req_login = $meta['_req_login'][0] ?? 'no';
    ?>
    <div style="background:#f9f9f9; padding:15px; border:1px solid #ddd;">
        <p><strong>Tipo de Item:</strong></p>
        <select name="link_type" id="link_type_selector" style="width:100%; padding:8px; margin-bottom:15px;">
            <option value="url" <?php selected($type, 'url'); ?>>Botão de Link</option>
            <option value="pix_static" <?php selected($type, 'pix_static'); ?>>Pix Estático</option>
            <option value="pix_dynamic" <?php selected($type, 'pix_dynamic'); ?>>Pix Dinâmico</option>
            <option value="raw_html" <?php selected($type, 'raw_html'); ?>>Conteúdo HTML/iFrame/Shortcode</option>
        </select>

        <div style="margin-bottom: 20px; background: #fff0f0; padding: 10px; border-left: 4px solid #ff4444;">
            <label style="font-size: 14px; font-weight: bold; color: #cc0000;">
                <input type="checkbox" name="req_login" value="yes" <?php checked($req_login, 'yes'); ?>>
                🔒 Bloquear este item (Requer Login com Senha)
            </label>
        </div>

        <div id="fields_button" style="display: <?php echo ($type == 'raw_html') ? 'none' : 'block'; ?>;">
            <div style="display:flex; gap:10px; margin-bottom:10px; align-items: flex-end;">
                <div style="flex:1">
                    <label>Ícone (FontAwesome class):</label>
                    <input type="text" name="link_icon" value="<?php echo esc_attr($icon); ?>" style="width:100%">
                </div>
                <div>
                    <label>Cor Personalizada:</label><br>
                    <input type="color" name="link_color" value="<?php echo esc_attr($color ? $color : '#ffffff'); ?>" style="width:60px; height:40px; cursor:pointer;">
                </div>
            </div>
            <p style="font-size:12px; color:#666; margin-top:-5px; margin-bottom:15px;">
                <em>Nota: Se você mudar a paleta de cores geral do site, esta cor será resetada para o padrão.</em>
            </p>
            
            <label>Link / URL Imagem:</label><input type="text" name="link_url" value="<?php echo esc_attr($url); ?>" style="width:100%; margin-bottom:10px;">
            <label>Chave Pix (apenas para tipos Pix):</label><input type="text" name="pix_key" value="<?php echo esc_attr($pix_key); ?>" style="width:100%">
        </div>

        <div id="fields_html" style="display: <?php echo ($type == 'raw_html') ? 'block' : 'none'; ?>;">
            <label><strong>Código (HTML/Shortcode):</strong></label><br>
            <textarea name="link_html_content" style="width:100%; height:150px; font-family:monospace;"><?php echo esc_textarea($html_content); ?></textarea>
        </div>
    </div>
    <script>
        document.getElementById('link_type_selector').addEventListener('change', function() {
            const isHtml = this.value === 'raw_html';
            document.getElementById('fields_button').style.display = isHtml ? 'none' : 'block';
            document.getElementById('fields_html').style.display = isHtml ? 'block' : 'none';
        });
    </script>
    <?php
}

function save_link_meta($post_id) {
    if(isset($_POST['link_type'])) update_post_meta($post_id, '_link_type', sanitize_text_field($_POST['link_type']));
    if(isset($_POST['link_url'])) update_post_meta($post_id, '_link_url', sanitize_text_field($_POST['link_url']));
    if(isset($_POST['link_icon'])) update_post_meta($post_id, '_link_icon', sanitize_text_field($_POST['link_icon']));
    if(isset($_POST['pix_key'])) update_post_meta($post_id, '_pix_key', sanitize_text_field($_POST['pix_key']));
    if(isset($_POST['link_html_content'])) update_post_meta($post_id, '_link_html_content', wp_kses_post($_POST['link_html_content']));
    
    $req = isset($_POST['req_login']) ? 'yes' : 'no';
    update_post_meta($post_id, '_req_login', $req);

    if(isset($_POST['link_color'])) {
        $c = sanitize_text_field($_POST['link_color']);
        update_post_meta($post_id, '_link_color', $c);
    }
}
add_action('save_post', 'save_link_meta');

// Opções Bio e vCard
function mybiolink_options_page() { add_menu_page('Perfil & Bio', 'Perfil & Bio', 'manage_options', 'mybiolink-settings', 'mybiolink_settings_html', 'dashicons-id', 2); }
add_action('admin_menu', 'mybiolink_options_page');
function mybiolink_settings_html() {
    if (isset($_POST['submit_mybiolink'])) {
        update_option('mybiolink_bio', wp_kses_post($_POST['mybiolink_bio']));
        update_option('vcard_name', sanitize_text_field($_POST['vcard_name']));
        update_option('vcard_phone', sanitize_text_field($_POST['vcard_phone']));
        update_option('vcard_email', sanitize_email($_POST['vcard_email']));
        echo '<div class="updated"><p>Salvo!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Editar Perfil</h1>
        <form method="post">
            <h3>Bio</h3> <?php wp_editor(get_option('mybiolink_bio'), 'mybiolink_bio', array('textarea_rows'=>4, 'media_buttons'=>false)); ?>
            <hr><h3>vCard</h3>
            <input type="text" name="vcard_name" placeholder="Nome" value="<?php echo esc_attr(get_option('vcard_name')); ?>" class="regular-text"><br><br>
            <input type="text" name="vcard_phone" placeholder="Telefone" value="<?php echo esc_attr(get_option('vcard_phone')); ?>" class="regular-text"><br><br>
            <input type="text" name="vcard_email" placeholder="Email" value="<?php echo esc_attr(get_option('vcard_email')); ?>" class="regular-text"><br><br>
            <button type="submit" name="submit_mybiolink" class="button button-primary">Salvar</button>
        </form>
    </div>
    <?php
}
?>
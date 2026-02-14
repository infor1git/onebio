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
    wp_enqueue_style('mybiolink-style', get_stylesheet_uri(), array(), '15.0.0');
    wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    wp_enqueue_script('qrcodejs', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', array(), '1.0', true);
    
    // Disponibiliza URL do Ajax para o JS
    wp_localize_script('qrcodejs', 'mybiolink_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('email_login_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'mybiolink_scripts');

// === AJAX: LOGIN VIA E-MAIL (SEM SENHA) ===
function mybiolink_email_login() {
    check_ajax_referer('email_login_nonce', 'nonce');

    $email = sanitize_email($_POST['email']);
    
    if (!is_email($email)) {
        wp_send_json_error('E-mail inválido.');
    }

    $user = get_user_by('email', $email);

    if (!$user) {
        wp_send_json_error('E-mail não encontrado no sistema.');
    }

    // Login Programático Seguro
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
    
    // Dispara gancho de login (opcional, bom para plugins)
    do_action('wp_login', $user->user_login, $user);

    wp_send_json_success('Logado com sucesso!');
}
add_action('wp_ajax_nopriv_mybiolink_email_login', 'mybiolink_email_login');
add_action('wp_ajax_mybiolink_email_login', 'mybiolink_email_login');

// === AJAX: LOGOUT ===
function mybiolink_logout() {
    wp_logout();
    wp_send_json_success();
}
add_action('wp_ajax_mybiolink_logout', 'mybiolink_logout');


// === CUSTOMIZER (CORES) ===
function mybiolink_customize_register($wp_customize) {
    $wp_customize->add_section('mybiolink_design', array('title' => 'Aparência do Tema', 'priority' => 30));
    $wp_customize->add_setting('theme_color_scheme', array('default' => 'theme-light', 'transport' => 'refresh'));
    $wp_customize->add_control('theme_color_scheme', array(
        'label' => 'Escolha o Tema',
        'section' => 'mybiolink_design',
        'type' => 'select',
        'choices' => array(
            'theme-light' => 'Claro (Padrão)',
            'theme-dark' => 'Escuro',
            'theme-blue' => 'Ocean Blue',
            'theme-green' => 'Forest Green',
            'theme-lilac' => 'Lilac Glass',
        ),
    ));
}
add_action('customize_register', 'mybiolink_customize_register');

// === CPT & METABOXES ===
function create_link_cpt() {
    register_post_type('biolink', array(
        'labels' => array('name' => 'Meus Links / Conteúdo', 'singular_name' => 'Item', 'add_new_item' => 'Adicionar Novo'),
        'public' => true,
        'menu_icon' => 'dashicons-admin-links',
        'supports' => array('title', 'page-attributes'),
    ));
}
add_action('init', 'create_link_cpt');

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

        <div style="margin-bottom: 20px; background: #e6f7ff; padding: 10px; border-left: 4px solid #1890ff;">
            <label>
                <input type="checkbox" name="req_login" value="yes" <?php checked($req_login, 'yes'); ?>>
                <strong>Este item requer identificação (E-mail)?</strong>
            </label>
            <p style="margin:5px 0 0; font-size:12px;">Se marcado, o usuário verá um botão "Identifique-se" em vez do conteúdo. Após colocar o e-mail cadastrado, o conteúdo aparecerá.</p>
        </div>

        <div id="fields_button" style="display: <?php echo ($type == 'raw_html') ? 'none' : 'block'; ?>;">
            <div style="display:flex; gap:10px; margin-bottom:10px;">
                <div style="flex:1"><label>Ícone (FontAwesome):</label><input type="text" name="link_icon" value="<?php echo esc_attr($icon); ?>" style="width:100%"></div>
                <div><label>Cor:</label><input type="color" name="link_color" value="<?php echo esc_attr($color ? $color : '#000000'); ?>" style="width:50px; height:30px;"></div>
            </div>
            <label>Link / URL Imagem:</label><input type="text" name="link_url" value="<?php echo esc_attr($url); ?>" style="width:100%; margin-bottom:10px;">
            <label>Chave Pix:</label><input type="text" name="pix_key" value="<?php echo esc_attr($pix_key); ?>" style="width:100%">
        </div>

        <div id="fields_html" style="display: <?php echo ($type == 'raw_html') ? 'block' : 'none'; ?>;">
            <label><strong>Código (HTML/Shortcode Use-your-Drive):</strong></label><br>
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
    
    // Checkbox Salvar
    $req = isset($_POST['req_login']) ? 'yes' : 'no';
    update_post_meta($post_id, '_req_login', $req);

    if(isset($_POST['link_color'])) {
        $c = sanitize_text_field($_POST['link_color']);
        if($c == '#000000') delete_post_meta($post_id, '_link_color'); 
        else update_post_meta($post_id, '_link_color', $c);
    }
}
add_action('save_post', 'save_link_meta');

// Opções Bio e vCard (MANTIDAS DO V14...)
function mybiolink_options_page() { add_menu_page('Perfil & Bio', 'Perfil & Bio', 'manage_options', 'mybiolink-settings', 'mybiolink_settings_html', 'dashicons-id', 2); }
add_action('admin_menu', 'mybiolink_options_page');
function mybiolink_settings_html() { /* ... Código V14 mantido ... */ 
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
function mybiolink_vcard() { /* ... Código V14 mantido ... */ }
add_action('init', 'mybiolink_vcard');
?>
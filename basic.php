/*Remove o Editor Gutemberg presente no WP 5.6.2*/
add_filter('use_block_editor_for_post', '__return_false');

/*Remove as Widgets do Dashboard*/
function remove_dashboard_widgets() {
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side'); // Rascunho rápido
    remove_meta_box('dashboard_primary', 'dashboard', 'side'); // Novidades e eventos do WordPress
    remove_meta_box('dashboard_activity', 'dashboard', 'normal'); // Atividade
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal'); // Agora
}
add_action('wp_dashboard_setup', 'remove_dashboard_widgets');

/*Altera o fundo, imagem da logo e o link de redicionamento da mesma*/
function login_background() {
    echo '<style type="text/css">
        body { background: #ffffff }
		.login #nav a { color: #ffffff;} 
		.login #nav a, .login #backtoblog a { color: #ffffff; }
		 .login #backtoblog a:hover, .login #nav a:hover, .login h1 a:hover {
            color: #ffffff; /* Mantém branco ao passar o mouse */
            transition: all 0.5s ease-out; /* Transição suave */
        }
    </style>';
}
add_action('login_head', 'login_background');

/*Remove o seletor de idiomas na tela de login*/
add_filter( 'login_display_language_dropdown', '__return_false' );

function custom_login_logo() {
    echo '<style type="text/css">
        #login h1 a, .login h1 a {
	    /*o caminho completo da url da logo do site*/
            background-image: url(https://seusite.com/wp-content/uploads/2023/10/Logo.svg); 
            height: 100px; /* Altere a altura caso precisar */
            width: 100%; /* Utilize 100% principalmente por causa do responsivo*/
            background-size: contain; /* Ajusta conforme necessidade*/
        }
    </style>';
}

add_action('login_enqueue_scripts', 'custom_login_logo');

function custom_login_logo_url() {
    return home_url(); // Define o link para a página inicial do site
}
add_filter('login_headerurl', 'custom_login_logo_url');

function my_login_logo_title() {
return get_bloginfo('name');
}
add_filter('login_headertitle', 'my_login_logo_title' );

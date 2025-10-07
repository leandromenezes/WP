add_filter('get_avatar', 'avatar_personalizado_id7', 1, 5);
function avatar_personalizado_id7($avatar, $id_or_email, $size, $default, $alt) {
    // Pega o ID do usuário (funciona para comentários e perfis)
    if (is_numeric($id_or_email)) {
        $user_id = absint($id_or_email);
    } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
        $user_id = absint($id_or_email->user_id);
    } elseif (is_object($id_or_email) && !empty($id_or_email->ID)) {
        $user_id = absint($id_or_email->ID);
    } else {
        // Se não conseguir o ID, tenta pelo email
        $user = get_user_by('email', $id_or_email);
        $user_id = $user ? $user->ID : 0;
    }

    // Avatar apenas para o ID 7
    if ($user_id == 7) {
        $avatar_url = 'https://seusite.com/wp-content/uploads/2023/10/avatar-id7.jpg';
        $avatar = '<img alt="' . esc_attr($alt) . '" src="' . esc_url($avatar_url) . '" class="avatar avatar-' . esc_attr($size) . ' photo" height="' . esc_attr($size) . '" width="' . esc_attr($size) . '" />';
    }

    return $avatar;
}

/*
Como usar:

Cole o código no functions.php do seu tema ativo.
Substitua a URL em $avatar_url pela URL real da imagem para o ID 7 (Substituir o 7 pelo ID do seu usuario).
*/

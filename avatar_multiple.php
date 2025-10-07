/*
Como usar:

Copie e cole esse código no arquivo functions.php do seu tema ativo (em Aparência > Editor de tema).
Substitua os valores:

Os IDs (ex: 1, 2, 3) pelos IDs reais dos seus usuários.
As URLs das imagens pelas URLs completas das suas imagens (faça upload delas via Mídia > Adicionar nova para ficarem na pasta /uploads/).
*/

add_filter('get_avatar', 'avatar_personalizado_por_usuario', 1, 5);
function avatar_personalizado_por_usuario($avatar, $id_or_email, $size, $default, $alt) {
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

    // Define as imagens para cada usuário (substitua pelos IDs e URLs reais)
    $avatars_personalizados = array(
        1 => 'https://seusite.com/wp-content/uploads/2023/10/avatar-usuario1.jpg',  // ID 1
        2 => 'https://seusite.com/wp-content/uploads/2023/10/avatar-usuario2.jpg',  // ID 2
        3 => 'https://seusite.com/wp-content/uploads/2023/10/avatar-usuario3.jpg'   // ID 3
    );

    // Se o usuário tem um avatar personalizado, usa ele
    if (isset($avatars_personalizados[$user_id])) {
        $avatar_url = $avatars_personalizados[$user_id];
        $avatar = '<img alt="' . esc_attr($alt) . '" src="' . esc_url($avatar_url) . '" class="avatar avatar-' . esc_attr($size) . ' photo" height="' . esc_attr($size) . '" width="' . esc_attr($size) . '" />';
    }

    return $avatar;
}


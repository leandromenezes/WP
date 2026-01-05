add_filter('get_avatar_data', 'avatar_fixo_alexandre', 100, 2);

function avatar_fixo_alexandre($args, $id_or_email) {
    $user_id = 0;

    // Pega o ID do usuário de qualquer forma (post, comentário, etc.)
    if (is_numeric($id_or_email)) {
        $user_id = absint($id_or_email);
    } elseif (is_object($id_or_email)) {
        if (!empty($id_or_email->user_id)) {
            $user_id = absint($id_or_email->user_id);
        } elseif (!empty($id_or_email->ID)) {
            $user_id = absint($id_or_email->ID);
        }
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        if ($user) $user_id = $user->ID;
    }

    if ($user_id === 7) {  // Troque 7 pelo ID do usuário desejado

        $args['url'] = 'https://maismottoristas.com.br/wp-content/uploads/Alexandre.webp';
        $args['found_avatar'] = true; // Força o WordPress a usar essa imagem
    }

    return $args;
}

/*
Como usar:

Cole o código no functions.php do seu tema ativo.
Substitua a URL em $avatar_url pela URL real da imagem para o ID 7 (Substituir o 7 pelo ID do seu usuario).
*/

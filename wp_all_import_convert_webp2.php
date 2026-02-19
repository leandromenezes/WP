<?php
/*
* Função que converte para o webp e aplica o nome do arquivo na imagem.
*
*/
// ─── 1. Helper: converte qualquer idioma para slug ASCII ───────────────────
function slugify_any_language(string $text): string {
    if (empty($text)) return 'image';

    // Tenta usar a extensão intl (melhor opção)
    if (class_exists('Transliterator')) {
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
        if ($transliterator) {
            $text = $transliterator->transliterate($text);
        }
    }

    // Fallback: remove qualquer coisa que não seja ASCII legível
    $text = preg_replace('/[^\x20-\x7E]/u', '', $text);

    // Aplica sanitização padrão do WordPress
    $text = sanitize_title($text);

    // Se ainda ficou vazio (texto 100% CJK sem transliteração disponível)
    if (empty($text)) {
        // Usa uma representação fonética simples via hash curto
        $text = 'img-' . substr(md5(mb_convert_encoding($text ?? '', 'UTF-8')), 0, 8);
    }

    return $text;
}

// ─── 2. Função central de conversão para WebP ─────────────────────────────
function convert_image_to_webp(string $file_path, int $attachment_id, string $new_name = ''): string {
    if (!file_exists($file_path)) return $file_path;

    $info = pathinfo($file_path);
    $ext  = strtolower($info['extension'] ?? '');

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'jfif'])) return $file_path;

    // Se recebeu um nome novo, usa ele — senão mantém o nome original
    $filename  = $new_name ? sanitize_title($new_name) : $info['filename'];
    $webp_path = $info['dirname'] . '/' . $filename . '.webp';

    // Evita colisão de nomes (ex: produto-nome.webp já existe de outro post)
    $counter = 1;
    while (file_exists($webp_path) && $webp_path !== $info['dirname'] . '/' . $info['filename'] . '.webp') {
        $webp_path = $info['dirname'] . '/' . $filename . '-' . $counter . '.webp';
        $counter++;
    }

    if (!file_exists($webp_path)) {
        $image = null;

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
            case 'jfif':
                $image = @imagecreatefromjpeg($file_path);
                break;
            case 'png':
                $image = @imagecreatefrompng($file_path);
                if ($image) {
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }
                break;
        }

        if (!$image) return $file_path;

        $quality     = 95;
        $min_quality = 80;
        $orig_size   = filesize($file_path);

        do {
            imagewebp($image, $webp_path, $quality);
            $quality -= 5;
        } while (
            file_exists($webp_path) &&
            filesize($webp_path) > $orig_size &&
            $quality >= $min_quality
        );

        imagedestroy($image);

        if (!file_exists($webp_path)) return $file_path;
    }

    @unlink($file_path);

    if ($attachment_id > 0) {
        $upload_dir = wp_upload_dir();
        $relative   = ltrim(str_replace($upload_dir['basedir'], '', $webp_path), '/');

        update_post_meta($attachment_id, '_wp_attached_file', $relative);

        // Atualiza também o título do attachment para o novo nome
        wp_update_post([
            'ID'             => $attachment_id,
            'post_mime_type' => 'image/webp',
            'post_title'     => $filename,
            'post_name'      => $filename,
        ]);

        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $metadata = wp_generate_attachment_metadata($attachment_id, $webp_path);
        wp_update_attachment_metadata($attachment_id, $metadata);
    }

    return $webp_path;
}

// ─── 3. Hook: imagem destaque + imagens no conteúdo ───────────────────────
add_action('pmxi_saved_post', function(int $post_id) {
    $post     = get_post($post_id);
    $slug     = $post->post_name ?: sanitize_title($post->post_title);

    // Imagem destaque
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        $file = get_attached_file($thumbnail_id);
        if ($file) {
            convert_image_to_webp($file, $thumbnail_id, $slug);
        }
    }
}, 10, 1);

// ─── 4. Hook: imagens de galeria ──────────────────────────────────────────
add_action('pmxi_gallery_image', function(int $attachment_id, string $image_url, int $post_id) {
    $post  = get_post($post_id);
    $slug  = $post->post_name ?: sanitize_title($post->post_title);
    $file  = get_attached_file($attachment_id);

    if ($file) {
        convert_image_to_webp($file, $attachment_id, $slug);
    }
}, 10, 3);

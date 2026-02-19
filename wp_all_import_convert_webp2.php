<?php

/**
 * Função central de conversão para WebP
 */
function convert_image_to_webp(string $file_path, int $attachment_id): string {
    if (!file_exists($file_path)) return $file_path;

    $info = pathinfo($file_path);
    $ext  = strtolower($info['extension'] ?? '');

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'jfif'])) return $file_path;

    $webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';

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

    // Remove o arquivo original
    @unlink($file_path);

    if ($attachment_id > 0) {
        $upload_dir = wp_upload_dir();

        // Atualiza caminho do arquivo
        $relative = ltrim(str_replace($upload_dir['basedir'], '', $webp_path), '/');
        update_post_meta($attachment_id, '_wp_attached_file', $relative);

        // Atualiza mime type
        wp_update_post([
            'ID'             => $attachment_id,
            'post_mime_type' => 'image/webp',
        ]);

        // Regenera metadados (thumbnails, dimensões, etc.)
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $metadata = wp_generate_attachment_metadata($attachment_id, $webp_path);
        wp_update_attachment_metadata($attachment_id, $metadata);
    }

    return $webp_path;
}

/**
 * Hook para imagem destaque importada pelo WP All Import
 */
add_action('pmxi_saved_post', function(int $post_id) {
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if (!$thumbnail_id) return;

    $file = get_attached_file($thumbnail_id);
    if ($file) {
        convert_image_to_webp($file, $thumbnail_id);
    }
}, 10, 1);

/**
 * Hook para imagens de galeria importadas pelo WP All Import
 */
add_action('pmxi_gallery_image', function(int $attachment_id, string $image_url, int $post_id) {
    $file = get_attached_file($attachment_id);
    if ($file) {
        convert_image_to_webp($file, $attachment_id);
    }
}, 10, 3);

/**
 * Converte imagens dentro do conteúdo HTML (<img src="...">)
 * e atualiza as URLs no banco de dados
 */
add_action('pmxi_saved_post', function(int $post_id) {
    $post = get_post($post_id);
    if (!$post || empty($post->post_content)) return;

    $content = $post->post_content;
    $upload_dir = wp_upload_dir();
    $base_url   = $upload_dir['baseurl'];
    $base_dir   = $upload_dir['basedir'];

    // Busca todas as URLs de imagem no conteúdo
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

    if (empty($matches[1])) return;

    $updated = false;

    foreach ($matches[1] as $img_url) {
        // Apenas imagens do próprio servidor
        if (strpos($img_url, $base_url) === false) continue;

        $file_path = str_replace($base_url, $base_dir, $img_url);
        $info = pathinfo($file_path);
        $ext  = strtolower($info['extension'] ?? '');

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'jfif'])) continue;
        if (!file_exists($file_path)) continue;

        // Tenta encontrar o attachment_id pela URL
        $attachment_id = attachment_url_to_postid($img_url);

        $webp_path = convert_image_to_webp($file_path, (int) $attachment_id);

        if ($webp_path !== $file_path) {
            $webp_url = str_replace($base_dir, $base_url, $webp_path);
            $content  = str_replace($img_url, $webp_url, $content);
            $updated  = true;
        }
    }

    if ($updated) {
        wp_update_post([
            'ID'           => $post_id,
            'post_content' => $content,
        ]);
    }
}, 20, 1); // priority 20 para rodar após o hook da featured image

<?php

// 1. Força a conversão WebP também nas imagens baixadas pelo WP All Import
add_filter('wp_all_import_image_filepath', 'force_convert_to_webp_on_wpai', 10, 4);

function force_convert_to_webp_on_wpai($file_path, $attachment_id, $image_url, $import_id) {
    // Só processa se o arquivo realmente existe e é JPG/PNG
    if (!file_exists($file_path)) {
        return $file_path;
    }

    $info = pathinfo($file_path);
    $ext  = strtolower($info['extension']);

    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
        return $file_path;
    }

    // Reusa exatamente a mesma lógica da sua função original
    $webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';

    // Se já existe o .webp (pode ter sido criado antes), usa ele
    if (file_exists($webp_path) && filesize($webp_path) < filesize($file_path)) {
        unlink($file_path); // remove o JPG/PNG original
        update_post_meta($attachment_id, '_wp_attached_file', ltrim(str_replace(ABSPATH, '', $webp_path), '/'));
        wp_update_attachment_metadata($attachment_id, [
            'file' => ltrim(str_replace(wp_upload_dir()['basedir'], '', $webp_path), '/'),
            'width'  => null,
            'height' => null,
            'sizes'  => [],
        ]);
        return $webp_path;
    }

    // === Conversão com redução progressiva de qualidade (igual à sua função) ===
    if (!function_exists('imagecreatefromjpeg')) {
        error_log('GD library not available for WebP conversion during WP All Import');
        return $file_path;
    }

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($file_path);
            break;
        case 'png':
            $image = imagecreatefrompng($file_path);
            // Para PNG com transparência, preserva alfa
            imagesavealpha($image, true);
            break;
    }

    if (!$image) {
        return $file_path;
    }

    $quality = 95;
    $min_quality = 80;

    do {
        imagewebp($image, $webp_path, $quality);
        $quality -= 5;
    } while (filesize($webp_path) > filesize($file_path) && $quality >= $min_quality);

    // Se mesmo na qualidade mínima ainda é maior? mantém original
    if (filesize($webp_path) > filesize($file_path)) {
        @unlink($webp_path);
        imagedestroy($image);
        return $file_path;
    }

    // WebP é menor → substitui tudo
    unlink($file_path); // remove JPG/PNG

    // Atualiza o caminho no banco de dados
    $new_file_path_for_db = str_replace(wp_upload_dir()['basedir'], '', $webp_path);
    update_post_meta($attachment_id, '_wp_attached_file', ltrim($new_file_path_for_db, '/'));

    // Limpa os metadados antigos de tamanho (força regeneração se precisar)
    delete_post_meta($attachment_id, '_wp_attachment_metadata');
    
    imagedestroy($image);

    return $webp_path;
}

add_action('pmxi_gallery_image', function($attachment_id, $image_url, $post_id) {
    $file = get_attached_file($attachment_id);
    if ($file) {
        force_convert_to_webp_on_wpai($file, $attachment_id, $image_url, 0);
    }
}, 10, 3);

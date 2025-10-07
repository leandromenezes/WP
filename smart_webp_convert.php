<?php 

function convert_to_webp($file) {
    $file_path = $file['file'];
    $file_type = $file['type'];

    // Only for JPEG, PNG, and GIF images
    if (in_array($file_type, ['image/jpeg', 'image/png', 'image/jpg'])) {
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);

        // Verifica se a biblioteca GD está disponível
        if (!function_exists('gd_info')) {
            error_log("GD library is not available.");
            return $file; // Return the original file if GD is not available
        }

        // Define quality thresholds
        $initial_quality = 95; // Start trying with 95%
        $min_quality = 80; //Minimum acceptable threshold is 80%

        $image = null;

        // Load the image based on its type
        switch ($file_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                break;
            default:
                error_log("Unsupported image type: $file_type");
                return $file;
        }

        // If the image was successfully loaded
        if ($image) {
            $quality = $initial_quality;
            imagewebp($image, $webp_path, $quality);

            // Reduce quality if WebP is larger than the original
            while (filesize($webp_path) > filesize($file_path) && $quality > $min_quality) {
                $quality -= 5;
                imagewebp($image, $webp_path, $quality);
            }

            // If even at minimum quality the WebP is larger, delete the WebP and keep the original
            if (filesize($webp_path) > filesize($file_path)) {
                unlink($webp_path);
            } else {
                //Remove the original image and replace it with the WebP
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                $file['file'] = $webp_path;
                $file['type'] = 'image/webp';
            }

            //Free memory
            imagedestroy($image);
        } else {
            error_log("Failed to create image from: $file_path");
        }
    }

    return $file;
}

add_filter('wp_handle_upload', 'convert_to_webp');

/*
* Essa função exibe apenas a primeira categoria marcada
* <body class="... categoria-tecnologia categoria-noticias">
*/

add_filter( 'body_class', 'add_category_name_to_body_class' );

function add_category_name_to_body_class( $classes ) {
    if ( is_single() ) {
        $categories = get_the_category();
        if ( ! empty( $categories ) ) {
            foreach ( $categories as $category ) {
                if ( $category->parent == 0 ) {
                    $classes[] = 'categoria-' . $category->slug;
                    break;
                }
            }
        }
    }
    return $classes;
}

/*
* Essa função exibe todas as categorias que o artigo fizer parte
* <body class="... categoria-tecnologia">
*/

add_filter( 'body_class', 'add_category_name_to_body_class' );

function add_category_name_to_body_class( $classes ) {
    if ( is_single() ) {
        $categories = get_the_category();
        if ( ! empty( $categories ) ) {
            foreach ( $categories as $category ) {
                $classes[] = 'categoria-' . $category->slug;
            }
        }
    }
    return $classes;
}

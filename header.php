<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head><?php wp_head(); ?></head>
<body  <?php body_class(); ?> >

<ul>
    <li>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
            <?php bloginfo( 'name' ); ?>
        </a>
    </li>
    <li>

    </li>
    <li>
        <a href="<?php echo esc_url( home_url( '/inexistent-url/' ) ); ?>" rel="home">
            404
        </a>
    </li>
</ul>

<?php wp_body_open(); ?>
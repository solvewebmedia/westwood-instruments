<?php
function solve_enqueue_scripts_styles()
{
    // Base Theme Enqueue
    wp_enqueue_style("solve-style-base", get_theme_file_uri() . "/dist/style-base.min.css", array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'solve_enqueue_scripts_styles');
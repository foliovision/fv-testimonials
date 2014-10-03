<?php

require_once( FVTESTIMONIALS_ROOT . 'model/fv-testimonials-class.php' );
add_shortcode('testimonials', 'show_testimonials_handle');

function show_testimonials_handle( $atts ) {
  /// End of addition
  extract(
    shortcode_atts(
      array(
        'category' => 0,
        'limit' => 0,
        'template' => 0,
        'include' => '',
        'exclude' => '',
        'offset' => 0,
        'image' => 'medium',
        'show' => '',
        'length' => '',
        'orderby' => ''
        ), $atts, 'testimonials' )
  );
  
  $objTestimonials = new FV_Testimonials();
  $output = $objTestimonials->show_testimonials($category, (int)$limit, $template, $image, $include, $exclude, $offset, $show, $length, $orderby);
  
  return $output;
}

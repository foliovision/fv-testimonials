<?php

define( 'FV_JC_NEWS_PREFIX', 'about');
define( 'FV_JC_NEWS_CAT_PREFIX', 'about/clients');
define( 'FV_JC_NEWS_CAT_NAME', 'testimonial_category');
define( 'FV_JC_NEWS_TYPE', 'testimonial');


add_filter( 'autoblog_pre_post_insert', 'fv_jc_autoblog_pre_post_insert', 10, 3 );

function fv_jc_autoblog_pre_post_insert( $aPostData ) {
  //$sFile = 'autoblog.log';
  $args = func_get_args();

  $aAutoblog = $args[1];
  $aFeedItem = $args[2];
  
  /*$objTerm = get_term_by( 'name', $aAutoblog['title'], FV_JC_NEWS_CAT_PREFIX );
  if( !$objTerm ) {
    if( $objTerm = wp_insert_term( $aAutoblog['title'], FV_JC_NEWS_CAT_PREFIX ) ) {
      $term_id = $objTerm['term_id'];
    }
  } else {
    $term_id = $objTerm->term_id;
  }
  
  $aPostData["tax_input"][FV_JC_NEWS_CAT_PREFIX] = intval($term_id);*/

  global $fvseo;
  if( method_exists($fvseo,'GeneratePostSlug') ) {
    $aPostData['post_name'] = $fvseo->GeneratePostSlug( sanitize_title($aPostData['post_title']), -1 );
  }
  
  return $aPostData;
}


add_filter( 'autoblog_post_post_insert', 'fv_jc_autoblog_post_post_insert', 10, 3 );
function fv_jc_autoblog_post_post_insert($post_id, $aBlog, $item) {
  //if ($_SERVER['REMOTE_ADDR'] == '158.195.213.206') {
    $objTerm = get_term_by( 'name', $aBlog['title'], FV_JC_NEWS_CAT_PREFIX );
    if( !$objTerm ) {
      if( $objTerm = wp_insert_term( $aBlog['title'], FV_JC_NEWS_CAT_PREFIX ) ) {
        $term_id = $objTerm['term_id'];
      }
    } else {
      $term_id = $objTerm->term_id;
    }

    wp_set_post_terms($post_id, array($term_id), FV_JC_NEWS_CAT_PREFIX);
  //}
}


add_action( 'init', 'fv_jc_news_post_type' );
function fv_jc_news_post_type() {
   register_post_type(
      FV_JC_NEWS_TYPE,
      array(
         'labels' => array(
            'name' => __( 'Testimonials' ),
            'singular_name' => __( 'Testimonial' ),
         ),
         'public' => true,
         'has_archive' => true,
         'rewrite' => true,
         'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'comments' ),
         'exclude_from_search' => true
      )
   );

   register_taxonomy(FV_JC_NEWS_CAT_NAME, FV_JC_NEWS_TYPE, array(
      'hierarchical' => true,
      'labels' => array(
         'name' => __( 'Categories' ),
         'singular_name' => __( 'Category' )
      ),
      'show_ui' => true,
      'update_count_callback' => '_update_post_term_count',
      'query_var' => true,
      'rewrite' => array( 'slug' => FV_JC_NEWS_PREFIX, 'hierarchical' => true )
   ));

}


/***
  *
  The best rewrite for hierarchical custom taxonomy
  Version: 0.2
  *
  */

/*
When should the rewrite rules be re-generated?
*/
register_activation_hook(__FILE__,'fv_jc_news_rewrite_rules_refresh');

add_action('create_'.FV_JC_NEWS_CAT_PREFIX,'testimonial_rewrite_rules_refresh');
add_action('edit_'.FV_JC_NEWS_CAT_PREFIX,'testimonial_rewrite_rules_refresh');
add_action('delete_'.FV_JC_NEWS_CAT_PREFIX,'testimonial_rewrite_rules_refresh');

function testimonial_rewrite_rules_refresh() {
  add_option('testimonial_rewrite_rules_flush', 'true');
}


/*
What to do when the plugin gets de-activated?
*/
register_deactivation_hook(__FILE__,'testimonial_deactivate');

function testimonial_deactivate() {
  remove_filter('testimonial_rewrite_rules_refresh', 'testimonial_rewrite_rules_filter'); // We don't want to insert our custom rules again
  delete_option('testimonial_rewrite_rules_flush');
}


/*
Check if we need to regenerate the rules
*/
add_action('init', 'testimonial_permalinks');
function testimonial_permalinks() {  
  if (get_option('fv_jc_news_rewrite_rules_flush') == 'true') {
    flush_rewrite_rules(false);
    delete_option('fv_jc_news_rewrite_rules_flush');
  }	
}

/*
Specify the rules here
*/
add_filter( FV_JC_NEWS_TYPE.'_rewrite_rules', 'testimonial_rewrite_rules_filter' );
function testimonial_rewrite_rules_filter( $rules ) {
  $new_rewrite = array();

  $fv_photos_categories = get_terms( FV_JC_NEWS_CAT_NAME , array( 'hide_empty' => true ));
  var_dump($fv_photos_categories);
  foreach( $fv_photos_categories as $item ) {
    $link = '';
    //$link = trim( str_replace( home_url(), '', get_term_link( $item, FV_JC_NEWS_CAT_NAME ) ), '/' );
   
    /*$new_rewrite['('.$link.')/(\d\d\d\d)/(\d\d)/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.FV_JC_NEWS_CAT_PREFIX.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&feed=$matches[4]';
    $new_rewrite['('.$link.')/(\d\d\d\d)/(\d\d)/page/?([0-9]{1,})/?$'] = 'index.php?'.FV_JC_NEWS_CAT_PREFIX.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]&paged=$matches[4]';
    $new_rewrite['('.$link.')/(\d\d\d\d)/(\d\d)/?$'] = 'index.php?'.FV_JC_NEWS_CAT_PREFIX.'=$matches[1]&year=$matches[2]&monthnum=$matches[3]';        
    
    $new_rewrite['('.$link.')/(\d\d\d\d)/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.FV_JC_NEWS_CAT_PREFIX.'=$matches[1]&year=$matches[2]&feed=$matches[3]';
    $new_rewrite['('.$link.')/(\d\d\d\d)/page/?([0-9]{1,})/?$'] = 'index.php?'.FV_JC_NEWS_CAT_PREFIX.'=$matches[1]&year=$matches[2]&paged=$matches[3]';
    $new_rewrite['('.$link.')/(\d\d\d\d)/?$'] = 'index.php?'.FV_JC_NEWS_CAT_PREFIX.'=$matches[1]&year=$matches[2]';    
    
    $new_rewrite['('.$link.')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.FV_JC_NEWS_CAT_PREFIX.'=$matches[1]&feed=$matches[2]';
    $new_rewrite['('.$link.')/page/?([0-9]{1,})/?$'] = 'index.php?'.FV_JC_NEWS_CAT_PREFIX.'=$matches[1]&paged=$matches[2]';
    $new_rewrite['('.$link.')/?$'] = 'index.php?'.FV_JC_NEWS_CAT_PREFIX.'=$matches[1]';*/
    $new_rewrite[FV_JC_NEWS_PREFIX . '('.$link.')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.FV_JC_NEWS_CAT_NAME.'=$matches[1]&feed=$matches[2]';
    $new_rewrite[FV_JC_NEWS_PREFIX . '('.$link.')/page/?([0-9]{1,})/?$'] = 'index.php?'.FV_JC_NEWS_CAT_NAME.'=$matches[1]&paged=$matches[2]';
    $new_rewrite[FV_JC_NEWS_PREFIX . '('.$link.')/?$'] = 'index.php?'.FV_JC_NEWS_CAT_NAME.'=$matches[1]';
  }
  
  $new_rewrite[FV_JC_NEWS_PREFIX.'/page/(\d+)/?'] = 'index.php?post_type='.FV_JC_NEWS_TYPE.'&paged=$matches[1]';
  //$new_rewrite[FV_JC_NEWS_PREFIX.'/(\d\d\d\d)/(\d\d)/page/?([0-9]{1,})/?$'] = 'index.php?post_type='.FV_JC_NEWS_TYPE.'&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]';     
  
  //  news/2013/11/
  
  $new_rewrite[FV_JC_NEWS_PREFIX.'/?'] = 'index.php?post_type='.FV_JC_NEWS_TYPE;
  $new_rewrite[FV_JC_NEWS_PREFIX.'/page/?([0-9]{1,})/?$'] = 'index.php?post_type='.FV_JC_NEWS_TYPE.'&paged=$matches[1]';

  return $new_rewrite;
}


add_filter('post_type_link', 'fv_js_news_post_type_link', 10, 4);
function fv_js_news_post_type_link($link) {
  $args = func_get_args();
  $post = $args[1];
  if( is_object($post) && $post->post_type == FV_JC_NEWS_TYPE && $post->post_status == 'publish' ) {
    $link = wp_cache_get( 'fv_photo_post_type_link-'.$post->ID );
    if( $link == false) { 
      $cats = wp_get_object_terms($post->ID, FV_JC_NEWS_CAT_NAME, array( 'orderby' => 'term_id' ) );
      
      if ( is_null($post->post_name) ) {
				$post->post_name = sanitize_title($post->post_name ? $post->post_name : $post->post_title, $post->ID);
				
				$post->post_name = wp_unique_post_slug($post->post_name, $post->ID, $post->post_status, $post->post_type, $post->post_parent);
			}
      
      //preg_match( '~(\d\d\d\d)-(\d\d)~', $post->post_date, $aDate );
      //$sDate = $aDate[1].'/'.$aDate[2].'/';
      $sDate = '';
      
      if( count($cats) > 0 ) {
        //$cats_url = $cats[0]->slug;
        $link = user_trailingslashit( trailingslashit( get_term_link( $cats[0], FV_JC_NEWS_CAT_PREFIX ) ).$sDate.$post->post_name );
      } else {
        $link = user_trailingslashit( home_url(FV_JC_NEWS_PREFIX.'/'.$sDate.$post->post_name) );
      }
      //$link = home_url( user_trailingslashit( 'gallery/'.$cats_url.'/'.$post->post_name ) );  
      wp_cache_set( 'fv_photo_post_type_link-'.$post->ID, $link ); 
    }                                                              
  }
  return $link;
}


add_filter('wp_unique_post_slug', 'fv_js_news_wp_unique_post_slug', 10, 6 );
function fv_js_news_wp_unique_post_slug( $slug ) {
  $args = func_get_args();
  if( $args[3] == FV_JC_NEWS_TYPE ) {
    remove_filter('wp_unique_post_slug', 'fv_js_news_wp_unique_post_slug', 10, 6 );
  
    $fv_photos_category = get_terms( FV_JC_NEWS_CAT_PREFIX, array( 'hide_empty' => false ) );
    foreach( $fv_photos_category as $item ) {
      if( $slug == $item->slug ) {
        if( preg_match( '~-(\d+)$~', $slug, $match ) ) {
          $match++;
          $slug = preg_replace( '~-\d+~', '-'.$match, $slug );
        } else {
          $slug = $slug.'-2';
        }
      }
    }
    $slug = wp_unique_post_slug($slug, $args[1], $args[2], $args[3], $args[4], $args[5]);
  
    add_filter('wp_unique_post_slug', 'fv_js_news_wp_unique_post_slug', 10, 6 );
  }
  return $slug;
}


add_filter('template_redirect', 'fv_jc_news_redirect_canonical', 999, 2);
function fv_jc_news_redirect_canonical( $link ) {
  global $wp_query;
  if( $wp_query->query_vars['post_type'] == FV_JC_NEWS_TYPE && intval($wp_query->queried_object_id) ) {
    $requested_url  = is_ssl() ? 'https://' : 'http://';
		$requested_url .= $_SERVER['HTTP_HOST'];
		$requested_url .= $_SERVER['REQUEST_URI'];
    
    $requested_url = preg_replace( '~\?.+$~', '', $requested_url );
    
    $real_permalink = get_permalink($wp_query->queried_object_id);
    if( $real_permalink != $requested_url ) {
      wp_redirect( $real_permalink, 301 );
      die();    
    }
    
  }
  return $link;
}
 
/*
*
The best rewrite for hierarchical custom taxonomy ends here
*
*/






/*
Template tags follow
*/


function fv_jc_news_article_category() {
  if( $aTerms = wp_get_post_terms( get_the_ID(), FV_JC_NEWS_CAT_PREFIX ) ) {
     foreach( $aTerms AS $objTerm ) {
       $html .= "<a href='".get_term_link( $objTerm )."'>".$objTerm->name."</a> ";
     }
     echo $html;
  }  
}


function fv_jc_news_article_source() {
  $sFeedTitle = get_post_meta( get_the_ID(), 'original_feed_title', true );
  $sArticleLink = esc_attr(get_post_meta( get_the_ID(), 'original_source', true ));
  echo "<a href='$sArticleLink'>$sFeedTitle</a>\n";
}


function fv_jc_news_category_title() {
  if( $term = get_term_by( 'slug', get_query_var(FV_JC_NEWS_CAT_PREFIX), FV_JC_NEWS_CAT_PREFIX ) ) {                              
     echo $term->name;
  }
}

?>
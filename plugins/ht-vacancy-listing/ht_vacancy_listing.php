<?php
/*
Plugin Name: HT Vacancy listing
Plugin URI: http://www.helpfultechnology.com
Description: Display closing vacancies
Author: Luke Oatham
Version: 1.2
Author URI: http://www.helpfultechnology.com
*/

class htVacancyListing extends WP_Widget {

	function __construct() {
		
		parent::__construct(
			'htVacancyListing',
			__( 'HT Vacancy listing' , 'govintranet'),
			array( 'description' => __( 'Vacancy listing widget' , 'govintranet') )
		);   
    }

    function widget($args, $instance) {
	    extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $items = intval($instance['items']);
        $days = intval($instance['days']);
        $cacheperiod = intval($instance['cacheperiod']);
        if ( isset($cacheperiod) && $cacheperiod ){ $cacheperiod = 60 * $cacheperiod; } 
		$gatransient = $widget_id;
		$output = "";
		if ( $cacheperiod > 0 ) $output = get_transient( $gatransient );
		if ( $output == '' ):

			$tzone = get_option('timezone_string');
			date_default_timezone_set($tzone);
	
			//display forthcoming events
			$checkdate = date('Ymd'); 
			$numdays = "+".$days." day";
			$sdate = date('Ymd', date( strtotime( $numdays, strtotime( $checkdate ) ) ) ); 
			$stime = date('H:i'); 
			$cquery = array(
			   'meta_query' => array(
				   'relation' => 'OR',
			       array(
				       'relation' => 'AND',
				       array(
				           'key' => 'vacancy_closing_date',
				           'value' => $checkdate,
				           'compare' => '=',
					   		'type' => 'DATE' 
				       ),
				       array(
				           'key' => 'vacancy_closing_time',
				           'value' => $stime,
				           'compare' => '>',
			           ),
			       ),
			       array(
				       'relation' => 'AND',
				       array(
			           		'key' => 'vacancy_closing_date',
					   		'value' => $sdate,
					   		'compare' => '<',
					   		'type' => 'DATE' 
					   		),
				       array(
			           		'key' => 'vacancy_closing_date',
					   		'value' => $checkdate,
					   		'compare' => '>',
					   		'type' => 'DATE' 
					   		) 
				        ),   
			        ),   
				    'orderby' => 'meta_value',
				    'meta_key' => 'vacancy_closing_date',
				    'order' => 'ASC',
				    'post_type' => 'vacancy',
					'posts_per_page' => $items,
			);
	
			$vacancies =new WP_Query($cquery);
			if ($vacancies->post_count!=0){
				$output.= $before_widget; 
				if ( $title ) $output.= $before_title . $title . $after_title;
				$output.= "<div class='widget-area widget-vacancies'>";
			}
			$k=0;
			$alreadydone= array();
	
			while ($vacancies->have_posts()) {
				$vacancies->the_post();
				//don't show if already in stickies
				if (in_array($post->ID, $alreadydone )) continue;
				$k++;
				if ($k > $items) break;
				global $post; //required for access within widget
				$thistitle = get_the_title($post->ID);
				$edate = get_post_meta($post->ID,'vacancy_closing_date',true);
				$etime = date(get_option('time_format'),strtotime(get_post_meta($post->ID,'vacancy_closing_time',true))); 
				$edate = date(get_option('date_format'),strtotime($edate));
				$thisURL = get_permalink(); 
				$output.= "<div class='media vacancylisting vacancy-".$post->ID."'>";
				$output.= "<div class='media-left alignleft'>";
				$output.= "<a class='calendarlink' href='".$thisURL."'>";
				$output.= "<div class='vacancybox'>";
				$output.= "<div class='vacancy-dow'>".date('D',strtotime(get_post_meta($post->ID,'vacancy_closing_date',true)))."</div>";
				$output.= "<div class='vacancy-date'>".date('d',strtotime(get_post_meta($post->ID,'vacancy_closing_date',true)))."</div>";
				$output.= "<div class='vacancy-month'>".date('M',strtotime(get_post_meta($post->ID,'vacancy_closing_date',true)))."</div>";
				$output.= "</div>";
				$output.= "</a>";
				$output.= "</div>";
				$output.= "<div class='media-body'>";
				$output.= "<p class='media-heading'>";
				$output.= "<a href='".$thisURL."'>";
				$output.= $thistitle;
				$output.= "</a>";
				$output.= "</p>";
				//$output.= "<small><strong>".$edate."</strong></small>";
				if ( date('Ymd') == date('Ymd',strtotime(get_post_meta($post->ID,'vacancy_closing_date',true)))) $output.= "<span class='alert-vacancy small' >" . sprintf( __('Closing at %s' , 'govintranet'), date(get_option('time_format'),strtotime($etime)))."</span>";
				$output.= "</div></div>";
			}
	
			if ($vacancies->post_count!=0){
				$landingpage = get_option('options_module_vacancies_page'); 
				if ( !$landingpage ):
					$landingpage_link_text = __('vacancies','govintranet');
					$landingpage = site_url().'/vacancies/';
				else:
					$landingpage_link_text = get_the_title( $landingpage[0] );
					$landingpage = get_permalink( $landingpage[0] );
				endif;
				$output.= '<p class="vacancy-more-link"><strong><a title="'.$landingpage_link_text.'" class="small" href="'.$landingpage.'">'.$landingpage_link_text.'</a></strong> <span class="dashicons dashicons-arrow-right-alt2"></span></p></div>';
				$output.= $after_widget;
			}

			if ( $cacheperiod ) {
				if ( $vacancies->post_count != 0 ){
					set_transient($gatransient,$output."<!-- Cached by GovIntranet at ".date('Y-m-d H:i:s')." -->",$cacheperiod); 
				} else {
					set_transient($gatransient,"<!-- Cached by GovIntranet at ".date('Y-m-d H:i:s')." -->",$cacheperiod); 
				}
			}
			
		endif;
		echo $output;
		wp_reset_query();								
    }

    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['items'] = strip_tags($new_instance['items']);
		$instance['days'] = strip_tags($new_instance['days']);
		$instance['cacheperiod'] = strip_tags($new_instance['cacheperiod']);
       return $instance;
    }

    function form($instance) {
        $title = esc_attr($instance['title']);
        $items = esc_attr($instance['items']);
        $days = esc_attr($instance['days']);
        $cacheperiod = esc_attr($instance['cacheperiod']);
        ?>
         <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','govintranet'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /><br><br>

          <label for="<?php echo $this->get_field_id('items'); ?>"><?php _e('Number of items:','govintranet'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('items'); ?>" name="<?php echo $this->get_field_name('items'); ?>" type="text" value="<?php echo $items; ?>" /><br><br>

          <label for="<?php echo $this->get_field_id('days'); ?>"><?php _e('Days to look forward:','govintranet'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('days'); ?>" name="<?php echo $this->get_field_name('days'); ?>" type="text" value="<?php echo $days; ?>" /><br><br>

          <label for="<?php echo $this->get_field_id('cacheperiod'); ?>"><?php _e('Cache (minutes):','govintranet'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('cacheperiod'); ?>" name="<?php echo $this->get_field_name('cacheperiod'); ?>" type="text" value="<?php echo $cacheperiod; ?>" /><br>

        </p>
        <?php 
    }

}

function ht_vacancy_head(){
	$custom_css = "
	.vacancybox .vacancy-dow {
		background: ".get_theme_mod('header_background', '0b2d49').";
		color: #".get_header_textcolor().";
		font-size: 16px;
	}
	.vacancybox { 
		width: 3.5em; 
		border: 3px solid ".get_theme_mod('header_background', '0b2d49').";
		text-align: center;
		border-radius: 3px;
		background: #fff;
		box-shadow: 0 2px 3px rgba(0,0,0,.2);
	}
	.vacancybox .vacancy-month {
		color: ".get_theme_mod('header_background', '0b2d49').";
		text-transform: uppercase;
		font-weight: 800;
		font-size: 18px;
		line-height: 20px;
	}
    ";	        
	wp_enqueue_style( 'govintranet_vacancy_styles', plugins_url("/ht-vacancy-listing/ht_vacancy_listing.css"));
	wp_add_inline_style('govintranet_vacancy_styles' , $custom_css);
}

add_action('wp_head','ht_vacancy_head',4);
add_action('widgets_init', create_function('', 'return register_widget("htVacancyListing");'));

?>
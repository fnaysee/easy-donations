<?php
/**
 * EDT Custome widgets
 * *****************************************************************************************
 * *****************************************************************************************
 */

// Creating the widget 
class EDT_Donate_Form_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
        // Base ID of your widget
        'EDT_widget', 

        // Widget name will appear in UI
        __( 'Easy Donations Widget', EDT_TEXT_DOMAIN ), 

        // Widget description
        array( 'description' => __( 'This widget displays easy donations form as a widget', 'wpb_widget_domain' ), ) 
        );
    }

    // Creating widget front-end
    // This is where the action happens
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
        // before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];

        the_easy_donations_form();
        
        echo $args['after_widget'];
    }
    
    // Widget Backend 
    public function form( $instance ) {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = 'آخرین مطالب';
        }
            

        // Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php 
    }
	
    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }
} // Class wpb_widget ends here

/**
 * Register all of the extended version of default wp widgets
 */
function edt_widgets_init() {  
    //wpsrc custome widgets
    register_widget( 'EDT_Donate_Form_Widget' );
}

add_action('widgets_init', 'edt_widgets_init', 1);
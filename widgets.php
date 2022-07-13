<?php

// Adds widget: Experience Reports
class Relatos_Widget extends WP_Widget {

    private $service_url;

	function __construct() {
        $this->service_url = 'https://experiencias.bvsalud.org';

		parent::__construct(
			'relatos_widget',
			esc_html__( 'Experience Reports', 'relatos' ),
			array( 'description' => esc_html__( 'Display the lastest experiences', 'relatos' ), ) // Args
		);
	}

	private $widget_fields = array(
		array(
			'label' => 'Number of experiences',
			'id' => 'total',
			'default' => '5',
			'type' => 'number',
		),
	);

	public function widget( $args, $instance ) {
		$site_language = strtolower(get_bloginfo('language'));
		$lang = substr($site_language,0,2);
		$locale = array(
		    'pt' => 'pt_BR',
		    'es' => 'es_ES',
		    'fr' => 'fr_FR',
		    'en' => 'en'
		);

        $relatos_config = get_option('relatos_config');
        $relatos_service_request = $this->service_url . '/api/experience?limit=' . $instance['total'] . '&lang=' . $locale[$lang];

        $response = @file_get_contents($relatos_service_request);
        if ($response){
            $response_json = json_decode($response);
            $total = $response_json->total;
            $items = $response_json->items;
        }

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		// Output widget
        if ( $total ) {
            foreach ( $items as $item ) {
                $data = $item->main_submission;
                echo '<article>';
    			echo '<div class="destaqueBP">';
                echo '<a href="' . real_site_url($relatos_config['plugin_slug']) . 'resource/?id=' . $item->id . '"><b>' . $data->title . '</b></a>';
                if ( $data->description ) {
                    echo '<p>'. wp_trim_words( $data->description, 60, '...' ) . '</p>';
                }
                echo '</div>';
                echo '</article>';
            }
            echo '<br />';
            echo '<div class="bp-link"><a href="' . real_site_url($relatos_config['plugin_slug']) . '" class="btn btn-outline-primary" title="' . esc_html__( 'See more experiences', 'relatos' ) . '">' . esc_html__( 'See more Experience Reports', 'relatos' ) . '</a></div>';
        } else {
            echo esc_html__( 'No experiences found', 'relatos' );
        }

		echo $args['after_widget'];
	}

	public function field_generator( $instance ) {
		$output = '';
		foreach ( $this->widget_fields as $widget_field ) {
			$default = '';
			if ( isset($widget_field['default']) ) {
				$default = $widget_field['default'];
			}
			$widget_value = ! empty( $instance[$widget_field['id']] ) ? $instance[$widget_field['id']] : esc_html__( $default, 'relatos' );
			switch ( $widget_field['type'] ) {
				default:
					$output .= '<p>';
					$output .= '<label for="'.esc_attr( $this->get_field_id( $widget_field['id'] ) ).'">'.esc_attr( $widget_field['label'] ).':</label> ';
					$output .= '<input class="widefat" id="'.esc_attr( $this->get_field_id( $widget_field['id'] ) ).'" name="'.esc_attr( $this->get_field_name( $widget_field['id'] ) ).'" type="'.$widget_field['type'].'" value="'.esc_attr( $widget_value ).'">';
					$output .= '</p>';
			}
		}
		echo $output;
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title', 'relatos' ); ?>:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
		$this->field_generator( $instance );
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		foreach ( $this->widget_fields as $widget_field ) {
			switch ( $widget_field['type'] ) {
				default:
					$instance[$widget_field['id']] = ( ! empty( $new_instance[$widget_field['id']] ) ) ? strip_tags( $new_instance[$widget_field['id']] ) : '';
			}
		}
		return $instance;
	}
}

function register_relatos_widget() {
	register_widget( 'Relatos_Widget' );
}

add_action( 'widgets_init', 'register_relatos_widget' );

?>

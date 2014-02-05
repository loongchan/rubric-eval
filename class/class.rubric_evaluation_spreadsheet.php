<?php
/**
 * started first with http://codex.wordpress.org/Creating_Options_Pages
* @author loongchan
*
*/
class CTLT_Rubric_Evaluation_Spreadsheet
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $roles;
    private $rubric;
    private $students;

    /**
     * Start up
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );

        //get student role
  		$this->_setup_author_and_options();
        $blog_id = get_current_blog_id();
        $fields = array('ID', 'user_login', 'user_nicename', 'display_name');
        $this->students = get_users(array('blog_id' => $blog_id, 'role' => $this->roles['rubric_evaluation_roles_settings']['rubric_evaluation_role_student'], 'fields' => $fields));
    
    	add_action('save_post', array( $this, 'save_rubric_grade'));
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
    	//add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
    	add_submenu_page(
	    	'rubric_evaluation_settings',
	    	__('Spreadsheet', 'ctlt_rubric_evaluation'),
	    	__('Spreadsheet', 'ctlt_rubric_evaluation'),
	    	'activate_plugins',
	    	'rubric_evaluation_subpage_settings',
	    	array( $this, 'create_rubric_evaluate_page')
    	);
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        add_settings_section(
            'rubric_evaluation_spreadsheet_group', // ID
            __('Spreadsheet Section', 'ctlt_rubric_evaluation'), // Title
            array( $this, 'print_section_info' ), // Callback
            'rubric_evaluation_spreadsheet' // Page
        );  

        add_settings_field(
            'rubric_evaluation_spreadsheet', // ID
            __('Spreadsheet', 'ctlt_rubric_evaluation'), // Title 
            array( $this, 'output_spreadsheet' ), // Callback
            'rubric_evaluation_spreadsheet', // Page
            'rubric_evaluation_spreadsheet_group' // Section           
        );      
        
        register_setting(
	        'rubric_evaluation_spreadsheet', // Option group
	        'rubric_evaluation_spreadsheet_name', // Option name
	        array( $this, 'sanitize' ) // Sanitize
        );
        
        //add metabox to edit post page
        //@TODO: need to make it more flexible by making it for posts or pages
        if (is_admin()) {
        	add_meta_box('rubric_evaluation_box', 'test rubric box title', array($this, 'add_rubric_metabox'), 'post', 'side' );
        }
    }
    
    public function save_rubric_grade($post_id) {
    	error_log('save_rubric_grade: '.print_r($_REQUEST['rubric_evaluation_instructor_grade'], true));
    }
    //======================================================================
    //
    // Output functions
    //
    //======================================================================
    /**
     * Options page callback
     */
    public function create_rubric_evaluate_page()
    {
		// Set class property
		$this->options = get_option( 'my_option_name' );
    	?>
			<div class="wrap">
				<?php screen_icon(); ?>
				<h2><?php _e('Spreadsheet', 'ctlt_rubric_evaluation');?></h2>           
				<form method="post" action="options.php">
				<?php
					// This prints out all hidden setting fields
					settings_fields( 'rubric_evaluation_spreadsheet' );
    				do_settings_sections( 'rubric_evaluation_spreadsheet' );
					submit_button(); 
				?>
				</form>
			</div>
			<?php
	}
	
	public function add_rubric_metabox() {
		global $post;
		error_log('metabox for rubric: '.print_r($post->ID, true));
		error_log('metabox for rubric author: '.print_r($post->post_author,true));
		if (is_admin()) {
			echo '<input type="text" name="rubric_evaluation_instructor_grade" value="">';
		}
	}

    /** 
     * Print the Section text
     */
    public function print_section_info() {
//         print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function output_spreadsheet()
    {
    	echo "<table class='spreadsheet'><tr><th>".__('Students', 'ctlt_rubric_evaluation')."</th>";
    	
    	//columns
    	$column_name = array_keys($this->rubric['rubric_evaluation_rubric_name']);
    	foreach ($column_name as $cols) {
    		echo '<th>'.$cols.'</th>'; 
    		
    	}
//     	echo "<th>".__('Total', 'ctlt_rubric_evaluation')."</th>\n";
    	echo '</tr>';
    	
    	//rows
    	foreach ($this->students as $row => $student_info) {
    		//need to make the linkc
    		$user_link = $student_info->display_name;
    		if (is_admin()) {
    			$user_link = '<a href="/wp-admin/user-edit.php?user_id='.$student_info->ID.'">'.$student_info->display_name.'</a>';
    		}
    		echo '<tr><td>'.$user_link.'</td>';
    		foreach ($column_name as $col => $mark) {
    			$id_name = 'rubric_evaluation_spreadsheet_value_'.($row + 1).'_'.($col + 1);
    			$value = 'a';
    			
    			//get postID
    			//@TODO: need to make more generic, page or posts or custom_type
    			//@thanks http://www.webdevdoor.com/wordpress/get-posts-custom-taxonomies-terms/
    			$args = array(
    				'tax_query' => array(
    					array(
    						'taxonomy' => 'ctlt_rubric_evaluation',
    						'field' => 'name',
    						'terms' => array($column_name[($col)])
    					)
    				)
    			);
    			$posts_array = get_posts($args);
//     			error_log('col: '.print_r($column_name[($col)], true));
//     			error_log('posts array: '.print_r($posts_array, true));
//     			error_log('authors: '.print_r($this->students,true));
    			$post_id = 0;
    			$post_url = '#na';
    			$post_title = __('Not Completed', 'ctlt_rubric_evaluation');
    			$author = $this->students[$row];
    			$author_id = $author->ID;
    			foreach ($posts_array as $post_info) {
    				if ($post_info->post_author == $author_id) {
    					$post_id = $post_info->ID;
    					$post_url = wp_get_shortlink($post_id, 'post'); 
    					$post_title = $post_info->post_title;
    				}
    			}
    			echo '<td>';
    			if ($post_id == 0) {
    				echo $post_title;
    			} else {
    				echo '<a href="'.$post_url.'">'.$post_title.'</a>';
    			}
//     			echo '<input type="text" id="'.$id_name.'" name="rubric_evaluation_spreadsheet_name['.$id_name.']" value="'.$value.'">';
    			echo '</td>';
    		}
    		echo '</tr>';
    			
    	}
    	
    	echo "</table>";
		/*
		 * 		$vertical_titles = !empty($this->options['rubric_evaluation_rubric_name'])? array_keys($this->options['rubric_evaluation_rubric_name']) : array();

    	echo "<table class='rubric'>\n<tr><th>Actions</th><th>".'Type'."</th>";
    	//table horizontal headers
    	foreach ($this->rubric_headers as $h_title) {
			echo '<th class="Type '.$this->_sanitize_class_name($h_title).'">'.$h_title.'</th>';
		}
		echo "</tr>\n";
		
		//table rows
		$row = 1; //row count
		foreach ($vertical_titles as $v_title) {
			echo '<tr>';
			echo '<td><a href="#delete" class="ctlt_rubric_delete_row" data-row="'.$row.'">x</a></td>';
			echo '<td class="heading '.$this->_sanitize_class_name($v_title).'">'.
					'<label id="rubric_evaluation_rubric_label_'.$row.'" value="'.$v_title.'" />'.$v_title.'</label>'.
					'<input type="hidden" id="rubric_evaluation_rubric_values_'.$row.'" name="rubric_evaluation_rubric_name[rubric_evaluation_rubric_values_'.$row.']" value="'.$v_title.'"/>'.
				'</td>';
			for ($column = 1; $column < (sizeof($this->rubric_headers) + 1); $column++) {
				$value = isset($this->options['rubric_evaluation_rubric_name'][$v_title][$this->rubric_headers[($column - 1)]]) ? $this->options['rubric_evaluation_rubric_name'][$v_title][$this->rubric_headers[($column - 1)]] : '';
 				echo '<td class="'.$this->_sanitize_class_name($v_title).' '.$this->_sanitize_class_name($this->rubric_headers[($column - 1)]).'">';
				echo '<input type="text" id="rubric_evaluation_rubric_values_'.$row.'_'.$column.'" name="rubric_evaluation_rubric_name[rubric_evaluation_rubric_values_'.$row.'_'.$column.']" value="'.$value.'" />';
				echo '</td>';
        	}
        	echo '</tr>';
        	$row++;
        }
        echo '</table>';
		 */
    }

    //======================================================================
    //
    // Sanitization callback functions
    //
    //======================================================================
    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
//     	error_log('sanitize spreadsheet input: '.print_r($input,true));
    	//         $new_input = array();
    	//         if( isset( $input['id_number'] ) )
    		//             $new_input['id_number'] = absint( $input['id_number'] );
    	//         if( isset( $input['title'] ) )
    		//             $new_input['title'] = sanitize_text_field( $input['title'] );
    
    		return $new_input;
    }
    
    
    //======================================================================
    //
    // Private functions
    //
    //======================================================================
    private function _setup_author_and_options() {
    	$this->roles = $this->options = $this->rubric = array();
    	
		//get options
		$options = get_option('rubric_evaluation_spreadsheet_name');
		if ($options !== false) {
			$this->options = $options;
		}
		
		//get roles
		$roles = get_option('rubric_evaluation_roles_settings');
		if ($roles !== false) {
			$this->roles = $roles;
			
			//@TODO: temp placeholder to force teacher to have some sort of role
			if (!isset($this->roles['rubric_evaluation_roles_settings']['rubric_evaluation_role_teacher'])) {
				$this->roles['rubric_evaluation_roles_settings']['rubric_evaluation_role_teacher'] = 'administrator';
			}
		}
		
		//get rubric
		$rubric = get_option('rubric_evaluation_rubric_name');
		if ($rubric !== false) {
			$this->rubric = $rubric;
		}
	}
}


if( is_admin() )
	$ctlt_rubric_evaluation_spreadsheet = new CTLT_Rubric_Evaluation_Spreadsheet();
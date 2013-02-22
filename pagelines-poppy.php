<?php
/*
Plugin Name: Poppy
Plugin URI: http://www.pagelines.com
Description: Adds a useful and versitile contact form shortcode to be used anywhere.
Author: PageLines
PageLines: true
Version: 1.0
External: http://www.pagelines.com
Demo: http://poppy.pagelines.me
*/

class PageLinesPoppy {

	function __construct() {

		$this->base_dir = sprintf( '%s/%s', WP_PLUGIN_DIR,  basename(dirname( __FILE__ )));
		$this->base_url = sprintf( '%s/%s', WP_PLUGIN_URL,  basename(dirname( __FILE__ )));
		$this->icon = $this->base_url . '/icon.png';
		add_filter( 'pagelines_lesscode', array( &$this, 'get_less' ), 10, 1 );
	
		add_action( 'admin_init', array( &$this, 'admin_page' ) );
		
		add_action( 'init', array( &$this, 'add_shortcode' ) );
		
		add_action( 'wp_print_styles', array( &$this, 'hooks_with_activation' ));	
		
		add_action( 'wp_ajax_nopriv_ajaxcontact_send_mail', array( &$this, 'ajaxcontact_send_mail' ) );
		add_action( 'wp_ajax_ajaxcontact_send_mail', array( &$this, 'ajaxcontact_send_mail' ) );
	}



function get_less( $less ){
	
	$less .= pl_file_get_contents( $this->base_dir.'/style.less' );

	return $less;
	
}

function hooks_with_activation() {
	wp_localize_script( 'ajaxurl', 'ajaxurl', array( admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_script( 'poppy-js', $this->base_url . '/script.js', array('jquery')); 
	
}

function add_shortcode() {
	add_shortcode( 'poppy', array(  &$this, 'draw_form' ) );
}

function draw_form() {
	ob_start();
	?>
	
<a class="btn" data-toggle="modal" href="#poppy-modal">Contact</a>

<div id="poppy-modal" class="hide fade modal poppy" >

<div class="modal-header"><a class="close" data-dismiss="modal" aria-hidden="true">×</a>
	<h3><?php echo ploption( 'poppy_form_title' ) ?></h3>
</div>

<div class="modal-body">

<form class="" id="ajaxcontactform" action="" method="post" enctype="multipart/form-data">
    <fieldset>
		<div class="poppy-response"></div>
		<div class="control-group">
			<div class="controls form-inline">
				<input class="poppy-input poppy-name" placeholder="Name" id="ajaxcontactname" type="text" name="ajaxcontactname">
				<input class="poppy-input poppy-email" placeholder="Email Address" id="ajaxcontactemail" type="text" name="ajaxcontactemail">
			</div>
		</div>

		<div class="control-group">
			<div class="controls">
				<div class="textarea">
					<textarea class="poppy-msg" row="8" placeholder="Your Message..." id="ajaxcontactcontents" name="ajaxcontactcontents"></textarea>
				</div>
			</div>
		</div>

<?php if ( ploption( 'poppy_enable_captcha' ) ) $this->captcha(); ?>

	          <div class="controls">
	            <a class="btn btn-primary send-poppy">Send Message</a>
	          </div>


	    </fieldset>
	  </form>
	
	
	</div>

</div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}


	function captcha() {

		$code = sprintf( '<div class="control-group">

          <!-- Prepended text-->
          <label class="control-label">Captcha</label>
          <div class="controls">
           
              <input class="span2 poppy-captcha" placeholder="%s" id="ajaxcontactcaptcha" type="text" name="ajaxcontactcaptcha" />
         
          </div>

        </div>', ploption( 'poppy_captcha_question' ) );
        echo $code;
	}


	function admin_page() {

		if ( ! function_exists( 'ploption' ) )
			return;
		$option_args = array(

			'name'		=> 'Poppy',
			'array'		=> $this->options_array(),
			'icon'		=> $this->icon,
			'position'	=> 6
		);

		foreach( $option_args['array'] as $k => $o )
			if( ! ploption( $k ) && isset( $o['default'] ) )
				plupop( $k, $o['default'] );
		pl_add_options_page( $option_args );
	}

	function options_array() {

		$options = array(
					'poppy_form_title' => array(
						'type' 		=> 'text',
						'inputlabel'	=>'Form Title.',
						'default'	=> 'Contact Us',
						'exp' => 'Main title for the form.'
					),
					'poppy_email'	=> array(
						'type'	=> 'text',
						'inputlabel'	=> 'Default email send address.',
						'exp'	=> 'Email address to send for To. Leave blank to use admin email.'
						),
					'poppy_misc'	=> array(
						'type'	=> 'check_multi',
						'selectvalues'	=> array(
							'poppy_enable_captcha'	=> array(
								'default'	=> true,
								'inputlabel'	=> 'Enable simple antispam question?')
							)),

					'poppy_captcha'	=> array(
						'type'		=> 'text_multi',
						'inputsize'	=> 'regular',
						'selectvalues'	=> array(
					'poppy_captcha_question'	=> array(
						'type'	=> 'text',
						'default'	=> '2 + 5',
						'inputlabel'	=> 'Antispam question.'
						),
					'poppy_captcha_answer'	=> array(
						'type'	=> 'text',
						'default'	=> '7',
						'inputlabel'	=> 'Antispam answer' )


					))


			);
	return $options;
	}

	function ajaxcontact_send_mail(){
  		$results = '';
		$error = 0;
		
		$data = $_POST; 
		
		$defaults = array(
			'name'	=> '',
			'email'	=> '',
			'msg'	=> '',
			'cap'	=> ''
		); 
		$data = wp_parse_args($data, $defaults);
		
		$name = $data['name'];
		$email = $data['email'];
		$contents = $data['msg'];
		$captcha = $data['cap'];
		
		$admin_email = ( ploption( 'poppy_email' ) ) ? ploption( 'poppy_email' ) : get_option('admin_email');
		
		$subject = 'New Message from '.$name;
		
		$captcha_ans = ploption( 'poppy_captcha_answer' );

		if ( ploption( 'poppy_enable_captcha' ) ){
			
			if( '' == $captcha )
				die( 'Captcha cannot be empty!' );
			if( $captcha !== $captcha_ans )
				die( 'Captcha does not match.' );
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
			die( 'Email address is not valid.' );
			
		elseif( strlen($name) == 0 ) 
			die( 'Name is invalid.' );
			
		elseif( strlen($contents) == 0 )
			die( 'Content is invalid.' );
		

		$headers = 'From:'.$email. "\r\n";
		
		if(wp_mail($admin_email, $subject, $contents, $headers)) 
			die( 'ok' );
		else
			die( "*The mail could not be sent." );
		
	}
}

new PageLinesPoppy;
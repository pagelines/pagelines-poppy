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

		$this->base_dir	= plugin_dir_path( __FILE__ );
		$this->base_url = plugins_url( __FILE__ );
		$this->icon		= plugins_url( '/icon.png', __FILE__ );
		$this->less		= $this->base_dir . '/style.less';

		add_filter( 'pagelines_lesscode', array( &$this, 'get_less' ), 10, 1 );
		add_action( 'admin_init', array( &$this, 'admin_page' ) );
		add_action( 'init', array( &$this, 'add_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'hooks_with_activation' ) );
		add_action( 'wp_ajax_nopriv_ajaxcontact_send_mail', array( &$this, 'ajaxcontact_send_mail' ) );
		add_action( 'wp_ajax_ajaxcontact_send_mail', array( &$this, 'ajaxcontact_send_mail' ) );
	}

	function hooks_with_activation() {
		wp_enqueue_script( 'poppyjs', plugins_url( '/script.js', __FILE__ ), array( 'jquery' ), time() );
		wp_localize_script( 'poppyjs', 'poppyjs', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	function get_less( $less ){

		$less .= pl_file_get_contents( $this->less );
		return $less;

	}
	function add_shortcode() {
		add_shortcode( 'poppy', array(  &$this, 'draw_form' ) );
	}

	function draw_form( $atts, $content = null ) {

		extract( shortcode_atts( array(
		    'class' => '',
		    'type'	=> 'button',
		), $atts ) );

		if( ! $content )
			$content = 'Contact';
		if( 'button' == $type )
			$class = 'btn ' . $class;
		if( 'label' == $type ) {
			$class = 'label ' . $class;
			$type = 'span';
		}
		ob_start();
		printf( '<%s class="%s" data-toggle="modal" href="#poppy-modal">%s</%s>',
			$type,
			$class,
			$content,
			$type
			);
	?>
<div id="poppy-modal" class="hide fade modal poppy" >
	<div class="modal-header"><a class="close" data-dismiss="modal" aria-hidden="true">×</a>
		<h3><?php echo ploption( 'poppy_form_title' ) ?></h3>
	</div>
	<div class="modal-body">
		<div class="poppy-response"></div>
		<form class="poppy-form" id="ajaxcontactform" action="" method="post" enctype="multipart/form-data">
			<fieldset>
				<div class="control-group">
					<div class="controls form-inline">
						<input class="poppy-input poppy-name" placeholder="Name" id="ajaxcontactname" type="text" name="Name">
						<input class="poppy-input poppy-email" placeholder="Email Address" id="ajaxcontactemail" type="text" name="Email">
						<?php if ( ploption( 'poppy_enable_extra' ) && '' != ploption( 'poppy_extra_field' ) )
								printf( '<input class="poppy-input poppy-custom" placeholder="%1$s" id="ajaxcontactcustom" type="text" name="%1$s">',ploption( 'poppy_extra_field' ) );
						?>
					</div>
				</div>
			<div class="control-group">
				<div class="controls">
					<div class="textarea">
						<textarea class="poppy-msg" row="8" placeholder="Your Message..." id="ajaxcontactcontents" name="Content"></textarea>
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
		pl_add_options_page( $option_args );
	}

	function options_array() {

		$options = array(

			'poppy_options'	=> array(
				'type'	=> 'multi_option',
				'layout'	=> 'full',
				'exp'	=> 'Customize how the subject is handled in your email client.<br />Possible values:<br />%name%<br />%blog%',
				'selectvalues'	=> array(
					'poppy_form_title' => array(
						'type' 		=> 'text',
						'inputlabel'	=>'Form Title.',
						'default'	=> 'Contact Us!',
						'shortexp' => 'Main title for the form.'
						),
					'poppy_email'	=> array(
						'type'	=> 'text',
						'inputlabel'	=> 'Default email send address.',
						'exp'	=> 'Email address to send for To. Leave blank to use admin email.'
						),
					'poppy_enable_extra'	=> array(
						'type'	=> 'check',
						'default'	=> false,
						'inputlabel'	=> 'Enable extra custom field.'
						),
					'poppy_extra_field'	=> array(
						'type'	=> 'text',
						'default'	=> '',
						'inputlabel'	=> 'Extra field text.'
						),
					'poppy_enable_captcha'	=> array(
						'type'	=> 'check',
						'default'	=> true,
						'inputlabel'	=> 'Enable simple antispam question?'
						),
					'poppy_captcha_question'	=> array(
						'type'	=> 'text',
						'default'	=> '2 + 5',
						'inputlabel'	=> 'Antispam question.'
						),
					'poppy_captcha_answer'	=> array(
						'type'	=> 'text',
						'default'	=> '7',
						'inputlabel'	=> 'Antispam answer'
						),
					'poppy_email_layout'	=> array(
						'type'	=> 'text',
						'inputlabel'	=> 'Format for email subject.',
						'default'	=> '[%blog%] New message from %name%.',

						)
					)
				)
			);
	return $options;
	}

	function ajaxcontact_send_mail(){

		$data = $_POST;

		$defaults = array(
			'name'	=> '',
			'email'	=> '',
			'custom'=> '',
			'msg'	=> '',
			'cap'	=> '',
			'width'	=> '',
			'height'=> '',
			'agent' => ''
		);

		$data = wp_parse_args($data, $defaults);

		$name			= $data['name'];
		$email			= $data['email'];
		$custom			= $data['custom'];
		$custom_field	= ( ploption( 'poppy_enable_extra' ) ) ? ploption( 'poppy_extra_field' ) : '';
		$contents		= $data['msg'];
		$admin_email	= ( ploption( 'poppy_email' ) ) ? ploption( 'poppy_email' ) : get_option( 'admin_email' );
		$captcha		= $data['cap'];
		$captcha_ans	= ploption( 'poppy_captcha_answer' );
		$width			= $data['width'];
		$height			= $data['height'];
		$ip				= $_SERVER['REMOTE_ADDR'];
		$agent			= $data['agent'];

		if ( ploption( 'poppy_enable_captcha' ) ){
			if( '' == $captcha )
				die( 'Captcha cannot be empty!' );
			if( $captcha !== $captcha_ans )
				die( 'Captcha does not match.' );
		}

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			die( 'Email address is not valid.' );
		} elseif( strlen( $name ) == 0 ) {
			die( 'Name cannot be empty.' );
		} elseif( strlen( $contents ) == 0 ) {
			die( 'Content cannot be empty.' );
		}

		// create an email.
		$headers			= 'From:'.$email. "\r\n";
		$subject_template	= ( '' != ploption( 'poppy_email_layout' ) ) ? ploption( 'poppy_email_layout' ) : '[%blog%] New message from %name%.';
		$subject			= str_replace( '%blog%', get_bloginfo( 'name' ), str_replace( '%name%', $name, $subject_template ) );

		$fields = 'Name: %s %7$sEmail: %s%7$sContents%7$s=======%7$s%s %7$s%7$sUser Info.%7$s=========%7$sIP: %s %7$sScreen Res: %s %7$sAgent: %s %7$s%7$s%8$s: %9$s';

		$template = sprintf( $fields,
			$name,
			$email,
			$contents,
			$ip,
			sprintf( '%sx%s', $width, $height ),
			$agent,
			"\n",
			$custom_field,
			$custom
			);
		if( wp_mail( $admin_email, $subject, $template, $headers ) ) {
			die( 'ok' );
		} else {
			 die( 'Unknown wp_mail() error.' );
		}
	}
}
new PageLinesPoppy;
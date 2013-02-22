<?php
/*
Plugin Name: PageLines Poppy
Plugin URI: http://www.pagelines.com
Description: Adds a useful and versitile contact form shortcode to be used anywhere.
Author: PageLines
PageLines: true
Version: 1.0
External: http://www.pagelines.com
Demo: http://www.
*/

class PageLinesPoppy {

	function __construct() {


		add_action( 'admin_init', array( &$this, 'admin_page' ) );
		add_action( 'init', array( &$this, 'add_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue' ) );
		add_action( 'wp_ajax_nopriv_ajaxcontact_send_mail', array( &$this, 'ajaxcontact_send_mail' ) );
		add_action( 'wp_ajax_ajaxcontact_send_mail', array( &$this, 'ajaxcontact_send_mail' ) );
	}


	function enqueue() {
		wp_enqueue_script('ajaxcontact', plugins_url( '/js/ajaxcontact.js', __FILE__ ), array('jquery'), time());
		wp_localize_script( 'ajaxcontact', 'ajaxcontactajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	function add_shortcode() {
		add_shortcode( 'poppy', array(  &$this, 'draw_form' ) );
	}

	function draw_form() {
		ob_start();
		?>
<a data-toggle="modal" href="#poppy-modal">Contact</a>

<div class="hide fade modal" id="poppy-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">

<div class="modal-header"><a class="close" data-dismiss="modal" aria-hidden="true">×</a><h3><?php echo ploption( 'poppy_form_title' ) ?></h3></div>

<div class="modal-body">

<form class="" id="ajaxcontactform" action="" method="post" enctype="multipart/form-data">
    <fieldset>



    <div class="control-group">

          <!-- Prepended text-->
          <label class="control-label"></label>
          <div class="controls">
            <div class="input-prepend">
              <span class="add-on">Name</span>
              <input class="span2" placeholder="" id="ajaxcontactname" type="text" name="ajaxcontactname">
            </div>
            <p class="help-block"></p>
          </div>

        </div><div class="control-group">

          <!-- Prepended text-->
          <label class="control-label"></label>
          <div class="controls">
            <div class="input-prepend">
              <span class="add-on">Title </span>
              <input class="span2" placeholder="" id="ajaxcontactsubject" type="text" name="ajaxcontactsubject">
            </div>
            <p class="help-block"></p>
          </div>

        </div><div class="control-group">

          <!-- Prepended text-->
          <label class="control-label"></label>
          <div class="controls">
            <div class="input-prepend">
              <span class="add-on">Email</span>
              <input class="span2" placeholder="" id="ajaxcontactemail" type="text" name="ajaxcontactemail">
            </div>
            <p class="help-block"></p>
          </div>

        </div>
        <div class="control-group">

          <!-- Textarea -->
          <div class="controls">
            <div class="textarea">
                  <textarea row="4" placeholder="Type in your message" id="ajaxcontactcontents" name="ajaxcontactcontents"></textarea>
            </div>
          </div>
        </div>
        <?php if ( ploption( 'poppy_enable_captcha' ) )
        	$this->captcha();
        ?>
    <div class="control-group">
          <label class="control-label"></label>

          <!-- Button -->
          <div class="controls">
            <a onclick="ajaxformsendmail(ajaxcontactname.value,ajaxcontactemail.value,ajaxcontactsubject.value,ajaxcontactcontents.value,ajaxcontactcaptcha.value);" class="btn btn-primary">Send</a>

          </div>
        </div>

    </fieldset>
  </form>
<div id="ajaxcontact-response"></div>
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
          <label class="control-label"></label>
          <div class="controls">
            <div class="input-prepend">
              <span class="add-on">Captcha</span>
              <input class="span2" placeholder="%s" id="ajaxcontactcaptcha" type="text" name="ajaxcontactcaptcha">
            </div>
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
			'icon'		=> '',
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
						'default'	=> 'Contact Us!',
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
		$name = $_POST['poppyname'];
		$email = $_POST['poppyemail'];
		$subject = $_POST['poppysubject'];
		$contents = $_POST['poppycontents'];
		$admin_email = ( ploption( 'poppy_email' ) ) ? ploption( 'poppy_email' ) : get_option('admin_email');
		$captcha = $_POST['poppycaptcha'];
		$captcha_ans = ploption( 'poppy_captcha_answer' );

		if ( ploption( 'poppy_enable_captcha' ) ){
			if( '' == $captcha )
				die( 'Captcha cannot be empty!' );
			if( $captcha !== $captcha_ans )
				die( 'Captcha does not match.' );
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			die( 'Email address is not valid.' );
		} elseif( strlen($name) == 0 ) {
			die( 'Name is invalid.' );
		} elseif( strlen($subject) == 0 ) {
			die( 'Title is invalid.' );
		} elseif( strlen($contents) == 0 ) {
			die( 'Content is invalid.' );
		}

		$headers = 'From:'.$email. "\r\n";
		if(wp_mail($admin_email, $subject, $contents, $headers)) {
			die( 'ok' );
		} else {
			 die( "*The mail could not be sent." );
		}
	}
}
new PageLinesPoppy;
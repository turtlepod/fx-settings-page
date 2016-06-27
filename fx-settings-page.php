<?php
/**
 * Plugin Name: f(x) Settings Page Library
 * Plugin URI: http://genbumedia.com/plugins/fx-settings-page/
 * Description: Single class settings page library for developer.
 * Version: 1.0.0
 * Author: David Chandra Purnama
 * Author URI: http://shellcreeper.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: fx-settings-page
 * Domain Path: /languages/
 *
 * @author David Chandra Purnama <david@genbumedia.com>
 * @copyright Copyright (c) 2016, Genbu Media
**/

/* Load Class */
fx_Settings_Page::get_instance();

/**
 * Meta Box Library
 * @since 1.0.0
 */
final class fx_Settings_Page{

	static $version = '';
	static $uri     = '';
	static $path    = '';

	/* Construct
	------------------------------------------ */
	public function __construct(){

		/* Vars */
		self::$version = '1.0.0';
		self::$path    = trailingslashit( plugin_dir_path(__FILE__) );
		self::$uri     = trailingslashit( plugin_dir_url( __FILE__ ) );

		/* Register Admin Scripts */
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 1 );
	}


	/* Admin Scripts
	------------------------------------------ */

	/**
	 * Register Admin Scripts
	 * @since 1.0.0
	 */
	public static function scripts( $hook_suffix ){

		/* Enqueue or use this as dependency */
		wp_register_style( 'fx-settings-page', self::$uri . 'assets/style.css', array(), self::$version );
		wp_register_script( 'fx-settings-page', self::$uri . 'assets/script.js', array( 'jquery' ), self::$version, true );
	}


	/* Settings Page UI
	------------------------------------------ */

	/**
	 * Tabbed UI
	 * Use this in add_menu_page() /  sub_page() callback.
	 * @since 1.0.0
	 */
	public static function tabs_ui( $args = array(), $tabs = array() ){
		global $hook_suffix;
		$args_default = array(
			'title'            => '',
			'message'          => array(
				'reset_success'   => 'All settings reset to defaults.',   
				'reset_fail'      => 'Failed to reset settings. Please try again.',
				'reset_confirm'   => 'Are you sure you want to reset all settings to defaults?',
			),
			'options_group'    => '',
			'page_slug'        => '', // ID
			'submitdiv'        => array(),
			'capability'       => 'manage_options',
		);
		$args = wp_parse_args( $args, $args_default );

		/* === Reset Settings === */
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$nonce  = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';

		if( $action && 'reset_settings' == $action ){
			if( $nonce && wp_verify_nonce( $nonce, $args['page_slug'] . __FILE__ ) && current_user_can( $args['capability'] ) ){

				/* Get all options name in group */
				global $new_whitelist_options;
				$options_group = $args['options_group'];
				$option_names = $new_whitelist_options[$options_group];

				/* Delete options */
				foreach( $option_names as $option_name ){
					delete_option( $option_name );
				}

				/* Reset Success Updated Message */
				add_settings_error( $args['page_slug'], '', $args['message']['reset_success'], 'updated' );
			}
			else{
				/* Reset Fail Error Message */
				add_settings_error( $args['page_slug'], '', $args['message']['reset_fail'], 'error' );
			}
		}

		/* Display Settings Error & Update Message. */
		settings_errors();
		?>
		<div id="<?php echo esc_attr( $args['page_slug'] ); ?>-settings-page" class="wrap fx-settings-page-tabs-ui">

			<?php if( $args['title'] ){ ?>
			<h1><?php echo $args['title'] ?></h1>
			<?php } ?>

			<form id="fx-sptabs-form" method="post" action="options.php">
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">

						<div id="postbox-container-2" class="postbox-container">

							<h1 class="nav-tab-wrapper wp-clearfix fx-sptabs-nav">
								<?php
								$i = 0;
								foreach( $tabs as $nav ){
									$i++;
									$class = ( 1 === $i ) ? 'nav-tab nav-tab-active' : 'nav-tab';
									$class = 'nav-tab';
									$id = "{$args['page_slug']}_{$nav['id']}";
									?>
									<a class="<?php echo esc_attr( $class ); ?>" href="#<?php echo esc_attr( $id ); ?>"><?php echo $nav['label']; ?></a>
									<?php
								}
								?>
							</h1>

							<div class="fx-sptabs-content">
								<?php
								$i = 0;
								foreach( $tabs as $panel ){
									$i++; $style = ( 1 === $i ) ? 'display:block;' : 'display:none;';
									$style = 'display:none;';
									$id = "{$args['page_slug']}_{$panel['id']}";
									?>
									<div id="<?php echo esc_attr( $id ); ?>" class="fx-sptabs-panel" style="<?php echo esc_attr( $style ); ?>">
										<?php if ( is_callable( $panel['callback'] ) ){
											call_user_func( $panel['callback'] );
										} ?>
									</div><!-- .fx-sptabs-panel -->
									<?php
								}
								?>
							</div>

						</div><!-- #postbox-container-2-->

						<div id="postbox-container-1" class="postbox-container">
							<?php if( false !== $args['submitdiv'] ) self::tabs_ui_submitdiv( $args['submitdiv'], $args ); ?>
							<?php if( $args['options_group'] ) settings_fields( $args['options_group'] ); ?>
						</div>

					</div><!-- #post-body -->
				</div><!-- #poststuff -->
			</form>

		</div><!-- wrap -->
		<?php
	}

	/* Utility Functions
	------------------------------------------ */

	/**
	 * Submit Box For Tabs UI
	 * @since 1.0.0
	 */
	public static function tabs_ui_submitdiv( $args, $tabs_args = array() ){
		$args_default = array(
			'title'        => 'Save Options',
			'save_text'    => 'Save Changes',
			'reset_text'   => 'Reset to defaults',
		);
		$args = wp_parse_args( $args, $args_default );

		/* Reset URL */
		if( $args['reset_text'] && isset( $tabs_args['page_slug'] ) ){
			$reset_url = add_query_arg( array(
					'page'       => $tabs_args['page_slug'],
					'action'     => 'reset_settings',
					'_wpnonce'   => wp_create_nonce( $tabs_args['page_slug'] . __FILE__ ),
				),
				admin_url( 'admin.php' )
			);
		}
		?>
		<div class="postbox" id="submitdiv" style="display:block;">

			<h2 class="hndle ui-sortable-handle"><span><?php echo $args['title']; ?></span></h2>

			<div class="inside">
				<div class="submitbox" id="submitpost">
					<div id="major-publishing-actions">

						<?php if( $args['reset_text'] ){ ?>
						<div id="delete-action">
							<a data-confirm-text="<?php echo esc_attr( $tabs_args['message']['reset_confirm'] ); ?>" class="submitdelete deletion" href="<?php echo esc_url( $reset_url ); ?>"><?php echo $args['reset_text']; ?></a>
						</div><!-- #delete-action -->
						<?php } ?>

						<?php if( $args['save_text'] ){ ?>
						<div id="publishing-action">
							<span class="spinner"></span>
							<?php submit_button( esc_attr( $args['save_text'] ), 'primary', 'submit', false );?>
						</div><!-- #publishing-action -->
						<?php } ?>

						<div class="clear"></div>
					</div><!-- #major-publishing-actions -->
				</div><!-- #submitpost -->
			</div><!-- .inside-->

		</div><!-- #submitdiv -->
		<?php
	}

	/* Fields
	------------------------------------------ */

	/**
	 * Input Field
	 * Valid for: Text, URL, Email, etc.
	 * @since 1.0.0
	 */
	public static function input_field( $args ){
		$args_default = array(
			'field_id'     => "fx-sp-field_{$args['name']}",
			'input_id'     => "fx-sp-input_{$args['name']}",
			'label'        => '',
			'name'         => '',
			'description'  => '',
			'value'        => '',
			'placeholder'  => '',
			'input_class'  => 'large-text',
			'input_type'   => 'text',
		);
		$args = wp_parse_args( $args, $args_default );
		?>
			<div id="<?php echo sanitize_html_class( $args['field_id'] );?>" class="fx-sp-field fx-sp-field-input">

				<?php if( $args['label'] ){ ?>
				<div class="fx-sp-label">
					<p>
						<label for="<?php echo sanitize_html_class( $args['input_id'] );?>">
							<?php echo $args['label']; ?>
						</label>
					</p>
				</div><!-- .fx-sp-label -->
				<?php } // label ?>

				<div class="fx-sp-content">
					<p>
						<input autocomplete="off" id="<?php echo sanitize_html_class( $args['input_id'] );?>" placeholder="<?php echo esc_attr( $args['placeholder'] );?>" type="<?php echo esc_attr( $args['input_type'] ); ?>" class="<?php echo esc_attr( $args['input_class'] ); ?>" name="<?php echo esc_attr( $args['name'] );?>" value="<?php echo $args['value']; ?>">
					</p>

					<?php if( $args['description'] ){ ?>
					<p class="fx-sp-description">
						<?php echo $args['description'];?>
					</p>
				<?php } // description ?>
				</div>

			</div><!-- .fx-sp-field -->
		<?php
	}


	/**
	 * Textarea Field
	 * @since 1.0.0
	 */
	public static function textarea_field( $args ){
		$args_default = array(
			'field_id'     => "fx-sp-field_{$args['name']}",
			'input_id'     => "fx-sp-input_{$args['name']}",
			'label'        => '',
			'name'         => '',
			'description'  => '',
			'value'        => '',
			'placeholder'  => '',
			'input_class'  => 'widefat',
		);
		$args = wp_parse_args( $args, $args_default );
		?>
			<div id="<?php echo sanitize_html_class( $args['field_id'] );?>" class="fx-sp-field fx-sp-field-textarea">

				<div class="fx-sp-label">
					<?php if( $args['label'] ){ ?>
					<p>
						<label for="<?php echo sanitize_html_class( $args['input_id'] );?>">
							<?php echo $args['label']; ?>
						</label>
					</p>
					<?php } // label ?>
				</div><!-- .fx-sp-label -->

				<div class="fx-sp-content">
					<div class="fx-sp-p">
						<textarea autocomplete="off" id="<?php echo sanitize_html_class( $args['input_id'] );?>" class="<?php echo esc_attr( $args['input_class'] ); ?>" placeholder="<?php echo esc_attr( $args['placeholder'] );?>" name="<?php echo esc_attr( $args['name'] );?>" rows="2"><?php echo esc_textarea( $args['value'] ); ?></textarea>
					</div>

					<?php if( $args['description'] ){ ?>
					<p class="fx-sp-description">
						<?php echo $args['description'];?>
					</p>
					<?php } // description ?>
				</div><!-- .fx-sp-content -->

			</div><!-- .fx-sp-field -->
		<?php
	}


	/**
	 * Select Field
	 * @since 1.0.0
	 */
	public static function select_field( $args ){
		$args_default = array(
			'field_id'     => "fx-sp-field_{$args['name']}",
			'input_id'     => "fx-sp-input_{$args['name']}",
			'label'        => '',
			'name'         => '',
			'description'  => '',
			'value'        => '',
			'placeholder'  => '',
			'input_class'  => '',
			'input_type'   => 'text',
			'option_none'  => '&mdash; Select &mdash;',
			'default'      => '',
			'choices'      => array(),
		);
		$args = wp_parse_args( $args, $args_default );
		?>
			<div id="<?php echo sanitize_html_class( $args['field_id'] );?>" class="fx-sp-field fx-sp-field-select">

				<div class="fx-sp-label">
					<?php if( $args['label'] ){ ?>
					<p>
						<label for="<?php echo sanitize_html_class( $args['input_id'] );?>">
							<?php echo $args['label']; ?>
						</label>
					</p>
					<?php } // label ?>
				</div><!-- .fx-sp-label -->

				<div class="fx-sp-content">
					<p>
						<select autocomplete="off" id="<?php echo sanitize_html_class( $args['input_id'] );?>" name="<?php echo esc_attr( $args['name'] );?>" class="<?php echo esc_attr( $args['input_class'] );?>">

							<?php if( false !== $args['option_none'] ){ ?>
							<option value="" <?php selected( $args['value'], '' ); ?>><?php echo $args['option_none']; ?></option>
							<?php } ?>

							<?php foreach( $args['choices'] as $c_value => $c_label ){ ?>
								<option value="<?php echo esc_attr( $c_value ); ?>" <?php selected( $args['value'], $c_value ); ?>><?php echo strip_tags( $c_label ); ?></option>
							<?php } ?>

						</select>
					</p>

					<?php if( $args['description'] ){ ?>
					<p class="fx-sp-description">
						<?php echo $args['description'];?>
					</p>
					<?php } // description ?>
				</div><!-- .fx-sp-content -->

			</div><!-- .fx-sp-field -->
		<?php
	}

	/* Utility Functions
	------------------------------------------ */

	/**
	 * Get Option
	 * if using single options name for multiple fields
	 * @since 1.0.0
	 */
	public static function get_option( $option, $default = '', $option_name = '' ){

		/* Bail early if no option defined */
		if ( !$option ){
			return false;
		}

		/* Get option from db */
		$get_option = get_option( $option_name );

		/* if the data is not array, return false */
		if( !is_array( $get_option ) ){
			return $default;
		}

		/* Get data if it's set */
		if( isset( $get_option[$option] ) ){
			return $get_option[$option];
		}

		/* Data is not set */
		else{
			return $default;
		}
	}

	/* Use singleton pattern
	------------------------------------------ */

	/**
	 * Returns the instance.
	 * @since  1.0.0
	 */
	public static function get_instance(){
		static $instance = null;
		if ( is_null( $instance ) ){
			$instance = new self;
		}
		return $instance;
	}

} // end class
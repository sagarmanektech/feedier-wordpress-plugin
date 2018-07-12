<?php
/**
 * Plugin Name:       Feedier
 * Description:       Smart surveys start now!
 * Version:           1.0.0
 * Author:            Alkaweb
 * Author URI:        https://alka-web.com
 * Text Domain:       alkaweb
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/2Fwebd/feedier-wordpress
 */


/*
 * Plugin constants
 */
if(!defined('FEEDIER_URL'))
	define('FEEDIER_URL', plugin_dir_url( __FILE__ ));
if(!defined('FEEDIER_PATH'))
	define('FEEDIER_PATH', plugin_dir_path( __FILE__ ));

/*
 * Main class
 */
/**
 * Class Feedier
 *
 * This class creates the option page and add the web app script
 */
class Feedier
{

	/**
	 * The security nonce
	 *
	 * @var string
	 */
	private $_nonce = 'feedier_admin';

	/**
	 * The option name
	 *
	 * @var string
	 */
	private $option_name = 'feedier_data';

	/**
	 * Feedier constructor.
     *
     * The main plugin actions registered for WordPress
	 */
	public function __construct()
    {

	    add_action('wp_footer', array( $this, 'addFooterCode'));

		// Admin page calls:
		add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );
		add_action( 'wp_ajax_store_admin_data', array( $this, 'storeAdminData' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'addAdminScripts' ) );

	}

	/**
	 * Returns the saved options data as an array
     *
     * @return array
	 */
	private function getData() {
	    return get_option($this->option_name, array());
    }

	/**
	 * Callback for the Ajax request
	 *
	 * Updates the options data
     *
     * @return void
	 */
	public function storeAdminData()
    {

		if (wp_verify_nonce($_POST['security'], $this->_nonce ) === false)
			die('Invalid Request!');

		$data = $this->getData();

		foreach ($_POST as $field=>$value) {

		    if (substr($field, 0, 8) !== "feedier_" || empty($value))
				continue;

		    // We remove the feedier_ prefix to clean things up
		    $field = substr($field, 8);

			$data[$field] = $value;

		}

		update_option($this->option_name, $data);

		echo __('Saved!', 'feedier');
		die();

	}

	/**
	 * Adds Admin Scripts for the Ajax call
	 */
	public function addAdminScripts()
    {

	    wp_enqueue_style('feedier-admin', FEEDIER_URL. 'assets/css/admin.css', false, 1.0);

		wp_enqueue_script('feedier-admin', FEEDIER_URL. 'assets/js/admin.js', array(), 1.0);

		$admin_options = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'_nonce'   => wp_create_nonce( $this->_nonce ),
		);

		wp_localize_script('feedier-admin', 'feedier_exchanger', $admin_options);

	}

	/**
	 * Adds the Feedier label to the WordPress Admin Sidebar Menu
	 */
	public function addAdminMenu()
    {
		add_menu_page(
			__( 'Feedback', 'feedier' ),
			__( 'Feedback', 'feedier' ),
			'manage_options',
			'feedier',
			array($this, 'adminLayout'),
			'dashicons-testimonial'
		);
	}

	/**
	 * Make an API call to the Feedier API and returns the response
     *
     * @param string $private_key
     * @return array
	 */
	private function getSurveys($private_key)
    {

        $data = array();

	    $response = wp_remote_get('https://api.feedier.com/v1/carriers/?api_key='. $private_key);

	    if (is_array($response) && !is_wp_error($response)) {
		    $data = json_decode($response['body'], true);
	    }

	    return $data;

    }

	/**
	 * Outputs the Admin Dashboard layout containing the form with all its options
     *
     * @return void
	 */
	public function adminLayout()
    {

		$data = $this->getData();

	    $surveys = $this->getSurveys($data['private_key']);

		?>

		<div class="wrap">

            <h1><?php _e('Feedier Settings - start collecting real Feedback!', 'feedier'); ?></h1>


            <form id="feedier-admin-form" class="postbox">

                <div class="form-group inside">

                    <h3>
		                <?php if (empty($surveys) || isset($surveys['error'])): ?>
                            <span class="dashicons dashicons-no-alt error-message"></span>
		                <?php else: ?>
                            <span class="dashicons dashicons-yes success-message"></span>
		                <?php endif; ?>
		                <?php _e('Feedier API Settings', 'feedier'); ?></h3>

                    <p>
	                    <?php _e('Make sure you have a Feedier account first, it\'s free! 👍', 'feedier'); ?>
		                <?php _e('You can <a href="https://feedier.com" target="_blank">create an account here</a>.', 'feedier'); ?>
		                <br>
                        <?php _e('If so you can find your api keys from your <a href="https://dashboard.feedier.com/#/integrations" target="_blank">integrations page</a>.', 'feedier'); ?>
                    </p>

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <td scope="row">
                                    <label><?php _e( 'Public key', 'feedier' ); ?></label>
                                </td>
                                <td>
                                    <input name="feedier_public_key"
                                           id="feedier_public_key"
                                           class="regular-text"
                                           type="text"
                                           value="<?php echo (isset($data['public_key'])) ? $data['public_key'] : ''; ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td scope="row">
                                    <label><?php _e( 'Private key', 'feedier' ); ?></label>
                                </td>
                                <td>
                                    <input name="feedier_private_key"
                                           id="feedier_private_key"
                                           class="regular-text"
                                           type="text"
                                           value="<?php echo (isset($data['private_key'])) ? $data['private_key'] : ''; ?>"/>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>


	            <?php if (!empty($data['private_key']) && !empty($data['public_key'])): ?>

                    <hr>

                    <div class="form-group inside">

                        <h3>
                            <span class="dashicons dashicons-yes success-message"></span>
		                    <?php _e('Engager widget options', 'feedier'); ?>
                        </h3>

                        <table class="form-table">
                            <tbody>
                                <?php
                                // if we don't even have a response from the API
                                if (empty($surveys)) : ?>

                                    <tr>
                                        <td>
                                            <p class="notice notice-error">
                                                <?php _e( 'An error happened on the WordPress side. Make sure your server allows remote calls.', 'feedier' ); ?>
                                            </p>
                                        </td>
                                    </tr>

                                <?php
                                // If we have an error returned by the API
                                elseif (isset($surveys['error'])): ?>

                                    <tr>
                                        <td>
                                            <p class="notice notice-error">
                                                <?php echo $surveys['error']; ?>
                                            </p>
                                        </td>
                                    </tr>

                                <?php
                                // If the surveys were returned
                                else: ?>

                                    <tr>
                                        <td scope="row">
                                            <label><?php _e( 'Feedback Carrier', 'feedier' ); ?></label>
                                        </td>
                                        <td>
                                            <select name="feedier_widget_carrier_id"
                                                    id="feedier_widget_carrier_id">
                                                <?php
                                                // We loop through the surveys
                                                foreach ($surveys['data'] as $survey) : ?>
                                                    <?php
                                                    // We also only keep the id -> x from the carrier_x returned by the API
                                                    $survey['id'] = substr($survey['id'], 8); ?>
                                                    <option value="<?php echo $survey['id']; ?>" <?php echo ($survey['id'] === $data['widget_carrier_id']) ? 'selected' : '' ?>>
                                                        <?php echo $survey['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td scope="row">
                                            <label>
                                                <?php _e( 'Display probability', 'feedier' ); ?>
                                                <br>
                                                <small><?php _e( '(from 0 to 100)', 'feedier' ); ?></small>
                                            </label>
                                        </td>
                                        <td>
                                            <input name="feedier_widget_display_probability"
                                                   id="feedier_widget_display_probability"
                                                   type="text"
                                                   class="regular-text"
                                                   value="<?php echo (isset($data['widget_display_probability'])) ? $data['widget_display_probability'] : '100'; ?>"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td scope="row">
                                            <label>
                                                <?php _e( 'Shaking effect', 'feedier' ); ?>
                                                <br>
                                                <small><?php _e( '(after 10s without any click)', 'feedier' ); ?></small>
                                            </label>
                                        </td>
                                        <td>
                                            <input name="feedier_widget_shake"
                                                   id="feedier_widget_shake"
                                                   type="checkbox"
                                                <?php echo (isset($data['widget_shake']) && $data['widget_shake']) ? 'checked' : ''; ?>/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td scope="row">
                                            <label><?php _e( 'Position', 'feedier' ); ?></label>
                                        </td>
                                        <td>
                                            <select name="feedier_widget_position"
                                                    id="feedier_widget_position">
                                                <option value="left" <?php echo (!isset($data['widget_position']) || (isset($data['widget_position']) && $data['widget_position'] === 'left')) ? 'checked' : ''; ?>>
                                                    <?php _e( 'Left side', 'feedier' ); ?>
                                                </option>
                                                <option value="right" <?php echo (isset($data['widget_position']) && $data['widget_position'] === 'right') ? 'checked' : ''; ?>>
                                                    <?php _e( 'Right side', 'feedier' ); ?>
                                                </option>
                                            </select>
                                        </td>
                                    </tr>

                                <?php endif; ?>
                            </tbody>
                        </table>

                    </div>

                <?php endif; ?>

                <hr>


                <div class="inside">
                    <button class="button button-primary" id="feedier-admin-save" type="submit"><?php _e( 'Save', 'feedier' ); ?></button>
                </div>

            </form>

		</div>

		<?php

	}

	/**
     * Add the web app code to the page's footer
     *
     * This contains the widget markup used by the web app and the widget API call on the frontend
     * We use the options saved from the admin page
     *
     * @return void
     */
	public function addFooterCode()
    {

        $data = $this->getData();

        // Only if the survey id is selected and saved
        if(empty($data) || !isset($data['widget_carrier_id']))
            return;

        ?>
        <div class="feedier-widget"
             data-type="engager"
             data-position="<?php echo (isset($data['widget_position'])) ? $data['widget_position'] : 'left'; ?>"
             data-display-probability="<?php echo (isset($data['widget_display_probability'])) ? $data['widget_display_probability'] : '100'; ?>"
             data-shake="<?php echo (isset($data['widget_shake'])) ? $data['widget_shake'] : 'false'; ?>"
             data-carrier-id="<?php echo (isset($data['widget_carrier_id'])) ? $data['widget_carrier_id'] : '0'; ?>"
             data-key="<?php echo (isset($data['public_key'])) ? $data['public_key'] : '0'; ?>"></div>

        <script src="https://feedier.com/js/widgets/widgets.min.js" type="text/javascript" async></script>

        <?php

    }

}

/*
 * Starts our plugin class, easy!
 */
new Feedier();

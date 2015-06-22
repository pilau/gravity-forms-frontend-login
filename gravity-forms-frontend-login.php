<?php

/*
Plugin Name: Gravity Forms Frontend Login
Plugin URI: https://github.com/pilau/gravity-forms-frontend-login
Description: A Gravity Forms add-on to provide frontend login functionality.
Version: 1.0.0
Author: Steve Taylor
Author URI: http://sltaylor.co.uk

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/


// Activation
register_activation_hook( __FILE__, array( 'GFFrontendLogin', 'activate' ) );


if ( class_exists("GFForms") ) {
	GFForms::include_addon_framework();

	class GFFrontendLogin extends GFAddOn {

		protected $_version = "1.0.0";
		protected $_min_gravityforms_version = "1.8.22";
		protected $_slug = "gffrontendlogin";
		protected $_path = "pilau-gravity-forms-frontend-login/gravity-forms-frontend-login.php";
		protected $_full_path = __FILE__;
		protected $_title = "Gravity Forms Frontend Login";
		protected $_short_title = "Frontend Login";
		protected static $form_titles = array( 'Pilau frontend login', 'Pilau lost password' );

		/**
		 * Initialise
		 *
		 * @since	1.0.0
		 * @return	void
		 */
		public function init() {
			parent::init();

			// Add hooks
			add_filter( 'gform_field_validation', array( $this, 'validate_form' ), 10, 4 );
			add_action( 'gform_after_submission', array( $this, 'log_user_in' ), 10, 2 );
			add_action( 'gform_after_submission', array( $this, 'remove_form_entry' ), 20, 2 );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			// Any actions to be taken on the init hook (this method is hooked to init already)
			$this->block_admin();

		}

		/**
		 * Activation
		 *
		 * @since	1.0.0
		 * @return	void
		 */
		public function activate() {

			// Get all installed forms
			$gforms = GFAPI::get_forms();

			// Make sure login forms are installed
			foreach ( self::$form_titles as $form_title ) {
				$form_installed = false;

				foreach ( $gforms as $gform ) {
					if ( $gform['title'] == $form_title ) {
						$form_installed = true;
						break;
					}
				}

				if ( ! $form_installed ) {

					// Get JSON file
					$form_slug = str_replace( ' ', '-', strtolower( $form_title ) );
					$form_json_path = dirname( __FILE__ ) . '/forms/' . $form_slug . '.json';
					if ( file_exists( $form_json_path ) ) {
						// Do import
						GFExport::import_file( $form_json_path );
					}

				}

			}

		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @since	1.0.0
		 * @return	array
		 */
		public function plugin_settings_fields() {

			// Prepare roles for checkbox choices
			$role_choices = array();
			foreach ( get_editable_roles() as $role => $role_details ) {
				$role_choices[] = array(
					'label'			=> $role_details['name'],
					'name'			=> 'roles_no_admin_access_' . $role,
					'default_value'	=> 0,
				);
			}

			// Prepare pages for select choices
			$page_choices = array();
			foreach ( get_pages() as $page ) {
				$label = $page->post_title;
				// Simple indenting
				if ( $page->post_parent ) {
					$label = '- ' . $label;
				}
				$page_choices[] = array(
					'label'		=> $label,
					'value'		=> $page->ID,
				);
			}

			return array(
				array(
					"fields" => array(
						array(
							"name"		=> "roles_no_admin_access",
							"tooltip"	=> __( "Roles selected here will be blocked from the admin area. By default they will be redirected to the home page, but another page can be selected below." ),
							"label"		=> __( "Roles with no admin access", $this->_slug ),
							"type"		=> "checkbox",
							"choices"	=> $role_choices,
						),
						array(
							"name"			=> "admin_redirect",
							"tooltip"		=> __( "If roles are selected above to be blocked, they'll be redirected to this page if they try to access the admin area.", $this->_slug ),
							"label"			=> __( "Admin redirect", $this->_slug ),
							"type"			=> "select",
							"default_value"	=> get_option( 'page_on_front' ),
							"choices"		=> $page_choices,
						),
						array(
							"name"			=> "keep_entries_pilau_frontend_login",
							"label"			=> __( "Keep entries for login form?", $this->_slug ),
							"type"			=> "radio",
							"horizontal"	=> true,
							"default_value"	=> "yes",
							"choices"		=> array(
								array(
									'label'			=> __( "Yes", $this->_slug ),
									'value'			=> 'yes',
								),
								array(
									'label'			=> __( "No", $this->_slug ),
									'value'			=> 'no',
								),
							)
						),
						array(
							"name"			=> "keep_entries_pilau_lost_password",
							"label"			=> __( "Keep entries for lost password form?", $this->_slug ),
							"type"			=> "radio",
							"horizontal"	=> true,
							"default_value"	=> "no",
							"choices"		=> array(
								array(
									'label'			=> __( "Yes", $this->_slug ),
									'value'			=> 'yes',
								),
								array(
									'label'			=> __( "No", $this->_slug ),
									'value'			=> 'no',
								),
							)
						),
					)
				)
			);
		}

		/**
		 * Block certain roles from the admin
		 *
		 * @since	1.0.0
		 * @return	void
		 */
		public function block_admin() {

			if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

				// Get settings
				$settings = $this->get_plugin_settings();

				// Get current user's role
				$user = wp_get_current_user();
				$role = $user->roles[0];

				// Blocked?
				if ( ! empty( $settings[ 'roles_no_admin_access_' . $role ] ) ) {
					$redirect = empty( $settings['admin_redirect'] ) ? home_url() : get_permalink( $settings['admin_redirect'] );
					wp_redirect( $redirect );
					exit;
				}

			}
		}

		/**
		 * Admin notices
		 *
		 * @since	1.0.0
		 * @return void
		 */
		public function admin_notices() {
			$screen = get_current_screen();

			if ( $screen->id == 'forms_page_gf_entries' ) {

				// Get form
				$form = GFAPI::get_form( $_REQUEST['id'] );

				if ( ! $this->form_should_keep_entries( $form['title'] ) ) { ?>
					<div class="error">
						<p><?php printf( __( '<strong>NOTE:</strong> This form is currently set to not retain entries. To change this, head over to the %1$ssettings page%2$s.' ), '<a href="' . admin_url( 'admin.php?page=gf_settings&subview=gffrontendlogin' ) . '">', '</a>' ); ?></p>
					</div>
				<?php }

			}

		}

		/**
		 * Automatically remove form entry?
		 *
		 * @since	1.0.0
		 * @link	https://gist.github.com/intelliweb/7460525
		 * @return	void
		 */
		public function remove_form_entry( $entry, $form ) {
			global $wpdb;

			if ( in_array( $form['title'], self::$form_titles ) && ! $this->form_should_keep_entries( $form['title'] ) ) {

				$lead_id = $entry['id'];
				$lead_table = RGFormsModel::get_lead_table_name();
				$lead_notes_table = RGFormsModel::get_lead_notes_table_name();
				$lead_detail_table = RGFormsModel::get_lead_details_table_name();
				$lead_detail_long_table = RGFormsModel::get_lead_details_long_table_name();

				// Delete from detail long
				$wpdb->query( $wpdb->prepare( " DELETE FROM $lead_detail_long_table
					WHERE lead_detail_id IN(
						SELECT id FROM $lead_detail_table WHERE lead_id = %d
					)", $lead_id ) );

				// Delete from lead details
				$wpdb->query( $wpdb->prepare( "DELETE FROM $lead_detail_table WHERE lead_id = %d", $lead_id ) );

				// Delete from lead notes
				$wpdb->query( $wpdb->prepare("DELETE FROM $lead_notes_table WHERE lead_id=%d", $lead_id ) );

				// Delete from lead
				$wpdb->query( $wpdb->prepare("DELETE FROM $lead_table WHERE id = %d", $lead_id ) );

			}

		}

		/**
		 * Check if a form is set to keep entries or not
		 *
		 * @since	1.0.0
		 * @param	string	$title
		 * @return	bool
		 */
		private function form_should_keep_entries( $title ) {
			$keep = true;

			// Prepare form title to match setting
			$form_slug = str_replace( '-', '_', sanitize_title( $title ) );

			// Get settings
			$settings = $this->get_plugin_settings();

			// Is this form set to not keep entries?
			if ( isset( $settings[ 'keep_entries_' . $form_slug ] ) && $settings[ 'keep_entries_' . $form_slug ] == 'no' ) {
				$keep = false;
			}

			return $keep;
		}

		/**
		 * Validate login
		 *
		 * @since	1.0.0
		 * @param	array	$result	The validation result to be filtered
		 * @param	mixed	$value		The field value to be validated.
		 * @param	object	$form
		 * @param	object	$field
		 * @return	array
		 */
		public function validate_form( $result, $value, $form, $field ) {
			global $user;

			switch ( $form['title'] ) {

				// Login form
				case 'Pilau frontend login': {

					// Username
					if ( $field['cssClass'] === 'username' ) {
						$user = get_user_by( 'login', $value );
						if ( empty( $user->user_login ) ) {
							$result["is_valid"] = false;
							$result["message"] = "Invalid username provided.";
						}
					}

					// Password
					if ( $field['cssClass'] === 'password' ) {
						if ( ! $user or ! wp_check_password( $value, $user->data->user_pass, $user->ID ) ) {
							$result["is_valid"] = false;
							$result["message"] = "Invalid password provided.";
						}
					}

					break;
				}

				// Lost password form
				case 'Pilau lost password': {

					break;
				}

			}

			return $result;
		}

		/**
		 * Log user in after form submission
		 *
		 * @since	1.0.0
		 * @param	array	$entry
		 * @param	object	$form
		 * @return	array
		 */
		public function log_user_in( $entry, $form ) {

			// Create the credentials array
			$creds['user_login'] = $entry[1];
			$creds['user_password'] = $entry[2];
			$creds['remember'] = $entry[3];

			// Sign in the user and set them as the logged-in user
			$sign = wp_signon( $creds );
			wp_set_current_user( $sign->ID );

		}

	}

	new GFFrontendLogin();
}
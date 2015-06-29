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
		//protected static $form_titles = array( 'Pilau frontend login', 'Pilau lost password' );
		protected static $form_titles = array( 'Pilau frontend login' );

		/**
		 * Initialise
		 *
		 * @since	1.0.0
		 * @return	void
		 */
		public function init() {
			parent::init();

			// Add hooks
			add_filter( 'gform_field_validation', array( $this, 'field_validation' ), 10, 4 );
			add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_filter( 'login_url', array( $this, 'login_url' ), 9999, 2 );

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
							"name"			=> "login_page",
							"label"			=> __( "Login page", $this->_slug ),
							"type"			=> "select",
							"default_value"	=> get_option( 'page_on_front' ),
							"choices"		=> $page_choices,
						),
						array(
							"name"			=> "login_page_not_if_admin",
							"label"			=> __( "Let admin logins use WP login page?", $this->_slug ),
							"tooltip"		=> __( "Admin logins are detected based on whether the user tried to first access the URLs /admin, /wp-admin, or if there's no referrer to wp-login.php", $this->_slug ),
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
						/*
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
						*/
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
		 * Field validation
		 *
		 * @since	1.0.0
		 * @param	array	$result		The validation result to be filtered
		 * @param	mixed	$value		The field value to be validated.
		 * @param	object	$form
		 * @param	object	$field
		 * @return	array
		 */
		public function field_validation( $result, $value, $form, $field ) {
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

			}

			return $result;
		}

		/**
		 * After submission actions
		 *
		 * @since	1.0.0
		 * @param	array	$entry
		 * @param	object	$form
		 * @return	array
		 */
		public function after_submission( $entry, $form ) {
			global $wpdb;

			/*
			 * Remove form entries if necessary
			 * @link	https://gist.github.com/intelliweb/7460525
			 */

			// First of all, never keep password fields
			$password_field = $this->get_field_by_meta( $form, 'cssClass', 'password' );
			$password_value = null;
			if ( $password_field ) {
				$password_value = $entry[ $password_field->id ];
				$entry[ $password_field->id ] = '';
				$this->delete_entry_data( $entry['id'], $password_field->id );
			}

			// Now check about removing the whole entry
			if ( in_array( $form['title'], self::$form_titles ) && ! $this->form_should_keep_entries( $form['title'] ) ) {
				$this->delete_entry_data( $entry['id'] );
			}

			/*
			 * Any other submission actions?
			 */

			switch ( $form['title'] ) {

				// Login form
				case 'Pilau frontend login': {

					// Create the credentials array
					$creds[ 'user_login' ] = $entry[ 1 ];
					$creds[ 'user_password' ] = $password_value;
					$creds[ 'remember' ] = $entry[ 3 ];

					// Sign in the user and redirect
					$sign = wp_signon( $creds );
					wp_set_current_user( $sign->ID );
					wp_redirect( apply_filters( 'login_redirect', home_url() ) );
					exit;

				}

			}

		}

		/**
		 * Filter login URL
		 *
		 * @since	1.0.0
		 * @param	string		$login_url
		 * @param	string		$redirect
		 * @return	string
		 */
		public function login_url( $login_url, $redirect ) {
			$settings = $this->get_plugin_settings();

			// Redirect to frontend login page?
			if ( strpos( $_SERVER['REQUEST_URI'], 'wp-admin' ) === false || ( isset( $settings['login_page_not_if_admin'] ) && $settings['login_page_not_if_admin'] == 'no' ) ) {
				$login_url = get_permalink( $settings['login_page'] );
			}

			return $login_url;
		}

		/**
		 * Return a field from a form object, based on a field meta value
		 *
		 * @since	1.1.0
		 * @param	array	$form
		 * @param	string	$field_meta_key
		 * @param	string	$field_meta_value
		 * @param	string	$checked_nested		Check this nested array of fields, e.g. 'inputs'
		 * @return	mixed						If $checked_nested, returns an array in the format:
		 * 										array( $field, [key of nested field] )
		 */
		private function get_field_by_meta( $form, $field_meta_key, $field_meta_value, $checked_nested = null ) {
			$the_field = false;
			$got_it = false;

			// Try to find field
			if ( isset( $form['fields'] ) && is_array( $form['fields'] ) ) {
				foreach ( $form['fields'] as $field ) {
					foreach ( $field as $key => $value ) {
						if ( $key == $checked_nested && is_array( $value ) ) {

							// Go through nested fields
							foreach ( $value as $nested_key => $nested_value ) {
								if ( array_key_exists( $field_meta_key, $nested_value ) && $nested_value[ $field_meta_key ] == $field_meta_value ) {
									$the_field = array( $field, $nested_key );
									$got_it = true;
								}
							}
							if ( $got_it ) {
								break;
							}

						} else if ( $key == $field_meta_key && $field_meta_value == $value ) {

							// Got it
							$the_field = $field;
							$got_it = true;
							break;

						}
					}
					if ( $got_it ) {
						break;
					}
				}
			}

			return $the_field;
		}

		/**
		 * Delete entry data (all for lead, or just one field)
		 *
		 * @since	1.1.0
		 * @param	int		$lead_id
		 * @param	int		$field_id
		 * @return	void
		 */
		private function delete_entry_data( $lead_id, $field_id = null ) {
			global $wpdb;

			// Get table names
			$lead_table = RGFormsModel::get_lead_table_name();
			$lead_notes_table = RGFormsModel::get_lead_notes_table_name();
			$lead_detail_table = RGFormsModel::get_lead_details_table_name();
			$lead_detail_long_table = RGFormsModel::get_lead_details_long_table_name();

			if ( $field_id ) {

				// Only delete data related to specified field

				// Delete from detail long
				$wpdb->query( $wpdb->prepare( " DELETE FROM $lead_detail_long_table
					WHERE lead_detail_id IN(
						SELECT id FROM $lead_detail_table WHERE lead_id = %d AND field_number = %d
					)", $lead_id, $field_id ) );

				// Delete from lead details
				$wpdb->query( $wpdb->prepare( "DELETE FROM $lead_detail_table WHERE lead_id = %d AND field_number = %d", $lead_id, $field_id ) );

			} else {

				// Delete all data related to entry/lead

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

	}

	new GFFrontendLogin();
}
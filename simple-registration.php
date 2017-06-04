<?php
/*
Plugin Name: Simple Regstration
Plugin URI: https://geek.hellyer.kiwi/plugins/simple-registration/
Description: Simple Regstration
Version: 1.0
Author: Ryan Hellyer
Author URI: https://geek.hellyer.kiwi/
Text Domain: simple-registration
License: GPL2

------------------------------------------------------------------------
Copyright Ryan Hellyer

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


/**
 * WordPress registration class.
 *
 * @version   1.0
 * @copyright Copyright (c), Ryan Hellyer
 * @author Ryan Hellyer <ryanhellyer@gmail.com>
 */
class Simple_Registration {

	public $fields;
	public $data_string;

	/**
	 * Fire the constructor up :)
	 */
	public function __construct( ) {

		$this->data_string = home_url() . date( 'Ymd' );

		$this->fields = array(
			'username'   => __( 'Username', 'simple-registration' ),
			'first-name' => __( 'First Name', 'simple-registration' ),
			'last-name'  => __( 'Last Name', 'simple-registration' ),
			'password'   => __( 'Password', 'simple-registration' ),
			'email'      => __( 'Email', 'simple-registration' ),
		);

		add_shortcode( 'simple-registration', array( $this, 'shortcode' ) );
		add_action( 'init', array( $this, 'process_registration' ), 20 );
	}

	/**
	 * Output the shortcode.
	 */
	public function shortcode() {
		if ( is_user_logged_in() ) {
		} else {
			$this->registration_form();
//			$this->login_form();
		}

	}

	/**
	 * Registration form.
	 */
	public function registration_form() {

		$this->fields = apply_filters( 'simple-registration-fields', $this->fields );

		// Add error message
		if ( isset( $_POST['error'] ) ) {
			echo '<p class="error" >' . esc_html( $_POST['error'] ) . '</p>';
		}

		// Output registration form
		echo '
		<form method="POST" action="">';

		foreach ( $this->fields as $key => $text ) {
			echo '
			<p>
				<label for="' . esc_attr( $key ) . '">' . esc_html( $text ) . '</label>
				<input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="text" value="" />
			</p>';

		}

		echo '
			<p>
				<input type="submit" value="' . esc_html__( 'Register', 'simple-registration' ) . '" />
				<input type="hidden" name="registration-data" value="' . md5( $this->data_string . date( 'H' ) ) . '" />
			</p>
		</form>';
	}

	/**
	 * Login form.
	 */
	public function login_form() {
		wp_login_form();
	}

	/**
	 * Process registration form data.
	 */
	public function process_registration() {
		$this->fields = apply_filters( 'simple-registration-fields', $this->fields );

		$hour_of_day = date( 'H' );

		// Perform security check (checks if post was submitted in the past hour)
		if (
			isset( $_POST['registration-data'] )
			&&
			(
				$_POST['registration-data'] === md5( $this->data_string . ( $hour_of_day - 1 ) )
				||
				$_POST['registration-data'] === md5( $this->data_string . ( $hour_of_day ) )
				||
				$_POST['registration-data'] === md5( $this->data_string . ( $hour_of_day + 1 ) )
			)

		) {

			$fields = array();
			foreach ( $this->fields as $key => $text ) {

				if ( ! isset( $_POST[$key] ) ) {
					wp_die( 'Error: Field missing' );
					echo '<p>Error: Field missing</p>';
					exit;
				}

				$fields[$key] = wp_kses_post( $_POST[$key] );

			}

			// Create the user
			$user_id = wp_create_user(
				$fields['username'],
				$fields['password'],
				$fields['email']
			);

			// Fire error if user registration failed
			if ( isset( $user_id->errors['existing_user_login'][0] ) ) {
				$_POST['error'] = esc_html( $user_id->errors['existing_user_login'][0] );
			} else if ( is_wp_error( $user_id ) ) {
				$_POST['error'] = esc_html__( 'There was an error during the registration process', 'simple-registration' );
			} else {

				// Add user meta
				foreach ( $this->fields as $key => $text ) {

					if (
						'username' !== $key
						&&
						'password' !== $key
						&&
						'email' !== $key
					) {

						update_user_meta(
							$user_id,
							wp_kses_post( $key ),
							wp_kses_post( $text )
						);

					}

				}

				// Log the user in automatically
				wp_clear_auth_cookie();
				wp_set_current_user ( $user_id );
				wp_set_auth_cookie  ( $user_id);

				// Redirect after login
				$redirect_to = user_admin_url();
				$redirect_to = apply_filters( 'simple-registration-redirect', $redirect_to );
				wp_safe_redirect( $redirect_to );
				exit();

			}

		}

	}

}
new Simple_Registration;

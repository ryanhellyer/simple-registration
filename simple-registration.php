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
	public $error_messages;
	public $errors;
	public $confirm;
	public $invalid_username;

	/**
	 * Fire the constructor up :)
	 */
	public function __construct( ) {

		$this->data_string = home_url() . date( 'Ymd' );

		$this->fields = array(
			'username'   => esc_html__( 'Username', 'simple-registration' ),
			'password'   => esc_html__( 'Password', 'simple-registration' ),
			'email'      => esc_html__( 'Email', 'simple-registration' ),
		);

		add_shortcode( 'simple-registration', array( $this, 'shortcode' ) );
		add_action( 'init', array( $this, 'process_form' ), 20 );
	}

	/**
	 * Output the shortcode.
	 */
	public function shortcode() {

		if ( ! is_user_logged_in() ) {
			return $this->registration_form();
		}

	}

	/**
	 * Registration form.
	 *
	 * @return  string  The shortcodes content
	 */
	public function registration_form() {

		// User said they're not registering, so don't load registration confirmation page
		if ( isset( $_POST['simple-registration-no'] ) ) {
			$this->confirm = false;
		}

		$this->fields = apply_filters( 'simple-registration-fields', $this->fields );
		$content = '';

		// Add error messages
		if ( isset( $_POST['simple-registration-invalid-username'] ) ) {
			$this->error_messages[] = '<strong>' . esc_html__( 'Error: ', 'src' ) . '</strong> ' . esc_html__( 'The username entered does not exist.', 'src' );
			$this->errors['username'] = true;
		}
		if ( isset( $this->error_messages ) ) {  //wp_kses_post( 

			$error_message = '';
			foreach ( $this->error_messages as $key => $message ) {
				$error_message .= $message;
			}

			$content .= '<p class="error-message" >' . wp_kses_post( $error_message ) /* Using kses as errors may contain HTML */ . '</p>';
		}

		// Hide most of the form when on confirmation of registration view
		if ( true === $this->confirm ) {
			$content .= '<style>.simple-registration p {display: none;} .simple-registration p.simple-registration-submit {display: block;}</style>';

			$content .= '<p>' . esc_html__( 'Do you wish to register a new account?', 'src' ) . '</p>';
		}

		// Output registration form
		$content .= '
		<form class="simple-registration" method="POST" action="">';

		foreach ( $this->fields as $key => $text ) {

			// Set the values if already submitted (occurs when errors present)
			$value = '';
			if ( isset( $_POST[$key] ) ) {
				$value = $_POST[$key];
			}

			// Set error class if error found
			$class = '';
			if ( isset( $this->errors[$key] ) ) {
				$class = ' class="error"';
			}

			$content .= '
			<p' . $class /* Not escaped as may contain attribute code */ . '>
				<label for="' . esc_attr( $key ) . '">' . esc_html( $text ) . '</label>
				<input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="text" value="' . esc_attr( $value ) . '" />
			</p>';

		}

		$content .= '
			<p class="simple-registration-submit">';

		// Display different submit fields when on registration confirmation view
		if ( true == $this->confirm ) {

			if ( true === $this->invalid_username ) {
				$content .= '
				<input type="hidden" name="simple-registration-invalid-username" value="1" />';
			}

			$content .= '
				<input name="simple-registration-yes" class="simple-registration-yes" type="submit" value="' . esc_html__( 'Yes, I want to register a new account', 'simple-registration' ) . '" />
				<input name="simple-registration-no" class="simple-registration-no" type="submit" value="' . esc_html__( 'No, I was just trying to log in', 'simple-registration' ) . '" />';
		} else {

			if ( isset( $_POST['simple-registration-invalid-username'] ) ) {
				$submit_text = __( 'Login', 'simple-registration' );
			} else {
				$submit_text = __( 'Login / Register', 'simple-registration' );
			}

			$content .= '
				<input type="submit" value="' . esc_attr( $submit_text ) . '" />';
		}

		$content .= '
			</p>
		</form>';

		return $content;
	}

	/**
	 * Process the form data.
	 * Performs security checks, then checks if logged in and if not, registers the user.
	 */
	public function process_form() {
		$this->fields = apply_filters( 'simple-registration-fields', $this->fields );

		// Bail out if all form fields haven't been filled out.
		foreach ( $this->fields as $key => $field ) {

			if (
				! isset( $_POST[$key] )
			) {
				return;
			}

		}

		// Serve relevant errors when incorrect data is submitted
		if ( '' === $_POST['password'] ) {
			$error_messages[] = esc_html__( 'Please enter a password.', 'src' );
			$this->errors['password'] = true;
		}
		if (
			'' !== $_POST['username']
			&&
			true !== validate_username( $_POST['username'] )
		) {
			$error_messages[] = esc_html__( 'The username entered was not valid.', 'src' );
			$this->errors['username'] = true;
		}
		if (
			'' === $_POST['username']
			&&
			'' === $_POST['email']
		) {
			$error_messages[] = esc_html__( 'Please enter a username and email address to register.', 'src' );
		}
		if ( $_POST['email'] !== sanitize_email( $_POST['email'] ) ) {
			$error_messages[] = esc_html__( 'The email address entered was not valid.', 'src' );
			$this->errors['email'] = true;
		}
		if ( isset( $error_messages ) ) {

			$this->error_messages[$key] = '<strong>' . esc_html__( 'Error:', 'simple-registration' ) . '</strong> ';
			foreach ( $error_messages as $key => $message ) {
				 $this->error_messages[$key] .= $message . ' ';
			}

			return;
		}

		// Process the form, since no errors encoutnered
		$this->attempt_user_login();
		if ( isset( $_POST['simple-registration-yes'] ) ) {
			$this->register_new_user();
		}

	}

	/**
	 * Attempt to log the user in.
	 * If no username specified, then it looks for the username by email address.
	 *
	 * @return  bool  True if user logged in, false if error
	 */
	public function attempt_user_login() {

		$credentials = array();
		$credentials['user_login'] = $_POST['username'];
		$credentials['user_password'] = $_POST['password'];
		$credentials['remember'] = true;

		if ( '' !== $_POST['email'] && '' === $credentials['user_login'] ) {
			$user_check = get_user_by( 'email', $_POST['email'] );
			if ( isset( $user_check->data->user_login ) ) {
				$credentials['user_login'] = $user_check->data->user_login;
			}
		}

		$user = wp_signon( $credentials, false );
		if ( is_wp_error( $user ) ) {

			// Don't worry about invalid username errors, since we're just going to register them if they get it wrong anyway
			if ( ! isset( $user->errors['invalid_username'] ) ) {
				$this->error_messages[] = $user->get_error_message();
			} else {
				$this->invalid_username = true;
			}

			// Set var here so that most of the form can be hidden and replaced with a confirmation page
			$this->confirm = true;

			return;
		}
		// Redirect after login
		$redirect_to = user_admin_url();
		$redirect_to = apply_filters( 'simple-login-redirect', $redirect_to );
		wp_safe_redirect( $redirect_to );
		exit();

	}

	/**
	 * Register the new user.
	 */
	public function register_new_user() {
		$fields = array();
		foreach ( $this->fields as $key => $text ) {

			if ( ! isset( $_POST[$key] ) ) {
				$error_messages[] = esc_html__( 'Some fields were missing.', 'src' );
			}

			$fields[$key] = wp_kses_post( $_POST[$key] );

		}

		// Bail out if no email address set, as we don't want people registering without one
		if ( '' === $_POST['email'] ) {

			$error_messages[] = esc_html__( 'Please enter an email address to register.', 'src' );
			$this->errors['email'] = true;

		} else {

			// Create the user
			$user_id = wp_create_user(
				$fields['username'],
				$fields['password'],
				$fields['email']
			);

			// Fire error if user registration failed
			if ( isset( $user_id->errors['existing_user_login'][0] ) ) {
				$this->error_messages[] = esc_html( $user_id->errors['existing_user_login'][0] );
			} else if ( is_wp_error( $user_id ) ) {
				$this->error_messages[] = esc_html__( 'Something went wrong during the registration process.', 'simple-registration' );
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

		// Format error messages
		if ( isset( $error_messages ) ) {

			$this->error_messages[$key] = '<strong>' . esc_html__( 'Error:', 'simple-registration' ) . '</strong> ';
			foreach ( $error_messages as $key => $message ) {
				 $this->error_messages[$key] .= $message . ' ';
			}

		}

	}

}
new Simple_Registration;

<?php
/**
 * Plugin Name: GitHub User Repo Widget
 * Plugin URI: https://github.com/jaredatch/github-user-repo-widget/
 * Description: A simple widget that will show a list of repos for a specified GitHub user. Optionally can display a GitHub follow badge as well.
 * Version: 1.0.0
 * Author: Jared Atchison
 * Author URI: http://jaredatchison.com 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author     Jared Atchison
 * @version    1.0.0
 * @package    GitHubUserRepoWidget
 * @copyright  Copyright (c) 2012, Jared Atchison
 * @link       http://jaredatchison.com
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/**
 * Github user repo widget
 *
 * @since   1.0.0
 * @package GitHubUserRepoWidget
 */
class ja_github_repo_Widget extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @since 1.0
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	function __construct() {

		// Widget defaults
		$this->defaults = array(
			'title'    => '',
			'username' => '',
			'badge'    => 0,
		);

		// Widget basics
		$widget_ops = array(
			'classname'   => 'guthub-repo-widget',
			'description' => 'Lists a user\'s repos from GitHub.'
		);

		// Widget controls
		$control_ops = array(
			'id_base' => 'ja-github',
		);

		// Load widget
		$this->WP_Widget( 'ja-github', 'GitHub Repos', $widget_ops, $control_ops );

	}

    /**
     * Outputs the HTML for this widget.
     *
     * @since 1.0
     * @param array  $args An array of standard parameters for widgets in this theme 
     * @param array  $instance An array of settings for this widget instance 
     */
	function widget( $args, $instance ) {

		extract( $args );

		// Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );
		$username = esc_attr( $instance['username'] );

		echo $before_widget;

		// Show title if provided
		if ( !empty( $instance['title'] ) ) { echo $before_title . $instance['title'] . $after_title; }

		// Check for transient
		$repos = get_transient( 'ja_github_repos_' . $username );

		// If the was no transiet let's cook one up
		if ( !$repos ) {

			// Ping GitHub API
			$repos = wp_remote_post( 'https://api.github.com/users/' . $username . '/repos', array( 'method' => 'GET' ) );

			// Check to make sure GitHub gave us the green light
			if ( $repos['response']['message'] == 'OK' ) {
				
				// Decode response
				$repos = json_decode( $repos['body'] );

			} else {

				// Something fucked up, note that
				$repos = 'error';

			}

			// Save to a transient and keep for 6 hours
			set_transient( 'ja_github_repos_' . $username , $repos, 21600 );

		}

		// Check to see if pinging the API resulted in an error
		if ( $repos == 'error' ) {

			echo 'An error occured with the GitHub API. Please try again later.';

		} else {

			// No error, so build the list of repos
			echo '<ul>';
			foreach ( $repos as $repo ) {
				echo '<li><a href="' . $repo->html_url . '">' . $repo->name . '</a></li>';
			}
			echo '</ul>';

		}

		// Show GitHub follow badge if set
		if ( $instance['badge'] == 1 ) {
			echo '<iframe src="http://ghbtns.com/github-btn.html?user=' . $username . '&type=follow&count=true" allowtransparency="true" frameborder="0" scrolling="0" width="165px" height="20px" style="display:block;margin:15px auto 0;"></iframe>';
		}

		echo $after_widget;

	}

    /**
     * Deals with the settings when they are saved by the admin. Here is
     * where any validation should be dealt with.
     *
     * @since 1.0
     * @param array  $new_instance An array of new settings as submitted by the admin
     * @param array  $old_instance An array of the previous settings 
     * @return array The validated and (if necessary) amended settings
     */
	function update( $new_instance, $old_instance ) {

		$new_instance['title']    = strip_tags( $new_instance['title'] );
		$new_instance['username'] = strip_tags( $new_instance['username'] );
		$new_instance['badge']    = intval( $new_instance['badge'] );

		return $new_instance;

	}

    /**
     * Displays the form for this widget on the Widgets page of the WP Admin area.
     *
     * @since 1.0
     * @param array $instance An array of the current settings for this widget
     */
	function form( $instance ) {

		// Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'username' ); ?>">Github username:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php echo esc_attr( $instance['username'] ); ?>" class="widefat" />
		</p>
		<p>
			<input id="<?php echo $this->get_field_id( 'badge' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'badge' ); ?>" value="1" <?php checked( $instance['badge'], 1 ); ?>/>
			<label for="<?php echo $this->get_field_id( 'badge' ); ?>">Show GitHub follow button</label>
		</p>
		<?php
	}
}
add_action( 'widgets_init', create_function( '', "register_widget('ja_github_repo_Widget');" ) );
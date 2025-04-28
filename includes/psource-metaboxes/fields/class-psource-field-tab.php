<?php

class PSOURCE_Field_Tab extends PSOURCE_Field {
	/**
	 * Runs on creation of parent
	 *12.3.20 alles fein DN
	 * @since 1.0
	 * @access public
	 *
	 * @param array $args {
	 *        An array of arguments. Optional.
	 *
	 * @type string $slug The tab slug.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive( array(
			'slug'        => '',
			'tab_content' => ''
		), $args );
	}

	/**
	 * Saves the field to the database.
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 *
	 * @param int $post_id
	 * @param string $meta_key The meta key to use when storing the field value. Defaults to null.
	 * @param mixed $value The value of the field. Defaults to null.
	 * @param bool $force Whether to bypass the is_subfield check. Subfields normally don't run their own save routine. Defaults to false.
	 */
	public function save_value( $post_id, $meta_key = null, $value = null, $force = false ) {
		// Don't save to db
	}

	/**
	 * Display the field
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param int /string $post_id
	 */
	public function display( $post_id ) {
		?>
		<input type="hidden" <?php echo $this->parse_atts(); ?> value=""/>
		<a class="psource-field-tab-anchor" name="<?php echo $this->args['slug']; ?>"></a>
		<div class="psource-field-tab-desc" id="<?php echo $this->args['slug'] ?>"><?php
			if ( ( $field = $this->args['tab_content'] ) instanceof PSOURCE_Field ) {
				do_action( 'psource_tab_field_display_' . $this->args['slug'] );
			} else {
				echo wpautop( $this->args['tab_content'] );
			}
			?></div>
		<?php
	}
}
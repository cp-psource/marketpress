<?php

class PSOURCE_Field_Section extends PSOURCE_Field {
	/**
	 * Runs on parent construct
	 *12.3.20 alles fein DN
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments. Optional.
	 *
	 *		@type string $title The title of the section.
	 *		@type string $subtitle The subtitle of the section.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'title' => __('Abschnittsüberschrift', 'psource_metaboxes'),
			'subtitle' => '',
		), $args);
	}

	/**
	 * Saves the field to the database.
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 * @param int $post_id
	 * @param string $meta_key The meta key to use when storing the field value. Defaults to null.
	 * @param mixed $value The value of the field. Defaults to null.
	 * @param bool $force Whether to bypass the is_subfield check. Subfields normally don't run their own save routine. Defaults to false.
	 */
	public function save_value( $post_id, $meta_key = null, $value = null, $force = false ) {
		// Don't save to db
	}

	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$class = 'psource-field-section-wrap';
		$atts = '';

		foreach ( $this->args['custom'] as $key => $att ) {
			if ( strpos($key, 'data-conditional') !== false ) {
				$atts .= ' ' . $key . '="' . esc_attr($att) . '"';
			}
		}

		if ( strlen($atts) > 0 ) {
			$class .= ' psource-field-has-conditional';
		}

		$this->before_field(); ?>
		<input type="hidden" <?php echo $this->parse_atts(); ?> value="" />
		<div class="<?php echo $class; ?>"<?php echo $atts; ?>>
			<h2 class="psource-section-title"><?php echo $this->args['title']; ?></h2>
			<?php
			if ( ! empty($this->args['subtitle']) ) : ?>
			<p><?php echo $this->args['subtitle']; ?></p>
			<?php
			endif; ?>
		</div>
		<?php
		$this->after_field();
	}
}
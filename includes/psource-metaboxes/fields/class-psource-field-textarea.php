<?php

class PSOURCE_Field_Textarea extends PSOURCE_Field {

	/**
	 * Displays the field
	 *12.3.20 alles fein DN
	 * @since 3.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$this->before_field();
		?>
		<textarea <?php echo $this->parse_atts(); ?>><?php echo esc_textarea( stripslashes( $this->get_value( $post_id ) ) ); ?></textarea>
		<?php
		$this->after_field();
	}

}
<?php

class PSOURCE_Field_Image extends PSOURCE_Field {

	/**
	 * Runs on creation of parent
	 *Checked 12.3.20 alles fein, ev. hier Übersetzungen, DN
	 * @since 1.0
	 * @access public
	 *
	 * @param array $args {
	 * 		An array of arguments. Optional.
	 *
	 * 		@type string $preview_size The preview size of the image in wp-admin.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive( array(
			'preview_size' => 'thumbnail',
		), $args );
	}

	/**
	 * Enqueues necessary field javascript.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'media-upload' );

		// 3.5 media gallery
		if ( function_exists( 'wp_enqueue_media' ) && !did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Print necessary field javascript.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function print_scripts() {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				var buttonHtml = '<a class="button psource-image-field-select" href="javascript:;"><?php _e( 'Bild wählen', 'psource_metaboxes' ); ?></a>';

				/*
				 * When adding a new group to the repeater field reset the image preview back to a button
				 */
				$( document ).on( 'psource_repeater_field/after_add_field_group', function( e, $group ) {
					$group.find( '.psource-image-field-preview' ).replaceWith( buttonHtml )
				} );

				/*
				 * Delete image
				 */
				$( '.psource-fields' ).on( 'click', '.psource-image-field-delete', function( e ) {
					e.preventDefault();

					var $this = $( this ),
						$parent = $this.parent();

					$parent.siblings( ':hidden' ).val( '' );
					$parent.replaceWith( buttonHtml );
				} );

				/*
				 * Show the media library popup
				 */
				$( '.psource-fields' ).on( 'click', '.psource-image-field-select', function( e ) {
					e.preventDefault();

					var $this = $( this ),
						$input = ( $this.hasClass( 'psource-image-field-edit' ) ) ? $this.parent().siblings( ':hidden' ) : $this.siblings( ':hidden' ),
						frame = wp.media( {
							"title": "<?php _e( 'Wähle das Bild für diese Variation.', 'psource_metaboxes' ); ?>",
							"multiple": false,
							"library": { "type": "image" },
							"button": { "text": "<?php _e( 'Bild wählen', 'psource_metaboxes' ); ?>" }
						} );

					/*
					 * Send image data back to the calling field
					 */
					frame.on( 'select', function() {
						var selection = frame.state().get( 'selection' );

						selection.each( function( attachment ) {
							var url = attachment.attributes.sizes.hasOwnProperty( '<?php echo $this->args[ 'preview_size' ]; ?>' ) ? attachment.attributes.sizes['<?php echo $this->args[ 'preview_size' ]; ?>'].url : attachment.attributes.sizes.thumbnail.url,
								html = '<div class="psource-image-field-preview"><a class="psource-image-field-edit psource-image-field-select dashicons dashicons-edit" href="#"></a><a class="psource-image-field-delete dashicons dashicons-trash" href="#"></a><img src="' + url + '" alt="" /></div>';

							if ( $this.hasClass( 'psource-image-field-edit' ) ) {
								$this.parent().replaceWith( html );
							} else {
								$this.replaceWith( html );
							}

							$input.val( attachment.id );
						} );
					} );

					/*
					 * Set the selected image
					 */
					frame.on( 'open', function() {
						var selection = frame.state().get( 'selection' ),
							id = $input.val();

						if ( id.length ) {
							var attachment = wp.media.attachment( id );
							attachment.fetch();
							selection.add( attachment ? [ attachment ] : [ ] );
						}
					} );

					frame.open();
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Displays the field.
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$value = $this->get_value( $post_id );

		$this->before_field();

		if ( $value ) :
			$img_url = wp_get_attachment_image_src( $value, $this->args[ 'preview_size' ] );
			?>
			<div class="psource-image-field-preview"><a class="psource-image-field-edit psource-image-field-select dashicons dashicons-edit" href="#"></a><a class="psource-image-field-delete dashicons dashicons-trash" href="#"></a><img src="<?php echo $img_url[ 0 ]; ?>" alt="" /></div>
			<?php else :
			?>
			<a class="button psource-image-field-select" href="javascript:;"><?php _e( 'Bild wählen', 'psource_metaboxes' ); ?></a>
		<?php endif; ?>

		<input type="hidden" <?php echo $this->parse_atts(); ?> value="<?php echo $value; ?>" />
		<?php
		$this->after_field();
	}

}
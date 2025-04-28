<?php

class PSOURCE_Field_Images extends PSOURCE_Field {

	/**
	 * Runs on creation of parent
	 *
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

				function mp_product_images_indexes() {
					$( '#mp_product_images_indexes' ).val( '' );
					$( '.mp_images_holder .psource-image-field-preview img' ).each( function() {
						$( '#mp_product_images_indexes' ).val( $( '#mp_product_images_indexes' ).val() + ',' + $( this ).attr( 'data-image-id' ) );
						if ( $( '#mp_product_images_indexes' ).val().charAt( 0 ) == ',' ) {
							$( '#mp_product_images_indexes' ).val( $( '#mp_product_images_indexes' ).val().substring( 1 ) );
						}
					} );
				}

				$( ".mp_images_holder" ).sortable( {
					items: '.psource-image-field-preview',
					receive: function( template, ui ) {
					},
					stop: function( template, ui ) {
						mp_product_images_indexes();
					}
				} );

				var buttonHtml = '<a class="button psource-image-field-add" href="javascript:;"><?php _e( 'Bild wählen', 'psource_metaboxes' ); ?></a>';

				/*
				 * Delete image
				 */
				$( '.psource-fields' ).on( 'click', '.psource-image-field-delete', function( e ) {
					e.preventDefault();

					var $this = $( this ),
						$parent = $this.parent();

					$parent.remove();

					$parent.siblings( ':hidden' ).val( '' );
					mp_product_images_indexes();
				} );

				/*
				 * Show the media library popup (BUTTON)
				 */
				$( '.psource-fields' ).on( 'click', '.psource-image-field-add', function( e ) {
					e.preventDefault();

					var $this = $( this ),
						frame = wp.media( {
							"title": "<?php _e( 'Wähle die Bilder aus, welche Du für dieses Produkt verwenden möchtest.', 'psource_metaboxes' ); ?>",
							"multiple": true,
							"library": { "type": "image" },
							"button": { "text": "<?php _e( 'Bild wählen', 'psource_metaboxes' ); ?>" }
						} );

					/*
					 * Send image data back to the calling field
					 */
					frame.on( 'select', function() {
						var selection = frame.state().get( 'selection' );

						selection.each( function( attachment ) {

							var url = attachment.attributes.sizes.hasOwnProperty( '<?php echo $this->args[ 'preview_size' ]; ?>' ) ? attachment.attributes.sizes['<?php echo $this->args[ 'preview_size' ]; ?>'].url : ( attachment.attributes.sizes.hasOwnProperty( 'thumbnail' ) ? attachment.attributes.sizes.thumbnail.url : attachment.attributes.sizes.full.url ),
								html = '<div class="psource-image-field-preview"><a class="psource-image-field-edit dashicons dashicons-edit" href="#"></a><a class="psource-image-field-delete dashicons dashicons-trash" href="#"></a><img src="' + url + '" alt="" data-image-id="' + attachment.id + '" /></div>';

							$( '.mp_images_holder' ).append( html );

						} );
						mp_product_images_indexes();
					} );

					frame.open();

				} );

				/*
				 * Show the media library popup (EDIT)
				 */
				$( '.psource-fields' ).on( 'click', '.psource-image-field-edit', function( e ) {
					e.preventDefault();

					var $this = $( this ),
						$input = ( $this.hasClass( 'psource-image-field-edit' ) ) ? $this.parent().siblings( ':hidden' ) : $this.siblings( ':hidden' ),
						frame = wp.media( {
							"title": "<?php _e( 'Wähle das passende Bild für Dein Produkt.', 'psource_metaboxes' ); ?>",
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
							var url = attachment.attributes.sizes.hasOwnProperty( '<?php echo $this->args[ 'preview_size' ]; ?>' ) ? attachment.attributes.sizes['<?php echo $this->args[ 'preview_size' ]; ?>'].url : ( attachment.attributes.sizes.hasOwnProperty( 'thumbnail' ) ? attachment.attributes.sizes.thumbnail.url : attachment.attributes.sizes.full.url ),
								html = '<div class="psource-image-field-preview"><a class="psource-image-field-edit dashicons dashicons-edit" href="#"></a><a class="psource-image-field-delete dashicons dashicons-trash" href="#"></a><img src="' + url + '" alt="" data-image-id="' + attachment.id + '" /></div>';

							$this.parent().replaceWith( html );

						} );
						mp_product_images_indexes();
					} );

					/*
					 * Set the selected image (EDIT
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

				mp_product_images_indexes();
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
		$this->before_field();
		?>
		<div class="mp_images_holder">
			<?php
			$values = $this->get_value( $post_id, 'mp_product_images', true );

			if ( empty( $values ) ) {
				$post_thumbnail = get_post_thumbnail_id( $post_id );
				if ( is_numeric( $post_thumbnail ) ) {
					$values = $post_thumbnail;
					update_post_meta($post_id, 'mp_product_images', $post_thumbnail);
				}
			}

			if ( $values ) {
				$values = explode( ',', $values );
				foreach ( $values as $value ) {
					$img_url = wp_get_attachment_image_src( $value, $this->args[ 'preview_size' ] );
					?>
					<div class="psource-image-field-preview"><a class="psource-image-field-edit dashicons dashicons-edit" href="#"></a><a class="psource-image-field-delete dashicons dashicons-trash" href="#"></a><img src="<?php echo $img_url[ 0 ] ?? 'default value'; ?>" alt="" data-image-id="<?php echo esc_attr( $value ); ?>" /></div>
					<?php
				}
			}
			?>

		</div>
		<div class="mp_images_select">
			<a class="button psource-image-field-add" href="javascript:;"><?php _e( 'Bilder auswählen', 'psource_metaboxes' ); ?></a>
			<input type="hidden" name="mp_product_images_indexes" id="mp_product_images_indexes" value="" />
		</div>
		<?php
		$this->after_field();
		?>
		<?php
	}

}
<?php
class pb_backupbuddy_medialibrary {
	private $_save_point = '';
	private $_default_options_point = '';
	private $_instance = 0;
	
	function __construct( $save_point, $default_options_point ) {
		$this->_save_point = $save_point;
		$this->_default_options_point = $default_options_point;
		$this->_instance = pb_backupbuddy::random_string();
	}
	
	function display() {
		pb_backupbuddy::load_script( 'jquery-ui-draggable' );
		pb_backupbuddy::load_script( 'jquery-ui-sortable' );
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				
				
				jQuery( '.pb_medialibrary_slot_<?php echo $this->_instance; ?>' ).sortable({
					opacity: 0.6,
					cursor: "move",
					revert: true,
					update: function(e,ui) {
						// Only save changes on drop IN, not pull out phase of the update.  Prevents double firing and pulling out of things too early.
						if (this === ui.item.parent()[0]) {
							var this_placed_item = jQuery(ui.item);
							jQuery(ui.item).removeClass('pb_medialibrary_draggable');
							jQuery(ui.item).addClass('pb_medialibrary_slotitem');
							slot_items = new Array();
							jQuery(this).children('.pb_medialibrary_slotitem').each(function(j) {
								slot_items.push( jQuery(this).attr('rel') );
							});
							
							jQuery('#pb_medialibrary_saving').show();
							
							//alert( slot_items );
							
							jQuery.ajax({
								type: 'POST',
								url: '<?php echo pb_backupbuddy::ajax_url( 'media_library' ); ?>&actionb=save_images_list',
								data: 'items=' + slot_items + '&save_point=<?php echo str_replace( '\'', '\\\'', $this->_save_point ); ?>&default_options_point=<?php echo str_replace( '\'', '\\\'', $this->_default_options_point ); ?>',
								success: function(msg){
									jQuery('#pb_saving').hide();
									//alert( msg );
									//this_placed_item.children('.loop_item_buttons').css({'display':'block'}); // Show settings icon on newly placed slot item.
									/*
									if ( msg.unique_id != 0 ) {
										this_placed_item.attr('id', 'pbloop-' + msg.unique_id);
									}
									*/
									
								}
								// , 'dataType': 'json'
							});
							
						}
					},
					start: function(e,ui) {
						/* New jQuery fix (new slot items do not cause over method to trigger. */
						//jQuery('.loop_slot').css({'background':'transparent'}); /* New jQuery fix. Changing existing slot item keeps its slot bg colored. */
						//jQuery(this).css({'background':'#EAF2FA'});
						jQuery( '.pb_medialibrary_item' ).draggable( 'option', 'revert', false );
					},
					stop: function(e,ui) {
						jQuery(this).css({'background':'transparent'});
						jQuery( '.pb_medialibrary_item' ).draggable( 'option', 'revert', true );
					},
					
					over: function(e,ui) {
						jQuery('.pb_medialibrary_item').css({'background':'transparent'}); /* New jQuery fix. Changing existing slot item keeps its slot bg colored. */
						
						jQuery(this).css({'background':'#EAF2FA'});
						jQuery( '.pb_medialibrary_item' ).draggable( 'option', 'revert', false );
					},
					out: function(e,ui) {
						jQuery(this).css({'background':'transparent'});
						/* jQuery( '.loop_item' ).draggable( 'option', 'revert', true ); New jQuery fix. Prevents revert from double flying. */
					}
				});
				//jQuery( '.pb_medialib_1' ).disableSelection();
				
				
				
				jQuery('.pb_medialibrary_draggable').draggable({
					revert: true,
					opacity: '.9',
					zIndex: 15,
					helper: 'clone',
					appendTo: 'body',
					connectToSortable: '.pb_medialibrary_slot_<?php echo $this->_instance; ?>'
				});
				
				
				
			});
		</script>
		<style type="text/css">
			.pb_medialibrary div {
				display: inline-block;
				height: 150px;
				position: relative;
				cursor: move;
			}
			.pb_medialibrary div img {
				//margin: 3px;
			}
			.pb_medialibrary {
				max-height: 480px;
				overflow: scroll;
			}
			
			
			.pb_medialibrary_item {
				margin: 5px;
				text-align: center;
				line-height: 1.1em;
			}
			
			.pb_medialibrary_imgbox {
				display: inline-block;
				position: relative;
				//margin: 3px;
				cursor: move;
			}
			.pb_medialibrary_settingsbox {
				position: absolute;
				right: 0;
				z-index: 30;
				display: none;
				cursor: pointer;
				background: white;
			}
			.pb_medialibrary_settingsbox_hot {
				color: red;
			}
		</style>
		
		
		
		
		<?php
		pb_backupbuddy::$ui->start_metabox( 'Images in Group <span id="pb_medialibrary_saving" style="display: none;">Saving</span>', true, 'width: 48%; min-width: 400px; float: left;' );
		
		echo '<div class="pb_medialibrary_slot_' . $this->_instance . ' pb_medialibrary">';
		
		$group_images = &pb_backupbuddy::get_group( $this->_save_point );
		foreach( $group_images as $image_index => $image ) {
			$image_dat = wp_get_attachment_image_src( $image['id'], 'thumbnail' );
			echo '<div class="pb_medialibrary_item pb_medialibrary_slotitem" rel="' . $image['id'] . '"><img src="' . $image_dat[0] . '"></div>';
		}
		
		echo '</div>';
		
		pb_backupbuddy::$ui->end_metabox();
		
		
		
		
		pb_backupbuddy::$ui->start_metabox( 'WordPress Media Library', true, 'width: 48%; min-width: 400px; float: right;' );
		?>
		<div class="pb_medialibrary">
		<?php
		global $wpdb;
		$result = mysql_query( "SELECT ID,post_title FROM {$wpdb->prefix}posts WHERE post_type='attachment'" );
		while ( $rs = mysql_fetch_assoc( $result ) ) {
			//echo '<pre>' . print_r( $rs, true ) . '</pre>';
			$image_dat = wp_get_attachment_image_src( $rs['ID'], 'thumbnail' );
			//echo '<pre>' . print_r( \wp_get_attachment_metadata( $rs['ID'] ) , true ) . '</pre>';
			//echo '<pre>' . print_r( $image_dat, true ) . '</pre>';
			echo '<div class="pb_medialibrary_item pb_medialibrary_draggable" rel="' . $rs['ID'] . '"><span class="pb_medialibrary_settingsbox">Settings</span><img class="pb_medialibrary_lazy" data-original="' . $image_dat[0] . '" src="" title="' . $rs['post_title'] . '"></div>';
		}
		unset( $result );
		?>
		</div>
		<?php
		pb_backupbuddy::$ui->end_metabox();
	}
	
	public function ajax( $actionb ) {
		if ( $actionb == 'save_images_list' ) { // User modified the list of images in a group.
			//echo '<pre>' . print_r( pb_backupbuddy::_POST(), true ) . '</pre>';
			$images = explode( ',', pb_backupbuddy::_POST( 'items' ) );
			echo '<pre>' . print_r( $images, true ) . '</pre>';
			
			
			// Prepare savepoint.
			if ( $this->_save_point != '' ) {
				$savepoint_root = $this->_save_point; // . '#';
			} else {
				$savepoint_root = '';
			}
			
			$image_root = &pb_backupbuddy::get_group( $savepoint_root );
			$image_root = array();
			
			
			foreach ( $images as $image_id ) { // Loop through each item in list.
				//echo 'id: ' . $image_id . '<br>';
				$image_defaults = pb_backupbuddy::settings( pb_backupbuddy::_POST( 'default_options_point' ) );
				$image_defaults['id'] = $image_id;
				$image_root[] = $image_defaults;
				/*
				//echo '<pre>' . print_r( $image_defaults, true ) . '</pre>';
				
				// From old save_settings():
				$savepoint_subsection = &pb_backupbuddy::$options;
				$savepoint_levels = explode( '#', $savepoint_root );
				foreach ( $savepoint_levels as $savepoint_level ) {
					$savepoint_subsection = &$savepoint_subsection{$savepoint_level};
				}
				// Apply settings.
				$savepoint_subsection[] = $image_defaults;
				*/
			}
			//echo '<pre>' . print_r( $image_root, true ) . '</pre>';
			pb_backupbuddy::save();
		}
		die();
	}
}
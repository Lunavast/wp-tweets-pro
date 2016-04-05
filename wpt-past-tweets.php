<?php
/**
 * get list of active post types, generate control to select which post type to view tweets for.
 */
function wpt_get_past_tweets() {
	$settings = get_option('wpt_post_types');
	$per_page = apply_filters( 'wpt_past_tweets_per_page', 50 );
	$post_types = array_keys($settings);
	$root = admin_url( "admin.php?page=wp-to-twitter-tweets" );
	if ( isset( $_GET['wpt_clear_saved'] ) && $_GET['wpt_clear_saved'] == $post_type ) {
		if (!wp_verify_nonce( $_GET['_wpnonce'], 'clear_saved_tweets' ) ) wp_die();
		$clear = get_posts( array( 'post_type'=>$post_type ) );
		foreach ( $clear as $p ) {	
			$id = $p->ID;
			//delete_post_meta( $id, '_wpt_failed' );
			delete_post_meta( $id, '_jd_wp_twitter' );
		}
		echo "<div class='notice'><p>".__('Saved Tweets Deleted','wp-tweets-pro')."</p></div>";
	}	
	$types = "<ul class='post-types'>";
	foreach ( $post_types as $pt ) {
		if ( $settings[$pt]['post-published-update'] == 1 || $settings[$pt]['post-edited-update'] == 1 ) {
			$types .= "<li><a href='$root&ptype=$pt'>$pt</a></li>";
		}
	}
	$types .= "</ul>";
	
	$post_type = ( isset( $_GET['ptype'] ) ) ? $_GET['ptype'] : 'post';
	$paged     = ( isset( $_GET['paged'] ) ) ? (int) $_GET['paged'] - 1 : false;
	$offset    = ( $paged ) ? $per_page*$paged : 0;
	$posts     = new WP_Query( array( 'posts_per_page'=>$per_page, 'offset'=>$offset, 'post_type'=>$post_type, 'meta_key'=>'_jd_wp_twitter', 'meta_query'=>array( array( 'key'=>'_jd_wp_twitter', 'compare'=>'EXISTS' ) ) ) );
	$output    = '';
	$class     = '';
	
	while ( $posts->have_posts() ) {
		$posts->the_post();
		$post = get_post( get_the_ID() );
		$user = get_userdata( $post->post_author );	
		$key = $post->post_title.'|'.$user->display_name.'|'.date('d M, Y; g:ia',strtotime( $post->post_date ) );
		$value = get_post_meta( get_the_ID(), '_jd_wp_twitter', true );
		$row = explode('|',$key);
		$list = '<ul>';
		if ( is_array( $value ) ) {
			foreach ( $value as $v ) {
				if ( is_array( $v ) ) {
					foreach ( $v as $t ) {
						$t2 = urlencode($t);
						$list .= "<li><a href='http://twitter.com/intent/tweet?text=$t2'>$t</a></li>";
					}
				} else {
					$v2 = urlencode($v);				
					$list .= "<li><a href='http://twitter.com/intent/tweet?text=$v2'>$v</a></li>";
				}
			}
		} else {
			$v2 = urlencode($value);		
			$list .= "<li><a href='http://twitter.com/intent/tweet?text=$v2'>$value</a></li>";
		}
		$list .= "</ul>";
		$output .= "<tr class='$class'><th scope='row'><a href='#'>$row[0]</a>$list</th><td>$row[1]</td><td>$row[2]</td></tr>";	
		$class = ( $class == 'alternate' ) ? '' : 'alternate';		
	}
	?>
	<div class="wrap" id="wp-to-twitter" >
	<?php $elem = ( version_compare( '4.3', get_option( 'version' ), '>=' ) ) ? 'h1' : 'h2'; ?>
	<<?php echo $elem; ?>><?php _e('Past Tweets saved by WP Tweets PRO', 'wp-tweets-pro'); ?></<?php echo $elem; ?>>
	<div class="postbox-container jcd-wide">
	<div class="metabox-holder">
	<div class="ui-sortable meta-box-sortables">
	<div class="postbox">
		
		<h3><?php _e('Posts with Sent Tweets','wp-tweets-pro'); ?></h3>
		<div class="inside">
		<?php
			if ( isset( $_GET['ptype'] ) ) { 
				$url = wp_nonce_url( admin_url( "admin.php?page=wp-to-twitter-tweets&ptype=$post_type&wpt_clear_saved=$post_type" ), 'clear_saved_tweets' ); ?>
				<p>
					<a href="<?php echo $url; ?>">
					<?php _e('Clear saved Tweets for this post type','wp-tweets-pro'); ?>
					</a>
				</p>
			<?php } ?>
	<?php echo $types; ?>
	<?php
		$items = $posts->found_posts;		
		$num_pages = ceil($items / $per_page);
		$current = ( isset($_GET['paged']) )? $_GET['paged'] : 1;
		if ( $num_pages > 1 ) {
			$page_links = paginate_links( array(
				'base' => add_query_arg( 'paged', '%#%' ),
				'format' => '',
				'prev_text' => __('&laquo; Previous Page','wp-tweets-pro'),
				'next_text' => __('Next Page &raquo;','wp-tweets-pro'),
				'total' => $num_pages,
				'current' => $current
			));
			printf( "<div class='tablenav'><div class='tablenav-pages'>%s</div></div>", $page_links );
		}
	?>
		<table class="widefat fixed" id="wpt">
		<thead>
			<tr>
				<th scope="col" style="width:60%"><?php _e('Post Title', 'wp-tweets-pro'); ?></th>
				<th scope="col"><?php _e('Author','wp-tweets-pro'); ?></th>
				<th scope="col"><?php _e('Date Posted', 'wp-tweets-pro'); ?></th>
			</tr>
		</thead>
		<tbody>
	<?php
	if ( $output ) { echo $output; }
	?>
		</tbody>
		</table>
	</div>
	</div>
	</div>
	</div>
	</div>
	<?php if ( function_exists( 'wpt_sidebar' ) ) { wpt_sidebar(); } else { _e('Please Activate WP to Twitter!','wp-tweets-pro'); } ?>	

	</div><?php
}
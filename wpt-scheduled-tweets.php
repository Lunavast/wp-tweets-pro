<?php
/**
 * Get and display list of scheduled Tweets
 */
function wpt_get_scheduled_tweets() {
	$schedule = wpt_schedule_custom_tweet( $_POST );
	$deletions = ( isset( $_POST['delete-tweets'] ) && isset( $_POST['delete-list'] ) ) ? $_POST['delete-list'] : array();
	$cron = _get_cron_array();
	$schedules = wp_get_schedules();
	$date_format = _x( 'M j, Y @ G:i', 'Publish box date format', 'wp-tweets-pro' );
	$clear_queue = wp_nonce_url( admin_url("admin.php?page=wp-to-twitter-schedule&amp;wpt=clear") );
	$cur_sched = '';
	if ( isset( $schedule['message'] ) ) { echo $schedule['message']; }

?>
<div class="wrap" id="wp-to-twitter" >

	<?php $elem = ( version_compare( '4.3', get_option( 'version' ), '>=' ) ) ? 'h1' : 'h2'; ?>
	<<?php echo $elem; ?>><?php _e('Scheduled Tweets from WP Tweets PRO', 'wp-tweets-pro'); ?></<?php echo $elem; ?>>
	<div class="postbox-container jcd-wide">
	<div class="metabox-holder">
	<div class="ui-sortable meta-box-sortables">
	<div class="postbox">
		
		<h3><?php _e('Your Scheduled Tweets','wp-tweets-pro'); ?></h3>
		<div class="inside">
	<form method="post" action="<?php echo admin_url( 'admin.php?page=wp-to-twitter-schedule&action=delete' ); ?>">
	<table class="widefat fixed">
		<thead>
			<tr>
				<th scope="col"><?php _e('Scheduled', 'wp-tweets-pro'); ?></th>
				<th scope="col" style="width:60%;"><?php _e('Tweet', 'wp-tweets-pro'); ?></th>
				<th scope="col"><?php _e('Account','wp-tweets-pro'); ?></th>
				<th scope="col"><?php _e('Delete', 'wp-tweets-pro'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php $offset = ( 60*60*get_option( 'gmt_offset' ) );
			$class = '';
			foreach ( $cron as $timestamp => $cronhooks ) { 
				foreach ( (array) $cronhooks as $hook => $events ) { 
					$i = 0; foreach ( (array) $events as $event ) { 
						if ( $hook == 'wpt_schedule_tweet_action' || $hook == 'wpt_recurring_tweets' ) {
							$i++; 
							if ( $hook == 'wpt_recurring_tweets' ) {
								$class = 'is_recurring';
								$cur_sched = ', '.$event['schedule'];
							}
							if ( count( $event[ 'args' ] ) ) {
								$auth = $event['args']['id'];
								$sentence = $event['args']['sentence'];	
								$rt = $event['args']['rt'];
								$post_ID = $event['args']['post_id'];
							}
							$id = md5( $timestamp . $auth . $rt . $post_ID . $sentence );
							
							if ( ( isset( $_GET['wpt'] ) && $_GET['wpt'] == 'clear' ) && ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'] ) ) ) {
								wp_unschedule_event( $timestamp, $hook, array( 'id'=>$auth,'sentence'=>$sentence, 'rt'=>$rt,'post_id'=>$post_ID ) );
								echo "<div id='message' class='updated'><p>".sprintf(__('Tweet for %1$s has been deleted.','wp-tweets-pro'),date( $date_format, ($timestamp+$offset) ))."</p></div>";
							} else if ( in_array( $id, $deletions ) ) {
								wp_unschedule_event( $timestamp, $hook, array( 'id'=>$auth,'sentence'=>$sentence,'rt'=>$rt,'post_id'=>$post_ID ) );
								echo "<div id='message' class='updated'><p>".__('Scheduled Tweet has been deleted.','wp-tweets-pro')."</p></div>";							
							} else {
								$time_diff = human_time_diff( $timestamp+$offset, time()+$offset );							
								$image = '';
								if ( get_option( 'wpt_media' ) == 1 ) {
									if ( get_post_meta( $post_ID, '_wpt_image', true ) != 1 ) {
										$tweet_this_image = wpt_filter_scheduled_media( true, $post_ID, $rt );
										if ( $tweet_this_image ) {
											$img = wpt_post_attachment( $post_ID );
											if ( $img ) {
												$img_url = wp_get_attachment_image_src( $img, apply_filters( 'wpt_upload_image_size', 'medium' ) );
												$image = "<a href='$img_url[0]' class='wpt_image'>".__('Includes Image','wp-tweets-pro')."</a>";
											}
										}
									}
								}  
								if ( !$auth || $auth == 'main' ) { 
									$account = '@'.get_option( 'wtt_twitter_username' ); 
									$link = 'https://twitter.com/' . get_option( 'wtt_twitter_username' ); 
								} else { 
									$account = '@'.get_user_meta( $auth, 'wtt_twitter_username',true ); 
									$link = 'https://twitter.com/' . get_user_meta( $auth, 'wtt_twitter_username',true );
								}
							?>
							<tr class='<?php echo $class; ?>'>
								<th scope="row"><?php echo date_i18n( $date_format, ( $timestamp + $offset ) ); ?><br /><small>(~<?php echo $time_diff.$cur_sched; ?>)</small></th>
								<td id='sentence-<?php echo $id; ?>'><strong><?php echo "$sentence $image"; ?></td>
								<td><a href='<?php echo $link; ?>'><?php echo $account; ?></a></td>
								<td><input type='checkbox' id='checkbox-<?php echo $id; ?>' value='<?php echo $id; ?>' name='delete-list[]' aria-describedby='sentence-<?php echo $id; ?>' /> <label for='checkbox-<?php echo $id; ?>'><?php _e( 'Delete', 'wp-tweets-pro' ); ?></label></td>
							</tr><?php 
							} 
						}
					}
				}
			} ?>
		</tbody>
	</table>
	<p><input type='submit' class='button-primary' name='delete-tweets' value='<?php _e( 'Delete checked Tweets', 'wp-tweets-pro' ); ?>' /></p>
	</form>
	<p><a href="<?php echo $clear_queue; ?>"><?php _e('Clear Tweets Queue','wp-tweets-pro'); ?></a></p>
	</div>
	</div>
	</div>
	<div class="ui-sortable meta-box-sortables">
	<div class="postbox">
		
		<h3><?php _e('Schedule a Tweet','wp-tweets-pro'); ?></h3>
		<div class="inside schedule" id="wp2t">	
		<?php $admin_url = admin_url('admin.php?page=wp-to-twitter-schedule'); ?>
		<form method="post" action="<?php echo $admin_url; ?>">
		<div><input type="hidden" name="submit-type" value="schedule-tweet" /><input type="hidden" name='author' id='author' value='<?php echo get_current_user_id(); ?>' /></div>
		<?php $nonce = wp_nonce_field('wp-to-twitter-nonce', '_wpnonce', true, false); echo "<div>$nonce</div>"; ?>	
			<p style='position: relative'>
				<label for='jtw'><?php _e('Tweet Text','wp-tweets-pro'); ?></label> <input type="checkbox" value='on' id='filter' name='filter' checked='checked' /> <label for='filter'><?php _e('Run WP to Twitter filters on this Tweet','wp-tweets-pro'); ?></label><br />
				<textarea id='jtw' name='tweet' rows='3' cols='70'><?php echo ( isset($schedule['tweet']) ) ? stripslashes($schedule['tweet'] ) : ''; ?></textarea>
			</p>
			<div class="datetime">
			<div class='date'>
				<label for='wpt_date'><?php _e('Date','wp-tweets-pro'); ?></label><br />
				<?php $date = date_i18n('Y-m-d',( current_time( 'timestamp' )+300 ) ); ?>
				<input type='text' name='date' id='wpt_date' size="20" value='' data-value='<?php echo $date; ?>' />
			</div>			
			<div class='time'>
				<label for='wpt_time'><?php _e('Time','wp-tweets-pro'); ?></label><br />
				<input type='text' name='time' id='wpt_time' size="20" value='<?php echo date_i18n('h:i a',(current_time( 'timestamp' )+300) ); ?>' />
			</div>
			<div class='recurrence'>
				<label for='wpt_recurrence'><?php _e( 'Frequency', 'wp-tweets-pro' ); ?></label>
				<select name='wpt_recurrence' id='wpt_recurrence'>
					<option value=''><?php _e( 'Once', 'wp-tweets-pro' ); ?></option>
					<?php
						$schedules = wp_get_schedules();
						$frequency = isset( $_GET['schedule'] ) ? '' : '';
						foreach ( $schedules as $key => $schedule ) {
							if ( $key != 'four-hours' && $key != 'eight-hours' && $key != 'sixteen-hours' ) {
								echo "<option value='$key'" . selected( $frequency, $key ) . ">$schedule[display]</option>";
							}
						}
					?>					
				</select>
			</div>
			</div>
			<?php $last = wp_get_recent_posts( array( 'numberposts'=>1, 'post_type'=>'post', 'post_status'=>'publish' ) ); $last_id = $last['0']['ID']; ?>
			<p>
				<label for='post'><?php _e('Associate with Post ID','wp-tweets-pro'); ?></label> <input type="text" name="post" id="post" value="<?php echo ( isset( $schedule['post'] ) ) ? $schedule['post'] : $last_id; ?>" />
			</p>
			<?php if ( get_option( 'jd_individual_twitter_users' ) == '1' ) { ?>
			<p>
			<?php print('
						<label for="alt_author">'.__('Post to this author', 'wp-tweets-pro').'</label>
						<select name="alt_author" id="alt_author">
							<option value="main">'.__('Main site account','wp-tweets-pro').'</option>
							<option value="false">'.__('Current User\'s account','wp-tweets-pro').'</option>');
							$user_query = get_users( array( 'role' => 'subscriber' ) );
							// This gets the array of ids of the subscribers
							$subscribers = wp_list_pluck( $user_query, 'ID' );
							// Now use the exclude parameter to exclude the subscribers
							$users = get_users( array( 'exclude' => $subscribers ) );
							if ( count( $users ) < 1000 ) {
								foreach ( $users as $this_user ) {
									if ( get_user_meta( $this_user->ID, 'wtt_twitter_username',true ) != '' ) {
										print('<option value="'.$this_user->ID.'">'.$this_user->display_name.'</option>');
									}
								}
							}
					print('
						</select>');
			?>
		</p>
		<?php } ?>
		<p><input type="submit" name="submit" value="<?php _e("Schedule a Tweet", 'wp-tweets-pro'); ?>" class="button-primary" /></p>
		</form>	
		<h3><?php _e('Recently published posts/IDs:','wp-tweets-pro'); ?></h3>
		<ul class='columns' id="recent">
		<?php 
			$recent = wp_get_recent_posts( array( 'numberposts'=>15,'post_status'=>'publish' ) ); 
			foreach( $recent as $post ) {
				echo "<li><code>$post[ID]</code> - <strong>" . strip_tags( $post['post_title'] ) . "</strong></li>";
			} 
		?>
		</ul>
	</div>
	</div>
	</div>
</div>
</div>
<?php if ( function_exists( 'wpt_sidebar' ) ) { wpt_sidebar(); } else { _e( 'Please Activate WP to Twitter!','wp-tweets-pro' ); } ?>	
</div>
<?php
}

/**
 * Schedule a custom Tweet
 */
function wpt_schedule_custom_tweet( $post ) {
	$offset = (60*60*get_option('gmt_offset'));
	if ( isset($post['submit-type']) && $post['submit-type'] == 'schedule-tweet' ) { 
		if ( isset( $post['alt_author'] ) ) {
			$auth = ( isset($post['author']) && $post['author'] != '' ) ? (int) $post['author']:false;
			$auth = ( isset($post['alt_author']) && $post['alt_author'] == 'false' ) ? $auth : (int) $post['alt_author'];
			$auth = ( isset($post['alt_author']) && $post['alt_author'] == 'main' ) ? false : $auth;
			if ( $auth && get_user_meta( $auth, 'wtt_twitter_username',true ) == '' ) {
				$auth = false;
			}
		} else {
			$auth = false;
		}
		$encoding = get_option('blog_charset');
		if ( $encoding == '' ) { $encoding = 'UTF-8'; }
		$sentence      = ( isset($post['tweet'] ) ) ? html_entity_decode( stripcslashes( $post['tweet'] ), ENT_COMPAT, $encoding  ) : '';
		$orig_sentence = $sentence;
		$post_id       = ( isset($post['post'] ) ) ? (int) $post['post'] : '';
		if ( isset( $post['filter'] ) && $post['filter'] == 'on' ) {
			$post_info = wpt_post_info( $post_id );
			$sentence  = jd_truncate_tweet( $sentence, $post_info, $post_id, false, false );
		}
		$time = ( isset( $post['time'] ) && isset( $post['date'] ) ) ? ( strtotime( $post['date'] . ' ' . $post['time'] ) ):'' ;
		$time = ( $time > current_time( 'timestamp' ) ) ? $time : false;
		$time = ( $time ) ? $time - $offset : $time; 
		if ( !$sentence || !$post ) {
			return array( 
				'message'=>"<div class='error'><p>".__('You must include a custom tweet text and a post ID to associate the tweet with.','wp-tweets-pro')."</p></div>", 
				'tweet'=>$sentence, 
				'post'=>$post_id 
			); 
		} else if ( !$time ) {
			return array( 
				'message'=>"<div class='error'><p>".__('The time provided was either invalid or in the past.','wp-tweets-pro')."</p></div>", 
				'tweet'=>$sentence, 
				'post'=>$post_id 
			); ; 
		} else {
			if ( !isset( $_POST['wpt_recurrence'] ) || $_POST['wpt_recurrence'] == '' ) {
				wp_schedule_single_event(
					$time, 
					'wpt_schedule_tweet_action', 
					array( 'id'=>$auth, 'sentence'=>$sentence, 'rt'=>0, 'post_id'=>$post_id ) 
				);
			} else {
				$recurrence = sanitize_text_field( $_POST['wpt_recurrence'] );
				wp_schedule_event( $time, $recurrence, 'wpt_recurring_tweets', array( 'id'=>$auth, 'sentence'=>$orig_sentence, 'rt'=>0, 'post_id'=>$post_id ) ); 
			}
			return array( 'message'=>"<div class='updated'><p>".__('Your custom Tweet has been scheduled.','wp-tweets-pro')."</p></div>", 'tweet'=>$sentence, 'post'=>$post_id ); ; 					
		}
	}
}
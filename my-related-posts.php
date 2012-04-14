<?php
/*
Plugin Name: my-related-posts
Plugin URI: http://www.isnowfy.com/wordpress-my-related-posts-plugin/
Description: related posts with thumbnail
Version: 1.1
Author: isnowfy
Author URI: http://www.isnowfy.com
*/
function my_rp_getRelatedPosts(){
	global $wpdb, $post;
	$my_rp = get_option("my_rp");
	$id = $post->ID;
	$limit = 5;
	if($my_rp["my_rp_limit"])
		$limit = $my_rp["my_rp_limit"];
	$title = "you may also like:";
	if($my_rp["my_rp_title"])
		$title = $my_rp["my_rp_title"];
	$sql = "SELECT p.ID, p.post_title, count(t_r.object_id) AS cnt FROM $wpdb->term_taxonomy AS t_t, $wpdb->term_relationships AS t_r, $wpdb->posts AS p WHERE t_t.term_taxonomy_id = t_r.term_taxonomy_id AND t_r.object_id  = p.ID AND t_t.term_taxonomy_id IN  (SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id = $id) AND p.ID != $id AND p.post_status = 'publish' AND p.post_type = 'post' GROUP BY t_r.object_id ORDER BY cnt DESC, p.post_date DESC LIMIT $limit";
	$sql_random = "SELECT ID, post_title FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' AND ID != $post->ID ORDER BY RAND() LIMIT $limit";
	$posts = $wpdb->get_results($sql);
	if(!$posts)
		$posts = $wpdb->get_results($sql_random);
	$relatedPosts = '<strong>'.$title.'</strong>';		
	if($my_rp["my_rp_thumbnail"]=='yes'){
		$relatedPosts .= "<div class=\"my-related-posts-box\"><ul>\n";
		foreach ($posts as $p){
			$permaLink = get_permalink($p->ID);
			$relatedPosts .= "<li><a href=\"{$permaLink}\" class=\"my-related-posts\">{$p->post_title}</a></li>\n";
		}
		$relatedPosts .= "</div>\n";
		$relatedPosts .= "<div class=\"my-related-posts-clearboth\"></div>";
	}else{
		$relatedPosts .= "<div class=\"my-related-posts-box\" style=\"width:100%;height:100%;clear:both;text-align:center;overflow:hidden;\">\n";
		$size=100;
		if($my_rp["my_rp_thumbnail_size"])
			$size=$my_rp["my_rp_thumbnail_size"];
		$h=$size+52;
		$imgsize=$size-6;
		foreach ($posts as $p){
			$permaLink = get_permalink($p->ID);
			$thumbImage = my_rp_getThumbImage($p->ID);
			$relatedPosts .= "<a href=\"{$permaLink}\" class=\"my-related-posts\" style=\"width:".$size."px;height:".$h."px;float:left;text-align:center;border:1px solid #f5f5f5;border-bottom-style:none;border-top-style:none;padding:0px;margin:1px;text-decoration:none;\" onmouseover=\"this.style.border='1px solid #CCC';this.style.background='#E2E2E2';this.style.borderBottom='none';this.style.borderTop='none'\" onmouseout=\"this.style.background='';this.style.border='1px solid #f5f5f5';this.style.borderBottom='none';this.style.borderTop='none'\">\n";
			$relatedPosts .= "<span class=\"my-related-posts-panel\" style=\"padding:3px 0;\"><img class=\"my-related-posts-img\" src=\"$thumbImage\" style=\"width:".$imgsize."px;height:".$imgsize."px;border:1px solid #f0f0f0;padding:1px;margin:1px;\" /></span>\n";
			$relatedPosts .= "<span class=\"my-related-posts-text\"><span class=\"my-related-posts-title\" style=\"font-size:11px;font-family:sans-serif;font-weight:700;line-height:140%;text-align:left;\">{$p->post_title}</span></span>\n";
			$relatedPosts .= "</a>\n";
		}
		$relatedPosts .= "</div>\n";
		$relatedPosts .= "<div class=\"my-related-posts-clearboth\" style=\"clear:both;height:1px;overflow:hidden;\"></div>";
	}
	return $relatedPosts;
}
function my_rp_getThumbImage($postid){
	$images = &get_children( 'post_type=attachment&post_mime_type=image&post_parent=' . $postid );
	$imageUrl = '';
	if ($images){
		$image = array_pop($images);
		$imageSrc = wp_get_attachment_image_src($image->ID);
		$imageUrl = $imageSrc[0];
	}else{
		$imageUrl = 'http://farm7.static.flickr.com/6182/6044467537_8995f813cc_z.jpg';
		$my_rp = get_option("my_rp");
		if($my_rp["my_rp_img"])
			$imageUrl = $my_rp["my_rp_img"];
	}
	return 	$imageUrl;	
}
function my_rp_posts(){
	$output = my_rp_getRelatedPosts() ;
	echo $output;
}
function my_rp_posts_auto($content){
	$my_rp = get_option("my_rp");
	if ((is_single() && $my_rp["my_rp_auto"])||(is_feed() && $my_rp["my_rp_rss"])){
		$output = my_rp_getRelatedPosts();
		$content = $content . $output;
	}
	return $content;
}
add_filter('the_content', 'my_rp_posts_auto',99);
add_action('admin_menu', 'my_rp_plugin_menu');
function my_rp_plugin_menu(){
	add_options_page('My Related Posts', 'My Related Posts', 'manage_options', basename(__FILE__), 'my_rp_plugin_options');
}
function my_rp_plugin_options(){
	if($_POST["my_rp_submit"]){
		$message = __("My Related Posts Setting Updated",'my_related_posts');
		$my_rp_saved = get_option("my_rp");
		$my_rp = array (
			"my_rp_title" 			=> trim($_POST['my_rp_title_option']),
			"my_rp_limit"			=> trim($_POST['my_rp_limit_option']),
			'my_rp_auto'			=> trim($_POST['my_rp_auto_option']),
			'my_rp_rss'				=> trim($_POST['my_rp_rss_option']),
			'my_rp_img'				=> trim($_POST['my_rp_img_option']),
			'my_rp_thumbnail'		=> trim($_POST['my_rp_thumbnail_option']),
			'my_rp_thumbnail_size'	=> trim($_POST['my_rp_thumbnail_size_option'])
		);	
		if ($my_rp_saved != $my_rp)
			if(!update_option("my_rp",$my_rp))
				$message = "Update Failed";
		echo '<div id="message" class="updated fade"><p>'.$message.'.</p></div>';
	}
	$my_rp = get_option("my_rp");
?>
	<div class="wrap">
	<h2>My Related Posts Settings</h2>
	<p>you can put the code <strong style="color:#FF0000">&lt;?php if (function_exists('my_rp_posts')) my_rp_posts(); ?&gt;</strong> anywhere to display the related posts</p>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo basename(__FILE__); ?>">
	<h3>Basic Settings</h3>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="my_rp_title">My Related Posts Title:</label></th>
			<td>
				<input name="my_rp_title_option" type="text" id="my_rp_title"  value="<?php echo $my_rp["my_rp_title"]; ?>" class="regular-text" />
			</td>
		</tr>	
		<tr valign="top">
			<th scope="row"><label for="my_rp_limit">Maximum Number:</label></th>
            <td>
              <input name="my_rp_limit_option" type="text" id="my_rp_limit" value="<?php echo $my_rp["my_rp_limit"]; ?>" />
            </td>
        </tr>
		<tr valign="top">
			<th scope="row"><label for="my_rp_auto">Auto Insert Related Posts</label></th>
            <td>
              <input name="my_rp_auto_option" type="checkbox" id="my_rp_auto" value="yes"  <?php echo ($my_rp["my_rp_auto"] == 'yes') ? 'checked' : ''; ?> /><span class="description">instead of put code yourself</span>
            </td>
        </tr>
		<tr valign="top">
			<th scope="row"><label for="my_rp_rss">Display Related Posts on Feed(RSS)</label></th>
            <td>
              <input name="my_rp_rss_option" type="checkbox" id="my_rp_rss" value="yes"  <?php echo ($my_rp["my_rp_rss"] == 'yes') ? 'checked' : ''; ?> />
            </td>
        </tr>
	</table>
	<h3>Thumbnail Settings</h3>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="my_rp_thumbnail">Related Posts without Thumbnail</label></th>
			<td>
				<input name="my_rp_thumbnail_option" type="checkbox" id="my_rp_thumbnail"  value="yes"  <?php echo ($my_rp["my_rp_thumbnail"] == 'yes') ? 'checked' : ''; ?> />
			</td>
		</tr>	
		<tr valign="top">
			<th scope="row"><label for="my_rp_img">Default Image Url(when there is no thumbnail to display the default image)</label></th>
            <td>
              <input name="my_rp_img_option" type="text" id="my_rp_img" value="<?php echo $my_rp["my_rp_img"]; ?>" class="regular-text"/>
            </td>
        </tr>
		<tr valign="top">
			<th scope="row"><label for="my_rp_thumbnail_size">Thumbnail Size(px default 100px)</label></th>
            <td>
              <input name="my_rp_thumbnail_size_option" type="text" id="my_rp_thumbnail_size" value="<?php echo $my_rp["my_rp_thumbnail_size"]; ?>" />
            </td>
        </tr>
	</table>
	<p class="submit"><input type="submit" value="Save changes" name="my_rp_submit" class="button-primary" /></p>
	</form>
<?php } ?>
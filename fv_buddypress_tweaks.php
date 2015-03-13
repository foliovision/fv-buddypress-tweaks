<?php
/*
Plugin Name: FV BuddyPress Tweaks
Description: When user is removed from a group, it should cancel the user's subscriptions for forums belonging into it. 
Author: Foliovision
Author URI: http://www.foliovision.com
Version: 0.0.1
*/

add_action('groups_remove_member','fv_buddypress_tweaks_function',1000,2);
add_action('groups_leave_group','fv_buddypress_tweaks_function',1000,2);

function fv_buddypress_tweaks_function($group_id){    
    
    //we need to know group_ID and user_ID after one of actions above
    $aArgs = func_get_args();
    if( intval($group_id) < 1 || !isset($aArgs[1]) ) {
        return;
    }
    
    $iUser_id = $aArgs[1];
    
    // one group can own only one forum in BuddyPress, so it will be integer
    $aForum = bbp_get_group_forum_ids($group_id);
	
	if(!isset($aForum[0]))
		return;
    
    global $wpdb;
	
	// here we want all topics, which forum contains
    $aTopics = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}posts WHERE post_parent='%s'",$aForum[0]));
	
	// here we want all topics, where user has subscriptions
    $sAllTopics = get_user_meta($iUser_id,$wpdb->prefix.'_bbp_subscriptions',true); 
	$sAllForums = get_user_meta($iUser_id,$wpdb->prefix.'_bbp_forum_subscriptions',true); 
	
	// now we want create backup
	update_user_meta($iUser_id, $wpdb->prefix.'_bbp_subscriptions_backup',$sAllTopics);
	update_user_meta($iUser_id, $wpdb->prefix.'_bbp_forum_subscriptions_backup',$sAllForums);
	
	// if user have no subscriptions, there is nothing to change and next part of code isn't necessary
	if($sAllTopics != false){
		
		$aTopicsToUnsubscribe = explode(",",$sAllTopics);
    
		$aNewSubscriptions = array();
		foreach($aTopicsToUnsubscribe as $sTopic){
			if(!in_array($sTopic,$aTopics)){
				$aNewSubscriptions[] = $sTopic;
			}
		}
		
		if(count($aNewSubscriptions) == 0){
			delete_user_meta( $iUser_id, $wpdb->prefix.'_bbp_subscriptions' );
		}else{
			update_user_meta($iUser_id,$wpdb->prefix.'_bbp_subscriptions',implode(",",$aNewSubscriptions));
		}
		
	}
	
	if($sAllForums != false){
		
		$aForumsToUnsubscribe = explode(",",$sAllForums);
		
		$aNewSubscriptionsForums = array();
		foreach($aForumsToUnsubscribe as $sForum){
			if((int)$sForum != $aForum[0]){
				$aNewSubscriptionsForums[] = $sForum;
			}
		}
		
		if(count($aNewSubscriptionsForums) == 0){
			delete_user_meta( $iUser_id, $wpdb->prefix.'_bbp_forum_subscriptions' );
		}else{
			update_user_meta($iUser_id,$wpdb->prefix.'_bbp_forum_subscriptions',implode(",",$aNewSubscriptionsForums));
		}
		
	}
        
}
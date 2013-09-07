<?php
/**
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* Adapted to add "Like" type functions by Pre in 2013.
*
*/
if(isset($_REQUEST['bbblikea'])){
  $action = $_REQUEST['bbblikea'];
}else{
  $action="";
}

/**
* Main bit for when we're NOT called as a lib.
* We either do a "like" or "unlike" action,
* or create the DB.
*/
if($action!=""){
	define('IN_PHPBB', true);
	$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	include($phpbb_root_path . 'common.' . $phpEx);
	include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
	include($phpbb_root_path . 'includes/bbcode.' . $phpEx);
	include_once("rollcall.php");

	// Start session management
	$user->session_begin();
	$auth->acl($user->data);

	// Initial var setup
        $action	= request_var('bbblikea', "");
	$post_id = request_var('bbblikep', 0);
	$post_id = intval($post_id);
	$user_id = intval($user->data['user_id']);

	$start		= request_var('start', 0);
	$view		= request_var('view', '');
        define('LIKES_TABLE',$table_prefix.'bbblikes');


	//Check if likes table exists...
	$result = $db->sql_query("show tables like '".LIKES_TABLE."'");
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if(!$row){
	  print "Creating Table";
	  print ("create table ".LIKES_TABLE." (post_id mediumint unsigned not null, user_id mediumint unsigned not null)");
	  $res = $db->sql_query("create table ".LIKES_TABLE." (post_id mediumint unsigned not null, created datetime, user_id mediumint unsigned not null)");
	  $db->sql_freeresult($res);
	  $res = $db->sql_query("create index i1 on ".LIKES_TABLE."(post_id)");
	  $db->sql_freeresult($res);
	  $res = $db->sql_query("create index i2 on ".LIKES_TABLE."(user_id)");
	  $res = $db->sql_query("create index i3 on ".LIKES_TABLE."(created)");
	  $db->sql_freeresult($res);
	}

	switch($action){
	  case "l":
	    $res = $db->sql_query("select user_id from ".LIKES_TABLE." where post_id = $post_id and user_id = $user_id");
	    $row = $db->sql_fetchrow($res);
	    $db->sql_freeresult($res);
	    if($row){
	      $res = $db->sql_query("delete from ".LIKES_TABLE." where post_id = $post_id and user_id = $user_id");
	      $db->sql_freeresult($res);
              header('Content-Type: text/html; charset=utf-8');
	      print "Unliked";
	    }else{
	      $res = $db->sql_query("insert into ".LIKES_TABLE." (post_id,user_id,created) values($post_id,$user_id,now())");
	      $db->sql_freeresult($res);
              header('Content-Type: text/html; charset=utf-8');
	      print "Liked";
	    }
	    break;

	}
}else{
  define('LIKES_TABLE',$table_prefix.'bbblikes');
}



/**
* Get the 'like' button, and the list
* of likers
*/
function getLikes($post_id){
    global $db,$hasScripts;
    $res = $db->sql_query("select username from ".LIKES_TABLE.",".USERS_TABLE." where ".USERS_TABLE.".user_id = ".LIKES_TABLE.".user_id and ".LIKES_TABLE.".post_id = $post_id");
    $names = "";
    while($row = $db->sql_fetchrow($res)){
       $names.=$row['username'].", ";
    }
    $db->sql_freeresult($res);
    if($names!=""){
      $names = substr($names,0,-2);
    }

    $ret = "<div class=\"likebody\">";
    if($hasScripts!="yes"){
        $hasScripts="yes";
	$ret.='<script type="text/javascript" src="jquery.js"></script>';
	$ret.='<script type="text/javascript">';
	$ret.='  function likePost(o,p){';
	$ret.='    o.value = "Sending...";';
	$ret.='    $.post("like.php",{bbblikea:"l",bbblikep:p},function(data){';
	$ret.='      o.value = data;';
	$ret.='    },"html");';
	$ret.='  }';
	$ret.='</script>';
    }
    $ret.= "<input type=\"button\" onclick=\"likePost(this,$post_id)\" value=\"Like\"></button>";
    if($names!=""){
      $ret.= "<br/>Liked By: $names";
    }
    $ret.= "</div>";
    return $ret;
}

?>

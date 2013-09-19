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
	// Setup look and feel
	$user->setup('viewtopic', $topic_data['forum_style']);

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
	  case "c":
	    showCharts();
	    break;

	}
}else{
  if (!defined('IN_PHPBB')) {
	print "I like you too. Send an action if you want more than that.";
	exit;
  }
  define('LIKES_TABLE',$table_prefix.'bbblikes');
}



/**
* Get the 'like' button, and the list
* of likers
*/
function getLikes($post_id){
    global $db,$hasScripts,$user,$style;
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
	$ret.='  function likePost(o,p,i1,i2,i3){';
	$ret.='    if(i1!=null){';
	$ret.='	         o.src=i3;';
	$ret.='    }else{';
	$ret.='      o.value = "Sending...";';
	$ret.='    }';
	$ret.='    $.post("like.php",{bbblikea:"l",bbblikep:p},function(data){';
	$ret.='      if(i1!=null){';
	$ret.='	       if(data=="Liked"){';
	$ret.='	         o.src=i1;';
	$ret.='        }else{';
	$ret.='	         o.src=i2;';
	$ret.='        }';
	$ret.='      }else{';
	$ret.='        o.value = data;';
	$ret.='      }';
	$ret.='    },"html");';
	$ret.='  }';
	$ret.='</script>';
    }
    if($user->theme['style_id']=="2"){
      $ret.= "<img style=\"cursor:pointer\" src=\"/styles/BoingSilver/theme/en/icon_post_like.png\" onclick=\"likePost(this,$post_id,'/styles/BoingSilver/theme/en/icon_post_liked.png','/styles/BoingSilver/theme/en/icon_post_unliked.png','/styles/BoingSilver/theme/en/icon_post_likeSending.png')\" value=\"Like\" />";
    }else{
      $ret.= "<input type=\"button\" onclick=\"likePost(this,$post_id)\" value=\"Like\" />";
    }
    if($names!=""){
      $ret.= "<br/>Liked By: $names";
    }
    $ret.= "</div>";
    return $ret;
}



/**
* Get all the likes since a startdate till
* an enddate (which defaults to now)
*/
function getLikesByDates($start,$end=null){
  global $db;
  if($end==null){$end = new \DateTime();}
  $likes = array();
  $q = "select p.post_subject as subject,p.poster_id as poster_id,u.username as user,l.post_id,count(l.user_id) as c from ".LIKES_TABLE." l,".POSTS_TABLE." p, ".USERS_TABLE." u where u.user_id = p.poster_id and l.post_id = p.post_id and l.created>='".$start->format("Y-m-d H:i:s")."' and l.created <= '".$end->format("Y-m-d H:i:s")."' group by l.post_id order by count(l.post_id) desc";
  $result = $db->sql_query($q);
  while($row = $db->sql_fetchrow($result)){
    $likes[] = array(
      'POST_ID' => $row['post_id'],
      'COUNT' => $row['c'],
      'TITLE' => $row['subject'],
      'AUTHOR' => $row['user'],
      'AUTHOR_ID' => $row['poster_id'],
    );
  }
  $db->sql_freeresult($result);
  return $likes;
}



/**
* Function to show a chart of recent liked posts
* It makes a whole phpbb3 page
*/
function showCharts(){
  global $user,$template;
  page_header("Liked Posts Charts");
  $template->set_filenames(array('body' => 'likechart_body.html'));
  $startDate = new \DateTime();
  $endDate = new \DateTime();

  $startDaysAgo = request_var('bbblikesda', 7);
  if($startDaysAgo=="ever"){
   $startDate = new \DateTime("2000-01-01");
  }else{
    $startDate->sub(new \DateInterval("P".intval($startDaysAgo)."D"));
  }
  $endDaysAgo = request_var('bbblikeeda', 0);
  $endDate->sub(new \DateInterval("P".intval($endDaysAgo)."D"));

  $likes = getLikesByDates($startDate);

  $template->assign_vars(array(
    "STARTDATE"=> $user->format_date($startDate->getTimestamp()),
    "STARTUNIX"=> $startDate->getTimestamp(),
    "ENDDATE"=> $user->format_date($endDate->getTimestamp()),
    "ENDUNIX"=> $endDate->getTimestamp(),
  ));
  foreach($likes as $l){
    $template->assign_block_vars('likerow', $l);
  }
  page_footer();
  print "Whatever";exit;
}

?>

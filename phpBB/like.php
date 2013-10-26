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
	define('LIKES_OPTIONS_TABLE',$table_prefix.'bbblikesoptions');
	define('LIKES_POSTS_TABLE',$table_prefix.'bbblikesposts');


	//Check if likes table exists...
	$result = $db->sql_query("show tables like '".LIKES_TABLE."'");
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if(!$row){
	  print "Creating Table";
	  $res = $db->sql_query("create table ".LIKES_TABLE." (post_id mediumint unsigned not null, created datetime, user_id mediumint unsigned not null)");
	  $db->sql_freeresult($res);
	  $res = $db->sql_query("create index i1 on ".LIKES_TABLE."(post_id)");
	  $db->sql_freeresult($res);
	  $res = $db->sql_query("create index i2 on ".LIKES_TABLE."(user_id)");
	  $res = $db->sql_query("create index i3 on ".LIKES_TABLE."(created)");
	  $db->sql_freeresult($res);
	}

	//Check if likes options table exists...
	$result = $db->sql_query("show tables like '".LIKES_OPTIONS_TABLE."'");
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if(!$row){
	  print "Creating Table";
	  $res = $db->sql_query("create table ".LIKES_OPTIONS_TABLE." (user_id mediumint unsigned not null, lastnotify datetime, defaultextract smallint unsigned not null)");
	  $db->sql_freeresult($res);
	  $res = $db->sql_query("create index io1 on ".LIKES_OPTIONS_TABLE."(user_id)");
	  $db->sql_freeresult($res);
	  $res = $db->sql_query("create index io2 on ".LIKES_OPTIONS_TABLE."(lastnotify)");
	  $db->sql_freeresult($res);
	}

	//Check if likes posts table exists...
	$result = $db->sql_query("show tables like '".LIKES_POSTS_TABLE."'");
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if(!$row){
	  print "Creating Table";
	  $res = $db->sql_query("create table ".LIKES_POSTS_TABLE." (post_id mediumint unsigned not null, extract smallint unsigned not null)");
	  $db->sql_freeresult($res);
	  $res = $db->sql_query("create index io1 on ".LIKES_POSTS_TABLE."(post_id)");
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
	  case "u":
	    print getLikeUpdates();
	    break;

	}
}else{
  if (!defined('IN_PHPBB')) {
	print "I like you too. Send an action if you want more than that.";
	exit;
  }
  global $table_prefix;
  define('LIKES_TABLE',$table_prefix.'bbblikes');
  define('LIKES_OPTIONS_TABLE',$table_prefix.'bbblikesoptions');
  define('LIKES_POSTS_TABLE',$table_prefix.'bbblikesposts');
}


/**
* Attach a excerpt-status to a post
*/
function bbb_attachExcerptStatus($pid,$enable){
  global $db,$user;
  if($enable){$enable=1;}else{$enable=0;}
  $result = $db->sql_query("select extract from ".LIKES_POSTS_TABLE." where post_id = $pid");
  $row = $db->sql_fetchrow($result);
  $db->sql_freeresult($result);
  if(!isset($row['extract'])){
    $res = $db->sql_query("insert into ".LIKES_POSTS_TABLE." (post_id,extract) values($pid,$enable)");
  }else{
    $res = $db->sql_query("update ".LIKES_POSTS_TABLE." set extract = $enable where post_id=$pid;");
  }
  $db->sql_freeresult($res);
}

/**
* Get the excerpt-status of a post
*/
function bbb_getExcerptStatus($pid){
  global $db,$user;
  $result = $db->sql_query("select extract from ".LIKES_POSTS_TABLE." where post_id = $pid");
  $row = $db->sql_fetchrow($result);
  $db->sql_freeresult($result);
  if(!isset($row['extract'])){
    return 0;
  }
  return $row['extract'];
}


/**
* Get the excerpt-default
*/
function bbb_getExcerptDefault(){
    global $db,$hasScripts,$user,$style;
    $res = $db->sql_query("select defaultextract from ".LIKES_OPTIONS_TABLE." where user_id = ".$user->data['user_id']);
    $row = $db->sql_fetchrow($res);
    $db->sql_freeresult($res);
    if($row['defaultextract']){return true;}
    return false;
}
/**
* Set the excerpt-default
*/
function bbb_setExcerptDefault($val){
  global $db,$hasScripts,$user,$style;
  if($val){$val=1;}else{$val=0;}
  $res = $db->sql_query("update ".LIKES_OPTIONS_TABLE." set defaultextract = $val where user_id=".$user->data['user_id']);
  $db->sql_freeresult($res);
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
  $q = "select p.post_subject as subject,u.user_avatar as avatar,p.poster_id as poster_id,u.username as user,l.post_id,count(l.user_id) as c from ".LIKES_TABLE." l,".POSTS_TABLE." p, ".USERS_TABLE." u where u.user_id = p.poster_id and l.post_id = p.post_id and l.created>='".$start->format("Y-m-d H:i:s")."' and l.created <= '".$end->format("Y-m-d H:i:s")."' group by l.post_id order by count(l.post_id) desc, p.post_id asc";
  $result = $db->sql_query($q);
  while($row = $db->sql_fetchrow($result)){
    $avatar = $row['avatar'];
    if(!preg_match("/^http/",$avatar)){
      $avatar = "http://boingboingboing.net/download/file.php?avatar=".$avatar;
    }
    $likes[] = array(
      'POST_ID' => $row['post_id'],
      'AVATAR' => $avatar,
      'EXCERPTABLE' => bbb_getExcerptStatus($row['post_id']),
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
* Get the 'Post Of The Day' from a bunch
* of liked posts, returned by the above
* GetLikesByDates function.
*/
function getBestPostFrom($likes,$excerptableOnly){
  if(sizeof($likes)<=0){return -1;}
  $best = -1;
  if(!$excerptableOnly){
    $best = $likes[0];
  }else{
    foreach($likes as $l){
      if($l['EXCERPTABLE']){
	$best = $l;
	break;
      }
    }
  }
  if($best!=-1){
    $best = addExtractEtc($best);
  }
  return $best;
}


/**
* Get first x sentences
*/
function xtruncate($body, $sentencesToDisplay = 2) {
    $nakedBody = preg_replace('/\s+/',' ',strip_tags($body));
    $sentences = preg_split('/(\.|\?|\!|\:)(\s)/',$nakedBody);
    if (count($sentences) <= $sentencesToDisplay)
        return $nakedBody;
    $stopAt = 0;
    foreach ($sentences as $i => $sentence) {
        $stopAt += strlen($sentence);
        if ($i >= $sentencesToDisplay - 1)
            break;
    }
    $stopAt += ($sentencesToDisplay * 2);
    return trim(substr($nakedBody, 0, $stopAt));
}


/**
* Get an extract from a post's content.
*/
function addExtractEtc($post){
  global $db;
  $extract = "Can't Extract";
  $q = "select p.post_text as content from ".POSTS_TABLE." p where p.post_id = ".$post['POST_ID'];
  $result = $db->sql_query($q);
  if($row = $db->sql_fetchrow($result)){
    $extract = $row['content'];
  }

  //Remove BBCode.
  $extract = preg_replace("/\[quote.*\[\/quote[^\]]*\]/s","",$extract);
  $extract = preg_replace("/\[img.*\[\/img[^\]]*\]/s","",$extract);
  $extract = preg_replace("/\[[^\]]*\]/s","",$extract);
  $extractx = xtruncate($extract, 2);
  if($extractx!=$extract){
     $extract = $extractx."...";
   }

  $post['EXTRACT'] = $extract;
  return $post;
}


/**
* Function to show any updates to the like status
* of posts you've posted. IE, we wanna find out
* if anyone has liked any of your posts since
* the last time you looked and update the store
* of the last time you looked.
*
* If there has been no looking yet, we don't
* wanna give updates on ALL the post EVER of
* course. So in that case just create the
* table row with now() in the date.
*
*/
function getLikeUpdates(){
  global $db,$user;
  $me = intval($user->data['user_id']);
  $result = $db->sql_query("select lastnotify from ".LIKES_OPTIONS_TABLE." where user_id = $me");
  $row = $db->sql_fetchrow($result);
  $db->sql_freeresult($result);
  if(!isset($row['lastnotify'])){
    //Not created an notity time yet. Better do that then stop.
    $res = $db->sql_query("insert into ".LIKES_OPTIONS_TABLE." (user_id,lastnotify,defaultextract) values($me,now(),0)");
    $db->sql_freeresult($res);
    return null;
  }
  $last = new \DateTime($row['lastnotify']);

  //Update it so that the last update is NOW
  $res = $db->sql_query("update ".LIKES_OPTIONS_TABLE." set lastnotify = now() where user_id=$me;");
  $db->sql_freeresult($res);
  

  //Find all your likes since last notify
  $q = "select p.post_subject as subject,l.post_id,u.username,u.user_id from ".LIKES_TABLE." l,".POSTS_TABLE." p, ".USERS_TABLE." u where u.user_id = l.user_id and l.post_id = p.post_id and p.poster_id = $me and l.created>='".$last->format("Y-m-d H:i:s")."' order by l.created";
  $result = $db->sql_query($q);
  while($row = $db->sql_fetchrow($result)){
    $likes[] = array(
      'POST_ID' => $row['post_id'],
      'TITLE' => $row['subject'],
      'USER' => $row['username'],
      'USER_ID' => $row['user_id'],
    );
  }
  $db->sql_freeresult($result);
  if(sizeof($likes)<=0){
    return null;
  }

  $s="<div class=\"likesnotify\">";
  foreach($likes as $l){
    $s.='<a href="memberlist.php?mode=viewprofile&u='.$l['USER_ID'].'">'.ucfirst($l['USER']).'</a> liked your post <a href="viewtopic.php?p='.$l['POST_ID'].'#p'.$l['POST_ID'].'">'.$l['TITLE']."</a><br/>\n";
  }
  $s.="</div>";
  return $s;
}


/**
* Function to show a chart of recent liked posts
* It makes a whole phpbb3 page
*/
function showCharts(){
  global $user,$template;
  $isPOTD=false;
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

  $nonPOTD = -1;
  $realPOTD = -1;
  if(isset($_REQUEST["s"])){
    $startDate = new \DateTime($_REQUEST["s"]."T00:00:0+00:00");
    if(isset($_REQUEST['e'])){
      $endDate = new \DateTime($_REQUEST["e"]."T00:00:0+00:00");
    }else{
      $isPOTD=true;
      $endDate = clone($startDate);
      $endDate->add(new \DateInterval(P1D));
    }
  }
  $likes = getLikesByDates($startDate,$endDate);
  if($isPOTD){
      $realPOTD = getBestPostFrom($likes,false);
      if(!$realPOTD['EXCERPTABLE']){
        $nonPOTD = $realPOTD;
        $realPOTD = getBestPostFrom($likes,true);
      }
  }

  $tomorrow = clone($startDate);
  $tomorrow->add(new \DateInterval("P1D"));
  $yesterday = clone($startDate);
  $yesterday->sub(new \DateInterval("P1D"));
  $template->assign_vars(array(
    "ISPOTD"=> $isPOTD,
    "TOMORROW"=> $tomorrow->format("Y-m-d"),
    "YESTERDAY"=> $yesterday->format("Y-m-d"),
    "STARTDAY"=> $startDate->format("D d M Y"),
    "STARTDATE"=> $user->format_date($startDate->getTimestamp()),
    "STARTUNIX"=> $startDate->getTimestamp(),
    "ENDDATE"=> $user->format_date($endDate->getTimestamp()),
    "ENDUNIX"=> $endDate->getTimestamp(),
  ));
  foreach($likes as $l){
    $template->assign_block_vars('likerow', $l);
  }
  if($nonPOTD!==-1){
    $template->assign_block_vars('non_potd_post', $nonPOTD);
  }
  if($realPOTD!==-1){
    $template->assign_block_vars('real_potd_post', $realPOTD);
  }
  page_footer();
}

?>

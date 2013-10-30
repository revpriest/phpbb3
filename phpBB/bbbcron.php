<?php
  /**
  * A cron for Boing which pushes post of the day
  * to facebook, updates the subtitle, and counts
  * scores on any games I decide to invent later.
  *
  * The cron should be called every how, it has
  * a function 'nowBetween' to do things less
  * frequenty. For example, new post of the day
  * is posted between 09:00h and 09:30h, and then
  * posted to Facebook between 12:00h and 12:30h.
  *
  * If the cron ends up getting called more than
  * once during that time, it'll end up posting
  * more than once. So really, hourly!
  *
  */
  $POTDThreadId = 33246;
  $POTDForumId = 3;

  define('IN_PHPBB', true);
  $phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
  $phpEx = substr(strrchr(__FILE__, '.'), 1);
  include($phpbb_root_path . 'common.' . $phpEx);
  include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
  include($phpbb_root_path . 'includes/bbcode.' . $phpEx);
  include("like.php");



  

  /**
  * Isolate the daily thread!
  * 
  * A post, in "chat", with the 3-letter day
  * in it, posted first after 3am.
  */
  function getTodaysDailyThreadId(){
    global $db;
    $now = new \DateTime();
    $todayName = strtolower($now->format("D")); 
    $threeAm = new \DateTime($now->format("Y-m-d 3:0:0"));

    //Get all today's topic names
    $result = $db->sql_query("select topic_id,topic_title from ".TOPICS_TABLE." where forum_id = 3 and topic_title like '%$todayName%' and topic_time>".$threeAm->getTimestamp());
    if($row = $db->sql_fetchrow($result)){
      $db->sql_freeresult($result);
      $row['todayname'] = $todayName;
      return $row; 
    }
    $db->sql_freeresult($result);
    return false;
  }
  

  /**
  * Set the subtitle to the daily-thread title, with apprioriate search-replace
  */
  function resetSubtitle(){
    global $db;

    $dailyThread = getTodaysDailyThreadId();
    if($dailyThread===false){
      return "No Change, can't find Daily thread.";
    }

    $newTitle = $dailyThread['topic_title'];
    $tn = $dailyThread['todayname'];
    $newTitle = preg_replace("/".$tn."\S*/i","Boing",$newTitle);

    $newTitle = mysql_real_escape_string($newTitle);

    $res = $db->sql_query("update ".CONFIG_TABLE." set config_value = \"$newTitle\", is_dynamic=1 where config_name=\"site_desc\"");
    $db->sql_freeresult($res);
    
    return $newTitle;
  } 

  /**
  * Get the Post Of The Day for a given day
  */
  function getPostOfTheDay($day=null){
    if($day==null){
      $day = new \DateTime();
      $day->sub(new \DateInterval("P1D"));
    }
    $day = new \DateTime($day->format("Y-m-d 00:00:00"));
    $dayAfter = clone($day);
    $dayAfter->add(new \DateInterval("P1D"));

    $likes = getLikesByDates($day,$dayAfter);
    $realPOTD = getBestPostFrom($likes,false);
    $realPOTD['POTD_TITLE'] = "Post Of The Day ".$day->format("D d M y"); 
    if(!$realPOTD['EXCERPTABLE']){
      $nonPOTD = $realPOTD;
      $realPOTD = getBestPostFrom($likes,true);
      $realPOTD['POTD_TITLE'] = "Post Of The Day ".$day->format("D d M y"); 
      $nonPOTD['POTD_TITLE'] = "NOT Of The Day, coz it wouldn't allow excerpting..."; 
    }
    return array("real"=>$realPOTD,"non"=>$nonPOTD);
  }

  /**
  * Push a post to the POTD thread
  */
  function postToThread($user_id,$username,$subject,$text){
    global $db,$POTDThreadId,$POTDForumId;
    $subject = mysql_escape_string($subject);
    $text = mysql_escape_string($text);
    $username = mysql_escape_string($username);
    $checksum = md5($text);
    $bbcodeUid = gen_rand_string();
    $time = new \DateTime();
    $time = $time->getTimestamp();
	  $res = $db->sql_query("insert into ".POSTS_TABLE." (post_id,topic_id,forum_id,poster_id,icon_id,poster_ip,post_time,post_approved,post_reported,enable_bbcode,enable_smilies,enable_magic_url,enable_sig,post_username,post_subject,post_text,post_checksum,post_attachment,bbcode_bitfield,bbcode_uid,post_postcount,post_edit_time,post_edit_reason,post_edit_user,post_edit_count,post_edit_locked ) values(null,$POTDThreadId,$POTDForumId,$user_id,0,\"127.0.0.1\",$time,1,0,1,1,1,0,\"$username\",\"$subject\",\"$text\",\"$checksum\",0,\"\",\"$bbcodeUid\",1,0,\"\",0,0,0);");
    $postId = $db->sql_nextid();

		// Update the topics table
		$sql = 'UPDATE ' . TOPICS_TABLE . " set topic_replies = topic_replies+1, topic_replies_real = topic_replies_real+1, topic_last_post_id = $postId, topic_last_poster_id = $user_id, topic_last_poster_name = \"$username\", topic_last_post_subject = \"$subject\", topic_last_post_time = $time WHERE topic_id = $POTDThreadId";
		$db->sql_query($sql);
	}


  /** 
  * Format a single Post Of The Day
  * as HTML
  */
  function formatPotd($x){
    $html = '<div class="potd">';
    $html.= '<h1>'.$x['POTD_TITLE'].'</h1>';
    $html.= '<div class="potddetails">';
    $html.= ' <img src="'.$x['AVATAR'].'" width ="75" /><br/>';
    $html.= ' <a href="memberlist.php?mode=viewprofile&u='.$x['AUTHOR_ID'].'">'.$x['AUTHOR'].'</a>';
    $html.= '</div>';
    $html.= '<div class="potdsubject">'.$x['TITLE'].'</div>';
    $html.= '<div class="potdcontent">'.$x['EXTRACT'].'</div>';
    $html.= '<div class="potdlink"><a href="viewtopic.php?p='.$x['POST_ID'].'#p'.$x['POST_ID'].'">Read Full Post...</a></div>';
    $html.= '</div>';
    return $html;
  }

  /**
  * Format The POST OF THE DAY as HTML ready for inserting 
  * (naughtily, HTML isn't normally allowed) to the tables
  */
  function formatPotds($p){
    $html = "";
    $html.= formatPotd($p['real']);
    if(isset($p['non'])){
      $html.="<br/ clear=\"both\"><br/></p>";
      $html.= formatPotd($p['non']);
    }
    return $html;
  }

  /**
  * Figure out which post is post of the day,
  * Then post it.
  */
  function postPOTD(){
    $potds = getPostOfTheDay();
    postToThread(1,"AutoPosted",$potds['real']['POTD_TITLE'],formatPotds($potds));
  }

  /**
  * Is the current time beteween two other times?
  */
  function nowBetween($from,$to){
    $now = new \DateTime();
    $from = new \DateTime($now->format("Y-m-d ").$from);
    $to = new \DateTime($now->format("Y-m-d ").$to);
    if(($from->getTimestamp()<$now->getTimestamp())&&
       ($to->getTimestamp()>$now->getTimestamp())){
      return true;
    }
    return false;
  }


  // Do all the things.
  if(nowBetween("9:00","9:30")){              //Post a new post of the day if it's about 9am.
    print "POTD was Post ".postPOTD()."\n";
  }
  print "Setting subtitle to: ".resetSubtitle()."\n";




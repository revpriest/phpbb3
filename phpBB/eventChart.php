<?php
/***************************************************************************\
* Events Charts. Show the top X events as judged by rollcall
\***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);

$defaultNumber=51;
$maxNumber=200;
$number =  $defaultNumber; //Max number of new posts at once.

if(isset($HTTP_GET_VARS['number'])){
  $number = $HTTP_GET_VARS['number'];
}else if(isset($HTTP_POST_VARS['number'])){
  $number = $HTTP_POST_VARS['number'];
}
if($number>$maxNumber){$number=$maxNumber;}


include_once("rollcall.php");

  #Forum names into an array
  $sql = "select topic,count(topic) as count,topics.topic_title as title from ".ROLLCALL_TABLE." as rollcall,".TOPICS_TABLE." as topics where topics.topic_id=rollcall.topic group by topic order by count(topic) desc limit $number";
  if ( !($result = $db->sql_query($sql)) ) {
     print "Can't get chart info";exit;
  }
  $n=1;
  $table =  "<table border=\"1\">";
  $table.= "<tr><th>Pos</th><th>Event</th><th>Count</th></tr>";
  $lastCount=-1;
  $lastn=1;
  while($row = $db->sql_fetchrow($result)) {
    $topicID=$row['topic'];
    $topicTitle=$row['title'];
    $count=$row['count'];
    if($topicID==18063){
      $table.="<tr><td><strike>$n</strike></td><td><strike><a target=\"_top\" href=\"/viewtopic.php?t=$topicID\">$topicTitle</a></strike></td><td><strike>$count</strike></td></tr>\n";
    }else{
      if($count==$lastCount){
        $m=$lastn;
      }else{
        $m=$n;
	$lastn=$m;
      }
      $table.="<tr><td>$m</td><td><a target=\"_top\" href=\"/viewtopic.php?t=$topicID\">$topicTitle</a></td><td>$count</td></tr>\n";
      $lastCount=$count;
      $n++;
    }
  }
  $db->sql_freeresult($result);
  $table.= "</table>";

  $user->setup('viewtopic', $topic_data['forum_style']);
  page_header("Event Chart", true, null);
  $template->assign_vars(array(
	'TABLE' 		=> $table
  ));
 $template->set_filenames(array('body' => 'eventchart.html'));
 make_jumpbox(append_sid("{$phpbb_root_path}viewforum.$phpEx"), $forum_id);
 page_footer();

?>

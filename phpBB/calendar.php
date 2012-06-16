<?php
/**
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* Adapted to add a calendar. The calendar can show any
* month from any year. It shows a box for every day of
* that month.
* 
* In that box it can show either topics started on that
* day, or topics last posted to on that day, or topics
* that have an event-date associated with that day.
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('viewtopic', $topic_data['forum_style']);



/****************************************************
* Function to return the topics associated with a 
* paritcular day. Depending on the mode, this can 
* be threads STARTED on that day, LAST_POSTED_TO
* on that day or EVENT_DATE on that day.
* 
* We're given a unix timestamp.
*/
function getTopicsForThisDay($t,$mode){
  global $db;
  $ret = "";
  $t1 = $t+24*60*60;   //At the end of the day, we'll need to have an upper bound.
  if($mode == 2){
    //Mode for showing time of LAST post
    $where = "topic_last_post_time > $t and topic_last_post_time < $t1";
    $sql = 'SELECT topic_id,topic_title FROM ' . TOPICS_TABLE . " WHERE $where";
  }else if($mode == 1){
    //Mode for showing time of FIRST post.
    $where = "topic_time > $t and topic_time < $t1";
    $sql = 'SELECT topic_id,topic_title FROM ' . TOPICS_TABLE . " WHERE $where";
  }else{
    //Default to showing event-dates on that day.
    $sql = 'SELECT topic_id,topic_title FROM ' . TOPICS_TABLE . " WHERE false";
  }

  $result = $db->sql_query($sql);
  while($row = $db->sql_fetchrow($result)){
    $ret .= '<a class="calendarLink"  href="viewtopic.php?t='.$row['topic_id'].'">'.$row['topic_title']."</a><br/>";
  }
  $db->sql_freeresult($result);
  return $ret;
}



//Right, first thing's first, what month do we wanna show?
if($_REQUEST['month']){
  $month = $_REQUEST['month'];
}else{
  $month = (int)date('m');
}
if($_REQUEST['year']){
  $year = $_REQUEST['year'];
}else{
  $year = (int)date('Y');
}
if($_REQUEST['mode']){
  $mode = $_REQUEST['mode'];
}else{
  $mode = 0;
}
$modeNames = array("with event on day","started during day","ended during day");
$modeName = $modeNames[$mode];


$startTime = strtotime("$year/$month/1");

if($month==1){
  $prevMonth=12;
  $prevYear = $year-1;
}else{
  $prevMonth=$month-1;
  $prevYear = $year;
}
if($month==12){
  $nextMonth=1;
  $nextYear = $year+1;
}else{
  $nextMonth=$month+1;
  $nextYear = $year;
}

// Send vars to template
$monthName = date("F",$startTime);
$template->assign_vars(array(
	'MONTHNAME'	=> $monthName,
	'MONTH' 	=> $month,
	'YEAR' 	    => $year,
	'PREVMONTH'	=> $prevMonth,
	'NEXTMONTH'	=> $nextMonth,
	'PREVYEAR' 	=> $prevYear,
	'NEXTYEAR' 	=> $nextYear,
	'SKIPYEAR' 	=> $year+1,
	'BACKYEAR' 	=> $year-1,
	'MODE'   	=> $mode,
	'MODENAME' 	=> $modeName
));

//Pad days from Sunday till first day of the month
$startWeekday = date('w',$startTime);
for($n=0;$n<$startWeekday;$n++){
  $template->assign_block_vars('calpad', array());
}

//One calday for each day of the month
$numDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
for($n=0;$n<$numDays;$n++){
  $events=getTopicsForThisDay($startTime+$n*24*60*60,$mode);
  $template->assign_block_vars('calday', array("DATE"=>$n+1,"EVENTS"=>$events));
}




// Output the page
page_header("Calendar");
$template->set_filenames(array('body' => 'calendar.html'));
make_jumpbox(append_sid("{$phpbb_root_path}viewforum.$phpEx"), $forum_id);
page_footer();

?>

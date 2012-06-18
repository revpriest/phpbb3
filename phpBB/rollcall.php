<?php
/**************************************************
* Functions for the rollcalll. Threads with event-
* dates also get a rollcall. We create the table
* here if it doesn't already exist, and define
* all the relevent functions.
*/
if ( !defined('IN_PHPBB') ) { die("Hacking attempt"); }
define('ROLLCALL_TABLE', $table_prefix."rollcall");

include_once("eventDates.php");

#Create the table if it ain't already there....
$sql = 'CREATE TABLE IF NOT EXISTS '.ROLLCALL_TABLE.' (`id` mediumint(9) NOT NULL, `topic` mediumint(9) NOT NULL, UNIQUE KEY `id` (`id`,`topic`));';
$result = $db->sql_query($sql);


#Assign the rollcall variables.
function assignRollcallVars($topic_id){
  global $template;
  global $db;
  global $_REQUEST;
  global $user;

  $sql = "SELECT count(*) as count from ".EVENTDATES_TABLE." where thread=$topic_id";
  $result = $db->sql_query($sql);
  $row = $db->sql_fetchrow($result);
  if($row['count']<=0){
    $template->assign_vars(array(
  	'ROLLCALL'		=> false
    ));
    return;     //Ain't no event-date, and so no rollcall for this topic.
  }


  //Are we agreeing to go? Backing out?
  //NOTE: We ought to do some CSRF checks on this really,
  //if being able to trick people into or out of the rollcall 
  //mattered in any way at all.
  if($_REQUEST['rollcall']=="true"){
      $sql = "insert into ".ROLLCALL_TABLE." values (".$user->data['user_id'].",$topic_id);";
      $result = $db->sql_query($sql);
  }
  if($_REQUEST['rollcall']=="false"){
      $sql = "delete from ".ROLLCALL_TABLE." where topic=$topic_id and id = ".$user->data['user_id'].";";
      $result = $db->sql_query($sql);
  }


  //Generate the rollcall data.
  $going = false;
  $rollcall = "";
  $sql = "SELECT u.username as username,u.user_id as id from ".USERS_TABLE." as u,".ROLLCALL_TABLE." as r where u.user_id = r.id and r.topic = $topic_id order by u.username";
  if ( !($result = $db->sql_query($sql)) ) {
    $rollcall .= "Error: Could not obtain Rollcall Data.";
  }else{
    $n=0;
    while($row = $db->sql_fetchrow($result)) {
       if($n>0){ $rollcall.= ", "; }
       if($user->data['user_id']==$row['id']){$going=true;}
       $rollcall .= "<a href=\"profile.php?mode=viewprofile&u=".$row['id']."\">".$row['username']."</a>";
       $n++;
    }
  }
  $rollcall.="<br/><br/>Total:".$n." people";

  $clicky = '<a href="viewtopic.php?t='.$topic_id;
  if($going){
    $clicky .= '&rollcall=false">I\'m Backing Out';
  }else{
    $clicky .= '&rollcall=true">I\'m Going';
  }
  $clicky .= "</a>";

  $template->assign_vars(array(
  	'ROLLCALL'		=> true,
	'ROLLCALL_USERLIST'		=> $rollcall,
	'ROLLCALL_CLICKY'		=> $clicky
  ));
}




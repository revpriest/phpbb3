<?php
/**************************************************
* Functions for events dating. The calendar and the
* posting functions need access to this stuff so
* they can attach dates to events and whatnot.
*/
if ( !defined('IN_PHPBB') ) { die("Hacking attempt"); }
define('EVENTDATES_TABLE', $table_prefix."event_dates");

#Create the table if it ain't already there....
$sql = 'CREATE TABLE IF NOT EXISTS '.EVENTDATES_TABLE.' (thread mediumint not null, date datetime not null, primary key (thread), key datekey (date))';
$result = $db->sql_query($sql);




/************************************************************
* Attach a date to an event. Delete any old one first
* coz there can be only one.
*/
function eventDates_attachDate($topic_id,$date){
  global $db;
  $dateStr = $date['year']."/".$date['month']."/".$date['day'];
  $sql = "delete from ".EVENTDATES_TABLE." where thread = $topic_id";
  $result = $db->sql_query($sql);
  if($date!='reset'){
    $sql = "insert into ".EVENTDATES_TABLE." values($topic_id,'$dateStr')";
    $result = $db->sql_query($sql); 
  }
}


/********************************************************
* Assign the {EVENTDATE} var if there's a date attached
* to some given topic_id
*/
function eventDates_assignVars($topic_id){
  global $template;
  global $db;
  $sql = 'SELECT date FROM '.EVENTDATES_TABLE.' where thread='.$topic_id;
  if ( $result = $db->sql_query($sql))  {
    $row = $db->sql_fetchrow($result);
    $date=$row['date'];
    $datearray = explode("-",$date);
    $datearrayt = explode(" ",$datearray[2]);
    $datearray[2]=$datearrayt[0];
    $date = $datearray[2]."/".$datearray[1]."/".$datearray[0];
  }
  if(empty($date)||($date=="//")){
    $date = "dd/mm/yyyy";
  }
  $template->assign_vars(array('EVENTDATE'=>$date));
}


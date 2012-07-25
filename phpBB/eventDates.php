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


/****************************************
* Function to fetch the date associated
* with a given topic. Returns empty string
* if there ain't one.
*/
function getEventDate($topic_id,$returnEmpty=0){
  global $db;
  $date = "";
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
    if($returnEmpty){
      return "";
    }else{
      $date = "dd/mm/yyyy";
    }
  }
  if($returnEmpty){
    return " - ".$date;
  }else{
    return $date;
  }
}


/********************************************************
* Assign the {EVENTDATE} var if there's a date attached
* to some given topic_id
*/
function eventDates_assignVars($topic_id,$isFirst){
  global $template;
  global $db;
  global $mode;

  //Replies don't get event-dates, edits only if it's first.
  if(($mode=="quote")||($mode=='reply')||($mode=='edit')&&(!$isFirst)){
    return;
  }
  $date = getEventDate($topic_id);
  $template->assign_vars(array('EVENTDATE'=>$date));
}


<?php
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);
include_once("rollcall.php");

  $query = "select username,user_regdate,max(post_time) as lasttime,count(username) as count from phpbb_users left join phpbb_posts on phpbb_users.user_id = phpbb_posts.poster_id where user_type=0 or user_type=3 group by username order by max(post_time);";
  $result = $db->sql_query($query);

?>

  <table border="1">
  <tr> 
    <th>Username</th>
    <th>Registered</th>
    <th>Num Posts</th>
    <th>Most Recent Post</th>
  </tr> 
  <?php while($r = $db->sql_fetchrow($result)): ?>
     <tr>
	<td><?php echo $r['username']; ?></td>
	<?php $dt = new DateTime(); $dt->setTimestamp(intval($r['user_regdate'])); ?>
	<td><?php echo $dt->format("Y-M-d H:i"); ?></td>
	<td><?php echo $r['count']; ?></td>
	<?php $dt = new DateTime(); $dt->setTimestamp(intval($r['lasttime'])); ?>
	<td><?php echo $dt->format("Y-M-d H:i"); ?></td>
     </tr>
  <?php endwhile; ?>

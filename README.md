[![phpBB](http://www.phpbb.com/theme/images/logos/blue/160x52.png)](http://www.phpbb.com)

## THIS FORK

Adding some changes that have been working well for me in
a phpbb2 board. We're switching to phpbb3 soon with luck
so I'll need all the same changes here that we had there:

1) "Chat-View" page. "Adams Chat Engine".
    Allows users to view all posts in either
    the whole board, a given forum, or a given
    topic. Shows the first 20 initially, and
    new posts pop into the bottom as they are
    recieved. You can reply just by selecting
    which post you're replying to and writing
    into the reply box at the bottom. Makes
    an IRC-style view on the board content.

2) Events Calendar.
   All topics can have a "Date", and if that's
   set then the topic is called an "Event" and
   it gets an entry in the calendar. Events
   also get a rollcall, in which users who are
   planning to attend the event can click to 
   let everyone know. Existing calendar mods 
   don't attach events to topic threads for
   some reason. Crazy! We do.

3) Post voting.
   Allow users to "Like" a topic, and organize
   the like-votes to allow "Best of" pages showing
   highly voted topics between given dates.

## PROGRESS

The IRC-View, "ACE", is mosty complete and available
from /ace.php with either a "t" or an "f" CGI parameter
to specify what to listen to. The templates have not
been adjusted to link to that page as yet.

The events calendar hasn't even been started yet
(well, it exists in the old phpbb2 board but likely
I'll rewrite rather than use that code).

The post-voting hasn't even been started yet
(also exists in the old board, will likely
not reuse the code and just rewrite better
though)

## ABOUT

phpBB is a free bulletin board written in PHP.

## COMMUNITY

Find support and lots more on [phpBB.com](http://www.phpbb.com)! Discuss the development on [area51](http://area51.phpbb.com/phpBB/index.php).

## CONTRIBUTE

1. [Create an account on phpBB.com](http://www.phpbb.com/community/ucp.php?mode=register)
2. [Create a ticket (unless there already is one)](http://tracker.phpbb.com/secure/CreateIssue!default.jspa)
3. [Read our Git Contribution Guidelines](http://wiki.phpbb.com/Git); if you're new to git, also read [the introduction guide](http://wiki.phpbb.com/display/DEV/Working+with+Git)
4. Send us a pull request

## LICENSE

[GNU General Public License v2](http://opensource.org/licenses/gpl-2.0.php)

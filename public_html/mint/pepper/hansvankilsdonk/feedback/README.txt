**************************************************************************
Feedback v0.1 - 20070131
(c) Hans van Kilsdonk
Website: http://mint.ufx.nl
E-mail: mail@mint.ufx.nl

Thanks to:

- Developers of MagpieRSS for creating a simple RSS parser
  => http://magpierss.sourceforge.net/
- Simon for the peppers' name, bugtesting and the useful comments;
- The people @ the Mint forum for the useful comments;
- Steffx, David, Helmut for the extra aggregator icons;
- All the people that bugtested this damn thing :);
- James Byers for the Sparks PHP class
  => http://sparkline.org/
  edited the class a bit to support transparency;
- Shaun for making Mint.

**************************************************************************

~ DESCRIPTION
=============
Feedback for Mint 1.2/2.0 is a Pepper which tracks your (RSS/Atom) feeds. 
By using a seperate 'tracker' you can see how many hits, subscribers and
views your feed has. The tracker will not redirect your feed and you
can also select that the tracker does not change your feed in any way
(however, in that situation you can't track clicks).

Mod_rewrite is needed on your host for Feedback.

~UPGRADE
===================
If you upgrade from version 0.01 then you need to remove the tracker.php
files and any custom additions you made to your .htaccess files. Then 
overwrite the 'hansvankilsdonk/feedback' directory with the new version
and read the 'HOW TO INSTALL' section.

If you upgrade from any other version to the latest just overwrite the
'hansvankilsdonk/feedback' directory. Then generate the rewrite rules
from the preferences, add them to your .htaccess file and you're done!

~ HOW TO INSTALL
================
Copy the directory:

hansvankilsdonk/feedback

to your 'mint/pepper/hansvankilsdonk' directory. Go to the Mint preferences, 
select 'install' and push the button 'install' next to 'Feedback'.
Feedback creates two extra MySQL tables: one for tracking the current users
and one for storing the monthly, archived information.

Now you've installed Feedback in Mint, you need to set the feeds you would
like to track in the preferences tab of Feedback. Enter the full URLS to
your feeds in the 'Feeds' section. Press the '+' to add the feed. Now you
will see a textblock with the mod_rewrite rules you need to add to your
.htaccess file in your root directory. You need to do this every time you
will add a new feed!

If you've added the rules now your feed(s) will be tracked by Feedback.  

~ USAGE
=======
~ Daily ~
This tab shows you the subscribers, hits and clicks per day. By clicking
on a day you will see the stats specified by feed name.

~ Monthly ~
This tab shows you the subscribers, hits and clicks per month. You can
click on a month to specify it by feed name.

~ Subscribers ~
A list of active subscribers. You can view their clicks by clicking on
the subscriber. If there's an icon installed for a particular reader,
it is shown next to the subscriber. If you have nametags installed, you
will see the cookie- and IP tags also. 

~ Hot items ~
The top 25 most clicked items.

~ Sparks ~
This tab shows you tiny graphs of the past 14 days and past 12 months.

Feedback stores the information for 14 days. After 14 days the statistics
are archived. 

~ PREFERENCES
=============
You can set the following preferences:

- Number of subscribers to show: maximum number of subscribers you would
  like to see in the 'subscribers' tab (maximum of 50);

- Number of hot items to show: maximum number of hot items to show (maximum of 
  50);

- Number of days to show: how many days to show in the 'stats' tab (maximum
  of 14);

- Show only subscribers with clicks: show only the subscribers that clicked
  on a feed item.

- Track clicks in the feed: enabled by default and this will rewrite the URL's
  in the feed so they are trackable. If you disable this function your feed
  will remain the same as the original feed but you will not see any click-
  statistics.

- Which type of sparks to use: the type of graphs you'd like to see in the 
  sparks tab (bars or lines);

- Use hostip.info for country/city resolve: you can use hostip.info for
  tracking your subscribers' city and country;

- Show debug information on error: If the tracker doesn't work, generate
  some debug information. You may send it to me so I can try to solve the
  problem.

~ AGGREGATOR ICONS
==================
In the 'subscribers' tab you will see some aggregator icons. There are some
default but you can add extra icons (size: 16 x 16 pixels) for yourself. 
You can do this by uploading the new icon in the following directory:

mint/pepper/hansvankilsdonk/feedback/icons/

Next, you need to edit the 'icons.txt' in the directory:

mint/pepper/hansvankilsdonk/feedback

Add a line to the end:

<name of the aggregator>		<name of the icon>

For example:

firefox		firefox.png

The name of the aggregator is case insensitive. And you can use some basic
regular expressions. 

If you add an item, please let me know at mail@mint.ufx.nl so I can add it
to the main distribution file.  

~ TROUBLESHOOTING 
=================
If you encounter any problems with Feedback please enable the 'debug'
function in the preferences.  If the tracker can't find your Mint
installation you can edit the tracker.php file:

$your_mint_path = '/path/to/mint/';

If you still have troubles send the output of the debug info to:

mail@mint.ufx.nl

Please attach the contents of your .htaccess file and I will try to
solve your problem in exchange for ONE MILLION DOLLARS!!!!!!!!!!!!!
(or maybe for free...) 

~ CHANGELOG
===========

~ 0.1 - 20070131
- Mint 2.0 compatible;
- Added the option to use hostip.info to add a country and a city to
  an IP address (if available). This slows down the subscribers tab a 
  bit and if you don't like it you can disable it in the preferences;
- If the tracker.php file cannot find your mint installations, you
  can define it yourself in tracker.php;
- The monthly and daily stats are split up in two different tabs;
- If the reader shows the number of subscribers (eg: 'subscribers: 5')
  Feedback will add this number to the total number of subscribers;
- New tab: Sparks. Shows tiny graphs of the last 14 days and past 12
  months. In the preferences you can set the type of sparks you would
  like to see; 
- Longer day and month names;
- Changed the rewrite code so multiple feeds are possible. If you
  know a better way to rewrite this, let me know :)
- Again a few new aggregator icons;
- Some small bugfixes.

~ 0.07 - 20070103
- More aggregator icons added (thanks to Steffx);
- Special characters in the titles are fixed;
- Added anti-looping to the tracker file;
- Added a 'debug' option so you will see some info when things go wrong in
  the tracker. You can enable the debug function in the preferences;
- Rewrite rules changed a bit so update them when you upgrade; 
- Some minor bugfixes.

~ 0.06 - 20070101
- Added Atom feed support;
- Added new aggregator icons;
- Feed names are now abbreviated in the stats tab if they are too long;
- Tracker works now with 'allow_url_fopen' set to 'off';
- Added an option in the preferences to disable click tracking. This
  way the links in the feed are not rewritten (and - ofcourse - you
  cannot see any clicks in your statistics);
- Fixed a bug in the archiving of the statistics.

~ 0.05 - 20061230
- Updated the way Feedback is implemented. Only one tracker.php file is 
  needed for all the feeds. Using mod_rewrite you can rewrite your feeds
  to the tracker. Using a helper in the preferences you can see what to
  add in your .htaccess file;
- Full daynames instead of three letters in the daily stats;
- Feedback now tracks the client of the subscriber;
- Added icons to the subscriber (depending on which client they use). You
  can add extra icons using the icons.txt file;
- If you have Nametags installed it will show the IP- and cookietags in the
  subscribers tab;
- Fixed a bug in the daily stats where all the hits were only shown on the
  last day;
- Fixed a bug in tracker.php.

~ 0.01 - 20061228
- First public release.

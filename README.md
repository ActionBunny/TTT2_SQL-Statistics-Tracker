# TTT2_SQL-Statistics-Tracker
Repository for my addon [TTT2 SQL Statistics Tracker] (https://steamcommunity.com/sharedfiles/filedetails/?id=3100649542)

TTT feels short lived? You can't compare to your mates? Hold on. This addon creates SQL-tables and fills them with events like kills, round wins, orders and so on. Perfect way to create a scoreboard on your loadingscreen.
If you are educated in SQL, PHP, HTML you can set up very neat scoreboards. If not, I got a ready to use template for you.

Only you as a server-owner have to set up this addon.
My addon is based on [MySQLOO9 from FredyH](https://github.com/FredyH/MySQLOO)

You can enable/disable tracking with cvar ttt_testing 0/1 (enable/disable)

## 1. Get a Webspace
To track statistics and show them in a scoreboard on your loading screen, you need a webspace including a MySQL-Database with a fair amount of dataspace (over 50MB recommended). You can either rent a external webspace OR set up XAMPP Apache on your machine. To set up 

**XAMPP Apache**:
1. Download an install from https://www.apachefriends.org/de/index.html
2. In XAMPP start modules Apache and MySQL and check for errors, alternatively enable autostart.
3. In XAMPP open MySQL Config and set your username and password
4. Get a free DDNS-Services running like https://account.dyn.com/. With DDNS your alternating numeric IP can always be reached by a static IP (yourcoolip.dyndns.org instead of 123.456.789.123)
5. Open your router configuration-page, portforward 80 443 3306 to your PC and configure DDNS with your data. If you configure DDNS in your router, it will automatically update your numeric ip to your static ip.
6. Try to login to your MySQL Database editor called http://localhost/phpmyadmin
7. Try to edit your website XAMPP/htdocs/index.html

**External webspace**
Your chosen hosting services should guide you through all steps.

### Do you have everything?
* added my addon to your collection?
* have a website?
* able to edit your website?
* have access to a mysql database?

## 2. Setup Addon TTT2 SQL Statistics tracker
Now to the part to let TTT communicate with your SQL Database

1. Visit [TTT2 SQL Statistics Tracker] (https://steamcommunity.com/sharedfiles/filedetails/?id=3100649542) and add it to your server collection.
2. Subscribe and add to your server collection
3. Goto: [MySQLOO9 from FredyH](https://github.com/FredyH/MySQLOO), download your MySQLOO version and place it inside garrysmod/lua/bin/ **OR** download _garrysmod/lua/bin/_ from this git and place it in your TTT-Server
4. Start your server once. After fully loading, shut it down.
5. Edit _garrysmod/data/dblogging/config.txt_ and fill in your Webspace and MySQL credentials.
6. Edit _garrysmod/cfg/server.cfg_ and set [sv_loadingurl](https://wiki.facepunch.com/gmod/Loading_URL) to yourcoolip.dyndns.org
7. Start your server. One of the last outputs should be _Database linked_.
8. You should see a message, that a new player was added to database when joining.

## 3. Create a loadingscreen
1. Download my template _loading_ from this git and put all contents on your webserver. Replace all previous files.
2. Open index.php and set your MySQL credentials.
3. Try to open yourcoolip.dyndns.org. However it should throw an error, because we are not fully done yet. Check again after:

## 4. Creating views for detailed evaluation
At the moment, we just track stupid events in game. Use all following SQL-prompts on database-level in your phpmyadmin to create views. Views are virtual tables. These views are used by the loadingscreen-website and are handeled like normal tables.
```
DROP VIEW IF EXISTS view_ttt_wins_total;
CREATE VIEW view_ttt_wins_total AS SELECT *, 
(A.wins_total / A.rounds_total) AS winrate_total,
(A.rounds_survived_total / A.rounds_total) AS surviverate_total
FROM
    (SELECT
    player,
    COUNT(*) as rounds_total,
    SUM(rated_win) as wins_total,
    SUM(survived) as rounds_survived_total
    FROM `ttt_wins`
    GROUP BY player) AS A;

DROP VIEW IF EXISTS view_ttt_wins_per_role;
CREATE VIEW view_ttt_wins_role AS SELECT * ,
(A.wins_per_role / A.rounds_per_role) AS winrate_per_role,
(A.rounds_survived_per_role / A.rounds_per_role) AS surviverate_per_role
FROM
    (SELECT
    player,
    player_subrole,
    COUNT(*) as rounds_per_role,
    SUM(rated_win) as wins_per_role,
    SUM(survived) as rounds_survived_per_role
    FROM `ttt_wins`
    GROUP BY player, player_subrole) AS A;

DROP VIEW IF EXISTS view_ttt_maps_count;
CREATE VIEW view_ttt_maps_count AS
SELECT mapname,
COUNT(*) AS rounds_played
from ttt_rounds
GROUP BY mapname

DROP VIEW IF EXISTS view_ttt_loading;
CREATE VIEW view_ttt_loading AS

SELECT

ttt_stats.steamid,
ttt_stats.nickname,
ttt_stats.maxscore,
ttt_stats.minscore,
ttt_stats.salt,
ttt_stats.last_seen,
view_ttt_wins_total.rounds_total,
view_ttt_wins_total.winrate_total,
view_ttt_kills_total.kills_total,
view_ttt_kills_total.kills_randomrate_total,
view_ttt_kills_total.kills_headshotrate_total,
view_ttt_deaths_total.deaths_total,
view_ttt_deaths_total.deaths_randomrate_total,
wI.winrate_innocent,
wT.winrate_traitor,
wD.winrate_detective

FROM

ttt_stats
LEFT JOIN view_ttt_wins_total ON view_ttt_wins_total.player = ttt_stats.steamid
LEFT JOIN view_ttt_kills_total ON view_ttt_kills_total.killer = ttt_stats.steamid
LEFT JOIN view_ttt_deaths_total ON view_ttt_deaths_total.victim = ttt_stats.steamid
LEFT JOIN ((SELECT player, winrate_per_role AS winrate_innocent FROM view_ttt_wins_role WHERE player_subrole = 'innocent') AS wI) ON wI.player = ttt_stats.steamid
LEFT JOIN ((SELECT player, winrate_per_role AS winrate_detective FROM view_ttt_wins_role WHERE player_subrole = 'detective') AS wD) ON wD.player = ttt_stats.steamid
LEFT JOIN ((SELECT player, winrate_per_role AS winrate_traitor FROM view_ttt_wins_role WHERE player_subrole = 'traitor') AS wT) ON wT.player = ttt_stats.steamid

DROP VIEW IF EXISTS view_ttt_kills_total;
CREATE VIEW view_ttt_kills_total AS
SELECT *,
(A.kills_random_total / A.kills_total) as kills_randomrate_total,
(A.kills_headshots_total / A.kills_total) as kills_headshotrate_total,
(A.kills_headshots_random_total / A.kills_headshots_total) as kills_headshotrandomrate_total
FROM
(SELECT
    killer,
    COUNT(*) as kills_total,
    SUM(headshot) as kills_headshots_total,
	SUM(victim_team = killer_team AND victim <> killer) as kills_random_total,
	SUM(headshot AND victim_team = killer_team AND victim <> killer) as kills_headshots_random_total
    FROM `ttt_kills`
    WHERE #weapon LIKE "weapon_%" AND 
	killer LIKE "STEAM%" AND victim LIKE "STEAM%"
    GROUP BY killer) AS A;

DROP VIEW IF EXISTS view_ttt_kills_per_weapon;
CREATE VIEW view_ttt_kills_per_weapon AS
SELECT *,
(A.kills_random_per_weapon / A.kills_per_weapon) as kills_randomrate_per_weapon,
(A.kills_headshots_per_weapon / A.kills_per_weapon) as kills_headshotrate_per_weapon,
(A.kills_headshots_random_per_weapon / A.kills_headshots_per_weapon) as kills_headshotrandomrate_per_weapon
FROM
(SELECT
    killer,
    weapon,
    COUNT(*) as kills_per_weapon,
    SUM(headshot) as kills_headshots_per_weapon,
	SUM(victim_team = killer_team AND victim <> killer) as kills_random_per_weapon,
	SUM(headshot AND victim_team = killer_team AND victim <> killer) as kills_headshots_random_per_weapon
    FROM `ttt_kills`
    WHERE #weapon LIKE "weapon_%" AND 
	killer LIKE "STEAM%" AND victim LIKE "STEAM%"
    GROUP BY killer, weapon) AS A  

DROP VIEW IF EXISTS view_ttt_favorite_weapon;
CREATE VIEW view_ttt_favorite_weapon AS
SELECT
DISTINCT A.killer AS player,
    A.weapon AS weapon,
    MAX(A.kills_per_weapon) AS kills_weapon_fav,
   B.kills_total AS kills_total,
    ( MAX(A.kills_per_weapon) / B.kills_total ) AS kills_weapon_fav_rate
FROM

(SELECT
    DISTINCT killer,
    weapon,
    kills_per_weapon
    FROM view_ttt_kills_per_weapon
    GROUP BY killer, weapon
	ORDER BY kills_per_weapon DESC) AS A,
(SELECT
    killer,
    kills_total
    FROM view_ttt_kills_total
    GROUP BY killer) AS B

WHERE B.killer = A.killer

GROUP BY A.killer  
ORDER BY `kills_weapon_fav`  DESC

DROP VIEW IF EXISTS view_ttt_deaths_total;
CREATE VIEW view_ttt_deaths_total AS
SELECT *,
(A.deaths_random_total / A.deaths_total) as deaths_randomrate_total,
(A.deaths_headshots_total / A.deaths_total) as deaths_headshotrate_total,
(A.deaths_headshots_random_total / A.deaths_headshots_total) as deaths_headshotrandomrate_total
FROM
(SELECT
    victim,
    COUNT(*) as deaths_total,
    SUM(headshot) as deaths_headshots_total,
	SUM(victim_team = killer_team AND victim <> killer) as deaths_random_total,
	SUM(headshot AND victim_team = killer_team AND victim <> killer) as deaths_headshots_random_total
    FROM `ttt_kills`
    WHERE #weapon LIKE "weapon_%" AND 
	victim LIKE "STEAM%"
    GROUP BY victim) AS A 

DROP VIEW IF EXISTS view_ttt_deaths_per_weapon;
CREATE VIEW view_ttt_deaths_per_weapon AS
SELECT *,
(A.deaths_random_per_weapon / A.deaths_per_weapon) as deaths_randomrate_per_weapon,
(A.deaths_headshots_per_weapon / A.deaths_per_weapon) as deaths_headshotrate_per_weapon,
(A.deaths_headshots_random_per_weapon / A.deaths_headshots_per_weapon) as deaths_headshotrandomrate_per_weapon
FROM
(SELECT
    victim,
    weapon,
    COUNT(*) as deaths_per_weapon,
    SUM(headshot) as deaths_headshots_per_weapon,
	SUM(victim_team = killer_team AND victim <> killer) as deaths_random_per_weapon,
	SUM(headshot AND victim_team = killer_team AND victim <> killer) as deaths_headshots_random_per_weapon
    FROM `ttt_kills`
    WHERE #weapon LIKE "weapon_%" AND 
	victim LIKE "STEAM%"
    GROUP BY victim, weapon) AS A 
```

# HAVE FUN

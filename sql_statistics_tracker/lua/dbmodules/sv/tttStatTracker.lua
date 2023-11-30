local peopleToGeneralUpdate = {}

local startingMap = "gm_construct"
util.AddNetworkString( "Staty" )
util.AddNetworkString( "DatabaseWeaponUpdate" )

globalIdRound = 0

function GetTrainmode()
	local trainmode = GetConVar("ttt_trainmode")
	
	if trainmode:GetInt() == 1 and (game.GetMap() == "ttt_train_" or game.GetMap() == "ttt_modern_house") then
		return true
	end
	
	return false
end
		
local function Print( Message )
	MsgC( Color( 127, 255, 0 ), "TTTracker: " )
	print( Message )
end
 
local function dump(o)
   if type(o) == 'table' then
      local s = '{ '
      for k,v in pairs(o) do
         if type(k) ~= 'number' then k = '"'..k..'"' end
         s = s .. '['..k..'] = ' .. dump(v) .. ','
      end
      return s .. '} '
   else
      return tostring(o)
   end
end

--[[
function ShortenId(id)
	tempId = id
	tempId = string.gsub(tempId, '%:', '')
	tempId = string.sub(tempId, 8)
	return tempId
end ]]

local function EscapeItem(item)
	tempItem = item
	tempItem = string.gsub(tempItem, ' ', '_')
	tempItem = tempItem:lower()
	return tempItem
end

local function ExecuteQuery( queryString )
	local tquery = db:query ( queryString )
	tquery.onSuccess = function(q)
	end

	tquery.onError = function(q,e)
		Print ( "Query Failed: " .. queryString )
		notifyerror(e)
	end
	
	tquery:start()
end

local function GetPlayTime(ply)
	ply.tracker_playtime = (ply.tracker_playtime + (CurTime() - math.Round(ply.timeCheck or CurTime())));
	ply.timeCheck = CurTime();

	return ply.tracker_playtime
end

local function GetSalz(ply)	--berechne Salzgehalt (ply.tracker_salt)
	-- print(ply:Nick() )
	-- print(ply:Frags() ) -- frags sind punkte für komplette runde

	--conversion
	if ply:Frags() > ply.tracker_maxscore then
		ply.tracker_maxscore = ply:Frags()
	elseif ply:Frags() < ply.tracker_minscore then
		ply.tracker_minscore = ply:Frags()
	end
		
	ply.tracker_salt = ply.tracker_salt / 1000
	
	ply.tracker_points_this_round = ply:Frags() - ( tonumber(ply.tracker_points_prevround) ) --score diese runde
	
	k = 0.04
	
	ply.tracker_salt = (ply.tracker_salt + ( k ) * (ply.tracker_points_this_round - ply.tracker_salt))
	ply.tracker_points_prevround = ply:Frags();

	--convert back
	ply.tracker_salt = ply.tracker_salt * 1000
	return ply
end

local function getTimestamp()
	local ts = tostring( util.DateStamp() )
	ts = string.reverse(ts) --reverse
	ts = string.gsub(ts, "-", ":", 2)
	ts = string.reverse(ts)
	return ts
end


function StatsTrackerGetRoundId()
	local tquery2Load = db:query( "SELECT MAX(id) AS id FROM ttt_rounds")
	tquery2Load.onSuccess = function(q, sdata) 	-- function (query, selectedData)
		local row = sdata[1];					-- write first line of selectedData to row
		if (#tquery2Load:getData() == 1) then	-- if query result is not empty
			if (row['id']) ~= nil then	
				globalIdRound = tonumber( row['id'] ) + 1
			else
				globalIdRound = 0
			end
		end
	end

	tquery2Load.onError = function(q,e)
		notifymessage("TTTracker: Couldn't get Round-Id")
		notifyerror(e)
	end
	tquery2Load:start()
end

function CheckForRatedRound( val )	
	if val == 0 and (game.GetMap() == startingMap or GetConVar( "ttt_testing" ):GetInt() ~= 0 ) then
		Print( "TESTMODE ENABLED" )
		return false
	end
	if val == 1 and (game.GetMap() == startingMap or GetConVar( "ttt_testing" ):GetInt() ~= 0 or GetTrainmode() ) then 
		Print( "TRAINMODE ENABLED" )
		return fals
	end
	if val == 2 and (game.GetMap() == startingMap or GetConVar( "ttt_testing" ):GetInt() ~= 0 or GetTrainmode() or GAMEMODE.round_state ~= ROUND_ACTIVE ) then 
		-- Print( "ROUND NOT ACTIVE ENABLED" )
		return false
	end

	return true
end

function StatsTrackerLoadGeneralPlyStats( ply )
	--runs after each map change > resets points prevround

	--Comment after first init
	ExecuteQuery( "CREATE TABLE IF NOT EXISTS ttt_rounds ( id int PRIMARY KEY auto_increment, time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, mapname text );")
	ExecuteQuery( "CREATE TABLE IF NOT EXISTS ttt_kills( id int PRIMARY KEY auto_increment, time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, id_round int, killer text, killer_role text, killer_subrole text, killer_team text, victim text, victim_role text, victim_subrole text, victim_team text, weapon text, headshot int NULL DEFAULT '0', hitgroup text);" )
	ExecuteQuery( "CREATE TABLE IF NOT EXISTS ttt_wins( id int PRIMARY KEY auto_increment, time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, id_round int, player text, player_role text, player_subrole text, player_team text, winning_team text, rated_win int, survived int, points_this_round int NULL DEFAULT '0', salt int NULL DEFAULT '0');" )
	ExecuteQuery( "CREATE TABLE IF NOT EXISTS ttt_stats( id int PRIMARY KEY auto_increment, steamid text, nickname text, playtime int NULL DEFAULT '0', first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP, last_seen text, salt int NULL DEFAULT '0', maxscore int NULL DEFAULT '0', minscore int NULL DEFAULT '0');")
	ExecuteQuery( "CREATE TABLE IF NOT EXISTS ttt_orders ( id int PRIMARY KEY auto_increment, time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, id_round int, player text, weapon text, player_role text, player_subrole text, player_team text );")
	ExecuteQuery( "CREATE TABLE IF NOT EXISTS ttt_traitorbutton ( id int PRIMARY KEY auto_increment, time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, id_round int, player text );")
	ExecuteQuery( "CREATE TABLE IF NOT EXISTS ttt_c4disarm ( id int PRIMARY KEY auto_increment, time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, id_round int, player text, result int );")
	ExecuteQuery( "CREATE TABLE IF NOT EXISTS ttt_weapons ( id int PRIMARY KEY auto_increment, classname text, printname text );")

	if not IsValid( ply ) then return end
	ply.tracker_ready = false
	if not ply:IsPlayer() then return end
	if ply:IsBot() then return end

	if not ServerStatsDB.connected then
		timer.Simple( 10, function()
			StatsTrackerLoadGeneralPlyStats(ply)
		end)
		return
	end

	ply.tracker_playtime = 0
	ply.tracker_points_prevround = 0
	ply.tracker_salt = 0
	ply.tracker_points_this_round = 0
	ply.tracker_ready = true
	ply.tracker_maxscore = 0;
	ply.tracker_minscore = 0;
	ply.escape_name = db:escape( ply:Nick() )
	
	local tquery1Load = db:query( "SELECT * FROM ttt_stats WHERE steamid = '" .. ply:SteamID() .. "'")
	tquery1Load.onSuccess = function(q)
		if not checkQuery(q) then -- wenn nicht existiert --checkQuery q: prüfe ob Eintrag Reihe existiert
			-- create entry in generaltats (ttt_stats) for ply
			local queryrows =  "steamid, nickname, playtime, last_seen, salt, minscore, maxscore";
			local queryvalues ="'" .. ply:SteamID() .. "', '"  .. ply.escape_name .. "', '0', '0', '0', '0', '0'";
			local tquery2Load = db:query("INSERT INTO ttt_stats(" .. queryrows .. ") VALUES (" .. queryvalues .. ")")

			tquery2Load.onSuccess = function(q)

				Print ( "TTTracker: GeneralStats Created: " .. ply:Nick() )

			end

			tquery2Load.onError = function(q,e)
				notifymessage("TTTracker: Failed to Creat GeneralStats for: " .. ply:Nick())
				notifyerror(e)
			end
			tquery2Load:start()
		else
			-- wenn existiert: lade generalstats für ply
			Print ( "TTTracker: GeneralStats Loading: " .. ply:Nick() )
			local tquery3Load = db:query( "SELECT * FROM ttt_stats WHERE steamid = '" .. ply:SteamID() .. "'")

			tquery3Load.onSuccess = function(q, sdata) 	-- function (query, selectedData)
			local row = sdata[1];					-- write first line of selectedData to row
				if (#tquery3Load:getData() == 1) then	-- if query result is not empty
					ply.tracker_playtime = row['playtime'];
					ply.tracker_salt = row['salt'];
					ply.tracker_maxscore = row['maxscore'];
					ply.tracker_minscore = row['minscore'];
					ply.tracker_timeCheck = CurTime();
					ply.tracker_ready = true;
				end
			end

			tquery3Load.onError = function(q,e)
				notifymessage("TTTracker: Couldn't Load GeneralData for: " .. ply:Nick())
				notifyerror(e)
			end
			tquery3Load:start()
		end
	end
	tquery1Load.onError = function(q,e)
		notifymessage("TTTracker: Couldn't select GeneralData for: " .. ply:Nick())
		notifyerror(e)
	end
	tquery1Load:start()
end

function StatsTrackerLogKill( victim, killer, damageInfo )
	if not CheckForRatedRound( 2 ) then return end
	if not IsValid(victim) then Print("Victim invalid") return end
	if not victim:IsPlayer() then Print("Victim no Player") return end
	if victim:IsBot() then Print("Victim is Bot") return end
	if not victim.tracker_ready then Print("Victim not tracker_ready " .. victim:Nick() ) return end

	--Der Angreifer
	local victim_ = victim:SteamID()
	local victimrole_ = roles.GetByIndex( victim:GetRole() ).name 
	local victimsubrole_ = roles.GetByIndex( victim:GetSubRole() ).name
	local victimteam_ = victim:GetTeam()
	local killer_ = "none"
	local killerrole_ = "none"
	local killersubrole_ = "none"
	local killerteam_ = "none"
	local weapon = "none"
	-- local weapon = inflicitor:GetClass()
	local hitgroup = 0
	local headshot = 0
	
	--Die Waffe/Verursacher
	if damageInfo:IsBulletDamage() and IsValid(damageInfo:GetAttacker()) and IsValid(damageInfo:GetAttacker():GetActiveWeapon()) and IsValid(damageInfo:GetAttacker():GetActiveWeapon():GetClass()) then
		weapon = damageInfo:GetAttacker():GetActiveWeapon():GetClass()
		--OK			
	elseif ( not killer:IsPlayer() ) and damageInfo:IsFallDamage() then
		weapon = "falldamage"
	else
		if IsValid( damageInfo:GetInflictor() ) then
			weapon = damageInfo:GetInflictor():GetClass()
		else
			weapon = "world"
		end
	end
	
	if IsValid(killer) and killer:IsPlayer() then
		if killer == victim and weapon == "player" then
			-- return Print("Durch Admin ausgeschaltet")
			killer_ = "world"
			weapon = "admin" --durch world mit admin	
			--elseif killer == victim and weapon ~= "player" then --selbst gekillt
				--case = "self"
		else
			killer_ = killer:SteamID()
			killerrole_ = roles.GetByIndex( killer:GetRole() ).name
			killersubrole_ = roles.GetByIndex( killer:GetSubRole() ).name
			killerteam_ = killer:GetTeam()
		end
	else
		killer_ = "world"
	end
	
	hitgroup = tostring( victim:LastHitGroup() )
	if victim:LastHitGroup() == HITGROUP_HEAD then
		headshot = 1
	end
	-- Print("log Kill")
	-- Print(killer_)
	-- Print(killerrole_)
	-- Print(killersubrole_)
	-- Print(killerteam_)
	-- Print(victim_)
	-- Print(victimrole_)
	-- Print(victimsubrole_)
	-- Print(victimteam_)
	-- Print(weapon)
	-- Print(headshot)

	queryInsertStringKill = "INSERT INTO ttt_kills (killer, killer_role, killer_subrole, killer_team, victim, victim_role, victim_subrole, victim_team, weapon, headshot, hitgroup, id_round) VALUES ('" .. killer_ .. "', '"  .. killerrole_ .. "', '"  .. killersubrole_ .. "', '"  .. killerteam_ .. "','" .. victim_ .. "', '"  .. victimrole_.. "', '"  .. victimsubrole_ .. "', '"  .. victimteam_ .. "', '"  .. weapon .. "', '"  .. tonumber(headshot) .. "', '"  .. hitgroup .. "', '"  .. tonumber(globalIdRound) .. "')";
	ExecuteQuery( queryInsertStringKill )
end

function StatsTrackerEndRoundMap( result )
	if not CheckForRatedRound(0) then return end
	ExecuteQuery( "INSERT INTO ttt_rounds (mapname) VALUES ('" .. game.GetMap().. "')" )
end

function StatsTrackerEndRoundWins(result)
	-- if not CheckForRatedRound( 1 ) then return end
	for _, ply in pairs(player.GetAll()) do
		if ply.tracker_ready and ( ply:ShouldSpawn() ) and not ply:IsBot() then --ttt2/libraries/credits.lua --sv_player_ext
			local role = "none"
			local subrole = "none"
			local team = "none"
			local rated_win = 0
			local survived = 0
			
			if ply:HasRole() then
				role = roles.GetByIndex( ply:GetRole() ).name
				subrole = roles.GetByIndex( ply:GetSubRole() ).name
				team = ply:GetTeam()
			end
			
			if result == ply:GetTeam() then
				rated_win = 1
			end
			
			if ply:Alive() then
				survived = 1
			end
			
			ply = GetSalz(ply)
			
			queryInsertString = "INSERT INTO ttt_wins (player, player_role, player_subrole, player_team, winning_team, rated_win, survived, points_this_round, salt, id_round) VALUES ('" .. ply:SteamID() .. "', '"  .. role .. "', '"  .. subrole .. "', '"  .. team .. "','" .. result .. "', '"  .. tonumber(rated_win) .. "', '"  .. tonumber(survived) .. "', '"  .. tonumber(ply.tracker_points_this_round) .. "', '"  .. tonumber(ply.tracker_salt) .. "', '"  .. tonumber(globalIdRound) .. "')";

			ExecuteQuery( queryInsertString )
			
			StatsTrackerSaveGeneralPlyStats(ply)
		end	
	end
end

function StatsTrackerSaveGeneralPlyStats(ply)
	if not CheckForRatedRound( 1 ) then return end
	if not ply.tracker_ready then return end
	if not IsValid(ply) then return end
	ExecuteQuery( "UPDATE ttt_stats SET nickname= '" .. ply.escape_name .. "', playtime='"..tonumber(GetPlayTime(ply)).."',maxscore='"..tonumber(ply.tracker_maxscore).."',minscore='"..tonumber(ply.tracker_minscore).."', salt='"..ply.tracker_salt.."', last_seen='".. tostring( getTimestamp() ).."' WHERE steamid ='"..ply:SteamID().."'" )
end

function StatsTrackerOrderedEquipment(ply, class, isItem, credits, ignoreCost)
	if not CheckForRatedRound( 2 ) then return end
	queryInsertStringKill = "INSERT INTO ttt_orders (player, weapon, player_role, player_subrole, player_team, id_round) VALUES ('" .. ply:SteamID() .. "', '"  .. class .. "', '"..  roles.GetByIndex( ply:GetRole() ).name .."', '"..  roles.GetByIndex( ply:GetSubRole() ).name .."', '"..  ply:GetTeam() .."', '"  .. tonumber(globalIdRound) .. "')"
	ExecuteQuery( queryInsertStringKill )
end

function StatsTrackerTraitorButtonActivated(ent, ply)
	if not CheckForRatedRound( 2 ) then return end
	queryInsertStringKill = "INSERT INTO ttt_traitorbutton (player, id_round) VALUES ('" .. ply:SteamID() .. "', '" .. tonumber( globalIdRound ) .. "')"
	ExecuteQuery( queryInsertStringKill )
end

function StatsTrackerC4Disarm(bomb, result, player)
	if not CheckForRatedRound( 2 ) then return end
	ExecuteQuery( "INSERT INTO ttt_c4disarm (player, result, id_round ) VALUES ('" .. ply:SteamID() .. "', '" .. tonumber(result) .. "', '"  .. tonumber(globalIdRound) .. "');")
end

hook.Add( "PlayerInitialSpawn", "StatsTrackerPlayerComes", StatsTrackerLoadGeneralPlyStats )
hook.Add( "TTTEndRound", "StatsTrackerEndRoundMap", StatsTrackerEndRoundMap)
hook.Add( "TTTEndRound", "StatsTrackerEndRoundWins", StatsTrackerEndRoundWins)
-- hook.Add( "TTTEndRound", "StatsTrackerEndRoundPlyStats", StatsTrackerSaveGeneralPlyStats)


hook.Add("TTTPrepareRound","StatsTrackerPrepareRound",StatsTrackerGetRoundId)
hook.Add("TTTBeginRound","StatsTrackerBeginRound",StatsTrackerGetRoundId) --just to be safe

hook.Add( "PlayerDisconnected", "StatsTrackerPlayerGoes", function(ply)
	if ply:IsBot() then return end
	ply = GetSalz(ply)
	StatsTrackerSaveGeneralPlyStats(ply)
end)
hook.Add( "DoPlayerDeath", "StatsTrackerLogKill", StatsTrackerLogKill )
--hook.Add( "TTT2PostPlayerDeath", "StatsTrackerLogKillPostDeath", StatsTrackerLogKill2)

hook.Add( "TTTOrderedEquipment", "StatsTrackerOrder", StatsTrackerOrderedEquipment )
hook.Add( "TTTTraitorButtonActivated", "StatsTrackerTraitorButtonActivated", StatsTrackerTraitorButtonActivated )
hook.Add( "TTTC4Disarm", "StatsTrackerC4Disarm", StatsTrackerC4Disarm )

-- hook.Add("ScalePlayerDamage", "TTTrackerDeathState.Headshot", function(ply, hitGroup)
--     ply.lastHitGroup = hitGroup -- Abfrage über Deathstat
-- end)
--[[
local function DBLoggingKillHook(ply, attacker, dmgInfo)
	if GetRoundState() ~= ROUND_ACTIVE then return end
	if GetConVar( "ttt_testing" ):GetInt()  == 1 then return end
	if game.GetMap() == startingMap then return end
	
	events.Trigger(EVENT_DBLOGGING_KILL, ply, attacker, dmgInfo)
end]]

--https://github.com/Facepunch/garrysmod/blob/master/garrysmod/gamemodes/terrortown/gamemode/player.lua

if SERVER then
	net.Receive( "DatabaseWeaponUpdate", function( len, ply )
		Print("Update Weapons")
		
		local bytes_amount = net.ReadUInt( 16 ) -- Gets back the amount of bytes our data has
		local compressed_message = net.ReadData( bytes_amount ) -- Gets back our compressed message
		local message = util.Decompress( compressed_message ) -- Decompresses our message
		local newWeps = util.JSONToTable(message)
		
		file.Write( "weapons.json", newWeps )
		
		for k, v in ipairs(newWeps) do
			local pn = db:escape(v["PrintName"])
			local cn = db:escape(v["ClassName"])
			ExecuteQuery( "INSERT INTO ttt_weapons (classname, printname) SELECT '" .. cn .. "', '" .. pn .. "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM ttt_weapons  WHERE classname = '" .. cn .. "' LIMIT 1)" )
			-- queryUpdateString = "UPDATE " .. tableName .. " SET " .. columnName2 .. " = " .. columnName2 .. " + 1 WHERE( " .. columnName1.. "='" .. map .. "');"
		end
	end)
end
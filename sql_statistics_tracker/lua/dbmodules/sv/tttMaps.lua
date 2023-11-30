local function ExecuteQuery( queryString )
	local tquery = db:query ( queryString )
	tquery.onSuccess = function(q)
	end

	tquery.onError = function(q,e)
		notifymessage("TTTMaps failed: " .. queryString)
		notifyerror(e)
	end
	
	tquery:start()
end

function DatabaseMapUpdate()
	print("Mapupdate")
	
	local status = db:status();
    if (status == STATUS_WORKING or status == STATUS_READY) then

		ExecuteQuery( "CREATE TABLE IF NOT EXISTS ttt_maps ( id int PRIMARY KEY auto_increment, mapname text, size text, description text, rating text, last_played int NOT NULL DEFAULT '0');")
		
		local maps = file.Find("maps/*.bsp", "GAME")
		-- print(#maps)
		for k, map in ipairs(maps) do
			-- print(map)
			local mapstr = map:sub(1, -5):lower()
			if (string.find(mapstr, "ttt_")) then 
				local query = "INSERT INTO ttt_maps (mapname) SELECT '" .. mapstr .. "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM ttt_maps  WHERE mapname = '" .. mapstr .. "' LIMIT 1)"
				-- print(query)
				ExecuteQuery( query )
			end
		end
		
		DatabaseMapsRead()
	else
		timer.Create("MapupdateCheck", 15, 0, DatabaseMapUpdate)
	end
end

globListTTTMaps = {}

function TransferOldMapsTXTtoSQL()
	for i = 1, #globListTTTMaps do
		local map = globListTTTMaps[i]["mapname"]
		if file.Exists( "mapvote/maps/" .. map .. "_desc.txt", "DATA" ) then
			local desc = file.Read("mapvote/maps/" .. map .. "_desc.txt", "DATA")
			if desc ~= "x" then
				DatabaseMapsEdit("Description", map, desc)
			end
			file.Delete( "mapvote/maps/" .. map .. "_desc.txt" )
		end
		if file.Exists( "mapvote/maps/" .. map .. "_lastplayed.txt", "DATA" ) then
			local last_played = file.Read("mapvote/maps/" .. map .. "_lastplayed.txt", "DATA")
			if last_played ~= "x" then
				DatabaseMapsEdit("LastPlayed", map, last_played)
			end
			file.Delete( "mapvote/maps/" .. map .. "_lastplayed.txt" )
		end
		if file.Exists( "mapvote/maps/" .. map .. "_size.txt", "DATA" ) then
			local size = file.Read("mapvote/maps/" .. map .. "_size.txt", "DATA")
			if size ~= "x" then
				DatabaseMapsEdit("Size", map, size)
			end
			file.Delete( "mapvote/maps/" .. map .. "_size.txt" )
		end
	end	
end

function SetGlobListTTTMaps( tbl )
	--to sync, else globListTTTMAps stays empty
	globListTTTMaps = tbl
	
	TransferOldMapsTXTtoSQL()
end

function DatabaseMapsRead()
	local tbl = {} --empty everything
	local tquery1Load = db:query( "SELECT * FROM ttt_maps WHERE mapname LIKE 'ttt%' ORDER BY last_played ASC")
	tquery1Load.onSuccess = function(q, sdata) 	-- function (query, selectedData)
		for k, dat in ipairs(sdata) do
			
			tbl[ #tbl + 1 ] = {
				mapname = tostring( dat["mapname"] ),
				size = tostring( dat["size"] ),
				description = tostring( dat["description"] ),
				rating = tostring( dat["rating"] ),
				last_played = tonumber( dat["last_played"] ),
			}
			--[[
			{
				mapname A
				size S
				...
			},
			{
				mapname B
				size L
				...
			}
			]]
			
		end
		
		SetGlobListTTTMaps( tbl )
	end

	tquery1Load.onError = function(q,e)
		notifymessage("TTTMaps: Couldn't read maps")
		notifyerror(e)
	end
	tquery1Load:start()
end

function DatabaseMapsEdit(case, map, data)
	if case == "LastPlayed" then
		ExecuteQuery("UPDATE ttt_maps SET last_played = '" .. tonumber(data) .. "' WHERE (mapname = '" .. map .. "') ")
	elseif case == "Rating" then
		ExecuteQuery("UPDATE ttt_maps SET rating = '" .. tostring(data) .. "' WHERE (mapname = '" .. map .. "') ")
	elseif case == "Description" then
		ExecuteQuery("UPDATE ttt_maps SET description = '" .. tostring(data) .. "' WHERE (mapname = '" .. map .. "') ")
	elseif case == "Size" then
		ExecuteQuery("UPDATE ttt_maps SET size = '" .. tostring(data) .. "' WHERE (mapname = '" .. map .. "') ")
	end
	DatabaseMapsRead()
end

hook.Add( "Initialize", "HookDatabaseMapUpdate", DatabaseMapUpdate ); 
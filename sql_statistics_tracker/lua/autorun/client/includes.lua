-- include( "dbmodules/client/cl_showstats.lua")
-- include( "dbmodules/client/cl_dermastuff.lua" )



concommand.Add("updateweapons", function(ply, cmd, args)
	local weps = weapons.GetList()
	for k,v in ipairs(weps) do
		v["PrintName"] = language.GetPhrase( v["PrintName"] )
	end
	
	local json = util.TableToJSON( weps )
	local comp = util.Compress(json)
	local bytes = #comp

	net.Start( "DatabaseWeaponUpdate" )
		net.WriteUInt( bytes, 16 ) -- Writes the amount of bytes we have. Needed to read the data
		net.WriteData( comp, bytes ) -- Writes the datas
	net.SendToServer()
end) 
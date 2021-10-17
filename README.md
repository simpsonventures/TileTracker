# TileTracker
PHP Class for Tile Bluetooth Trackers

<b>Create new user session with Tile:</b><br>
$TileTracker = new TileTracker(USER, PASS);
<br><br>
<b>Requests via TileRequest Method:</b><br>
$TileTracker->TileRequest();
<br><br>
<b>Known Requests:</b><br>
/tiles/tile_states - list of tile's active on account<br>
/tiles?tile_uuids= - particular tile information via UUID
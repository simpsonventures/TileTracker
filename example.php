<?php

include('TileTracker.php');

// Define Login Variables
$USER = 'admin@email.com';
$PASS = 'password123';

$TileTracker = new TileTracker($USER, $PASS);

$user_tiles = $TileTracker->TileRequest('/tiles/tile_states');
$user_tiles_decoded = json_decode($user_tiles, true);

$tiles = array();
foreach ($user_tiles_decoded['result'] as $tile_id) {
    $tile_decoded = json_decode($TileTracker->TileRequest('/tiles?tile_uuids=' . $tile_id['tile_id']), true);
    $tiles[$tile_id['tile_id']]['full_data'] = $tile_decoded;
    $tiles[$tile_id['tile_id']]['uuid'] = preg_replace("/(\W)+/", "", $tile_id['tile_id']);
    $tiles[$tile_id['tile_id']]['timestamp'] = $tile_decoded['result'][$tile_id['tile_id']]['last_tile_state']['timestamp'];
    $tiles[$tile_id['tile_id']]['name'] = $tile_decoded['result'][$tile_id['tile_id']]['name'];
    $tiles[$tile_id['tile_id']]['lon'] = $tile_decoded['result'][$tile_id['tile_id']]['last_tile_state']['latitude'];
    $tiles[$tile_id['tile_id']]['lat'] = $tile_decoded['result'][$tile_id['tile_id']]['last_tile_state']['longitude'];

    $firstKey = array_key_first($tiles);
}

?>

<!DOCTYPE html>
<html lang="">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>OpenMap Tile</title>

    <script src="node_modules/jquery/dist/jquery.slim.js"></script>

    <link rel="stylesheet" href="node_modules/leaflet/dist/leaflet.css"/>
    <script src="node_modules/leaflet/dist/leaflet.js"></script>

    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css"/>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

    <style media="screen">

        body {
            padding: 0;
            margin: 0;
        }

        html, body, #map {
            height: 80vh;
        }

        .container {
            height: 100vh;
            overflow: auto;
        }

        #content {
            height: 80vh;
            overflow: auto;
        }

        #footer {
            height: 5vh;
        }

        #header {
            height: 10vh;
        }

    </style>
</head>

<body>

<div class="container">

    <div class="row" id="header">
        <div class="col-sm-12">

            <nav class="navbar navbar-light bg-light">
                <span class="navbar-brand mb-0 h1">Tile Tracker</span>
            </nav>

        </div>
    </div>

    <div class="row" id="content">
        <div class="col-sm-4">
            <ul class="list-group">
                <?php
                foreach ($tiles as $tile) {
                    echo "<li class=\"list-group-item\">
<a href=\"#\" id=\"_" . $tile['uuid'] . "\">" . $tile['name'] . "</a><br>
<small>UUID: " . $tile['uuid'] . "</small><br>
<small>Updated " . date("d-m-Y H:i:s", ($tile['timestamp'] / 1000)) . "</small>
</li>";
                }
                ?>
            </ul>
        </div>
        <div class="col-sm-8">
            <div id="map"></div>
        </div>
    </div>

    <div class="row" id="footer">
        <div class="col-sm-12 text-center align-middle"><br><?= "Session expiry in " . round(($TileTracker->USER_UUID_EXPIRE - (time() * 1000)) / 1000 / 60) . " mins<br><br>" ?></div>
    </div>

</div>

<script>

    let map = L.map('map').setView([<?=$tiles[$firstKey]['lon']?>, <?=$tiles[$firstKey]['lat']?>], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    function markerFunction(id) {
        for (let i in markers) {
            let markerID = markers[i].options.title;
            if (markerID === id) {
                let latLngs = [markers[i].getLatLng()];
                let markerBounds = L.latLngBounds(latLngs);
                map.fitBounds(markerBounds);
                markers[i].openPopup();
            }
        }
    }

    let markers = [];
    let latlngs = [];

    <?php
    foreach ($tiles as $tile) {
        echo "var _" . $tile['uuid'] . " = L.marker([" . $tile['lon'] . ", " . $tile['lat'] . "],{title:'" . $tile['name'] . "'}).addTo(map).bindPopup('" . $tile['name'] . "');";
        echo "markers.push(_" . $tile['uuid'] . ");";
        echo "var latlng_" . $tile['uuid'] . " = [" . $tile['lon'] . ", " . $tile['lat'] . "];";
        echo "latlngs.push(latlng_" . $tile['uuid'] . ");";
    }
    ?>

    let bounds = new L.LatLngBounds(latlngs);
    map.fitBounds(bounds, {padding: [50, 50]});

    $("a").click(function () {
        markerFunction($(this)[0].id);
    });

</script>
</body>
</html>

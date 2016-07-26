<?php
// Version: 0.1.2
// Start editing here!
// ----------------------

// Connection data for the mysql database
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "databasename";

// Google Maps API-Key
$gmaps_api_key = "key";

// ----------------------
// Stop editing here!

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET["pokemonid"])) {

    // get the spawnpoints of the pokemon
    // prepare and bind
    $stmt = $conn->prepare("SELECT DISTINCT spawnpoint_id FROM pokemon WHERE pokemon_id=? AND disappear_time > DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->bind_param("i", $pokemonid);

    // set parameters and execute and bind results
    $pokemonid = $_GET["pokemonid"];
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($spawnpointid);

    // store results in an array
    $spawnpointsarray = array();
    while ($stmt->fetch()) {
        array_push($spawnpointsarray, $spawnpointid);
    }

    // free results
    $stmt->free_result();

    // close statement
    $stmt->close();

    // get the coordinates of all the pokemon spawnpoints and save them in an array
    $coordinatesarray = array();
    $stmt = $conn->prepare("SELECT latitude, longitude FROM pokemon WHERE spawnpoint_id=? LIMIT 1");
    $stmt->bind_param("s", $spawnpoint);
    foreach ($spawnpointsarray as $spawnpoint) {
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($latitude, $longitude);
        while ($stmt->fetch()) {
            $coordinatesarray[$spawnpoint] = array("latitude" => $latitude, "longitude" => $longitude);
        }
        $stmt->free_result();
    }

    // close statement
    $stmt->close();

    // calculate the center of the map
    if (count($coordinatesarray) > 0) {
        $sumoflat = 0;
        $sumoflong = 0;
        foreach ($coordinatesarray as $coord) {
            $sumoflat += $coord["latitude"];
            $sumoflong += $coord["longitude"];
        }
        $centerlat = $sumoflat / count($coordinatesarray);
        $centerlong = $sumoflong / count($coordinatesarray);
    } else {
        $centerlat = 0;
        $centerlong = 0;
    }

    // get the spawntimes for the pokemon of all spawnpoints and save them in an array
    $spawntimearray = array();
    $stmt = $conn->prepare("SELECT DISTINCT DATE_FORMAT(SUBTIME(disappear_time, '00:15:00'), 'Minute: %i, Seconds: %s') FROM pokemon WHERE spawnpoint_id=? AND disappear_time > DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->bind_param("s", $spawnpoint);
    foreach ($spawnpointsarray as $spawnpoint) {
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($spawntime);
        $spawntimearray[$spawnpoint] = array();
        while ($stmt->fetch()) {
            array_push($spawntimearray[$spawnpoint], $spawntime);
        }
        $stmt->free_result();
    }

    // close statement
    $stmt->close();

    // get the frequency of pokemon spawns at the spawnpoints and save them in an array
    $frequencyarray = array();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pokemon WHERE spawnpoint_id=? AND disappear_time > DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->bind_param("s", $spawnpoint);
    $stmt2 = $conn->prepare("SELECT COUNT(*) FROM pokemon WHERE spawnpoint_id=? AND pokemon_id=? AND disappear_time > DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt2->bind_param("si", $spawnpoint, $pokemonid);

    foreach ($spawnpointsarray as $spawnpoint) {
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($allspawns);
        $stmt2->execute();
        $stmt2->store_result();
        $stmt2->bind_result($spawnswithpokemon);
        $stmt->fetch();
        $stmt2->fetch();
        $frequencyarray[$spawnpoint] = array("allspawns" => $allspawns, "spawnswithpokemon" => $spawnswithpokemon);
        $stmt->free_result();
        $stmt2->free_result();
    }

    // close statements
    $stmt->close();
    $stmt2->close();

}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Pokemon-Spots Finder</title>

    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0px;
        }
    </style>

</head>

<body>
<form method="get">
    <input type="number" min="1" name="pokemonid" placeholder="<?php if (isset($_GET["pokemonid"])) echo $_GET["pokemonid"]; else echo "ID of the pokemon"; ?>" style="display: block; margin : 0 auto;">
    <button type="submit" style="display: block; margin : 0 auto;">Send it!</button>
</form>
<div id="map" style="height: calc(100% - 42px); width: auto;"></div>

<script>

    // init the map on which everything gets marked
    var map;
    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            center: {lat: <?php if (isset($centerlat)) echo $centerlat; else echo 0; ?>, lng: <?php if (isset($centerlong)) echo $centerlong; else echo 0; ?>},
            zoom: 14
        });

        <?php
        if (isset($spawnpointsarray) && isset($coordinatesarray) && isset($spawntimearray) && isset($frequencyarray)) {
            foreach ($spawnpointsarray as $spawnpoint) {
                if (isset($coordinatesarray[$spawnpoint]) && isset($frequencyarray[$spawnpoint]) && isset($spawntimearray[$spawnpoint])) {

                    // add a marker
                    echo "
                    var marker" . $spawnpoint . " = new google.maps.Marker({
                    position: {lat: " . $coordinatesarray[$spawnpoint]["latitude"] . ", lng: " . $coordinatesarray[$spawnpoint]["longitude"] . "},
                    map: map
                    });";

                    // fill the content for the info window of the marker
                    $contentstring = "";
                    foreach ($spawntimearray[$spawnpoint] as $spawntime) {
                        $contentstring = $contentstring . "<b>Spawntime</b> - " . $spawntime . "<br>";
                    }

                    $contentstring = $contentstring . "<b>Frequency</b> - All counted: " . $frequencyarray[$spawnpoint]["allspawns"] . ", Spawns with pokemon: " . $frequencyarray[$spawnpoint]["spawnswithpokemon"] . ", In percent: " . $frequencyarray[$spawnpoint]["spawnswithpokemon"] / $frequencyarray[$spawnpoint]["allspawns"] * 100 . "<br>";

                    // add the info window
                    echo "
                    var infowindow" . $spawnpoint . " = new google.maps.InfoWindow({
                    content: '" . $contentstring . "'
                    });
                
                    marker" . $spawnpoint . ".addListener('click', function() {
                    infowindow" . $spawnpoint . ".open(map, marker" . $spawnpoint . ");
                    });";

                    // yellow marker if there have been less than 10 pokemon seen at this spawn
                    if ($frequencyarray[$spawnpoint]["allspawns"] < 10) {
                        echo "marker" . $spawnpoint . ".setIcon('http://maps.google.com/mapfiles/ms/icons/yellow-dot.png');";
                    }
                    // green marker if there are more than 10 percent of the spawns the pokemon we are looking for
                    elseif ($frequencyarray[$spawnpoint]["spawnswithpokemon"] / $frequencyarray[$spawnpoint]["allspawns"] > 1 / 10) {
                        echo "marker" . $spawnpoint . ".setIcon('http://maps.google.com/mapfiles/ms/icons/green-dot.png');";
                    }
                    // red marker - more than 10 spawns of all pokemon have been seen, but less or equal to 10 percent our pokemon
                    else {
                        echo "marker" . $spawnpoint . ".setIcon('http://maps.google.com/mapfiles/ms/icons/red-dot.png');";
                    }
                }
            }
        }
        ?>

    }

</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $gmaps_api_key; ?>&callback=initMap" async defer></script>

</body>

</html>
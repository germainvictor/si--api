<?php
  // Get content
  $url  = 'https://data.nasa.gov/resource/y77d-th95.json';
  $path   = './cache/'.md5('nasa');

  // From cache
  if(file_exists($path))
  {
    $forecast = file_get_contents($path);
    echo '<p class="probleme-none"><p>';
  }
  // From API
  else
  {
    echo "API";
    $forecast = file_get_contents($url);
    file_put_contents($path, $forecast);
  }

  // Json decode
  $forecast = json_decode($forecast);

  // Delete empty data
  for($i = 0; $i <= count($forecast); $i++)
  {
    if (empty($forecast[$i]->mass))
  {
    unset($forecast[$i]);
  }
  if (empty($forecast[$i]->reclong))
  {
    unset($forecast[$i]);
  }
  if (empty($forecast[$i]->reclat))
  {
    unset($forecast[$i]);
  }
  if (empty($forecast[$i]->year))
  {
    unset($forecast[$i]);
  }
  }

  // Sorting function cmp
  function cmp($a, $b)
  {
    print_r($a->year);
      return strcmp($a->year, $b->year);
  }

  // Duplicate tab
  $asteroids = new stdClass();
  $asteroids = $forecast;


  // Call Sorting function cmp
  usort($asteroids, function($a, $b)
  {
    if (!empty($a->year) && !empty($b->year))
    {
      return strcmp($a->year, $b->year);
    }
  });

  // Find the year
  for($i = 0; $i <= count($asteroids); $i++)
  {
    if (!empty($asteroids[$i]->year))
    {
      // echo "<br>";
      $foo = $asteroids[$i]->year;
      $rest = substr($foo, 0, 4);
      $asteroids[$i]->year = $rest;
    }
  }
?>
<!DOCTYPE HTML>
<html>
  <head>
    <meta name="robots" content="index, all" />
    <title>Map - meteroide</title>
    <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css" />
    <link rel="stylesheet" href="assets/css/timeline.css" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <script src="http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js"></script>
    <script src="http://www.webglearth.com/v2/api.js"></script>
  </head>
  <body onload="javascript:init()">

  <script type="text/javascript">
    var asteroids = <?= json_encode($asteroids)  ?>;
  </script>

  <section class="time-line">
    <div class="line-data">
      <div class="data-year">
        <p>Depuis </p>
        <p class="data-year-item"></p>
      </div>
      <div class="data-number">
        <p class="data-number-item"></p>
        <p> astéroïdes sont tombés</p>
      </div>
    </div>
    <div class="time-line-item">
      <div class="progress-line"></div>
    </div>
  </section>

  <section class="full-map">
    <div id="mapL"></div>
    <div id="coords"></div>
  </section>
      
    <script>
      var timeLine = {};

      timeLine.el               = {};
      timeLine.el.container     = document.querySelector('.time-line-item');
      timeLine.el.progress      = document.querySelector('.progress-line');
      timeLine.el.datayear      = document.querySelector('.data-year-item');
      timeLine.el.datanb        = document.querySelector('.data-number-item');

      var lineWidth     = document.querySelector('.time-line-item'),
          lineProgress  = document.querySelector('.progress-line'),
          linepos       = 0,
        timeLapse     = 1500,
        timeLapseRatio  = 1,
        timeLapseEnd  = 2017,
        asteroid_id   = 0,
        asteroidlength  = asteroids.length,
        lineContain   = timeLine.el.container.offsetWidth,
        ratiodiff   = (timeLapseEnd - timeLapse), // ~2.4
        ratio     = lineContain / ratiodiff,
        ratioTot    = ratio * timeLapseRatio;



      function set_interval()
      {
          timeLapse   += timeLapseRatio;
          linepos   += ratioTot;
        timeLine.el.progress.style.transform = 'translateX(' + linepos + 'px)'; 

          if(timeLapse >= timeLapseEnd - 1)
          {
          // Stop interval
            clearInterval(clear);
          }
          else
          {
          var nbAst   = 0;
          var massAst = -1;

            // Show asteroid per year
              while((asteroids[asteroid_id].year <= timeLapse) && asteroid_id < asteroidlength - 1)
              {
                asteroid_id += 1;
                nbAst     += 1;
                // Edit year and number Asteroid
            timeLine.el.datayear.innerHTML  = timeLapse;
            timeLine.el.datanb.innerHTML  = asteroid_id;
            
                if(massAst == -1)
                  massAst = asteroids[asteroid_id].mass
                else if(massAst < asteroids[asteroid_id].mass)
                  massAst = asteroids[asteroid_id].mass
              }
              if (nbAst != 0)
              {
                var scalenb   = 0.5 + Math.log(nbAst),
                  scalemass   = 0.3 + Math.log(massAst);

                // Create year bar
            var div       = document.createElement("div");
            div.className     = 'asteroid-number ' + asteroids[asteroid_id].year;
            div.style.transform = 'translateX(' + linepos + 'px) scaleY(' + scalenb + ')';
              timeLine.el.container.appendChild(div);
                // Create mass bar
            var div       = document.createElement("div");
            div.className     = 'asteroid-mass ' + asteroids[asteroid_id].mass;
            div.style.transform = 'translateX(' + linepos + 'px) scaleY(' + scalemass + ')';
              timeLine.el.container.appendChild(div);       
              }
          }
          return;
      }
      clear = setInterval("set_interval()", 20);




      /*******************
      * * * * MAP * * * *
      *****************/
      var astrs = [];

      function init() {
        var m = {};

        start_(L, 'L');

        function start_(API, suffix) {
          var mapDiv = 'map' + suffix;
          var map = API.map(mapDiv, {
            center: [51.505, -0.09],
            maxZoom: 7,
            minZoom: 2,
            dragging: true,
            scrollWheelZoom: true,
          });
          m[suffix] = map;

          //Add baselayer
          API.tileLayer('http://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',{
            attribution: '© OpenStreetMap contributors'
          }).addTo(map);

          //Add TileJSON overlay
          var json = {
            "profile": "mercator",
            "name": "Grand Canyon USGS",
            "format": "png",
            "bounds": [-112.26379395, 35.98245136, -112.10998535, 36.13343831],
            "minzoom": 10, "version": "1.0.0",
            "maxzoom": 16,
            "center": [-112.18688965, 36.057944835, 1],
            "type": "overlay", "description": "",
            "basename": "grandcanyon",
            "tilejson": "2.0.0",
            "sheme": "xyz",
            "tiles": ["http://tileserver.maptiler.com/grandcanyon/{z}/{x}/{y}.png"]};
          
          //If not able to display the overlay, at least move to the same location
          map.setView([2, 46], json.center[2]);

          // get API nasa data
          var astrs = <?= json_encode($asteroids) ?>;

          var _astrs = [];

          function astr (name, lat, ltn, mass, year)
          {
            this.name = name;
            this.lat = lat;
            this.ltn = ltn;
            this.mass = mass;
            this.year = year; 
          }

          document.addEventListener('click', function () {
            // console.log('map.zoom : ' + map._zoom);
            // console.log('zoom : ' + zoom);
            // console.log('className :');
            // console.log(myIcon.options.className);
          });



          // filtration des astéroides 
          // En fonction du nombre de d'astéroide
          for (let key in astrs) {
            if (astrs[key].reclat && astrs[key].reclong && astrs[key].year && astrs[key].mass ){
              _astrs.push(new astr(astrs[key].name, astrs[key].reclat, astrs[key].reclong, astrs[key].mass, astrs[key].year));
            }
          }
          
          var zoom = 2;

          setInterval(function(){ 
            // check if zoom change and zoom event
            if (zoom != map._zoom && zoom <= map._zoom) {
              zoom = map._zoom;              
              myIcon.options.className = 'zoom-' + zoom;
              filter_marker(zoom);
            }
            // check if zoom change and dezoom event
            if (zoom != map._zoom && zoom > map._zoom) {
              var elements = document.querySelectorAll('img.'+ 'zoom-' + zoom);
              for (var i = 0; elements.length > i; i++) {
                elements[i].parentNode.removeChild(elements[i]);
              }
              zoom = map._zoom; 
            }
          }, 100);
          

          var rank = [0,0, 200000, 100000, 50000, 25000, 15000, 1000];

          // parametres marker
          var myPopup = L.popup({
            className: 'popup',
          });          
          console.log(map);

          // parametres marker
          var myIcon = L.icon({
            iconUrl: 'assets/img/meteorite.png',
            className: 'zoom-' + zoom,
          });

          // Add marker and show and filtre
          function filter_marker (zoom) {
            for (var i = 0; i<_astrs.length; i++) {
                if (_astrs[i].mass > rank[zoom]) {  
                  var marker_ = L.marker([_astrs[i].lat, _astrs[i].ltn], {icon: myIcon}).addTo(map);
                  marker_.bindPopup( '<b>' + _astrs[i].name + '</b> <br>' + '<b>Year : </b>' + _astrs[i].year + '<br>' + '<b>Mass : </b>' + _astrs[i].mass + ' kg' , 50);
                }
            }
          }
          // Init function
          filter_marker(2);

          //Print coordinates of the mouse
          map.on('mousemove', function(e) {
            document.getElementById('coords').innerHTML = e.latlng.lat + ', ' + e.latlng.lng;
          });
        }
      }

    </script>
  </body>
</html>
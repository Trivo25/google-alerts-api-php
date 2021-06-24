<?php

require_once(dirname(__FILE__) . "./GoogleAlert.php");

$ga = new GoogleAlert();
$alert = $ga->create("monday", "de");

echo json_encode($alert);

/*  response
  {
    "rss": "https://www.google.de/alerts/feeds/somerssfeed/123",  // rssfeed:   articles will be delivered to this feed
    "googleid": "12341234b12341:a112341239364456:com:de:DE"       // googleid:  needed to delete and modify alerts
  }
*/
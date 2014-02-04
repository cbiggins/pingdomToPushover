<?php

require 'conf.php';
require 'vendor/autoload.php';

define('PINGDOM_CHECKS_ENDPOINT', 'https://api.pingdom.com/api/2.0/checks');
define('PUSHOVER_ENDPOINT', 'https://api.pushover.net/1/messages.json');

// Create the new guzzle client.
$client = new Guzzle\Http\Client(PINGDOM_CHECKS_ENDPOINT);

// Construct our request.
$pingdom_request = $client->get();
$pingdom_request->setAuth(PINGDOM_USER, PINGDOM_PASS);
$pingdom_request->addHeader('App-Key', PINGDOM_KEY);

// Make our request, catch any exceptions.
try {
  $pingdom_response = $pingdom_request->send();
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

// Make sure the checks are all ok.
$checks = $pingdom_response->json();

foreach ($checks['checks'] as $check) {
  if ($check['status'] !== 'up') {
    sendAlert($client, $check);
  }
}

function sendAlert($client, $check) {
  $alert = array(
    'token'     => PUSHOVER_APP_TOKEN,
    'user'      => PUSHOVER_USER_KEY,
    'message'   => $check['name'] . ' has been down since ' . strftime(USER_DATE_FORMAT, $check['lasterrortime']),
    'title'     => $check['name'] . ' is down!',
    'priority'  => 1,
  );

  $po_request = $client->post(PUSHOVER_ENDPOINT, array(), $alert);
  try {
    $po_response = $po_request->send();
  } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
  }
}

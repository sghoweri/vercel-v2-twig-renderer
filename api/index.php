<?php

require_once __DIR__ . '/../vendor/autoload.php';

use BasaltInc\TwigRenderer\TwigRenderer;
use \Webmozart\PathUtil\Path; // https://github.com/webmozart/path-util
use \Shudrum\Component\ArrayFinder\ArrayFinder; // https://github.com/Shudrum/ArrayFinder

header('Access-Control-Allow-Origin: *');

$data = json_encode($_GET);
$data = json_decode($data ? $data : '{}', true);
$templatePath = $_GET && $_GET["templatePath"] ? $_GET["templatePath"] : '';


// temporarily hard-coding the paths that work on vercel due to very weird path differences running locally vs locally (via vercel dev) vs deployed on vercel v2.0
// todo: swap based on env?
$configFilePath = '/var/task/user/data/shared-config.json'; 
try {
  $configString = file_get_contents($configFilePath);
} catch (\Exception $e) {
  $msgs[] = 'No configFile found at: ' . $configFilePath;
  $responseCode = 500;
}

try {
  $config = json_decode($configString, true);
} catch (\Exception $e) {
  $msgs[] = 'Error parsing JSON from config';
  $msgs[] = $e->getMessage();
  $responseCode = 500;
}

// `GET` or `POST`
$method = $_SERVER['REQUEST_METHOD'];
// All query string params parsed
$query = [];
if (isset($_SERVER['QUERY_STRING'])) {
  parse_str($_SERVER['QUERY_STRING'], $query);
}

if ($config) {
  try {
    $twigRenderer = new TwigRenderer($config);
  } catch (\Exception $e) {
    $msg = 'Error creating Twig Environment. ' . $e->getMessage();
    print $msg;
    $msgs[] = $msg;
    $responseCode = 400;
    $response['ok'] = false;
    $response['message'] = $msg;
  }
}

if ($twigRenderer) {
  if (key_exists('templatePath', $query)) {
    $templatePath = $query['templatePath'];
  } else {
    // @todo Provide more clear way to "ping" the server and know that it is ready.
    $msgs[] = "Url must have a query param of 'templatePath' for which twig template to render, but yes - the server is running.";
    $responseCode = 202;
  }

  if ($templatePath && $method === 'POST') {
    try {
      $json = file_get_contents('php://input');
    } catch(\Exception $e) {
      $msgs[] = 'No POST body found. ' . $e->getMessage();
      $responseCode = 400;
    }
    if ($json) {
      try {
        $data = json_decode($json, true);
      } catch (\Exception $e) {
        $msgs[] = 'Not able to parse JSON. ' . $e->getMessage();
        $responseCode = 400;
      }
    }
  }

  if ($templatePath) {
    try {
      $response = $twigRenderer->render($templatePath, $data);
      $responseCode = 200;

      // header("Content-Type: text/html");
      echo $response["html"];
    } catch (\Exception $e) {
      $msgs[] = $e->getMessage();
      $responseCode = 400;
    }
  }

  // if ($templatePath && $method === 'GET') {
  //   $data = (isset($payload['data']) && is_array($payload['data'])) ? $payload['data'] : array();
  // }
//   // echo $_GET;

//   // $data = [];
//   $data = json_encode($_GET);



//   // $newData = [];
//   // $newData['data'] = $data;
//   // print_r($newData);

//   $data = json_decode($data ? $data : '{}', true);


} else {
  print "Twig Renderer Not Found!";
}

$msgs = [];

if ($msgs) {
  print_r($msgs);
  // header('Warning: ' . join(' ', $msgs));
//  $response['message'] = join(' ', $msgs);
}

// http_response_code($responseCode);



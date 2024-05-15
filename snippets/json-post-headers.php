<?php



// good solution for reading data from fetch (sent as application/json)
// You can't access JSON by default in PHP with the $_POST variable so you have to do a bit of trickery to get access to it.

function fetch_json_post(){

  $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

  if ($contentType === "application/json") {
    //Receive the RAW post data.
    $content = trim(file_get_contents("php://input"));

    $decoded = json_decode($content, true);

    //If json_decode failed, the JSON is invalid.
    if(! is_array($decoded)) {

    } else {
      // Send error back to user. (improperly formatted)
    }

    $_POST = $decoded;

  } else{
    // not application/json ... send error if expecting application/json?
  }  
}
fetch_json_post();
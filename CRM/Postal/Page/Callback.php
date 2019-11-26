<?php

use CRM_Postal_ExtensionUtil as E;

class CRM_Postal_Page_Callback extends CRM_Core_Page {

  var $bounceTypes = array('softfail','hardfail','bounced','held');

  public function run() {
    // get json data from post
    $postdata = file_get_contents("php://input");
    $callback = json_decode($postdata);
    $oob = 0; //is out of bound bounce

    //for inbound bounces
    if(!empty($callback->payload->status)) {
      $status = strtolower($callback->payload->status);
    }
    elseif(!empty($callback->payload->bounce)) {
      $status = 'bounced';
      $oob = 1;
    }

    if(!in_array($status,$this->bounceTypes)) {
      die('No Action');
    }
    
    //get verp seperator from civi settings, used to parse from address
    $verp = civicrm_api3('Setting', 'get', [
      'sequential' => 1,
      'return' => ["verpSeparator"],
    ])['values'][0]['verpSeparator'];

    //get required data for processing bounce
    if($oob) {
      $from = $callback->payload->original_message->from;
      $bounceBody = $callback->payload->bounce->subject;
    }
    else {
      $from = $callback->payload->message->from;
      if($status == 'held') {
        $bounceBody = $callback->payload->details;
      }
      else {
        $bounceBody = $callback->payload->output;
      }
    }
    
    //get IDs from the 'from'address
    $fromParts = explode($verp,strstr($from, '@', true));
    $jobID = $fromParts[1];
    $queID = $fromParts[2];
    $hashID = $fromParts[3];

    //confirm elements for boucne processing are present
    if(empty($jobID) || empty($queID) || empty($hashID)) {
      die('Not a CiviMail email');
    }

    //attempt to process bounce with output from receiving mail server 
    try {
      $trackBounce = civicrm_api3('Mailing', 'event_bounce', [
        'job_id' => $jobID,
        'event_queue_id' => $queID,
        'hash' => $hashID,
        'body' => $bounceBody
      ]);
    }
    catch (exception $e) {
        http_response_code(400);
        die($e->getMessage());
    }
    
    die('Bounce Processed for '.$from);

  }

}

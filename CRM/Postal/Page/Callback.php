<?php

use CRM_Postal_ExtensionUtil as E;

class CRM_Postal_Page_Callback extends CRM_Core_Page {

  var $bounceTypes = array('softfail','hardfail','bounced','held');

  public function run() {
    // get json data from post
    $postdata = file_get_contents("php://input");
    $callback = json_decode($postdata);

    $status = strtolower($callback->payload->status);

    if(!in_array($status,$this->bounceTypes)) {
      die('No Action');
    }

    //get fom address in message
    $from = $callback->payload->message->from;
    
    //get verp seperator from civi settings, used to parse from address
    $verp = civicrm_api3('Setting', 'get', [
      'sequential' => 1,
      'return' => ["verpSeparator"],
    ])['values'][0]['verpSeparator'];

    //get IDs from the 'from'address
    $fromParts = explode($verp,strstr($from, '@', true));
    $jobID = $fromParts[1];
    $queID = $fromParts[2];
    $hashID = $fromParts[3];

    //confirm elements for boucne processing are present
    if(empty($jobID) || empty($queID) || empty($hashID)) {
      die('Not a CiviMail email');
    }

    if($status == 'held') {
      $bounceBody = $callback->payload->details;
    }
    else {
      $bounceBody = $callback->payload->output;
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
        die($e->getMessage());
    }

    watchdog('postal',$status.'-'.print_r($callback,true));
    die('Bounce Processed for '.$from);

  }

}

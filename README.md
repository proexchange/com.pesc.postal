# CiviCRM/Postal Bounce Handling

This extension allows CiviCRM to process bounces from webhooks geerated from the [Postal](https://github.com/postalhq/postal) email system. 

## Setup

Prerequisites: You have a working installation of Postal with domain added and authorized for sending

1. Install this extension and enable
1. Create SMTP credentials and setup CiviCRM's SMTP mailer.
2. Add webhook in Postal to http(s)://example.com/civicrm/postal/callback
    
Suggested Events Webhook Events
    - MessageDelayed
    - MessageDeliveryFailed
    - MessageHeld
    - MessageBounced

Optional: Install [Transactional Mail](https://github.com/fuzionnz/nz.co.fuzion.transactional) extension to process bounces for transactional mail 

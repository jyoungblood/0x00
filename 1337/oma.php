<?php








$app->get('/debug-email/?', function(){

// for /api/apply-save/

  // $application_id = '603838911bae8603838911baeb';
  // $_application = db_find("applications", "_id ='". $application_id ."'");
  // $_user = db_find("users", "_id ='". $_application['data'][0]['user_id'] ."'");



  // for /schedule/save/

    // $_input = db_find("applications", "_id ='603838911bae8603838911baeb'");
    // $input = $_input['data'][0];
    // $__user = db_find("users", "_id ='". $input['user_id'] ."'");
    // $user = $_user['data'][0];



    
// fixit we still need to get a string of the date/time scheduled

  // $contact_domain = 'port164.com';
  // $_desired_development = db_find("developments", "_id = '".$input['desired_development_id']."'");
  // if ($_desired_development){
  //   $contact_domain = $_desired_development['data'][0]['domain'];
  // }
  // $tenant_email_input = array(
  //   'to' => $_user['data'][0]['email'],
  //   'from' => 'Ocean Management <application-'.$application_id.'@notifications.oceancompaniesok.com>',
  //   'subject' => 'Showing Scheduled',
  //   'template' => 'email/tenant-showing-scheduled',
  //   'data' => array(
  //     'first_name' => $user['first_name'],
  //     'contact_domain' => $contact_domain,
  //     'scheduled_string' => '' // ??
  //   )
  // );
  // $GLOBALS['app']->email_send($tenant_email_input);



  // print_r($tenant_email_input);
  echo "👍";

});








?>
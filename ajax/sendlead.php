<?php

  $name 		    = isset($_POST['name']) ? $_POST['name'] : '';
  $phone 		    = isset($_POST['phone']) ? $_POST['phone'] : '';
  $description      = isset($_POST['description']) ? $_POST['description'] : '';
  $email 			= isset($_POST['email']) ? $_POST['email'] : '';
  $company          = isset($_POST['company']) ? $_POST['company'] : '';

  $contact = array(
    'NAME' => $name,
    'PHONE' => $phone,
    'DESCRIPTION' => $description,
    'EMAIL' => $email,
    'COMPANY' => $company,
    'CONTACT_ID' => 0,
    'COMPANY_ID' => 0,
    'DEAL_ID' => 0,
  );

  $webhook_url = 'YOUR WEBHOOK URL'

  $contact['COMPANY_ID'] = addCompany($contact);
  $contact['CONTACT_ID'] = addContact($contact);
  $contact['DEAL_ID'] = addDeal($contact);

  if($contact['DESCRIPTION'] != '') addMessage($contact);

  echo json_encode($contact['DEAL_ID'], JSON_UNESCAPED_UNICODE);

  function sendDataToBitrix($method, $data) {
    $queryUrl = webhook_url . $method ;
    $queryData = http_build_query($data);

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_POST => 1,
      CURLOPT_HEADER => 0,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $queryUrl,
      CURLOPT_POSTFIELDS => $queryData,
    ));

    $result = curl_exec($curl);
    curl_close($curl);
    return json_decode($result, 1);
  }

  function addDeal($contact) {
    $dealData = sendDataToBitrix('crm.deal.add', [
      'fields' => [
        'TITLE' => 'Заявка с сайта',
        'STAGE_ID' => 'NEW',
        'CONTACT_ID' => $contact['CONTACT_ID'],
      ], 'params' => [
        'REGISTER_SONET_EVENT' => 'Y'
      ]
    ]);

    return $dealData['result'];
  }

  function addContact($contact) {
    $check = checkContact($contact);
    if($check['total'] != 0) return $check['result'][0]['ID'];
    $contactData = sendDataToBitrix('crm.contact.add', [
      'fields' => [
        'NAME' => $contact['NAME'],
        'EMAIL' => [['VALUE' => $contact['EMAIL'], 'VALUE_TYPE' => 'WORK']],
        'PHONE' => [['VALUE' => $contact['PHONE'], 'VALUE_TYPE' => 'WORK']],
        'TYPE_ID' => 'CLIENT',
        'COMPANY_ID' => $contact['COMPANY_ID'],
      ], 'params' => [
        'REGISTER_SONET_EVENT' => 'Y'
      ]
    ]);

    return $contactData['result'];
  }

  function addCompany($contact) {
    $check = checkCompany($contact);
    if($check['total'] != 0) return $check['result'][0]['ID'];
    $companyData = sendDataToBitrix('crm.company.add', [
      'fields' => [
        'TITLE' => $contact['COMPANY'],
      ], 'params' => [
        'REGISTER_SONET_EVENT' => 'Y'
      ]
    ]);
    return $companyData['result'];
  }

  function addMessage($contact) {
    $messageData = sendDataToBitrix('crm.livefeedmessage.add', [
      'fields' => [
        'MESSAGE' => $contact['DESCRIPTION'],
        'POST_TITLE' => 'Сообщение с формы сайта',
        'ENTITYTYPEID' => 2,
        'ENTITYID' => $contact['DEAL_ID'],
      ], 'params' => [
        'REGISTER_SONET_EVENT' => 'Y'
      ]
    ]);
    return $messageData['result'];
  }

  function checkCompany($contact){
    $list = sendDataToBitrix('crm.company.list', [
      'filter' => [ 'TITLE' =>  $contact['COMPANY']],
      'select' => [ 'ID'],
    ]);
    return $list;
  }

  function checkContact($contact){
    $list = sendDataToBitrix('crm.contact.list', [
      'filter' => [ 'PHONE' =>  $contact['PHONE']],
      'select' => [ 'ID'],
    ]);
    return $list;
  }

 ?>

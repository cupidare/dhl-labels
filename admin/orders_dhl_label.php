<?php
// https://github.com/Petschko/dhl-php-sdk/tree/master/includes
// https://github.com/Petschko/dhl-php-sdk

// Für den Betrieb Ihrer Applikation und den Zugriff auf unsere produktiven Services benötigen Sie als Credentials Ihre Applikations-ID und ein Token.
// Das Token können Sie hier initial erzeugen und sofern notwendig jederzeit neu generieren.
// Sie müssen die Applikations-ID als User und das Token als Password für die BasicAuth Authentifizierung verwenden.


if (isset($_POST['action'])) {
  print_label();
}
function print_label() {
  $sandbox = 0;
  
  if ($sandbox == 1){
    $sLogin = "YOUR_SANDBOX_DATA"; // ID on entwickler.dhl.de
    $sPassword = "YOUR_SANDBOX_DATA"; // identical with passwort on entwickler.dhl.de
    $sUser = "2222222222_01"; // this should be replaced in live 
    $sSignature = "pass"; // this should be replaced in live
    $sLocation = "https://cig.dhl.de/services/sandbox/soap"; //Sandbox
    $label_path = 'YOUR_SANDBOX_DATA in local folder structure e.g. /homepage/yourdomain.com/dhl_labels/' . $_POST['r_oid']. '/'; //Sandbox
    if ($_POST["r_countryISOCode"] == "DE") {
      $sAccountNumber = "22222222220101"; // Sandbox
      $product_name = "V01PAK";
    } else {
      // Account number Übersicht: https://entwickler.dhl.de/group/ep/wsapis/geschaeftskundenversand/authentifizierung
      $sAccountNumber = "22222222225301"; // International Sandbox     
      // https://handbuch.mauve.de/DHL-Service
      $product_name = "V53WPAK";
    }

  } else {
    $sLogin = "YOUR_LIVE_DATA"; // Live
    $sPassword = "YOUR_LIVE_DATA"; // Live
    $sUser = "YOUR_LIVE_DATA"; // Live
    $sSignature = 'YOUR_LIVE_DATA'; // Live
    $sLocation = "https://cig.dhl.de/services/production/soap"; //Live
    $label_path = 'YOUR_LIVE_DATA in local folder structure e.g. /homepage/yourdomain.com/dhl_labels/' . $_POST['r_oid']. '/'; //Live
    if ($_POST["r_countryISOCode"] == "DE") {
      $sAccountNumber = "YOUR_LIVE_DATA"; // Live national
      $product_name = "V01PAK";
    } else {
      $sAccountNumber = "YOUR_LIVE_DATA"; // Live international
      $product_name = "V53WPAK";
    }
  }
  
  $aIntraship = [
    'Version' => [
      'majorRelease' => '2',
      'minorRelease' => '2'
    ],
    'ShipmentOrder' => [
      'sequenceNumber' => '1',
      'Shipment' => [
        'ShipmentDetails' => [
          'product' => $product_name,
          'accountNumber' => $sAccountNumber,
          'shipmentDate' => date(w) == 0 ? date("Y-m-d",strtotime('tomorrow')) : date("Y-m-d"),
          'customerReference' => $_POST['r_oid'],
          'ShipmentItem' => [
            'weightInKG' => $_POST['r_weight']
          ]
        ],
        'Shipper' => [
          'Name' => [
                  'name1' => 'YOUR_LIVE_DATA'
          ],
          'Address' => [
            'streetName' => 'YOUR_LIVE_DATA',
            'streetNumber' => 'YOUR_LIVE_DATA',
            'zip' => 'YOUR_LIVE_DATA',
            'city' => 'YOUR_LIVE_DATA',
            'Origin' => [
              'countryISOCode' => 'DE'
            ]
          ],
          'Communication' => [
            'phone' => 'YOUR_LIVE_DATA',
            'contactPerson' => 'YOUR_LIVE_DATA',
            'email' => 'YOUR_LIVE_DATA'
          ]
        ],
        'Receiver' => [
          'name1' => $_POST['r_company'],
          'Address' => [
            'name2' => $_POST['r_name'],
            'streetName' => $_POST['r_street_name'],
            'streetNumber' => $_POST['r_street_number'],
            'zip' => $_POST['r_postcode'],
            'city' => $_POST['r_city'],
            'Origin' => [
              'countryISOCode' => $_POST['r_countryISOCode']
            ]
          ],
          'Communication' => [
            'phone' => $_POST['r_tele'],
            'contactPerson' => $_POST['r_name'],
            'email' => $_POST['r_mail']
          ]
        ]
        
      ],
      'labelResponseType' => 'URL',
      'PrintOnlyIfCodeable' => [
        'active' => 1
      ]
    ]
  ];
  
  $aOptions = ['soap_version' => SOAP_1_1, 'encoding' => 'UTF-8', 'trace' => true, 'login' => $sLogin, 'password' => $sPassword, 'location' => $sLocation];

  try {
      $oSoapClient = new SoapClient('https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/2.2/geschaeftskundenversand-api-2.2.wsdl', $aOptions);
  } catch (SoapFault $oFault) {
      echo $oFault->getMessage()."\n";
      die("Initialisation of SOAP-Client failed");
  }
  $aCredentials = ['user' => $sUser, 'signature' => $sSignature, 'type' => '0'];

  $oHeaders = new SoapHeader('http://dhl.de/webservice/cisbase','Authentification', $aCredentials);
  $oSoapClient->__setSoapHeaders([$oHeaders]);
  $sFunction = 'createShipmentOrder';

  try {
      $oResult = $oSoapClient->__soapCall($sFunction, [$aIntraship]);
  } catch (SoapFault $oFault) {
      echo $oFault->getMessage()."\n";
      die("SOAP call failed");
  }

  $oDOM = new DOMDocument;
  $oDOM->preserveWhiteSpace = false;
  $oDOM->loadXML($oSoapClient->__getLastResponse());
  $oDOM->formatOutput = true;

  if (file_get_contents($oResult->CreationState->LabelData->labelUrl) <> "") {
    mkdir($label_path, 0777);
    $fi = new FilesystemIterator($label_path, FilesystemIterator::SKIP_DOTS);
    $filenumber = iterator_count($fi)+1;
    file_put_contents($label_path . $_POST['r_oid'] . '_' . $oResult->CreationState->LabelData->shipmentNumber . ".pdf", file_get_contents($oResult->CreationState->LabelData->labelUrl));
  }
  echo '###'; //Trennzeichen
  echo $oResult->CreationState->LabelData->shipmentNumber;
  exit;
}

?>
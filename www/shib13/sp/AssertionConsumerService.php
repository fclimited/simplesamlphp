<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/Shib13/AuthnRequest.php');
require_once('SimpleSAML/Bindings/Shib13/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');

$session = SimpleSAML_Session::getInstance(TRUE);


SimpleSAML_Logger::info('Shib1.3 - SP.AssertionConsumerService: Accessing Shibboleth 1.3 SP endpoint AssertionConsumerService');

try {

	$config = SimpleSAML_Configuration::getInstance();
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$binding = new SimpleSAML_Bindings_Shib13_HTTPPost($config, $metadata);
	$authnResponse = $binding->decodeResponse($_POST);

	$authnResponse->validate();
	$session = $authnResponse->createSession();


	if (isset($session)) {
	
		SimpleSAML_Logger::notice('Shib1.3 - SP.AssertionConsumerService: Successfully created local session from Authentication Response');
	
		$relayState = $authnResponse->getRelayState();
		if (isset($relayState)) {
			SimpleSAML_Utilities::redirect($relayState);
		} else {
			SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
		}
	} else {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOSESSION');
	}


} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
}


?>
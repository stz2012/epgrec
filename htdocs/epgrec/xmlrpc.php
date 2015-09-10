<?php
require_once dirname(dirname(dirname(__FILE__))).'/config.php';
require_once 'XML/RPC2/Server.php';

$options = array(
	"backend"  => "php",
	"encoding" => "UTF-8",
	"prefix" => "epgrec.",
);

$server = XML_RPC2_Server::create("EpgrecRpc", $options);
$server->handleCall();
?>

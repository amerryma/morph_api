<?php
include('morphapi.php');

$functionName;
$arguments;
$result = "";
$api = new MorphAPI();

if (isset($_POST["f"])) {
	$functionName = $_POST["f"];
	if (isset($_POST["a"])) {
		$arguments = $_POST["a"];
	}
}
if (isset($_GET["f"])) {
	$functionName = $_GET["f"];
	if (isset($_GET["a"])) {
		$arguments = $_GET["a"];
	}
}


$failed = false;
if (isset($functionName)) {
	if ($functionName == "getDeck") {
		$result = $api->getDeck();
	}
	if ($functionName == "getCard") {
		$result = $api->getCard();
	}
	if ($functionName == "giveCardToUser") {
		$result = $api->giveCardToUser($arguments);
	}
	if ($functionName == "getUserDeck") {
		$result = $api->getUserDeck($arguments);
	}
	if ($functionName == "clearUserDeck") {
		$result = $api->clearUserDeck($arguments);
	}
	if ($functionName == "removeCardFromDeck") {
		$result = $api->removeCardFromDeck($arguments);
	}
	if ($functionName == "giveRandomDeckToUser") {
		$result = $api->getDeck();
		$results = $result->getResults();
		for ($i=0; $i < count($results); $i++) {
			$result = $api->giveCardToUser('{"idUser":"'.$arguments.'","idCard":"'.$results[$i]["idCard"].'"}');
		}
	}
	if (!$result->getSuccess()) {
		$failed = true;
		echo $result->getMessage();
		echo "Function '$functionName' not valid, check your query string.";
	}
} else {
	$failed = true;
	echo "Post variable 'f' not defined, check your query string.";
}

if (!$failed) {
	header('Content-Type: application/json; charset=utf-8', true,200);
}

echo $result->getJsonResults();
unset($api);
?>
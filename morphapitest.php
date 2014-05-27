<?php
require_once 'PHPUnit/Autoload.php';
include('morphapi.php');

class MorphAPITest extends PHPUnit_Framework_TestCase
{
    public function testOwnedFunctions()
    {
		$api = new MorphAPI();

		//Get card and test SQLResult 
		$apiResult = $api->getCard();
		$this->assertEquals("1",count($apiResult->getResults()));
		$this->assertEquals("",$apiResult->getMessage());
		$this->assertEquals("SELECT * FROM cards ORDER BY rand() LIMIT 1",$apiResult->getQuery());
		$this->assertEquals("1",count(json_decode($apiResult->getJsonResults())));

		//Get deck
		$apiResult = $api->getRandomDeck();
		$this->assertEquals("50",count($apiResult->getResults()));

		//Clear deck from user 1
		$apiResult = $api->clearUserDeck('{"idUser":"1"}');
		$this->assertEquals(true, $apiResult->getSuccess());

		//Give 2 cards to user 1
		$card1Result = $api->giveCardToUser('{"idUser":"1","idCard":"6"}')->getResults();
		$card2Result = $api->giveCardToUser('{"idUser":"1","idCard":"3"}')->getResults();
		$card3Result = $api->giveCardToUser('{"idUser":"1","idCard":"1299"}')->getResults();
		$card4Result = $api->giveCardToUser('{"idUser":"1"}')->getResults();

		//Get all cards of user 1
		$apiResult = $api->getOwnedCards('{"idUser":"1"}');
		$dataResult = $apiResult->getResults();

		//Check if cards were added correctly to user 1
		$this->assertEquals($card1Result,$dataResult[0]["idOwned"]);
		$this->assertEquals($card2Result,$dataResult[1]["idOwned"]);
		$this->assertEquals("2",count($dataResult));

		//Give card to a user that doesn't exist, query should fail.
		$card5Result = $api->giveCardToUser('{"idUser":"10102","idCard":"3"}')->getSuccess();
		$this->assertEquals(false, $card5Result);

		//Create decks for user 3 (this should give user 3 two decks), should fail on the 3rd deck
		$apiResult = $api->createDeckForUser('{"idUser":"3","deckTitle":"Awesome Deck1","deckDescription":"My first awesome deck."}');
		$this->assertEquals(true, $apiResult->getSuccess());
		$deckId1 = $apiResult->getResults();
		$apiResult = $api->createDeckForUser('{"idUser":"3","deckTitle":"Awesome Deck2","deckDescription":"My second awesome deck."}');
		$this->assertEquals(true, $apiResult->getSuccess());
		$deckId2 = $apiResult->getResults();

		//Add an owned card owned by user 3 to a deck owned by user 3. Use card 609.
		$apiResult = $api->addCardToDeck('{"idDeck":"'.$deckId1.'","idOwned":"609"}');
		$this->assertEquals(true, $apiResult->getSuccess());
		//Add an owned card owned by user 2 to a deck owned by user 3. Use card 702. (query should fail)
		$apiResult = $api->addCardToDeck('{"idDeck":"'.$deckId1.'","idOwned":"702"}');
		$this->assertEquals(false, $apiResult->getSuccess());

		//Delete the extra decks created for user 3
		$apiResult = $api->deleteDeck('{"idDeck":"'.$deckId1.'"}');
		$this->assertEquals(true, $apiResult->getSuccess());
		$apiResult = $api->deleteDeck('{"idDeck":"'.$deckId2.'"}');
		$this->assertEquals(true, $apiResult->getSuccess());


    }

    public function testGameManagementFunctions()
    {
    	$api = new MorphAPI();

    	//Create a game with user 1 as the host
		$apiResult = $api->createGame('{"idUser":"3"}');
		$this->assertEquals(true, $apiResult->getSuccess());
		$gameId = $apiResult->getResults();

		//Add both users to game: $gameId
		$apiResult = $api->addPlayerToGame('{"idUser":"3","idGame":"'.$gameId.'"}');
		$this->assertEquals(true, $apiResult->getSuccess());
		$playerId = $apiResult->getResults();
		$apiResult = $api->addPlayerToGame('{"idUser":"2","idGame":"'.$gameId.'"}');
		$this->assertEquals(true, $apiResult->getSuccess());
		$player2Id = $apiResult->getResults();

		//Add both player's decks to game
		$apiResult = $api->addDeckToGame('{"idDeck":"53","idGame":"'.$gameId.'"}');
		$this->assertEquals("50",$apiResult->getResults());
		$apiResult = $api->addDeckToGame('{"idDeck":"54","idGame":"'.$gameId.'"}');
		$this->assertEquals("50",$apiResult->getResults());

		//Add a deck that doesn't exist
		$apiResult = $api->addDeckToGame('{"idDeck":"3","idGame":"'.$gameId.'"}');
		$this->assertEquals("0",$apiResult->getResults());

		//Set identities for players
		$apiResult = $api->setIdentity('{"idPlayer":"'.$playerId.'"}');
		$this->assertEquals(true, $apiResult->getSuccess());
		$hash = $apiResult->getResults();
		$apiResult = $api->setIdentity('{"idPlayer":"'.$playerId.'"}');
		$this->assertEquals(false, $apiResult->getSuccess());
		$apiResult = $api->setIdentity('{"idPlayer":"'.$player2Id.'"}');
		$this->assertEquals(true, $apiResult->getSuccess());
		$hash2 = $apiResult->getResults();

		//Start game tests
		$apiResult = $api->startGame('{"uniqueId":"'.$hash.'","idGame":"'.$gameId.'"}');
		$this->assertEquals(true, $apiResult->getSuccess());
		$apiResult = $api->startGame('{"uniqueId":"'.$hash.'","idGame":"10000"}');
		$this->assertEquals(false, $apiResult->getSuccess());
		$apiResult = $api->startGame('{"uniqueId":"'.$hash2.'","idGame":"'.$gameId.'"}');
		$this->assertEquals(false, $apiResult->getSuccess());

		//Delete game
		$apiResult = $api->deleteGame('{"idGame":"'.$gameId.'"}');
		$this->assertEquals("1",$apiResult->getResults());
    }

    public function testDeckFunctions()
    {
    	$api = new MorphAPI();

    	$apiResult = $api->clearDeck('{"idDeck":"54"}');
		$this->assertEquals(true, $apiResult->getSuccess());

    	$cardIds = array(478,479,544,545);

		foreach ($cardIds as $value) {
			$apiResult = $api->addCardToDeck('{"idDeck":"54","idOwned":"'.$value.'"}');
			$this->assertEquals(true, $apiResult->getSuccess());
		}

		$apiResult = $api->removeCardFromDeck('{"idDeck":"54","idOwned":"479"}');
    }
}
?>
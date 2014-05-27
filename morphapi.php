<?php

/**
 * This function contains success results any MorphAPI function call.
 */
class MorphAPIResult
{
	protected $success;
	protected $message;
	protected $results;

   	public function __construct($success, $message, $results)
   	{
		$this->success = (boolean) $success;
		$this->message = $message;
		$this->results = $results;
	}

	public function getSuccess()
	{
		return $this->success;
	}

	public function getMessage()
	{
		return $this->message;
	}

	public function getResults()
	{
		return $this->results;
	}

	public function getJsonResults()
	{
		return json_encode($this->results, true);
	}
}

/**
 * This function contains information about a query as well as success results for running that query.
 * @extends MorphAPIResult
 */
class SQLResult extends MorphAPIResult
{
	protected $sqlQuery;
   	public function __construct($success, $message, $results, $query)
   	{
		$this->success = (boolean) $success;
		$this->message = $message;
		$this->results = $results;
		$this->sqlQuery = $query;
	}

	public function getQuery()
	{
		return $this->sqlQuery;
	}

}

/**
 * This function contains information related to SQLResult.
 * TurnResult also contains information regarding every single card on the board.
 * @extends SQLResult
 * @todo Complete this class.
 */
class TurnResult extends SQLResult
{
	private $cards; //List of "CurrentCard"s
   	public function __construct($success, $message, $results, $query)
   	{
		$this->success = (boolean) $success;
		$this->message = $message;
		$this->results = $results;
		$this->sqlQuery = $query;
	}

}

/**
 * Contains information about the current card.
 * @todo Complete this class.
 */
class CurrentCard
{
	private $idPlayer; //The id of the player it refers to
	private $location; //Image location with updated text (can be the actual card image or just the flipped over card image)
	private $tableSlot; //Location on the table
	private $promptCard; //Set to true if this card needs to be prompted to the user for selection.
	private $promptId; //An id that corresponds to the prompted card.

	public function getIdPlayer(){
		return $this->idPlayer;
	}

	public function setIdPlayer($idPlayer){
		$this->idPlayer = $idPlayer;
	}

	public function getLocation(){
		return $this->location;
	}

	public function setLocation($location){
		$this->location = $location;
	}

	public function getTableSlot(){
		return $this->tableSlot;
	}

	public function setTableSlot($tableSlot){
		$this->tableSlot = $tableSlot;
	}

	public function getPromptCard(){
		return $this->promptCard;
	}

	public function setPromptCard($promptCard){
		$this->promptCard = $promptCard;
	}

	public function getPromptId(){
		return $this->promptId;
	}

	public function setPromptId($promptId){
		$this->promptId = $promptId;
	}
}

/**
 * This class contains all functions related to queries and returning results based on those queries.
 */
class SQL
{
	private $sqlcon;
    private $sqlResult;

   	public function __construct()	
	{
		$this->sqlcon = mysql_connect("sql.wurbo.com", "wurboadmin", "Dragon609!")or die("cannot connect"); 
		$error = mysql_select_db("morphdb") == true;
		$sqlResult = new SQLResult("", "", $error, "");
	}

	/**
	 * Returns the results from a select query.
	 * @param  string $query
	 * @return SQLResult $sqlResult
	 */
	public function getSelectQuery($query)
	{
		$result=mysql_query($query);
		$bigarray = array();
		$message = "Unknown error.";
		if ($result) {
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$bigarray[] = $row;
			}
			$message = "No error.";
		}
		$sqlResult = new SQLResult($result, mysql_error(), $bigarray, $query);
		return $sqlResult;
	}

	/**
	 * Returns the last insert ID.
	 * @param  string $query
	 * @return SQLResult $sqlResult
	 */
	public function getInsertQuery($query) {
		$result=mysql_query($query);
		$id = mysql_insert_id();
		$sqlResult = new SQLResult($result, mysql_error(), $id, $query);
		return $sqlResult;
	}

	/**
	 * Returns # of rows affected by the delete.
	 * @param  string $query
	 * @return SQLResult $sqlResult
	 */
	public function getDeleteQuery($query) {
		$result=mysql_query($query);
		$sqlResult = new SQLResult($result, mysql_error(), mysql_affected_rows(), $query);
		return $sqlResult;
	}

	/**
	 * Returns # of rows affected by the update.
	 * @param  string $query
	 * @return SQLResult $sqlResult
	 */
	public function getUpdateQuery($query) {
		$result=mysql_query($query);
		$sqlResult = new SQLResult($result, mysql_error(), mysql_affected_rows(), $query);
		return $sqlResult;
	}


	/**
	 * Gets the results from the stored procedure. Must have @success and @resultMsg as the last values.
	 * @param  string $query
	 * @return SQLResult $sqlResult
	 */
	public function getStoredProcedure($query) {
		$result=mysql_query($query);
		$affectedRows = mysql_affected_rows();

		//Get the success and resultmsg values
		$procedureResult=mysql_query("SELECT @success,@resultMsg;");
		$row = mysql_fetch_assoc($procedureResult);
		$sqlResult = new SQLResult($row["@success"], $row["@resultMsg"], $affectedRows, $query);
		return $sqlResult;
	}

	//
	/**
	 * Returns true or false as a MorphAPIResult if the 2nd (...) arguments are in the 1st argument.
	 * @param  string $jsonArgs 
	 * @return boolean
	 */
	public static function checkArgs($jsonArgs) {
		$success = true;
		$errorStr = "";
		$numargs = func_get_args();
		for ($i=1; $i < count($numargs); $i++) {
			if (!array_key_exists($numargs[$i],$jsonArgs)) {
				$success = false;
				$errorStr .= "Could not find argument: '".$numargs[$i]."' required for this function. <br/>";
			}
		}
		if ($errorStr == "") {
			$errorStr = "No error.";
		}
		$morphApiResult = new MorphAPIResult($success, $errorStr, $success);
		return $morphApiResult;
	}

   	public function __destruct()
	{
		mysql_close($this->sqlcon);
	}
}

/**
 * This class contains all functions related to the game logic on the server.
 */
class MorphAPI
{
	private $database;
   	public function __construct() {
		$this->database = new SQL();
	}

	/**
	 * This function returns a random deck of 50 cards, with rarities included.
	 * @return SQLResult $result
	 */
    public function getRandomDeck()
    {
    	$result =  $this->database->getSelectQuery("(SELECT * FROM cards WHERE rarity=0 ORDER BY rand() LIMIT 20)
													UNION ALL
													(SELECT * FROM cards WHERE rarity=1 ORDER BY rand() LIMIT 20)
													UNION ALL
													(SELECT * FROM cards WHERE rarity=2 ORDER BY rand() LIMIT 9)
													UNION ALL
													(SELECT * FROM cards WHERE rarity=3 ORDER BY rand() LIMIT 1)");
    	return $result;
	}

	/**
	 * This function returns a random card.
	 * @return SQLResult $result
	 */
    public function getCard()
    {
		$result = $this->database->getSelectQuery("SELECT * FROM cards ORDER BY rand() LIMIT 1");
    	return $result;
    }

    /**
     * This function gives a card to a user. Returns the owned id.
     * @param  string $arguments Contains idUser and idCard in JSON format.
     * @return SQLResult $result
     */
    public function giveCardToUser($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idCard", "idUser");
		if ($result->getSuccess()) {
			$result = $this->database->getInsertQuery("INSERT INTO owned (users_id, cards_idCard) VALUES ('".$jsonArgs["idUser"]."', '".$jsonArgs["idCard"]."');");
		}
		return $result;
    }

    /**
     * This function returns the all the users owned cards.
     * @param  string $arguments Contains idUser in JSON format.
     * @return SQLResult $result
     */
    public function getOwnedCards($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idUser");
		if ($result->getSuccess()) {
			$result = $this->database->getSelectQuery("SELECT * FROM cards, owned WHERE owned.users_id = '".$jsonArgs["idUser"]."' AND cards.idCard = owned.cards_idCard");
		}
		return $result;
    }

    /**
     * This function removes all cards from the specified users deck.
     * @param  string $arguments Contains idUser in JSON format.
     * @return SQLResult $result
     */
    public function clearUserDeck($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idUser");
		if ($result->getSuccess()) {
			$result = $this->database->getDeleteQuery("DELETE FROM owned WHERE owned.users_id = '".$jsonArgs["idUser"]."';");
		}
		return $result;
    }

    /**
     * This function creates a game with the creator and returns the game ID. Players (including creator) need to be added to this game.
     * @param  string $arguments Contains idUser in JSON format.
     * @return SQLResult $result
     * @todo Automatically add creator to game
     */
    public function createGame($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idUser");
		if ($result->getSuccess()) {
	    	//Has default gameType of 1 (Marathon)
			$result = $this->database->getInsertQuery("INSERT INTO games (creator) VALUES (".$jsonArgs["idUser"].")");
		}
		return $result;
    }

    /**
     * This function deletes a game. Will cascading delete.
     * @param  string $arguments Contains idGame in JSON format.
     * @return SQLResult $result
     */
    public function deleteGame($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idGame");
		if ($result->getSuccess()) {
			$result = $this->database->getDeleteQuery("DELETE FROM games WHERE idGame = '".$jsonArgs["idGame"]."';");
		}
		return $result;
    }

    /**
     * This function adds a player to the game, returns the id that represents that players participation in that game.
     * @param  string $arguments Contains idUser and idGame in JSON format.
     * @return SQLResult $result
     */
    public function addPlayerToGame($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idUser", "idGame");
		if ($result->getSuccess()) {
			$result = $this->database->getInsertQuery("INSERT INTO players (users_id, games_idGame) VALUES ('".$jsonArgs["idUser"]."', '".$jsonArgs["idGame"]."');");
		}
		return $result;
    }

    /**
     * This function adds a players cards to the game, returns the id that represents that current card in the game.
     * Integrity of the deck is validated by the mysql database.
     * @param  string $arguments Contains idGame, idDeck in JSON format.
     * @return SQLResult $result
     */
    public function addDeckToGame($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idGame", "idDeck");
		if ($result->getSuccess()) {
			$result = $this->database->getStoredProcedure("CALL addDeckToGame('".$jsonArgs["idDeck"]."', '".$jsonArgs["idGame"]."', @success, @resultMsg);");
		}
		return $result;
    }

    /**
     * This function adds a deck for the current user. Returns the deck id.
     * @param  string $arguments Contains idUser, deckTitle, and deckDescription in JSON format.
     * @return SQLResult $result
     */
    public function createDeckForUser($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idUser", "deckTitle", "deckDescription");
		if ($result->getSuccess()) {
			$result = $this->database->getInsertQuery("INSERT INTO decks (users_id, deckTitle, deckDescription) VALUES ('".$jsonArgs["idUser"]."', '".$jsonArgs["deckTitle"]."', '".$jsonArgs["deckDescription"]."');");
		}
		return $result;
    }

    /**
     * This function deletes a deck. This will not delete the owned cards, only set them to a NULL deck. Returns the number of affected rows.
     * @param  string $arguments Contains idDeck in JSON format.
     * @return SQLResult $result
     */
    public function deleteDeck($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idDeck");
		if ($result->getSuccess()) {
			$result = $this->database->getDeleteQuery("DELETE FROM decks WHERE decks.idDeck = '".$jsonArgs["idDeck"]."';");
		}
		return $result;
    }

    /**
     * This function clears a deck.
     * @param  string $arguments Contains idDeck in JSON format.
     * @return SQLResult $result
     */
    public function clearDeck($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idDeck");
		if ($result->getSuccess()) {
			$result = $this->database->getDeleteQuery("DELETE FROM deckCards WHERE decks_idDeck = '".$jsonArgs["idDeck"]."';");
		}
		return $result;
    }

    /**
     * Adds the card to a specified deck.
     * @param  string $arguments Contains idOwned and idDeck in JSON format.
     * @return SQLResult $result
     */
    public function addCardToDeck($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idOwned", "idDeck");
		if ($result->getSuccess()) {
			//Select the last card in the deck
			$result = $this->database->getSelectQuery("SELECT * FROM deckCards WHERE decks_idDeck = '".$jsonArgs["idDeck"]."' AND nextCard IS NULL;");
			$dataResult = $result->getResults();
			//Check to make sure there are any cards in the deck.
			if (count($dataResult) > 0) {
				//There is at least one card in the deck, we need to insert our card into the deck and set the other card's next owned to our and our prev owned to that card.
				$lastCardId = $dataResult[0]["owned_idOwned"];
				$result = $this->database->getInsertQuery("INSERT INTO deckCards (decks_idDeck, owned_idOwned, prevCard) VALUES ('".$jsonArgs["idDeck"]."', '".$jsonArgs["idOwned"]."', '".$lastCardId."');");
				if ($result->getSuccess()) {
					$result = $this->database->getUpdateQuery("UPDATE deckCards SET nextCard='".$jsonArgs["idOwned"]."' WHERE decks_idDeck='".$jsonArgs["idDeck"]."' AND owned_idOwned = '".$lastCardId."';");
				}
			} else {
				//There are no cards in the deck, the rest is easy. Just insert the card with null for next and prev.
				$result = $this->database->getInsertQuery("INSERT INTO deckCards (decks_idDeck, owned_idOwned) VALUES ('".$jsonArgs["idDeck"]."', '".$jsonArgs["idOwned"]."');");
			}
		}
		return $result;
    }

    /**
     * Removes the card from a specified deck.
     * @param  string $arguments Contains idOwned and idDeck in JSON format.
     * @return SQLResult $result
     */
    public function removeCardFromDeck($arguments)
    {
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idOwned", "idDeck");
		if ($result->getSuccess()) {
			//Select the card in the deck
			$result = $this->database->getSelectQuery("SELECT * FROM deckCards WHERE decks_idDeck = '".$jsonArgs["idDeck"]."';");
			$dataResults = $result->getResults();
			//Check to make sure that this card is already in the deck
			if (count($dataResults) > 1) {
				//Select the card previous and next to our card 
				foreach ($dataResults as $dataResult) {
					if (isset($dataResult["prevCard"]) && $dataResult["prevCard"] == $jsonArgs["idOwned"]) {
						$prevCard = $dataResult["owned_idOwned"];
					}
					if (isset($dataResult["nextCard"]) && $dataResult["nextCard"] == $jsonArgs["idOwned"]) {
						$nextCard = $dataResult["owned_idOwned"];
					}
				}
				//Check if we found our card
				if (isset($prevCard) && isset($nextCard)) {
					//Update the previous card's record to match the next cards record
					$result = $this->database->getUpdateQuery("UPDATE deckCards SET prevCard='".$nextCard."' WHERE decks_idDeck='".$jsonArgs["idDeck"]."' AND owned_idOwned = '".$prevCard."';");
					$result = $this->database->getUpdateQuery("UPDATE deckCards SET nextCard='".$prevCard."' WHERE decks_idDeck='".$jsonArgs["idDeck"]."' AND owned_idOwned = '".$nextCard."';");
				} else if(isset($prevCard)) {
					//Update the next card's record to NULL because there is no previous card.
					$result = $this->database->getUpdateQuery("UPDATE deckCards SET prevCard=NULL WHERE decks_idDeck='".$jsonArgs["idDeck"]."' AND owned_idOwned = '".$prevCard."';");
				} else if(isset($nextCard)) {
					//Update the previous card's record to NULL because there is no next card.
					$result = $this->database->getUpdateQuery("UPDATE deckCards SET nextCard=NULL WHERE decks_idDeck='".$jsonArgs["idDeck"]."' AND owned_idOwned = '".$nextCard."';");
				}
			}

			//Now that other records are taken care of, we can delete the current card.
			$result = $this->database->getDeleteQuery("DELETE FROM deckCards WHERE decks_idDeck= '".$jsonArgs["idDeck"]."' AND owned_idOwned = '".$jsonArgs["idOwned"]."';");

		}
		return $result;
    }

    /**
     * This function assigns a unique identifier to the requester. All future functions must contain that unique ID.
     * This will make sure the identity of the user is always the same and will make sure it is always allowed to do what it's requesting.
     * Should be called when the player is ready.
     * @param  string $arguments Contains idPlayer in JSON format.
     * @return MorphAPIResult $result
     */
    public function setIdentity($arguments)
    {
    	//Check if identity already exists, if it does, nothing is returned because it might not be the original player.
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "idPlayer");
		if ($result->getSuccess()) {
			$result = $this->database->getSelectQuery("SELECT * FROM players WHERE uniqueHash IS NULL AND idPlayer = ".$jsonArgs["idPlayer"].";");
			if (count($result->getResults()) == 1) {
    			//If they are the first then they get back a unique string that should be used for every transaction.
				$hash = md5($jsonArgs["idPlayer"] + time());
				$result = $this->database->getUpdateQuery("UPDATE players SET uniqueHash = '".$hash."' WHERE uniqueHash IS NULL AND idPlayer = ".$jsonArgs["idPlayer"].";");
				$result = new MorphAPIResult($result->getSuccess(), "No error.", $hash);
			} else {
				$result = new MorphAPIResult(false, "Unique hash already created for this user.", 0);
			}
		}
		return $result;
    }

    /**
     * Starts the specified game; based on the game type. Returns a player id (the first players turn; which is random)
     * if the game successfully started (meaning all decks are good and players are good). Or else it returns a 0. $result->getResults();
     * Game Type 1 - Marathon - at least 2 players.
     * @param  string $arguments Contains uniqueId, idGame in JSON format.
     * @return MorphAPIResult $result
     * @see setIdentity
     */
    public function startGame($arguments)
    {
    	/**
    	 * @todo Draw 7 cards.
    	 * @todo Start turn for first player.
    	 */
		$jsonArgs = json_decode($arguments, true);
		$result = SQL::checkArgs($jsonArgs, "uniqueId", "idGame");
		$uniqueId = $jsonArgs["uniqueId"];
		$idGame = $jsonArgs["idGame"];
		if ($result->getSuccess()) {
			//Make sure the identity of the caller is the creator of the game.
			$result = $this->database->getSelectQuery("SELECT creator=users_id AS isCreator FROM players, games WHERE games_idGame = idGame AND idGame = ".$idGame." AND uniqueHash = '".$uniqueId."';");
			$dataResult = $result->getResults();
			if ($result->getSuccess() && count($dataResult) == 1) {
				if ($dataResult[0]["isCreator"]) {
					//Make sure all players have an identity (a "wireshark" safe identifier that changes per game, like a session id)
					$result = $this->database->getSelectQuery("SELECT * FROM games, players WHERE idGame = ".$idGame." AND games_idGame = idGame AND uniqueHash IS NOT NULL;");
					$dataResult = $result->getResults();
					if (count($dataResult) >= 2) {

					} else {
						$result = new MorphAPIResult(false, "Game does not have enough ready players.", 0);
					}
				} else {
					$result = new MorphAPIResult(false, "User is not the creator of the game. Only creators can start games.", 0);
				}
			} else {
				$result = new MorphAPIResult(false, "Game doesn't exist", 0);
			}
		}
		$this->onTurnStart("");
		return $result;
    }

    /**
     * Uses a card on another card. Will check to make sure idPlayer's (from idPlayerCard) turn is active.
     * @param  string $arguments Contains uniqueId, idPlayerCard, idEnemyPlayerCard in JSON format.
     * @return TurnResult $result
     * @todo Complete this function.
     */
    public function useCard($arguments)
    {
    	//Check to make sure the current player is correct by checking uniqueId
    }

    /**
     * Morphs a card, will take the next available tableslot for morphing.
     * @param  string $arguments Contains uniqueId, idPlayerCard in JSON format.
     * @return TurnResult $result
     * @todo Complete this function.
     */
    public function morphCard($arguments)
    {
    	//Check to make sure the current player is correct by checking uniqueId
    }

    /**
     * When user ends their turn, this function is called and it will take care of all of end of turns checks.
     * @param  string $arguments Contains uniqueId in JSON format.
     * @return TurnResult $result
     * @todo Complete this function.
     */
    public function onTurnEnd($arguments)
    {
    	/**
    	 * @todo Check to make sure the current player is correct by checking uniqueId
    	 * @todo a. Make sure you have 7 or less cards in hand (otherwise discard something)
    	 * @todo b. All players must discard any cards that have 0 defense
    	 * @todo c. Switch current player of the game to the next player.
    	 */
    }

    /**
     * Private function, called right after onTurnEnd. Grabs current player from idGame.
     * @param  string $arguments Contains uniqueId in JSON format.
     * @return TurnResult $result
     * @todo Complete this function.
     */
    public function onTurnStart($arguments)
    {
    	/**
    	 * @todo Check to make sure the current player is correct by checking uniqueId
    	 * @todo a. Move any morphing cards to the morphed position.
    	 * @todo b. Apply any poison and subtract one from any poison turns or stun turns. Anything with 0 turns removes the affect. 
    	 * @todo c. Draw 1 card (unless first turn of game, draw 7)
    	 */
    }
    
    /**
     * Private function to validate hash
     * @param  string $hash
     * @return SQLResult $result
     */
    private function validateHash($hash)
    {
    }

   	public function __destruct() {
		unset($this->database);
	}
}
?>
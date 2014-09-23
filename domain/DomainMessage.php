<?php 

/**
* A Simple Domain class (model) for the message. 
*/
class DomainMessage
{
	const NEW_ADDED_MSG = 0;
	const AUTH_SENT	    = 1;
	const AUTH_DONE 	= 2;

	protected static $_db=null;

	protected static function _getDb()
	{
		if(!self::$_db) 
			self::$_db =  new Database; 

		return self::$_db;
	}

	public static function senderWithAuthPendingThisWeek($sender_email)
	{
		$db = self::_getDB();
		$query = "SELECT `date_auth_sent` 
			FROM `messages` 
			WHERE `from_email` = '" . $sender_email . "' 
			AND status = ".self::AUTH_SENT." 
			LIMIT 1";

	    $res = $db->getArray($query);
	    $time_diff  = time() - strtotime($res[0]['date_auth_sent']);
	    $nb_days    = $time_diff/(60*60*24);

	    return $nb_days < 7 ? true : false;
	}
}
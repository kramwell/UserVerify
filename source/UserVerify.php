<?php
#Written by KramWell.com - 18/APR/2018
#Useful bot for discouraging spammers joining a telegram chat group by asking them to verify themselves first.

$botID = "000000000";
$botToken = $botID.":XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
$url = "https://api.telegram.org/bot".$botToken;

$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);

$addonSend = "?disable_notification=TRUE&parse_mode=HTML&disable_web_page_preview=TRUE&";

$reply = '';
$executeQuery = FALSE;
##############################################################

#checkJSON("ouputAll.txt", $update);

$isCPUtalkBotId = $update['callback_query']['message']['from']['id'];

if ($isCPUtalkBotId == $botID){ #this is my bot replying.
	$snip = $update['callback_query']['data'];
	$callback_id = $update['callback_query']['id'];
	$message = $update['callback_query']['data'];
	$chat_id = $update['callback_query']['message']['chat']['id'];
	$message_id = $update['callback_query']['message']['message_id'];
	$from_id = $update['callback_query']['message']['from']['id'];
	$username = $update['callback_query']['from']['username'];	
}else{
	$from_id = $update['message']['from']['id'];
	$first_name = $update['message']['from']['first_name'];
	$chat_id = $update['message']['chat']['id'];
	$snip = $update['message']['text'];
	$message = $update['message']['text'];
	$message_id = $update['message']['message_id'];
	$new_member_id = $update['message']['new_chat_member']['id'];
	$isBot = $update['message']['from']['is_bot'];
	$username = $update['message']['from']['username'];
}

if (!is_numeric($chat_id)){
	displayError($url, $chat_id, $message_id, "Error: chat id not valid!");
}
if (!is_numeric($from_id)){
	displayError($url, $chat_id, $message_id, "Error: from id not valid!");
}
if (!is_numeric($message_id)){
	displayError($url, $chat_id, $message_id, "Error: message id not valid!");
}

if ($new_member_id){

	if (!is_numeric($chat_id)){
		displayError($url, $chat_id, $message_id, "Error: new member id not valid!");
	}

	if ($new_member_id == $botID){
		displayError($url, $chat_id, $message_id, "Thanks for adding me! be sure to check out @CPUtalk !");
	}	
	
	if ($chat_id == $from_id){
		displayError($url, $chat_id, $message_id, "Can not do this in PM sorry!");
	}
	
	#LOGGING OF SENT TO TELEGRAM
	#checkJSON("newMember.txt", $update);
	
	$new_chat_members = $update['message']['new_chat_members'];
	
	foreach ($new_chat_members as $new_chat_member) {

		$new_member_welcome = $new_chat_member['first_name'];	
		$new_member_id = $new_chat_member['id'];
	
		#BAN MEMBER
		$postfields = array(
			'chat_id' => "$chat_id",
			'user_id' => "$new_member_id"
		);
		$resultRestrictMember = executeQuery($postfields, $url."/restrictChatMember", TRUE);	
		
		$resultRestrictMember = json_decode($resultRestrictMember, true);
		$resultRestrictMemberOK = $resultRestrictMember['ok'];
			
			if ($resultRestrictMemberOK <> 1){
				displayError($url, $chat_id, $message_id, "Error restricting user $new_member_welcome, please contact @KramWell");
				checkJSON("restrict.txt", $resultRestrictMember);
			}		

		#compile verify message
		$keyboard = array(
		"inline_keyboard" => array(
		array(
			array(
				"text" => "Verify",
				"callback_data" => "verify_".$new_member_id
			)
		),				
		)); 		
		
		$reply = "Hey $new_member_welcome <b>!Welcome!</b>

Thanks for joining CPU Miners Club!		
		
Because of <b>!spammers!</b>, You have to verify your account before you can post. Please click the button below to do so.";
		
		$postfields = array(
			'chat_id' => "$chat_id",
			'text' => "$reply",
			'message_id' => "$message_id",
			'reply_markup' => json_encode($keyboard)
		);	
	
	executeQuery($postfields, $url."/sendMessage".$addonSend, TRUE);		
	}
}



##############################################################	
# DISPLAY VERIFY
##############################################################	
if ($callback_id){ #if button clicked

	if (mb_substr($snip, 0, 7, 'utf-8') == 'verify_'){

		#LOGGING OF SENT TO TELEGRAM
		#checkJSON("verifyClick.txt", $update);

		$user_id_verify = str_replace("verify_","",$snip);

		$from_id_user = $update['callback_query']['from']['id'];

		if (!is_numeric($from_id_user)){
			displayError($url, $chat_id, $message_id, "Error 1");
		}
		if (!is_numeric($user_id_verify)){
			displayError($url, $chat_id, $message_id, "Error 2");
		}

		if (!$from_id_user){
			displayError($url, $chat_id, $message_id, "Error 3");
		}	

		if (!$user_id_verify){
			displayError($url, $chat_id, $message_id, "Error 4");
		}	

		if ($user_id_verify <> $from_id_user){
			displayBanner($url, $callback_id, "Only original joiner can verify.");
		}

			$reply = "Thanks! Your account is now verified.
			
	TIP: use /coins to see a list of CPU coins available to mine with.		

			";

			$postfields = array(
				'chat_id' => "$chat_id",
				'user_id' => "$from_id_user",
				'can_send_messages' => TRUE,
				'can_send_media_messages' => TRUE,
				'can_send_other_messages' => TRUE,
				'can_add_web_page_previews' => TRUE	
			);

			$resultUnrestrictMemebr = executeQuery($postfields, $url."/restrictChatMember", TRUE);			
			$resultUnrestrictMemebr = json_decode($resultUnrestrictMemebr, true);
			$resultUnrestrictMemebrOK = $resultUnrestrictMemebr['ok'];
			
			if ($resultUnrestrictMemebrOK <> 1){
				displayError($url, $chat_id, $message_id, "Error unrestricting user, please contact @KramWell");
				checkJSON("unrestrict.txt", $resultUnrestrictMemebr);
			}

		#display verified banner
		displayBanner($url, $callback_id, "Verified! \xE2\x9C\x85", TRUE);
			
		$postfields = array(
			'chat_id' => "$chat_id",
			'text' => "$reply",
			'message_id' => "$message_id"
		);		
		
	}
$executeQuery = TRUE;
}
##############################################################	
# DISPLAY START MESSAGE
##############################################################	
if ($snip == '/start@cputalk_bot' || $snip == '/start'){

$reply = "<b>!Welcome!</b>

Thanks for joining CPU Miners Club!	
	
Take a look around and ask anything you'd like (related of course). We like to discuss various CPU mining coins, which is best and currently most profitable.

TIP: use /coins to see a list of CPU coins available to mine with.

Thanks!
https://t.me/CPUtalk
";

		$postfields = array(
			'chat_id' => "$chat_id",
			'text' => "$reply",
			'message_id' => "$message_id"
		);	
$executeQuery = TRUE;
}

##############################################################	
#Show Coins
##############################################################
if ($snip == '/coins@cputalk_bot' || $snip == '/coins'){
$reply = "<b>Current list of CPU Minable coins:</b>\n
Please use the spreadsheet provided by -KaptainBlaZzed

https://docs.google.com/spreadsheets/d/1pkp8tfUK70Fs-HsdKL9PJpEcpcYhnMO0cKylyV8QVt0/edit?usp=sharing";
		
		$postfields = array(
			'chat_id' => "$chat_id",
			'message_id' => "$message_id",
			'text' => "$reply"
		);
$executeQuery = TRUE;	
}	

##############################################################
#REPLACE OR DISPLAY
##############################################################
if ($executeQuery == TRUE){
	if ($isCPUtalkBotId == $botID){ #this is my bot replying.	
		executeQuery($postfields, $url."/editMessageText".$addonSend);	
	}else{
				#LOGGING OF SENT TO TELEGRAM
				#checkJSON("test.txt", $update);
		executeQuery($postfields, $url."/sendMessage".$addonSend);
	}	
}else{
	if ($chat_id == "xxxxxxxxxxxx"){
		#postToDiscord($first_name, $username, $snip);	
	}
}
##############################################################
#EXECUTE QUERY - SEND TO CURL
##############################################################
function executeQuery($postfields, $urlToSend, $returnBack = FALSE){

	if (!$curld = curl_init()) {
	exit;
	}

	curl_setopt($curld, CURLOPT_POST, true);
	curl_setopt($curld, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($curld, CURLOPT_URL,$urlToSend);
	curl_setopt($curld, CURLOPT_RETURNTRANSFER, true); #seemed to speed things up?
	
	$outputFromTelegram = curl_exec($curld);

	curl_close ($curld);
	
	#checkJSON("outputFromExecute.txt", json_decode($outputFromTelegram, true));
	
	if ($returnBack == TRUE){
		RETURN $outputFromTelegram;
	}else{
		exit;
	}
}
##############################################################
# DISPLAY TOP BANNER TEXT
##############################################################
function displayBanner($url, $callback_id, $reply = 'ERROR', $returnBack = FALSE){
	$postfields = array(
		'callback_query_id' => "$callback_id",
		'text' => "$reply"
	);	
	if ($returnBack == TRUE){
		RETURN executeQuery($postfields, $url."/answerCallbackQuery", TRUE);
	}else{
		executeQuery($postfields, $url."/answerCallbackQuery");
	}
}
##############################################################
#DELETE MESSAGE
##############################################################
function deleteMessage($postfields, $urlToSend){

	if (!$curld = curl_init()) {
	exit;
	}

	curl_setopt($curld, CURLOPT_POST, true);
	curl_setopt($curld, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($curld, CURLOPT_URL,$urlToSend);
	#curl_setopt($curld, CURLOPT_RETURNTRANSFER, true); #seemed to speed things up?

	$output = curl_exec($curld);

	curl_close ($curld);
}

##############################################################
# FOR SENDING ERROR OUTPUT TO USER
##############################################################
function displayError($url, $chat_id, $message_id, $reply = 'ERROR'){
	$postfields = array(
		'chat_id' => "$chat_id",
		'message_id' => "$message_id",
		'text' => "$reply"
	);
executeQuery($postfields, $url."/sendMessage?disable_notification=TRUE&parse_mode=HTML&");	
}

##############################################################
#output all results if dumpResult() is called.
##############################################################
function checkJSON($myFile, $update){

	$updateArray = print_r($update,TRUE);
	$fh = fopen($myFile, 'a') or die("can't open file");
	fwrite($fh, $updateArray."\n\n");
	fclose($fh);
}

##############################################################
# sending to discord all chat info
##############################################################
/*
function postToDiscord($first_name, $username, $message)
{

/*
, 
		"embeds" => [[
			"title" => "via Telegram",
			"url" => "https://t.me/CPUtalk"
		]] 
*/
/*
	$hookObject = json_encode(
	[
	"content" => "$message", 
	"username" => "$first_name"
	], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );	
	
	#https://discordapp.com/api/webhooks/xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
    $data = array("content" => $message, "username" => "$username via @CPUtalk");
    $curl = curl_init("https://discordapp.com/api/webhooks/xxxxxxxxxxxxxxxxx");
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $hookObject);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    return curl_exec($curl);
}
*/
?>
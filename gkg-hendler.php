<?php

define( 'TOKEN', 'xxxxxxxxx:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' );
define( 'API_URL', 'https://api.telegram.org/bot'.TOKEN.'/' );
define( 'BOT_NAME', 'GitKraken Glo - Telegram Notify Bot!' );
define( 'ADMIN_ID', 11111111 );
define( 'LOG_FILE', 'dump.txt' );
define( 'LOG_REQUESTS', true );

/**
 * GitKraken Glo - Telegram Notify Bot
 *
 * @author   Samad Arshadi
 */
class GkgTbotWebhookHandler {
    
    public function execute() {
        if ( isset($_GET['set']) ) {
            $this->setTelegramWebhook();
        }else{
            $this->requestHandler();
        }
    }
    
    private function setTelegramWebhook() {
        $this->apiRequest('setWebhook', array('url' => $this->getFullUrl() ));
        die();
    }
    
	private function requestHandler() {
        $request = file_get_contents('php://input');
        
        if (!$request) {
    		return;
    	}
		
		$headers = $this->getHeaderList();
		
		$jsonData = json_decode($request, true);
		
		if (isset($jsonData["message"])) {
		    $this->logRequest($request, $headers, 'tel_');
            $this->botProcessMessage($jsonData["message"]);
        }else{
            $this->logRequest($request, $headers, 'gkg_');
            $this->requestEventHandler($headers, $jsonData);
        }
        
	}
	
	private function botProcessMessage($message) {
	    // process incoming message
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        if (isset($message['text'])) {
            // incoming text message
            $text = $message['text'];
    
            if (strpos($text, "/start") === 0) {
                $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Thanks, lets get start!\nPlease use /getme command to get your ID."));
            } else if (strpos($text, "/stop") === 0) {
                $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => 'Bot stoped!'));
            } else if (strpos($text, "/getme") === 0) {
                $userid = $message["from"]["id"];
                $text = "Your user ID is: $userid";
                $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => $text));
            } else {
                $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "Please follow install instructure to get start!\nMore feature and commands coming soon.\nThanks!"));
            }
        } else {
            $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => 'I understand only text messages'));
        }
	}
	
	private function requestEventHandler($headers, $jsonData) {
	    $message = $this->eventMainDetails($headers, $jsonData);
		$this->sendUpdateMessage($message);
	}
	
	private function eventMainDetails($headers, $jsonData) {
	    $event = ucfirst($headers['X-Gk-Event']);
		
		$board = ucfirst($jsonData['board']['name']);
		$action = ucfirst($jsonData['action']);
		$sequence = $jsonData['sequence'];
		
		$user = $jsonData['sender']['name'];
		$username = $jsonData['sender']['username'];
		
		$message = BOT_NAME . "\n\n";
		$message .= "Board Name: $board\nEvent Type: $event\nAction: $action\nUser: $user - $username\nSequence Number: #$sequence\n\n";
		$message .= "Event Details:\n\n";
		
		$message .= $this->eventExtraDetails($jsonData);
		
		return $message;
	}
	
	private function eventExtraDetails($jsonData) {
		$extraDetails = '';
		
		if( isset($jsonData['board']) ){
		    $extraDetails .= $this->parseEventDetails($jsonData['board']);
		}
		if( isset($jsonData['card']) ){
		    $extraDetails .= $this->parseEventDetails($jsonData['card']);
		}
		if( isset($jsonData['comment']) ){
		    $extraDetails .= $this->parseEventDetails($jsonData['comment']);
		}
		if( isset($jsonData['column']) ){
		    $extraDetails .= $this->parseEventDetails($jsonData['column']);
		}
		if( isset($jsonData['labels']) ){
		    $extraDetails .= $this->parseEventDetails($jsonData['labels']);
		}
		if( isset($jsonData['assignees']) ){
		    $extraDetails .= $this->parseEventDetails($jsonData['assignees']);
		}
		if( isset($jsonData['members']) ){
		    $extraDetails .= $this->parseEventDetails($jsonData['members']);
		}
		
		return $extraDetails;
	}
	
	private function sendUpdateMessage($message) {
	    $method = 'SendMessage';
		$parameters = array('chat_id' => ADMIN_ID, 'text' => $message, 'parse_mode' => 'MARKDOWN', 'disable_web_page_preview' => 'true' );
		
		$this->apiRequest($method, $parameters);
		die();
	}
	
	private function parseEventDetails(array $array, $path = null) {
        $filter = array('id', 'column_id', 'board_id', 'created_date', 'color', 'updated_date', 'due_date', 'archived_date', 'created_by');
        $out = '';
        foreach ($array as $k => $v) {
            if( !in_array($k, $filter, true) ){
                if (!is_array($v)) {
                    $fullpath = "$path $k : $v";
                    $out .= "$fullpath\n";
                }
                else {
                    $out .= $this->parseEventDetails($v, "$path - $k");
                }
            }
        }
        return $this->cleanEventDetails($out);
    }
    
    private function cleanEventDetails($data) {
        $find = array('name', 'description', 'text', 'labels', 'assignees', 'completed_task_count', 'total_task_count', 'attachment_count', 'comment_count', 'previous', 'members', 'invited_members', 'added', 'updated', 'username', 'role', 'invited', 'joined', 'removed', 'card_counts', 'unarchived', 'archived', 'pre_archived_cards', 'position');
        $replace = array('Name', 'Description', 'Text', 'Labels', 'Assignees', 'Completed Task Count', 'Total Task Count', 'Attachment Count', 'Comment Count', 'Previous', 'Members', 'Invited Members', 'Added', 'Updated', 'Username', 'Role', 'Invited', 'Joined', 'Removed', 'Card Counts', 'Unarchived', 'Archived', 'Pre Archived Cards', 'Position');
        return str_replace($find, $replace, $data);
    }
    
    private function apiRequest($method, $parameters) {
        if (!is_string($method)) {
            error_log("Method name must be a string\n");
            return false;
        }
        
        if (!$parameters) {
            $parameters = array();
        } else if (!is_array($parameters)) {
            error_log("Parameters must be an array\n");
            return false;
        }
        
        foreach ($parameters as $key => &$val) {
            // encoding to JSON array parameters, for example reply_markup
            if (!is_numeric($val) && !is_string($val)) {
                $val = json_encode($val);
            }
        }
        $url = API_URL.$method.'?'.http_build_query($parameters);
        
        file_get_contents($url);
    }
    
    private function apiRequestWebhook($method, $parameters) {
        if (!is_string($method)) {
          error_log("Method name must be a string\n");
          return false;
        }
    
        if (!$parameters) {
          $parameters = array();
        } else if (!is_array($parameters)) {
          error_log("Parameters must be an array\n");
          return false;
        }
    
        $parameters["method"] = $method;
    
        header("Content-Type: application/json");
        echo json_encode($parameters);
        return true;
    }
    
    private function getFullUrl(){
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $link = "https"; 
        }else{
            $link = "http"; 
        }
        
        // Here append the common URL characters. 
        $link .= "://"; 
        
        // Append the host(domain name, ip) to the URL. 
        $link .= $_SERVER['HTTP_HOST']; 
        
        // Append the requested resource location to the URL 
        $link .= $_SERVER['REQUEST_URI']; 
          
        // Return the link 
        return strtok($link,'?');
    }
    
    private function logRequest($request, $headers, $prefix) {
        if( LOG_REQUESTS ){
            
            $logFile = $prefix . LOG_FILE;
            
            if (file_exists($logFile)) {
                $data = file_get_contents($logFile);
            } else {
                $data = '';
            }
            
    		$data .= sprintf(
    			"%s %s %s\n\nHTTP headers:\n",
    			$_SERVER['REQUEST_METHOD'],
    			$_SERVER['REQUEST_URI'],
    			$_SERVER['SERVER_PROTOCOL']
    		);
    		
    		foreach ($headers as $name => $value) {
    			$data .= $name . ': ' . $value . "\n";
    		}
    		
            $data .= "\nRequest body:\n";
            
    		file_put_contents(
    			$logFile,
    			$data . $request . "\n"
    		);
        }
    }
    
	private function getHeaderList() {
		$headerList = [];
		foreach ($_SERVER as $name => $value) {
			if (preg_match('/^HTTP_/',$name)) {
				// convert HTTP_HEADER_NAME to Header-Name
				$name = strtr(substr($name,5),'_',' ');
				$name = ucwords(strtolower($name));
				$name = strtr($name,' ','-');
				// add to list
				$headerList[$name] = $value;
			}
		}
		return $headerList;
	}
	
}

(new GkgTbotWebhookHandler)->execute();
<?php namespace App\Controllers;
// use CodeIgniter\Controller;
// use App\Libraries\Telegram;

class Telegram_controller extends BaseController{

	private $token = "1456393662:AAE6ckb3yoCRRoR9EOi-aB3GvcsRDhvTUJQ",
			$offset_file = "offset.txt",
			$url="https://api.telegram.org/bot1456393662:AAE6ckb3yoCRRoR9EOi-aB3GvcsRDhvTUJQ/";

	public function index(){
		echo 'hi new';
	}

	private function loadUrl($url = "https://tg.kia24.com/public", $params=array()){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		if(!empty($params)){
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		if (curl_errno($ch)) { 
			print curl_error($ch); 
		}
		$result = curl_exec($ch);
		$result = json_decode($result);
		curl_close($ch);
		return $result; 
	}

	private function renderForTelegram($content, $data){
		$result = array();
		$result['data'] = $data;
		if(isset($content->text)){
			$text_reply = $content->text;
			$result["text"] = $text_reply;
		}
		if(isset($content->keyboards)){
			$keyboard = $this->renderKeyboard($content->keyboards);
			$result['keyboard'] = $keyboard;
		}
		// if(isset($content->default)){
		// 	$keyboard = $this->renderInlineButton($content->default);
		// 	$result['keyboard'] = $keyboard;
		// }
		if(isset($content->categories)){
			$inlineKeyboard = $this->renderInlineButton($content->categories);
			$result["inlineKeyboard"] = $inlineKeyboard;
		}
		if(isset($content->products)){
			$media = $this->renderMedia($content->products);
			$result["media"] = json_encode($media);
		}
		return $result;
	}
	
	private function renderKeyboard($content){ // not yet
		$final = array();
		for($i = 0 ; $i < count($content); $i+=2){
			$temp=array();
			if(isset($content[$i])){
				$temp[] = array('text'=>$content[$i]->title);
			};
			if(isset($content[$i+1])){
				$temp[] = array('text'=>$content[$i+1]->title);
			};
			$final['keyboard'][] = $temp;
		}
		$final['one_time_keyboard'] = false;
		$final['resize_keyboard'] = true;
		$final['hide_keyboard'] = false;
		
		return json_encode($final);
	}

	public function renderInlineButton($content=''){
		$keyboardArray = array();
		for($i = 0 ; $i < count($content); $i+=2){
			$temp=array();
			if(isset($content[$i])){
				$temp[] = array("text" => $content[$i]->title, "callback_data" => $content[$i]->link);
			};
			if(isset($content[$i+2])){
				$temp[] = array("text" => $content[$i+1]->title, "callback_data" => $content[$i+1]->link);
			};
			$keyboardArray[] = $temp;
		}
		$inlineKeyboard = array(
			"inline_keyboard" => $keyboardArray
		);
		$inlineKeyboard = json_encode($inlineKeyboard);
		return $inlineKeyboard;
	}

	public function renderMedia($content=''){
		$media = array();
		$i = 0;
		if(isset($content[$i])){
			for($i = 0; $i < count($content); $i++){
				// $media[] = array('type' => 'photo', 'media' => $content[$i]->picture, 'caption' => $content[$i]->title, 'link' => $content[$i]->link);
				$media[] = array(
					'type' => 'photo',
					'id' => $content[$i]->id !== NULL ? $content[$i]->id : '',
					'title' => $content[$i]->title !== NULL ? $content[$i]->title : '',
					'varient' => $content[$i]->varient !== NULL ? $content[$i]->varient : '',
					'qnt' => $content[$i]->qnt !== NULL ? $content[$i]->qnt : '',
					'media' => $content[$i]->picture,
					'price' => $content[$i]->price !== NULL ? $content[$i]->price : '',
					'caption' => $content[$i]->title !== NULL ? $content[$i]->title : '',
					// 'link' => $content[$i]->link !== NULL ? $content[$i]->link : '',
				);
			}
		}
		return $media;
	}

	// public function renderBasket(){
	// 	$basket = array();
	// 	$i = 0;
	// 	if(isset($content[$i])){
	// 		for($i=0; $i < count($content); $i++){
	// 			$basket[] = array('type' => 'media')
	// 		}
	// 	}
	// }

	public function getUpdates(){
		$offset = file_exists(FCPATH.$this->offset_file) ? file_get_contents($this->offset_file) : 0;
		$this->offset = $offset;
		$result = $this->loadUrl($this->url."getUpdates?offset=".$this->offset);
		
		if(isset($result)){
			foreach($result->result as $items){
					$this->responseToMessage($result);
			}
		}
	}

	public function replyToTelegram($data){	
		$text = isset($data['text']) ? str_replace(PHP_EOL, '', $data['text']) : '';
		$keyboard = isset($data['keyboard']) ? $data['keyboard'] : '';
		$inlineKeyboard = isset($data['inlineKeyboard']) ? str_replace(PHP_EOL, '', $data['inlineKeyboard']) : '';
		$media = isset($data['media']) ? $data['media'] : '';
		$chat_id = isset($data['data']['chat_id']) ? str_replace(PHP_EOL, '', $data['data']['chat_id']) : NULL;
		
		var_dump($text, $keyboard, $media, $inlineKeyboard, $chat_id); 
		
		if(isset($keyboard)){
			$url = "https://api.telegram.org/bot".$this->token."/sendMessage?chat_id=".$chat_id."&text=".$text."&reply_markup=".$keyboard;
			$this->loadUrl($url);
		}
		// if(isset($media)){
		// 	$this->loadUrl("https://api.telegram.org/bot".$this->token."/sendMediaGroup?chat_id=".$chat_id."&media=".$media);
		// }
		// if(isset($photo)){
		// 	$this->loadUrl("https://api.telegram.org/bot".$this->token."/sendMediaGroup?chat_id=".$chat_id."&photo=".$photo);
		// }	
		// if(isset($inlineKeyboard)){
		// 	$this->loadUrl("https://api.telegram.org/bot".$this->token."/sendMessage?chat_id=".$chat_id."&text=".$text."&reply_markup=".$inlineKeyboard);
		// }
	}

	public function responseToMessage($sendMessage){
		$data = array();
		foreach($sendMessage->result as $items){
			if(isset($items->callback_query)){
				$data['callback_query_id'] = $items->callback_query->id;
				$data['text'] = $items->callback_query->data;
				$data['chat_id'] = $items->callback_query->message->chat->id;
				$this->loadUrl($this->url."/answerCallbackQuery?callback_query_id=".$items->callback_query->id);
				echo ('call back answered:'.$items->callback_query->id);
				$this->offset++;
			}
			else{
				$data['text'] = isset($items->message->text) ? $items->message->text: '';
			}
			$data['update_id'] = isset($items->update_id) ? $items->update_id : '';
			$data['message_id'] = isset($items->message->message_id) ? $items->message->message_id: '';
			$data['from_id'] = isset($items->message->from->id) ? $items->message->from->id: '';
			$data['first_name'] = isset($items->message->from->first_name) ?$items->message->from->first_name: '';
			$data['username'] = isset($items->message->from->username) ? $items->message->from->username: '';
			$data['chat_id'] =isset($items->message->chat->id) ? $items->message->chat->id: '';
			$data['chat_type'] = isset($items->message->chat->type) ? $items->message->chat->type: '';
			$data['date'] = isset($items->message->date) ? $items->message->date: '';	
			if(isset($items->message->entities)){
				foreach($items->message->entities as $item){
					$data['entities'][] = $item;
				}
			}

			$apiUrl  = array(
				'hello' => "https://tg.kia24.com/public/api/defaultMessage",
				'hi' => "https://tg.kia24.com/public/api/defaultMessage",
				'/start' =>  "https://tg.kia24.com/public/api/getWelcomeScreen",
				'getCategories'=> "https://tg.kia24.com/public/api/getCategories",
				'دسته محصولات'=> "https://tg.kia24.com/public/api/getCategories",
				'contact'=> "https://tg.kia24.com/public/api/contact",
				'تماس با ما'=> "https://tg.kia24.com/public/api/contact",
				'basket'=> "https://tg.kia24.com/public/api/showBasket",
				'سبد خرید'=> "https://tg.kia24.com/public/api/showBasket",
				'tracking'=> "https://tg.kia24.com/public/api/tracking",
				'پیگیری سفارش'=> "https://tg.kia24.com/public/api/tracking"
			);

			$renderedContent = $url = '';
			if(isset($apiUrl[$data["text"]])){
				$url = $apiUrl[$data["text"]];
				$content = $this->loadUrl($url);
				if(isset($content->content)){
					$renderedContent = $this->renderForTelegram($content->content, $data);
					$this->replyToTelegram($renderedContent);
					$this->offset++;
				}
			}
			$last_update_id = $items->update_id;
		}
		if($last_update_id != 0)
			file_put_contents(FCPATH.$this->offset_file, $this->offset);
			
		echo ('updated :'.$this->offset);
	}
}

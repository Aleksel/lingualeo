<?php
	define('AUTH', "lingualeo.com/api/login"); //Адрес для аутинтификации
	define('WORD', "api.lingualeo.com/gettranslates"); //Адрес для отправки слов
	define('DEVICE_FORM', "\r\n"); //разделитель слов в форме
	define('DEVICE_FILE', "|"); //разделитель слов в файле
	define('FILE', "words.txt"); //Файл для записи
	define('USER_NAME', "omatic"); //Логин пользователя на сайте lingualeo
	define('USER_PASSWORD', ""); //Пароль пользователя на сайте lingualeo

	//Создаем соединение для отправки запроса
	class DataPost{
		private $url;
		private $post;
		private $ch;
		private function getConnect(){
			$this->ch = curl_init();
			curl_setopt($this->ch, CURLOPT_URL,$this->url);
			//curl_setopt ($this->ch, CURLOPT_VERBOSE, 2); // Отображать детальную информацию о соединении
			curl_setopt ($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); //Прописываем User Agent, чтобы приняли за своего
			curl_setopt ($this->ch, CURLOPT_COOKIEJAR, "cookie.txt");
			curl_setopt ($this->ch, CURLOPT_COOKIEFILE, "cookie.txt");
			curl_setopt ($this->ch, CURLOPT_POSTFIELDS, $this->post);
			curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, 1);  // Возвращать результат
			curl_setopt ($this->ch, CURLOPT_HEADER, 1); // Наши заголовочки
			curl_setopt ($this->ch, CURLINFO_HEADER_OUT, 1); // Где то наткнулся на этот параметр, решил оставить
			curl_setopt ($this->ch, CURLOPT_CONNECTTIMEOUT, 60);
			return curl_exec($this->ch);
		}
		function __destruct(){
			if ($this->ch)
				curl_close($this->ch);
		}
		
		function sendPost($url,$post=""){
			$this->url = $url;
			if (WORD===($this->url))
				$this->post = "word=".$post."&include_media=1&add_word_forms=1&port=1002";
			else{
				$this->post = "port=1002";
				$result = $this->getConnect();
				$res = json_decode(substr($result,stripos($result,'{"error_msg"'),strlen($result)),true);
				$this->post = "email=".USER_NAME."&password=".USER_PASSWORD."&port=1002";
			}
			$result = $this->getConnect();
			$res = json_decode(substr($result,stripos($result,'{"error_msg"'),strlen($result)),true);
			return $res;
		}
		function getData() {
			if ("POST"===$_SERVER['REQUEST_METHOD']){
				define('PATH', $_POST['user']);
				if (file_exists(FILE)){
					date_default_timezone_set('Europe/Moscow');
					rename(FILE,"words".date("d_m_Y H_i",time()).".txt");
				}
				$this->sendPost(AUTH);
				$words = explode(DEVICE_FORM,strip_tags(trim($_POST['thetext'])));
				foreach ($words as $word){
					unset($out);
					$i=0;
					$res = $this->sendPost(WORD,$word);
					$out["word"] = $word;
					//Копируем картинку в папку Lingialeo/pic и в папку Anki
					$path = '.\\lingualeo\\pic\\'.$word.".png";
					file_put_contents($path, file_get_contents($res["pic_url"]));
					$out["pic"] = str_replace("\\","/",($_SERVER["DOCUMENT_ROOT"].substr(($path),1)));
					
					$path = 'c:\\Users\\'.PATH.'\\Documents\\Anki\\Andrey\\collection.media\\'.$word.".png";
					copy($out["pic"],$path);
					//file_put_contents($path, file_get_contents($res["pic_url"]));
					
					
					$out["transcription"] = $res["transcription"];
					
					//Копируем mp3 в папку Lingialeo/sound и в папку Anki
					$path = '.\\lingualeo\\sound\\'.$word.".mp3";
					file_put_contents($path, file_get_contents($res["sound_url"]));
					$out["sound"] = str_replace("\\","/",($_SERVER["DOCUMENT_ROOT"].substr(($path),1)));;
					
					$path = 'c:\\Users\\'.PATH.'\\Documents\\Anki\\Andrey\\collection.media\\'.$word.".mp3";
					copy($out["sound"],$path);
					//file_put_contents($path, file_get_contents($res["sound_url"]));
					
					foreach ($res["translate"] as $item){
						if ($item["votes"]>50){
							if ($i){
								$out["translate"].= "; ".$item["value"];
								$out["votes"].= "; ".$item["votes"];
							}
							else{
								$out["translate"] = $item["value"];
								$out["votes"] = $item["votes"];
								$i=1;
							}
						}
						else
							if (!isset($out["translate"])) 
								$out["translate"]="";
					}
					$file = $out["word"].DEVICE_FILE;
					$file.= $out["translate"].DEVICE_FILE;
					$file.= $out["transcription"].DEVICE_FILE;
					$file.= "'".$out["word"].".png"."'".DEVICE_FILE;
					$file.= '[sound:'.$out["word"].'.mp3]'.DEVICE_FILE;
					$file.= $out["sound"]."\r\n";
					file_put_contents(FILE,$file,FILE_APPEND );
				}
			}
		}
	}
	
		
	$get = new DataPost();
	$get->getData();

?>
<!DOCTYPE html>
<html>
<head>
	<title>Получение слов</title>
	<meta charset="utf-8">
</head>
<body>
	<form action="lingualeo.php" method="post">
		<textarea name="thetext" rows="20" cols="80">Вводим слова построчно</textarea><br>
		<input name="user" value="Keiser"/>Имя пользователя Windows<br>
		<input type="submit" value="Send"/>
	</form>
	
	
</body>
</html>
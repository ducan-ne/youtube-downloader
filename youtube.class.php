<?php
class YOUTUBE{
	public	$_config, 
			$videoInfo = '', 
			$typeMap = array(
				//2D Non-Dash
				17 => '3GP; 144p',
				36 => '3GP; 240p',
				5  => 'FLV; 240p',
				18 => 'MP4;	360p',
				22 => 'MP4;	720p',
				43 => 'WebM; 360p',
				//3D Non-Dash
				82 => 'MP4;	360p',
				83 => 'MP4;	240p',
				84 => 'MP4;	720p',
				85 => 'MP4;	1080p',
				100 => 'WebM; 360p',
				//DASH (video)
					//MP4
					160 => 'MP4; 144p',
					133 => 'MP4; 240p',
					134 => 'MP4; 360p',
					135 => 'MP4; 480p',
					136 => 'MP4; 720p',
					298 => 'MP4; 720p HFR',
					137 => 'MP4; 1080p',
					299 => 'MP4; 1080p HFR',
					264 => 'MP4; 1440p',
					266 => 'MP4; 2160p–2304p',
					138 => 'MP4; 2160p–4320p',
					//WebM
					278 => 'WebM; 144p',
					242 => 'WebM; 240p',
					243 => 'WebM; 360p',
					244 => 'WebM; 480p',
					247 => 'WebM; 720p',
					248 => 'WebM; 1080p',
					271 => 'WebM; 1440p',
					313 => 'WebM; 2160p',
					302 => 'WebM; 720p HFR',
					303 => 'WebM; 1080p HFR',
					308 => 'WebM; 1440p HFR',
					315 => 'WebM; 2160p HFR',
				//DASH (audio)
				140 => 'M4A; 128Kbps',
				141 => 'M4A; 256Kbps',
				171 => 'WebM; 128Kbps',
				249 => 'WebM; 48Kbps',
				250 => 'WebM; 64Kbps',
				251 => 'WebM; 160kbps'
			);
	public function __construct($config = array()){
		$this->_config = $config;
	}
	public function _Download($token = false){
		if($token){
			$token = json_decode(base64_decode($token));
			if(isset($token->title)){
				$file_size = get_headers($token->url, true);
				$file_size = $file_size['Content-Length'][count($file_size['Content-Length']) - 1];
				$ext = explode(';', $token->itag);
				$ext = strtolower($ext[0]);
				if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE){
					header('Content-Type: "'.$token->mime.'"');
					header('Content-Disposition: attachment; filename="'.$token->title.'.'.$ext.'"');
					header('Expires: 0');
					header('Content-Length: '.$file_size);
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header("Content-Transfer-Encoding: binary");
					header('Pragma: public');
				}else{
					header('Content-Type: "'.$token->mime.'"');
					header('Content-Disposition: attachment; filename="'.$token->title.'.'.$ext.'"');
					header("Content-Transfer-Encoding: binary");
					header('Expires: 0');
					header('Content-Length: '.$file_size);
					header('Pragma: no-cache');
				}
				readfile($token->url);
				exit();
			}else{
				exit('Invaild download Token');
			}
		}else{
			exit('Empty download Token');
		}
		
	}
	public function _genDownload($json = false){
		if($this->videoInfo != '' && !isset($this->videoInfo->errorcode)){
			//print_r($this->videoInfo->sig);
			foreach(explode(',',$this->videoInfo->adaptive_fmts) as $stream){
				parse_str($stream, $video);
				$video['url'] = preg_replace('/\/\/(.*?)\.googlevideo\.com\/(.*?)/', '//redirector.googlevideo.com/$2', $video['url']).'&title='.$this->videoInfo->title;
				$urlInfo = (object) parse_url($video['url']);
				parse_str($urlInfo->query, $urlDetail);
				//print_r($urlDetail);
				$video['mime'] = $urlDetail['mime'];
				$video['expire'] = $urlDetail['expire'];
				$video['type'] = preg_replace('/(.*?); (.*)/', '$1', $video['type']);
				$video['itag'] = $this->typeMap[$video['itag']] ? $video['itag'] : 0;
				$video['title'] = $this->videoInfo->title;
				$return[] = (object) $video;
			}
		}else{
			if($this->videoInfo->errorcode){
				$return = (array) $this->videoInfo;
			}else{
				$return = array(
					'errorcode' => 404,
					'status' => 'fail',
					'reason' => 'Not found video info, check again'
				);
			}
		}
		return $json ? json_encode($return) : $return;
	}
	public function _getInfoVideo($id, $json = false){
		$this->videoInfo = $this->_cURL('https://www.youtube.com/get_video_info?html5=1&video_id='.trim($id).'&cpn=pHOEwSK9WiHazA2t&eurl=http://video.genyoutube.net/z0A3hvfpN-0&el=embedded&hl=vi_VN&sts=17007&lact=108&autoplay=1&width=780&height=439&authuser=3&ei=kxGeV7rCFJG34AKBoobgDA&iframe=1&c=WEB&cver=1.20160728&cplayer=UNIPLAYER&cbr=Chrome&cbrver=51.0.2704.103&cos=Windows&cosver=10.0', false, 'str');
		return ($json) ? json_encode($this->videoInfo) : $this->videoInfo;
	}
	protected function _cURL($url, $postArray = false, $parse = false){
		$s = curl_init();
		$opts = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FRESH_CONNECT => true
		);
		if($postArray){
			$opts[CURLOPT_POST] = true;
			$opts[CURLOPT_POSTFIELDS] = $postArray;
		}
		curl_setopt_array($s, $opts);
		$return = curl_exec($s);
		curl_close($s);
		if($parse != false){
			if($parse == 'json'){
				$return = json_decode($return);
			}else if($parse == 'str'){
				parse_str($return, $nreturn);
				$return = (object) $nreturn;
			}
		}
		return $return;
	}
}

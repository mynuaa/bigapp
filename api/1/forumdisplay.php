<?php
/**
* @file forumdisplay.php
* @Brief 
* @author youzu
* @version 1
* @date 2015-04-03
*/
if(!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}
class BigAppAPI {

	static protected $collect = array(); 

	function common() {
		
		global $_G;
		if(true === BigAppConf::$debug){
			$_G['trace'][] = __CLASS__ . '::' . __FUNCTION__;
		}
		if(!empty($_GET['pw'])) {
			$_GET['action'] = 'pwverify';
		}
		$_G['forum']['allowglobalstick'] = false;
		if(isset($_G['forum']['picstyle']) && $_G['forum']['picstyle'] && isset($_G['tpp']) && is_numeric($_G['tpp']) && intval($_G['tpp']) >= 1){
			$_G['setting']['forumpicstyle']['thumbnum'] = intval($_G['tpp']);
		}
		if(isset($_GET['digest']) && $_GET['digest']){
			$_SERVER['QUERY_STRING'] = 'mod=forumdisplay&' . $_SERVER['QUERY_STRING'];
		}
		if(isset($_G['setting']['bigapp_settings'])){
            $tmp = unserialize($_G['setting']['bigapp_settings']);
        }
        if(!isset($tmp['enable_pic_opt'])){
            $tmp['enable_pic_opt'] = 1;   
        }
        BigAppConf::$enablePicOpt = (!!$tmp['enable_pic_opt']);	
	}

	function output() {
		
		global $_G;
		if(true === BigAppConf::$debug){
			$_G['trace'][] = __CLASS__ . '::' . __FUNCTION__;
		}
		$needMore = 1;
		
		if('1' == $_G['page'] && count($_G['forum_threadlist']) < $_G['tpp']) {
			$needMore = 0;
		}
		
		$total = $_G['forum_threadcount'];
		$start = $_G['tpp'] * ($_G['page'] - 1);
		
		if($start + count($_G['forum_threadlist']) >= $total){
			$needMore = 0;
		}
		$tids = array();
		$threadList = array();
		BigAppAPI::_getDetails($_G['forum_threadlist']);
		foreach($_G['forum_threadlist'] as $k => $thread) {
			$_G['forum_threadlist'][$k]['tid'] = $thread['icontid'];
			if($thread['displayorder'] > 0) {
				unset($_G['forum_threadlist'][$k]);
				continue;
			}
			if(!isset($_G['cache']['stamps'][$thread['icon']])){
				$_G['forum_threadlist'][$k]['icon'] = -1;
			}
			$typeIcon = '';
			if(isset($_G['forum']['threadtypes']['icons'][$thread['typeid']])){
				$typeIcon = $_G['forum']['threadtypes']['icons'][$thread['typeid']];
			}
			$tids[] = $thread['tid'];
			$threadList[$thread['tid']] = $_G['forum_threadlist'][$k];
			$threadList[$thread['tid']]['avatar'] = avatar($_G['forum_threadlist'][$k]['authorid'], 'big', 'true');
			$threadList[$thread['tid']]['avatar'] = str_replace("\r", '', $threadList[$thread['tid']]['avatar']);
			$threadList[$thread['tid']]['avatar'] = str_replace("\n", '', $threadList[$thread['tid']]['avatar']);
			$threadList[$thread['tid']]['lastpost'] = str_replace('&nbsp;', '', $threadList[$thread['tid']]['lastpost']);
			$threadList[$thread['tid']]['dateline'] = str_replace('&nbsp;', '', $threadList[$thread['tid']]['dateline']);
			$threadList[$thread['tid']]['dateline'] = preg_replace('/<.*?\>/', '', $threadList[$thread['tid']]['dateline']);
			$threadList[$thread['tid']]['typeicon'] = $typeIcon;
			$threadList[$thread['tid']]['forum_name'] = isset($_G['forum']['name'])?$_G['forum']['name']:"";
			if(!isset($thread['subject'])){
				$threadList[$thread['tid']]['subject'] = "";//hack
			} else {			
				//filter &quot
			   $threadList[$thread['tid']]['subject'] = str_replace('&quot;', '"', $thread['subject']);
			}
		}
		$_G['forum_threadlist'] = array_values($threadList);
		$moderators = explode("\t", $_G['forum']['moderators']);
		if(!is_array($moderators) || 0 === count($moderators)){
			$_G['forum']['moderators'] = array();
		}else{
			foreach ($moderators as &$_v){
				$_v = "'${_v}'";
			}
			unset($_v);
			$sql = 'SELECT username, uid FROM ' . DB::table('common_member') . ' WHERE username IN (' . implode(', ', $moderators) . ')';
			$subQuery = DB::query($sql);
			$_G['forum']['moderators']  = array();
			while($moderator = DB::fetch($subQuery)){
				$_G['forum']['moderators'][] = array('uid' => $moderator['uid'], 'username' => $moderator['username']);
			}
		}
		$variable = array(
			'forum' => bigapp_core::getvalues($_G['forum'], array('fid', 'fup', 'name', 
					'threads', 'posts', 'rules', 'autoclose', 'password', 'todayposts', 'yesterdayposts', 'moderators', 'allowspecialonly')),
			'group' => bigapp_core::getvalues($_G['group'], array('groupid', 'grouptitle')),
			'open_image_mode' => 1,
			'forum_threadlist' => bigapp_core::getvalues(array_values($_G['forum_threadlist']), array('/^\d+$/'), 
					array('fid', 'tid', 'author', 'authorid', 'subject', 'dbdateline', 'dateline', 'dblastpost', 
					'lastpost', 'lastposter', 'attachment', 'replies', 'readperm', 'views', 'heats', 'icon', 
					'threadimage', 'avatar', 'message_abstract', 'attachment_urls', 'typeid', 'typename', 'typeicon', 'mobile', 'highlight', 'digest','forum_name')),
			'sublist' => bigapp_core::getvalues($GLOBALS['sublist'], array('/^\d+$/'), array('fid', 'name', 'threads', 'todayposts', 'posts')),
			'tpp' => $_G['tpp'],
			'page' => $GLOBALS['page'],
			'need_more' => $needMore,
		);
		$variable['activity_config'] = array(
			'allowpostactivity' => 0,
			'credit_title' => '',
			'activitytype' => array(),
			'activityfield' => array(),
			'activityextnum' => 0,
			'activitypp' => 1,
		);
		if(isset($_G['group']['allowpostactivity']) && $_G['group']['allowpostactivity'] && ($_G['forum']['allowpostspecial'] & 8)){
			$variable['activity_config']['allowpostactivity'] = 1;
			if(isset($_G['setting']['activitycredit']) && isset($_G['setting']['extcredits'][$_G['setting']['activitycredit']]['title'])){
				$variable['activity_config']['credit_title'] = $_G['setting']['extcredits'][$_G['setting']['activitycredit']]['title'];
			}
			if(isset($_G['setting']['activitytype'])){
				$variable['activity_config']['activitytype'] = explode("\r\n", $_G['setting']['activitytype']);
			}
			if(isset($_G['setting']['activityfield'])){
				$variable['activity_config']['activityfield'] = unserialize($_G['setting']['activityfield']);
				$tmp = array();
				foreach ($variable['activity_config']['activityfield'] as $k => $v){
					$tmp[] = array('fieldid' => $k, 'fieldtext' => $v);
				}
				$variable['activity_config']['activityfield'] = $tmp;
			}
			if(isset($_G['setting']['activityextnum'])){
				$variable['activity_config']['activityextnum'] = $_G['setting']['activityextnum'];
			}
			if(isset($_G['setting']['activitypp'])){
				$variable['activity_config']['activitypp'] = $_G['setting']['activitypp'];
			}

		}
		$variable['poll_config']['allowpostpoll'] = 0;
		if(isset($_G['group']['allowpostpoll'])){
			$variable['poll_config']['allowpostpoll'] = intval($_G['group']['allowpostpoll']);
		}
		if(isset($_G['setting']['maxpolloptions']) && $_G['setting']['maxpolloptions'] > 0){
			$variable['poll_config']['maxpolloptions'] = intval($_G['setting']['maxpolloptions']);
		}else{
			$variable['poll_config']['maxpolloptions'] = 0;
			$variable['poll_config']['allowpostpoll'] = 0;
		}
		if(isset($_G['setting']['bigapp_settings']['threadlist_image_mode']) && !$_G['setting']['bigapp_settings']['threadlist_image_mode']){
			$variable['open_image_mode'] = 0;
		}
		if(!empty($_G['forum']['threadtypes']) || !empty($_GET['debug'])) {
			$variable['threadtypes'] = $_G['forum']['threadtypes'];
			unset($variable['threadtypes']['types']);
			foreach ($_G['forum']['threadtypes']['types'] as $typeId => $typeValue){
				$typeValue = preg_replace('/<.*?>/', '', $typeValue);
				$variable['threadtypes']['types'][] = array('typeid' => $typeId, 'typename' => $typeValue);
			}
			unset($variable['threadtypes']['icons']);
			foreach ($_G['forum']['threadtypes']['icons'] as $typeId => $icon){
				$variable['threadtypes']['icons'][] = array('typeid' => $typeId, 'typeicon' => $icon);
			}
		}
		if(!empty($_G['forum']['threadsorts']) || !empty($_GET['debug'])) {
			$variable['threadsorts'] = $_G['forum']['threadsorts'];
		}
		foreach ($variable['forum_threadlist'] as &$tl){
			if(!isset($tl['message_abstract'])){
				$tl['message_abstract'] = '';
			}
			if(!isset($tl['attachment_urls'])){
				$tl['attachment_urls'] = array();
			}
			$tl['typename'] = preg_replace('/<.*?>/', '', $tl['typename']);
            $tl['author_display'] = $tl['author'];
		}
		unset($tl);
		$variable['forum']['password'] = $variable['forum']['password'] ? '1' : '0';
		$variable['forum']['icon'] = '';
		if(!empty($_G['forum']['icon'])){
			$variable['forum']['icon'] = ApiUtils::getDzRoot() . $_G['setting']['attachurl'] . 'common/' . $_G['forum']['icon'];
		}
		if(isset($variable['threadtypes']) && 
			(empty($variable['threadtypes']) || 
			 (isset($variable['threadtypes'][0]) && empty($variable['threadtypes'][0]) ) ||
			 (isset($variable['threadtypes']['types']) && empty($variable['threadtypes']['types']) ) ||
			 (isset($variable['threadtypes']['icons']) && empty($variable['threadtypes']['icons']) ) 
			)
		){
			unset($variable['threadtypes']);
		}
		bigapp_core::result(bigapp_core::variable($variable));
	}

	protected function _getPictures(&$threadInfo)
	{
		global $_G;
		$collect = &BigAppAPI::$collect;
		foreach ($threadInfo as $tid => &$info){
			$collect = array();
			$message = str_replace("\r", '', $info['message']);
			$message = str_replace("\n", '', $message);
			$message = str_replace('\r', '', $message);
			$message = str_replace('\n', '', $message);
			if(function_exists('iconv')){
				$message = iconv(CHARSET, 'UTF-8//ignore', $message);
			}else{
				$message = mb_convert_encoding($message, 'UTF-8', CHARSET);
			}
			$message = preg_replace_callback('/\[img.*?\](.*?)\[\/img\]|\[attach\]([0-9]+)\[\/attach\]|\[hide\](.*?)\[\/hide\]|\[i.*\](.*)' . 
					'\[\/i\]|\[.*?\]|\\n|\\r/', 'BigAppAPI::imgCallback', $message);
			if(function_exists('mb_substr')){
				$message = mb_substr($message, 0, 1000, 'UTF-8');
			}else{
				$message = substr($message, 0, 2000);
			}
			/*$find = array(':)', ':(', ':D', ':\'(', ':@', ':o', ':P', ':$', ';P', ':L', ':Q', ':lol', ':loveliness:', 
					':funk:', ':curse:', ':dizzy:', ':shutup:', ':sleepy:', ':hug:', ':victory:', ':time:', ':kiss:', ':handshake', ':call:');
			$replace = array("\xF0\x9F\x98\x8C", "\xF0\x9F\x98\x94",  "\xF0\x9F\x98\x83", "\xF0\x9F\x98\xAD", "\xF0\x9F\x98\xA0", 
					"\xF0\x9F\x98\xB2", "\xF0\x9F\x98\x9C", "\xF0\x9F\x98\x86", "\xF0\x9F\x98\x9D",  "\xF0\x9F\x98\x93",  "\xF0\x9F\x98\xAB", 
					"\xF0\x9F\x98\x81", "\xF0\x9F\x98\x8A", "\xF0\x9F\x98\xB1", "\xF0\x9F\x98\xA4", "\xF0\x9F\x98\x96", "\xF0\x9F\x98\xB7", 
					"\xF0\x9F\x98\xAA", "\xF0\x9F\x98\x9A", "\xE2\x9C\x8C", "\xE2\x8F\xB0", "\xF0\x9F\x92\x8B", "\xF0\x9F\x91\x8C", "\xF0\x9F\x93\x9E");
			$message = str_replace($find, $replace, $message);*/
			
			loadcache(array('smilies', 'smileytypes'));
	
			foreach($_G['cache']['smilies']['replacearray'] as $id => $img) {
			    $pattern = $_G['cache']['smilies']['searcharray'][$id];
			    $message = preg_replace($pattern, '[表情]', $message);
			}
			
			if(function_exists('mb_strlen')){
				if(mb_strlen($message, 'UTF-8') > 30){
					$message = mb_substr($message, 0, 30, 'UTF-8') . '...';
				}
			}else{
				if(strlen($message) > 60){
					$message = substr($message, 0, 30) . '...';
				}
			}
			$info['message'] = '__DONT_DICONV_TO_UTF8___' . $message;
			$attachments = array();
			$infoAttrs = $info['attachments'];
			foreach ($collect as $attach){
				if(is_numeric($attach)){
					if(isset($infoAttrs[$attach])){
						$url = ($infoAttrs[$attach]['remote'] ? $_G['setting']['ftp']['attachurl'] : $_G['setting']['attachurl']).'forum/';
						$url .= $infoAttrs[$attach]['attachment'];
						$tmp = parse_url($url);
						if(!isset($tmp['scheme'])){
							$url = ApiUtils::getDzRoot() . $url;
						}
						$info['attachment_urls'][] = $url;
						unset($infoAttrs[$attach]);
					}
					continue;
				}
				$tmp = parse_url($attach);
				if(!isset($tmp['scheme'])){
					$url = ApiUtils::getDzRoot() . $attach;
				}else{
					$url = str_replace('source/plugin/mobile/', '', $attach);
					$url = str_replace('source/plugin/mobile/', '', $url);
				}
				$info['attachment_urls'][] = $url;
			}
			global $_G;
			//feed others
			foreach ($infoAttrs as $aid => $aInfo){
				$url = ($aInfo['remote'] ? $_G['setting']['ftp']['attachurl'] : $_G['setting']['attachurl']).'forum/';
				$url .= $aInfo['attachment'];
				$tmp = parse_url($url);
				if(!isset($tmp['scheme'])){
					$url = ApiUtils::getDzRoot() . $url;
				}
				$info['attachment_urls'][] = $url;
			}
		}
		unset($info);
	}

	protected static function imgCallback($matches)
	{
		$collect = &BigAppAPI::$collect;
		if(isset($matches[1]) && !empty($matches[1])){
			$collect[] = $matches[1];
		}else if(isset($matches[2]) && !empty($matches[2])){
			$collect[] = $matches[2];
		}else if(isset($matches[3]) && !empty($matches[3])){
			return '[隐藏内容，请登录后查看]';	
		}
		return '';
	}

	protected function _getDetails(&$list)
	{
		//check whether thread list image mode is open
		global $_G;
		if(isset($_G['setting']['bigapp_settings']) && is_string($_G['setting']['bigapp_settings'])){
    		$_G['setting']['bigapp_settings'] = unserialize($_G['setting']['bigapp_settings']);
		}
		if(isset($_G['setting']['bigapp_settings']['threadlist_image_mode']) && !$_G['setting']['bigapp_settings']['threadlist_image_mode']){
			return ;
		}
		$tids = array();
		foreach ($list as $l){
			$tids[] = $l['tid'];
		}
		if(empty($tids)){
			return ;
		}
        $_tids = $tids;
        $tids = array();
        $expire = 5;
        $threadInfoCache = array();
        foreach ($_tids as $tid){
            $cacheKey = 'bigapptsum' . $tid;
            loadcache($cacheKey);
            if(isset($_G['cache'][$cacheKey]) && TIMESTAMP - $_G['cache'][$cacheKey]['expiration'] <= $expire){
                $threadInfoCache[$tid] = $_G['cache'][$cacheKey]['variable'];
            }else{
                $tids[] = $tid;
            }
        }
        $threadInfo = array();
        if(!empty($tids)){
            runlog('bigapp', 'such tids has no cache, get them from database [ tids: ' . json_encode($tids) . ' ]');
		    $sql = 'SELECT pid, tid, first FROM ' . DB::table('forum_post') . ' WHERE tid IN (' . implode(', ', $tids) . ')';
    		$query = DB::query($sql);
    		$pids = array();
    		while($tmp = DB::fetch($query)){
    			//if(!isset($threadInfo[$tmp['tid']]) || $threadInfo[$tmp['tid']]['pid'] > $tmp['pid']){
    			if(!!$tmp['first']){
    				if(isset($pids[$threadInfo[$tmp['tid']]['pid']])){
    					unset($pids[$threadInfo[$tmp['tid']]['pid']]);
    				}
    				$threadInfo[$tmp['tid']] = array('pid' => $tmp['pid']);
    				$pids[$tmp['pid']] = $tmp['pid'];   
    			}
    		}
    		if(!empty($pids)){
        		$pids = array_values($pids);
        		$sql = 'SELECT pid, tid, message FROM ' . DB::table('forum_post') . ' WHERE pid IN (' . implode(', ', $pids) . ')';
        		$query = DB::query($sql);
        		while($tmp = DB::fetch($query)){
        			$threadInfo[$tmp['tid']]['message'] = $tmp['message'];
        		}
        		$sql = 'SELECT aid, tid, tableid, pid FROM ' . DB::table('forum_attachment') . ' WHERE pid IN (' . implode(', ', $pids) . ')';
        		$tbIdx = array();
        		$query = DB::query($sql); 
        		while($tmp = DB::fetch($query)){
        			if($tmp['tableid'] < 10){
        				$threadInfo[$tmp['tid']]['aid'][] = $tmp['aid'];
        				$tbIdx[$tmp['tableid']][] = $tmp['aid'];
        			}
        		}
        		foreach ($tbIdx as $tableId => $aids){
        			$sql = 'SELECT aid, tid, attachment, description, remote, isimage FROM ' . DB::table('forum_attachment_' . 
        					$tableId) . ' WHERE aid IN (' . implode(', ', $aids) . ')';
            			$query = DB::query($sql);
        			while($tmp = DB::fetch($query)){
        				$isImage = $tmp['isimage'];
        				if($tmp['isimage'] && !$_G['setting']['attachimgpost']){
        					$isImage = 0;
        				}
        				if($isImage){
        					$threadInfo[$tmp['tid']]['attachments'][$tmp['aid']] = array('attachment' => $tmp['attachment'], 
        							'description' => $tmp['description'], 'remote' => $tmp['remote'], 'isimage' => $isImage);
        				}
        			}
        		}
        		BigAppAPI::_getPictures($threadInfo);
                foreach ($threadInfo as $tid => $tInfo){
                    runlog('bigapp', 'save thread sum [ tid: ' . $tid . ' ]');
                    savecache('bigapptsum' . $tid, array('variable' => $tInfo, 'expiration' => TIMESTAMP));
                }
            }
        }
		foreach ($threadInfoCache as $tid => $tInfo){
            if(!isset($threadInfo[$tid])){
                $threadInfo[$tid] = $tInfo;
            }
        }
        foreach ($list as &$l){
			$l['attachment_urls'] = array();
			$l['message_abstract'] = '';
			if(isset($threadInfo[$l['tid']]['message'])){
				$l['message_abstract'] = $threadInfo[$l['tid']]['message'];
			}
			if(isset($threadInfo[$l['tid']]['attachment_urls'])){
				$l['attachment_urls'] = $threadInfo[$l['tid']]['attachment_urls'];
			}
			if(true === BigAppConf::$enablePicOpt){
				foreach ($l['attachment_urls'] as &$_url){
					if(ApiUtils::isOptFix($_url)){
						$_url = rtrim($_G['siteurl'], '/') . '/plugin.php?id=bigapp:optpic&mod=__x__&size=' . urlencode(BigAppConf::$thumbSize) . '&url=' . urlencode($_url);
						$_url = str_replace('source/plugin/mobile/', '', $_url);
						$_url = str_replace('source/plugin/bigapp/', '', $_url);
					}
				}
				unset($_url);
			}
		}
		unset($l);
	}
}

?>

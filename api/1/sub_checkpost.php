<?php
/**
* @file sub_checkpost.php
* @Brief 
* @author youzu
* @version 1.0.0
* @date 2015-07-07
*/
if(!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}

class bigapp_api_sub {

	function getvariable() {
		global $_G;
		if(empty($_G['forum'])) {
			$allowpost = "0";
		} elseif(!$_G['uid'] && !((!$_G['forum']['postperm'] && $_G['group']['allowpost']) || ($_G['forum']['postperm'] && forumperm($_G['forum']['postperm'])))) {
			$allowpost = "0";
		} elseif(empty($_G['forum']['allowpost'])) {
			if(!$_G['forum']['postperm'] && !$_G['group']['allowpost']) {
				$allowpost = "0";
			} elseif($_G['forum']['postperm'] && !forumperm($_G['forum']['postperm'])) {
				$allowpost = "0";
			} else {
				$allowpost = "1";
			}
		} elseif($_G['forum']['allowpost'] == "-1") {
			$allowpost = "0";
		} else {
			$allowpost = "1";
		}

		if(empty($_G['forum'])) {
			$allowreply = "0";
		} elseif(!$_G['uid'] && !((!$_G['forum']['replyperm'] && $_G['group']['allowreply']) || ($_G['forum']['replyperm'] && forumperm($_G['forum']['replyperm'])))) {
			$allowreply = "0";
		} elseif(empty($_G['forum']['allowreply'])) {
			if(!$_G['forum']['replyperm'] && !$_G['group']['allowreply']) {
				$allowreply = "0";
			} elseif($_G['forum']['replyperm'] && !forumperm($_G['forum']['replyperm'])) {
				$allowreply = "0";
			} else {
				$allowreply = "1";
			}
		} elseif($_G['forum']['allowreply'] == "-1") {
			$allowreply = "0";
		} else {
			$allowreply = "1";
		}

		$mobile_attachextensions = array('jpg', 'jpeg', 'gif', 'png', 'mp3', 'txt', 'zip', 'rar', 'pdf');
		$_G['forum']['allowpostattach'] = isset($_G['forum']['allowpostattach']) ? $_G['forum']['allowpostattach'] : '';
		$allowupload = $_G['forum']['allowpostattach'] != -1 && ($_G['forum']['allowpostattach'] == 1 || (!$_G['forum']['postattachperm'] && $_G['group']['allowpostattach']) || ($_G['forum']['postattachperm'] && forumperm($_G['forum']['postattachperm'])));
		$allowupload = $allowupload && (!$_G['group']['maxattachnum'] || $_G['group']['maxattachnum'] && $_G['group']['maxattachnum'] > getuserprofile('todayattachs'));
		$attachremain = array();
		if($allowupload) {
			$attachextensions = !$_G['group']['attachextensions'] ? $mobile_attachextensions : array_map('trim', explode(',', $_G['group']['attachextensions']));
			$allowupload = $forummaxattachsize = array();
			loadcache('attachtype');
			if(isset($_G['cache']['attachtype'][$_G['forum']['fid']])) {
				$attachtype = $_G['cache']['attachtype'][$_G['forum']['fid']];
			} elseif(isset($_G['cache']['attachtype'][0])) {
				$attachtype = $_G['cache']['attachtype'][0];
			} else {
				$attachtype = array();
			}
			if($attachtype) {
				foreach($attachtype as $extension => $maxsize) {
					$forummaxattachsize[$extension] = $maxsize;
				}
			}
			foreach($mobile_attachextensions as $ext) {
				if(in_array($ext, $attachextensions)) {
					$allowupload[$ext] = -1;
					if(isset($forummaxattachsize[$ext]) && 0 <= $forummaxattachsize[$ext]){
						$allowupload[$ext] = $forummaxattachsize[$ext];
					}
					if(isset($_G['group']['maxattachsize']) && 0 < $_G['group']['maxattachsize']){
						if(-1 == $allowupload[$ext] || $allowupload[$ext] > $_G['group']['maxattachsize']){
							$allowupload[$ext] = $_G['group']['maxattachsize'];
						}
					}
					/*
					if(isset($forummaxattachsize[$ext])) {
						if($forummaxattachsize[$ext] > 0) {
							$allowupload[$ext] = $forummaxattachsize[$ext] ? $forummaxattachsize[$ext] : $_G['group']['maxattachsize'];
						} else {
							$allowupload[$ext] = 0;
						}
					} else {
						$allowupload[$ext] = -1;
					}
					*/
				} else {
					$allowupload[$ext] = 0;
				}
			}
			$attachremain = array(
				'size' => $_G['group']['maxsizeperday'] ? $_G['group']['maxsizeperday'] - getuserprofile('todayattachsize') : -1,
				'count' => $_G['group']['maxattachnum'] ? $_G['group']['maxattachnum'] - getuserprofile('todayattachs') : -1,
			);
		} else {
			$allowupload = array();
		}
		if(empty($allowupload)){
			unset($allowupload);
		}
		$uploadhash = md5(substr(md5($_G['config']['security']['authkey']), 8).$_G['uid']);
		$allowperm = array();
		$allowperm['allowperm'] = array();
		$allowkey = array('allowpost', 'allowreply', 'allowupload', 'attachremain', 'uploadhash');
		foreach($allowkey as $key) {
			if((!empty(${$key}) || ${$key} === 0) || !empty($_GET['debug'])) {
				$allowperm['allowperm'][$key] = ${$key};
			}
		}
		return $allowperm;
	}

}

?>

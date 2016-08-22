<?php
/**
 * @file notify.php
 * @Brief user notifications
 * @author Rex Zeng
 * @version 1
 * @date 2016-08-22
 */
if(!defined('IN_MOBILE_API')) {
	exit('Access Denied');
}

$_GET['mod'] = 'space';
$_GET['do'] = 'notice';
include_once 'home.php';

class mobile_api {
	function common() {
		global $_G;
		if(true === BigAppConf::$debug){
			$_G['trace'][] = __CLASS__ . '::' . __FUNCTION__;
		}
	}
	function output() {
		global $_G;
		if(true === BigAppConf::$debug){
			$_G['trace'][] = __CLASS__ . '::' . __FUNCTION__;
		}
		global $_G;
		$variable = [
			'list' => mobile_core::getvalues($GLOBALS['list'], ['/^\d+$/'], ['id', 'uid', 'type', 'new', 'authorid', 'author', 'note', 'dateline', 'from_id', 'from_idtype', 'from_num', 'style', 'rowid']),
			'count' => $GLOBALS['count'],
			'perpage' => $GLOBALS['perpage'],
			'page' => intval($GLOBALS['page']),
		];
		$variable['list'] = array_values($variable['list']);
		mobile_core::result(mobile_core::variable($variable));
	}
}

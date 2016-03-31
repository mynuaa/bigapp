<?php

if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once 'functions.inc.php';

// ==================================================================
// 【注意，请务必保证本段代码与synlogin.class.php中的checkLogin函数完全相同】
// ==================================================================
// 检查用户登录状态
require_once libfile('function/member');
require_once libfile('function/forum');
loaducenter();
if (isset($_COOKIE['myauth_uid'])) {
	// 解密uid
	$uid = getuid();
	// 尝试在discuz中通过uid获取用户信息
	$user = getuserbyuid($uid, 1);
	// 从sso系统中通过uid获取学号
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://localhost/sso/?action=getuser');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('uid' => $uid)));
	$result = json_decode(curl_exec($ch), true);
	$result = $result[0];
	// 根据学号判断用户类型
	switch ($result['auth_ded']) {
	case 'FRESHMAN':
		// 若学号是FRESHMAN，则用户组为南航新生
		$groupid = 46;
		break;
	case 'MALLUSER':
		// 若学号是MALLUSER，则用户组为纸飞机合作商家
		$groupid = 72;
		break;
	}
	// 用户组计算完毕
	if (!$user) {
		// 若之前没有在discuz中注册，则自动注册、更新信息并激活
		$user = uc_get_user($uid, 1);
		C::t('common_member')->insert($uid, $user[1], md5(random(10)), $user[2], $_G['clientip'], $groupid, $init_arr);
		C::t('common_member_profile')->update($uid, array('field1' => $result['auth_ded']));
		$user = getuserbyuid($uid);
	}
	else {
		// 这里是之前有在discuz中注册过的情况
		// 8为未激活用户，46为南航新生，这个在discuz后台“用户组”可以查到
		// 只要通过了教务处验证，这两个用户组需要无条件更新为10（韶华一笑间）
		$groupsNeedUpdate = array(8, 46);
		if (!in_array($result['auth_ded'], array('FRESHMAN', 'MALLUSER'))) {
			if (in_array($GLOBALS['_G']['member']['groupid'], $groupsNeedUpdate)) {
				C::t('common_member')->update($uid, array('groupid' => 10));
				C::t('common_member_profile')->update($uid, array('field1' => $result['auth_ded']));
				$GLOBALS['_G']['member']['groupid'] = 10;
			}
		}
	}
	// 下面是原版discuz的登录函数
	uc_user_synlogin($uid);
	setloginstatus($user, 1296000);
	setglobal('uid', getglobal('uid', 'member'));
	setglobal('username', getglobal('username', 'member'));
	setglobal('adminid', getglobal('adminid', 'member'));
	setglobal('groupid', getglobal('groupid', 'member'));
	if($GLOBALS['_G']['member']['newprompt']) {
		$GLOBALS['_G']['member']['newprompt_num'] = C::t('common_member_newprompt')->fetch($GLOBALS['_G']['member']['uid']);
		$GLOBALS['_G']['member']['newprompt_num'] = unserialize($GLOBALS['_G']['member']['newprompt_num']['data']);
		$GLOBALS['_G']['member']['category_num'] = helper_notification::get_categorynum($GLOBALS['_G']['member']['newprompt_num']);
	}
}
else {
	// 强行退出
	uc_user_synlogout();
	clearcookies();
}

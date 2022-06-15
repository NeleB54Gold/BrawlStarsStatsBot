<?php
	
# Ignore inline messages (via @)
if ($v->via_bot) die;

# Player/Club commands
if (in_array(explode(' ', $v->command, 2)[0], ['player', 'club'])) {
	$v->text = explode(' ', $v->command, 2)[1];
	$user['settings']['select'] = explode(' ', $v->command, 2)[0] . 's';
	unset($v->command, $v->query_data);
}
# Player/Club callbacks
elseif (in_array(explode(' ', $v->query_data, 2)[0], ['player', 'club'])) {
	$v->text = explode(' ', $v->query_data, 2)[1];
	$user['settings']['select'] = explode(' ', $v->query_data, 2)[0] . 's';
	unset($v->command, $v->query_data);
}
# No other commands in groups and channnels
elseif (in_array($v->chat_type, ['group', 'supergroup', 'channels'])) {
	die;
}

# Private chat with Bot
if ($v->chat_type == 'private' or $v->inline_message_id) {
	if ($bot->configs['database']['status'] and $user['status'] !== 'started') $db->setStatus($v->user_id, 'started');
	# Set default selection
	if (!isset($user['settings']['select'])) $user['settings']['select'] = 'players';
	
	$watermark = 'Brawl Stars Bot ⭐️' . PHP_EOL;
	# Change selection
	if (in_array($v->query_data, ['players', 'clubs'])) {
		$user['settings']['select'] = $v->query_data;
		$db->query('UPDATE users SET settings = ? WHERE id = ?', [json_encode($user['settings']), $v->user_id]);
		$v->query_data = 'start';
	}
	# Edit message by inline messages
	if ($v->inline_message_id) {
		$v->message_id = $v->inline_message_id;
		$v->chat_id = 0;
	}
	# Test API
	if ($v->command == 'test' and $v->isAdmin()) {
		$bs = new BrawlStars($db);
		$t = $bot->code(substr(json_encode($bs->getPlayer('#2PQP892V9'), JSON_PRETTY_PRINT), 0, 4096));
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Start message
	elseif (in_array($v->command, ['start', 'start inline']) or $v->query_data == 'start') {
		$t = $bot->bold($watermark) . $bot->italic($tr->getTranslation('startMessage'), 1);
		$se = ['', ''];
		if ($user['settings']['select'] == 'clubs') {
			$se[1] = '🔘';
		} else {
			$se[0] = '🔘';
		}
		$buttons[] = [
			$bot->createInlineButton($tr->getTranslation('playersButton') . $se[0], 'players'),
			$bot->createInlineButton($tr->getTranslation('clubsButton') . $se[1], 'clubs')
		];
		$buttons[] = [
			$bot->createInlineButton($tr->getTranslation('aboutButton'), 'about'),
			$bot->createInlineButton($tr->getTranslation('helpButton'), 'help')
		];
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('changeLanguage'), 'lang');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Help message
	elseif ($v->command == 'help' or $v->query_data == 'help') {
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		$t = $bot->bold($watermark) . $tr->getTranslation('helpMessage');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# About
	elseif ($v->command == 'about' or $v->query_data == 'about') {
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		$t = $tr->getTranslation('aboutMessage', [explode('-', phpversion(), 2)[0]]);
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Change language
	elseif ($v->command == 'lang' or $v->query_data == 'lang' or strpos($v->query_data, 'changeLanguage-') === 0) {
		$langnames = [
			'en' => '🇬🇧 English',
			'es' => '🇪🇸 Español',
			'fr' => '🇫🇷 Français',
			'it' => '🇮🇹 Italiano',
			'pt_br' => '🇧🇷 Português'
		];
		if (strpos($v->query_data, 'changeLanguage-') === 0) {
			$select = str_replace('changeLanguage-', '', $v->query_data);
			if (in_array($select, array_keys($langnames))) {
				$tr->setLanguage($select);
				$user['lang'] = $select;
				$db->query('UPDATE users SET lang = ? WHERE id = ?', [$user['lang'], $user['id']]);
			}
		}
		$langnames[$user['lang']] .= ' ✅';
		$t = '🔡 Select your language';
		$formenu = 2;
		$mcount = 0;
		foreach ($langnames as $lang_code => $name) {
			if (isset($buttons[$mcount]) and count($buttons[$mcount]) >= $formenu) $mcount += 1;
			$buttons[$mcount][] = $bot->createInlineButton($name, 'changeLanguage-' . $lang_code);
		}
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# General stats command
	else {
		if (in_array(explode(' ', $v->command, 2)[0], ['player', 'club'])) {
			$v->text = explode(' ', $v->command, 2)[1];
			$user['settings']['select'] = explode(' ', $v->command, 2)[0] . 's';
			unset($v->command, $v->query_data);
		} elseif (in_array(explode(' ', $v->query_data, 2)[0], ['player', 'club'])) {
			$v->text = explode(' ', $v->query_data, 2)[1];
			$user['settings']['select'] = explode(' ', $v->query_data, 2)[0] . 's';
			unset($v->command, $v->query_data);
		}
		if (!$v->query_data and !$v->command and in_array($user['settings']['select'], ['players', 'clubs'])) {
		} else {
			if ($v->command) {
				$t = $tr->getTranslation('unknownCommand');
			} elseif (!$v->query_data) {
				$t = $tr->getTranslation('noCommandRun');
			}
			if ($v->query_id) {
				$bot->answerCBQ($v->query_id, $t);
			} else {
				$bot->sendMessage($v->chat_id, $t);
			}
		}
	}
}

# General stats command
if (!$v->query_data and !$v->command and in_array($user['settings']['select'], ['players', 'clubs'])) {
	$bs = new BrawlStars($db);
	if ($user['settings']['select'] == 'clubs') {
		$data = $bs->getClub($v->text);
		if ($data['tag']) {
			$args = [
				'https://cdn.brawlify.com/club/' . $data['badgeId'] . '.png',
				$data['name'],
				$data['trophies'],
				$data['description'],
				$tr->getTranslation($data['type']),
				$data['requiredTrophies'],
				str_replace('#', '', $data['tag']),
				count($data['members'])
			];
			$buttons[][] = $bot->createInlineButton($tr->getTranslation('membersButton'), 'members ' . $data['tag']);
			$t = $tr->getTranslation('clubStats', $args);
		} else {
			$t = $tr->getTranslation('clubNotFound');
		}
	} else {
		$data = $bs->getPlayer($v->text);
		if ($data['tag']) {
			$args = [
				'https://cdn.brawlify.com/profile/' . $data['icon']['id'] . '.png?v=1',
				$data['name'],
				$data['expLevel'],
				$data['expPoints'],
				$data['highestTrophies'],
				$data['3vs3Victories'],
				$data['soloVictories'],
				$data['duoVictories'],
				$data['bestRoboRumbleTime'],
				$data['bestTimeAsBigBrawler'],
				str_replace('#', '', $data['tag'])
			];
			$buttons[][] = $bot->createInlineButton($tr->getTranslation('brawlers'), 'brawlers ' . $data['tag']);
			if ($data['club'] and $data['club']['tag']) $buttons[][] = $bot->createInlineButton($tr->getTranslation('clubsButton'), 'club ' . $data['club']['tag']);
			$t = $tr->getTranslation('playerStats', $args);
		} else {
			$t = $tr->getTranslation('playerNotFound');
		}
	}
	if ($v->query_id) {
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', 0);
		$bot->answerCBQ($v->query_id, $cbt);
	} else {
		$bot->sendMessage($v->chat_id, $t, $buttons, 'def', 0, 0);
	}
}
# Member list command
elseif (strpos($v->query_data, 'members ') === 0) {
	$bs = new BrawlStars($db);
	$data = $bs->getClub(str_replace('members ', '', $v->query_data));
	$preview = $bot->text_link('&#8203;', 'https://telegra.ph/file/91649508046052ab4af7a.jpg');
	$t = $preview . $bot->bold($tr->getTranslation('membersListOf', [$data['name']]), 1) . PHP_EOL;
	foreach ($data['members'] as $num => $member) {
		if ($num === 0) {
			$lemoji = '┌';
		} elseif ($num === (count($data['members']) - 1)) {
			$lemoji = '└';
		} else {
			$lemoji = '├';
		}
		$t .= PHP_EOL . $lemoji . ' [' . $bot->code($member['tag'], 1) . '] ' . $bot->specialchars($member['name'], 1);
	}
	$buttons[][] = $bot->createInlineButton('◀️', 'club ' . $data['tag']);
	$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', 0);
	$bot->answerCBQ($v->query_id);
}
# Brawler list command
elseif (strpos($v->query_data, 'brawlers ') === 0) {
	$bs = new BrawlStars($db);
	$data = $bs->getPlayer(str_replace('brawlers ', '', $v->query_data));
	$preview = $bot->text_link('&#8203;', 'https://telegra.ph/file/91649508046052ab4af7a.jpg');
	$t = $preview . $bot->bold($tr->getTranslation('brawlersListOf', [$data['name']]), 1) . PHP_EOL;
	$formenu = 6;
	$mcount = 0;
	foreach ($data['brawlers'] as $num => $brawler) {
		if ($num === 0) {
			$lemoji = '┌';
		} elseif ($num === (count($data['brawlers']) - 1)) {
			$lemoji = '└';
		} else {
			$lemoji = '├';
		}
		$brawler['name'] = strtolower($brawler['name']);
		$brawler['name'][0] = strtoupper($brawler['name'][0]);
		$t .= PHP_EOL . $lemoji . ' ' . ($num + 1) . ' ' . $bot->specialchars($brawler['name'], 1);
		if (isset($buttons[$mcount]) and count($buttons[$mcount]) >= $formenu) $mcount += 1;
		$buttons[$mcount][] = $bot->createInlineButton($num + 1, 'brawler ' . $data['tag'] . ' ' . $num);
	}
	$buttons[][] = $bot->createInlineButton('◀️', 'player ' . $data['tag']);
	$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', 0);
	$bot->answerCBQ($v->query_id);
}
# Brawler info
elseif (strpos($v->query_data, 'brawler ') === 0) {
	$bs = new BrawlStars($db);
	$e = explode(' ', $v->query_data, 3);
	$data = $bs->getPlayer($e[1]);
	$brawler = $data['brawlers'][$e[2]];
	$brawler['name'] = strtolower($brawler['name']);
	$brawler['name'][0] = strtoupper($brawler['name'][0]);
	$preview = $bot->text_link('&#8203;', 'https://cdn.brawlify.com/brawler-bs/' . str_replace(' ', '-', $brawler['name']) . '.png');
	$t = $preview . $tr->getTranslation('brawlerStats', [$brawler['name'], $brawler['rank'], $brawler['trophies'], $brawler['highestTrophies'], $brawler['power']]);
	$buttons[][] = $bot->createInlineButton('◀️', 'brawlers ' . $data['tag']);
	$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', 0);
	$bot->answerCBQ($v->query_id);
}

# Inline commands
if ($v->update['inline_query']) {
	$sw_text = 'Start the Bot!';
	$sw_arg = 'inline'; // The message the bot receive is '/start inline'
	$results = [];
	# Search players and clubs with inline mode
	if ($v->query) {
		$bs = new BrawlStars($db);
		if ($user['settings']['select'] == 'clubs') {
			$data = $bs->getClub($v->query);
			if ($data['tag']) {
				$args = [
					'https://cdn.brawlify.com/club/' . $data['badgeId'] . '.png',
					$data['name'],
					$data['trophies'],
					$data['description'],
					$tr->getTranslation($data['type']),
					$data['requiredTrophies'],
					str_replace('#', '', $data['tag']),
					count($data['members'])
				];
				$buttons[][] = $bot->createInlineButton($tr->getTranslation('membersButton'), 'members ' . $data['tag']);
				$t = $tr->getTranslation('clubStats', $args);
			} else {
				$t = $tr->getTranslation('clubNotFound');
			}
		} else {
			$data = $bs->getPlayer($v->query);
			if ($data['tag']) {
				$args = [
					'https://cdn.brawlify.com/profile/' . $data['icon']['id'] . '.png?v=1',
					$data['name'],
					$data['expLevel'],
					$data['expPoints'],
					$data['highestTrophies'],
					$data['3vs3Victories'],
					$data['soloVictories'],
					$data['duoVictories'],
					$data['bestRoboRumbleTime'],
					$data['bestTimeAsBigBrawler'],
					str_replace('#', '', $data['tag'])
				];
				$buttons[][] = $bot->createInlineButton($tr->getTranslation('brawlers'), 'brawlers ' . $data['tag']);
				if ($data['club'] and $data['club']['tag']) $buttons[][] = $bot->createInlineButton($tr->getTranslation('clubsButton'), 'club ' . $data['club']['tag']);
				$t = $tr->getTranslation('playerStats', $args);
			} else {
				$sw_text = $tr->getTranslation('playerNotFound');
			}
		}
		if ($t) {
			$results[] = $bot->createInlineArticle(
				$v->query,
				$data['name'],
				$data['tag'],
				$bot->createTextInput($t, 'def', 0),
				$buttons,
				0,
				0,
				$args[0]
			);
		}
	}
	$bot->answerIQ($v->id, $results, $sw_text, $sw_arg);
}

?>
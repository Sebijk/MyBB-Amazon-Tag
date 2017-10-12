<?php
/*
Amazon Tag
Autor: Sebijk
Original: phpBB Mod by Hotschi
@ http://phpbb.hotschi.de
Website: http://www.sebijk.com
Download auf: http://www.mybbcoder.info
*/

if(!defined("IN_MYBB"))
{
    die("This file cannot be accessed directly.");
}

$plugins->add_hook("parse_message", "amazontag");

// Diese Informationen werden im Plugin-Manager angezeigt
function amazontag_info()
{
	return array(
		"name"		    => "Amazon Tag",
		"description"	=> "Fügt den Amazon Tag ein",
		"website"		=> "http://www.mybbcoder.info",
		"author"		=> "Home of the Sebijk.com",
		"authorsite"	=> "http://www.sebijk.com",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => ""
	);
}

function amazontag_activate()
{
global $db, $mybb;

$amazontag_group = array(
		"gid"			=> "NULL",
		"name"			=> "amazontag_settings",
		"title"			=> "Amazon-Tag Einstellungen",
		"description"	=> "Einstellungen für den Amazon-Tag.",
		"disporder"		=> "3",
		"isdefault"		=> "no",
	);

	$db->insert_query("settinggroups", $amazontag_group);
	$gid = $db->insert_id();


	$amazontag_setting_1 = array(
		"sid"			=> "NULL",
		"name"			=> "amazontag_id",
		"title"			=> "Amazon Partner-ID",
		"description"	=> "Geben Sie hier Ihre Amazon Partner-ID ein.",
		"optionscode"	=> "text",
		"value"			=> '',
		"disporder"		=> '1',
		"gid"			=> intval($gid),
	);

	$amazontag_setting_2 = array(
		"sid"			=> "NULL",
		"name"			=> "amazontag_bf",
		"title"			=> "Bildformat",
		"description"	=> "Geben Sie hier das Bildformat ein. Möglich sind MZZZZZZZ, TZZZZZZZ oder THUMBZZZ.",
		"optionscode"	=> "text",
		"value"			=> "MZZZZZZZ",
		"disporder"		=> '2',
		"gid"			=> intval($gid),
	);

	$amazontag_setting_3 = array(
		"sid"			=> "NULL",
		"name"			=> "amazontag_fopen",
		"title"			=> "Artikel überprüfen",
		"description"	=> "Wenn Sie dies einschalten, wird der Amazon-Artikel auf Existenz überprüft. Sollte er nicht existieren, so wird eine entsprechende Fehlermeldung angezeigt.",
		"optionscode"	=> "onoff",
		"value"			=> "on",
		"disporder"		=> '3',
		"gid"			=> intval($gid),
	);

	$amazontag_setting_4 = array(
		"sid"			=> "NULL",
		"name"			=> "amazontag_texthinweis",
		"title"			=> "Klick-Text",
		"description"	=> "Hier können Sie ihren eigenen Text eingeben, der dann unten beim Artikel angezeigt wird.
							<br />Mit dem %s, also z.B %sLinks% definieren Sie den Text als Link.",
		"optionscode"	=> "textarea",
		"value"			=> "Klicken Sie %shier%s, um den Artikel bei Amazon.de anzuschauen.",
		"disporder"		=> '4',
		"gid"			=> intval($gid),
	);

	$db->insert_query("settings", $amazontag_setting_1);
	$db->insert_query("settings", $amazontag_setting_2);
	$db->insert_query("settings", $amazontag_setting_3);
	$db->insert_query("settings", $amazontag_setting_4);
	rebuild_settings();

}

function amazontag_deactivate()
{
	global $db;

	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='amazontag_id'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='amazontag_bf'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='amazontag_fopen'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='amazontag_texthinweis'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='amazontag_settings'");
	rebuild_settings();


}


function amazontag($message)
{
    return preg_replace("#\[amazon](.*?)\[/amazon\]#ei", "amazontag_parse('$1')", $message);
}

function amazontag_parse($text)
{
	global $mybb;

	$amazonid = $mybb->settings['amazontag_id'];
	$bildformat = $mybb->settings['amazontag_bf'];
	$text = filter_var($text, FILTER_SANITIZE_STRING);

	$az_pic = "http://images-eu.amazon.com/images/P/" . $text . ".03." . $bildformat . ".jpg";
	$az_page = "http://www.amazon.de/exec/obidos/ASIN/" . $text . "/". $amazonid;

	if ($mybb->settings['amazontag_fopen'] == "on") {
	$page_exist = @file_get_contents($az_page);
	if ($page_exist)
	{
		$az_click_here = sprintf($mybb->settings['amazontag_texthinweis'], "<a href=" . $az_page . " target=\"_blank\">", "</a>");
		$amazon = "<br /><br /><hr /><center><a href=\"" . $az_page . "\" target=\"_blank\"><img src=\"" . $az_pic . "\" border=\"0\" alt=\"\" /></a><br />" . $az_click_here . "</center>";
	}
	else $amazon = "<br /><br /><hr /><center>Dieser Artikel existiert nicht oder ist nicht mehr auf dem Amazon-Server.</center>";
	}
	else {
	$az_click_here = sprintf($mybb->settings['amazontag_texthinweis']. '<br />Sollte kein Bild angezeigt werden, so melden Sie es dann dem Administrator des Forums.', "<a href=" . $az_page . " target=\"_blank\">", "</a>");
	$amazon = "<br /><br /><hr /><center><a href=\"" . $az_page . "\" target=\"_blank\"><img src=\"" . $az_pic . "\" border=\"0\" alt=\"\" /></a><br />" . $az_click_here . "</center>";
	}

	return $amazon;
}

if(!function_exists("rebuild_settings"))
{
	function rebuild_settings()
	{
		global $db;
		$query = $db->query("SELECT * FROM ".TABLE_PREFIX."settings ORDER BY title ASC");
		while($setting = $db->fetch_array($query))
		{
			$setting['value'] = addslashes($setting['value']);
			$settings .= "\$settings['".$setting['name']."'] = \"".$setting['value']."\";\n";
		}
		$settings = "<?php\n/*********************************\ \n  DO NOT EDIT THIS FILE, PLEASE USE\n  THE SETTINGS EDITOR\n\*********************************/\n\n$settings\n?>";
		$file = fopen(MYBB_ROOT."/inc/settings.php", "w");
		fwrite($file, $settings);
		fclose($file);
	}
}
?>

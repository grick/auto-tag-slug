<?php
add_action('admin_menu', 'ats_add_page');
add_action('admin_init', 'ats_option_init');
add_action('admin_init', 'ats_process_all');

function ats_add_page() {
	add_options_page('Auto Tag Slug', 'Auto Tag Slug', 'manage_options', 'auto-tag-slug', 'ats_option_page');
}

function ats_option_init() {
	load_plugin_textdomain( 'auto-tag-slug', false, basename(dirname(__FILE__)) .'/languages' );
	register_setting('ats_options', 'ats_options', 'ats_options_validate');
	add_settings_section('top', __('General Settings', 'auto-tag-slug'), 'ats_section_top', __FILE__);
	add_settings_field('ats_switch_chk', __('Enable Convertor', 'auto-tag-slug'), 'ats_setting_switch', __FILE__, 'top');
	add_settings_section('middle', __('Slug Format', 'auto-tag-slug'), 'ats_section_middle', __FILE__);
	add_settings_field('ats_engine_radio1', ats_label_radio(__('Pin Yin', 'auto-tag-slug')), 'ats_setting_engine1', __FILE__, 'middle');
	add_settings_field('ats_engine_radio2', ats_label_radio(__('English', 'auto-tag-slug')), 'ats_setting_engine2', __FILE__, 'middle');
}

function ats_section_top() {
}

function ats_section_middle() {
}

function ats_setting_switch() {
	global $ats_options;
	if($ats_options['switch']) { $checked = 'checked="check"'; }
	echo '<label><input id="ats_switch_chk" name="ats_options[switch]" type="checkbox" '.$checked.' />  ';
	echo __('If turn on, the tag slug will be convert automatically while new post save/update.', 'auto-tag-slug') .'</label>';
}

function ats_setting_engine1() {
	global $ats_options;
	_e('Use Chinese pinyin for tag slug. Tags permalink: <code>http://www.xxx.com/tag/ni-hao</code>', 'auto-tag-slug');
	$items = array(__("Simplified Chinese", 'auto-tag-slug'), __("Traditional Chinese", 'auto-tag-slug') );
	echo '<p>';
	foreach($items as $item) {
		$value = ($item == __('Simplified Chinese', 'auto-tag-slug')) ? 'zh-hans' : 'zh-hant';
		$checked = ($ats_options['cnlang']==$value) ? ' checked="checked" ' : '';
		echo "<label><input ".$checked." value='$value' name='ats_options[cnlang]' type='radio' /> $item</label><br />";
	}
	echo '</p>';
}

function ats_setting_engine2() {
	global $ats_options;
	_e('Use English words for tag slug. Tags permalink: <code>http://www.xxx.com/tag/english-word</code>', 'auto-tag-slug');
	echo '<p><label>Bing API KEY: ';
	echo "<input id='ats_key_text' name='ats_options[bing_key]' size='50' type='text' value='{$ats_options['bing_key']}' /></label>";
	echo ' <a href="http://www.bing.com/developers/appids.aspx" target="_blank">' .__('GET ONE HERE.', 'auto-tag-slug') .'</a></p>';
}

function ats_label_radio($title) {
	global $ats_options;
	$value = ($title == __('Pin Yin', 'auto-tag-slug')) ? 'pinyin' : 'english';
	$checked = ($ats_options["engine"]==$value) ? ' checked="checked" ' : '';
	return "<label><input ".$checked." value='$value' name='ats_options[engine]' type='radio' /> $title</label><br />";
}

function ats_options_validate($input) {
	$input['bing_key'] = trim($input['bing_key']);
	if ($input['engine']!='pinyin' && $input['cnlang']) :
		unset($input['cnlang']);
		return $input;
	elseif ($input['engine']=='pinyin' && !$input['cnlang']) :
		$input['cnlang'] = 'zh-hans';
		return $input;
	else :
		return $input;
	endif;
}

function ats_process_all() {
	if ( isset($_GET['page']) && $_GET['page'] == 'auto-tag-slug' ) {
		if ( isset($_POST['covert_all']) ) :
			ats_notice( ats_convert_all() );
		elseif ( isset($_POST['recover']) ) :
			ats_notice( ats_recover_all() );
		endif;
	}
}

function ats_notice($num) {
	$notice = "Processed $num tags.";
	echo "<div class='updated'><p>$notice</p></div>";
}

function ats_api_warning() {
	global $ats_options;
	if ( empty($ats_options['bing_key']) ) {
	echo "
		<div class='updated fade'><p> Atuo Tag Slug: " .__('You need an API key to activate English translator. Get one <a href="http://www.bing.com/developers/appids.aspx" target=_blank>here</a> and <a href="options-general.php?page=auto-tag-slug">fix it</a>.', 'auto-tag-slug') ."</p></div>
		";
	}
}

function ats_option_page() {
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.', 'auto-tag-slug') );
	}
	echo '<div class="wrap">';
	if ( function_exists('screen_icon') ) echo screen_icon();
	echo "<h2>Auto Tag Slug</h2> 
	<form method='post' action='options.php'>";
	settings_fields('ats_options');
	do_settings_sections(__FILE__);
	echo " <p class='submit'> <input name='Submit' type='submit' class='button-primary' value=\"". __('Save Changes', 'auto-tag-slug') . "\" /> </p>
	</form>";

	echo "<form method='post' action=''> ".
	 '<h3>'.__('Batch Process', 'auto-tag-slug').'</h3>
	<p>'.__('Use this to covert/recover all tags.', 'auto-tag-slug').'</p>
	<p>'.__('Warning: Recommend to backup your database first. This may take a long time if you have large number of tags.', 'auto-tag-slug').'
	</p>
	<p>'.__('Before converting, you must select one slug format and save changes first. If you want to change previous posts tag slug format, you should recover all tags and then convert all. <br />For example if you want change all Pin Yin tags to English tags, you should do the following steps:', 'auto-tag-slug').'
	</p>
	<ol>
		<li>'.__('Click "Recover All"', 'auto-tag-slug').'</li>
		<li>'.__('Select English slug format', 'auto-tag-slug').'</li>
		<li>'.__('Save changes', 'auto-tag-slug').'</li>
		<li>'.__('Click "Convert All"', 'auto-tag-slug').'</li>
		</ol>' .
	"<p class='submit'>
		<input name='recover' type='submit' value=\"". __('Recover All', 'auto-tag-slug')." \" />
		<input name='covert_all' type='submit' value=\"" . __('Convert All', 'auto-tag-slug') ."\" />
	</p>
	</form>
	</div>";
}
?>

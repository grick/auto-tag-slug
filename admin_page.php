<?php
add_action('admin_menu', 'ats_add_page');
add_action('admin_init', 'ats_option_init');
add_action('admin_init', 'ats_process_all');

function ats_add_page() {
	add_options_page('Auto Tag Slug', 'Auto Tag Slug', 'manage_options', 'auto-tag-slug', 'ats_option_page');
}

function ats_option_init() {
	load_plugin_textdomain( 'auto-tag-slug', false, 'auto-tag-slug/languages' );
register_setting('ats_options', 'ats_options', 'ats_options_validate');
	add_settings_section('top', __('General Settings'), 'ats_section_top', __FILE__);
	add_settings_field('ats_switch_chk', __('Enable Convertor'), 'ats_setting_switch', __FILE__, 'top');
	add_settings_section('middle', __('Slug Format'), 'ats_section_middle', __FILE__);
	add_settings_field('ats_engine_radio1', ats_label_radio(__('Pin Yin')), 'ats_setting_engine1', __FILE__, 'middle');
	add_settings_field('ats_engine_radio2', ats_label_radio(__('English')), 'ats_setting_engine2', __FILE__, 'middle');
}

function ats_section_top() {
}

function ats_section_middle() {
}

function ats_setting_switch() {
	global $ats_options;
	if($ats_options['switch']) { $checked = 'checked="check"'; }
	echo '<label><input id="ats_switch_chk" name="ats_options[switch]" type="checkbox" '.$checked.' />  ';
	echo __('If turn on, the tag slug will be convert automatically while new post save/update.') .'</label>';
}

function ats_setting_engine1() {
	global $ats_options;
	_e('Use Chinese pinyin for tag slug. Tags permalink: <code>http://www.xxx.com/tag/ni-hao</code>');
	$items = array(__("Simplified Chinese"), __("Traditional Chinese") );
	echo '<p>';
	foreach($items as $item) {
		$value = ($item == __('Simplified Chinese')) ? 'zh-hans' : 'zh-hant';
		$checked = ($ats_options['cnlang']==$value) ? ' checked="checked" ' : '';
		echo "<label><input ".$checked." value='$value' name='ats_options[cnlang]' type='radio' /> $item</label><br />";
	}
	echo '</p>';
}

function ats_setting_engine2() {
	global $ats_options;
	_e('Use English words for tag slug. Tags permalink: <code>http://www.xxx.com/tag/english-word</code>');
	echo '<p><label>BING API KEY: ';
	echo "<input id='ats_key_text' name='ats_options[bing_key]' size='50' type='text' value='{$ats_options['bing_key']}' /></label>";
	echo ' <a href="http://www.bing.com/developers/appids.aspx" target="_blank">' .__('GET ONE HERE.') .'</a></p>';
}

function ats_label_radio($title) {
	global $ats_options;
	$value = ($title == __('Pin Yin')) ? 'pinyin' : 'english';
	$checked = ($ats_options["engine"]==$value) ? ' checked="checked" ' : '';
	return "<label><input ".$checked." value='$value' name='ats_options[engine]' type='radio' /> $title</label><br />";
}

function ats_options_validate($input) {
	$input['bing_key'] = trim($input['bing_key']);
	if ($input['engine']=='english' && !$input['bing_key']) :
		add_settings_error('ats_key_text', 'no-key', __('You need an API key to activate English translator. Get one <a href="http://www.bing.com/developers/appids.aspx\" target=_blank>here</a>.') );
	elseif ($input['engine']!='pinyin' && $input['cnlang']) :
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

function ats_option_page() {
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	echo '<div class="wrap">';
	if ( function_exists('screen_icon') ) echo screen_icon(); ?>
	<h2>Auto Tag Slug</h2> 
	<form method='post' action='options.php'>
	<?php settings_fields('ats_options'); ?>
	<?php do_settings_sections(__FILE__); ?>
	<p class='submit'>
		<input name='Submit' type='submit' class='button-primary' value="<?php esc_attr_e('Save Changes'); ?>" />
	</p>
	</form>

	<form method='post' action=''><?php
	echo '<h3>'.__('Batch Process').'</h3>
	<p>'.__('Use this to covert/recover all tags.').'</p>
	<p>'.__('Warning: Recommend to backup your database first. This may take a long time if you have large number of tags.').'
	</p>
	<p>'.__('Before converting, you must select one slug format and save changes first. If you want use new format, you should recover all tags and then convert all. <br />For example if you want change all Pin Yin tags to English tags, you should do the following steps:').'
	</p>
	<ol>
		<li>'.__('Click "Recover All"').'</li>
		<li>'.__('Select English slug format').'</li>
		<li>'.__('Save changes').'</li>
		<li>'.__('Click "Convert All"').'</li>
		</ol>';
?>
	<p class='submit'>
		<input name='covert_all' type='submit' value="<?php esc_attr_e('Convert All'); ?>" />
		<input name='recover' type='submit' value="<?php esc_attr_e('Recover All'); ?>" />
	</p>
	</form>
	</div>

<?
}


<?php

	/*
	Plugin Name: FormCraft MailPoet Add-On
	Plugin URI: http://formcraft-wp.com/addons/mailpoet/
	Description: MailPoet Add-On for FormCraft
	Author: nCrafts
	Author URI: http://formcraft-wp.com/
	Version: 1.0.1
	Text Domain: formcraft-mailpoet
	*/

	global $fc_meta, $fc_forms_table, $fc_submissions_table, $fc_views_table, $fc_files_table, $wpdb;
	$fc_forms_table = $wpdb->prefix . "formcraft_3_forms";
	$fc_submissions_table = $wpdb->prefix . "formcraft_3_submissions";
	$fc_views_table = $wpdb->prefix . "formcraft_3_views";
	$fc_files_table = $wpdb->prefix . "formcraft_3_files";

	add_action('formcraft_after_save', 'formcraft_mailpoet_trigger', 10, 4);
	function formcraft_mailpoet_trigger($content, $meta, $raw_content, $integrations)
	{
		global $fc_final_response;
		if (!class_exists('WYSIJA')) { return false; }
		if ( in_array('MailPoet', $integrations['not_triggered']) ){ return false; }
		$mailpoet_data = formcraft_get_addon_data('MailPoet', $content['Form ID']);

		if (!$mailpoet_data){return false;}
		if (!isset($mailpoet_data['Map'])){return false;}

		$submit_data = array();
		foreach ($mailpoet_data['Map'] as $key => $line) {
			$submit_data[$line['listID']]['user_list'] = array('list_ids'=>array($line['listID']));
			if ($line['columnID']=='email')
			{
				$email = fc_template($content, $line['formField']);
				if ( !filter_var($email,FILTER_VALIDATE_EMAIL) ) { continue; }
				$submit_data[$line['listID']]['user']['email'] = $email;
			}
			else
			{
				$name = fc_template($content, $line['formField']);
				$name = trim(preg_replace('/\s*\[[^)]*\]/', '', $name));				
				$submit_data[$line['listID']]['user'][$line['columnID']] = $name;
			}
		}


		foreach ($submit_data as $key => $list_submit) {
			if (!isset($list_submit['user']['email']))
				{$fc_final_response['debug']['failed'][] = __('MailPoet: No Email Specified','formcraft-mailpoet');continue;}

			$helper_user = WYSIJA::get('user','helper');
			$result = $helper_user->addSubscriber($list_submit);

			if ( isset($result->message) )
			{
				$fc_final_response['debug']['failed'][] = "(".$list_submit['user']['email'].")<br>".__($result->message,'formcraft-mailpoet');
			}
			else
			{
				$fc_final_response['debug']['success'][] = 'MailPoet Added: '.$list_submit['user']['email'].' to list '.implode($list_submit['user_list']['list_ids'], '');
			}
		}
	}

	add_action('formcraft_addon_init', 'formcraft_mailpoet_addon');
	add_action('formcraft_addon_scripts', 'formcraft_mailpoet_scripts');

	function formcraft_mailpoet_addon()
	{
		register_formcraft_addon('MailPoet_PrintContent',473,'MailPoet','MailPoetController',plugins_url('assets/logo.png', __FILE__ ), plugin_dir_path( __FILE__ ).'templates/',1);
	}
	function formcraft_mailpoet_scripts()
	{
		wp_enqueue_script('formcraft-mailpoet-main-js', plugins_url( 'assets/builder.js', __FILE__ ));
		wp_enqueue_style('formcraft-mailpoet-main-css', plugins_url( 'assets/builder.css', __FILE__ ));
	}

	function MailPoet_PrintContent()
	{
		if (!class_exists('WYSIJA')) {
			?>
			<div style='text-align: center; padding: 20px; font-size: 15px; line-height: 1.7em; color: #999'>You don't seem to have MailPoet installed.<br>The add-on isn't of much use.</div>
			<?php
		}
		else
		{

			$model_list = WYSIJA::get('list','model');
			$mailpoet_lists = $model_list->get(array('name','list_id'),array('is_enabled'=>1));

			?>
			<div id='mailpoet-cover'>
				<div class='loader'>
					<div class="fc-spinner small">
						<div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div>
					</div>
				</div>
				<div>
					<div id='mapped-mailpoet' class='nos-{{Addons.MailPoet.Map.length}}'>
						<div>
							<?php _e('Nothing Here','formcraft-mailpoet') ?>
						</div>
						<table cellpadding='0' cellspacing='0'>
							<tbody>
								<tr ng-repeat='instance in Addons.MailPoet.Map'>
									<td style='width: 30%'>
										<span>{{instance.listName}}</span>
									</td>
									<td style='width: 30%'>
										<span>{{instance.columnName}}</span>
									</td>
									<td style='width: 30%'>
										<span><input type='text' ng-model='instance.formField'/></span>
									</td>
									<td style='width: 10%; text-align: center'>
										<i ng-click='removeMap($index)' class='icon-cancel-circled'></i>
									</td>								
								</tr>
							</tbody>
						</table>
					</div>
					<div id='mailpoet-map'>
						<select class='select-list' ng-model='SelectedList'>
							<option value='' selected="selected">(<?php _e('List','formcraft-mailpoet') ?>)</option>
							<?php
							foreach($mailpoet_lists as $list){
								echo "<option value='".$list['list_id']."'>".$list['name']."</option>";
							}
							?>
						</select>

						<select class='select-column' ng-model='SelectedColumn'>
							<option value='' selected="selected">(<?php _e('Column','formcraft-mailpoet') ?>)</option>
							<option value='email'>E-mail</option>
							<option value='firstname'>Firstname</option>
							<option value='lastname'>Lastname</option>
						</select>

						<input class='select-field' type='text' ng-model='FieldName' placeholder='<?php _e('Form Field','formcraft-mailpoet') ?>'>
						<button class='button' ng-click='addMap()'><i class='icon-plus'></i></button>
					</div>
				</div>
			</div>
			<?php
		}
	}


	?>
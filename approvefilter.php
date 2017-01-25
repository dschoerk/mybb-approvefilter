<?php

if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function approvefilter_info()
{
    global $mybb, $db, $lang;
    
    return array(
        'name'          => 'MyBB Disapprove Spam',
        'description'   => 'Unapproves inappropriate posts',
        'website'       => '',
        'author'        => 'dschoerk',
        'authorsite'    => '',
        'version'       => '0.1',
        'codename'      => 'latestuser',
        'compatibility' => '18*'
    );
}

function approvefilter_install()
{
    global $db, $mybb;

    if($mybb->version_code < 1801)
    {
        flash_message("Sorry, but this plugin requires you to update to 1.8.1 or higher.", "error");
        admin_redirect("index.php?module=config-plugins");
    }

    // Add some settings
    $new_setting_group = array(
        "name" => "approvefilter",
        "title" => "Filter for approval",
        "description" => "Set filters for automatic unapproval of Threads and Posts",
        "disporder" => 78,
        "isdefault" => 0
    );

    $gid = $db->insert_query("settinggroups", $new_setting_group);

    $new_setting[] = array(
        "name" => "approvefilter_subjectfilters",
        "title" => "Filters for Subjects",
        "description" => "Regex filters per line for subjects",
        "optionscode" => "textarea",
        "disporder" => 1,
        "value" => '',
        "gid" => $gid
    );
	
	$new_setting[] = array(
        "name" => "approvefilter_messagefilters",
        "title" => "Filters for Message",
        "description" => "Regex filters per line for messages in Posts and Threads",
        "optionscode" => "textarea",
        "disporder" => 1,
        "value" => '',
        "gid" => $gid
    );
	
	$db->insert_query_multiple("settings", $new_setting);
    rebuild_settings();
}

function approvefilter_is_installed()
{
    global $db;
    $query = $db->simple_select("settinggroups", "*", "name='approvefilter'");
    if($db->num_rows($query))
    {
        return TRUE;
    }
    return FALSE;
}

function approvefilter_uninstall()
{
    global $db;
    $query = $db->simple_select("settinggroups", "gid", "name='approvefilter'");
    $gid = $db->fetch_field($query, "gid");
    if(!$gid)
    {
        return;
    }
    $db->delete_query("settinggroups", "name='approvefilter'");
    $db->delete_query("settings", "gid=$gid");
    rebuild_settings();
}

function approvefilter_activate()
{

}


function approvefilter_deactivate()
{

}

function validateThread(&$datahandler)
{
    global $mybb;
    global $cache;
	global $db;
	global $thread_info, $new_thread;
	
	
	
	$thread  = get_thread($thread_info['tid']);
	$subject = $thread['subject'];
	$firstpost = get_post($thread['firstpost']);
	$message = $firstpost['message'];
	
	/*if(strcmp($message, "Test"))
	{
		//error('validateThread');
		//$datahandler->set_error("test");
		
		error('Nope nope');
	}*/
	
	$subjectfilters = explode(PHP_EOL, $mybb->settings['approvefilter_subjectfilters']);
	$messagefilters = explode(PHP_EOL, $mybb->settings['approvefilter_messagefilters']);
	
	/*echo($subject);
	echo($message);
	echo("<pre>");
	print_r($subjectfilters);
	print_r($messagefilters);
	echo("</pre>");*/
	
	foreach($subjectfilters as $filter)
	{	
		$filter = trim($filter);
		if($filter == "")
			continue;
	
		// echo("testing: >" . $filter . "<\n");
		
		$match = preg_match($filter, $subject);
		if($match)
		{
			unapprove($thread_info['tid']);
			
			/*echo("<pre>");
			print_r($matches);
			echo("</pre>");*/
			
			//error('fucked subject');
			return;
		}
	}
	
	foreach($messagefilters as $filter)
	{
		$filter = trim($filter);
		if($filter == "")
			continue;
		
		// echo("testing: >" . $filter . "<\n");
		
		$match = preg_match($filter, $message);
		if($match)
		{
			unapprove($thread_info['tid']);
			
			/*echo("<pre>");
			print_r($matches);
			echo("</pre>");*/
			
			//error('fucked message');
			return;
		}
	}
	
	// error('ok');
}

function unapprove($threadid)
{
	global $fid, $moderation;
	
	if(!is_object($moderation))
	{
		require_once MYBB_ROOT.'inc/class_moderation.php';
		$moderation = new Moderation;
	}
	
	// Unapprove the thread
	$moderation->unapprove_threads(array($threadid));
	
	// error('validateThread ' . $thread_info);

	// Redirect to thread's forum
	// $mybb->settings['redirects'] = $mybb->user['showredirect'] = 1;
	redirect(get_forum_link($fid, 0, 'newthread'), 'Your thread has been flagged and is awaiting approval.<br />You will now be returned to the forum.');
}

function validatePost(&$datahandler)
{
    global $mybb;
    global $cache;
	global $db;
	
	$message = $datahandler->data['message'];
	
	if($message == "Test")
	{
		error('validateThread');
		$datahandler->set_error("test");
	}
	
	$datahandler->set_error("test");
}

//$plugins->add_hook("datahandler_post_validate_post", "validatePost");
$plugins->add_hook("newthread_do_newthread_end", "validateThread");
?>
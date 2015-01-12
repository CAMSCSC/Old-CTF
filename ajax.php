<?php

include( 'config.php' );
include( 'functions.php' );

if( !isset( $_GET['m'] ) || !is_string( $_GET['m'] ) )
{
	exit();
}

switch( $_GET['m'] )
{
	case 'get_message':
		output_xml
		(
			get_messages( 1 )
		);

	case 'get_attacks':
		if( !isset( $_POST['last'] ) )
		{
			exit();
		}

		output_xml
		(
			get_attacks
			(
				$_POST['last']
			)
		);

	case 'get_ranking':
		output_xml
		(
			get_ranking()
		);
		
	case 'view_completed':
		output_xml
		(
			view_completed()
		);
		
	case 'view_fails':
		output_xml
		(
			view_fails()
		);

	case 'view_messages':
		output_xml
		(
			view_messages()
		);
		
	case 'get_challenges':
		output_xml
		(
			get_challenges()
		);

	case 'get_challenge':
		if( !isset( $_POST['id'] ) )
		{
			exit();
		}

		output_xml
		(
			get_challenge
			(
				$_POST['id']
			)
		);

	case 'submit_key':
		if( !isset( $_POST['key'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}

		output_xml
		(
			submit_key
			(
				$_POST['key'],
				$_POST['token']
			)
		);

	case 'login':
		if( !isset( $_POST['username'] ) || !isset( $_POST['password'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}

		output_xml
		(
			login
			(
				$_POST['username'],
				$_POST['password'],
				$_POST['token']
			)
		);

	case 'logout':
		if( !isset( $_POST['token'] ) )
		{
			exit();
		}

		output_xml
		(
			logout
			(
				$_POST['token']
			)
		);

	case 'get_session':
		output_xml
		(
			get_session()
		);
		
	case 'team_stats':
		if( !isset( $_POST['id'] ) )
		{
			exit();
		}
		output_xml
		(
			team_stats( $_POST['id'] )
		);
		
	case 'view_team_attacks':
		output_xml
		(
			view_team_attacks()
		);
		
	case 'attack_team':
		if( !isset( $_POST['to'] ) || !isset( $_POST['points'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}
		output_xml
		(
			attack_team
			(
				$_POST['to'],
				$_POST['points'],
				$_POST['token']
			)
		);
	
	case 'get_teams':
		output_xml
		(
			get_teams()
		);
		
	case 'get_attack_team':
		if( !isset( $_POST['get'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}
		output_xml
		(
			get_attack_team
			(
				$_POST['get'],
				$_POST['token']
			)
		);
		
	case 'update_info':
		if( !isset( $_POST['m1'] ) || !isset( $_POST['m2'] ) || !isset( $_POST['m3'] ) || !isset( $_POST['email'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}
		output_xml
		(
			update_info
			(
				$_POST['m1'],
				$_POST['m2'],
				$_POST['m3'],
				$_POST['email'],
				$_POST['token']
			)
		);
		
	case 'update_flag':
		if( !isset( $_POST['token'] ) || !isset( $_FILES['file'] ) )
		{
			exit();
		}
		output_xml
		(
			update_flag
			(
				$_POST
			)
		);
		
	case 'create_account':
		if( !isset( $_POST['team_name'] ) || !isset( $_POST['school'] ) || !isset( $_POST['password'] ) || !isset( $_POST['repeat'] ) || !isset( $_POST['email'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}
		output_xml
		(
			create_account
			(
				$_POST['team_name'],
				$_POST['school'],
				$_POST['password'],
				$_POST['repeat'],
				$_POST['email'],
				$_POST['token']	
			)
		);
	
	case 'verify_account':
		if( !isset( $_GET['key'] ) )
		{
			exit();
		}
		header( 'Refresh: 5;url=/' );
		verify_account( $_GET['key'] );
	
	case 'forgot_pass':
		if( !isset( $_POST['name'] ) || !isset( $_POST['email'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}
		output_xml
		(
			forgot_pass
			(
				$_POST['name'],
				$_POST['email'],
				$_POST['token']
			)
		);
	
	case 'change_pass':
		if( !isset( $_POST['current'] ) || !isset( $_POST['new'] ) || !isset( $_POST['repeat'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}
		output_xml
		(
			change_pass
			(
				$_POST['current'],
				$_POST['new'],
				$_POST['repeat'],
				$_POST['token']
			)
		);

	case 'send_message':
		if( !isset( $_POST['password'] ) || !isset( $_POST['message'] ) || !isset( $_POST['image'] ) || !isset( $_POST['script'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}

		output_xml
		(
			send_message
			(
				$_POST['password'],
				$_POST['message'],
				$_POST['image'],
				$_POST['script'],
				$_POST['token']
			)
		);

	case 'terminal':
		if( !isset( $_POST['command'] ) || !isset( $_POST['token'] ) )
		{
			exit();
		}

		output_xml
		(
			terminal
			(
				trim( $_POST['command'] ),
				$_POST['token']
			)
		);
}

?>

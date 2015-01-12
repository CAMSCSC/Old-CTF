<?php

if( !defined( 'INITIALIZED' ) ) #Not needed
{ // No direct call, needs config
	exit();
}

// General

function output_xml( $xml ) #Not needed
{
	header( 'Content-Type: text/xml' );
	exit( $xml->asXML() );
}

function valid_token( $token ) #Not needed
{
	return ( $_SESSION['token'] === $token );
}

function update_score( $id, $score )
{
	$database = Database::getConnection();
	$id = (int) $database->real_escape_string( $id );
	$score = (int) $database->real_escape_string( $score );
	
	$result = $database->query
	(
		"UPDATE
			teams
		SET
			teams.score = teams.score + '$score'
		WHERE
			teams.id = '$id'"
	);
}

// View All Messages

function view_messages()
{
	$database = Database::getConnection();
	
	$result = $database->query
	(
		"SELECT
			ticker.text
		FROM
			ticker
		ORDER BY
			ticker.id DESC"
	);
	
	if( !$result )
	{
		die( 'MySQL: Syntax error' );
	}
	
	$xml = new SimpleXMLElement( '<messages></messages>' );
	
	while( $answer = mysqli_fetch_assoc( $result ) )
	{
		$xml->addChild( 'text', $answer['text'] );
	}
	
	return $xml;
}

//View Fails
function view_fails()
{
	$database = Database::getConnection();
	
	$result = $database->query
	(
		"SELECT
			teams.name,
			locations.fails
		FROM
			teams, locations
		WHERE
			teams.location = locations.id
			AND locations.id != 1
		ORDER BY
			locations.fails ASC"
	);
	
	if ( !$result )
	{
		die( 'MySQL: Syntax error' );
	}
	
	$xml = new SimpleXMLElement( '<fails></fails>' );
	
	while( $answer = mysqli_fetch_assoc( $result ) )
	{
		$xml_team = $xml->addChild( 'team' );
		$xml_team->addChild( 'name', $answer['name'] );
		$xml_team->addChild( 'fails', $answer['fails'] );		
	}
	
	return $xml;
}

// Strategic Viewer (What challenges were completed by what teams)

function view_completed()
{
	$database = Database::getConnection();
	
	$result = $database->query
	(
		"SELECT
			teams.name,
			teams.id
		FROM
			teams
		ORDER BY
			teams.name ASC"
	);
	
	if( !$result )
	{
		die( 'MySQL: Syntax error' );
	}

	$xml = new SimpleXMLElement( '<completed></completed>' );
	
	while( $supresult = mysqli_fetch_assoc( $result ) )
	{
	
		$subresult = $database->query
		(
			"SELECT
				challenges.title
			FROM
				challenges
			JOIN
				attacks
			ON
				attacks.challenge = challenges.id
				AND attacks.team = '" . $database->real_escape_string( $supresult['id'] ) . "'
			ORDER BY
				challenges.title ASC"
		);
		
		if( !$subresult )
		{
			die( 'MySQL: Syntax error' );
		}
		
		$xml_team = $xml->addChild( 'team' );
		$xml_team->addChild( 'name', $supresult['name'] );
		
		$completed_list = array();
		
		while( $answer = mysqli_fetch_assoc( $subresult ) )
		{	
			$quotes = '"';
			array_push( $completed_list, $quotes . $answer['title'] . $quotes );
		}
		
		$counts = array_count_values( $completed_list );
		
		if( isset( $counts['"Daily Trivia"'] ) && $counts['"Daily Trivia"'] > 1 )
		{
			$completed_list = array_unique( $completed_list );
			$key = array_search( '"Daily Trivia"', $completed_list );
			$completed_list[$key] = '"Daily Trivia (x' . $counts['"Daily Trivia"'] . ')"';
		}
		
		$tdata = implode( ' ', $completed_list );
		$xml_team->addChild( 't', $tdata );
	}
	
	return $xml;
}

// Message

function get_messages( $limit )
{
	$database = Database::getConnection();
	
	if( !is_numeric( $limit ) || ( $limit > 100 ) )
	{
		exit();
	}

	$result = $database->query
	(
		"SELECT
			ticker.id,
			ticker.text,
			ticker.image,
			ticker.sound,
			ticker.script
		FROM
			ticker
		ORDER BY
			ticker.id DESC
		LIMIT " .
		    (int) $limit
	);

	if( !$result )
	{
		die( 'MySQL: Syntax error' );
	}

	$xml = new SimpleXMLElement( '<transmissions></transmissions>' );

	while( $answer = mysqli_fetch_assoc( $result ) )
	{
		$xml_announcement = $xml->addChild( 'announcement' );
		$xml_announcement->addChild( 'id', $answer['id'] );
		$xml_announcement->addChild( 'text', htmlspecialchars( $answer['text'] ));
		$xml_announcement->addChild( 'image', $answer['image'] );
		$xml_announcement->addChild( 'sound', $answer['sound'] );
		$xml_announcement->addChild( 'script', $answer['script'] );
	}

	return $xml;
}

// Latest attacks

function get_attacks( $id )
{
	$database = Database::getConnection();
	
	$limit = 20;

	if( $id < 1 )
	{ // First call
		$limit = 1;
	}

	$result = $database->query
	(
		"SELECT
			attacks.id,
			teams.name,
			locations.members,
			locations.code,
			challenges.score
		FROM
			attacks
		JOIN
			teams
		ON
			attacks.team = teams.id
		JOIN
			locations
		ON
			teams.location = locations.id
		JOIN
			challenges
		ON
			attacks.challenge = challenges.id
		WHERE
			attacks.id > '" . $database->real_escape_string( $id ) . "'
		ORDER BY
			attacks.id DESC
		LIMIT " .
			(int) $limit
	);

	if( !$result )
	{
		die( 'MySQL: Syntax error' );
	}

	$xml = new SimpleXMLElement( '<attacks></attacks>' );

	while( $answer = mysqli_fetch_assoc( $result ) )
	{
		$xml_attack = $xml->addChild( 'attack' );
		$xml_attack->addChild( 'id', $answer['id'] );
		$xml_attack->addChild( 'teamname', htmlspecialchars( $answer['name'] ));
		$xml_attack->addChild( 'members', htmlspecialchars( $answer['members'] ));
		$xml_attack->addChild( 'code', $answer['code'] );
		$xml_attack->addChild( 'score', ( $answer['score'] / 500 ) );
	}

	return $xml;
}

// Current ranking

function get_ranking()
{
	$database = Database::getConnection();
	
	$result = $database->query
	(
		"SELECT
			teams.id,
			teams.name,
			teams.score,
			locations.members,
			locations.code
		FROM
			teams
		JOIN
			locations
		ON
			teams.location = locations.id
		ORDER BY
			teams.score DESC,
			teams.name ASC"
	);

	if( !$result )
	{
		die( 'MySQL: Syntax error' );
	}

	$xml = new SimpleXMLElement( '<ranking></ranking>' );
	
	$current['rank'] = 1;
	$current['score'] = -1;

	while( $answer = mysqli_fetch_assoc( $result ) )
	{
		if( $current['score'] < 0 )
		{
			$current['score'] = $answer['score'];
		}

		if( $answer['score'] < $current['score'] )
		{
			$current['score'] = $answer['score'];
			$current['rank']++;
		}

		$xml_team = $xml->addChild( 'team' );
		$xml_team->addChild( 'id', $answer['id'] );
		$xml_team->addChild( 'rank', $current['rank'] );
		$xml_team->addChild( 'name', htmlspecialchars( $answer['name'] ));
		$xml_team->addChild( 'members', htmlspecialchars( $answer['members'] ));
		$xml_team->addChild( 'code', $answer['code'] );
		$xml_team->addChild( 'score', $answer['score'] );
	}

	$result->close();
	return $xml;
}

// Challenge browser

function get_challenges()
{
	$database = Database::getConnection();
	
	if( !is_loggedin() )
	{
		$result = $database->query
		(
			"SELECT
				categories.id,
				categories.name
			FROM
				categories
			ORDER BY
				categories.id ASC"
		);

		$xml = new SimpleXMLElement( '<categories></categories>' );

		while( $category = mysqli_fetch_assoc( $result ) )
		{
			$subresult = $database->query
			(
				"SELECT
					challenges.id,
					challenges.title,
					challenges.score,
					challenges.deactivated,
					challenges.hidden
				FROM
					challenges
				WHERE
					challenges.category = '" . $database->real_escape_string( $category['id'] ) . "'
					AND challenges.hidden != 1
				ORDER BY
					challenges.title ASC"
			);

			if( !$subresult )
			{
				die( 'MySQL: Syntax error' );
			}

			$xml_category = $xml->addChild( 'category' );
			$xml_category->addChild( 'name', $category['name'] );

			while( $answer = mysqli_fetch_assoc( $subresult ) )
			{
				$xml_challenge = $xml_category->addChild( 'challenge' );
				$xml_challenge->addChild( 'id', $answer['id'] );
				$xml_challenge->addChild( 'title', $answer['title'] );
				$xml_challenge->addChild( 'deactivated', $answer['deactivated'] );
				$xml_challenge->addChild( 'score', $answer['score'] );
			}
		}

		return $xml;
	}

	$result = $database->query
	(
		"SELECT
			categories.id,
			categories.name
		FROM
			categories
		ORDER BY
			categories.id ASC"
	);

	if( !$result )
	{
		die( 'MySQL: Syntax error' );
	}

	$xml = new SimpleXMLElement( '<categories></categories>' );

	while( $category = mysqli_fetch_assoc( $result ) )
	{
		$subresult = $database->query
		(
			"SELECT
				challenges.id,
				challenges.title,
				challenges.score,
				challenges.deactivated,
				challenges.hidden,
				(
					SELECT
						COUNT( attacks.id )
					FROM
						attacks
					WHERE
						attacks.challenge = challenges.id
						AND attacks.team = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "'
				) AS solved
			FROM
				challenges
			WHERE
				challenges.category = '" . $database->real_escape_string( $category['id'] ) . "'
				AND challenges.hidden != 1
			ORDER BY
				challenges.title ASC"
		);

		if( !$subresult )
		{
			die( 'MySQL: Syntax error' );
		}

		$xml_category = $xml->addChild( 'category' );
		$xml_category->addChild( 'name', $category['name'] );

		while( $answer = mysqli_fetch_assoc( $subresult ) )
		{
			$xml_challenge = $xml_category->addChild( 'challenge' );
			$xml_challenge->addChild( 'id', $answer['id'] );
			$xml_challenge->addChild( 'title', $answer['title'] );
			$xml_challenge->addChild( 'solved', $answer['solved'] );
			$xml_challenge->addChild( 'deactivated', $answer['deactivated'] );
			$xml_challenge->addChild( 'score', $answer['score'] );
		}
	}

	return $xml;
}

function get_challenge( $id )
{
	$database = Database::getConnection();
	
	$result = $database->query
	(
		"SELECT
			challenges.id,
			challenges.title,
			challenges.description,
			challenges.hidden
		FROM
			challenges
		WHERE
			challenges.id = '" . $database->real_escape_string( $id ) . "'
			AND challenges.hidden != 1"
	);

	if( !$result )
	{
		die( 'MySQL: Syntax error' );
	}

	$answer = array();
	
	if( mysqli_num_rows( $result ) === 1 )
	{
		$answer = mysqli_fetch_assoc( $result );
	}
	else
	{
		$answer = array( 'id' => 0, 'title' => '', 'description' => '', 'image' => '' ); 
	}

	$xml = new SimpleXMLElement( '<challenge></challenge>' );
	$xml->addChild( 'id', $answer['id'] );
	$xml->addChild( 'title', $answer['title'] );
	$xml->addChild( 'description', htmlentities( $answer['description'] ) );

	return $xml;
}

// Team Stats
function team_stats( $id )
{
	$database = Database::getConnection();
	
	$id = $database->real_escape_string( $id );
	
	$r1 = $database->query( "SELECT COALESCE( SUM( challenges.score ), 0 ) + COALESCE( SUM( attacks.additional ), 0 ) + ( SELECT COALESCE( SUM( interact.value ), 0 ) FROM interact WHERE interact.to = '$id' AND interact.success = 1 ) + ( SELECT COALESCE( SUM( bonus.value ), 0 ) FROM bonus WHERE bonus.team = '$id' ) AS score FROM challenges, attacks WHERE attacks.team = '$id' AND attacks.challenge = challenges.id" ); //Score (score) ===
	$r2 = $database->query( "SELECT COALESCE( SUM( bonus.value ), 0 ) AS bonus FROM bonus WHERE bonus.team = '$id'" ); //Bonus (bonus) ===
	$r3 = $database->query( "SELECT COALESCE( SUM( interact.value ), 0 ) AS loss FROM interact WHERE interact.to = '$id' AND interact.success = 1" ); //Points lost (loss) ===
	$r4 = $database->query( "SELECT COALESCE( SUM( challenges.score ), 0 ) + COALESCE( SUM( attacks.additional ), 0 ) AS raw FROM challenges, attacks WHERE attacks.team = '$id' AND attacks.challenge = challenges.id" ); //Raw points (raw) ===
	$r5 = $database->query( "SELECT teams.registration FROM teams WHERE teams.id = '$id'" ); // Time registered (registration) ===
	$r6 = $database->query( "SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) AS ap FROM interact WHERE interact.from = '$id'" ); // Used attack points (ap) ===
	$r7 = $database->query( "SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) AS aps FROM interact WHERE interact.from = '$id' AND interact.success = 1" ); // Successful attacks (aps)
	$r8 = $database->query( "SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) AS apf FROM interact WHERE interact.from = '$id' AND interact.success = 0" ); // Failed attacks (apf)
	$r9 = $database->query( "SELECT locations.fails, locations.lastlogin FROM locations JOIN teams ON locations.id = teams.location AND teams.id = '$id'" ); // Last login & fails ===
	$r10 = $database->query( "SELECT COUNT(*) as numcompleted FROM attacks WHERE attacks.team = '$id'" ); // Last login & fails
	$a1 = $database->query( "SELECT teams.id, teams.name, (SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) FROM interact WHERE interact.to = '$id' AND interact.success = 1 AND interact.from = teams.id) AS points, (SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) FROM interact WHERE interact.to = '$id' AND interact.from = teams.id) AS total FROM teams HAVING total != 0 ORDER BY 3 DESC, teams.name ASC" ); // See who hates you
	$a2 = $database->query( "SELECT teams.id, teams.name, (SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) FROM interact WHERE interact.to = teams.id AND interact.success = 1 AND interact.from = '$id') AS points, (SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) FROM interact WHERE interact.to = teams.id AND interact.from = '$id') AS total FROM teams HAVING total != 0 ORDER BY 3 DESC, teams.name ASC" ); // See who you hate
	
	$xml = new SimpleXMLElement( '<stat></stat>' );
	
	for( $i = 1; $i<=10; $i++ )
	{
		${"data" . $i} = mysqli_fetch_assoc( ${"r" . $i} );
	}
	$xml->addChild( 'item', "Score (non-cached):" . $data1['score'] );
	$xml->addChild( 'item', "Bonus points (write-ups and daily points):" . $data2['bonus'] );
	$xml->addChild( 'item', "Points lost:" . $data3['loss'] );
	$xml->addChild( 'item', "Raw points (all challenges):" . $data4['raw'] );
	
	$diff = abs(strtotime(date("Y-m-d H:i:s")) - strtotime($data5['registration']));
	$years = floor($diff / (365*60*60*24));
	$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
	$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
	$xml->addChild( 'item', "Time registered:" . $years . " year(s), " . $months . " month(s) and " . $days . " day(s)" );
	
	$lastactive = number_format( (abs(strtotime(date("Y-m-d H:i:s")) - strtotime($data9['lastlogin']))/(60*60)), 3 );
	$xml->addChild( 'item', "Last active:" . $lastactive . " hours ago" );
	$xml->addChild( 'item', "Successful flag submissions:" . $data10['numcompleted'] );
	$xml->addChild( 'item', "Failed flag submissions:" . $data9['fails'] );
	
	if( $data10['numcompleted'] + $data9['fails'] == 0 )
	{
		$rate = "Undefined";
	}
	else
	{
		$rate = number_format( ( $data10['numcompleted'] / ( $data10['numcompleted'] + $data9['fails'] ) * 100 ), 3) . "%";
	}
	$xml->addChild( 'item', "Flag submission accuracy:" . $rate );
	
	$xml->addChild( 'item', "Attack points used:" . $data6['ap'] );
	$xml->addChild( 'item', "Successful attacks (in points):" . $data7['aps'] );
	$xml->addChild( 'item', "Failed attacks (in points):" . $data8['apf'] );
	
	if( $data6['ap'] == 0 )
	{
		$rate = "Undefined";
	}
	else
	{
		$rate = number_format( ( $data7['aps'] / $data6['ap'] * 100 ), 3) . "%";
	}
	$xml->addChild( 'item', "Attack success rate:" . $rate );
	
	$hate_list = array();
	while( $answer = mysqli_fetch_assoc( $a1 ) )
	{
		array_push( $hate_list, $answer['name'] . " (" . $answer['points'] . "/" . $answer['total'] . ")" ); 
	}
	if( empty( $hate_list ) )
	{
		$hate_string = "None";
	}
	else
	{
		$hate_string = implode( ', ', $hate_list );
	}
	$xml->addChild( 'add', "Attacked by: " . htmlspecialchars( $hate_string ) );
	
	$like_list = array();
	while( $answer = mysqli_fetch_assoc( $a2 ) )
	{
		array_push( $like_list, $answer['name'] . " (" . $answer['points'] . "/" . $answer['total'] . ")" ); 
	}
	if( empty( $like_list ) )
	{
		$like_string = "None";
	}
	else
	{
		$like_string = implode( ', ', $like_list );
	}
	$xml->addChild( 'add', "Attacked: " . htmlspecialchars( $like_string ) );
	
	return $xml;
}

// See who attacked you
function view_team_attacks()
{
	$database = Database::getConnection();
	
	$result = $database->query //One killer query...
	(
		"SELECT
			*
		FROM
			(SELECT interact.id, teams.name AS origin FROM teams JOIN interact WHERE interact.from = teams.id ORDER BY interact.id DESC LIMIT 50) a
		JOIN
			(SELECT interact.id, interact.success, interact.value, teams.name AS destination FROM teams JOIN interact WHERE interact.to = teams.id ORDER BY interact.id DESC LIMIT 50) b
		ON a.id = b.id"
	);
	$xml = new SimpleXMLElement( '<data></data>' );
	while( $answer = mysqli_fetch_assoc( $result ) )
	{
		$xml_team = $xml->addChild( 'attack' );
		$xml_team->addChild( 'from', htmlspecialchars( $answer['origin'] ) );
		$xml_team->addChild( 'to', htmlspecialchars( $answer['destination'] ));
		$xml_team->addChild( 'success', htmlspecialchars( $answer['success'] ));
		$xml_team->addChild( 'value', -1 * (int) $answer['value'] );
	}
	return $xml;
}

// Attack Team
function get_attack_team( $get, $token )
{
	$database = Database::getConnection();
	
	if( !is_loggedin() || !valid_token( $token ) )
	{
		exit();
	}

	$result = $database->query
	(
		"SELECT
			locations.money
		FROM
			locations
		WHERE
			locations.id = (SELECT teams.location FROM teams WHERE teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "')"
	);
	if( mysqli_num_rows( $result ) === 1 )
	{
		$answer = mysqli_fetch_assoc( $result );
		if( $get == 1 )
		{
			return $answer['money'];
		}
		else
		{
			$xml = new SimpleXMLElement( '<info></info>' );
			$xml->addChild( 'money', $answer['money'] );
			return $xml;
		}
	}
}

function get_teams()
{
	$database = Database::getConnection();
	
	$result = $database->query
	(
		"SELECT
			teams.id,
			teams.name
		FROM
			teams
		ORDER BY
			teams.name ASC"
	);
	
	$xml = new SimpleXMLElement( '<teamlist></teamlist>' );
	while( $answer = mysqli_fetch_assoc( $result ) )
	{
		$xml_team = $xml->addChild( 'team' );
		$xml_team->addChild( 'id', $answer['id'] );
		$xml_team->addChild( 'name', htmlspecialchars( $answer['name'] ));
	}
	return $xml;
}

function attack_team( $to, $points, $token )
{
	$database = Database::getConnection();
	global $min_ratio;
	global $def_ratio;
	global $disable_antagonism;
	global $min_draw;
	$list = array();
	$team_score = 0;
	$ratio = 0;
	$random = mt_rand() / mt_getrandmax(); //Random always makes it more fun.
	$money = get_attack_team( 1, $token );
	$answer = array();
	
	if( !is_loggedin() || !valid_token( $token ) )
	{
		exit();
	}
	
	$result = $database->query
	(
		"SELECT
			teams.id,
			teams.name,
			teams.score
		FROM
			teams
		ORDER BY
			teams.id ASC"
	);
	
	while( $reply = mysqli_fetch_assoc( $result ) )
	{
		$list[$reply['id']] = $reply['score'];
		if( $reply['id'] == $_SESSION['teamid'] )
		{
			$team_score = $reply['score'];
		}
	}
	
	if( $list[$to] != 0 && $team_score < $list[$to] ) //Someone is sure to get that divide by zero error.
	{
		$ratio = $team_score / $list[$to];
		if( $ratio > $min_ratio )
		{
			$ratio = $def_ratio;
		}
	}
	else
	{
		$ratio = $min_ratio;
	}
	
	if( $disable_antagonism )
	{
		$answer['code'] = 1;
		$answer['text'] = "This feature is disabled.";
	}
	else if( !is_updated() )
	{
		$answer['code'] = 1;
		$answer['text'] = 'Registration incomplete. Please update team information using "admin -ui" in the terminal.';
	}
	else if( $database->real_escape_string( $_SESSION['teamid'] ) == $to )
	{
		$answer['code'] = 1;
		$answer['text'] = "I don't understand why you would work so hard to attack yourself.";
	}
	else if( (int) $points > (int) $money )
	{
		$answer['code'] = 1;
		$answer['text'] = "You do no have enough attack points to perform that action.";
	}
	else if( (int) $points < (int) $min_draw )
	{
		$answer['code'] = 1;
		$answer['text'] = "The minimum attack value is " . $min_draw . " points.";
	}
	else if( $list[$to] <= 100 )
	{
		$answer['code'] = 1;
		$answer['text'] = "That team is too poor to be attacked.";
	}
	else if( ( $list[$to] - (int) $points ) <= 0 )
	{
		$answer['code'] = 1;
		$answer['text'] = "Don't try to give other teams 0 or negative points.";
	}
	else
	{
		$success = 0;
		$answer['code'] = 3;
		
		if( $random >= $ratio )
		{
			$success = 1;
			$answer['code'] = 2;
			
			//Update cached score
			update_score( $to, -1 * (int) $points );
		}
		
		$database->query
		(
			"UPDATE
				locations
			SET
				money = money - '" . $database->real_escape_string( (int) $points ) . "'
			WHERE 
				locations.id = ( SELECT teams.location FROM teams WHERE teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "' )"
		);
		
		$from = $database->real_escape_string( $_SESSION['teamid'] );
		$to = $database->real_escape_string( $to );
		$value = -1 * (int)$points;
		$add = $database->query
		(
			"INSERT INTO `interact`
				(`from`, `to`, `success`, `value`, `date`)
			VALUES
				('$from', '$to', '$success', '$value', NOW())"
		);
	}
	
	$money = get_attack_team( 1, $token );
	
	$xml = new SimpleXMLElement( '<info></info>' );
	$xml->addChild( 'code', $answer['code'] );
	if( isset( $answer['text'] ) ) { $xml->addChild( 'text', $answer['text'] ); }
	$xml->addChild( 'money', $money );
	$xml->addChild( 'new_score', (int) $list[$to] - (int) $points );
	return $xml;
}

// HackerCat

function hcat_attack()
{
	$message = '';
	
	$cat1 = Array( "HackerCat finds your struggles humorous.", "HackerCat grows tired of your tomfoolery.", "HackerCat is disappointed in you.", "HackerCat does not approve of your attempts to brute-force the flag.");
	$cat2 = Array( "I can’t believe you keep screwing this up.", "Are you seriously trying to brute-force this?", "Weren’t you listening? You have to put in the right flag!", "You are so lucky I can’t claw you right now.", "Are you allergic to cats? Yes? No? Fine, ignore me then...", "The amount of space I have in this box is as great as your success. As in, not very.", "You humans are so bad at coding...", "Come on, Lujing taught you better than that...", "Go use your attack points to annoy other people." );
	
	$rand1 = array_rand( $cat1, 1 );
	$rand2 = array_rand( $cat2, 1 );
	
	if( $_SESSION['failcount'] < 2 )
	{
		$message = '';
	}
	else if( $_SESSION['failcount'] >= 2 && $_SESSION['failcount'] <= 10 )
	{
		$message = $cat1[$rand1];
	}
	else if( $_SESSION['failcount'] < 50 )
	{
		$message = $cat2[$rand2];
	}
	else
	{
		$message = "You have two choices. Stop brute-forcing or prepare for destruction.";
	}
	
	return $message;
}

function hcat_angry() //INCOMPLETE!!! Needs more... maliciousness.
{
	$script = "play_sound('http://ctf.camscsc.org/meow.mp3', false);";
	
	return $script;
}

// Attack

function submit_key( $key, $token )
{
	$database = Database::getConnection();
	global $disable_antagonism;
	
	if( !valid_token( $token ) || !is_loggedin() )
	{
		exit();
	}

	if( strtolower( strtok( $key, " " ) ) == "daily:" )
	{
		$regex = strtolower( substr( strstr( $key, " " ), 1 ) );
		$result = $database->query
		(
			"SELECT
				challenges.id,
				challenges.title,
				challenges.score,
				challenges.deactivated,
				challenges.hidden,
				(
					SELECT
						COUNT( attacks.id )
					FROM
						attacks
					WHERE
						attacks.challenge = challenges.id
						AND attacks.team = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "'
				) AS already_solved,
				(
					SELECT
						COUNT( attacks.id )
					FROM
						attacks
					WHERE
						attacks.challenge = challenges.id
				) AS number_solved_all
			FROM
				challenges
			WHERE
				'" . $database->real_escape_string( $regex ) . "' REGEXP challenges.key
				AND challenges.hidden != 1"
		);		
	}
	else
	{
		$hash = hash( 'sha512', $key );
		
		$result = $database->query
		(
			"SELECT
				challenges.id,
				challenges.title,
				challenges.score,
				challenges.deactivated,
				challenges.hidden,
				(
					SELECT
						COUNT( attacks.id )
					FROM
						attacks
					WHERE
						attacks.challenge = challenges.id
						AND attacks.team = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "'
				) AS already_solved,
				(
					SELECT
						COUNT( attacks.id )
					FROM
						attacks
					WHERE
						attacks.challenge = challenges.id
				) AS number_solved_all
			FROM
				challenges
			WHERE
				challenges.key = '" . $database->real_escape_string( $hash ) . "'
				AND challenges.hidden != 1"
		);
	}

	$answer = array();

	if( mysqli_num_rows( $result ) === 1 && is_updated() )
	{
		$data = mysqli_fetch_assoc( $result );
		
		$title = $data['title'];
		
		if( $data['already_solved'] == '1' )
		{
			$answer['code'] = 2;
			$answer['text'] = '"' . $title . '" has already been completed!';
		}
		else if( $data['deactivated'] == '1' ) 
		{
			$answer['code'] = 4;
			$answer['text'] = 'Correct flag found for "' . $title . '." However, the challenge is deactivated.';
		}
		else if( $data['already_solved'] == '0' and $data['deactivated'] == '0' )
		{
			// Additional score			
			$additional = 0;

			if( $data['number_solved_all'] === '0' )
			{
				$additional = 3;
			}
			else if( $data['number_solved_all'] === '1' )
			{
				$additional = 2;
			}
			else if( $data['number_solved_all'] === '2' )
			{
				$additional = 1;
			}

			// Insert
			$database->query
			(
				"INSERT INTO
					attacks
					(
						team,
						challenge,
						additional,
						date
					)
					VALUES
					(
						'" . $database->real_escape_string( $_SESSION['teamid'] ) . "',
						'" . $database->real_escape_string( $data['id'] ) . "',
						'" . $database->real_escape_string( $additional )  . "',
						NOW()
					)"
			);
			
			//MONEY!!!!
			if( !$disable_antagonism )
			{
				$money = (int)((int)$data['score'] * mt_rand(15, 25)/100);
				$database->query
				(
					"UPDATE
						locations
					SET
						money = money + '$money'
					WHERE 
						locations.id = ( SELECT teams.location FROM teams WHERE teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "' )"
				);
				$answer['text'] = 'You earned ' . $money . ' attack points.';
			}
			
			// Update Cached Score
			update_score( $_SESSION['teamid'], $additional + $data['score'] );

			$answer['code'] = 1;
		}
	}
	else if( !is_updated() )
	{
		$answer['code'] = 2;
		$answer['text'] = 'Registration incomplete. Please update team information using "admin -ui" in the terminal.';
	}
	else
	{
		$database->query
		(
			"UPDATE
				locations
			SET
				fails = fails + 1
			WHERE 
				locations.id = ( SELECT teams.location FROM teams WHERE teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "' )"
		);
		
		usleep(10000); //Discourages brute forcing.
		$_SESSION['failcount'] += 1;
		
		$answer['code'] = 3;
		$answer['text'] = 'Incorrect Flag.';
		
		if( $_SESSION['failcount'] < 100 )
		{
			$answer['cat'] = hcat_attack();
			$answer['script'] = '';
		}
		else
		{
			$answer['cat'] = '';
			$answer['script'] = hcat_angry();
		}
	}

	$xml = new SimpleXMLElement( '<attack></attack>' );
	
	if( !empty( $answer['text'] ) )
	{
		$xml->addChild( 'text', $answer['text'] );
	}
	
	$xml->addChild( 'code', $answer['code'] );
	
	if( !empty( $answer['cat'] ) )
	{
		$xml->addChild( 'cat', $answer['cat'] );
	}
	
	if( !empty( $answer['script'] ) )
	{
		$xml->addChild( 'script', $answer['script'] );
	}

	return $xml;
}

// Authentication

function is_loggedin()
{
	if( isset( $_SESSION['teamid'] ) && ( $_SESSION['teamid'] !== false ) && ( (int) $_SESSION['teamid'] > 0 ) )
	{
		return true;
	}
	else
	{
		return false;
	}
}

function is_updated()
{
	$database = Database::getConnection();
	
	$registered = $database->query
	(
		"SELECT
			teams.location
		FROM
			teams
		WHERE
			teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "'"
	);
	
	$reg = mysqli_fetch_assoc( $registered );
	
	if( $reg['location'] == '1' )
	{
		return false;
	}
	else
	{
		return true;
	}
}

function login( $team, $password, $token ) #Done
{
	$database = Database::getConnection();
	global $disable_login;
	
	if( !valid_token( $token ) )
	{
		exit();
	}

	$answer = array();

	if( is_loggedin() )
	{
		$answer['code'] = 2;
		$answer['text'] = 'You are already logged in!';
	}
	else
	{
		$hash = hash( 'sha512', $password );
		
		$result = $database->query
		(
			"SELECT
				teams.id
			FROM
				teams
			WHERE
				BINARY teams.name = '" . $database->real_escape_string( $team ) . "'
				AND teams.password = '" . $database->real_escape_string( $hash ) . "'"
		);
		
		if( !$result )
		{
			die( 'MySQL: Syntax error' );
		}
		
		if( $disable_login == 1 && $team != 'administrator' )
		{
			$answer['code'] = 3;
			$answer['text'] = 'Site is currently under construction.';
			$_SESSION['teamid'] = false;
		}
		else if( mysqli_num_rows( $result ) === 1 )
		{
			$answer['code'] = 1;
			$data = mysqli_fetch_assoc( $result );
			$_SESSION['teamid'] = (int) $data['id'];
			$answer['text'] = '';
			$_SESSION['failcount'] = 0;
			
			$lastlogin = $database->query
			(
				"UPDATE
					locations
				SET
					lastlogin = NOW()
				WHERE
					locations.id = ( SELECT teams.location FROM teams WHERE teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "' )"
			);

			if( !$lastlogin )
			{
				die( 'MySQL: Syntax error' );
			}
		}
		else
		{
			$answer['code'] = 3;
			$answer['text'] = 'Incorrect username and/or password.';
			$_SESSION['teamid'] = false;
		}
	}

	$xml = new SimpleXMLElement( '<login></login>' );
	$xml->addChild( 'code', $answer['code'] );
	$xml->addChild( 'text', $answer['text'] );

	return $xml;
}

function logout( $token ) #Done 
{
	if( !valid_token( $token ) ) //In case everyone gets logged in as admin again...
	{
		session_unset();
		session_destroy(); 
		session_start();
		$_SESSION['token'] = hash( 'ripemd160', sha1( uniqid( '', true ) ) );
		exit();
	}

	$answer = array();

	if( is_loggedin() )
	{
		$answer['code'] = 1;
	}
	else
	{
		$answer['code'] = 2;
	}
	
	$_SESSION['teamid'] = false;

	$xml = new SimpleXMLElement( '<logout></logout>' );
	$xml->addChild( 'code', $answer['code'] );

	return $xml;
}

function get_session()
{
	$answer = array();

	if( is_loggedin() )
	{
		$answer['loggedin'] = 1;
	}
	else
	{
		$answer['loggedin'] = 0;
	}

	$xml = new SimpleXMLElement( '<status></status>' );
	$xml->addChild( 'loggedin', $answer['loggedin'] );
	$xml->addChild( 'token', $_SESSION['token'] );

	return $xml;
}

// Verify Account. Thank you all who spammed the site.
function verify_account( $key )
{
	$database = Database::getConnection();
	
	$result = $database->query
	(
		"SELECT
			*
		FROM
			temp
		WHERE
			temp.key = '" . $database->real_escape_string( $key ) . "'"
	);
	
	if( mysqli_num_rows( $result ) === 1 )
	{
		$data = mysqli_fetch_assoc( $result );
		
		if( $data['approved'] == 1 )
		{
			$name = $data['name'];
			$local = $data['local'];
			$hash = $data['password'];
			$email = $data['email'];
			$ip = $data['ip'];
			
			$insert = $database->query
			(
				"INSERT INTO teams
					(`name`, `registration`, `location`, `local`, `password`, `email`, `ip`)
				VALUES
					('$name', NOW(), '1', '$local', '$hash', '$email', '$ip')"
			);
					
			$delete = $database->query
			(
				"DELETE FROM
					temp
				WHERE
					temp.key = '" . $database->real_escape_string( $key ) . "'"
			);
			
			echo( '<style>body{background-color: #000000;} p {color: #0e0; text-align: center; font-size: 30px;}</style><body><p>Your account has been successfully approved. You may now login. Welcome to CTF.</p></body>' );
			
			exit();
		}
		else if( $data['clicked'] == 1 )
		{
			echo( "<style>body{background-color: #000000;} p {color: #0e0; text-align: center; font-size: 30px;}</style><body><p>Your account is awaiting the administrator's approval. Please wait a while before returning to this link.</p></body>" ); 
			exit();
		}
		else
		{
			$update = $database->query
			(
				"UPDATE
					temp
				SET
					clicked = 1
				WHERE
					temp.key = '" . $database->real_escape_string( $key ) . "'"
			);
			echo( "<style>body{background-color: #000000;} p {color: #0e0; text-align: center; font-size: 30px;}</style><body><p>Please wait for the administrator's approval. Check back in a while.</p></body>" ); 
			exit();
		}
	}
	else
	{
		echo( '<style>body{background-color: #000000;} p {color: #0e0; text-align: center; font-size: 30px;}</style><body><p>Error.</p></body>' );
		exit();
	}
}

// Create Account
function create_account( $team_name, $school, $password, $repeat, $email, $token )
{
	global $disable_create;
	$database = Database::getConnection();
	
	if( !valid_token( $token ) )
	{
		exit();
	}
	
	$answer = array();
	
	$duplicate_name = $database->query
	(
		"SELECT
			teams.name
		FROM 
			teams
		WHERE 
			teams.name = '" . $database->real_escape_string( $team_name ) . "'
		UNION SELECT
			temp.name
		FROM
			temp
		WHERE 
			temp.name = '" . $database->real_escape_string( $team_name ) . "'"
	);
	
	$duplicate_email = $database->query
	(
		"SELECT
			teams.email
		FROM
			teams
		WHERE
			teams.email = '" . $database->real_escape_string( $email ) . "'
		UNION SELECT
			temp.email
		FROM
			temp
		WHERE
			temp.email = '" . $database->real_escape_string( $email ) . "'"
	);
	
	if( $disable_create )
	{
		$answer['code'] = 2;
		$answer['text'] = 'Account creation is disabled temporarily.';
	}
	else if( mysqli_num_rows( $duplicate_name ) != 0 )
	{
		$answer['code'] = 2;
		$answer['text'] = 'The team name you have chosen already exists.';
	}
	else if( mysqli_num_rows( $duplicate_email ) != 0 )
	{
		$answer['code'] = 2;
		$answer['text'] = 'One email, one account. No exceptions.';
	}
	else if( empty( $team_name ) )
	{
		$answer['code'] = 3;
		$answer['text'] = 'You must enter a team name!';
	}
	else if( strlen( $team_name ) > 15 )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Your team name must be under 15 characters long!';
	}
	else if( preg_match( '/\s/', $team_name ) )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Spaces are not allowed in the team name. Thank you.';
	}
	else if( empty( $school ) )
	{
		$answer['code'] = 3;
		$answer['text'] = 'It is not necessary to conceal your school...';
	}
	else if( $school != 'CAMS' && $school != 'Other' )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Stop it. Stop changing your school.';
	}
	else if( empty( $password ) )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Not adding a password is dangerous. Please consider adding one.';
	}
	else if( empty( $email ) )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Your email is necessary for password recovery purposes.';
	}
	else if( $password != $repeat )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Your passwords do not match!';
	}
	else if( !filter_var( $email, FILTER_VALIDATE_EMAIL ) )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Lying is abominable. Lying about your email address is not an exception.';
	}
	else if( preg_match('/[\#\&\'\"]/', $team_name) || preg_match('/[\#\&\'\"]/', $email) )
	{
		$answer['code'] = 3;
		$answer['text'] = "DON'T ACT LIKE YOU DON'T KNOW THAT YOU HAVE ENTERED DANGEROUS AND ILLEGAL CHARACTERS ON PURPOSE TO DESTROY THIS WEBSITE!";
	}

	else
	{
		$name = $database->real_escape_string( $team_name );
		
		if( $school == 'CAMS' )
		{
			$local = 1;
		}
		else
		{
			$local = 0;
		}
		
		$hash = hash( 'sha512', $password );
		$email = $database->real_escape_string( $email );
		$key = hash( 'ripemd160', sha1( $_SESSION['token'] . microtime() . mt_rand() / mt_getrandmax() ) );
		$ip = $_SERVER['REMOTE_ADDR'];
		
		$change = $database->query
		(
			"INSERT INTO temp
				(`name`, `local`, `password`, `email`, `key`, `ip`)
			VALUES
				('$name', '$local', '$hash', '$email', '$key', '$ip')"
		);
		
		if( !$change )
		{
			die( 'MySQL: Syntax error' );
		}
		
		$answer['code'] = 1;
		$answer['text'] = 'The account has been created successfully. Please check your email for the activation link!';
		
		$to = $email;
		$subject = 'CTF Account Creation';
		
		$link = "http://ctf.camscsc.org/ajax.php?m=verify_account&key=" . $key;
		$message = "You have successfully created the account " . $name . " on CAMS CTF. Navigate to " . $link . " and verify your account.\r\n\r\nAfter your account is approved, please update your team members' names using the website terminal.\r\n\r\nIf you would like to delete your account or if you have any questions, please reply to this email.";
		$headers = 'From: "CTF Admin" <admin@ctf.camscsc.org>';	
		mail($to, $subject, $message, $headers);
	}

	$xml = new SimpleXMLElement( '<info></info>' );
	$xml->addChild( 'code', $answer['code'] );
	$xml->addChild( 'text', $answer['text'] );
	
	return $xml;
}

// Update Flag
function update_flag( $data )
{
	$database = Database::getConnection();
	
	if( !valid_token( $data['token'] ) || !is_loggedin() )
	{
		exit();
	}
	
	$error = $_FILES['file']['error'];
	$imgsize = $_FILES['file']['size'];
	$imgtype = $_FILES['file']['type'];
	$imgtempname = $_FILES['file']['tmp_name'];
	list( $width, $height )=getimagesize( $imgtempname );
	
	if( $imgtype != 'image/png' )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Only PNG images are allowed';
	}
	else if( $imgsize > 1500 )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Only PNG files under 1500 bytes are allowed.';
	}
	else if( $width == 0 || $height == 0 )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Please upload a VALID PNG image.';
	}
	else if( $width > 16 || $height > 16 )
	{
		$answer['code'] = 3;
		$answer['text'] = 'The maximum dimensions of the image are 16 x 16.';
	}
	else if( $error == 3 )
	{
		$answer['code'] = 3;
		$answer['text'] = 'Try upload a COMPLETE image. Patience grasshopper.';
	}
	else if ( $error == 0 )
	{
		$result = $database->query
			(
				"SELECT
					locations.code,
					locations.id
				FROM
					locations
				WHERE
					locations.id = ( SELECT teams.location FROM teams WHERE teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "' )"
			);
			
		$current = mysqli_fetch_assoc( $result );
		
		if( $current['id'] != 1 )
		{
			if( $current['code'] != 'def' )
			{
				$code = $current['code'];
			}
			else
			{
				$done = false;
				
				while ( !$done )
				{
					$alphabet = "abcdefghijklmnopqrstuwxyz";
					$code = array();
					$alphaLength = strlen( $alphabet ) - 1;
					for ( $i = 0; $i < 4; $i++ )
					{
						$n = rand( 0, $alphaLength );
						$code[] = $alphabet[$n];
					}
					$code = implode( $code );
					$repeat = $database->query
						(
							"SELECT
								*
							FROM
								locations
							WHERE
								locations.code = '$code'"
						);
					if( mysqli_num_rows( $repeat ) === 0 )
					{
						$done = true;
					}
				}
						
				$update = $database->query
					(
						"UPDATE
							locations
						SET
							code = '$code'
						WHERE
							locations.id = ( SELECT teams.location FROM teams WHERE teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "' )"
					);
			}

			
			$imgname = $code . '.png';
			$location = '/home3/camscsco/public_html/ctf/images/flags/' . $imgname;
			
			if( move_uploaded_file( $imgtempname, $location ) )
			{
				$answer['text'] = 'Your team flag has been successfully updated!';
				$answer['code'] = 1;
			}
			else
			{
				$answer['text'] = 'Something is wrong. That is all the CTF server knows.';
				$answer['code'] = 2;
			}
		}
		else
		{
			$answer['code'] = 3;
			$answer['text'] = 'Please update your team information first before updating your flag.';
		}
	}
	else
	{
		$answer['code'] = 2;
		$answer['text'] = 'You broke something. Seriously. Try re-uploading at a later time.';
	}
	
	$xml = new SimpleXMLElement( '<update></update>' );
	$xml->addChild( 'code', $answer['code'] );
	$xml->addChild( 'text', $answer['text'] );

	return $xml;
}

// Update Information
function update_info( $m1, $m2, $m3, $email, $token )
{
	$database = Database::getConnection();
	
	if( !valid_token( $token ) || !is_loggedin() )
	{
		exit();
	}
	
	$answer = array();
	
	if( !empty( $email ) && !filter_var( $email, FILTER_VALIDATE_EMAIL ) )
	{
		$answer['code'] = 3;
		$answer['text'] = "Please don't update your email address if you're not going to enter a legitimate one.";
	}
	else if( preg_match('/[\#\&\'\"]/', $m1) || preg_match('/[\#\&\'\"]/', $m2) || preg_match('/[\#\&\'\"]/', $m3) || preg_match('/[\#\&\'\"]/', $email) )
	{
		$answer['code'] = 3;
		$answer['text'] = "DON'T ACT LIKE YOU DON'T KNOW THAT YOU HAVE ENTERED DANGEROUS AND ILLEGAL CHARACTERS ON PURPOSE TO DESTROY THIS WEBSITE!";
	}
	else if( strlen( $m1 ) > 20 || strlen( $m2 ) > 20 || strlen( $m3 ) > 20 )
	{
		$answer['code'] = 2;
		$answer['text'] = "Make the names shorter please. Keep them under 20 characters.";
	}
	else
	{
		if( !empty( $email ) )
		{
			$update = $database->query
			(
				"UPDATE
					teams
				SET
					email = '" . $database->real_escape_string( $email ) . "'
				WHERE
					teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "'"
			);
			if ( !$update )
			{
				die( 'MySQL: Syntax errors' );
			}
			
			$answer['code'] = 1;
			$answer['text'] = "Your team information has been update successfully.";
		}
		if( ( !empty( $m1 ) || !empty( $m2 ) || !empty( $m3 ) ) )
		{
			if( !empty( $m1 ) && empty( $m2 ) && empty ( $m3 ) )
			{
				$members = $m1;
			}
			else if( empty( $m1 ) && !empty( $m2 ) && empty ( $m3 ) )
			{
				$members = $m2;
			}
			else if( empty( $m1 ) && empty( $m2 ) && !empty ( $m3 ) )
			{
				$members = $m3;
			}
			else if( !empty( $m1 ) && !empty( $m2 ) && empty ( $m3 ) )
			{
				$members = $m1 . ', ' . $m2;
			}
			else if( !empty( $m1 ) && empty( $m2 ) && !empty ( $m3 ) )
			{
				$members = $m1 . ', ' . $m3;
			}
			else if( empty( $m1 ) && !empty( $m2 ) && !empty ( $m3 ) )
			{
				$members = $m2 . ', ' . $m3;
			}
			else
			{
				$members = $m1 . ', ' . $m2 . ', ' . $m3;
			}
			
			$result = $database->query
			(
				"SELECT
					teams.location
				FROM
					teams
				WHERE
					teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "'"
			);
			if( !$result )
			{
				die( 'MySQL: Syntax errors' );
			}
			
			$newteam = mysqli_fetch_assoc( $result );
			
			if( $newteam['location'] != 1 )
			{
				$update = $database->query
				(
					"UPDATE
						locations
					SET
						members = '$members'
					WHERE
						locations.id = ( SELECT teams.location FROM teams WHERE teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "' )"
				);
			}
			else
			{
				$numeric = $database->query
				(
					"SELECT
						AUTO_INCREMENT
					FROM
						information_schema.TABLES
					WHERE
						TABLE_SCHEMA = 'camscsco_ctf'
					AND
						TABLE_NAME = 'locations'"
				);
				
				$data = mysqli_fetch_assoc( $numeric );
				$next = $data['AUTO_INCREMENT'];
				$code = 'def';
	
				$update1 = $database->query
				(
					"INSERT INTO locations
						(members, code, fails, money)
					VALUES
						('$members', '$code', 0, 100)"
				);
				if( !$update1 )
				{
					die( 'MySQL: Syntax errors' );
				}
				
				$update2 = $database->query
				(
					"UPDATE
						teams
					SET
						location = '$next'
					WHERE
						teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "'"
				);
				if( !$update2 )
				{
					die( 'MySQL: Syntax errors' );
				}
				
				$lastlogin = $database->query
				(
					"UPDATE
						locations
					SET
						lastlogin = NOW()
					WHERE
						locations.id = ( SELECT teams.location FROM teams WHERE teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "' )"
				);
				if( !$lastlogin )
				{
					die( 'MySQL: Syntax error' );
				}
			}
			
			$answer['code'] = 1;
			$answer['text'] = "Your team information has been update successfully.";
		}
	}
	
	$xml = new SimpleXMLElement( '<update></update>' );
	$xml->addChild( 'code', $answer['code'] );
	$xml->addChild( 'text', $answer['text'] );

	return $xml;
}

// Forgot Password
function forgot_pass( $team, $email, $token )
{
	$database = Database::getConnection();
	
	if( !valid_token( $token ) || is_loggedin() )
	{
		exit();
	}
	
	$answer = array();
	
	$result = $database->query
	(
		"SELECT
			*
		FROM
			teams
		WHERE
			teams.email = '" . $database->real_escape_string( $email ) . "'
			AND teams.name = '" . $database->real_escape_string( $team ) . "'"
	);
	
	if( !$result )
	{
		die( 'MySQL: Syntax error' );
	}
	
	if( empty( $team ) )
	{
		$answer['code'] = 2;
		$answer['text'] = 'Please enter a team name.';
	}
	else if( empty( $email ) )
	{
		$answer['code'] = 2;
		$answer['text'] = 'Please enter the email associated with your team name.';
	}
	else if( mysqli_num_rows( $result ) === 1 )
	{
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array();
		$alphaLength = strlen( $alphabet ) - 1;
		for ( $i = 0; $i < 8; $i++ )
		{
			$n = rand( 0, $alphaLength );
			$pass[] = $alphabet[$n];
		}
		$pass = implode( $pass );
		
		$newhash = hash( 'sha512', $pass );
		
		$subresult = $database->query
		(
			"UPDATE
				teams
			SET
				password = '" . $database->real_escape_string( $newhash ) . "'
			WHERE
				teams.name = '" . $database->real_escape_string( $team ) . "'"
		);

		if( !$subresult )
		{
			die( 'MySQL: Syntax error' );
		}
		else
		{
			$to = $email;
			$subject = 'CTF Temporary Password';
			$message = "A temporary password has been created for your account. Please login and change your password to something that you hopefully won't forget again.\nUsername: " . $team . "\nPassword: " . $pass;
			$headers = 'From: "CTF Admin" <admin@ctf.camscsc.org>';	
			mail($to, $subject, $message, $headers);
			$answer['code'] = 1;
			$answer['text'] = 'A temporary password has been sent to the email account associated with your team.';
		}
	}
	else
	{
		$answer['code'] = 3;
		$answer['text'] = 'The team name and email you have entered do not match the records.';
	}
	
	$xml = new SimpleXMLElement( '<forgot></forgot>' );
	$xml->addChild( 'code', $answer['code'] );
	$xml->addChild( 'text', $answer['text'] );

	return $xml;
}

// Change Password

function change_pass( $current, $new, $repeat, $token )
{
	$database = Database::getConnection();

	if( !valid_token( $token ) || !is_loggedin() )
	{
		exit();
	}
	
	$answer = array();
	
	$hash = hash( 'sha512', $current );
	
	$result = $database->query
	(
		"SELECT
			teams.password,
			teams.email
		FROM
			teams
		WHERE
			teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "'"
	);
		
	if( !$result )
	{
		die( 'MySQL: Syntax errors' );
	}
		
	$data = mysqli_fetch_assoc( $result );
		
	if( $hash !== $data['password'] )
	{
		$answer['code'] = 3;
		$answer['text'] = 'The current password you have entered is incorrect.';
	}
	else if( empty( $new ) )
	{
		$answer['code'] = 2;
		$answer['text'] = 'The new password field cannot be left blank.';
	}
	else if( $new !== $repeat )
	{
		$answer['code'] = 2;
		$answer['text'] = 'The passwords you have entered do not match.';
	}
	else
	{
		$newhash = hash( 'sha512', $new );
		
		$subresult = $database->query
		(
			"UPDATE
				teams
			SET
				password = '" . $database->real_escape_string( $newhash ) . "'
			WHERE
				teams.id = '" . $database->real_escape_string( $_SESSION['teamid'] ) . "'"
		);

		if( !$subresult )
		{
			die( 'MySQL: Syntax error' );
		}

		$answer['code'] = 1;
		$answer['text'] = 'Your password has been changed successfully.';
		
		$to = $data['email'];
		$subject = 'CTF Password Change';
		$message = 'The password to your CTF account has recently been changed on ' . date('Y-m-d') . ' at ' . date('H:i:s') . '. If you did not authorize this change, please reset your password immediately.' . "\r\n" . 'You may reply to this message if you have any questions or concerns.';
		$headers = 'From: "CTF Admin" <admin@ctf.camscsc.org>';	
		mail($to, $subject, $message, $headers);
	}
	
	$xml = new SimpleXMLElement( '<password></password>' );
	$xml->addChild( 'code', $answer['code'] );
	$xml->addChild( 'text', $answer['text'] );

	return $xml;
}

function send_message( $password, $message, $image, $script, $token )
{
	$database = Database::getConnection();
	
	if( !valid_token( $token ) )
	{
		exit();
	}

	$answer = array();

	if( hash( 'sha512', $password ) !== MESSAGE_KEY )
	{
		$answer['code'] = 3;
	}
	else
	{
		$directory = 'images/avatars/';
		$real_base = realpath( $directory );
		$image_relative = $directory . $image;
		$image_absolute = realpath( $image_relative );

		if( ( $image_absolute === false ) || ( strpos( $image_absolute, $real_base ) !== 0 ) || !file_exists( $image_absolute ) )
		{
			$answer['code'] = 2;
		}
		else
		{
			$result = $database->query
			(
				"INSERT INTO
					ticker
					(
						text,
						image,
						script
					)
					VALUES
					(
						'" . $database->real_escape_string( $message ) . "',
						'" . $database->real_escape_string( $image ) . "',
						'" . $database->real_escape_string( $script ) . "'
					)"
			);

			if( !$result )
			{
				die( 'MySQL: Syntax error' );
			}

			$answer['code'] = 1;
		}
	}

	$xml = new SimpleXMLElement( '<message></message>' );
	$xml->addChild( 'code', $answer['code'] );

	return $xml;
}

function terminal( $command, $token )
{
	if( !valid_token( $token ) )
	{
		exit();
	}
	
	$answer['command'] = '';
	$answer['output'] = '';
	
	$command = strtolower( $command );

	switch( $command )
	{
		case 'help':
			$answer['output'] =
				"page [command]\n" .
				"   []      Main page\n" .
				"   [-a]    Authentication page\n" .
				"   [-at]   Attack another team\n" .
				"   [-c]    Challenges page\n" .
				"   [-sf]   Submit flag\n" .
				"   [-set]  Settings\n" .
				"   [-ts]   View team statistics\n" .
				"   [-vc]   View completed challenges\n" .
				"   [-vm]   View system messages\n" .
				"   [-vf]   View fail counter\n" .
				"   [-vt]   View team-team attacks\n" .
				"admin [command]\n" .
				"   [-ca]   Create an account\n" .
				"   [-cp]   Change password\n" .
				"   [-ui]   Update team information\n" .
				"   [-uf]   Update team flag\n" .
				"   [-fp]   Forgot password\n" .
				"   [-hc]   Toggle HackerCat\n" .
				"   [-s]    Toggle Sound\n" .
				"   [-f]    Font size (add -h more information)\n" .
				"   [-r]    Refresh rate (add -h for more information)\n" .
				"refresh [command]\n" .
				"   []      Refresh page\n" .
				"   [-a]    Refresh latest attacks\n" .
				"   [-t]    Refresh ticker\n" .
				"   [-r]    Refresh rankings\n";
			break;
		
		case 'admin -f -h':
			$answer['output'] =
				"admin -f [size]\n" .
				"   [s]     Small\n" .
				"   [m]     Medium\n" .
				"   [l]     Large\n" .
				"   [xl]    Extra large\n";
			break;
			
		case 'admin -r -h':
			$answer['output'] =
				"admin -r [rate]\n" .
				"   [0]     Off\n" .
				"   [1]     Slow\n" .
				"   [2]     Medium\n" .
				"   [3]     Fast\n";
			break;
		
		case 'page -vt':
			$answer['command'] = 'view_team_attacks();';
			$answer['output'] = 'Loading view team-team attacks page.';
			break;
		
		case 'page -at':
			if( is_loggedin() )
			{
				$answer['command'] = 'attack_team();';
				$answer['output'] = 'Loading attack team page.';
			}
			else
			{
				$answer['output'] = 'You have to be authenticated.';
			}
			break;
				
		case 'page':
			$answer['command'] = 'clear_mainframe( false );';
			$answer['output'] = 'Loading main page.';
			break;

		case 'page -a':
			if( is_loggedin() )
			{
				$answer['command'] = 'page_logout();';
				$answer['output'] = 'Loading logout page.';
			}
			else
			{
				$answer['command'] = 'page_login();';
				$answer['output'] = 'Loading login page.';
			}
			break;

		case 'page -c':
			$answer['command'] = 'page_challenges();';
			$answer['output'] = 'Loading challenges page.';
			break;

		case 'page -sm':
			$answer['command'] = 'page_sendmessage();';
			$answer['output'] = 'Loading send system messages page.';
			break;

		case 'page -set':
			$answer['command'] = 'page_settings();';
			$answer['output'] = 'Loading settings page.';
			break;

		case 'page -sf':
			if( is_loggedin() )
			{
				$answer['command'] = 'page_submit();';
				$answer['output'] = 'Loading submit page.';
			}
			else
			{
				$answer['output'] = 'You have to be authenticated.';
			}
			break;
			
		case 'page -vc':
			$answer['command'] = 'view_completed();';
			$answer['output'] = 'Loading Strategic Challenge Planner.';
			break;		
			
		case 'page -ts':
			$answer['command'] = 'team_stats();';
			$answer['output'] = 'Loading team statistics page.';
			break;
			
		case 'page -vm':
			$answer['command'] = 'view_messages();';
			$answer['output'] = 'Loading messages page.';
			break;
		
		case 'page -vf':
			$answer['command'] = 'view_fails();';
			$answer['output'] = 'Loading Le Fail Counter.';
			break;

		case 'admin -fp':
			if( !is_loggedin() )
			{
				$answer['command'] = 'forgot_pass();';
				$answer['output'] = 'Loading forgot password page.';
			}
			else
			{
				$answer['output'] = 'You are already logged in.';
			}
			break;
		
		case 'admin -cp':
			if( is_loggedin() )
			{
				$answer['command'] = 'change_pass();';
				$answer['output'] = 'Loading password change page.';
			}
			else
			{
				$answer['output'] = 'You have to be authenticated.';
			}
			break;
			
		case 'admin -ui':
			if( is_loggedin() )
			{
				$answer['command'] = 'update_info();';
				$answer['output'] = 'Loading team info update page.';
			}
			else
			{
				$answer['output'] = 'You have to be authenticated.';
			}
			break;
			
		case 'admin -uf':
			if( is_loggedin() )
			{
				$answer['command'] = 'update_flag();';
				$answer['output'] = 'Loading flag update page.';
			}
			else
			{
				$answer['output'] = 'You have to be authenticated.';
			}
			break;
			
		case 'admin -ca':
			$answer['command'] = 'create_account();';
			$answer['output'] = 'Loading account creation page.';
			break;
			
		case 'admin -hc':
			$answer['command'] = 'set_cat();';
			break;
			
		case 'admin -s':
			$answer['command'] = 'set_sound();';
			break;
			
		case 'admin -f s':
			$answer['command'] = 'set_fontsize("0");';
			$answer['output'] = 'Setting font size to small.';
			break;
			
		case 'admin -f m':
			$answer['command'] = 'set_fontsize("1");';
			$answer['output'] = 'Setting font size to medium.';
			break;
			
		case 'admin -f l':
			$answer['command'] = 'set_fontsize("2");';
			$answer['output'] = 'Setting font size to large.';
			break;
			
		case 'admin -f xl':
			$answer['command'] = 'set_fontsize("3");';
			$answer['output'] = 'Setting font size to extra large.';
			break;
			
		case 'admin -r 0':
			$answer['command'] = 'set_refresh_rate("0");';
			$answer['output'] = 'Turning off refresh.';
			break;
			
		case 'admin -r 1':
			$answer['command'] = 'set_refresh_rate("1");';
			$answer['output'] = 'Setting refresh rate to low.';
			break;
			
		case 'admin -r 2':
			$answer['command'] = 'set_refresh_rate("2");';
			$answer['output'] = 'Setting refresh rate to medium.';
			break;		

		case 'admin -r 3':
			$answer['command'] = 'set_refresh_rate("3");';
			$answer['output'] = 'Setting refresh rate to high.';
			break;			

		case 'refresh':
			$answer['command'] = 'window.location.reload();';
			$answer['output'] = 'Refreshing window.';
			break;

		case 'refresh -a':
			$answer['command'] = 'last_attack = "0"; check_attacks();';
			$answer['output'] = 'Refreshing attacks.';
			break;

		case 'refresh -t':
			$answer['command'] = 'last_message = "0"; check_ticker();';
			$answer['output'] = 'Refreshing ticker.';
			break;

		case 'refresh -r':
			$answer['command'] = 'update_ranking();';
			$answer['output'] = 'Refreshing ranking.';
			break;
			
		default:
			$answer['output'] = 'Unknown command. Type help for more information.';
			break;
	}

	$xml = new SimpleXMLElement( '<terminal></terminal>' );
	$xml->input = $command;
	$xml->command = $answer['command'];
	$xml->output = $answer['output'];

	return $xml;
}

?>
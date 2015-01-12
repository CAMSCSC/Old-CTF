<?php
include('jpgraph/jpgraph.php');
include('jpgraph/jpgraph_line.php');
include('config.php');

if( !isset( $_GET['m'] ) || !is_string( $_GET['m'] ) )
{
	exit();
}

switch( $_GET['m'] )
{
	case 'team_score':
		if( !isset( $_GET['id'] ) || !isset( $_GET['type'] ) || !isset( $_GET['w'] ) || !isset( $_GET['h'] ) )
		{
			exit();
		}
		team_score( $_GET['id'], $_GET['type'], $_GET['w'], $_GET['h'] );
}
		
function dateDifference( $date_1 , $date_2 , $differenceFormat = '%a' )
{
    $datetime1 = date_create($date_1);
    $datetime2 = date_create($date_2);
   
    $interval = date_diff($datetime1, $datetime2);
   
    return $interval->format($differenceFormat);
   
}

function replace_zero(&$item, $key, $till)
{
	if( $key < $till )
	{
		$item = 'x';
	}
}

function team_score( $id, $type, $w, $h )
{
	$database = Database::getConnection();
	$id = $database->real_escape_string( $id );
	$primary = $database->query( "SELECT teams.registration FROM teams WHERE teams.id = '$id'" );
	$temp = mysqli_fetch_assoc( $primary );
	if( mysqli_num_rows( $primary ) !== 1 ) { exit(); }
	if( $type != 'score' && $type != 'ap' ) { exit(); }
	if( !is_int( (int) $w ) || !is_int( (int) $h ) ) { exit(); }
	if( (int) $w > 5000 ) { $w = 5000; } if( (int) $h > 1000 ) { $h = 1000; } 
	$start = date('Y-m-d', strtotime('today', strtotime($temp['registration'])));
	$today = date( "Y-m-d" ); $date = $start;
	$data1 = array(); $data2 = array(); $data3 = array(); $data4 = array(); $data5 = array();
	
	for( $i = 0; $i <= (int) dateDifference($start, $today); $i++ )
	{
		$date = date( 'Y-m-d', strtotime( $date. ' + 1 days' ) );
		
		if( $type == 'score' )
		{
			$result = $database->query
			(
				"SELECT
					COALESCE( SUM( challenges.score ), 0 ) + COALESCE( SUM( attacks.additional ), 0 ) + ( SELECT COALESCE( SUM( interact.value ), 0 ) FROM interact WHERE interact.to = '$id' AND interact.success = 1 AND interact.date <= '$date' ) + ( SELECT COALESCE( SUM( bonus.value ), 0 ) FROM bonus WHERE bonus.team = '$id' AND bonus.date <= '$date') AS data
				FROM
					challenges, attacks
				WHERE
					attacks.team = '$id'
					AND attacks.challenge = challenges.id
					AND attacks.date <= '$date'"
			);
			$reply = mysqli_fetch_assoc( $result ); array_push( $data1, $reply['data'] );
		}
		else if( $type == 'ap' )
		{
			$q1 = $database->query("SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) AS totalap FROM interact WHERE interact.from = '$id' AND interact.date <= '$date'");
			$q2 = $database->query("SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) AS sap FROM interact WHERE interact.from = '$id' AND interact.date <= '$date' AND interact.success = 1");
			$q3 = $database->query("SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) AS fap FROM interact WHERE interact.from = '$id' AND interact.date <= '$date' AND interact.success = 0");
			$q4 = $database->query("SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) AS fat FROM interact WHERE interact.to = '$id' AND interact.date <= '$date' AND interact.success = 0");
			$q5 = $database->query("SELECT COALESCE( (SUM( interact.value ) * -1), 0 ) AS sat FROM interact WHERE interact.to = '$id' AND interact.date <= '$date' AND interact.success = 1");

			$r1 = mysqli_fetch_assoc( $q1 ); array_push( $data1, $r1['totalap'] );
			$r2 = mysqli_fetch_assoc( $q2 ); array_push( $data2, $r2['sap'] );
			$r3 = mysqli_fetch_assoc( $q3 ); array_push( $data3, $r3['fap'] );
			$r4 = mysqli_fetch_assoc( $q4 ); array_push( $data4, $r4['fat'] );
			$r5 = mysqli_fetch_assoc( $q5 ); array_push( $data5, $r5['sat'] );			
		}
	}
	
	$graph = new Graph( $w ,$h );
	$graph->SetScale('int');
	//$graph->yaxis->scale->SetGrace(10);
	$theme_class = new UniversalTheme();
	$graph->SetTheme($theme_class);
	
	if( $type == 'score' )
	{
		$min = 0;
		if( in_array( '0', $data1 ) )
		{
			$min = max(array_keys($data1, '0'));
		}
		
		if( $min > 0 )
		{
			$min -= 1;
		}
		$graph->title->Set('CTF Score Progression');
		$graph->xaxis->scale->SetAutoMin($min);
		array_walk( $data1, 'replace_zero', $min );
		$p = new LinePlot( $data1 );
		$graph->Add($p);
		$p->SetLegend( "Total Score" );
	}
	else if( $type == 'ap' )
	{
		$min = 0;
		if( in_array( '0', $data1 ) && in_array( '0', $data2 ) && in_array( '0', $data3 ) && in_array( '0', $data4 ) && in_array( '0', $data5 ) )
		{
			$min = min( max(array_keys($data1, '0')), max(array_keys($data2, '0')), max(array_keys($data3, '0')), max(array_keys($data4, '0')), max(array_keys($data5, '0')) );
		}
				
		if( $min > 0 )
		{
			$min -= 1;
		}
		$graph->title->Set('Attack Points Data');
		//$graph->SetClipping();
		$graph->xaxis->scale->SetAutoMin($min);
		//array_walk( $data1, 'replace_zero', $min );
		//array_walk( $data2, 'replace_zero', $min );
		//array_walk( $data3, 'replace_zero', $min );
		//array_walk( $data4, 'replace_zero', $min );
		//array_walk( $data5, 'replace_zero', $min );
		$p1 = new LinePlot( $data1 ); $graph->Add($p1); $p1->SetLegend( "Used AP" );
		$p2 = new LinePlot( $data2 ); $graph->Add($p2); $p2->SetLegend( "Successful Attacks" );
		$p3 = new LinePlot( $data3 ); $graph->Add($p3); $p3->SetLegend( "Failed Attacks" );
		$p4 = new LinePlot( $data4 ); $graph->Add($p4); $p4->SetLegend( "Successful Defences" );
		$p5 = new LinePlot( $data5 ); $graph->Add($p5); $p5->SetLegend( "Failed Defences" );
	}
	
	$graph->title->SetFont(FF_DV_SANSSERIF, FS_BOLD, 20);
	$graph->SetBox(false);
	$graph->img->SetAntiAliasing();
	$graph->yaxis->HideZeroLabel();
	$graph->yaxis->HideLine(false);
	$graph->yaxis->HideTicks(false,false);
	$graph->xgrid->Show();
	$graph->xgrid->SetLineStyle("solid");
	$graph->xgrid->SetColor('#E3E3E3');
	$graph->legend->SetAbsPos( $w / 2 , $h - 5, 'center', 'bottom');

	// Output line
	$graph->Stroke();
}

function score_progression()
{
	$database = Database::getConnection();
	$start = "2014-9-3";
	$today = date( "Y-m-d" );
	$date = $start;

	$names = array();

	for( $i = 0; $i <= (int) dateDifference($start, $today); $i++ )
	{
		$date = date( 'Y-m-d', strtotime( $date. ' + 1 days' ) );
		$result = $database->query
		(
			"SELECT
				teams.id,
				teams.name,
				locations.members,
				locations.code,
				(
					SELECT
						COALESCE( SUM( challenges.score ), 0 ) + COALESCE( SUM( attacks.additional ), 0 ) + ( SELECT COALESCE( SUM( interact.value ), 0 ) FROM interact WHERE interact.to = teams.id AND interact.success = 1 AND interact.date <= '$date' ) + ( SELECT COALESCE( SUM( bonus.value ), 0 ) FROM bonus WHERE bonus.team = teams.id AND bonus.date <= '$date')
					FROM
						challenges, attacks
					WHERE
						attacks.team = teams.id
						AND attacks.challenge = challenges.id
						AND attacks.date <= '$date'
						AND teams.id != 1
				) AS score
			FROM
				teams
			JOIN
				locations
			ON
				teams.location = locations.id
			ORDER BY
				teams.id ASC"
		);

		while( $reply = mysqli_fetch_assoc( $result ) )
		{
			${ "data" . $reply['id'] }[$i] = $reply['score'];
			if( $i == 0 )
			{
				array_push( $names, $reply['name'] );
			}
		}
	}

	$graph = new Graph(1920,1080);
	$graph->SetScale('textint');
	$graph->yaxis->scale->SetGrace(10);

	$theme_class = new UniversalTheme();
	$graph->SetTheme($theme_class);

	$graph->img->SetAntiAliasing(true);

	$graph->title->Set('CTF Score Progression');
	$graph->title->SetFont(FF_DV_SANSSERIF, FS_BOLD, 50);

	$graph->SetBox(false);
	$graph->img->SetAntiAliasing();

	//$graph->yaxis->HideZeroLabel();
	$graph->yaxis->HideLine(false);
	$graph->yaxis->HideTicks(false,false);


	$graph->xgrid->Show();
	$graph->xgrid->SetLineStyle("solid");
	$graph->xgrid->SetColor('#E3E3E3');

	$counter = 0;

	for( $i = 0; $i <= 24; $i++ )
	{
		if( isset( ${ "data" . $i } ) )
		{
			${ "p" . $i } = $p1 = new LinePlot( ${ "data" . $i } );
			$graph->Add( ${ "p" . $i } );
			$p1->SetLegend( $names[$counter] );
			$counter += 1;
		}
	}

	$graph->legend->SetFrameWeight(1);

	// Output line
	$graph->Stroke();
}
?>
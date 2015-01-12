//View changelog.txt for update info and licensing info.

// Global variables

var attack_list = new Array();
var explosion_list = new Array();
var last_attack = '0';
var last_message = '0';
var allow_requests = true;
var muted = false;
var alpha_max = 0;
var loggedin = false;
var terminal = false;
var token = false;
var canvas = false;
var context = false;
var update_rate = localStorage.getItem( 'csc_update_rate' );
var draw_animation = true;
var fontsize = localStorage.getItem( 'csc_font_size' );
var b_height = $(window).height();
var b_width = $(window).width();
var world_height = 0;
var world_width = 0;
var constant = 0;
var allow_cat = ( localStorage.getItem( 'csc_allow_cat' ) == 'true' ); 
var allow_sound = ( localStorage.getItem( 'csc_allow_sound' ) == 'true' ); 
var cat_active = false;
var ticker_active = false;
var terminaldata = "";

// For Helix...
var Helix = true;

// Classes

function cAttack( origin_x, origin_y, teamname, members, flag, score )
{
	this.origin_x = parseInt( origin_x );
	this.origin_y = parseInt( origin_y );
	this.current_x = this.origin_x;
	this.current_y = this.origin_y;
	this.teamname = teamname;
	this.members = members;
	this.flag = flag;
	this.score = parseFloat( score );

	this.fadein = true;
	this.fadeout = false;
	this.wait = false;
	this.fadein_original = 50;
	this.fadeout_original = 20;
	this.wait_original = 25;
	this.fadein_current = this.fadein_original;
	this.fadeout_current = this.fadeout_original;
	this.wait_current = this.wait_original;

	this.get_alpha = function()
	{
		if( this.fadein )
		{
			return ( 1 - ( this.fadein_current / this.fadein_original ) );
		}
		else if( this.fadeout )
		{
			return ( this.fadeout_current / this.fadeout_original );
		}
		else
		{
			return 1;
		}
	}

	this.get_origin_x = function()
	{
		return this.origin_x;
	}

	this.get_origin_y = function()
	{
		return this.origin_y;
	}

	this.get_current_x = function()
	{
		return this.current_x;
	}

	this.get_current_y = function()
	{
		return this.current_y;
	}

	this.get_members = function()
	{
		return this.members;
	}

	this.get_teamname = function()
	{
		return this.teamname;
	}

	this.get_flag = function()
	{
		return this.flag;
	}

	this.get_score = function()
	{
		return this.score;
	}

	this.update = function()
	{
		// Tip: Calculate the positions with timestamps (not ticks) for more complex animations.
		// In this case it doesn't matter, because the calculations are very simple and fast.

		if( this.fadein )
		{
			if( this.fadein_current-- < 1 )
			{
				this.fadein = false;
			}
		}
		else if( this.wait )
		{
			if( this.wait_current-- < 1 )
			{
				this.wait = false;
				this.fadeout = true;
			}
		}
		else if( this.fadeout )
		{
			if( this.fadeout_current-- < 1 )
			{
				return true;
			}
		}
		else if( this.current_x < Math.floor(world_width/2) )
		{
			this.current_x++;
		}
		else if( this.current_x > Math.floor(world_width/2) )
		{
			this.current_x--;
		}
		else if( this.current_y < Math.floor(world_height/2) )
		{
			this.current_y++;
		}
		else if( this.current_y > Math.floor(world_height/2) )
		{
			this.current_y--;
		}
		else
		{
			this.wait = true;
		}

		return false;
	}
}

function cExplosion( x, y, maxsize, maxalpha, speed )
{
	this.x = x;
	this.y = y;
	this.maxsize = maxsize;
	this.maxalpha = maxalpha;
	this.speed = speed;
	this.size = 0;

	this.get_x = function()
	{
		return this.x;
	}

	this.get_y = function()
	{
		return this.y;
	}

	this.get_maxsize = function()
	{
		return this.maxsize;
	}

	this.get_size = function()
	{
		return this.size * speed;
	}

	this.get_alpha = function()
	{
		return ( this.maxalpha - 0.1 ) * ( this.get_size() / this.maxsize ) + 0.1;
	}

	this.update = function()
	{
		if( this.get_size() < this.maxsize )
		{
			this.size++;
			return false;
		}
		else
		{
			return true;
		}
	}
}

// Random Generator
function rand_between(min, max)
{
	return Math.floor( Math.random() * ( max - min + 1 ) + min );
}

// Class helper

function add_attack( teamname, members, code, score )
{
	var flag = new Image();
	flag.src = 'images/flags/' + code + '.png';
	
	var origin_x = rand_between(20, world_width - 300);
	var origin_y = rand_between(20, world_height - 100);
	
	while ( Math.abs( world_width/2 - origin_x ) < 20 || Math.abs( world_height/2 - origin_y ) < 20 )
	{
		origin_x = rand_between(20, world_width - 300);
		origin_y = rand_between(20, world_height - 100);
	}
	while ( origin_x < 325 && origin_y > world_height - 90 )
	{
		origin_x = rand_between(20, world_width - 300);
		origin_y = rand_between(20, world_height - 100);
	}

	attack_list.push( new cAttack( origin_x, origin_y, teamname, members, flag, score ) );
}

function add_explosion( x, y, maxsize, maxalpha, speed )
{
	explosion_list.push( new cExplosion( x, y, maxsize, maxalpha, speed ) );
}

// Drawing

function clear()
{
	context.clearRect( 0, 0, canvas.width, canvas.height );
}

function render()
{
	clear();

	attack_list.forEach
	(
		function( element, index, array )
		{
			var alpha = element.get_alpha() * alpha_max;

			context.strokeStyle = '#00EE00'; //color of name (green)
			context.fillStyle = "rgba(0, 238, 0, " + alpha + ")"; //color of name (green)
			context.globalAlpha = alpha * 0.6;

			// Flag
			context.drawImage
			(
				element.get_flag(),
				element.get_origin_x() + 5,
				element.get_origin_y() + 2
			);

			context.font = "12px Ubuntu Mono";

			// members
			context.fillText
			(
				'[' + element.get_members() + ']',
				element.get_origin_x() + 5, //27
				element.get_origin_y() + 30 //14
			);

			// Teamname
			context.fillText
			(
				element.get_teamname(),
				element.get_origin_x() + 27, //5
				element.get_origin_y() + 14 //30
			);

			// Position
			context.fillText
			(
				'[' + element.get_current_x() + ',' + element.get_current_y() + ']',
				element.get_origin_x() + 5,
				element.get_origin_y() + 46
			);

			context.strokeStyle = '#00cccc'; //color of the pointer, Erin's favorite color?
			context.globalAlpha = alpha;
			context.beginPath();

			// Vertical line
			context.moveTo( element.get_current_x(), 0 );
			context.lineTo( element.get_current_x(), element.get_current_y() - 3 );
			context.moveTo( element.get_current_x(), element.get_current_y() + 3 );
			context.lineTo( element.get_current_x(), world_height - 3 );

			// Horizontal line
			context.moveTo( 0, element.get_current_y() );
			context.lineTo( element.get_current_x() - 3, element.get_current_y() );
			context.moveTo( element.get_current_x() + 3, element.get_current_y() );
			context.lineTo( world_width - 3, element.get_current_y() );

			// Circle
			context.arc( element.get_current_x(), element.get_current_y(), 10, 0, Math.PI*2, true );

			context.closePath();
			context.stroke();
		}
	);

	explosion_list.forEach
	(
		function( element, index, array )
		{
			var x = element.get_x();
			var y = element.get_y();
			var size = element.get_size();
			var alpha = element.get_alpha() * alpha_max;

			context.strokeStyle = '#00EE00'; //color of circle
			context.fillStyle = 'rgba(0, 238, 0, 0.7)'; //color of circle (+ alpha)
			context.globalAlpha = alpha;

			context.beginPath();
			context.arc( x, y, size, 0, 2 * Math.PI, true );
			context.closePath();

			context.stroke();
			context.fill();
		}
	);
}

function update()
{
	attack_list.forEach
	(
		function( element, index, array )
		{
			var finished = element.update();

			if( finished )
			{
				add_explosion
				(
					element.get_current_x(),
					element.get_current_y(),
					Math.floor(20 * constant * element.get_score()),	// Max radius
					rand_between( 20, 80 ) / 100,	// Max alpha
					rand_between( 20, 80 ) / 100	// Speed
				);

				// Remove attack
				delete element;
				array.splice( index, 1 );

				// Update the scoreboard
				update_ranking();
			}
		}
	);

	explosion_list.forEach
	(
		function( element, index, array )
		{
			var finished = element.update();

			if( finished )
			{
				// Remove explosion
				delete element;
				array.splice( index, 1 );
			}
		}
	);
}

function animate()
{
	setTimeout
	(
		function()
		{
			requestAnimationFrame( animate );
		},
		1
	);

	if( !draw_animation )
	{
		return;
	}

	if( ( attack_list.length > 0 ) || ( explosion_list.length > 0 ) )
	{
		// Disable ajax calls
		allow_requests = false;

		// Draw the animation
		render();
	}
	else if( !allow_requests )
	{
		// Clear the canvas one last time
		clear();

		// Enable ajax calls
		allow_requests = true;
	}
}

// Attacks

function check_attacks()
{
	var formData = new FormData();    
	formData.append( 'last', last_attack );
	
	$.ajax({
		type: 'POST',
		url: 'ajax.php?m=get_attacks',
		data: formData,
		processData: false, 
		contentType: false,
		success: function( xml )
		{
			$( xml ).find( 'attack' ).each
			(
				function()
				{
					last_attack = $( this ).find( 'id' ).text();

					add_attack
					(
						$( this ).find( 'teamname' ).text(),
						$( this ).find( 'members' ).text(),
						$( this ).find( 'code' ).text(),
						$( this ).find( 'score' ).text()
					);
				}
			);
		}
	});
}

function attack_wait()
{
	if( allow_requests )
	{
		check_attacks();
	}

	setTimeout
	(
		function()
		{
			attack_wait()
		},
		get_update_rate()
	);
}

// Ranking

function minimize_ranking()
{
	//$( '#rankingframe' ).css( 'height', world_height - 255 + 'px' );
	var temp_data = world_height - 255;
	$( '#rankingframe' ).animate({height: temp_data + 'px'}, 200 );
	$( '#scrollable' ).css( 'max-height', world_height - 295 + 'px' );
}

function maximize_ranking()
{
	$( '#rankingframe' ).animate({height: world_height + 'px'}, 200 );
	$( '#scrollable' ).css( 'max-height', world_height - 40 + 'px' );
}

function update_ranking()
{
	$.get
	(
		'ajax.php?m=get_ranking',
		function( xml )
		{
			var table = $( '<table>' );

			$( xml ).find( 'team' ).each
			(
				function()
				{
					var trow = $( '<tr>' );
					$( '<td>' ).text( $( this ).find( 'rank' ).text() ).appendTo( trow );
					$( '<td>' ).html( '<img alt="flag" class="flagmove" src="images/flags/' + $( this ).find( 'code' ).text() + '.png" alt="flag" />' ).appendTo( trow );
					$( '<td>' ).text( $( this ).find( 'name' ).text() ).appendTo( trow );
					$( '<td>' ).addClass( 'score' ).text( $( this ).find( 'score' ).text() ).appendTo( trow );

					trow.appendTo( table );
				}
			);

			$( '#ranking' ).html( table.html() );
		}
	);
}

//Sound on/off
function set_sound( data )
{
	switch ( data )
	{
		case '1':
			localStorage.setItem( 'csc_allow_sound', 'true' );
			allow_sound = true;
			break;
			
		case '0':
			localStorage.setItem( 'csc_allow_sound', 'false' );
			allow_sound = false;
			break;
			
		default:
			if( allow_sound == true )
			{
				localStorage.setItem( 'csc_allow_sound', 'false' );
				allow_sound = false;
				terminaldata = "Turning sound off.";
			}
			else
			{
				localStorage.setItem( 'csc_allow_sound', 'true' );
				allow_sound = true;
				terminaldata = "Turning sound on.";
			}
			break;
	}
}

//HackerCat on/off
function set_cat( data )
{
	switch ( data )
	{
		case '1':
			localStorage.setItem( 'csc_allow_cat', 'true' );
			allow_cat = true;
			break;
			
		case '0':
			localStorage.setItem( 'csc_allow_cat', 'false' );
			allow_cat = false;
			break;
			
		default:
			if( allow_cat == true )
			{
				localStorage.setItem( 'csc_allow_cat', 'false' );
				allow_cat = false;
				terminaldata = "Turning HackerCat off.";
			}
			else
			{
				localStorage.setItem( 'csc_allow_cat', 'true' );
				allow_cat = true;
				terminaldata = "Turning HackerCat on.";
			}
			break;
	}
}

//General Sound Player
function play_sound( location, repeat )
{
	myAudio = new Audio( location ); 
	myAudio.addEventListener('ended', function() {
		this.currentTime = 0;
		if( allow_sound && repeat )
		{
			this.play();
		}
	}, false);
	
	if( allow_sound )
	{
		myAudio.play();
	}
}

// Ticker

function check_ticker()
{
	$.get
	(
		'ajax.php?m=get_message',
		function( xml )
		{
			$( xml ).find( 'announcement' ).each
			(
				function()
				{
					var id = $( this ).find( 'id' ).text();

					if( ( id ) && ( id != last_message ) && !cat_active )
					{
						last_message = id;
						ticker_active = true;
						
						show_message
						(
							$( this ).find( 'text' ).text(),
							$( this ).find( 'image' ).text()
						);

						try
						{
							eval( $( this ).find( 'script' ).text() );
						}
						catch( e ) {}
					}
				}
			);
		}
	);
}

function show_message( text, image )
{
	$( '#tickerframe' ).html( '<p id="ticker"></p>' );
	$( '#ticker' ).html( '<span class="type"></span>' );
	
	$(".type").typed({
        strings: [text, "End Transmission"],
        typeSpeed: 40,
		backDelay: text.length * 200,
		callback: function(){ reset_ticker( false ) }
    });
	
	//$( '#ticker' ).text( text );
	$( '#announcements' ).css( 'display', 'inline-block' );
	$( '#announcements' ).css( 'background-image', 'url(images/avatars/' + image + ')' );
	
	minimize_ranking();
}

function show_cat( text )
{
	$( '#tickerframe' ).html( '<p id="ticker"></p>' );
	$( '#ticker' ).html( '<span class="type"></span>' );
	
	$(".type").typed({
        strings: [text, "Meow, Meow."],
        typeSpeed: 30,
		backDelay: text.length * 150,
		callback: function(){ reset_ticker( true ) }
    });
	
	//$( '#ticker' ).text( text );
	$( '#announcements' ).css( 'display', 'inline-block' );
	$( '#announcements' ).css( 'background-image', 'url(images/avatars/cat.png)' );
	var spacing = Math.floor( text.length / 3);
	$( '#announcements' ).animate( {'border-spacing': spacing},{step: function(now, fx) {$(fx.elem).css("background-position", "0px "+now+"px");}, duration: 5000}) ;

	minimize_ranking();
}

function reset_ticker( cat )
{
	$( '#announcements' ).fadeOut
	(
		'slow',
		function()
		{
			maximize_ranking();
		}
	);
	
	if( cat )
	{
		cat_active = false;
	}
	else
	{
		ticker_active = false;
	}
}

function ticker_wait()
{
	if( allow_requests )
	{
		check_ticker();
	}

	setTimeout
	(
		function()
		{
			ticker_wait()
		},
		Math.floor( get_update_rate() * 1.2 )
	);
}

// Pages

function clear_mainframe( active )
{
	if( active )
	{
		$( '#challenges' ).hide();
		$( '#submit' ).hide();
		$( '#authenticate' ).hide();
		$( '#terminal' ).hide();
		$( '.separator', '#menu' ).hide();
		$( '#close' ).show();

		$( '#innercontent' ).empty();
		$( '#innercontent' ).css( 'display', 'inline-block' );

		alpha_max = 0.1;
		$( '#world' ).css( {'background-image' : 'url(../images/backgrounds/milky.png), url(../images/backgrounds/shield.jpg)', 'background-repeat' : 'repeat, no-repeat', 'background-position' : 'center center', 'background-size' : 'auto, cover'} );
	}
	else
	{
		$( '#challenges' ).show();
		$( '#submit' ).show();
		$( '#authenticate' ).show();
		$( '#terminal' ).show();
		$( '.separator', '#menu' ).show();
		$( '#close' ).hide();

		$( '#innercontent' ).empty();
		$( '#innercontent' ).css( 'display', 'none' );

		alpha_max = 1;
		$( '#world' ).css( {'background-image' : 'url(../images/backgrounds/shield.jpg)', 'background-repeat' : 'no-repeat', 'background-size' : 'cover', 'background-position' : 'center center'} );
	}
}

function toggle_color( element, color )
{
	var old_color = $( element ).css( 'color' );
	$( element ).css( 'color', color );

	setTimeout
	(
		function()
		{
			$( element ).css( 'color', old_color );
		},
		300
	);
}

//New, View all messages
function view_messages()
{
	clear_mainframe( true );

	var basicframe = $( '<div>' ).attr( 'id', 'basicframe' ).appendTo( $( '#innercontent' ) );
	var title = $ ( '<h1>' ).text( 'All System Messages and Hints' ).appendTo( $( basicframe ) );
	var container = $ ( '<div>' ).attr( 'id', 'basicinner' ).appendTo( $( basicframe ) );
	var list = $ ( '<ul>' ).appendTo( $( container ) );
	
	$.get
	(
		'ajax.php?m=view_messages',
		function( xml )
		{
			$( xml ).find( 'text' ).each
			(
				function()
				{
					$( '<li>' ).html( $( this ).text() ).appendTo( $( list ) );
				}
			);
			
		}
	);
}
//View Attacked Teams
function view_team_attacks()
{
	clear_mainframe( true );
	
	var basicframe = $( '<div>' ).attr( 'id', 'basicframe' ).appendTo( $( '#innercontent' ) );
	var title = $ ( '<h1>' ).text( 'Last 50 Team-Team Attacks' ).appendTo( $( basicframe ) );
	var container = $ ( '<div>' ).attr( 'id', 'basicinner' ).appendTo( $( basicframe ) );
	var list = $ ( '<ul>' ).appendTo( $( container ) );
	
	$.get
	(
		'ajax.php?m=view_team_attacks',
		function( xml )
		{
			$( xml ).find( 'attack' ).each
			(
				function()
				{
					var from = $( this ).find( 'from' ).text();
					var to = $( this ).find( 'to' ).text();
					var value = $( this ).find( 'value' ).text();
					if( $( this ).find( 'success' ).text() == '1' )
					{
						var data = $( '<li>' ).html( from + " successfully attacked " + to + " with " + value + " points." ).appendTo( $( list ) );
						data.css('color', 'green');
					}
					else
					{
						var data = $( '<li>' ).html( from + " failed to attack " + to + " with " + value + " points." ).appendTo( $( list ) );
						data.css('color', 'red');
					}
				}
			);
		}
	);
}

//Attack Team
function attack_team()
{
	clear_mainframe( true );
	
	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Attack Another Team' ).appendTo( form );

	var label_team = $( '<label>' ).text( 'Team' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Attack Destination' ).appendTo( label_team );
	var dropdown = $( '<select>' ).attr( 'id', 'team' ).appendTo( form );

	$.ajax({
		type: 'GET',
		url: 'ajax.php?m=get_teams',
		success: function( xml )
		{
			$( xml ).find( 'team' ).each
			(
				function()
				{
					var name = $( this ).find( 'name' ).text();
					var id = $( this ).find( 'id' ).text();
					var data = $( '<option>' ).val( id ).text( name ).appendTo( dropdown );			
				}
			);
		}
	});

	var label_points = $( '<label>' ).text( 'Points' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Attack Size' ).appendTo( label_points );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'points' ).appendTo( form );

	$( '<button>' ).attr( 'type', 'submit' ).text( 'Send' ).appendTo( form );

	var current_money = $( '<p>' ).attr( 'class', 'response' ).appendTo( $( '#innercontent' ) );
	
	var formData = new FormData(); 
	formData.append( 'get', '' );
	formData.append( 'token', token );
	$.ajax({
		type: 'POST',
		url: 'ajax.php?m=get_attack_team',
		data: formData,
		processData: false, 
		contentType: false,
		success: function( xml )
		{
			$( xml ).each
			(
				function()
				{
					var money = $( this ).find( 'money' ).text();
					current_money.empty();
					current_money.append( 'Your team currently has ' + money + ' attack points.' );
				}
			);
		}
	});
	
	var reply = $( '<p>' ).attr( 'class', 'response' ).appendTo( $( '#innercontent' ) );
	
	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'to', $( '#team' ).prop( 'value' ) );
			formData.append( 'points', $( '#points' ).prop( 'value' ) );
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=attack_team',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					var response = $( xml ).find( 'text' ).text();
					var money = $( xml ).find( 'money' ).text();
					var new_score = $( xml ).find( 'new_score' ).text();
					reply.empty();
					current_money.empty();
					current_money.append( 'Your team currently has ' + money + ' attack points.' );
					
					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							reply.append(response);
							reply.css('color', 'blue');
							break;

						case '2':
							reply.append( "The attack was successfully executed! " + $( '#team' ).find( ":selected" ).text() + " now has " + new_score + " points."  );
							reply.css('color', 'green');
							break;

						case '3':
							reply.append( "The attack failed!" );
							reply.css('color', 'red');
							break;
					}
				}
			});

			return false;
		}
	);
}

//View Fails
function view_fails()
{
	clear_mainframe( true );
	
	var basicframe = $( '<div>' ).attr( 'id', 'basicframe' ).appendTo( $( '#innercontent' ) );
	var title = $( '<h1>' ).text( 'Le Fail Counter' ).appendTo( $( '#basicframe' ) );
	var list = $( '<div>' ).attr( 'id', 'basicinner' ).appendTo( $( '#basicframe' ) );
	var description = $( '<p>' ).text( 'How many times did your team submit a wrong flag?' ).appendTo( $( list ) );
	var ul = $( '<ul>' ).appendTo( $( list ) );
	
	$.get
	(
		'ajax.php?m=view_fails',
		function( xml )
		{
			
			$( xml ).find( 'team' ).each
			(
				function()
				{
					var name = $( this ).find( 'name' ).text();
					var fails = $( this ).find( 'fails' ).text();
					var data = $( '<li>' ).text( name + ' - ' + fails + ' times' ).appendTo( ul );					
				}
			);
			
		}
	);
}

//View Stats
function team_stats()
{
	clear_mainframe( true );
	
	var basicframe = $( '<div>' ).attr( 'id', 'basicframe' ).appendTo( $( '#innercontent' ) );
	var title = $( '<h1>' ).text( 'Team Statistics' ).appendTo( $( '#basicframe' ) );
	var dropdown = $( '<select>' ).attr( 'id', 'team' ).css( 'margin-bottom', '25px' ).appendTo( $( '#basicframe' ) );
	var basicinner = $( '<div>' ).attr( 'id', 'basicinner' ).css( 'max-height', 'calc(100% - 85px)' ).appendTo( $( '#basicframe' ) );

	$.ajax({
		type: 'GET',
		url: 'ajax.php?m=get_teams',
		success: function( xml )
		{
			$( xml ).find( 'team' ).each
			(
				function()
				{
					var name = $( this ).find( 'name' ).text();
					var id = $( this ).find( 'id' ).text();
					var data = $( '<option>' ).val( id ).text( name ).appendTo( dropdown );			
				}
			);
			dropdown.change();
		}
	});
	
	dropdown.change(
		function()
		{
			var formData = new FormData();
			formData.append( 'id', dropdown.val() );
						
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=team_stats',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					basicinner.empty();
					var table = $( '<table>' );
					var th = $( '<tr>' );
					$( '<th>' ).text( 'Information' ).appendTo( th );
					$( '<th>' ).text( 'Data' ).appendTo( th );
					th.appendTo( table );
					
					$( xml ).find( 'item' ).each
					(
						function()
						{
							var trow = $( '<tr>' );
							var str = $( this ).text();
							var info = str.split( ":" );
							$( '<td>' ).text( info[0] ).appendTo( trow );
							$( '<td>' ).text( info[1] ).appendTo( trow );

							trow.appendTo( table );
						}
					);
										
					$( '#basicinner' ).html( table.html() );
					$( '<hr>' ).appendTo( basicinner );
					
					$( xml ).find( 'add' ).each
					(
						function()
						{
							var reply = $( '<p>' ).text( $( this ).text() ).appendTo( basicinner );
						}
					);
					$( '<hr>' ).appendTo( basicinner );
					$( '<p>' ).text( "Graph - total score at x days from registration:" ).appendTo( basicinner );
					basicinner.append( '<img src="graph.php?m=team_score&type=score&id=' + dropdown.val() + '&w=' + Math.floor(basicinner.width()-50) + '&h=' + Math.floor(basicinner.height()-100) + '" alt="Loading...">' );
					$( '<p>' ).text( "Graph - various attack points data at x days from registration:" ).appendTo( basicinner );
					basicinner.append( '<img src="graph.php?m=team_score&type=ap&id=' + dropdown.val() + '&w=' + Math.floor(basicinner.width()-50) + '&h=' + Math.floor(basicinner.height()-100) + '" alt="Loading...">' );
				}
			});
		}
	);
}

//View Completed
function view_completed()
{
	clear_mainframe( true );

	var basicframe = $( '<div>' ).attr( 'id', 'basicframe' ).appendTo( $( '#innercontent' ) );
	var title = $( '<h1>' ).text( 'Strategic Challenge Planner' ).appendTo( $( '#basicframe' ) );
	var basicinner = $( '<div>' ).attr( 'id', 'basicinner' ).appendTo( $( '#basicframe' ) );
	
	$.get
	(
		'ajax.php?m=view_completed',
		function( xml )
		{
			var table = $( '<table>' );
			var th = $( '<tr>' );
			$( '<th>' ).text( 'Team Name' ).appendTo( th );
			$( '<th>' ).text( 'Completed Challenge(s)' ).appendTo( th );
			th.appendTo( table );
			
			$( xml ).find( 'team' ).each
			(
				function()
				{
					var trow = $( '<tr>' );
					$( '<td>' ).text( $( this ).find( 'name' ).text() ).appendTo( trow );
					$( '<td>' ).text( $( this ).find( 't' ).text() ).appendTo( trow );

					trow.appendTo( table );
				}
			);
			
			$( '#basicinner' ).html( table.html() );
			
		}
	);
}

function page_challenges()
{
	clear_mainframe( true );

	var challengesframe = $( '<div>' ).attr( 'id', 'challengesframe' ).appendTo( $( '#innercontent' ) );
	var challengeframe = $( '<div>' ).attr( 'id', 'challengeframe' ).appendTo( $( '#innercontent' ) );
	var categories = $( '<ul>' ).appendTo( challengesframe );

	$.get
	(
		'ajax.php?m=get_challenges',
		function( xml )
		{
			var last_category = false;

			$( xml ).find( 'category' ).each
			(
				function()
				{
					var category = $( '<li>' ).addClass( 'category' ).text( $( this ).find( 'name' ).text() ).appendTo( categories );
					var challenges = $( '<ul>' ).appendTo( category );

					$( this ).find( 'challenge' ).each
					(
						function()
						{
							var id = $( this ).find( 'id' ).text();
							var title = $( this ).find( 'title' ).text();
							var score = $( this ).find( 'score' ).text();
							var challenge = $( '<li>' ).addClass( 'challenge' ).text( title + ' (' + score + ')' ).appendTo( challenges );

							challenge.click
							(
								function()
								{
									open_challenge( id );
								}
							);

							var solved = $( this ).find( 'solved' ).text();
							var deactivated = $( this ).find( 'deactivated' ).text();

							if( solved == '1' )
							{
								challenge.addClass( 'solved' );
							}
							else if( deactivated == '1')
							{
								challenge.addClass( 'deactivated' );
							}
							else
							{
								challenge.addClass( 'notsolved' );
							}
						}
					);
				}
			);
		}
	);
}

function open_challenge( id )
{
	$( '#challengeframe' ).empty();
	
	var formData = new FormData();    
	formData.append( 'id', id );
	
	$.ajax({
		type: 'POST',
		url: 'ajax.php?m=get_challenge',
		data: formData,
		processData: false, 
		contentType: false,
		success: function( xml )
		{
			$( '<h1>' ).text( $( xml ).find( 'title' ).text() ).appendTo( $( '#challengeframe' ) );
			var challenge_description = $( '<span>' ).html( $( xml ).find( 'description' ).text() ).appendTo( $( '#challengeframe' ) );
		}
	});
}

function page_submit()
{
	clear_mainframe( true );

	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).addClass( 'flags' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Attack' ).appendTo( form );

	var label_flag = $( '<label>' ).text( 'Flag' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Any challenge' ).appendTo( label_flag );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'flag' ).appendTo( form );

	$( '<button>' ).attr( 'type', 'submit' ).text( 'Send' ).appendTo( form );

	var reply = $( '<p>' ).attr( 'class', 'response' ).appendTo( $( '#innercontent' ) );
	
	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'key', $( '#flag' ).prop( 'value' ) );
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=submit_key',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					var response = $( xml ).find( 'text' ).text();
					var cat = $( xml ).find( 'cat' ).text();
					var script = $( xml ).find( 'script' ).text();
					
					if( allow_cat && !cat_active && !ticker_active)
					{					
						try
						{
							eval( script );
						}
						catch( e ) {}
						
						if( cat.length != 0 )
						{	
							cat_active = true;
							show_cat( cat );
						}
					}
				
					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							reply.empty();
							check_attacks();
							if( response )
							{
								reply.append(response);
								setTimeout( function(){clear_mainframe( false );}, 3000 );
							}
							else
							{
								clear_mainframe( false );
							}
							break;

						case '2':
							toggle_color( $( '#header' ), 'orange' );
							reply.empty();
							reply.append(response);
							break;

						case '3':
							toggle_color( $( '#header' ), 'red' );
							reply.empty();
							reply.append(response);
							break;
						
						case '4':
							toggle_color( $( '#header' ), 'blue' );
							reply.empty();
							reply.append(response);
							break;
					}
				}
			});

			return false;
		}
	);
}

function page_login()
{
	clear_mainframe( true );

	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Authenticate' ).appendTo( form );

	var label_username = $( '<label>' ).text( 'Username' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Enter your teamname' ).appendTo( label_username );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'username' ).appendTo( form );

	var label_password = $( '<label>' ).text( 'Password' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Enter your password' ).appendTo( label_password );
	$( '<input>' ).attr( 'type', 'password' ).attr( 'id', 'password' ).appendTo( form );

	$( '<button>' ).attr( 'type', 'submit' ).text( 'Login' ).appendTo( form );
	
	var reply = $( '<p>' ).attr( 'class', 'response' ).appendTo( $( '#innercontent' ) );

	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'username', $( '#username' ).prop( 'value' ) );
			formData.append( 'password', $( '#password' ).prop( 'value' ) );
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=login',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					var response = $( xml ).find( 'text' ).text()
					
					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							login();
							clear_mainframe( false );
							break;

						case '2':
							toggle_color( $( '#header' ), 'orange' );
							reply.empty();
							reply.append(response);
							break;

						case '3':
							toggle_color( $( '#header' ), 'red' );
							reply.empty();
							reply.append(response);
							break;
					}
				}
			});

			return false;
		}
	);
}

function page_logout()
{
	clear_mainframe( true );

	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Authenticate' ).appendTo( form );
	$( '<button>' ).attr( 'type', 'submit' ).text( 'Logout' ).appendTo( form );

	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=logout',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							logout();
							clear_mainframe( false );
							break;

						case '2':
							toggle_color( $( '#header' ), 'red' );
							break;
					}
				}
			});

			return false;
		}
	);	
}

//Update Team Flag
function update_flag()
{
	clear_mainframe( true );
	
	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).attr( 'method', 'POST' ).attr( 'enctype', 'multipart/form-data').appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Update Flag' ).appendTo( form );

	var label_m1 = $( '<label>' ).text( 'Flag' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Optional' ).appendTo( label_m1 );
	$( '<input>' ).attr( 'type', 'file' ).attr( 'name', 'file' ).attr( 'id', 'file' ).attr( 'style', 'padding: 0;' ).appendTo( form );

	$( '<button>' ).attr( 'type', 'submit' ).text( 'Send' ).appendTo( form );

	var reply = $( '<p>' ).attr( 'class', 'response' ).appendTo( $( '#innercontent' ) );

	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'file', $('#file').get(0).files[0] );
			formData.append( 'token', token );
		
			$.ajax({
				url: 'ajax.php?m=update_flag',
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function( xml )
				{
					var response = $( xml ).find( 'text' ).text()

					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							toggle_color( $( '#header' ), 'green' );
							reply.empty();
							reply.append(response);
							setTimeout( function(){location.reload(true)}, 5000 );
							break;

						case '2':
							toggle_color( $( '#header' ), 'orange' );
							reply.empty();
							reply.append(response);
							break;

						case '3':
							toggle_color( $( '#header' ), 'red' );
							reply.empty();
							reply.append(response);
							break;
					}
				}
			});

			return false;
		}
	);
}

//Update Team Information
function update_info()
{
	clear_mainframe( true );
	
	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Update Information' ).appendTo( form );

	var label_m1 = $( '<label>' ).text( 'Member 1' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Optional' ).appendTo( label_m1 );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'm1' ).appendTo( form );

	var label_m2 = $( '<label>' ).text( 'Member 2' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Optional' ).appendTo( label_m2 );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'm2' ).appendTo( form );

	var label_m3 = $( '<label>' ).text( 'Member 3' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Optional' ).appendTo( label_m3 );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'm3' ).appendTo( form );
	
	var label_email = $( '<label>' ).text( 'New Email' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Optional' ).appendTo( label_email );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'email' ).appendTo( form );
	
	$( '<button>' ).attr( 'type', 'submit' ).text( 'Send' ).appendTo( form );

	var reply = $( '<p>' ).attr( 'class', 'response' ).appendTo( $( '#innercontent' ) );

	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'm1', $( '#m1' ).prop( 'value' ) );
			formData.append( 'm2', $( '#m2' ).prop( 'value' ) );
			formData.append( 'm3', $( '#m3' ).prop( 'value' ) );
			formData.append( 'email', $( '#email' ).prop( 'value' ) );
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=update_info',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					var response = $( xml ).find( 'text' ).text()
					
					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							toggle_color( $( '#header' ), 'green' );
							reply.empty();
							reply.append(response);
							setTimeout( function(){location.reload(true)}, 5000 );
							break;

						case '2':
							toggle_color( $( '#header' ), 'orange' );
							reply.empty();
							reply.append(response);
							break;

						case '3':
							toggle_color( $( '#header' ), 'red' );
							reply.empty();
							reply.append(response);
							break;
					}
				}
			});

			return false;
		}
	);
}

//Create Account
function create_account()
{
	clear_mainframe( true );

	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Create Team' ).appendTo( form );

	var label_team = $( '<label>' ).text( 'Team Name' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Same as log in' ).appendTo( label_team );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'team_name' ).appendTo( form );

	var label_school = $( '<label>' ).text( 'School' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( label_school );
	var dropdown = $( '<select>' ).attr( 'id', 'school' ).appendTo( form );
	$( '<option>' ).val( 'CAMS' ).text( 'CAMS' ).appendTo( dropdown );
	$( '<option>' ).val( 'Other' ).text( 'Other' ).appendTo( dropdown );

	var label_pass = $( '<label>' ).text( 'Password' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( label_pass );
	$( '<input>' ).attr( 'type', 'password' ).attr( 'id', 'password' ).appendTo( form );

	var label_repeat = $( '<label>' ).text( 'Confirm Password' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( label_repeat );
	$( '<input>' ).attr( 'type', 'password' ).attr( 'id', 'repeat' ).appendTo( form );
	
	var label_email = $( '<label>' ).text( 'Email' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( label_email );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'email' ).appendTo( form );

	$( '<button>' ).attr( 'type', 'submit' ).text( 'Send' ).appendTo( form );

	var reply = $( '<p>' ).attr( 'class', 'response' ).appendTo( $( '#innercontent' ) );
	
	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'team_name', $( '#team_name' ).prop( 'value' ) );
			formData.append( 'school', $( '#school' ).val() );
			formData.append( 'password', $( '#password' ).prop( 'value' ) );
			formData.append( 'repeat', $( '#repeat' ).prop( 'value' ) );
			formData.append( 'email', $( '#email' ).prop( 'value' ) );
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=create_account',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					var response = $( xml ).find( 'text' ).text()
					
					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							toggle_color( $( '#header' ), 'green' );
							reply.empty();
							reply.append(response);
							setTimeout( function(){location.reload(true)}, 5000 );
							break;

						case '2':
							toggle_color( $( '#header' ), 'orange' );
							reply.empty();
							reply.append(response);
							break;

						case '3':
							toggle_color( $( '#header' ), 'red' );
							reply.empty();
							reply.append(response);
							break;
					}
				}
			});

			return false;
		}
	);
}

// Forgot Password
function forgot_pass()
{
	clear_mainframe( true );

	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Forgot Password' ).appendTo( form );

	var tname = $( '<label>' ).text( 'Team Name' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( tname );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'name' ).appendTo( form );

	var email = $( '<label>' ).text( 'Email' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( email );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'email' ).appendTo( form );

	$( '<button>' ).attr( 'type', 'submit' ).text( 'Send' ).appendTo( form );

	var reply = $( '<p>' ).attr( 'class', 'response' ).appendTo( $( '#innercontent' ) );
	
	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'name', $( '#name' ).prop( 'value' ) );
			formData.append( 'email', $( '#email' ).prop( 'value' ) );
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=forgot_pass',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					var response = $( xml ).find( 'text' ).text()
					
					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							toggle_color( $( '#header' ), 'green' );
							reply.empty();
							reply.append(response);
							setTimeout( function(){clear_mainframe( false )}, 5000 );
							break;

						case '2':
							toggle_color( $( '#header' ), 'orange' );
							reply.empty();
							reply.append(response);
							break;

						case '3':
							toggle_color( $( '#header' ), 'red' );
							reply.empty();
							reply.append(response);
							break;
					}
				}
			});

			return false;
		}
	);
}

//Change Password
function change_pass()
{
	clear_mainframe( true );

	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Change Password' ).appendTo( form );

	var current_password = $( '<label>' ).text( 'Current Password' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( current_password );
	$( '<input>' ).attr( 'type', 'password' ).attr( 'id', 'current' ).appendTo( form );

	var new_password = $( '<label>' ).text( 'New Password' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( new_password );
	$( '<input>' ).attr( 'type', 'password' ).attr( 'id', 'new' ).appendTo( form );

	var repeat_password = $( '<label>' ).text( 'Confirm Password' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( repeat_password );
	$( '<input>' ).attr( 'type', 'password' ).attr( 'id', 'repeat' ).appendTo( form );

	$( '<button>' ).attr( 'type', 'submit' ).text( 'Send' ).appendTo( form );

	var reply = $( '<p>' ).attr( 'class', 'response' ).appendTo( $( '#innercontent' ) );
	
	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'current', $( '#current' ).prop( 'value' ) );
			formData.append( 'new', $( '#new' ).prop( 'value' ) );
			formData.append( 'repeat', $( '#repeat' ).prop( 'value' ) );
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=change_pass',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					var response = $( xml ).find( 'text' ).text()
					
					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							toggle_color( $( '#header' ), 'green' );
							reply.empty();
							reply.append(response);
							setTimeout( function(){clear_mainframe( false )}, 5000 );
							break;

						case '2':
							toggle_color( $( '#header' ), 'orange' );
							reply.empty();
							reply.append(response);
							break;

						case '3':
							toggle_color( $( '#header' ), 'red' );
							reply.empty();
							reply.append(response);
							break;
					}
				}
			});

			return false;
		}
	);
}

function page_sendmessage()
{
	clear_mainframe( true );

	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Send Message' ).appendTo( form );

	var label_password = $( '<label>' ).text( 'Password' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( label_password );
	$( '<input>' ).attr( 'type', 'password' ).attr( 'id', 'password' ).appendTo( form );

	var label_message = $( '<label>' ).text( 'Message' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( label_message );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'message' ).appendTo( form );

	var label_image = $( '<label>' ).text( 'Image' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Required' ).appendTo( label_image );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'image' ).appendTo( form );

	var label_script = $( '<label>' ).text( 'Script' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Optional' ).appendTo( label_script );
	$( '<input>' ).attr( 'type', 'text' ).attr( 'id', 'script' ).appendTo( form );

	$( '<button>' ).attr( 'type', 'submit' ).text( 'Send' ).appendTo( form );

	form.submit
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'password', $( '#password' ).prop( 'value' ) );
			formData.append( 'message', $( '#message' ).prop( 'value' ) );
			formData.append( 'image', $( '#image' ).prop( 'value' ) );
			formData.append( 'script', $( '#script' ).prop( 'value' ) );
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=send_message',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					switch( $( xml ).find( 'code' ).text() )
					{
						case '1':
							check_ticker();
							clear_mainframe( false );
							break;

						case '2':
							toggle_color( $( '#header' ), 'orange' );
							break;

						case '3':
							toggle_color( $( '#header' ), 'red' );
							break;
					}
				}
			});

			return false;
		}
	);
}

function page_settings()
{
	clear_mainframe( true );

	var formframe = $( '<div>' ).attr( 'id', 'formframe' ).appendTo( $( '#innercontent' ) );
	var form = $( '<form>' ).appendTo( formframe );
	$( '<h1>' ).attr( 'id', 'header' ).text( 'Settings' ).appendTo( form );

	var label_updaterate = $( '<label>' ).text( 'Update rate' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Whatever' ).appendTo( label_updaterate );
	var updaterate_dropdown = $( '<select>' ).attr( 'id', 'updaterate' ).appendTo( form );
	var updaterate_off = $( '<option>' ).attr( 'value', '0' ).text( 'Slow' ).appendTo( updaterate_dropdown );
	var updaterate_low = $( '<option>' ).attr( 'value', '1' ).text( 'Normal' ).appendTo( updaterate_dropdown );
	var updaterate_mid = $( '<option>' ).attr( 'value', '2' ).text( 'High' ).appendTo( updaterate_dropdown );
	var updaterate_high = $( '<option>' ).attr( 'value', '3' ).text( 'Very High' ).appendTo( updaterate_dropdown );

	switch( update_rate )
	{
		case '0':
			updaterate_off.attr( 'selected', 'selected' );
			break;

		case '1':
			updaterate_low.attr( 'selected', 'selected' );
			break;

		case '2':
			updaterate_mid.attr( 'selected', 'selected' );
			break;

		case '3':
			updaterate_high.attr( 'selected', 'selected' );
			break;
	}

	var label_fontsize = $( '<label>' ).text( 'Font Size' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Whatever' ).appendTo( label_fontsize );
	var fontsize_dropdown = $( '<select>' ).attr( 'id', 'fontsize' ).appendTo( form );
	var fontsize_low = $( '<option>' ).attr( 'value', '0' ).text( 'Small' ).appendTo( fontsize_dropdown );
	var fontsize_mid = $( '<option>' ).attr( 'value', '1' ).text( 'Normal' ).appendTo( fontsize_dropdown );
	var fontsize_high = $( '<option>' ).attr( 'value', '2' ).text( 'Large' ).appendTo( fontsize_dropdown );
	var fontsize_ultra = $( '<option>' ).attr( 'value', '3' ).text( 'Extra Large' ).appendTo( fontsize_dropdown );

	switch( fontsize )
	{
		case '0':
			fontsize_low.attr( 'selected', 'selected' );
			break;

		case '1':
			fontsize_mid.attr( 'selected', 'selected' );
			break;

		case '2':
			fontsize_high.attr( 'selected', 'selected' );
			break;
			
		case '3':
			fontsize_ultra.attr( 'selected', 'selected' );
			break;
	}
	
	var label_hcat = $( '<label>' ).text( 'HackerCat' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Meow, meow' ).appendTo( label_hcat );
	var hcat_dropdown = $( '<select>' ).attr( 'id', 'hcat' ).appendTo( form );
	var hcat_off = $( '<option>' ).attr( 'value', '0' ).text( 'Off' ).appendTo( hcat_dropdown );
	var hcat_on = $( '<option>' ).attr( 'value', '1' ).text( 'On' ).appendTo( hcat_dropdown );

	switch( allow_cat )
	{
		case false:
			hcat_off.attr( 'selected', 'selected' );
			break;

		case true:
			hcat_on.attr( 'selected', 'selected' );
			break;
	}
	
	var label_sound = $( '<label>' ).text( 'Sound' ).appendTo( form );
	$( '<span>' ).addClass( 'small' ).text( 'Silence' ).appendTo( label_sound );
	var sound_dropdown = $( '<select>' ).attr( 'id', 'sound' ).appendTo( form );
	var sound_off = $( '<option>' ).attr( 'value', '0' ).text( 'Off' ).appendTo( sound_dropdown );
	var sound_on = $( '<option>' ).attr( 'value', '1' ).text( 'On' ).appendTo( sound_dropdown );

	switch( allow_sound )
	{
		case false:
			sound_off.attr( 'selected', 'selected' );
			break;

		case true:
			sound_on.attr( 'selected', 'selected' );
			break;
	}

	$( '<button>' ).attr( 'type', 'submit' ).text( 'Send' ).appendTo( form );

	form.submit
	(
		function()
		{
			set_update_rate( $( '#updaterate' ).val() );
			set_fontsize( $( '#fontsize' ).val() );
			set_cat( $( '#hcat' ).val() );
			set_sound( $( '#sound' ).val() );

			clear_mainframe( false );

			return false;
		}
	);
}

function page_terminal()
{
	if( !terminal )
	{
		terminal = generate_terminal( generate_box( 'Terminal' ) ).appendTo( $( 'body' ) );
		$( '.terminal-input', terminal ).focus();
	}
	else
	{
		$( terminal ).remove();
		terminal = false;
	}
}

function generate_box( title )
{
	var container = $( '<div>' ).addClass( 'container' );
	var box = $( '<div>' ).addClass( 'box' );

	var header = $( '<div>' ).addClass( 'box-header' ).appendTo( box );
	var body = $( '<div>' ).addClass( 'box-body' ).appendTo( box );

	$( '<h1>' ).text( title ).appendTo( header );

	var navigation = $( '<div>' ).addClass( 'box-navigation' ).appendTo( header );

	var clicking = false;
	var offsetX = offsetY = 0;

	header.mousedown
	(
		function( pointer )
		{
			clicking = true;

			var offset = header.offset();

			offsetX = pointer.pageX - offset.left;
			offsetY = pointer.pageY - offset.top;
		}
	);

	$( document ).mouseup
	(
		function()
		{
		    clicking = false;
		}
	);

	$( document ).mousemove
	(
		function( pointer )
		{
			if( clicking == false ) return;

			var x = pointer.pageX - offsetX;
			var y = pointer.pageY - offsetY;

			box.css( 'left', x ).css( 'top', y );
		}
	);

	$( '<img>' ).addClass( 'close' ).attr( 'src', 'images/icons/close_small.png' ).appendTo( navigation );

	box.appendTo( container );

	return container;
}

function generate_terminal( container )
{
	var output = $( '<pre>' ).addClass( 'terminal-output' );
	var frame = $( '<div>' ).addClass( 'box-frame' ).append( output );
	var input = $( '<input>' ).attr( 'type', 'text' ).addClass( 'terminal-input' );
	var submit = $( '<input>' ).attr( 'type', 'submit' ).val( 'Send' );
	var clear = $( '<input>' ).attr( 'type', 'reset' ).val( 'Clear' );
	var form = $( '<form>' ).addClass( 'terminal-form' );

	form.append( input ).append( submit ).append( clear );
	$( '.box-body', container ).append( frame ).append( form );

	submit.click
	(
		function()
		{
			var formData = new FormData();    
			formData.append( 'command', $( input ).prop( 'value' ) );
			formData.append( 'token', token );
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php?m=terminal',
				data: formData,
				processData: false, 
				contentType: false,
				success: function( xml )
				{
					var command = $( xml ).find( 'command' );

					try
					{
						eval( command.text() );
					}
					catch( e ) {}
					
					if( !terminaldata )
					{
						if( $( xml ).find( 'output' ).text() )
						{
							output.append( "> " + $( xml ).find( 'input' ).text() + "\n" + $( xml ).find( 'output' ).text() + "\n" );
						}
						else
						{
							output.append( "> " + input.text() + "\n" + "Session token error. Please refresh the page." + "\n" );
						}
					}
					else
					{
						output.append( + "> " + $( xml ).find( 'input' ).text() + "\n" + terminaldata + "\n" );
						terminaldata = "";
					}
					output.animate( { scrollTop: $( output ).get(0).scrollHeight }, 750 );
				}
			});

			input.val( '' );
			input.focus();

			return false;
		}
	);

	clear.click
	(
		function()
		{
			input.val( '' );
			output.text( '' );

			return false;
		}
	);

	$( '.close', container ).click
	(
		function()
		{
			page_terminal();
		}
	);

	return container;
}

function set_update_rate( value )
{
	switch( value )
	{
		case '0':
			localStorage.setItem( 'csc_update_rate', '0' );
			update_rate = '0';
			break;

		case '1':
			localStorage.setItem( 'csc_update_rate', '1' );
			update_rate = '1';
			break;

		case '2':
			localStorage.setItem( 'csc_update_rate', '2' );
			update_rate = '2';
			break;

		case '3':
			localStorage.setItem( 'csc_update_rate', '3' );
			update_rate = '3';
			break;
	}
}

function get_update_rate()
{
	switch( update_rate )
	{
		default:
		case '0':
			return 30000;

		case '1':
			return 10000;

		case '2':
			return 5000;

		case '3':
			return 2500;
	}
}

function set_fontsize( value )
{
	switch( value )
	{
		case '0':
			localStorage.setItem( 'csc_font_size', '0' );
			fontsize = '0';
			$( '#innercontent' ).css( 'font-size', '90%' );
			break;

		case '1':
			localStorage.setItem( 'csc_font_size', '1' );
			fontsize = '1';
			$( '#innercontent' ).css( 'font-size', '100%' );
			break;

		case '2':
			localStorage.setItem( 'csc_font_size', '2' );
			fontsize = '2';
			$( '#innercontent' ).css( 'font-size', '110%' );
			break;
			
		case '3':
			localStorage.setItem( 'csc_font_size', '3' );
			fontsize = '3';
			$( '#innercontent' ).css( 'font-size', '120%' );
			break;
	}
}

function login()
{
	loggedin = true;
	$( '#submit' ).css( 'cursor', 'pointer' );
}

function logout()
{
	loggedin = false;

	//$( '#challenges' ).css( 'cursor', 'url(images/icons/disabled.png),auto' );
	$( '#submit' ).css( 'cursor', 'url(images/icons/disabled.png),auto' );
}

function load_session()
{
	$.get
	(
		'ajax.php?m=get_session',
		function( xml )
		{
			if( $( xml ).find( 'loggedin' ).text() == '1' )
			{
				login();
			}
			else
			{
				logout();
			}

			token = $( xml ).find( 'token' ).text();
		}
	);
}

function check_ie() {

	var ua = window.navigator.userAgent;
	var msie = ua.indexOf("MSIE ");

	if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))
	{
		return false;
	}
	else
	{
		return true;
	}
}

function check_cookies()
{
	if (navigator.cookieEnabled)
	{
		return true;
	}
	else
	{
		return false;
	}
}

function initialize()
{
	$( '#errors' ).css( 'display', 'none' );
	$( '#content' ).css( 'display', 'inline-block' );
	
	$( '#challenges' ).css( 'cursor', 'pointer' );

	canvas = document.getElementById( 'canvas' );
	canvas.width  = world_width;
	canvas.height = world_height;
	context = canvas.getContext( '2d' );

	attack_wait();
	ticker_wait();
	update_ranking();

	if ( !localStorage.csc_update_rate )
	{
		set_update_rate( '1' );
	}
	
	if ( !localStorage.csc_font_size )
	{
		set_fontsize( '1' );
	}
	else
	{
		set_fontsize( fontsize ); //Actually set the fontsize on load.
	}

	if ( !localStorage.csc_allow_cat )
	{
		set_cat( '1' );
	}
	
	if ( !localStorage.csc_allow_sound )
	{
		set_sound( '1' );
	}
	
	// Animation loop
	animate();

	// Update loop
	setInterval( "update()", 25 );
}

function write_css()
{
	world_height = b_height - 70;
	world_width = b_width - 400;
	var top = 35;
	
	if ( world_height < 600 )
	{
		world_height = 600;
		top = 10;
	}
	if ( world_width < 1000 )
	{
		world_width = 1000;
	}
	
	constant = world_height / 600;
	
	$( '#world' ).css( {'height' : world_height, 'width' : world_width} );
	$( '#canvas' ).css( {'height' : world_height, 'width' : world_width, 'margin-top': '-3px'} );
	$( '#rankingframe' ).css( {'height' : world_height - 255} );
	$( '#content' ).css( {'height' : world_height, 'width' : world_width + 310, 'margin-top' : top} );
	$( '#innercontent' ).css( {'height' : world_height - 70, 'width' : world_width - 20} );
}

$( document ).ready
(
	function()
	{
		if( !check_cookies() )
		{ // Cookies not enabled
			$( "<li>Please enable your browser's delicious cookie feature. Seriously. <a href='http://www.google.com/search?q=how+to+enable+cookies' target='_blank'>Help me Google!</a></li>" ).appendTo( "#errors" );
			return;
		}
		
		if( !check_ie() )
		{
			$( "<li>Seriously? Internet Explore? Please come back when you have a better browser such as <a href='http://www.google.com/intl/en_us/chrome/browser/' target='_blank'>Chrome</a> or <a href='http://www.mozilla.org/en-US/firefox/new/' target='_blank'>Firefox</a>.</li>" ).appendTo( "#errors" );
			return;
		}
		
		write_css(); //Dynamic page sizing

		load_session();
		
		clear_mainframe( false );

		$( '#challenges' ).click
		(
			function()
			{
				page_challenges();
			}
		);

		$( '#submit' ).click
		(
			function()
			{
				if( !loggedin )
				{
					return;
				}

				page_submit();
			}
		);

		$( '#authenticate' ).click
		(
			function()
			{
				if( loggedin )
				{
					page_logout();
				}
				else
				{
					page_login();
				}
			}
		);

		$( '#terminal' ).click
		(
			function()
			{
				page_terminal();
			}
		);

		$( '#close' ).click
		(
			function()
			{
				clear_mainframe( false );
			}
		);

		// Keybinds
		$( document ).keypress
		(
			function( e )
			{
				if( ( e.which == 122 && e.ctrlKey ) || ( e.which == 26 ) )
				{ // Ctrl + z
					page_terminal();
				}
			}
		);

		initialize();
	}
);
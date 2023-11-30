<?php
	error_reporting(E_ERROR | E_PARSE);
	ini_set('allow_url_include', '1');
	ini_set('allow_url_fopen', '1');
	// require_once('../weapons.php');
	// DB Link	
	$db_link = mysqli_connect (
						 "host", 
						 "user", 
						 "password", 
						 "tablename"
						);

	// function SelectFromSql( $data){
		// $value = mysqli_fetch_row( mysqli_query( $GLOBALS["db_link"], "SELECT $data FROM ttt_stats WHERE UNIX_TIMESTAMP(STR_TO_DATE(last_seen, '%a %b  %e %H:%i:%s %Y')) > ( UNIX_TIMESTAMP() - " . $GLOBALS["unixinterval"] . ")" ))[0];
		// $value = round($value, 2);
		// return $value;
	// }
	
	// function SelectFromSql2( $role ){
		// $value = mysqli_fetch_row( mysqli_query( $GLOBALS["db_link"], "SELECT $data FROM ttt_stats WHERE UNIX_TIMESTAMP(STR_TO_DATE(last_seen, '%a %b  %e %H:%i:%s %Y')) > ( UNIX_TIMESTAMP() - " . $GLOBALS["unixinterval"] . ")" ))[0];
		// return $value;
	// }
	
// Komplette Datenbank abfragen
	$id = $_GET["steamid"];
	$map = $_GET["mapname"];
	
	if(empty($_GET["steamid"])){
		$id =  "ursteamid";
		$map = "blank";
	};
	
	$link = file_get_contents('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=yourkeygoeshere&steamids=' . $id . '&format=json');
	$steamarray = json_decode($link, true);
	
	$unixinterval = 1209600; //1209600
// Coloring
	function ColVal( $val, $refmax, $refmin ){
		if ( $val == $refmax) {
			$color = 'lightgreen';
		} elseif ( $val == $refmin ){
			$color = '#ff5551';
		} else {
			$color = 'white';
		}
		
		return $color;
	}
?>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel='stylesheet' type='text/css' href='/games/ttt/loading/style/style.css' /> 
		<!-- <link rel='stylesheet' type='text/css' href='/games/ttt/loading/style/bootstrap.min.css' /> bootstrap 4.0-->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
		<script src="https://kit.fontawesome.com/afa5380eb2.js" crossorigin="anonymous"></script>
	</head>

	<body class="standard m-3">
		    <audio  id="myaudio" autoplay loop>
				<source src="http://actionbunny.dyndns.org/games/ttt/loading/TTT_theme.ogg" type="audio/ogg">
			</audio>
			<script>
			  var audio = document.getElementById("myaudio");
			  audio.volume = 0.3;
			</script>
		<div class="row">
			<div class="col-3">
				<div class="row">
					<div class='col m-0 mb-2' style="width: max-content;">	
						<div class='greeting m-0 p-2'>
							<p class='greeting'>Hi <?php echo $steamarray['response']['players'][0]['personaname']; ?> !</p>
							<p class='map1'>Current Map: <?php echo $map; ?></p>
						</div>
					</div>
				</div>
				<div id="carouselExampleSlidesOnly2" class="carousel slide" data-ride="carousel">
				  <div class="carousel-inner">
					<div class="carousel-item active">
					  <p>Waffenstats?</p>
					</div>
					<div class="carousel-item">
					  <p>Bestenlisten/Awards</p>
					</div>
					<!--<div class="carousel-item">
					  <p>test3</p>
					</div>-->
				  </div>
				</div>
			</div>
			<div class="col-6">
				<?php
				echo"
				<table id='tableStats'>
					<tr>
						<td class='head'>Platz</td>
						<td class='head'>Name</td>
						<td class='head'>Salz</td>
						<td class='head'>Runden</td>
						<td colspan='4' class='head'>Winrates</td>
						<td class='head'>Kills</td>
						<td class='head'>Tode</td>
						<td class='head'>Heads</td>
						<td class='head'>Rec</td>
					</tr>
					<tr>
						<td class='desc'></td>
						<td class='desc'></td>
						<td class='desc'></td>
						<td class='desc'></td>
						<td class='desc'>All</td>
						<td class='desc'>Inno</td>
						<td class='desc'>Trai</td>
						<td class='desc'>Det</td>
						<!-- <td class='desc'>Rest</td> -->
						<td class='desc'>pro Runde</br>Randomrate</td>
						<td class='desc'>pro Runde</br>Randomrate</td>
						<td class='desc'></td>
						<td class='desc'>Max</br>Min</td>
					</tr>
				";
				// $time_start = microtime(true); 
				$sql = "SELECT * FROM view_ttt_loading ORDER by salt DESC";

				$db_erg = mysqli_query( $db_link, $sql );
				if ( ! $db_erg )
				{
				  die('Ungültige Abfrage: ' . mysqli_error());
				}

				$pos = 0;
				while ($zeile = mysqli_fetch_assoc( $db_erg))
				{
					
					if ((time() - strtotime($zeile['last_seen'])) > $unixinterval) {
						continue;
					}

					$pos++;
					if ($pos % 2 == 0) {
						$rowcolor = 'black';
					} else {
						$rowcolor = 'grey';
					}
					// Platz
					
					if ($pos == 1) {
						$posdesc = 'first';  
					}
					elseif ($pos == 2) {
						$posdesc = 'second';
					}
					elseif ($pos == 3) {
						$posdesc = 'third';
					}
					else {
						$posdesc = 'else';
					}

					$avg = $zeile['salt'] / 100;
					$roundsplayed = $zeile['rounds_total'];
					if ($roundsplayed == 0) {
						continue;
					}
					$winrate = $zeile['winrate_total'];
					$winrate_innocent = $zeile['winrate_innocent'];
					$winrate_detective = $zeile['winrate_detective'];
					$winrate_traitor = $zeile['winrate_traitor'];
					$kills = $zeile['kills_total'];
					$kills_randomrate_total = $zeile['kills_randomrate_total'];
					$deaths = $zeile['deaths_total'];
					$deaths_randomrate_total = $zeile['deaths_randomrate_total'];
					$kills_headshotrate_total = $zeile['kills_headshotrate_total'];
					$nickname = substr($zeile['nickname'], 0, 15);
					echo"
					<tr class=$rowcolor>	
					  <td class=$posdesc>$pos</td>
					  
					  <td class='name'>$nickname</td>
					  <td>$avg</td>";
								
					$val = $roundsplayed;
			
					
					echo"
					<td>
						<span data-column='1' data-value='$val'>$val</span></br>
					</td>";
					
					$val = round( $winrate, 2)*100;

					
					echo"
					<td>
						<span data-column='2' data-value='$val'>$val%</span></br>
					</td>";
					
					$val = round( $winrate_innocent, 2)*100;
				
					
					echo"
					<td>
						<span data-column='3' data-value='$val'>$val%</span></br>
					</td>";		
					$val = round( $winrate_traitor, 2)*100;


					echo"
					<td>
						<span data-column='4' data-value='$val'>$val%</span></br>
					</td>";		
					$val = round( $winrate_detective, 2)*100;

					
					// echo"
					// <td>
						// <span data-column='5' data-value='$val'>$val%</span></br>
					// </td>";		
					// $roundsothers = $roundsplayed - $roundsinnocent - $roundstraitor - $roundsdetective;
					// $roundsotherswon = $roundswon - $roundsinnocentwon - $roundstraitorwon - $roundsdetectivewon;
					
					// $val = round( $roundsotherswon/$roundsothers, 2)*100;

					
					echo"
					<td>
						<span data-column='5' data-value='$val'>$val%</span></br>
					</td>";		

					
					$val = round($kills/$roundsplayed,1);
				
					
					$val2 = round($kills_randomrate_total, 2) *100;

					
					echo"
					<td>
						<span data-column='6' data-value='$val'>$val</span></br>
						<span data-column='7' data-value='$val2' data-invert='1'>$val2%</span>
					</td>";
					
					$val = round($deaths/$roundsplayed,1);
			
					
					$val2 = round($deaths_randomrate_total, 2) * 100;


					echo"
					<td>
						<span data-column='8' data-value='$val' data-invert='1'>$val</span></br>
						<span data-column='9' data-value='$val2' data-invert='1'>$val2%</span>
					</td>";
					
					$val = round($kills_headshotrate_total,2) * 100;
					
					echo"
					<td>
						<span data-column='10' data-value='$val'>$val%</span></br>
					</td>";		
					
					$val = $zeile['maxscore'];			
					$val2 = $zeile['minscore'];
					
					echo"
					<td>
						<span data-column='11' data-value='$val' data-onlymax='1'>$val</span></br>
						<span data-column='12' data-value='$val2' data-onlymin='1'>$val2</span>
					</td>";
					//kills pro runde, prozent randomkills prozent randomtode
					//prozent heads pro kills
				}
				echo "</table>";
				// $time_end = microtime(true);
				// $execution_time = ($time_end - $time_start);

				//execution time of the script
				//echo '<b>Total Execution Time:</b> '.$execution_time.'S';		
				mysqli_free_result( $db_erg ); ?>
			</div>

			<div class="col-3">

				<div class="row mb-2">
					<div class="col-12">
						<p class="text-center m-0 bg-warning"><small>I gewinnt in Team-Innocent // T gewinnt in Team Traitor // ? wechselt Team // N gewinnt als eigenes Team</br>
						40% WSK auf eine Spezialrolle. Ab 8 Spielern: 10% WSK auf 2 Spezialrollen.</small></p>
					</div>
				</div>
				
				<div id="carouselExampleSlidesOnly" class="carousel slide" data-ride="carousel">
				  <div class="carousel-inner">
					<div class="carousel-item active">
					  <div class="col-12 p-0">
						<div class="card rounded-0 mb-1" style="background-color: green">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Innocent (I)</strong></p>
								<p class="card-text"><small>Die üblichen Opfer.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>

						<div class="card rounded-0 mb-1" style="background-color: green">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Sniffer (I)</strong></p>
								<p class="card-text"><small>Sieht Fußspuren und blutige Fußspuren der Killer.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						<div class="card rounded-0 mb-1" style="background-color: green">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Spy (I)</strong></p>
								<p class="card-text"><small>Ist als Traitor getarnt.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						<div class="card rounded-0 mb-1" style="background-color: green">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Trapper (I)</strong></p>
								<p class="card-text"><small>Sieht Fallen und von wo diese aktiviert wurden.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						<div class="card rounded-0 mb-1" style="background-color: green">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Beacon (I)</strong></p>
								<p class="card-text"><small>Wird stärker, je mehr Innos tot sind.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: green">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Medium (I)</strong></p>
								<p class="card-text"><small>Sieht gelegentlich Worte aus dem Spectator-Chat.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: green">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Orakel (I)</strong></p>
								<p class="card-text"><small>Erhält minütlich 2 Spielernamen und das Team eines dieser Spieler.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: green">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Pure (I)</strong></p>
								<p class="card-text"><small>Killer wird zeitweise geblendet. Pure wird Inno, sobald er jemanden killt.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
												
						<div class="card rounded-0 mb-1" style="background-color: green">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Priest (I)</strong></p>
								<p class="card-text"><small>Darf mit Heiliger Deagle - die immer Recht hat - einen RDM for free machen.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>	
						<div class="card rounded-0 mb-1" style="background-color: blue">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Detective (I)</strong></p>
								<p class="card-text"><small>Inno mit Armor & Shop.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>						
						<div class="card rounded-0 mb-1" style="background-color: blue">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Sheriff & Deputy (I)</strong></p>
								<p class="card-text"><small>Detective der einen Deputy rekrutieren kann. Deputy stirbt mit Sheriff.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>				
					</div>
					</div>
					<div class="carousel-item">
					  <div class="col-12 p-0">
						<div class="card rounded-0 mb-1" style="background-color: maroon">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Traitor (T)</strong></p>
								<p class="card-text"><small>Die üblichen Schurken.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: maroon">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Hitman (T)</strong></p>
								<p class="card-text"><small>Muss vorgegebenes Ziel töten. Bekommt dafür Credits.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>

						<div class="card rounded-0 mb-1" style="background-color: maroon">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Defective (T)</strong></p>
								<p class="card-text"><small>Erscheint als Detective, ist aber ein Traitor.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>

						<div class="card rounded-0 mb-1" style="background-color: maroon">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Imposter (T)</strong></p>
								<p class="card-text"><small>Kein Shop. Melee-Instantkill. Kann Vents platzieren. Kann Sabotagen durchführen.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>	
						<div class="card rounded-0 mb-1" style="background-color: maroon">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Roider (T)</strong></p>
								<p class="card-text"><small>Kann nur mit Crowbar Schaden machen, dafür viel.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						<div class="card rounded-0 mb-1" style="background-color: grey">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Amnesiac (?)</strong></p>
								<p class="card-text"><small>Übernimmt Rolle eines Unconfirmten.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						<div class="card rounded-0 mb-1" style="background-color: grey">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Bodyguard (?)</strong></p>
								<p class="card-text"><small>Bekommt Partner zum beschützen, gewinnt mit diesem. Partner reflektiert Schaden.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>	
						<div class="card rounded-0 mb-1" style="background-color: grey">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Duelist (?)</strong></p>
								<p class="card-text"><small>Immer zu zweit. Duellgewinner erhält Rolle des Gegners.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						<div class="card rounded-0 mb-1" style="background-color: brown">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Swedefugee (N)</strong></p>
								<p class="card-text"><small>Integriere dich in Schweden. Versuche NIEMANDEN zu vergewaltigen, auch wenn es schwer ist.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>						
						<!--<div class="card rounded-0 mb-1" style="background-color: grey">-->
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<!--<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Elderly (?)</strong></p>
								<p class="card-text"><small>Muss überleben, gewinnt mit Gewinnerteam zusammen.</small></p>
							  </div>
							</div>-->
						  <!--</div>-->
						<!--</div>-->							
					
					</div>
					</div>
					<div class="carousel-item">
					  <div class="col-12 p-0">
						<div class="card rounded-0 mb-1" style="background-color: fuchsia">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Jester (N)</strong></p>
								<p class="card-text"><small>Darf nicht getötet werden. Macht keinen Schaden.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: fuchsia">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Clown (N)</strong></p>
								<p class="card-text"><small>Muss getötet werden. Macht keinen Schaden. Sieht aus wie Jester.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: fuchsia">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Swapper (N)</strong></p>
								<p class="card-text"><small>Darf nicht getötet werden. Respawnt und wechselt Rolle mit Killer. Macht keinen Schaden. Sieht aus wie Jester. Bekommt Punkte bei Tod.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: red">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Infected (N)</strong></p>
								<p class="card-text"><small>Muss alle töten. Getötete Gegner werden Zombies.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: red">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Hitman (N)</strong></p>
								<p class="card-text"><small>Muss alle Spieler markieren.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: red">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Serialkiller (N)</strong></p>
								<p class="card-text"><small>Muss alle Spieler töten.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>		
						
						<div class="card rounded-0 mb-1" style="background-color: red">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Hidden (N)</strong></p>
								<p class="card-text"><small>Muss alle töten. Kann unsichtbar werden und erhält Wallhack, Walljump, Speed, HP. Nur Nahkampf.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						
						<div class="card rounded-0 mb-1" style="background-color: red">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Jackal & Sidekick (N)</strong></p>
								<p class="card-text"><small>Muss alle töten. Kann mit Sidekick-Deagle einen Gehilfen rekrutieren.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>				
						
						<div class="card rounded-0 mb-1" style="background-color: red">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Necromancer (N)</strong></p>
								<p class="card-text"><small>Muss alle töten. Kann Tote als Zombies wiederbeleben.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>
						<div class="card rounded-0 mb-1" style="background-color: red">
						  <!-- <div class="row g-0">
							<div class="col-2 d-flex align-items-start justify-content-center p-1">
							  <img src="/games/ttt/loading/images/icon32.png" class="img-fluid">
							</div>-->
							<div class="col-12">
							  <div class="card-body p-0 text-light">
								<p class="card-title m-0"><strong>Thief (N)</strong></p>
								<p class="card-text"><small>Muss getötet werden. Stiehlt Sieg von Gewinnerteam.</small></p>
							  </div>
							</div>
						  <!--</div>-->
						</div>						
					</div>
					</div>
				  </div>
				</div>
			</div>
		</div>
		<script>
			j = 1;
			x = 0
			while (true){
				elements = document.querySelectorAll("[data-column='" + j + "']")
				if (elements.length < 1){
					break
				}
				
				var min_val = 9999.0
				var max_val = -9999.0
				
				for (var i = 0; i < elements.length; i++) {
					var el = elements[i];
					var val = parseFloat(el.getAttribute("data-value"))
					
					if (isNaN(val)){
						val = 0
						el.setAttribute("data-value", '0') 
						el.innerHTML = 0
					}
					
					if (val < min_val){
						min_val = val
					}
					if (val > max_val){
						max_val = val
					}
				}
				elements = document.querySelectorAll("[data-column='" + j + "'][data-value='" + min_val + "']")
				for (var i = 0; i < elements.length; i++) {
					if (elements[i].getAttribute("data-onlymax") == 1){
						//elements[i].style.color = '#ff5551';
					}else{
						if (elements[i].getAttribute("data-invert") == 1){
							elements[i].style.color = 'lightgreen';
						}else{
							elements[i].style.color = '#ff5551';
						}
					}
				}
				elements = document.querySelectorAll("[data-column='" + j + "'][data-value='" + max_val + "']")
				for (var i = 0; i < elements.length; i++) {
					if (elements[i].getAttribute("data-onlymin") == 1){
						//elements[i].style.color = 'lightgreen';
					}else{
						if (elements[i].getAttribute("data-invert") == 1){
							elements[i].style.color = '#ff5551';
						}else{
							elements[i].style.color = 'lightgreen';
						}
					}
				}
				
				j = j + 1
			}	
		</script>
	</body>
	

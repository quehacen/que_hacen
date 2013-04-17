<?php

include_once "lib/basiclib.php";
include_once "quehacenlib.php";

echo cabecera("Pruebas diputados");
echo "<body>";

//$iniciativasGrupos=array();
//$iniciativasGrupos=obtenerIniciativasGrupos();

/*foreach ($iniciativasGrupos as $grupo){
	echo"<p>Iniciativas del <b>GP ".$grupo["nombreGP"]."</b> = <b>".$grupo["inic"]."</b></p>"; 
}
*/

$diputados=infoCsvDiputados("Congresocsv.csv");
foreach ($diputados as $dipu){
	$ficha=scrapFichaDiputado($dipu["id"]);
	if($ficha["emails"]!==""){
		echo "<p>Correos de ".$ficha["nombre"]." ".$ficha["apellidos"]." : <b>".$ficha["emails"]."</b></p>";
	}else{
		echo "<p>".$ficha["nombre"]." ".$ficha["apellidos"]." no tiene correos </p>";
	}
}

//scrapMostrandoFichasDiputados();

/*
$contw=obtenerDiputadosSin("tw",$diputados);
echo "<ul><b>".count($contw)." diputados no tienen twitter</b>";
foreach($contw as $diputw){
	echo "<li><a href='https://www.twitter.com/".$diputw["tw"]."'>@".$diputw["tw"]."</a></li>";
}
*/

/*foreach($diputados as $diputado){
	echo "<ul>Iniciativas de ".$diputado["nombre"]." ".$diputado["apellidos"]." (id ".$diputado["id"]." )";
	$inic=obtenerNumIniciativas($diputado["id"]);
	foreach($inic as $campo => $valor){
		echo "<li>".$campo." : ".$valor."</li>";
	}
	echo "</ul>";
}*/



echo "</body></html>";
?>
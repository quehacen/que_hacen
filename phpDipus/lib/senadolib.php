<?php

include_once "basiclib.php";
include_once "../quehacenlib.php";

echo cabecera("Pruebas XML");
echo "<body>";

function scrapNombreSenadoresHistorico(){
	$senadores=array();
	$i=0;
	for($j=0;$j<11;$j++){
		$url="http://www.senado.es/web/composicionorganizacion/senadores/composicionsenado/senadoresdesde1977/consultaorden/index.html?legis=$j";
		$html=obtenerSimpleHTML($url);
		$listado=$html->find("div[class=listado_completo]",0)->find("div[class=senador_ampliado]");
		foreach($listado as $elem){
			$nombre=$elem->find("a",0)->title;
			$existe=estaEnArray($nombre,$senadores);
			if($existe===false){
				$senadores[$i]=$nombre;
				$i++;
			}
		}
	}
	return $senadores;
}

function scrapNombreSenadores(){
	$senadores=array();
	$i=0;
	$url="http://www.senado.es/web/composicionorganizacion/senadores/composicionsenado/senadoresenactivo/consultaordenalfabetico/index.html";
	$html=obtenerSimpleHTML($url);
	$listado=$html->find("div[class=listado_completo]",0)->find("div[class=senador_ampliado]");
	echo "<ul>Listado de senadores:";
	foreach($listado as $elem){
		$senadores[$i]=array();
		$senadores[$i]["nombre"]=$elem->find("a",0)->title;
		$i++;
	}
	
	return $senadores;
}

function scrapSenadores(){
	$senadores=array();
	$i=0;
	$url="http://www.senado.es/web/composicionorganizacion/senadores/composicionsenado/senadoresenactivo/consultaordenalfabetico/index.html";
	$html=obtenerSimpleHTML($url);
	$listado=$html->find("div[class=listado_completo]",0)->find("div[class=senador_ampliado]");
	echo "<ul>Listado de senadores:";
	foreach($listado as $elem){
		$senadores[$i]=array();
		$urlFicha="http://www.senado.es".$elem->find("a",0)->href;
		$senadores[$i]["nombre"]=$elem->find("a",0)->title;
		echo "<li><a href='".$urlFicha."'>".$senadores[$i]["nombre"]."</a></li>";
		$fichahtml=obtenerSimpleHTML($urlFicha);
		$basicohtml=$fichahtml->find("div[class=content_left_colum2]",0);
		$actividadhtml=$fichahtml->find("div[id=actividad]",0);
		
		$senadores[$i]["circunsc"]=scrapCircunsSenador($basicohtml);
		$senadores[$i]["fecha"]=scrapFechaSenador($basicohtml);
		$senadores[$i]["partido"]=scrapPartidoSenador($basicohtml);
		
		$actParl=scrapActividadSenador($actividadhtml);
		$senadores[$i]["iniciativas"]=$actParl["inic"];
		$senadores[$i]["intervenciones"]=$actParl["interv"];
		
		echo "<li>Circunscripción: ".$senadores[$i]["circunsc"]."</li>";
		echo "<li>Fecha: ".$senadores[$i]["fecha"]."</li>";
		echo "<li>Partido: ".$senadores[$i]["partido"]."</li>";
		echo "<li><ul>Iniciativas:";
		foreach($senadores[$i]["iniciativas"] as $tipo => $valor) 
			echo "<li>".$tipo.": ".$valor."</li>";
		echo "</ul></li>";
		echo "<li><ul>Intervenciones:";
		foreach($senadores[$i]["intervenciones"] as $tipo => $valor) 
			echo "<li>".$tipo.": ".$valor."</li>";
		echo "</ul></li>";
		
		
		$i++;
	}
	echo "</ul>";
	return $senadores;
}

function scrapCircunsSenador($fichahtml){
	$text=$fichahtml->find('li',1)->plaintext;
	$quitar=array("Electo:","Electa:");
	return sinTNS(str_replace($quitar,"",$text));
}

function scrapFechaSenador($fichahtml){
	$text=$fichahtml->find('li',2)->plaintext;
	$quitar=array("Fecha:");
	return sinTNS(str_replace($quitar,"",$text));
}

function scrapPartidoSenador($fichahtml){
	if($fichahtml->find('li',5)!==null){
		$text=$fichahtml->find('li',5)->plaintext;
	}else{
		$text=$fichahtml->find('li',4)->plaintext;
	}
	$separado=parentesisTexto($text);
	return $separado["ptsis"];
}

function scrapActividadSenador($html){
	$elems=$html->children();
	$tipo=0;
	$inic=0;
	$interv=0;
	$actividad=array();
	$actividad["inic"]=array();
	$actividad["interv"]=array();
	$actividad["inic"]["total"]=0;
	$actividad["inic"]["pregEscritas"]=0;
	$actividad["inic"]["pregOralPleno"]=0;
	$actividad["inic"]["pregOralComision"]=0;
	$actividad["inic"]["interp"]=0;
	$actividad["inic"]["solicitInformes"]=0;
	$actividad["inic"]["peticComparecencias"]=0;
	$actividad["inic"]["mocion"]=0;
	$actividad["inic"]["otras"]=0;
	$actividad["interv"]["total"]=0;
	$actividad["interv"]["proyLey"]=0;
	$actividad["interv"]["comparecencias"]=0;
	$actividad["interv"]["mociones"]=0;
	$actividad["interv"]["interp"]=0;
	$actividad["interv"]["proposLey"]=0;
	$actividad["interv"]["pregOralPleno"]=0;
	$actividad["interv"]["pregOralComision"]=0;
	$actividad["interv"]["informes"]=0;
	$actividad["interv"]["otras"]=0;
	
	foreach($elems as $elem){
		if($elem->tag==="h2"){
			if(strpos($elem->plaintext,"Iniciativas")!==false){
				$tipo=1;
			}elseif(strpos($elem->plaintext,"Intervenciones")!==false){
				$tipo=2;
			}else{
				echo "<li><b>Sección de actividad no reconocida: ".$elem->plaintext.".</b></li>";
			}
		}else{
			$nom=sinTNS($elem->find('p[class=nombre]',0)->plaintext);
			$num=sinTNS($elem->find('p[class=fecha]',0)->plaintext);
			if($tipo==1){
				$campo=obtenerCampoInic($nom);
				$actividad["inic"][$campo]=$actividad["inic"][$campo]+$num;
				$inic=$inic+$num;
			}elseif($tipo==2){
				$campo=obtenerCampoInterv($nom);
				$actividad["interv"][$campo]=$actividad["interv"][$campo]+$num;
				$interv=$interv+$num;
			}else{
				if($tipo==0)	echo "<li><b>ERROR, número de actividad sin clasificar.</b></li>";
			}
		}
	}
	$actividad["interv"]["total"]=$interv;
	$actividad["inic"]["total"]=$inic;
	
	return $actividad;
	//echo "<li>Número de iniciativas: ".$inic."</li>";
	//echo "<li>Número de intervenciones: ".$interv."</li>";
}

function inside($lista,$valor){
	for($i=0;$i<count($lista);$i++){
		if($lista[$i]["nombre"]===$valor)	return $i;
	}
	return false;
}

function obtenerCampoInic($nombre){
	if(strpos($nombre,"Pregunta oral")!==false){
			if(strpos($nombre,"Pleno")!==false){
				return "pregOralPleno";
			}else{
				return "pregOralComision";
			}
	}elseif(strpos($nombre,"respuesta escrita")!==false){
		return "pregEscritas";
	}elseif(strpos($nombre,"Interpelación")!==false){
		return "interp";
	}elseif(strpos($nombre,"omparecencia")!==false){
		return "peticComparecencias";
	}elseif(strpos($nombre,"Solicitud de informe")!==false){
		return "solicitInformes";
	}elseif(strpos($nombre,"Moción")!==false){
		return "mocion";
	}else{
		return "otras";
	}
}
	
function obtenerCampoInterv($nombre){
	if(strpos($nombre,"Proyecto de Ley")!==false){
		return "proyLey";
	}elseif(strpos($nombre,"omparecencia")!==false){
		return "comparecencias";
	}elseif(strpos($nombre,"Pregunta oral")!==false){
		if(strpos($nombre,"Pleno")!==false){
			return "pregOralPleno";
		}else{
			return "pregOralComision";
		}
	}elseif(strpos($nombre,"Moción")!==false){
		return "mociones";
	}elseif(strpos($nombre,"Interpelación")!==false){
		return "interp";
	}elseif(strpos($nombre,"Proposición de ley")!==false){
		return "proposLey";
	}elseif(strpos($nombre,"nforme")!==false){
		return "informes";
	}else{
		return "otras";
	}
}


function obtenerTipoActividades(){
	$url="http://www.senado.es/web/composicionorganizacion/senadores/composicionsenado/senadoresenactivo/consultaordenalfabetico/index.html";
	$html=obtenerSimpleHTML($url);
	$listado=$html->find("div[class=listado_completo]",0)->find("div[class=senador_ampliado]");
	$tipoInic=array();
	$tipoInterv=array();
	foreach($listado as $elem){
		$urlFicha="http://www.senado.es".$elem->find("a",0)->href;
		$nombre=$elem->find("a",0)->title;
		echo "<ul>Buscando en senador <a href='".$urlFicha."'>".$nombre."</a>";
		$fichahtml=obtenerSimpleHTML($urlFicha);
		$actividadhtml=$fichahtml->find("div[id=actividad]",0);
		
		$elems=$actividadhtml->children();
		$tipo=0;
		foreach($elems as $elem){
			if($elem->tag==="h2"){
				if(strpos($elem->plaintext,"Iniciativas")!==false){
					$tipo=1;
				}elseif(strpos($elem->plaintext,"Intervenciones")!==false){
					$tipo=2;
				}else{
					echo "<li>Sección de actividad no reconocida: <b>'".$elem->plaintext."'</b></li>";
				}
			}else{
				$nom=sinTNS($elem->find('p[class=nombre]',0)->plaintext);
				if($tipo==1){
					$estaEn=inside($tipoInic,$nom);
					if($estaEn===false){
						$index=count($tipoInic);
						$tipoInic[$index]["nombre"]=$nom;
						$tipoInic[$index]["cont"]=1;
						echo "<li>Tipo de Iniciativa número $index: <b>'".$nom."'</b></li>";
					}else{
						$tipoInic[$estaEn]["cont"]++;
						echo "<li>Inic[$estaEn]= ".$tipoInic[$estaEn]["cont"].";</li>";
					}
				}elseif($tipo==2){
					$estaEn=inside($tipoInterv,$nom);
					if($estaEn===false){
						$index=count($tipoInterv);
						$tipoInterv[$index]["nombre"]=$nom;
						$tipoInterv[$index]["cont"]=1;
						echo "<li>Tipo de Intervención número $index: <b>'".$nom."'</b></li>";
					}else{
						$tipoInterv[$estaEn]["cont"]++;
						echo "<li>Interv[$estaEn]= ".$tipoInterv[$estaEn]["cont"].";</li>";
					}
				}else{
					echo "<li><b>ERROR</b>, número de actividad sin clasificar.</li>";
				}
			}
		}
		echo "</ul>";
	}
	echo "<ul>Tipos de iniciativas: ".count($tipoInic);
	foreach($tipoInic as $ti)	echo "<li>".$ti["nombre"]." <b>".$ti["cont"]."</b></li>";
	echo "</ul>";
	
	echo "<ul>Número de intervenciones: ".count($tipoInterv);
	foreach($tipoInterv as $ti)	echo "<li>".$ti["nombre"]." <b>".$ti["cont"]."</b></li>";
	echo "</ul>";
}

function csvActividadSenadores($senadores){

	$fila=array();
	$fp = fopen('ActividadSenadores.csv', 'w');
	for($i=0;$i<24;$i++)	$fila[$i]="";
	$fila[0]="Datos básicos";
	$fila[4]="Totales";
	$fila[7]="Iniciativas";
	$fila[15]="Intervenciones";
	fputcsv($fp,$fila);
	
	$fila[0]="Nombre";
	$fila[1]="Circunscripción/CCAA";
	$fila[2]="Partido";
	$fila[3]="Fecha alta";
	$fila[4]="TOTAL INICIATIVAS";
	$fila[5]="TOTAL INTERVENCIONES";
	$fila[6]="TOTAL INIC + INTERV";
	$fila[7]="pregEscritas";
	$fila[8]="pregOralPleno";
	$fila[9]="pregOralComision";
	$fila[10]="interp";
	$fila[11]="solicitInformes";
	$fila[12]="peticComparecencias";
	$fila[13]="mocion";
	$fila[14]="otras";
	$fila[15]="proyLey";
	$fila[16]="comparecencias";
	$fila[17]="mociones";
	$fila[18]="interp";
	$fila[19]="proposLey";
	$fila[20]="pregOralPleno";
	$fila[21]="pregOralComision";
	$fila[22]="informes";
	$fila[23]="otras";
	fputcsv($fp,$fila);

	foreach($senadores as $senador){
		$fila[0]=$senador["nombre"];
		$fila[1]=$senador["circunsc"];
		$fila[2]=$senador["partido"];
		$fila[3]=$senador["fecha"];

		$fila[4]=$senador["iniciativas"]["total"];
		$fila[5]=$senador["intervenciones"]["total"];
		$fila[6]=$senador["iniciativas"]["total"] + $senador["intervenciones"]["total"];
		$fila[7]=$senador["iniciativas"]["pregEscritas"];
		$fila[8]=$senador["iniciativas"]["pregOralPleno"];
		$fila[9]=$senador["iniciativas"]["pregOralComision"];
		$fila[10]=$senador["iniciativas"]["interp"];
		$fila[11]=$senador["iniciativas"]["solicitInformes"];
		$fila[12]=$senador["iniciativas"]["peticComparecencias"];
		$fila[13]=$senador["iniciativas"]["mocion"];
		$fila[14]=$senador["iniciativas"]["otras"];
		$fila[15]=$senador["intervenciones"]["proyLey"];
		$fila[16]=$senador["intervenciones"]["comparecencias"];
		$fila[17]=$senador["intervenciones"]["mociones"];
		$fila[18]=$senador["intervenciones"]["interp"];
		$fila[19]=$senador["intervenciones"]["proposLey"];
		$fila[20]=$senador["intervenciones"]["pregOralPleno"];
		$fila[21]=$senador["intervenciones"]["pregOralComision"];
		$fila[22]=$senador["intervenciones"]["informes"];
		$fila[23]=$senador["intervenciones"]["otras"];

		fputcsv($fp, $fila);
	}
	fclose($fp);
}

/*
$senadores=scrapSenadores();
csvActividadSenadores($senadores);
*/

/*
$senadores=scrapNombreSenadores();
echo "<p>Hay ".count($senadores)." senadores</p>";


if(($gestor = fopen("ActividadSenadores.csv", "r")) !== FALSE) {
	while ( ($datos = fgetcsv($gestor, 3000, ",")) !== FALSE) {
		$esta=false;
		for($i=0;$i<count($senadores);$i++){
			if($senadores[$i]["nombre"] === $datos[0]){
				$esta=true;
				break;
			}
		}
		if($esta===false){
			echo "<p>El senador ".$datos[0]." no está en el listado del senado";
			
		}
	}
}
*/

function nombreParaComparar(){
	
}

$diputados=infoCsvDiputados("../Congresocsv.csv");
echo "<h2>Dipus</h2>";
foreach($diputados as $dipu){
	$nomDip=quitarTildes(aMayus($dipu["apellidos"]).", ". aMayus($dipu["nombre"]));
	echo "<p>$nomDip</p>";
}

$senadores=scrapNombreSenadoresHistorico();
echo "<h2>Senadores</h2>";
foreach($senadores as $senador){
	$nomSen=quitarTildes($senador);
	echo "<p>$nomSen</p>";
}

echo "<h2>Diputados que han sido senadores</h2>";
$cont=0;
foreach($diputados as $dipu){
	$is=false;
	$nombreDipu=quitarTildes(aMayus($dipu["apellidos"]).", ". aMayus($dipu["nombre"]));
	$i=0;
	while($is===false && $i<count($senadores)){
		$nombreSen=quitarTildes($senadores[$i]);
		if($nombreDipu===$nombreSen){
			$cont++;
			$is=true;
			echo "<p>$cont: $nombreDipu ha sido senador y diputado</p>";
		}
		$i++;
	}
}


/*$url="http://www.senado.es/web/composicionorganizacion/senadores/composicionsenado/fichasenador/index.html?id1=14926&legis=10";
$ficha=obtenerSimpleHTML($url);
$act=$ficha->find("div[id=actividad]",0);
scrapActividadSenador($act);
*/

//obtenerTipoActividades();

echo "</body></html>";

?>
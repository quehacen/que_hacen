<?php
/*
PROCESAMIENTO DEL CSV DE DIPUTADOS
	infoCsvDiputados($csv)
	obtenerIdDipuUrl($url)
	obtenerCargos($datos)
	obtenerCargosPartidoGob($datos)
	
	[Visualización]
	obtenerDiputadosCon($campo,$diputados)
	obtenerDiputadosSin($campo,$diputados)
	mostrarDiputado($diputado)
	mostrarDiputados($dipus)
	mostrarNDiputados($diputados,$n)
	mostrarDipusOrd($dipus)
	numDiputadosCon($diputados,$nombreCampo) : Número de diputados con un determinado campo
	numDiputadosSin($diputados,$nombreCampo)
	numDiputadosConAlguno($diputados,$nombreCampo,$nombreCampo2) : Número de diputados con alguno de los 2 campos
	listarDiputadosCon($diputados, $nombreCampo) : Lista los diputados con un determinado campo
	listarDiputadosSin($diputados, $nombreCampo)
	
SCRAPEO
	scrapFichasDiputados()
	scrapMostrandoFichasDiputados()
	scrapFichaDiputado($id)
	obtenerDatosBaja($html)
	obtenerGP($html)
	obtenerNacimiento($html)
	obtenerOtrasLegis($html)
	obtenerURLsDipu($html)
	obtenerPosHemiciclo($html)
	obtenerNumIniciativas($idDiputado)
	obtenerNumIntervenciones($idDiputado)
	obtenerImagenDipu($id)
	obtenerMiniaturaDipu($id)
	obtenerCargosCongreso()
	obtenerComSubcom()
	
	obtenerNumIniciativasGrupos()
	
	generarXMLComisiones()
	
	
PROCESAMIENTO DATOS DIPUTADOS, CARGOS Y ÓRGANOS
	estandarizarLegis($cad)
	tipoCargoToID($cargo)
	nombreCargo($cargo,$sexo)
	enTipoOrgano($cargos,$tipoOrgano)
	tieneCargoCongreso($cargos,$tipoOrgano,$cargo)
	enMC($cargos)
	enJP($cargos)
	tieneCargoMC($cargos,$cargo)
	tieneCargoJP($cargos,$cargo)
	tieneCargoComision($cargos,$cargo)
	cargoEnMC($cargos)
	cargoEnJP($cargos)
	mayorCargoEnCom($cargos)
	estandarizarOrgano($organo)
	prefijoComision($organo)
	nombreCom($organo)
	nombreSubcom($organo)
	
	completarDiputado($dipu,$dipusCsv)
	meterCargosSueldo($dipu,$cargos)
	obtenerSueldo($sexo,$provincia,$gp,$cargos,$cargosGob,$retIRPF)
	tieneURL($urls,$tipo,$url="")
	insertarURL($urls,$tipo,$url)
	
CADENAS, INTERNOS Y OTROS
	separarFrases($cad)
	separarContenido($cont) : Devuelva un array. Cada elemento del array --> array con "texto" y "ptsis"
	parentesisTexto($cad,[$desde]) : A partir de una cadena, separaDevuelve un array con los indices "texto" y "ptsis"
	traducirHTML($cad)
	obtenerSimpleHTML($url)
	obtenerCampoUrl($url,$campo)
	fechanumerica($fecha)
	mesNumerico($mes)
    estandarizarFecha($fecha)

FUNCIONES MONGODB
    getHTMLCongreso($url,$forzarAct)
    getSimpleHTMLCongreso($url,$forzarAct)
    verHTMLCol()
    dropHTMLCol()
    verDipusCol()
    dropDipusCol()
    csvLegislaturas()
*/

include_once "basiclib.php";
include_once "simple_html_dom.php";
include_once "phpthumb/ThumbLib.inc.php";


// 		[PROCESAMIENTO DEL CSV DE DIPUTADOS] 	

/* Esta función obtiene del csv los datos que nunca o casi nunca pueden scrapearse de su congreso.es (más nombre y apellidos), 
   y da como salida un array $diputados con los siguientes campos:
	- id, nombre, apellidos, lugarnac, parentescoRel: string
	- empresasPriv, cargosPublicos : int (0 ó 1)
	- mail, mail2, tlf, tw, fb, goo, utube, flickr, ldin, wiki, blog, blog2, web : string
	- estudios : array(
		"centro" : string
		"estudio" : string
		)
	- cargosPartido : array(
		"fini" : string (el año o vacío si no se especifica)
		"cargo" : string
		)
	- cargosGobierno : array(
		"fini" : string (el año o vacío si no se especifica)
		"cargo" : string
		)
	- cargos : array( [Trayectoria profesional]
		- fini : string (el año o vacío si no se especifica)
		- ffin : string (el año o vacío si no se especifica)
		- cargo : string
		) */
function infoCsvDiputados($csv){
	$diputados= array();
	$fila = 0;

	if(($gestor = fopen($csv, "r")) !== FALSE) {
		while ( ($datos = fgetcsv($gestor, 3000, ",")) !== FALSE) {
			if ($datos[0]!="Provincia"){
				for($i=0;$i<count($datos);$i++){
                 //   if($i !== 13) 
                 //   $datos[$i]=htmlspecialchars($datos[$i], ENT_COMPAT | ENT_HTML401 , 'UTF-8');
					$datos[$i]=sinPFS($datos[$i]);	
				}
					
				// Procesamiento de datos
				$empresasPriv=0;
				if(strpos($datos[10],"s")!==false || strpos($datos[10],"S")!==false)
					$empresasPriv=1;
				$cargosPublicos=0;
				if(strpos($datos[11],"s")!==false || strpos($datos[11],"S")!==false)
					$cargosPublicos=1;
				if (strpos($datos[7],",") !== false){
					$poblnac=quitarEspaciosSobra(substrHasta($datos[7],","));
					$provnac=quitarEspaciosSobra(substrDesde($datos[7],","));
					$lugarnac=$poblnac." (".$provnac.")";
				}else{
					$lugarnac=$datos[7];
				}
				$id = obtenerIdDipuUrl($datos[13]); 
				$estudios = separarContenido($datos[9],"estudio","centro");
				$cargos = obtenerCargos($datos);
				$cargosPartido = obtenerCargosPartidoGob($datos[31]);
				$cargosGobierno = obtenerCargosPartidoGob($datos[32]);
				
				// Asignación de datos
				$diputados[$fila] = array();
				$diputados[$fila]["circunscripcion"]=$datos[0];
				$diputados[$fila]["partido"]=$datos[1];
				$diputados[$fila]["nombre"]=$datos[2];
				$diputados[$fila]["apellidos"]=$datos[3]." ".$datos[4];
				$diputados[$fila]["lugarnac"]=$lugarnac;
				$diputados[$fila]["parentescoRel"]=$datos[8];
				$diputados[$fila]["url_nomina"]=$datos[33];
				$diputados[$fila]["retencion_irpf"]=$datos[34];
				$diputados[$fila]["email"]=$datos[35];
				$diputados[$fila]["email2"]=$datos[36];
				$diputados[$fila]["telefono"]=$datos[37];
				$diputados[$fila]["twitter"]=$datos[41];
				$diputados[$fila]["facebook"]=$datos[42];
				$diputados[$fila]["google"]=$datos[43];
				$diputados[$fila]["youtube"]=$datos[44];
				$diputados[$fila]["flickr"]=$datos[45];
				$diputados[$fila]["linkedin"]=$datos[46];
				$diputados[$fila]["wikipedia"]=$datos[47];
				$diputados[$fila]["blog"]=$datos[48];
				$diputados[$fila]["blog2"]=$datos[49];
				$diputados[$fila]["web"]=$datos[50];
				
				$diputados[$fila]["id"]=$id;
				$diputados[$fila]["empresasPriv"]=$empresasPriv;
				$diputados[$fila]["cargosPublicos"]=$cargosPublicos;
				$diputados[$fila]["estudios"]=$estudios;
				$diputados[$fila]["cargos_partido"]=$cargosPartido;
				$diputados[$fila]["cargos_gobierno"]=$cargosGobierno;
				$diputados[$fila]["trayectoria"]=$cargos;
				
				$fila++;
			}
			
		}
		fclose($gestor);
	}
	return $diputados;
}

// A partir de una URL de ficha de diputado, devuelve el ID del diputado
function obtenerIdDipuUrl($url){
	$sub= substrHasta($url,'&idLegislatura');
	if ($sub != ""){
		$aux=$sub;
	}else{
		$aux=$url;
	}
	$id = substrDesde($aux,'=');
	return intval($id);
}

// Devuelve un array con los cargos de la trayectoria profesional del diputado
function obtenerCargos($datos){
	$patron1="/\((\d\d\d\d|\?)[-\/](\d\d\d\d|\?|Actualidad|actualidad|ACTUALIDAD)\)/";
	$patron2="/\(\d\d\d\d\)/";
	$cargos=array();
	$i=0;
	$j=15;
	
	while($j<31){
		if($datos[$j]!=""){
			$cargos[$i]=array();
			if (preg_match($patron1, $datos[$j], $coincidencias, PREG_OFFSET_CAPTURE) !== 0){
				$sep=parentesisTexto($datos[$j],$coincidencias[0][1]);
				$fini=$coincidencias[1][0];
				$ffin=$coincidencias[2][0];
                if($ffin==="Actualidad" || $ffin==="actualidad" || $ffin==="ACTUALIDAD")
                    $ffin=0;
				$cargos[$i]["cargo"]=$sep["texto"];
				if($fini!=="?") $cargos[$i]["fini"]=$fini;
				if($ffin!=="?") $cargos[$i]["ffin"]=$ffin;
				
			}elseif (preg_match($patron2, $datos[$j], $coincidencias, PREG_OFFSET_CAPTURE) !== 0){
				$sep=parentesisTexto($datos[$j],$coincidencias[0][1]);
				$cargos[$i]["cargo"]=$sep["texto"];
				$cargos[$i]["fini"]=$sep["ptsis"];
				$cargos[$i]["ffin"]=$sep["ptsis"];
			}else{
				$cargos[$i]["cargo"]=$datos[$j];
			}
			$i++;
		}
		$j++;
	}
	return $cargos;
}

// Devuelve un array con los cargos actuales del diputado en su partido o en el gobierno (funciona para ambos)
	// dividiendo si se puede entre el cargo y el año de inicio
function obtenerCargosPartidoGob($datos){
	$patron="/\(\d\d\d\d\)/";
	$separados=separarFrases($datos);
	
	if($separados === false){
		return false;
	}else{
		$cargos=array();
		for($i=0;$i<count($separados);$i++){
			$cargos[$i]=array();
			if (preg_match($patron, $separados[$i], $coincidencias, PREG_OFFSET_CAPTURE) !== 0){
				$sep=parentesisTexto($separados[$i],$coincidencias[0][1]);
				$cargos[$i]["cargo"]=$sep["texto"];
				$cargos[$i]["fini"]=$sep["ptsis"];
			}else{
				$cargos[$i]["cargo"]=$separados[$i];
			}
		}
		return $cargos;
	}
}

// Muestra todos los datos de un diputado obtenido del csv
function mostrarDiputado($diputado){
	$url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Diputados/BusqForm?_piref73_1333155_73_1333154_1333154.next_page=/wc/fichaDiputado?idDiputado=".$diputado["id"]."&idLegislatura=10";
	$img="http://www.congreso.es/wc/htdocs/web/img/diputados/". $diputado["id"] ."_10.jpg";
	echo "<br/><ul style='clear:left; list-style-type:none;'><a href='$url'>".$diputado["nombre"]." ".$diputado["apellidos"]."</a>  :";
	echo "<br/><img style=' float:right; width:160px; height:200px; padding:20px;' src='$img' />";
	foreach ($diputado as $campo => $valor){
		if($campo == "cargos" || $campo=="cargosPartido" || $campo=="cargosGobierno" || $campo=="estudios"){
			if($valor!==false){
				if ($campo=="cargos") echo "<li><ul><b>Pasado profesional</b>:";
				if ($campo=="cargosPartido") echo "<li><ul><b>Cargos actuales en el partido</b>:";
				if ($campo=="cargosGobierno") echo "<li><ul><b>Cargos actuales en el gobierno</b>:";
				if ($campo=="estudios") echo "<li><ul><b>Estudios cursados:</b>:";
				
				if($campo=="cargos"){
					for($i=0;$i<count($valor);$i++)
						echo "<li>".$valor[$i]["cargo"]." / ".$valor[$i]["fini"]. " / ".$valor[$i]["ffin"]."</li>";
				}elseif($campo=="estudios"){
					for($i=0;$i<count($valor);$i++)
							echo "<li>".$valor[$i]["estudio"]." / ".$valor[$i]["centro"]. "</li>";
				}else{
					for($i=0;$i<count($valor);$i++)
						echo "<li>".$valor[$i]["cargo"]." / ".$valor[$i]["fini"]. "</li>";
				}
					
				echo "</ul></li>";
			}
		}elseif(gettype($valor) === "array"){
			echo "<li><ul>$campo";
			for($i=0;$i<count($valor);$i++){
				echo "<li>".$valor[$i]["texto"]." / ".$valor[$i]["ptsis"]."</li>";
			}
			echo "</ul></li>";
		}elseif($valor!=="" && $campo!="nombre" && $campo!="circunscripcion" && $campo!="partido"){
			echo "<li>$campo = $valor</li>";
		}
	}
	echo "</ul><br/>";
}

// Muestra todos los $diputados
function mostrarDiputados($dipus){
	for ($i=0; $i<count($dipus); $i++){
		mostrarDiputado($dipus[$i]);
	}
}

// Muestra $n diputados
function mostrarNDiputados($diputados,$n){
	for ($i=0; $i<$n; $i++){
		mostrarDiputado($diputados[$i]);
	}
}

// Devuelve el nº de $diputados con $campo
function numDiputados($diputados,$nombreCampo){
	$num=0;
	for($i=0;$i<count($diputados);$i++){
		if($diputados[$i][$nombreCampo] === "" || $diputados[$i][$nombreCampo] === false){
			$num++;
		}
	}
	return $num;
}

// Devuelve el nº de $diputados con $campo
function numDiputadosCon($diputados,$nombreCampo){
	$num=0;
	for($i=0;$i<count($diputados);$i++){
		if($diputados[$i][$nombreCampo] !== "" && $diputados[$i][$nombreCampo] !== false){
			$num++;
		}
	}
	return $num;
}

// Devuelve el nº de $diputados que tengan $nombreCampo y/o $nombreCampo2
function numDiputadosConAlguno($diputados,$nombreCampo,$nombreCampo2){
	$num=0;
	for($i=0;$i<count($diputados);$i++){
		if(($diputados[$i][$nombreCampo] !== "" && $diputados[$i][$nombreCampo] === false )
			|| ($diputados[$i][$nombreCampo2] !== "" && $diputados[$i][$nombreCampo2] === false)){
			$num++;
		}
	}
	return $num;
}

// A partir de $diputados (csv), devuelve array con los diputados que tengan el campo $campo
function obtenerDiputadosCon($campo,$diputados){
	$diputadosCon=array();
	$i=0;
	foreach($diputados as $diputado){
		if($diputado[$campo]!=="" && $diputado[$campo]!==false){
			$diputadosCon[$i]=$diputado;
			$i++;
		}
	}
	return $diputadosCon;
}

// Devuelve array con los diputados que no tengan el campo $campo
function obtenerDiputadosSin($campo,$diputados){
	$diputadosSin=array();
	$i=0;
	foreach($diputados as $diputado){
		if($diputado[$campo]==="" || $diputado[$campo]===false){
			$diputadosSin[$i]=$diputado;
			$i++;
		}
	}
	return $diputadosSin;
}

// 			SCRAPEO 

// Scrapea de congreso.es los datos de la ficha de los diputados de la XLegislatura (tanto activos como inactivos)
// Devuelve array $diputados
function scrapFichasDiputados(){
	$diputados=array();
	$i=0;
	$seguir=true;
	while($seguir){
		$scrap=scrapFichaDiputado($i+1);
		if ($scrap===false){
			$seguir=false;
		}else{
			$diputados[$i]=$scrap;
			$i++;
		}
	}
	return $diputados;
}

// Igual que scrapFichasDiputados(), pero mostrando por pantalla los datos que va scrapeando
function scrapMostrandoFichasDiputados(){
	$diputados=array();
	$i=0;
	$seguir=true;
	while($seguir){
		$dipu=scrapFichaDiputado($i+1);
		if($dipu===false){
			echo "<p><b>El diputado $i no existe</b></p>";
			$seguir=false;
		}else{
			$diputados[$i]=$dipu;
			$i++;
			$url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Diputados/BusqForm?_piref73_1333155_73_1333154_1333154.next_page=/wc/fichaDiputado?idDiputado=$i&idLegislatura=10";
			//$img="http://www.congreso.es/wc/htdocs/web/img/diputados/".$i."_10.jpg";
			echo "<ul'><a href='$url'>".$dipu["nombre"]." ".$dipu["apellidos"]."</a>  (".$dipu["partido"].", ".$dipu["circunscripcion"].")";
			foreach($dipu as $c => $v){
				if(gettype($v) === "array"){
					echo "<li>$c :<ul>";
					foreach($v as $c2 => $v2){
						if(gettype($v2) === "array"){
							echo "<li>$c2 :<ul>";
							foreach($v2 as $c3 => $v3){
								echo "<li>$c3 = $v3</li>";
							}
							echo "</ul></li>";
						}else{
							echo "<li>$c2 = $v2</li>";
						}
					}
					echo "</ul></li>";
				}elseif($c!=="nombre" && $c!=="apellidos" && $c!=="partido" && $c!=="circunscripcion" && $v!==false && $v!==""){
					echo "<li>$c = $v</li>";
				}
			}
			echo "</ul>";
		}
	}
	return $diputados;
}

/* Si la id es correcta, devuelve array de diputado con los siguientes campos:
	- activo : int (0 ó 1)
	- sustituto : int
	- fecha_baja : string 
	- escano : int
	- id : int
	- nombre, apellidos, circunscripcion, grupo, fecha_nac, lugar_nac,
	- sexo : string ("H" ó "M"),
	- legislaturas : array (todas las legislaturas en las que ha sido diputado)
	- contacto : array[]{
		"tipo" : string,
		"url" : string
		}
	- actividad: array[]{
		"fecha" : date
		"iniciativas" : 
	}
Si la id no es correcta, devuelve false	*/
// HAY QUE COMPROBAR DESDE LA FUNCIÓN SI EL HTML ESTÁ YA DESCARGADO
function scrapFichaDiputado($id,$forzarAct=0){
    $diputado=array();
    $url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Diputados/BusqForm?_piref73_1333155_73_1333154_1333154.next_page=/wc/fichaDiputado?idDiputado=".$id."&idLegislatura=10";
    if($forzarAct===1 || $forzarAct===2){
        $html=getSimpleHTMLCongreso($url,1);
    }else{
        $html=getSimpleHTMLCongreso($url,0);
    }
	
	// Obtenemos los dos divs principales donde está la información
	$ppal=$html->find('div[id=curriculum]',0);
	$izq=$html->find('div[id=datos_diputado]',0);
	
	// Si no tiene el div principal --> La página de ese diputado no existe --> Salimos con false
    if($ppal===null)	return false;
	
	// Comprobamos si el diputado sigue activo o se ha dado de baja, y obtenemos los
    // datos necesarios en cada caso
    
    $datosAB=obtenerDatosAltaBaja($ppal);
    foreach ($datosAB as $campo => $valor)    $diputado[$campo]=$valor;
    if($diputado["activo"]===1) 
        $diputado["escano_actual"]=obtenerPosHemiciclo($izq);
	
	// Obtenemos los demás datos de la ficha del diputado
	$diputado["id"]=$id;
	$nombreCompleto=sinTNS($ppal->find('div[class=nombre_dip]',0)->plaintext);
    $diputado["apellidos"]=sinSS(substrHasta($nombreCompleto,","));
    $diputado["nombre"]=sinSS(substrDesde($nombreCompleto,","));
	$aux=sinTNS($ppal->find('div[class=texto_dip]',0)->find('div[class=dip_rojo]',0)->plaintext);
	$diputado["sexo"]="H";
	if(strpos($aux,"Diputada")!==false) $diputado["sexo"]="M";
	$diputado["circunscripcion"]=sinSS(str_replace(array("Diputado","Diputada","por","."),"",$aux));
	$diputado["partido"]=sinTNS($izq->find('p[class=nombre_grupo]',0)->plaintext); //Ojo, algunos son coalición, no el partido
	$diputado["grupo"]=obtenerGP($ppal);
    $nacim=obtenerNacimiento($ppal);
	$diputado["fecha_nac"]=$nacim["fecha"];
	$diputado["lugar_nac"]=sinPFS($nacim["lugar"]);
    $legis=obtenerOtrasLegis($ppal);
    if($legis!==false) $diputado["legislaturas"]=$legis;
	$diputado["contacto"]=obtenerURLsDipu($ppal);
	
    $fechaHoy=date("d-m-Y");
    $act=0;
    if($forzarAct===1 || $forzarAct===3) $act=1;
	$diputado["actividad"]=array();
	$diputado["actividad"][0]=array();
    $diputado["actividad"][0]["fecha"]=$fechaHoy;
    $diputado["actividad"][0]["intervenciones"]=obtenerNumIntervenciones($id,$act);
	$diputado["actividad"][0]["iniciativas"]=obtenerNumIniciativas($id,$act);
	
	return $diputado;
}


function obtenerDatosAltaBaja($html){
    $datos=array();
    $listaDatos=$html->find('div[class=texto_dip]',1)->find('div[class=dip_rojo]');
    $numElem=count($listaDatos);

    //Obtenemos fecha de alta, que tienen todos los diputados
    $fechaCad=sinTNS($listaDatos[0]->plaintext);
    if (preg_match("/\d+\/\d+\/\d+/",$fechaCad,$coinc)!==0)
        $datos["fecha_alta"]=$coinc[0];  

    // Si hay 1 o 2 elem en la lista --> El diputado está activo. Si hay 3 o 4 --> Causó baja
    // Si hay 2 elementos --> Sustituyó a otro diputado
    // Si hay 3 elementos --> Tiene sustituto
    // Si hay 4 elementos --> Tiene sustituto y sustituyó a otro diputado
    $numElem=count($listaDatos);
    if($numElem<3){
        $datos["activo"]=1;
        if($numElem===2){
            $sustURL=$listaDatos[1]->find('a',0)->href;
            $datos["id_sustituido"]=obtenerIdDipuURL($sustURL);
        }
    }else{
        $datos["activo"]=0;
        if($numElem==3){
            $fechaCad=sinTNS($listaDatos[1]->plaintext);
            if (preg_match("/\d+\/\d+\/\d+/",$fechaCad,$coinc)!==0) 
                $datos["fecha_baja"]=$coinc[0];
            $sustURL=$listaDatos[2]->find('a',0)->href;  
            $datos["id_sustituto"]=obtenerIdDipuURL($sustURL);
        }else{
            $sustURL=$listaDatos[1]->find('a',0)->href;
            $datos["id_sustituido"]=obtenerIdDipuURL($sustURL);
            $fechaCad=sinTNS($listaDatos[2]->plaintext);
            if (preg_match("/\d+\/\d+\/\d+/",$fechaCad,$coinc)!==0)
                $datos["fecha_baja"]=$coinc[0];
            $sustURL=$listaDatos[3]->find('a',0)->href;
            $datos["id_sustituto"]=obtenerIdDipuURL($sustURL);
        }
    }
    return $datos;
}


function obtenerGP($html){
	$gp=sinTNS($html->find('div[class=texto_dip]',0)->find('div[class=dip_rojo]',1)->plaintext);
    return sinSS(mb_substr(mb_substrHasta($gp,"("),5));
}

function obtenerNacimiento($html){
	$cad=sinTNS($html->find('div[class=texto_dip]',1)->find('li',0)->plaintext);
	$nac=array();
	if(strpos($cad," en ")===false){
		$lugarcad="";
		$fechacad=sinSS(str_replace(array("Nacido","Nacida", " el "),"",$cad));
	}else{
		$fechacad=sinSS(str_replace(array("Nacido","Nacida"," el "),"",substrHasta($cad," en ")));
		$lugarcad=sinSS(substr(substrDesde($cad," en "),3));
	}
	$nac["fecha"]=fechanumerica($fechacad);
	$nac["lugar"]=$lugarcad;
	return $nac;
}

function obtenerOtrasLegis($html){
	$cad=sinTNS($html->find('div[class=texto_dip]',1)->find('li',1)->plaintext);
    $legis=estandarizarLegis($cad);
    if($legis===""){
        return false;
    }else{
	    return palabrasArray($legis);
    }
}

function obtenerURLsDipu($html){
	$urls=array();
	$num=0;
	$listaURL=$html->find('div[class=webperso_dip_parte]');
	$listaRS=$html->find('div[class=webperso_dip_imagen]');
	foreach($listaURL as $div){
        $urlTag=$div->find('a',0);
        if($urlTag!==null){
            $urlCad=sinTNS($urlTag->plaintext);
            if(strpos($urlCad,'@')!==false){
				$urls[$num]["tipo"]="email";
                $urls[$num]["url"]=$urlCad;
                $urls[$num]["oficial"]=1;
                $num++;
            }elseif(strpos($urlCad,'http://')!==false){
                $urls[$num]["tipo"]="web";
                $urls[$num]["url"]=$urlCad;
                $urls[$num]["oficial"]=1;
                $num++;
            }
        }
    }
	foreach($listaRS as $div){
        $urlTag=$div->find('a',0);
        if($urlTag!==null){
            $urlCad=sinTNS($urlTag->href);
            if(strpos($urlCad,'twitter')!==false){
                $urls[$num]["tipo"]="twitter";
                $urls[$num]["url"]=$urlCad;
                $urls[$num]["oficial"]=1;
                $num++;
            }elseif(strpos($urlCad,'facebook')!==false){
                $urls[$num]["tipo"]="facebook";
                $urls[$num]["url"]=$urlCad;
                $urls[$num]["oficial"]=1;
                $num++;
            }elseif(strpos($urlCad,'linkedin')!==false){
                $urls[$num]["tipo"]="linkedin";
                $urls[$num]["url"]=$urlCad;
                $urls[$num]["oficial"]=1;
                $num++;
            }elseif(strpos($urlCad,'flickr')!==false){
                $urls[$num]["tipo"]="flickr";
                $urls[$num]["url"]=$urlCad;
                $urls[$num]["oficial"]=1;
                $num++;
            }
        }
    }
	return $urls;
}

function obtenerPosHemiciclo($html){
    $img=$html->find('p[class=pos_hemiciclo]',0)->find('img',0)->src;
    $pos_hemiciclo=mb_substr(mb_substrHasta(mb_substrDesde($img,"hemi_100_"),".gif"),8);
    return intval($pos_hemiciclo);
}

// Devuelve un array con el número de iniciativas de un diputado, clasificadas por tipo
function obtenerNumIniciativas($idDiputado,$act=0){
    $url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Diputados/BusqForm?_piref73_1333155_73_1333154_1333154.next_page=/wc/buscarIniciativasForm?tipoIniciativas=1&idDiputado=".$idDiputado."&origen=diputados&idLegislatura=10&muestraLeg=false";
	$prevHTML= getSimpleHTMLCongreso($url,$act);
	$main=$prevHTML->find('div[class=resultados_competencias]',0)->find('div[class=paginacion_brs]',0);
	$numInic=array();
	
	// Si el diputado tiene tabla de iniciativas, scrapeamos los datos de la tabla de iniciativas 
	// Si no tiene tabla de iniciativas, asignamos cero a todo
	if($main!==null){
		$url="http://www.congreso.es".$main->find('a',0)->href;
		$inicHTML=getSimpleHTMLCongreso($url);
		$lista=$inicHTML->find('div[class=resultados_encontrados]',0);
		$numInic["preg_orales"]=intval(sinSS($lista->find('li',0)->find('span',0)->plaintext));
		$numInic["preg_escritas"]=intval(sinSS($lista->find('li',1)->find('span',0)->plaintext));
		$numInic["solicit_comparecencias"]=intval(sinSS($lista->find('li',2)->find('span',0)->plaintext));
		$numInic["solicit_informes"]=intval(sinSS($lista->find('li',3)->find('span',0)->plaintext));
		$numInic["solicit_nuevo_organo"]=intval(sinSS($lista->find('li',4)->find('span',0)->plaintext));
		$numInic["total"]=intval(sinSS($lista->find('li',5)->find('span',0)->plaintext));
	}else{
		$numInic["preg_orales"]=0;
		$numInic["preg_escritas"]=0;
		$numInic["solicit_comparecencias"]=0;
		$numInic["solicit_informes"]=0;
		$numInic["solicit_nuevo_organo"]=0;
		$numInic["total"]=0;
	}
	return $numInic;
}

// Devuelve un array con el número de intervenciones de un diputado, clasificadas por tipo
function obtenerNumIntervenciones($idDiputado,$act=0){
	$sigPag="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Diputados/BusqForm?_piref73_1333155_73_1333154_1333154.next_page=/wc/buscarIntervencionesForm?idDiputado=".$idDiputado."&tipoIntervenciones=tipo&idLegislatura=10&muestraLeg=false";
	$numInterv=array();
	$numInterv["pleno"]=0;
	$numInterv["comision"]=0;
	$numInterv["dipuPerm"]=0;
	$numInterv["otras"]=0;
	$numInterv["total"]=0;
	$numrb=1;
	$pag=1;
	while($sigPag!==false){
		$html=getSimpleHTMLCongreso($sigPag,$act);
		$pleno=0;
		$comision=0;
		$otras=0;
		$dipuPerm=0;
		// Si no hay intervenciones, salimos con todo a 0
		if($html->find('div[class=paginacion_brs]',0)===null) return $numInterv;
		
		//Chequeamos si hay siguiente página de intervenciones
		if($pag==2) $numrb=0;
		$result=$html->find('div[id=RESULTADOS_BUSQUEDA]',$numrb);
		if($result->find('div[class=paginacion_brs]',1)->find('a',0)===null){
			$sigPag=false;
		}else{
			$lista=$result->find('div[class=paginacion_brs]',1)->find('a');
			$ultimo=$lista[count($lista)-1];
			if(strpos($ultimo->plaintext,"Siguiente")===false){
				$sigPag=false;
			}else{
				$sigPag="http://www.congreso.es".$ultimo->href;
			}
		}
		
		//Si hay resultados en la página, vamos procesando uno a uno
		$listaRes=$result->find('div[class=resultados_encontrados]');
		$tipoInterv="";
		foreach($listaRes as $res){
			// Si el div indica el tipo de intervención, almacenamos el tipo
			if(strpos($res->plaintext,"Intervención en")!==false){
				if(strpos($res->plaintext,"Comisión")!==false){
					$tipoInterv="comision";
				}elseif(strpos($res->plaintext,"Pleno")!==false){
					$tipoInterv="pleno";
				}elseif(strpos($res->plaintext,"Diputación")!==false){
					$tipoInterv="dipuPerm";
				}else{
					echo "<p><b> Otras con: ".$res->plaintext."</b></p>";
					$tipoInterv="otras";
				}
			}elseif(strpos($res->plaintext,"Intervención")!==false){
				//echo "<p><b>NO ENTRA CON: ". $res->plaintext ."</b></p>";
			}
			// Si el div indica una intervención individual, la sumamos teniendo en cuenta el tipo
			if(strpos($res->plaintext,"D.S.")!==false && strpos($res->plaintext,"texto íntegro")!==false && strpos($res->plaintext,"PDF")!==false){
				switch($tipoInterv){
					case "comision":
						$comision++;
						break;
					case "pleno":
						$pleno++;
						break;
					case "dipuPerm":
						$dipuPerm++;
						break;
					case "otras":
						$otras++;
						break;
					default:
						echo "<p><b>tipoInterv no está inicializado</b></p>";
						break;
				}
			}
			
		}
		// Sumamos los resultados de la página a los globales
		$totalPag=$pleno+$comision+$dipuPerm+$otras;
		//echo "<p>Pág $pag: Pleno: $pleno, Comisión: $comision, Otras: $otras, DP: $dipuPerm. Total: $totalPag.</p>";
		$numInterv["comision"] =$numInterv["comision"]+$comision;
		$numInterv["pleno"]=$numInterv["pleno"]+$pleno;
		$numInterv["dipuPerm"]=$numInterv["dipuPerm"]+$dipuPerm;
		$numInterv["otras"]=$numInterv["otras"]+$otras;
		
		$pag++;
	}
	// Almacenamos el total como la suma de todas
	$numInterv["total"]=$numInterv["comision"]+$numInterv["pleno"]+$numInterv["dipuPerm"]+$numInterv["otras"];
	
	//Código para monitorizar en tiempo de ejecución si todo va bien. Descomentar para usarlo.
	/* <p>Procemiento del <a href='$sigPag'> diputado $idDiputado</a></p> 
	echo "<p>Intervenciones totales: <b>Pleno: ".$numInterv["pleno"].", Comisión: ".$numInterv["comision"].", Otras: ".$numInterv["otras"].", DipuPerm: ".$numInterv["dipuPerm"]." Total: ".$numInterv["total"]."</b></p>";*/
	
	return $numInterv;
}

// Obtiene de la imagen de un dipu y la guarda en img/imagenesDipus/
function obtenerImagenDipu($id){
	$imgExterna="http://www.congreso.es/wc/htdocs/web/img/diputados/". $id ."_10.jpg";
	$tituloImg = "img/imagenesDipus/".$id.".jpg";
	$img=imagecreatefromjpeg($imgExterna);
	imagejpeg($img,$tituloImg);
}

// Obtiene la miniatura (48x48) de la imagen de un dipu y la guarda en img/miniaturasDipus/
function obtenerMiniaturaDipu($id){
	$imgExterna="http://www.congreso.es/wc/htdocs/web/img/diputados/".$id."_10.jpg";
	$tituloImg = "img/miniaturasDipus/".$id.".jpg";
	try{
		 $thumb = PhpThumbFactory::create($imgExterna);
	}catch (Exception $e){
		 echo "<p>Error con el diputado id= ".$id."</p>";
	}
	$dimens=$thumb->getCurrentDimensions();
	$ancho=$dimens["width"];
	$alto=$dimens["height"];
	//echo "<ul><a href='$imgExterna'>dipu".$id."</a><li>ancho: $ancho</li><li>alto: $alto</li><li>dif = $dif</li></ul>";
	$thumb->crop(0,9,$ancho,$ancho);
	$thumb->resize(48,48);
	$thumb->save($tituloImg);
}

/* Scrapea todos los cargos en el congreso de los diputados en MC, JP, DP, comisiones y subcomisiones. 
   Devuelve un array de cargos con estos campos para cada cargo:

*/
function obtenerCargosCongreso($act=0){
	$cargosCongreso=array();
	$cont=0;
	
	// Obtenemos los cargos de JP, MC y DP
	for($i=0;$i<3;$i++){
		switch($i){
			case 0: $idOrgano=100; $tipoOrgano="MC"; break; // Mesa del Congreso
			case 1: $idOrgano=300; $tipoOrgano="JP"; break; // Junta de Portavoces
			case 2: $idOrgano=500; $tipoOrgano="DP"; break; // Diputación Permanente
		}
		$url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Organos/DipuPerm?_piref73_1339250_73_1339243_1339243.next_page=/wc/composicionOrganoHistorico&idOrgano=$idOrgano&idLegislatura=10&muestraLeg1=false";
		$html = getSimpleHTMLCongreso($url,$act);
		$tabla=$html->find('div[class=listado_2]',0)->find('table',0);
		$elem=$tabla->first_child();
		while($elem!==null){
			if($elem->find("th[class=tit_granate]",0)!==null){
				$tipoCargo=tipoCargoToID(sinSS($elem->find('th',0)->plaintext));
			}elseif($elem->find("td",0)!==null && $elem->find("td",0)->find('a',0)!==null){
				$idDipu=obtenerCampoUrl($elem->find("td",0)->find('a',0)->href,"idDiputado");
				$alta=sinSS($elem->find("td",1)->plaintext);
				$baja=sinSS($elem->find("td",2)->plaintext);
				$cargosCongreso[$cont]["idDipu"]=intval($idDipu);
				$cargosCongreso[$cont]["tipoOrgano"]=$tipoOrgano;
				$cargosCongreso[$cont]["cargo"]=$tipoCargo;
				$cargosCongreso[$cont]["alta"]=$alta;
                if($baja!=="") $cargosCongreso[$cont]["baja"]=$baja;
				$cont++;
				//echo "<p>IdDipu: $idDipu; cargo: $tipoCargo; idOrgano: $idOrgano; alta: $alta; baja: $baja;</p>";
			}
			$elem=$elem->next_sibling();
		}
	}
	
	$comSubcom=obtenerComSubcom($act);
	$comisiones=$comSubcom["comisiones"];
	$subcomisiones=$comSubcom["subcomisiones"];
	
	//Obtenemos los cargos de las comisiones
	foreach($comisiones as $comision){
		if($comision["id"]!==150){
			$url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Organos/Comision?_piref73_7498063_73_1339256_1339256.next_page=/wc/composicionOrganoHistorico&idOrgano=".$comision["id"]."&idLegislatura=10&muestraLeg1=false";
			$comHTML = getSimpleHTMLCongreso($url,$act);
			$tabla=$comHTML->find('div[class=listado_2]',0)->find('table',0);
			$elem=$tabla->first_child();
			while($elem!==null){
				//echo "<p>". $elem->tag ." : ". $elem->plaintext ."</p>";
				if($elem->find("th[class=tit_granate]",0)!==null){
					$tipoCargo=tipoCargoToID(sinSS($elem->find('th',0)->plaintext));
				}elseif($elem->find("td",0)!==null && $elem->find("td",0)->find('a',0)!==null){
					$idDipu=obtenerCampoUrl($elem->find("td",0)->find('a',0)->href,"idDiputado");
					$alta=sinSS($elem->find("td",1)->plaintext);
					$baja=sinSS($elem->find("td",2)->plaintext);
					$cargosCongreso[$cont]["idDipu"]=intval($idDipu);
					$cargosCongreso[$cont]["tipoOrgano"]="C";
					$cargosCongreso[$cont]["idOrgano"]=$comision["id"];
					$cargosCongreso[$cont]["cargo"]=$tipoCargo;
					$cargosCongreso[$cont]["alta"]=$alta;
					if($baja!=="") $cargosCongreso[$cont]["baja"]=$baja;
					$cont++;
				}
				$elem=$elem->next_sibling();
			}
		}else{
			// Si es la comisión 150 (Comisión Consultiva de Nombramientos), hay que introducirlos a mano (no aparece el listado):
			// El presidente de la mesa es presidente de esta comisión
			// Los portavoces titulares de la JP son portavoces de esta comisión
			// Las fechas de alta y baja suponemos que son las mismas que las de sus cargos en JP Y MC
			foreach($cargosCongreso as $cargo){
				if($cargo["tipoOrgano"]==="MC" && $cargo["cargo"]==="P"){
					//echo "<p><b>ENCONTRADO PRES MC".$cargo["idDipu"]."</b></p>";
					$cargosCongreso[$cont]["idDipu"]=intval($cargo["idDipu"]);
					$cargosCongreso[$cont]["tipoOrgano"]="C";
					$cargosCongreso[$cont]["idOrgano"]=$comision["id"];
					$cargosCongreso[$cont]["cargo"]="P";
					$cargosCongreso[$cont]["alta"]=$cargo["alta"];
					if($baja!=="") $cargosCongreso[$cont]["baja"]=$cargo["baja"];
					$cont++;
				}elseif($cargo["tipoOrgano"]==="JP" && $cargo["cargo"]==="PT"){
					//echo "<p><b>ENCONTRADO PORT JP".$cargo["idDipu"]."</b></p>";
					$cargosCongreso[$cont]["idDipu"]=intval($cargo["idDipu"]);
					$cargosCongreso[$cont]["tipoOrgano"]="C";
					$cargosCongreso[$cont]["idOrgano"]=$comision["id"];
					$cargosCongreso[$cont]["cargo"]="PO";
					$cargosCongreso[$cont]["alta"]=$cargo["alta"];
					if($baja!=="") $cargosCongreso[$cont]["baja"]=$cargo["baja"];
					$cont++;
				}
			}
		}
	}
	
	//Obtenemos los cargos de las subcomisiones
	foreach($subcomisiones as $subcomision){
		$url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Organos/Comision?_piref73_7498063_73_1339256_1339256.next_page=/wc/composicionOrganoHistorico&idOrgano=".$subcomision["id"]."&idLegislatura=10&muestraLeg1=false";
		$comHTML = getSimpleHTMLCongreso($url,$act);
		$tabla=$comHTML->find('div[class=listado_2]',0)->find('table',0);
		$elem=$tabla->first_child();
		while($elem!==null){
			if($elem->find("th[class=tit_granate]",0)!==null){
				$tipoCargo=tipoCargoToID(sinSS($elem->find('th',0)->plaintext));
			}elseif($elem->find("td",0)!==null && $elem->find("td",0)->find('a',0)!==null){
				$idDipu=obtenerCampoUrl($elem->find("td",0)->find('a',0)->href,"idDiputado");
				$alta=sinSS($elem->find("td",1)->plaintext);
				$baja=sinSS($elem->find("td",2)->plaintext);
				$cargosCongreso[$cont]["idDipu"]=intval($idDipu);
				$cargosCongreso[$cont]["tipoOrgano"]="SC";
				$cargosCongreso[$cont]["idOrgano"]=$subcomision["id"];
				$cargosCongreso[$cont]["cargo"]=$tipoCargo;
				$cargosCongreso[$cont]["alta"]=$alta;
				if($baja!=="") $cargosCongreso[$cont]["baja"]=$baja;
				$cont++;
				//echo "<p>IdDipu: $idDipu; idOrgano: ".$subcomision["id"]."; cargo: $tipoCargo; alta: $alta; baja: $baja;</p>";
			}
			$elem=$elem->next_sibling();
		}
	}
	return $cargosCongreso;
}

/* Scrapea la información básica de las comisiones y subcomisiones en el congreso. Devuelve un array con:
	"comisiones": array(
		"id" : int (la id de comisión que usa congreso.es)
		"pre" : string (Prefijo en el nombre de la comisión)
		"nombre" : string (nombre sin prefijo, ejemplo: Constitucional)
		"legis" : int (0 si no es legislativa, 1 si es legistlativa)
		"perm" : int (0 si no es permanente, 1 si es permanente)
		"mixta" : int (0 si no es permanente, 1 si es permanente)
	)
	"subcomisiones" : array() (Mismos campos que 'comisiones' sin 'pre', ya que las subcomisiones nunca tienen prefijo)
*/
function obtenerComSubcom($act=0){
	$info=array();
	$com=array();
	$subCom=array();
	$url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Organos/Comision";
	$comHTML = getSimpleHTMLCongreso($url,$act);
	$num=0;
	for($i=0;$i<4;$i++){
		$lista=$comHTML->find('div[class=listado_1_comisiones]',$i)->find('li');
		if($lista !== null){
			foreach ($lista as $comElem){
				$organo_=sinSS($comElem->plaintext);
				$organo=estandarizarOrgano($organo_);
				if (strpos($organo, "Comisión") !== false){
					$com[$num]["id"]=obtenerIdDipuUrl($comElem->find('a',0)->href);
					$com[$num]["pre"]=prefijoComision($organo);
					$com[$num]["nombre"]=nombreCom($organo);
					switch($i){
						case 0: $com[$num]["legis"]=1;$com[$num]["perm"]=1;$com[$num]["mixta"]=0;break;
						case 1: $com[$num]["legis"]=0;$com[$num]["perm"]=1;$com[$num]["mixta"]=0;break;
						case 2: $com[$num]["legis"]=0;$com[$num]["perm"]=0;$com[$num]["mixta"]=0;break;
						case 3: $com[$num]["legis"]=0;$com[$num]["perm"]=1;$com[$num]["mixta"]=1;break;
					}
					$num++;
				}
			}
		}else{
			echo "<p>Error al obtener la lista de comisiones $i/4</p>";
		}
	}
	$num=0;
	for($i=0;$i<4;$i++){
		$lista=$comHTML->find('div[class=listado_1_comisiones]',$i)->find('span');
		if($lista !== null){
			foreach ($lista as $comElem){
				$organo_=sinSS($comElem->plaintext);
				$organo=estandarizarOrgano($organo_);
				if (strpos($organo, "Subcomisión") !== false){
					$subCom[$num]["id"]=obtenerIdDipuUrl($comElem->find('a',0)->href);
					$subCom[$num]["nombre"]=nombreSubcom($organo);
					switch($i){
						case 0: $subCom[$num]["legis"]=1;$subCom[$num]["perm"]=1;$subCom[$num]["mixta"]=0;break;
						case 1: $subCom[$num]["legis"]=0;$subCom[$num]["perm"]=1;$subCom[$num]["mixta"]=0;break;
						case 2: $subCom[$num]["legis"]=0;$subCom[$num]["perm"]=0;$subCom[$num]["mixta"]=0;break;
						case 3: $subCom[$num]["legis"]=0;$subCom[$num]["perm"]=1;$subCom[$num]["mixta"]=1;break;
					}
					$num++;
				}
			}
		}else{
			echo "<p>Error al obtener la lista de subcomisiones $i/4</p>";
		}
	}
	$info["comisiones"]=$com;
	$info["subcomisiones"]=$subCom;
	return $info;
}

// A la espera de que congreso.es arregle la URL
function obtenerIniciativasGrupos(){
	$iniciativasGP=array();
	$inicGP[0]["nombreGP"]="Popular"; $inicGP[0]["inic"]=array();
	$inicGP[1]["nombreGP"]="Socialista"; $inicGP[1]["inic"]=array();
	$inicGP[2]["nombreGP"]="Catalán (CiU)"; $inicGP[2]["inic"]=array();
	$inicGP[3]["nombreGP"]="Izquierda Plural"; $inicGP[3]["inic"]=array();
	$inicGP[4]["nombreGP"]="UPyD"; $inicGP[4]["inic"]=array();
	$inicGP[5]["nombreGP"]="Vasco (EAJ-PNV)"; $inicGP[5]["inic"]=array();
	$inicGP[6]["nombreGP"]="Mixto"; $inicGP[6]["inic"]=array();
	
	for($i=0;$i<count($inicGP);$i++){
		$idGrupo=200+$i+1;
        $url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Iniciativas?_piref73_2148295_73_1335437_1335437.next_page=/wc/servidorCGI&CMD=VERLST&BASE=IW10&FMT=INITXLUS.fmt&NUM1=&DES1=&DOCS=1-25&DOCORDER=FIFO&OPDEF=Y&QUERY=%28I%29.ACIN1.+%26+%28".$idGrupo."+G%29.SAUT.";
	    
		echo "url = '$url'</p>";
		$html=getSimpleHTMLCongreso($url);
		$check=$html->find("div[class=SUBTITULO_CONTENIDO_INICIATIVAS]",0);
		if($check!==null){
			$inicGP[$i]["inic"]=sinTNS($check->find("span",0)->plaintext);
		}else{
			echo "Error: ".$html->find('div[class=titulo_contenido',0)->plaintext;
			echo "Error: ". $html->find("body",0)->plaintext;
			$inicGP[$i]["inic"]=0;
		}
	}
	return $inicGP;
}

function mostrarDiputadoScrap($dipu){
	$i=$dipu["id"];
	$url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Diputados/BusqForm?_piref73_1333155_73_1333154_1333154.next_page=/wc/fichaDiputado?idDiputado=$i&idLegislatura=10";
	//$img="http://www.congreso.es/wc/htdocs/web/img/diputados/".$i."_10.jpg";
	echo "<h2><a href='$url'>".$dipu["nombre"]." ".$dipu["apellidos"]."</a>  (".$dipu["partido"].", ".$dipu["circunscripcion"].")</h2><ul>";
	foreach($dipu as $c => $v){
		if(gettype($v) === "array"){
			echo "<li>$c:";
			verArray($v);
			echo "</li>";
		}elseif($c!=="nombre" && $c!=="apellidos" && $c!=="partido" && $c!=="circunscripcion" && $v!==false && $v!==""){
			echo "<li>$c = $v</li>";
		}
	}
	echo "</ul>";
}

// Scrapea los datos básicos de todas las comisiones y subcomisiones del congreso y los compone en 'comisiones.xml'
// Su base se puede usar para guardar (y actualizar) en la BD los datos de estos órganos 
function generarXMLComisiones(){
	$url="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Organos/Comision";
	$comHTML = getSimpleHTMLCongreso($url);
	
	$atributos=array();
	$atributos[0]=" legis='1' perm='1' mixta='0' ";
	$atributos[1]=" legis='0' perm='1' mixta='0' ";
	$atributos[2]=" legis='0' perm='0' mixta='0' ";
	$atributos[3]=" legis='0' perm='1' mixta='1' ";
	
	$xml='<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
	$xml=$xml."<Organos>".PHP_EOL."<Comisiones>".PHP_EOL;
	for($i=0;$i<4;$i++){
		$lista=$comHTML->find('div[class=listado_1_comisiones]',$i)->find('li');
		if($lista !== null){
			foreach ($lista as $comElem){
				$organo_=sinSS($comElem->plaintext);
				$organo=estandarizarOrgano($organo_);
				if (strpos($organo, "Comisión") !== false){
					$pre=prefijoComision($organo);
					$organo=nombreCom($organo);
					$xml=$xml."\t<Comision ".$atributos[$i]." pre='$pre' >$organo</Comision>".PHP_EOL;
				}
			}
		}else{
			echo "<p>Error al obtener la lista de comisiones $i/4</p>";
		}
	}
	$xml=$xml."</Comisiones>".PHP_EOL;
	$xml=$xml."<Subcomisiones>".PHP_EOL;
	for($i=0;$i<4;$i++){
		$lista=$comHTML->find('div[class=listado_1_comisiones]',$i)->find('span');
		if($lista !== null){
			foreach ($lista as $comElem){
				$organo_=sinSS($comElem->plaintext);
				$organo=estandarizarOrgano($organo_);
				if (strpos($organo, "Subcomisión") !== false){
					$organo=nombreSubcom($organo);
					$xml=$xml."\t<Subcomision".$atributos[$i].">$organo</Subcomision>".PHP_EOL;
				}
			}
		}else{
			echo "<p>Error al obtener la lista de subcomisiones $i/4</p>";
		}
	}
	$xml=$xml."</Subcomisiones>".PHP_EOL."</Organos>";
	
	$fd = fopen("comisiones.xml","w+");
	if ($fd!==false){
		if (fwrite($fd,$xml) === false){
			echo "<p>No se pudo escribir en el archivo</p>";
		}
	}else{
		echo "<p>No se pudo crear el archivo</p>";
	}
	fclose($fd);
}


//		[PROCESAMIENTO DE DATOS]

// Devuelve las legislaturas de un diputado en una cadena con sus números en romano separados por espacios
function estandarizarLegis($cad){
    $patron="/[E-Z]+/";
    $legis="";
    preg_match_all($patron,$cad,$matches);
    for($i=0;$i<count($matches[0]);$i++){
        if($matches[0][$i]!=="X"){
            $legis=$legis.$matches[0][$i]." ";
        }
    }
    return $legis;
}

// Devuelve el ID de un cargo a partir de su nombre
function tipoCargoToID($cargo){
	if(strpos($cargo,"President")!==false){
		return "P";
	}elseif(strpos($cargo,"Vicepresident")!==false && strpos($cargo,"Primer")!==false ){
		return "VP1";
	}elseif(strpos($cargo,"Vicepresident")!==false && strpos($cargo,"Segund")!==false){
		return "VP2";
	}elseif(strpos($cargo,"Vicepresident")!==false && strpos($cargo,"Tercer")!==false){
		return "VP3";
	}elseif(strpos($cargo,"Vicepresident")!==false && strpos($cargo,"Cuart")!==false){
		return "VP4";
	}elseif(strpos($cargo,"Vicepresident")!==false){
		return "VP";
	}elseif(strpos($cargo,"Secretari")!==false && strpos($cargo,"Primer")!==false){
		return "S1";
	}elseif(strpos($cargo,"Secretari")!==false && strpos($cargo,"Segund")!==false){
		return "S2";
	}elseif(strpos($cargo,"Secretari")!==false && strpos($cargo,"Tercer")!==false){
		return "S3";
	}elseif(strpos($cargo,"Secretari")!==false && strpos($cargo,"Cuart")!==false){
		return "S4";
	}elseif(strpos($cargo,"Secretari")!==false){
		return "S";
	}elseif(strpos($cargo,"Portavo")!==false && strpos($cargo,"Titular")!==false){
		return "POT";
	}elseif(strpos($cargo,"Portavo")!==false && strpos($cargo,"Sustitut")!==false){
		return "POS";
	}elseif(strpos($cargo,"Portavo")!==false && strpos($cargo,"adjunt")!==false){
		return "POA";
	}elseif(strpos($cargo,"Portavo")!==false){
		return "PO";
	}elseif(strpos($cargo,"Vocal")!==false && strpos($cargo,"Suplent")!==false){
		return "VS";
	}elseif(strpos($cargo,"Vocal")!==false){
		return "V";
	}elseif(strpos($cargo,"Adscrit")!==false){
		return "A";
	}else{
		return false;
	}
}

// Devuelve el cargo como cadena de caracteres, a partir del ID del cargo y del sexo del diputado
function nombreCargo($cargo,$sexo){
	$sex="e";$sex2="o";
	if($sexo==="M"){ $sex="a"; $sex2="a"; }
	switch($cargo){
		case "P": return "President$sex";
		case "VP": return "Vicepresident$sex";
		case "VP1": return "Vicepresident$sex Primer$sex2";
		case "VP2": return "Vicepresident$sex Segund$sex2";
		case "VP3": return "Vicepresident$sex Tercer$sex2";
		case "VP4": return "Vicepresident$sex Cuart$sex2";
		case "S": return "Secretari$sex2";
		case "S1": return "Secretari$sex2 Primer$sex2";
		case "S2": return "Secretari$sex2 Segund$sex2";
		case "S3": return "Secretari$sex2 Tercer$sex2";
		case "S4": return "Secretari$sex2 Cuart$sex2";
		case "PO": return "Portavoz";
		case "POT": return "Portavoz Titular";
		case "POS": return "Portavoz Sustitut$sex2";
		case "POA": return "Portavoz Adjunt$sex2";
		case "V": return "Vocal";
		case "VS": return "Vocal Suplente";
		case "A": return "Adscrit$sex2";
		default: return false;
	}
}

// Devuelve true si el diputado está en $tipoOrgano, false si no
function enTipoOrgano($cargos,$tipoOrgano){
	foreach($cargos as $cargo){
		if($cargo["tipoOrgano"]===$tipoOrgano && !isset($cargo["baja"])) return true;
	}
	return false;
}


// Devuelve true si el diputado tiene el cargo $cargo en un órgano $tipoOrgano
function tieneCargoCongreso($cargos,$tipoOrgano,$cargo){
	foreach($cargos as $cargoi){
		if($cargoi["tipoOrgano"]===$tipoOrgano && $cargoi["cargo"]===$cargo  && !isset($cargoi["baja"])) return true;
	}
	return false;
}

// Funciones para saber si un diputado está o no en MC y JP 
function enMC($cargos){ return enTipoOrgano($cargos,"MC");}
function enJP($cargos){ return enTipoOrgano($cargos,"JP");}

// Funciones apra saber si un diputado tiene el cargo $cargo en MC, JP o Comisión
function tieneCargoMC($cargos,$cargo){ return tieneCargoCongreso($cargos,"MC",$cargo);}
function tieneCargoJP($cargos,$cargo){ return tieneCargoCongreso($cargos,"JP",$cargo);}
function tieneCargoComision($cargos,$cargo){ return tieneCargoCongreso($cargos,"C",$cargo);}

// Devuelve el $cargo del diputado en la MC, o false si no está en MC
function cargoEnMC($cargos){
	foreach($cargos as $cargo){
        if($cargo["tipoOrgano"]==="MC" && !isset($cargo["baja"])) 
            return $cargo["cargo"];
	}
	return false;
}

// Devuelve el $cargo del diputado en la JP, o false si no está en JP
function cargoEnJP($cargos){
	foreach($cargos as $cargo){
		if($cargo["tipoOrgano"]==="JP" && !isset($cargo["baja"])) return $cargo["cargo"];
	}
	return false;
}

// Devuelve el cargo en comisión del diputado con mayor remuneración, o false si no tiene cargos en comisión con remuneración
function mayorCargoEnCom($cargos){
	$ordenCargos=array("P","VP","VP1","VP2","VP3","VP4","PO","S","S1","S2","S3","S4","POA");
	for($i=0;$i<count($ordenCargos);$i++){
		if(tieneCargoComision($cargos,$ordenCargos[$i])!==false) return $ordenCargos[$i];
	}
	return false;
}

// Completa palabras incompletas de órganos en congreso.es
function estandarizarOrgano($organo){
	$abrev=array("Parlam.","Seguimto.","Concil.","Corresponsab.");
	$completas=array("Parlamentario","Seguimiento","Conciliación","Corresponsabilidad");
	$correcto= ucfirst(sinSS(str_replace($abrev,$completas,$organo)));
	return $correcto;
}

/* Devuelve el prefijo de una comisión, para poder almacenarlo aparte y poder presentar una comisión de las dos formas:
	- Sólo con su nombre. Ejemplo: Agricultura y Pesca
	- Título completo: Comisión de Agricultura y Pesca */
function prefijoComision($organo){
	if (strpos($organo, "Comisión del") !== false){
		$pre="del";
	}else if (strpos($organo, "Comisión sobre") !== false){
		$pre="sobre";
	}else if (strpos($organo, "Comisión de") !== false){
		$pre="de";
	}else if (strpos($organo, "Comisión para el") !== false){
		$pre="para el";
	}else if (strpos($organo, "Comisión para las") !== false){
		$pre="para las";
	}else if (strpos($organo, "Comisión Mixta para las") !== false){
		$pre="para las";
	}else if (strpos($organo, "Comisión Mixta para la") !== false){
		$pre="para la";
	}else if (strpos($organo, "Comisión Mixta de") !== false){
		$pre="de";
	}else if (strpos($organo, "Comisión Mixta para el") !== false){
		$pre="para el";
	}else if (strpos($organo, "Comisión Mixta") !== false){
		$pre="";
	}else{
		$pre="";
	}
	return $pre;
}

//Devuelve sólo el nombre de la comisión, sin prefijo
function nombreCom($organo){
	if (strpos($organo, "Comisión del") !== false){
		$com=sinSS(substr($organo,14));
	}else if (strpos($organo, "Comisión sobre") !== false){
		$com=sinSS(substr($organo,15));
	}else if (strpos($organo, "Comisión de") !== false){
		$com=sinSS(substr($organo,13));
	}else if (strpos($organo, "Comisión para el") !== false){
		$com=sinSS(substr($organo,17));
	}else if (strpos($organo, "Comisión para las") !== false){
		$com=sinSS(substr($organo,18));
	}else if (strpos($organo, "Comisión Mixta para las") !== false){
		$com=sinSS(substr($organo,24));
	}else if (strpos($organo, "Comisión Mixta para la") !== false){
		$com=sinSS(substr($organo,23));
	}else if (strpos($organo, "Comisión Mixta de") !== false){
		$com=sinSS(substr($organo,18));
	}else if (strpos($organo, "Comisión Mixta para el") !== false){
		$com=sinSS(substr($organo,23));
	}else if (strpos($organo, "Comisión Mixta") !== false){
		$com=sinSS(substr($organo,15));
	}else{
		$com=sinSS(substr($organo,9));
	}
	return $com;
}

function nombreSubcom($organo){
	$subcom=ucfirst(sinSS(substr($organo,12)));
	if(strpos($subcom,"(") !== false) $subcom=sinSS(substrHasta($subcom,"("));
	return $subcom;
}

// Fusiona los datos del scrapeo de un diputado $dipu con los datos del diputado en el csv 
function completarDiputado($dipu,$dipusCsv){
	// Buscamos al diputado en $dipusCsv por su ID
	$i=0; $encontrado=false;
	while($i<count($dipusCsv) && $encontrado===false){
		if($dipu["id"]===$dipusCsv[$i]["id"]){
			$encontrado=true;
		}else{
			$i++;
		}
	}
	
	// Si no está el id de diputado en los csvs, informamos por pantalla y salimos
	if($encontrado===false){
		echo "<p><b>Diputado ".$dipu["id"]." NO ENCONTRADO en los CSVs</b></p>";
		return $dipu;
	}

	//Añadimos los valores que nunca se scrapean
    if($dipusCsv[$i]["parentescoRel"]!=="")
        $dipu["relaciones"]=$dipusCsv[$i]["parentescoRel"];
	$dipu["tuvo_cargos_publicos"]=$dipusCsv[$i]["cargosPublicos"];
    $dipu["estuvo_empresas_priv"]=$dipusCsv[$i]["empresasPriv"];
    if(count($dipusCsv[$i]["estudios"])>0)   
        $dipu["estudios"]=$dipusCsv[$i]["estudios"];
    if(count($dipusCsv[$i]["trayectoria"])>0)
        $dipu["trayectoria"]=$dipusCsv[$i]["trayectoria"];
    if($dipusCsv[$i]["cargos_partido"]!==false)
        $dipu["cargos_partido"]=$dipusCsv[$i]["cargos_partido"];
    if($dipusCsv[$i]["cargos_gobierno"]!==false)
        $dipu["cargos_gobierno"]=$dipusCsv[$i]["cargos_gobierno"];
    if($dipusCsv[$i]["retencion_irpf"]!=="")
        $dipu["retencion_irpf"]=$dipusCsv[$i]["retencion_irpf"];
    if($dipusCsv[$i]["url_nomina"]!=="")
        $dipu["url_nomina"]=$dipusCsv[$i]["url_nomina"];
	
	// Añadimos 'lugarnac' si no se scrapeó (no lo tiene en su ficha) y mejoramos 'partido' para Izq. Plural
    if($dipu["lugar_nac"]===""){
        if($dipusCsv[$i]["lugarnac"]!==""){ 
            $dipu["lugar_nac"]=$dipusCsv[$i]["lugarnac"];
        }else{
            unset($dipu["lugar_nac"]);
        }
    }
    if($dipusCsv[$i]["partido"]!=="" && $dipu["partido"]==="IZQ-PLU")
        $dipu["partido"]=$dipusCsv[$i]["partido"];
	
	$urls=$dipu["contacto"];
	$urls=incluirURLsNoOficiales($urls,$dipuCsv);

    if(count($urls)>0){
	    $dipu["contacto"]=$urls;
    }else{
        unset($dipu["contacto"]);
    }
	return $dipu;
}

// Busca en $cargos los que sean del diputado $dipu y se los añade.
// A partir de sus cargos, genera el sueldo del diputado y se lo añade.
function meterCargosSueldo($dipu,$cargos){
	$idDipu=$dipu["id"];
	$cargosDipu=array();
	$cont=0;
	foreach($cargos as $cargo){
		if($cargo["idDipu"]===$idDipu){
			foreach($cargo as $campo => $valor){
				if($campo!=="idDipu") $cargosDipu[$cont][$campo]=$valor;
			}
			$cont++;
		}
	}
    $dipu["cargos_congreso"]=$cargosDipu;
    $reten=false;
    $cargosGob=false;
    if(isset($dipu["retencion_irpf"])) $reten=$dipu["retencion_irpf"];
    if(isset($dipu["cargos_gobierno"])) $cargosGob=$dipu["cargos_gobierno"];  
	$dipu["sueldo"]=obtenerSueldo($dipu["sexo"],$dipu["circunscripcion"],$dipu["grupo"],$cargosDipu,$cargosGob,$reten);
	
	return $dipu;
}

/* Función que calcula el sueldo del diputado y lo desglosa. Devuelve el siguiente array:
	  "bruto_mes": float, (Sueldo bruto mensual)
	  "neto_mes": float, (Sueldo neto mensual, -1 si no se sabe)
	  "retencion": float, (-1 si no se sabe)
	  "desglose": array[]{
			"concepto": string
			"cantidad": float
			"tributa": int (0 ó 1)
			}
	Conflictos: Ante las dudas que no nos aclara congreso.es, la función opta por lo siguiente (se puede cambiar):
		- Los miembros de la Mesa no cobran por cargo en comisión.
		- Los miembros de la JP sí cobran por cargo en comisión (ver nómina de Rosa Díez).
		- Los portavoces adjuntos en JP cobran siempre (es lo que aparece en el pdf de cognreso.es. Aunque sabemos que esto no 
			es siempre así por lo que nos dijo Yuste, no hay forma de saber ahora mismo quién cobra y quién no de cada grupo).
*/
function obtenerSueldo($sexo,$provincia,$gp,$cargos,$cargosGob,$retIRPF){
	$sueldo=array();
	$sueldo["desglose"]=array();
	$enMesa=enMC($cargos);
	$enJP=enJP($cargos);
	$cobraGob=false;

	// Comprobamos si tiene cargo en el gobierno con remuneración
	// Un poco cutre la búsqueda (juntando todos los cargos y comparando su cadena), mejorable
	if($cargosGob!==false){
		$cargoGob="";
		for($i=0;$i<count($cargosGob);$i++) $cargoGob=$cargoGob." ".$cargosGob[$i]["cargo"];
		$sex="e";
		if($sexo==="M") $sex="a";
		$cobraGob=true;
		if(strpos($cargoGob,"President")!==false && strpos($cargoGob,"Consejo Económico y Social")!==false){
			$sueldo["desglose"][1]["cantidad"]=7083.69;
            $sueldo["desglose"][1]["concepto"]="President$sex del Consejo Económico y Social";
            $sueldo["desglose"][1]["tributa"]=1;
		}else if(strpos($cargoGob,"Presidente del Gobierno")!==false){
			$sueldo["desglose"][1]["cantidad"]=6515.42;
            $sueldo["desglose"][1]["concepto"]="President$sex del Gobierno";
            $sueldo["desglose"][1]["tributa"]=1;
		}else if(strpos($cargoGob,"President")!==false && strpos($cargoGob,"Consejo de Estado")!==false){
			$sueldo["desglose"][1]["cantidad"]=6484.08;
			$sueldo["desglose"][1]["concepto"]="President$sex del Consejo de Estado";
            $sueldo["desglose"][1]["tributa"]=1;
        }else if(strpos($cargoGob,"Vicepresident")!==false && strpos($cargoGob,"Gobierno")!==false){
			$sueldo["desglose"][1]["cantidad"]=6123.86;
			$sueldo["desglose"][1]["concepto"]="Vicepresident$sex del Gobierno";
            $sueldo["desglose"][1]["tributa"]=1;
        }else if(strpos($cargoGob,"Ministr")!==false){
			if($sexo==="H") $sex="o";
			$sueldo["desglose"][1]["cantidad"]=5748.49;
			$sueldo["desglose"][1]["concepto"]="Ministr$sex del Gobierno";
            $sueldo["desglose"][1]["tributa"]=1;
        }else if(strpos($cargoGob,"Secretari")!==false && strpos($cargoGob,"Estado")!==false){
			if($sexo==="H") $sex="o";
			$sueldo["desglose"][1]["cantidad"]=5587.9425;
			$sueldo["desglose"][1]["concepto"]="Secretari$sex de Estado";
            $sueldo["desglose"][1]["tributa"]=1;
        }else if(strpos($cargoGob,"Subsecretari")!==false && strpos($cargoGob,"Estado")!==false){
			if($sexo==="H") $sex="o";
			$sueldo["desglose"][1]["cantidad"]=4954.2825;
			$sueldo["desglose"][1]["concepto"]="Subsecretari$sex de Estado";
            $sueldo["desglose"][1]["tributa"]=1;
        }else if(strpos($cargoGob,"Director")!==false && strpos($cargoGob,"General del Estado")!==false){
			if($sexo==="H") $sex="";
			$sueldo["desglose"][1]["cantidad"]=4236.0275;
			$sueldo["desglose"][1]["concepto"]="Director$sex General del Estado";
            $sueldo["desglose"][1]["tributa"]=1;
        }else{
			$cobraGob=false;
		}
		// Si cobra del gobierno, del Congreso sólo cobra la indemnización según circunscripción
		if($cobraGob===true){ 
			$sueldo["desglose"][1]["tributa"]=1;
			if ($provincia==="Madrid"){
				$sueldo["desglose"][0]["cantidad"]=870.56;
				$sueldo["desglose"][0]["concepto"]="Indemnización para los diputados electos por Madrid";
			}else{
				$sueldo["desglose"][0]["cantidad"]=1823.86;
				$sueldo["desglose"][0]["concepto"]="Indemnización para los diputados no electos por Madrid";
			}
			$sueldo["desglose"][0]["tributa"]=0;
		}
	}
	
	// Si no cobran por cargos del Gobierno, miramos los demás cargos (comisiones, JP y MC)
	// Lo primero, asignamos lo que cobran como diputados
	if ($cobraGob === false){
		$sueldo["desglose"][0]["cantidad"]=2813.87;
		$sueldo["desglose"][0]["concepto"]="Asignación constitucional para todos los diputados";
		$sueldo["desglose"][0]["tributa"]=1;
		if ($provincia==="Madrid"){
			$sueldo["desglose"][1]["cantidad"]=870.56;
			$sueldo["desglose"][1]["concepto"]="Indemnización para los diputados electos por Madrid";
			$sueldo["desglose"][1]["tributa"]=0;
		}else{
			$sueldo["desglose"][1]["cantidad"]=1823.86;
			$sueldo["desglose"][1]["concepto"]="Indemnización para los diputados no electos por Madrid";
			$sueldo["desglose"][1]["tributa"]=0;
		}
		
		// Comprobamos si cobra por cargos en MC o JP
		if ($enMesa!==false){
			$cargoMesa=cargoEnMC($cargos);
			if($cargoMesa==="P"){
				$sueldo["desglose"][2]["cantidad"]=9121.03;
			}elseif(strpos($cargoMesa,"VP")!==false){
				$sueldo["desglose"][2]["cantidad"]=2927.53;
			}else{
				$sueldo["desglose"][2]["cantidad"]=2440.3;
			}
            $sueldo["desglose"][2]["concepto"]=nombreCargo($cargoMesa,$sexo)." del Congreso";
            $sueldo["desglose"][2]["tributa"]=1;
		}elseif($enJP!==false){
			$cargoJP=cargoEnJP($cargos);
			if($cargoJP==="POT"){
				$sueldo["desglose"][2]["cantidad"]=2667.5;
			}elseif($cargoJP==="POS"){
				$sueldo["desglose"][2]["cantidad"]=2087.07;
			}
			$sueldo["desglose"][2]["concepto"]=nombreCargo($cargoJP,$sexo)." del Grupo Parlamentario ".$gp;
			$sueldo["desglose"][2]["tributa"]=1;
		}

		//Si no cobra por cargo en MC, comprobamos si cobra por cargos en Comisión
		$cargoCom=mayorCargoEnCom($cargos);
		$num=count($sueldo["desglose"]);
		if ($cargoCom!==false && $enMesa===false){
			if($cargoCom==="P"){
				$sueldo["desglose"][$num]["cantidad"]=1431.31;
			}elseif(strpos($cargoCom,"VP")!==false){
				$sueldo["desglose"][$num]["cantidad"]=1046.48;
			}elseif($cargoCom==="PO"){
				$sueldo["desglose"][$num]["cantidad"]=1046.48;
			}elseif($cargoCom==="S" || $cargoCom==="S1" || $cargoCom==="S2" || $cargoCom==="S3" || $cargoCom==="S4"){
				$sueldo["desglose"][$num]["cantidad"]=697.65;
			}elseif($cargoCom==="POA"){
				$sueldo["desglose"][$num]["cantidad"]=697.65;
			}
			$sueldo["desglose"][$num]["concepto"]=nombreCargo($cargoCom,$sexo)." de Comisión";
			$sueldo["desglose"][$num]["tributa"]=1;
		}
		/* CÓDIGO PARA USAR SI LOS MIEMBROS DE LA MESA COBRAN POR COMISIÓN QUE NO SEA REGLAMENTO (312) o CONSULTIVA DE NOMBRAMIENTOS (150) [CONGRESO.ES NO LO DEJA CLARO]
		elseif($cargoCom!==false && $enMesa!==false){
			// Seleccionamos los cargos en comisión que no sean Reglamento y Consultiva de Nombramientos
			cargosSin=array();
			$i=0;
			foreach($cargos as $c){
				if($c["tipoOrgano"]==="C" && c["idOrgano]!==312 && c["idOrgano]!==150){
					cargosSin[$i]=$c;
					$i++;
				}
			}
			$cargoCom=mayorCargoEnCom($cargosSin);
			if($cargoCom!==false){
				if($cargoCom==="P"){
					$sueldo["desglose"][$num]["cantidad"]=1431.31;
				}elseif(strpos($cargoCom,"VP")!==false){
					$sueldo["desglose"][$num]["cantidad"]=1046.48;
				}elseif($cargoCom==="PO"){
					$sueldo["desglose"][$num]["cantidad"]=1046.48;
				}elseif($cargoCom==="S" || $cargoCom==="S1" || $cargoCom==="S2" || $cargoCom==="S3" || $cargoCom==="S4"){
					$sueldo["desglose"][$num]["cantidad"]=697.65;
				}elseif($cargoCom==="POA"){
					$sueldo["desglose"][$num]["cantidad"]=697.65;
				}
				$sueldo["desglose"][$num]["concepto"]=nombreCargo($cargoCom,$sexo)." de Comisión";
				$sueldo["desglose"][$num]["tributa"]=1;
			}
			
		}*/
	}
	$total=0.0;
	for($i=0;$i<count($sueldo["desglose"]);$i++){
		$total=$total + $sueldo["desglose"][$i]["cantidad"];
	}
	$sueldo["bruto_mes"]=$total;
	
	// Si tenemos el porcentaje de retención, calculamos el sueldo neto mensual
	if($retIRPF!==false){
		$sueldo["retencion"]=$retIRPF;
		$totalTributa=0;
		foreach($sueldo["desglose"] as $concepto){
			if($concepto["tributa"]===1){
				$totalTributa=$totalTributa + $concepto["cantidad"];
			}
		}
		$tributado=$totalTributa*$retIRPF/100.00;
		$sueldo["neto_mes"]=truncar($sueldo["bruto_mes"]-$tributado,2);
		$sueldo["neto_mes"]=round($sueldo["bruto_mes"]-$tributado,2);
	}
	
	return $sueldo;
}

// Comprueba si diputado tiene una $url de determinado $tipo.
// Si no se pasa argumento $url, comprueba si tiene alguna url de $tipo
 function tieneURL($urls,$tipo,$url=""){
	foreach($urls as $enlace){
		if($enlace["tipo"]===$tipo){
			if($url!==""){
				if($enlace["url"]===$url) return true;
			}else{
				return true;
			}
		}
	}
	return false;
 }

// Inserta url no oficial al array $urls del diputado
 function insertarURL($urls,$tipo,$url){
	$i=count($urls);
	$urls[$i]["tipo"]=$tipo;
    $urls[$i]["url"]=$url;
    $urls[$i]["oficial"]=0;
	return $urls;
 }

 // Incluye las urls no oficiales junto a las oficiales (scrapeadas) del diputado
 function incluirURLsNoOficiales($urls,$dipuCsv){
   	// Si tiene Telefono, Google+, Utube o wikipedia en el csv --> Se lo insertamos
     if($dipuCsv["telefono"]!=="") 
         $urls=insertarURL($urls,"telefono",$dipuCsv["telefono"]);
     if($dipuCsv["google"]!=="") 
         $urls=insertarURL($urls,"google",$dipuCsv["google"]);
     if($dipuCsv["youtube"]!=="") 
         $urls=insertarURL($urls,"youtube",$dipuCsv["youtube"]);
     if($dipuCsv["wikipedia"]!=="") 
         $urls=insertarURL($urls,"wikipedia",$dipuCsv["wikipedia"]);
	
	// Si no tiene tw, fb, ldin, flickr en su ficha pero si en el csv --> Se lo añadimos
	if(tieneURL($urls,"twitter")===false && $dipuCsv["twitter"]!=="") 
		$urls=insertarURL($urls,"twitter",$dipuCsv["twitter"]);
	if(tieneURL($urls,"facebook")===false && $dipuCsv["facebook"]!=="") 
		$urls=insertarURL($urls,"facebook",$dipuCsv["facebook"]);
	if(tieneURL($urls,"linkedin")===false && $dipuCsv["linkedin"]!=="") 
		$urls=insertarURL($urls,"linkedin",$dipuCsv["linkedin"]);
	if(tieneURL($urls,"flickr")===false && $dipuCsv["flickr"]!=="") 
        $urls=insertarURL($urls,"flickr",$dipuCsv["flickr"]);

    // Comparamos correos y webs (scrapeo VS csv). Insertamos los que estén en el csv y no en su ficha.
	if($dipuCsv["email"]!=="" && tieneURL($urls,"email",$dipuCsv["email"])===false) 
		$urls=insertarURL($urls,"email",$dipuCsv["email"]);
	if($dipuCsv["email2"]!=="" && tieneURL($urls,"email",$dipuCsv["email2"])===false) 
		$urls=insertarURL($urls,"email",$dipuCsv["email2"]);
	if($dipuCsv["blog"]!=="" && tieneURL($urls,"web",$dipuCsv["blog"])===false) 
		$urls=insertarURL($urls,"web",$dipuCsv["blog"]);
	if($dipuCsv["blog2"]!=="" && tieneURL($urls,"web",$dipuCsv["blog2"])===false) 
		$urls=insertarURL($urls,"web",$dipuCsv["blog2"]);
	if($dipuCsv["web"]!=="" && tieneURL($urls,"web",$dipuCsv["web"])===false) 
        $urls=insertarURL($urls,"web",$dipuCsv["web"]);

    return $urls;
 }
 	
	
//		[FUNCIONES DE CADENAS, INTERNAS Y OTROS]

// Divide en un array todas las subcadenas que están separadas por un punto
// Mejora: Separar frases por subcadena pasada (para que no tenga que ser siempre punto)
function separarFrases($contenido){
	if ($contenido == ""){
		return false;
	}
	if( (strrpos($contenido,'.') === false ) || ((strrpos($contenido,'.') < strlen($contenido)-1)) ){
		$contenido[strlen($contenido)]='.';
	}
	$num=numApariciones($contenido,".");
	if ($num>0){
		$separados = array();
		$separados[0]=sinSS(substrHasta($contenido,"."));
	
		for($i=1;$i<$num;$i++){
			$separados[$i]=sinSS(substrEntre($contenido,".",$i));
		}
		return $separados;
	}else{
		return false;
	}
}

// Separa frases por punto, y dentro de éstas, por lo que hay dentro y fuera de paréntesis (si tiene)
function separarContenido($contenido,$texto="texto",$ptsis="ptsis"){
	if ($contenido == ""){
		return false;
	}
	if( (strrpos($contenido,'.') === false ) || ((strrpos($contenido,'.') < strlen($contenido)-1)) ){
		$contenido[strlen($contenido)]='.';
	}
	$num=numApariciones($contenido,".");
	if ($num>0){
		$separados = array();
		$subcad=sinSS(substrHasta($contenido,"."));
		$separados[0]=parentesisTexto($subcad,0,$texto,$ptsis);
	
		for($i=1;$i<$num;$i++){
			$subcad=sinSS(substrEntre($contenido,".",$i));
			$separados[$i]=parentesisTexto($subcad,0,$texto,$ptsis);
		}
		return $separados;
	}else{
		return false;
	}
}

// Si la cadena pasada tiene paréntesis, separa el texto fuera del paréntesis del que hay dentro.
// Sino, devuelve la cadena original
function parentesisTexto($cad,$desde=0,$texto="texto",$ptsis="ptsis"){
	$sep = array();
	$par1 = strpos($cad,"(",$desde);
	$par2 = strpos($cad,")",$desde);
	if($par1 !== false && $par2 !== false && $par1<$par2){
		$sep[$ptsis]=substr($cad,$par1+1,$par2-$par1-1);
		//$antes=sinSS(substrHasta($cad,"("));
		//$despues=sinSS(substrDesde($cad,")"));
		$antes=sinSS(substr($cad,0,$par1));
		$despues=sinSS(substr($cad,$par2+1,strlen($cad)-$par2));
		if ($despues ==="."){
			$sep[$texto]=sinSS($antes);
		}else{
			$sep[$texto]=sinSS($antes." ".$despues);
		}
	}else{
		$sep[$texto]=$cad;
	}
	return $sep;
}

// Traduce los carácteres especiales de HTML más usuales a sus carácteres normales
function traducirHTML($cad){
    $cad=htmlspecialchars_decode($cad);
    $esp=array("&nbsp;","&aacute;","&eacute;","&iacute;","&oacute;","&uacute;","&ntilde;","&Aacute;","&Eacute;","&Iacute;","&Oacute;","&Uacute;","&Ntilde;");
    $sust=array(" ","á","é","í","ó","ú","ñ","Á","É","Í","Ó","Ú","Ñ");
    return str_replace($esp,$sust,$cad);
}

// A partir de una URL, devuelve en html con el que poder usar la libreria SimpleHTMLDOM
function obtenerSimpleHTML($url){
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_URL, $url);  
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  
	$str = curl_exec($curl);  
	curl_close($curl);  
	$str=traducirHTML($str);
	$html= str_get_html($str);
	return $html;
}

// A partir de una URL, devuelve su html como string
function obtenerHTML($url){
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_URL, $url);  
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  
	$str = curl_exec($curl);  
	curl_close($curl);  
	$str=traducirHTML($str);
	return $str;
}




// Convierte una fecha en formato "dd de <mes> de aaaa" a formato dd/mm/aaaa
function fechaNumerica($fecha){
    if(preg_match_all("/\d+/",$fecha,$coinc)==2){
        $mes=mb_substrEntre($fecha," ",2);
        $mesNum=mesNumerico($mes);

        if(strlen($coinc[0][0])==1) $coinc[0][0]="0".$coinc[0][0];
        if(strlen($coinc[0][1])==1) $coinc[0][1]="0".$coinc[0][1];
        return $coinc[0][0]."/".$mesNum."/".$coinc[0][1];
    }else{
        return $fecha;
    }
}

// Pasa a número un mes escrito en español
function mesNumerico($mes){
    switch(mb_strtolower($mes,'UTF-8')){
        case "enero":
            $mesNum="01";
            break;
        case "febrero":
            $mesNum="02";
            break;
        case "marzo":
            $mesNum="03";
            break;
        case "abril":
            $mesNum="04";
            break;
        case "mayo":
            $mesNum="05";
            break;
        case "junio":
            $mesNum="06";
            break;
        case "julio":
            $mesNum="07";
            break;
        case "agosto":
            $mesNum="08";
            break;
        case "septiembre":
            $mesNum="09";
            break;
        case "octubre":
            $mesNum="10";
            break;
        case "noviembre":
            $mesNum="11";
            break;
        case "diciembre":
            $mesNum="12";
            break;
        default:
            $mesNum=$mes;
            break;
    }
    return $mesNum;
}

// Si la fecha no cumple el formato estricto dd/mm/aaaa, la convierte al formato estricto
function estandarizarFecha($fecha){
	$separados=SepararTexto($fecha,"/");
	if($separados!==false){
		$ok="";
		$sep="/";
		for($i=0;$i<count($separados);$i++){
			if($i==2) $sep="";
			if(strlen($separados[$i])==1){
				$ok=$ok."0".$separados[$i].$sep;
			}else{
				$ok=$ok.$separados[$i].$sep;
			}
		}
		return $ok;
	}else{
		return false;
	}
}

//      FUNCIONES MONGODB
//
// Función que comprueba si ya ha sido descargado y almacenado en la colección un html de congreso.es: 
// Si ya está, lo devuelve. Sino, descarga, almacena y devuelve. 
// Permite parámetro forzarAct, a 1 para forzar actualización
function getHTMLCongreso($url,$forzarAct=0){
    $m=new MongoClient();
    $db=$m->que_hacen;
    $htmlCol=$db->htmlCongreso;
    $iter=$htmlCol->find();
    $encontrado=false;

    // Buscamos la url en la colección, si se encuentra:
    // Si forzarAct=1, actualizamos. Sino, sólo obtenemos el html
    foreach($iter as $elem){
        if ($url===$elem["url"]){
            $encontrado=true;
            if($forzarAct===1){
                $html=obtenerHTML($url);
                $nuevoHTML=array();
                $nuevoHTML["fecha"]=date("d-m-Y");
                $nuevoHTML["html"]=$html;
                $nuevoHTML["url"]=$url;
                $htmlCol->update(array("url" => $url),$nuevoHTML);
                echo "Actualizado el html para $url \n\n";
            }else{
                $html=$elem["html"];
                echo "Obtenido de la coleccion el html para $url \n\n";
            }
            break;
        }
    }

    // Si no se encontró el html, lo descargamos y lo almacenamos
    if($encontrado===false){
        $html=obtenerHTML($url);
        $nuevoHTML=array();
        $nuevoHTML["fecha"]=date("d-m-Y");
        $nuevoHTML["html"]=$html;
        $nuevoHTML["url"]=$url;
        $htmlCol->insert($nuevoHTML);
        echo "Descargado el html para $url \n\n";
    }

    return $html;

}

// Obtiene el SimpleHTML de una url para poder procesarlo con SImpleHTMLDOM
function getSimpleHTMLCongreso($url,$forzarAct=0){
    $htmlStr=getHTMLCongreso($url,$forzarAct);
    $html= str_get_html($htmlStr);
    return $html;
}

function verHTMLCol(){
    $m=new MongoClient();
    $db=$m->que_hacen;
    $htmlCol=$db->htmlCongreso;
    $cursor = $htmlCol->find();
    foreach ($cursor as $document) { 
        echo "URL: ".$document["url"]."\n";
        echo "Fecha de actualización: ".$document["fecha"]."\n\n";
    }
    echo "Número de HTMLS: ".$htmlCol->count()."\n";
}

function dropHTMLCol(){
    $m=new MongoClient();
    $db=$m->que_hacen;
    $htmlCol=$db->htmlCongreso;
    $htmlCol->drop();
    echo "La colección htmlCol ha sido borrada\n";
}

function getDipusCol(){
    $m=new MongoClient();
    $db=$m->que_hacen;
    $dipusCol=$db->diputados;
    return $dipusCol;
}

function getDipusCursor(){
    $dipusCol=getDipusCol();
    $cursor = $dipusCol->find();
    return $cursor;
}

function verDipusCol(){
    $dipusCol=getDipusCol();
    $cursor = $dipusCol->find();
    foreach ($cursor as $dipu){ 
        echo $dipu["nombre"]." ".$dipu["apellidos"].":\n";
        foreach($dipu as $campo => $valor){
            if ($campo!=="nombre" && $campo!=="apellidos"){
                if(gettype($valor)==="array"){
                    echo "$campo:";
                    echoArray($valor,1);
                }else{
                    echo "$campo : $valor\n";
                }
            }
        }
        echo "\n";
    }
    echo "Número de Diputados: ".$dipusCol->count()."\n";

}


function dropDipusCol(){
    $diputados=getDipusCol();
    $diputados->drop();
    echo "Se ha borrado la coleción de diputados\n";
} 

// Pasa a csv las legislaturas anteriores de los diputados
function csvLegislaturas(){
    $cursor=getDipusCursor();
    
    $fila=array();
	$fp = fopen('../csv/OtrasLegislaturas.csv', 'w');
	$fila[0]="Nombre";
	$fila[1]="Legis I";
    $fila[2]="Legis II";
    $fila[3]="Legis III";
    $fila[4]="Legis IV";
    $fila[5]="Legis V";
    $fila[6]="Legis VI";
    $fila[7]="Legis VII";
    $fila[8]="Legis VIII";
    $fila[9]="Legis IX";
    fputcsv($fp,$fila);
	
    $num=0;
    foreach($cursor as $dipu){
        if($dipu["activo"]===1){
            $legis=array(0,0,0,0,0,0,0,0,0);
            $fila[0]= $dipu["nombre"]." ".$dipu["apellidos"];
            if(count($dipu["legislaturas"])>1){
                foreach($dipu["legislaturas"] as $legislatura){
                    switch($legislatura){
                        case "I": $legis[0]=1; break;
                        case "II": $legis[1]=1; break;
                        case "III": $legis[2]=1; break;
                        case "IV": $legis[3]=1; break;
                        case "V": $legis[4]=1; break;
                        case "VI": $legis[5]=1; break;
                        case "VII": $legis[6]=1; break;
                        case "VIII": $legis[7]=1; break;
                        case "IX": $legis[8]=1; break;
                    }
                }
                foreach($dipu["legislaturas"] as $legislatura)
                    echo " ".$legislatura;
            }
            for($i=0;$i<9;$i++){
                $fila[$i+1]=$legis[$i];
            }
            fputcsv($fp,$fila);
        }
    }
    fclose($fp);
}

?>

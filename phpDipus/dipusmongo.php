<?php

include_once "lib/basiclib.php";
include_once "lib/quehacenlib.php";

function infoDipusMongo($e=""){
    echo "Llamada incorrecta al script.";
    if($e!=="") echo "$e";
    echo "\nUso: php dipusmongo.php -option\n";
    echo "Opciones:\n";
    echo "-t:\tAñadir a DB todos los diputados (scrapeo + csv)\n";
    echo "-T:\tAñadir a DB todos los diputados forzando descarga de sus html\n";
    echo "-t5:\tAñadir a DB los 5 primeros diputados (para pruebas)\n";
    echo "-a:\tActualizar diputados (añadir nuevos diputados de congreso.es si los hay)\n";
    echo "-c:\tActualizar cargos de los diputados en el Congreso\n";
    echo "-url:\tActualizar URLs no oficiales de los diputados\n";
    echo "-json:\tActualizar los JSON de los diputados\n";
    echo "-jsonv\tActualizar los JSON de las votaciones\n";
    echo "-img:\tActualizar imágenes y miniaturas de los diputados\n";
    echo "-gp:\tObtener iniciativas de los grupos (en fase de desarollo)\n";
    echo "-i:\tMostrar la colección de diputados en infoDB/dipus.txt\n";
    echo "-ih:\tMostrar la colección de htmls en infoDB/htmls.txt\n";
    echo "-d:\tBorrar la colección de diputados\n";
    echo "-dh:\tBorrar la colección de htmls\n";
}

// Procesamos los argumentos pasados al script
if($argc!==2){
    infoDipusMongo();
}else{
    switch($argv[1]){
        case "-t":  obtenerDiputados(0); break;
        case "-t5": obtenerDiputados(0,1,6); break;
        case "-T":  obtenerDiputados(1); break;        
        case "-a":  obtenerNuevosDiputados();break;
        case "-c":  actualizarCargosDipus();break;
        case "-url":actualizarUrlsCsvDipus();break;
        case "-json":actualizarJsonDipus();
            echo("Se ha actualizado la carpeta json/diputados/\n");break;
        case "-jsonv":actualizarJsonVotaciones();
            echo("Se ha actualizado la carpeta json/votaciones/\n");break;
        case "-gp": obtenerIniciativasGrupos();break;
        case "-i":  exec("php dipusmongo.php -if > infoDB/dipus.txt"); 
            echo("Info llevada a infoDB/dipus.txt\n");break;
        case "-img":actualizarImgDipus();break;
        case "-if": verDipusCol(); break;
        case "-d":  dropDipusCol(); break;
        case "-ih": exec("php dipusmongo.php -ihf > infoDB/htmls.txt"); 
            echo("Info llevada a infoDB/htmls.txt\n");break;
        case "-fb": exec("php dipusmongo.php -fbf > infoDB/facebooks.txt");
            echo("Info llevada a infoDB/facebooks.txt\n");break;
        case "-fbf": StatsUrlDipus("facebook");break; 
        case "-ihf": verHTMLCol(); break;
        case "-dh": dropHTMLCol(); break;
        default:    infoDipusMongo(" '".$argv[1]."' no es una opción válida.");break;
    }
}

 /* Función OBTENER DIPUTADOS
  - Obtiene la info de la ficha los diputados (scrapeo + csv) y la guarda en mongoDB
  - Estructura de datos para cada diputado $dipu se puede ver en leeme.txt
  - Con $desde se puede seleccionar desde qué ids de diputado queremos actualizar
  - Con $hasta hasta qué id de diputado como máximo queremos actualizar
  - Con $act establecemos qué htmls queremos forzar que se descarguen:
//   0 --> Sólo descargar los que no se encuentren en la colección (POR DEFECTO)
//   1 --> Forzar descarga de todos los html
//   2 --> Forzar descarga de los html de la ficha
//   3 --> Forzar descarga de los html de actividad
//   4 --> Forzar descarga de los html de los cargos
*/
function obtenerDiputados($act=0,$desde=1,$hasta=420){
	// Obtenemos la info de los csvs y los cargos en el congreso
	$dipusCsv=infoCsvDiputados("csv/Diputados.csv");
	$exDipusCsv=infoCsvDiputados("csv/ExDiputados.csv");
	$i=count($dipusCsv);
	foreach($exDipusCsv as $exDipu){
		$dipusCsv[$i]=$exDipu;
		$i++;
    }
    $actCargos=0;
    if($act===1 || $act===4) $actCargos=1; 
    $cargos=obtenerCargosCongreso($actCargos);

    // Abrimos la colección de diputados en Mongo
    $dipusCol=getDipusCol();

	// Obtenemos los diputados hasta que un scrapeo falle --> No es página de diputado --> Terminamos
	$i=$desde;
	$seguir=true;
	while($seguir===true && $i<$hasta){
		// Scrapeamos la info del diputado en su ficha de congreso.es
		// Si es página de diputado, seguimos. Sino, hemos acabado
		$dipu=scrapFichaDiputado($i,$act);
		if($dipu!==false){
			// Añadimos la info del csv referente al diputado
			$dipu=completarDiputado($dipu,$dipusCsv);
			
			// Añadimos sus cargos en el congreso. A partir de ellos, generamos su sueldo y lo añadimos
			$dipu=meterCargosSueldo($dipu,$cargos);
			
			// Mostramos la info del dipu por pantalla, para ver si todo va OK
			//echoArray($dipu);
			
			// CÓDIGO PARA PASAR A LA MONGODB LA INFO DEL DIPUTADO $dipu
            // Si el id del dipu ya está en mongo, actualizamos. Sino, insertamos
            $query=array('id' => $dipu['id']);
            $enc=$dipusCol->findOne($query);
            if($enc!==NULL){
                $dipusCol->update($query,$dipu);
                echo "Se ha actualizado el diputado ".$dipu["id"]."\n";
            }else{
                $dipusCol->insert($dipu);
                echo "Se ha insertado el diputado ".$dipu["id"]."\n";
            }
			
			// Almacenamos imagen y miniatura del diputado en img/imagenesDipus/ y img/miniaturasDipus/ con título <id_del_diputado>.jpg
			 obtenerImagenDipu($dipu["id"]);
			 obtenerMiniaturaDipu($dipu["id"]);

			$i++;
		}else{
			$seguir=false;
        }
    }
    $procesados= $i - $desde;
    echo "\nSe han actualizado $procesados diputados\n";
}


function obtenerNuevosDiputados(){
    $cursor=getDipusCursor();
    $max=1;
    //Buscamos el id mayor (último diputado almacenado)
    foreach($cursor as $dipu){
        if($dipu["id"]>$max)    
            $max=$dipu["id"];
    }
    //Si hay nuevos diputados en congreso.es, los almacenamos.
    $nuevo=scrapFichaDiputado($max+1,1);
    if($nuevo!==false){
        echo "\nHay nuevos diputados, los obtenemos:\n";
        obtenerDiputados(1,$max+1);
    }else{
        echo "\nNo hay nuevos diputados.\n";
    }
    
}

// Función para obtener estadísticas de las URL oficiales que ofrecen los dipus (correos, fb, tw) 
// En fase de desarrollo / ampliación
function StatsUrlDipus($tipoUrl){
    $cursor=getDipusCursor();
    $dipusCon=array();
    $i=0;
    foreach($cursor as $dipu){
        if(isset($dipu["contacto"])){
            $j=0;
            $urls=array();
            foreach($dipu["contacto"] as $url){
                if($url["tipo"]===$tipoUrl && $url["oficial"]===1){
                    $urls[$j]=$url["url"];
                    $j++;   
                }
            }
            if(count($urls)>0){
                $dipusCon[$i]["nom"]=$dipu["nombre"]." ".$dipu["apellidos"];
                $dipusCon[$i]["urls"]=$urls;
                $i++;
            }
        }
    }

   echo "Hay ".count($dipusCon)." diputados que ofrecen ".$tipoUrl.":\n";
    foreach($dipusCon as $dipuCon){
        echo $dipuCon["nom"].":\n";
        foreach($dipuCon["urls"] as $url){
            echo "\t$url\n";
        }
    }
}

// Busca las imágenes de 
function actualizarImgDipus(){
    $cursor=getDipusCursor();
    foreach($cursor as $dipu){
        $id=$dipu["id"];
        $urlImg="img/imagenesDipus/$id.jpg";
        $urlMin="img/miniaturasDipus/$id.jpg";
        if(file_exists($urlImg)===false){
            obtenerImagenDipu($id);
            echo "Se ha almacenado la imagen del diputado $id\n";
        }
        if(file_exists($urlMin)===false){
            obtenerMiniaturaDipu($id);
             echo "Se ha almacenado la miniatura del diputado $id\n";
        }
    }
}

function actualizarCargosDipus(){
    $cargos=obtenerCargosCongreso(1);
    $dipusCol=getDipusCol();
    $cursor=$dipusCol->find();
    foreach($cursor as $dipu){
        // Añadimos al dipu sus cargos actualizados y recalculamos su sueldo
        $dipu=meterCargosSueldo($dipu,$cargos);
        $query=array('id' => $dipu['id']);
        $dipusCol->update($query,$dipu);
    }    
}

function actualizarUrlsCsvDipus(){
    // Obtenemos la info de los csvs y los cargos en el congreso
	$dipusCsv=infoCsvDiputados("csv/Diputados.csv");
	$exDipusCsv=infoCsvDiputados("csv/ExDiputados.csv");
	$i=count($dipusCsv);
	foreach($exDipusCsv as $exDipu){
		$dipusCsv[$i]=$exDipu;
		$i++;
    }
    $dipusCol=getDipusCol();
    $cursor=$dipusCol->find();
    foreach($cursor as $dipu){
	    $i=0; $encontrado=false;
	    while($i<count($dipusCsv) && $encontrado===false){
		    if($dipu["id"]===$dipusCsv[$i]["id"]){
			    $encontrado=true;
		    }else{
			    $i++;
		    }
        }
        if($encontrado!==false){
            $dipuCsv=$dipusCsv[$i];
            $urls=array();
            $j=0;
            if(isset($dipu["contacto"])){
                foreach($dipu["contacto"] as $url){
                    if($url["oficial"]===1){
                        $urls[$j]["tipo"]=$url["tipo"];
                        $urls[$j]["url"]=$url["url"];
                        $urls[$j]["oficial"]=1;
                        $j++;
                    }
                }
            }
            $urls=incluirURLsNoOficiales($urls,$dipuCsv);
            if(count($urls)>0){
                $dipu["contacto"]=$urls;
                $query=array('id' => $dipu['id']);
                $dipusCol->update($query,$dipu);
            }
        }
    }
}

function actualizarJsonDipus(){
    $basicOp="mongoexport --db que_hacen --collection diputados"; 
    exec("$basicOp --jsonArray -o json/diputados/todos.json");
    exec("$basicOp --jsonArray -f id,nombre,apellidos -o json/diputados/id_nombre.json");
    $cursor=getDipusCursor();
    foreach($cursor as $dipu){
        $id=$dipu["id"];
        $nomJson="$id.json";
        $query="{'id':$id}";
        exec("$basicOp --query $query -o json/diputados/$nomJson");
    }
}

function actualizarJsonVotaciones(){
    $basicOp="mongoexport --db que_hacen --collection votacion"; 
    exec("$basicOp --jsonArray -o json/votaciones/todas.json");
    //exec("$basicOp --jsonArray -fieldFile json/fieldfiles/basicVotaciones.txt -o json/votaciones/basicoTodas.json");
    $cursor=getVotacionesCursor();
    foreach($cursor as $votacion){
        $numS=$votacion["xml"]["resultado"]["informacion"]["sesion"];
        $numV=$votacion["xml"]["resultado"]["informacion"]["numerovotacion"];
        $nomJson="sesion_".$numS."_votacion_".$numV.".json";
        $query="{'xml.resultado.informacion.sesion' : '$numS', 
            'xml.resultado.informacion.numerovotacion' : '$numV'}";
        exec("$basicOp --query \"$query\" -o json/votaciones/$nomJson");
    }
}

?>

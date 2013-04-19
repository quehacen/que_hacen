<?php 
/*
apariciones[$i]=strpos($cad,$subcad,$i);
ARITMÉTICAS BÁSICAS
	decimalBinario($decimal) : pasa un num de decimal a binario
	suma_() : media un conjunto de elementos pasados como entrada uno a uno
	media_() : media para un conjunto de elementos pasados como entrada uno a uno
	truncar($num,$decim) : trunca un número
	
ARRAYS 
	suma($a) : suma de valores de un array
	media($a) : Devuelve la media de valores en un array
	escribeSep($a,$sep)
	estaEnArray($valor,$array)

	[ASOCIATIVOS]
	amedia($a) : Devuelve la media de valores en un array asociativo
	aencimaDe($a,$umbral) : Devuelve un array asociativo con los elementos mayores que $umbral de %a (asociativo) 
	alistar($a) : Imprime un array asociativo en forma de lista <ul> de forma 'indice : valor'
	verArray(a) : Imprime un array de forma recursiva, teniendo en cuenta los campos que son también array
	
CADENAS
	quitarEspacios($cad) : devuelve cadena sin espacios
	quitarEspaciosSobra($cad) : devuelve cadena con espacios intermedios de longitud 1
	sinTNS($cad)
	sinSS($cad)
	aMayus($cad) : Convierte $cad a mayúscula teniendo en cuenta tildes y otros carácteres especiales
	quitarTildes($cad) : devuelve una cadena sin tildes
	caracteresSep($cad, $car) : devuelve los caracteres de $cad separados por $car
	numEspacios($cad) : devuelve número de espacios de $cad
	numPalabras($cad) : devuelve número de palabras de $cad
	palabrasArray($cad) : devuelve un array con todas las palabras de $cad. Si $cad no tiene palabras, devuelve false.
	Nortempo;
	numMinus($cad) : devuelve numero de minusculas de $cad
	numMayus($cad) : devuelve numero de mayusculas de $cad
	numApariciones($cad,$subcad) : devuelve numero de apariciones de $subcad en $cad
	indiceApariciones($cad,$subcad): Si hay apariciones de $subcad en $cad, devuelve array con los indices de todas las apariciones. Si no hay, devuelve -1.
	separarTexto($cad,$sep): separa todas las subcadenas en $cad que estén separadas por $sep
	descomponeMail($mail) : devuelve un array asociativo ["usuario","domsec","domppal"]
	substrHasta($cad,$subcad) : devuelve $cad desde el principio hasta que aparece $subcad. Si no está $subcad --> Cadena vacía
	substrDesde($cad,$subcad) : devuelve $cad desde que aparece $subcad hasta el final. Si no está $subcad --> Cadena vacía
	substrEntre($cad,$subcad,$pos): devuelve $cad desde el $num $subcad hasta el siguiente $subcad
	mb_substrHasta($cad,$subcad) : Igual que substrHasta pero procesando cadenas con multibytes
	mb_substrDesde($cad,$subcad) : Igual que substrDesde pero procesando cadenas con multibytes
	substrEntre($cad,$subcad,$pos): Igual que substrEntre pero procesando cadenas con multibytes
	obtenerCampoUrl($url,$campo) : para campo=valor en urls, devuelve valor. Solo admite numeros y letras en valor
	
COMPOSICIÓN HTML
	function cabecera() (args=$titulo,$estilos,$scripts,$doctype,$icono,$key,$descr)
	cuerpo($cab1)
	
FECHA Y HORA
	fechaEspanol()
	saludarSegunHora($nombre)
	cuantoQueda($tpost)
	
EJERCICIO AGENDA
	
*/

//FUNCIONES ARITMÉTICAS
	
	function decimalBinario($decimal){
		$restos="";
		while($decimal>1){
			$resto=$decimal%2;
			$restos=($resto).($restos);
			$decimal=intval($decimal/2);
		}
		$restos=($decimal).($restos);
		return $restos;
	}
	
	// media un conjunto de elementos pasados como entrada uno a uno
	function suma_(){
		$argumentos=func_num_args();
		if ($argumentos == 0){
			return 0;
		}else{
			$suma=0;
			for ($i=0;$i<$argumentos;$i++){
				$suma=$suma+func_get_arg($i);
			}
			return $suma;
		}
	}
	
	// media para un conjunto de elementos pasados como entrada uno a uno
	function media_(){
		$argumentos=func_num_args();
		if ($argumentos == 0){
			return 0;
		}else{
			$suma=0;
			for ($i=0;$i<$argumentos;$i++){
				$suma=$suma+func_get_arg($i);
			}
			return $suma/$argumentos;
		}
	}
	
	function truncar($numero,$decimales){
		$multiplo=1;
		for($i=0;$i<$decimales;$i++) $multiplo = $multiplo*10;
		$entero = intval($numero*$multiplo);
		return $entero/$multiplo;
	}

// ARRAYS
	function suma($a){
		$total=0;
		for($i=0;$i<count($a);$i++){
			$total=$total+$a[$i];
		}
		return $total;
	}
	
	function media($a){
		$total=suma($a);
		return $total/count($a);
	}
	
	function escribeSep($a,$sep){
		$retorno="";
		for ($i=0;$i<count($a);$i++){
			if ($i==0){
				$retorno=$a[$i];
			}else{
				$retorno=$retorno . $sep . $a[$i];
			}
		}
		return $retorno;
	}
	
	function estaEnArray($valor,$lista){
		for($i=0;$i<count($lista);$i++){
			if($lista[$i]===$valor)	return $i;
		}
		return false;
	}

// ARRAYS ASOCIATIVOS
	
	//Devuelve la media de un array asociativo
	function amedia($a){
		$total=0;
		foreach($a as $elem){
			$total=$total+$elem;
		}
		if ($total==0){
			return -1;
		}else{
			return $total/count($a);
		}
	}
	
	// Devuelve un array asociativo con los elementos mayores que umbral
	function aencimaDe($a,$umbral){
		$encima = array();
		foreach($a as $indice => $valor){
			if($valor > $umbral){
				$encima[$indice]=$valor;
			}
		}
		return $encima;
	}
	
	// Imprime un array asociativo en forma de lista de forma indice : valor
	function alistar($a){
		echo "<ul>";
		foreach($a as $indice => $valor){
			echo "<li>$indice : $valor</li>";
		}
		echo "</ul>";
	}
	
	// Imprime un array de forma recursiva en una lista <ul></ul>, teniendo en cuenta los campos que son array
	function verArray($array){
		echo "<ul>";
		foreach($array as $c => $v){
			if(gettype($v) === "array"){
				echo "<li>$c:";
				verArray($v);
				echo "</li>";
			}else{
				echo "<li>$c = $v</li>";
			}
		}
		echo "</ul>";
    }


    // Muestra recursivamente un array por terminal
    function echoArray($array,$ntab=0){
        $tab="";
        for($i=0;$i<$ntab;$i++)
            $tab=$tab."\t";
        echo "\n";
		foreach($array as $c => $v){
			if(gettype($v) === "array"){
                echo "$tab$c:";
				echoArray($v,$ntab+1);
				echo "\n";
			}else{
				echo "$tab$c = $v\n";
			}
		}
		echo "\n";
	}

// CADENAS
	
	function quitarEspacios($cad){
		$retorno="";
		for($i=0;$i<strlen($cad);$i++){
			if ($cad{$i} != " "){
				$retorno = $retorno . $cad{$i};
			}
		}
		return $retorno;
	}
	
	function quitarEspaciosSobra($cad){
		$cadena=trim($cad);
		$retorno="";
		if (strlen($cadena) > 0){
			$retorno=$cadena[0];
			for($i=1;$i<strlen($cadena);$i++){
				if( ($cadena[$i] != ' ') || ($cadena[$i] == ' ' && $cadena[$i-1] != ' ')){
					$retorno=$retorno . $cadena[$i];
				}
			}
		}
		return $retorno;
	}
	
	function sinTNS($cad){
		return sinSS(preg_replace("/(\r|\t)/","",$cad));
	}
	
	function sinPFS($cad){
		return sinSS(preg_replace("/\.$/"," ",$cad));
	}

	function sinSS($cad){
		return trim(preg_replace("/\s{2,}/"," ",$cad));
	}
	
	function aMayus($cad){
		$cad=strtoupper($cad);
		$esp=array("á","é","í","ó","ú","à","è","ì","ò","ù","Ä","Ë","Ï","Ö","Ü","ñ","ç");
		$sust=array("Á","É","Í","Ó","Ú","À","È","Ì","Ò","Ù","A","E","I","O","U","Ñ","Ç");
		return str_replace($esp,$sust,$cad);
	}
	
	function quitarTildes($cad){
		$no_permitidas= array ("á","é","í","ó","ú","Á","É","Í","Ó","Ú","ü","Ü","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹", "Ã ",);
		$permitidas= array ("a","e","i","o","u","A","E","I","O","U","u","U","A","E","I","O","U","a","e","i","o","u","c","C","a","e","i","o","u","A","E","I","O","U","u","o","O","i","a","e","U","I","A","E","a");
		$texto = str_replace($no_permitidas, $permitidas ,$cad);
		return $texto;
	}
	
	function caracteresSep($cad, $car){
		$retorno="";
		for ($i=0;$i<strlen($cad);$i++){
			if ($i==0){
				$retorno=$cad{$i};
			}else{
				$retorno=$retorno . $car . $cad{$i};
			}
		}
		return $retorno;
	}
	
	function numEspacios($cad){
		$espacios=0;
		for($i=0;$i<strlen($cad);$i++){
			if($cad[$i] == ' '){
				$espacios++;
			}
		}
		return $espacios;
	}
	
	function numPalabras($cad){
		return numEspacios(sinSS($cad))+1;
	}
	
	function palabrasArray($cad){
		$cad=sinSS($cad);
		$palabras=array();
		$numPalabras=numPalabras($cad);
		if($numPalabras>1){
			$palabras[0]=sinSS(substrHasta($cad," "));
			$espacios=indiceApariciones($cad," ");
			for($i=1;$i<$numPalabras-1;$i++){
				$inicio=$espacios[$i-1];
				$final=$espacios[$i]-$espacios[$i-1];
				$palabras[$i]=sinSS(substr($cad,$inicio,$final));
			}
			$palabras[$numPalabras-1]=sinSS(substr($cad,$espacios[count($espacios)-1]));
		}elseif($numPalabras==1){
			$palabras[0]=$cad;
		}else{
			return false;
		}
		return $palabras;
	}


	function obtenerNumPalabra($cad,$num){
		$numPal=numPalabras($cad);
		if($num<1 || $num>$numPal){
			return false;
		}else{
			$palabras=palabrasArray($cad);
			return $palabras[$num-1];
		}
	}

	
	function numMayus($cad){
		$mayus=0;
		for($i=0;$i<strlen($cad);$i++){
			if( (ord($cad[$i]) >= ord('A') && ord($cad[$i]) <= ord('Z')) || ord($cad[$i]) == ord('Ñ') ){
				$mayus++;
			}
		}
		return $mayus;
	}
	
	function numMinus($cad){
		$minus=0;
		for($i=0;$i<strlen($cad);$i++){
			if( (ord($cad[$i]) >= ord('a') && ord($cad[$i]) <= ord('z')) || ord($cad[$i]) == ord('ñ') ){
				$minus++;
			}
		}
		return $minus;
	}
	
	function numApariciones($cad,$subcad){
		$aparic=0;
		$i=0;
		while( strpos($cad,$subcad,$i) !== false ){
			$aparic++;
			$i=strpos($cad,$subcad,$i)+strlen($subcad);
		}
		return $aparic;
	}
	
	function indiceApariciones($cad,$subcad){
		$numAparic=0;
		$apariciones = array();
		$i=0;
		while( strpos($cad,$subcad,$i) !== false ){
			$apariciones[$numAparic]=strpos($cad,$subcad,$i);
			$i=strpos($cad,$subcad,$i)+strlen($subcad);
			$numAparic++;
		}
		if ( $numAparic == 0){
			return -1;
		}else{
			return $apariciones;
		}
	}
	
	function separarTexto($cad,$sep){
		$numAp=numApariciones($cad,$sep);
		$apariciones=indiceApariciones($cad,$sep);
		$ultimaAp=$apariciones[$numAp-1];
		if($numAp>0){
			$separados=array();
			for($i=0;$i<$numAp;$i++){
				$separados[$i]=substrEntre($cad,$sep,$i);
			}
			$separados[$numAp]=substr($cad,$ultimaAp+1);
			return $separados;
		}else{
			return false;
		}
	}
	
	function descomponeMail($mail){
		$correo = array();
		$arroba=strpos($mail,'@');
		$puntofinal = strrpos($mail,'.');
		
		$correo["usuario"]=substr($mail,0,$arroba);
		$correo["domsec"]=substr($mail,$arroba+1,$puntofinal-1-$arroba);
		$correo["domppal"]=substr($mail,$puntofinal,strlen($mail)-$puntofinal);
		
		return $correo;
	}
	
	function substrHasta($cad,$subcad){
		//$c = quitarEspaciosSobra($cad);
		$indice = strpos($cad,$subcad);
		if ($indice!== false){
			return substr($cad,0,$indice);
		}else{
			return "";
		}
	}
	
	function substrDesde($cad,$subcad){
		//$c = quitarEspaciosSobra($cad);
		$indice = strrpos($cad,$subcad);
		if ($indice!== false){
			return substr($cad,$indice+1,strlen($cad));
		}else{
			return "";
		}
	}
	
	function substrEntre($cad,$subcad,$pos){
		$sub="";
		if ($pos==0){
			return substrHasta($cad,$subcad);
		}else{
			$indice=0;
			for ($i=0;$i<$pos;$i++){
				$indice = strpos($cad,$subcad,$indice) +1;
				if ($indice=== false){
						break;
				}
			}
			$sig = strpos($cad,$subcad,$indice);
			$sub=substr($cad,$indice,($sig-$indice));
			return $sub;
		}
	}
	
	function mb_substrHasta($cad,$subcad){
		$indice = strpos($cad,$subcad);
		if ($indice!== false){
			return mb_substr($cad,0,$indice);
		}else{
			return "";
		}
	}

	function mb_substrDesde($cad,$subcad){
		$indice = strrpos($cad,$subcad);
		if ($indice!== false){
			return mb_substr($cad,$indice+1,strlen($cad));
		}else{
			return "";
		}
	}

	function mb_substrEntre($cad,$subcad,$pos){
		$sub="";
		if ($pos==0){
			return mb_substrHasta($cad,$subcad);
		}else{
			$indice=0;
			for ($i=0;$i<$pos;$i++){
				$indice = strpos($cad,$subcad,$indice) +1;
				if ($indice=== false){
						break;
				}
			}
			$sig = strpos($cad,$subcad,$indice);
			$sub=mb_substr($cad,$indice,($sig-$indice));
			return $sub;
		}
	}
	
	
	function obtenerCampoUrl($url,$campo){
		$patron="/$campo=(\d|\w)+/";
		if(preg_match($patron,$url,$coincidencias)!==false){
			$pre="$campo=";
			//$valor=$coincidencias[0];
			$valor=substr($coincidencias[0],strlen($pre));
			return $valor;
		}else{
			return false;
		}
	}
	
	// FUNCIONES COMPOSICIÓN HTML
	function cabecera($titulo,$estilo="",$imagen=""){
		$salida = "<html>\n<head>\n\t";
		$salida = $salida."<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>\n\t";
		if($estilo !="")
			$salida = $salida."<link rel='stylesheet' type='text/css' href='$estilo'/>\n\t";
		if($titulo !="")
			$salida = $salida."<title>$titulo</title>\n\t";
		if ($imagen!="")
			$salida = $salida."<link rel='shortcut icon' href='$imagen' />\n";
		$salida = $salida."</head>";
		return $salida;
	}
	
	function cuerpo($cab1){
		return "\n<body>\n\t<h1>$cab1</h1>\n</body>\n</html>";
	}
	
	// FUNCIONES FECHA Y HORA
	
	function fechaEspanol(){
		$diames=date("d");
		$anio=date("Y");
		
		switch(date("w")){
			case 1: $dia="Lunes";break;
			case 2: $dia="Martes";break;
			case 3: $dia="Miércoles";break;
			case 4: $dia="Jueves";break;
			case 5: $dia="Viernes";break;
			case 6: $dia="Sábado";break;
			case 0: $dia="Domingo";break;
		}
		switch( date("m") ){
			case 01: $mes="Enero";break;
			case 02: $mes="Febrero";break;
			case 03: $mes="Marzo";break;
			case 04: $mes="Abril";break;
			case 05: $mes="Mayo";break;
			case 06: $mes="Junio";break;
			case 07: $mes="Julio";break;
			case 08: $mes="Agosto";break;
			case 09: $mes="Septiembre";break;
			case 10: $mes="Octubre";break;
			case 11: $mes="Noviembre";break;
			case 12: $mes="Diciembre";break;
		}
		
		return "$dia, $diames de $mes de $anio";
	}
	
	function saludarSegunHora($nombre){
		$hora=date("H");
		$mensaje;
		switch($hora){
			case($hora < 6 && $hora > 20 ): $mensaje="Buenas noches";break;
			case($hora < 12 && $hora > 5 ): $mensaje="Buenos días";break;
			case($hora < 21 && $hora > 11 ): $mensaje="Buenas tardes";break;
		}
		return "$mensaje, $nombre.";
	}
	
	function cuantoQueda($tpost){
		$queda=$tpost-time();
		if ($queda>0){
			$dias=intval($queda/(3600*24));
			$queda= $queda % ($dias*3600*24);		
			
			$horas=intval($queda/3600);
			$queda=$queda%($horas*3600);
			
			$minutos=intval($queda/60);
			$queda=$queda%($minutos*60);
			
			$segundos=$queda;
		}
		return "$dias dias, $horas horas, $minutos minutos, $segundos segundos";
	}
	
	// EJERCICIO AGENDA
	
	function devolverDatos($linea){
		$datos = array();
		$datos['nom']=substrEntre($linea,".",0);
		$datos['ape']=substrEntre($linea,".",1);
		$datos['dni']=substrEntre($linea,".",2);
		$datos['dir']=substrEntre($linea,".",3);
		$datos['cp']=substrEntre($linea,".",4);
		$datos['tel']=substrDesde($linea,".");
		
		return $datos;
	}
	
	function tablaDatos($fd){
		if ($fd!==false){
			$tabla="<table id='contactos'><caption>Agenda de contactos</caption>";
			$tabla=$tabla."<tr><th>Nombre</th><th>Apellidos</th><th>DNI</th><th>Dirección</th><th>Código Postal</th><th>Teléfono</th>";
			while(!feof($fd)){
				$frase=fgets($fd);
				$datos=devolverDatos($frase);
				$fila= "<tr><td>".$datos["nom"]."</td><td>".$datos['ape']."</td><td>".$datos['dni']."</td>
						<td>".$datos['dir']."</td><td>".$datos['cp']."</td><td>".$datos['tel']."</td></tr>";
				$tabla=$tabla.$fila;
			}
			$tabla=$tabla."</table>";
			return $tabla;
		}else{
			return "<p>La agenda está vacía</p>";
		}
	}
?>

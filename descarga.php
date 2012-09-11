<?php
  ini_set('display_errors', '1');

  if(!isset($argc)) {
    print "Este programa solo puede ejecutarse en línea de comandos, tecleando 'php descarga.php'.";
    exit();
  }

  if(is_file('db.php')) {
    include_once('db.php');
  } else {
    include_once('db_sample.php');
  }

  if ($my->connect_errno) {
      printf("Connect failed: %s\n", $my->connect_error);
      exit();
  }

  // Espera a que se introduzca algo en el terminal y pulse ENTER
  function ask($msg = 'Por favor pulse ENTER para continuar ') {
    print $msg;
    return trim(fgets(STDIN));
  }

  // Extrae un <DIV class="blabla">...</DIV>
  // incluyendo todos los DIV anidados en su interior
  function get_div_by_class($html, $class) {
    // From http://www.devnetwork.net/viewtopic.php?f=38&t=102670
    $fila_diasRX = '{<div\s+class="'.$class.'"\s*>((?:(?:(?!<div[^>]*>|</div>).)++|<div[^>]*>(?1)</div>)*)</div>}si';
    preg_match_all($fila_diasRX, $html, $fila_diasA);
    return $fila_diasA[0];
  }



  // Sustituto de file_get_contents()
  // Por algún motivo no me funciona file_get_contents()
  // con una url, así que uso wget
  function get_file_from_url($url) {
    exec('wget "'.$url.'" -O file.html');
    $html = file_get_contents('file.html');
    unlink('file.html');
    return $html;
  }



  // Navega por el calendario de la página, va retrocediendo mes a mes
  // guardando todas las URL a sesiones para despues descargarse el HTML.
  function process_pending_url() {
    global $my;

    $res = $my->query("SELECT url FROM pending_url LIMIT 1");
    $row = $res->fetch_assoc();
    $my->query("DELETE FROM pending_url WHERE url='$row[url]'");
    $html = get_file_from_url($row['url']);
    $fila_diasA = get_div_by_class($html, 'fila_dias') ;

    $sesion_inserts = 0;
    foreach($fila_diasA AS $k => $div_fila_dias) {
      preg_match_all("/href='(http:.*?)'/", $div_fila_dias, $hrefA, PREG_PATTERN_ORDER);
      if(count($hrefA[1]) > 0) {
        foreach($hrefA[1] AS $k => $href) {
          // $href = http://www.congreso.es/portal/page/portal/Congreso/Congreso/Actualidad/Votaciones?_piref73_9564074_73_9536063_9536063.next_page=/wc/accesoHistoricoVotaciones&fechaSeleccionada=2012/7/iz
          preg_match('/fechaSeleccionada=(\d+)\/(\d+)\/(.+)/', $href, $fA);
          // $fA[1] = 2012
          // $fA[2] = 7
          // $fA[3] = 'iz';
          switch($fA[3]) {
            case 'de':
              // como empezamos en el presente, no avanzo hacia la derecha (futuro)
              break;
            case 'iz':
              $my->query("INSERT INTO pending_url(url) VALUES ('$href')");
              break;
            default:
              $my->query("INSERT INTO session(url, fecha) VALUES ('$href', '$fA[3]/$fA[2]/$fA[1]')");
              if($my->affected_rows > 0) {
                print "NEW: $href\n";
                $sesion_inserts ++;
              } else {
                print "OLD: $href\n";
              }
          }
        }
      }
    }

    return $sesion_inserts;
  }

  // Loop de descarga de URLs
  $my->query("TRUNCATE pending_url");
  $my->query("INSERT INTO pending_url(url) VALUES ('http://www.congreso.es/portal/page/portal/Congreso/Congreso/Actualidad/Votaciones')");

  $consecutive_empty_months = 0;
  while($consecutive_empty_months < 3) {
    $inserts = process_pending_url();
    if($inserts > 0) {
      $consecutive_empty_months = 0;
    } else {
      $consecutive_empty_months++;
    }
    sleep(2);
  }





  // Descarga el HTML pendiente de una sesion
  function process_session() {
    global $my;

    $res = $my->query("SELECT fecha,url FROM session WHERE html='' LIMIT 1");
    if ($res->num_rows > 0) {
      $row = $res->fetch_assoc();
      $html = $my->real_escape_string(get_file_from_url($row['url']));
      $my->query("UPDATE session SET html='$html' WHERE url='$row[url]'");
      print "Insert HTML for $row[fecha]\n";
      return true;
    } else {
      print "No pending URLs to read\n";
      return false;
    }
  }

  // Loop de descarga de HTML de sesiones
  while(process_session()) {
    sleep(2);
  }




  // Ahora de cada HTML extraer:

  // href="http://www.congreso.es/portal/page/portal/Congreso/Congreso/Iniciativas?_piref73_2148295_73_1335437_1335437.next_page=/wc/servidorCGI&amp;CMD=VERLST&amp;BASE=IW10&amp;FMT=INITXDSS.fmt&amp;DOCS=1-1&amp;DOCORDER=FIFO&amp;OPDEF=ADJ&amp;QUERY=%28173%2F000031*.NDOC.%29"
  // N veces
  //   iniciativa.num_expediente (162/000371)
  //   iniciativa.url
  //   (después leer url e insertar: iniciativa.html)

  // Por cada href anterion hay uno o varios enlaces a XML
  // URL XML: http://www.congreso.es/votaciones/OpenData?sesion=49&votacion=2&legislatura=10
  // votacion.xml_url
  // (después leer url e insertar: votacion.xml)
  // votacion.legislatura
  // votacion.num
  // votacion.num_expediente
  // (después leer contenido xml e insertar: votacion.puntos)

  // iniciativa.num_sesion=49

  function process_html() {
    global $my;

    $res = $my->query("SELECT id, html FROM session WHERE num_sesion=0 LIMIT 1");
    if ($res->num_rows > 0) {
      $row = $res-> fetch_assoc();
      preg_match_all('/href="(.+?)"/', $row['html'], $hrefA); // , PREG_PATTERN_ORDER
      $iniciativaFound = false;
      foreach($hrefA[1] as $i => $href) {
        if(strpos($href, 'Congreso/Congreso/Iniciativas?') > 0) {
          $iniciativaURL = $href;
          if(preg_match('/QUERY=%28(\d+)%2F([0-9.]+)\*\.NDOC\.%29/', $iniciativaURL, $iA)) {
            $numExpediente = "$iA[1]/$iA[2]";
            $my->query("INSERT INTO iniciativa SET num_expediente='$numExpediente', url='$href'");
            print "INSERT iniciativa: $numExpediente\n";
            $iniciativaFound = true;
          } else {
            print "ERROR: failed to find num_expediente WHERE session.id=$row[id] ($iniciativaURL)\n";
            exit();
          }
        }
        if(strpos($href, 'votaciones/OpenData') > 0 && $iniciativaFound) {
          $votosXMLURL = $href;
          if(preg_match('/sesion=(\d+)&votacion=(\d+)&legislatura=(\d+)/', $votosXMLURL, $vA)) {
            $numSesion = $vA[1];
            if(substr($votosXMLURL, 0, 1) == '/') {
              $votosXMLURL = 'http://www.congreso.es'.$votosXMLURL;
            }
            $my->query("INSERT INTO votacion SET xml_url='$votosXMLURL', legislatura=$vA[3], num=$vA[2], num_expediente='$numExpediente'");
            print "INSERT votacion: $numSesion, $vA[2], $vA[3]\n";
          }
        }
      }
      $my->query("UPDATE session SET num_sesion=$numSesion WHERE id=$row[id]");
      return true;
    } else {
      print "No pending HTML to process\n";
      return false;
    }
  }

  while(process_html()) {
    //sleep(1);
  }



  // Descarga XML desde votaciones.votacion.xml_url
  // votacion.xml

  function download_xml_votacion() {
    global $my;

    $res = $my->query("SELECT id, num_expediente, num, xml_url FROM votacion WHERE xml='' LIMIT 1");
    if ($res->num_rows > 0) {
      $row = $res->fetch_assoc();
      $xml = $my->real_escape_string(get_file_from_url($row['xml_url']));
      if(strlen($xml)) {
        $my->query("UPDATE votacion SET xml='$xml' WHERE id=$row[id]");
        if($my->affected_rows > 0) {
          print "DOWNLOADED XML: $row[num_expediente] $row[num]\n";
          return true;
        } else {
          print "CAN'T UPDATE XML: $row[num_expediente] $row[num]\n";
        }
      } else {
        print "CAN'T DOWNLOAD XML with id=$row[id]";
      }
    } else {
      print "No pending XML to download\n";
    }
    return false;
  }

  while(download_xml_votacion()) {
    usleep(800000);
  }



  // Descarga HTML desde votaciones.iniciativa.url
  // a iniciativa.html

  function download_html_iniciativa() {
    global $my;

    $res = $my->query("SELECT id, num_expediente, url FROM iniciativa WHERE html='' LIMIT 1");
    if ($res->num_rows > 0) {
      $row = $res->fetch_assoc();
      $html = $my->real_escape_string(get_file_from_url($row['url']));
      if(strlen($html)) {
        $my->query("UPDATE iniciativa SET html='$html' WHERE id=$row[id]");
        if($my->affected_rows > 0) {
          print "DOWNLOADED HTML-iniciativa: $row[num_expediente]\n";
          return true;
        } else {
          print "CAN'T UPDATE HTML-iniciativa: $row[num_expediente]\n";
        }
      } else {
        print "CAN'T DOWNLOAD HTML-iniciativa with id=$row[id]";
      }
    } else {
      print "No pending HTML-iniciativa to download\n";
    }
    return false;
  }

  while(download_html_iniciativa()) {
    usleep(800000);
  }

  //NOTA: Hay un error en la web del congreso (Un ID 8.0 que en realidad es 000008)
  //UPDATE iniciativa set url='http://www.congreso.es/portal/page/portal/Congreso/Congreso/Iniciativas?_piref73_2148295_73_1335437_1335437.next_page=/wc/servidorCGI&CMD=VERLST&BASE=IW10&FMT=INITXDSS.fmt&DOCS=1-1&DOCORDER=FIFO&OPDEF=ADJ&QUERY=%28121%2F000008*.NDOC.%29'
  //  html='' WHERE url='http://www.congreso.es/portal/page/portal/Congreso/Congreso/Iniciativas?_piref73_2148295_73_1335437_1335437.next_page=/wc/servidorCGI&CMD=VERLST&BASE=IW10&FMT=INITXDSS.fmt&DOCS=1-1&DOCORDER=FIFO&OPDEF=ADJ&QUERY=%28121%2F8.0*.NDOC.%29'
  //UPDATE `votacion` SET `num_expediente`='121/000008' WHERE num_expediente='121/8.0'

?>
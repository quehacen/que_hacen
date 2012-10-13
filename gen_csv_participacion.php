<?php
  ini_set('display_errors', '1');

  $filename = 'csv/participacion.csv';

  // Nota: la carpeta csv debe ser escribible por apache si
  // este programa se llama desde un navegador.
  // Para cambiar el propietario de la carpeta:
  // sudo chown www-data:www-data csv

  include_once('db.php');

  $my->real_query("SELECT id, xml FROM votacion");
  $res = $my->use_result();

  $diputado = array();
  while($row = $res->fetch_assoc()) {
    $r = new SimpleXMLElement($row['xml']);

    // A veces no hay votos a favor o en contra, solo "asentimiento"
    if($r->Totales->AFavor) {
      foreach($r->Votaciones->Votacion AS $v) {
        $nom = ''.$v->Diputado;
        $voto = ''.$v->Voto;
        $asiento = ''.$v->Asiento;

        if(!isset($diputado[$nom])) {
          $diputado[$nom] = array(
            'total' => 0,
            'Sí' => 0,
            'No' => 0,
            'Abstención' => 0,
            'No vota' => 0
          );
        }

        $diputado[$nom]['asientos'][$asiento] = true;
        $diputado[$nom]['total']++;
        $diputado[$nom][$voto]++;
      }
    }
  }

  $fp = fopen($filename, 'w');
  $bytes = fputcsv($fp, array('nombre', 'asientos', 'total', 'si', 'no', 'abs', 'novota'));

  if($fp == false) {
    print("No puedo abrir $filename en modo escritura\n");
    exit();
  }
  ksort($diputado);
  foreach($diputado AS $nom => $info) {
    $bytes += fputcsv($fp, array(
      $nom,
      implode(', ', array_keys($info['asientos'])),
      $info['total'],
      $info['Sí'],
      $info['No'],
      $info['Abstención'],
      $info['No vota']
    ));
  }

  fclose($fp);

  print "$bytes bytes guardados en $filename\n";

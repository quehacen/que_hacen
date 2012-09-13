<?php
  ini_set('display_errors', '1');

  $filename = 'csv/votaciones.csv';

  // Nota: la carpeta csv debe ser escribible por apache.
  // Para cambiar el propietario de la carpeta:
  // sudo chown www-data:www-data csv

  include_once('db.php');

  $my->real_query("SELECT id, xml FROM votacion");
  $res = $my->use_result();

  $fp = fopen($filename, 'w');
  $bytes = fputcsv($fp, array('fecha', 'presentes', 'si', 'no', 'abs'));
  while($row = $res->fetch_assoc()) {
    $r = new SimpleXMLElement($row['xml']);

    // A veces no hay votos a favor o en contra, solo "asentimiento"
    if($r->Totales->AFavor) {
      $bytes += fputcsv($fp, array(
        $r->Informacion->Fecha,
        $r->Totales->Presentes,
        $r->Totales->AFavor,
        $r->Totales->EnContra,
        $r->Totales->Abstenciones
      ));
    }
  }
  fclose($fp);

  print "$bytes bytes guardados en $filename";

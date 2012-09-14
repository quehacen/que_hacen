<?php
  ini_set('display_errors', '1');

  $filename = 'csv/votaciones.csv';

  // Nota: la carpeta csv debe ser escribible por apache.
  // Para cambiar el propietario de la carpeta:
  // sudo chown www-data:www-data csv

  include_once('db.php');

  $my->real_query("SELECT id, xml, num_expediente FROM votacion");
  $res = $my->use_result();

  $fp = fopen($filename, 'w');
  $bytes = fputcsv($fp, array('fecha', 'presentes', 'si', 'no', 'abs', 'num_expediente', 'titulo', 'texto_expediente', 'titulo_subgrupo', 'texto_subgrupo'));
  while($row = $res->fetch_assoc()) {
    $r = new SimpleXMLElement($row['xml']);

    // A veces no hay votos a favor o en contra, solo "asentimiento"
    if($r->Totales->AFavor) {
      $bytes += fputcsv($fp, array(
        $r->Informacion->Fecha,
        $r->Totales->Presentes,
        $r->Totales->AFavor,
        $r->Totales->EnContra,
        $r->Totales->Abstenciones,
        $row['num_expediente'],
        $r->Informacion->Titulo,
        $r->Informacion->TextoExpediente,
        $r->Informacion->TituloSubGrupo,
        $r->Informacion->TextoSubGrupo
      ));
    }
  }
  fclose($fp);

  print "$bytes bytes guardados en $filename";

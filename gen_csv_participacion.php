<?php

/*
    Herramientas para la descarga, análisis y visualización de datos referentes al 
    trabajo realizado por los diputados españoles en el Congreso.
    
    Copyright (C) 2012  Abraham Pazos Solatie

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

  ini_set('display_errors', '1');

  $filename = 'csv/participacion.csv';
  $filename2 = 'csv/diputados/%s.csv';

  // Nota: la carpeta csv debe ser escribible por apache si
  // este programa se llama desde un navegador.
  // Para cambiar el propietario de la carpeta:
  // sudo chown www-data:www-data csv

  include_once('db.php');

  function fixName($str) {
    return strtr($str, array(
      'Á'=>'A', 'À'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Å'=>'A', 'Ä'=>'A', 'Æ'=>'AE', 'Ç'=>'C',
      'É'=>'E', 'È'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Í'=>'I', 'Ì'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ð'=>'Eth',
      'Ñ'=>'N', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O',
      'Ú'=>'U', 'Ù'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y',

      'á'=>'a', 'à'=>'a', 'â'=>'a', 'ã'=>'a', 'å'=>'a', 'ä'=>'a', 'æ'=>'ae', 'ç'=>'c',
      'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e', 'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'eth',
      'ñ'=>'n', 'ó'=>'o', 'ò'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o',
      'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y',

      'ß'=>'sz', 'þ'=>'thorn', 'ÿ'=>'y',

      ' '=>'', ','=>'.'
    ));
  }

  $my->real_query("SELECT id, xml FROM votacion ORDER BY fecha");
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
            'No vota' => 0,
            'listado' => array()
          );
        }

        $diputado[$nom]['asientos'][$asiento] = true;
        $diputado[$nom]['total']++;
        $diputado[$nom][$voto]++;
        $id = $r->Informacion->Fecha .';'. $r->Informacion->Sesion .';'. $r->Informacion->NumeroVotacion;
        $diputado[$nom]['listado'][$id] = $voto;
      }
    }
  }

  $fp = fopen($filename, 'w');
  $bytes = fputcsv($fp, array('nombre', 'asientos', 'total', 'si', 'no', 'abs', 'novota', 'dias_no_vota', 'dias_vota_algo', 'dias_vota_todo'));

  if($fp == false) {
    print("No puedo abrir $filename en modo escritura\n");
    exit();
  }
  ksort($diputado);
  foreach($diputado AS $nom => $info) {
    $dias_con_no_voto = array();
    $dias_con_voto = array();

    $fp_listado = fopen(sprintf($filename2, fixName($nom)), 'w');
    $bytes += fputcsv($fp_listado, array('fecha', 'sesion', 'numeroVotacion', 'voto'));
    foreach($info['listado'] AS $id => $voto) {
      list($fecha, $sesion, $numeroVotacion) = explode(';', $id);
      $bytes += fputcsv($fp_listado, array(
        $fecha,
        $sesion,
        $numeroVotacion,
        $voto
      ));
      if($voto == 'No vota') {
        $dias_con_no_voto[$fecha] = true;
      } else {
        $dias_con_voto[$fecha] = true;
      }
    }
    fclose($fp_listado);

    $dias_no_vota = 0;
    $dias_vota_algo = 0;
    $dias_vota_todo = 0;
    foreach($dias_con_no_voto AS $fecha => $null) {
      if(isset($dias_con_voto[$fecha])) {
        $dias_vota_algo++;
      } else {
        $dias_no_vota++;
      }
    }
    foreach($dias_con_voto AS $fecha => $null) {
      if(!isset($dias_con_no_voto[$fecha])) {
        $dias_vota_todo++;
      }
    }

    $bytes += fputcsv($fp, array(
      $nom,
      implode(', ', array_keys($info['asientos'])),
      $info['total'],
      $info['Sí'],
      $info['No'],
      $info['Abstención'],
      $info['No vota'],
      $dias_no_vota,
      $dias_vota_algo,
      $dias_vota_todo
    ));

  }

  fclose($fp);

  print "$bytes bytes guardados en $filename\n";

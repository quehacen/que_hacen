/*
    Herramientas para la descarga, análisis y visualización de datos referentes al 
    trabajo realizado por los diputados españoles en el Congreso.
    
    Copyright (C) 2013  Abraham Pazos Solatie

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

var db = require('../db.js');
var csv = require('ya-csv');

// Nota: la carpeta csv debe ser escribible por apache si
// este programa se llama desde un navegador.
// Para cambiar el propietario de la carpeta:
// sudo chown www-data:www-data csv

// keep an eye on http://justinratner.com/2012/07/nodejs-fs-ya-csv-mysteriously-buffered-write-stream/

var cursor = false;

exports.input = function(pos, limit, cb) {
    if(cursor === false) {
        db.getVotacionAll(function(err, cur) {        
    		if(err || !cur)
	    		cb(null, false);
    		else
                cursor = cur;
                cursor.nextObject(function(err, item) {
                    if(item)
    	    		    cb([item]);
                    else
                        cb(null, false);
                });
            });
    } else {
        cursor.nextObject(function(err, item) {
            if(item)
    		    cb([item]);
            else
                cb(null, false);
        });
    }
}

exports.genCSV = function() {
    var filename = '../csv/votaciones.csv';

    db.getVotacion(function(err, result) {
        var writer = csv.createCsvFileWriter(filename);
        var data = [
            ['a','b','c','d','e','f','g'], 
            ['h','i','j','k','l','m','n']
        ];
        data.forEach(function(rec) {
            writer.writeRecord(rec);
        });
    });
}

exports.run = function(item) {
    var self = this;
    console.log(item.url);
	self.emit(["run F complete."]);
}

/*
  $my->real_query("SELECT id, xml, num_expediente FROM votacion");

  if($fp == false) {
    print("No puedo abrir $filename en modo escritura\n");
    exit();
  }

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

    console.log("$bytes bytes guardados en $filename\n");
*/


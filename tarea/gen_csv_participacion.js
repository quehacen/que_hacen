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

var filename = 'csv/participacion.csv';
var filename2 = 'csv/diputados/%s.csv';

var cursor = false;
var writer = csv.createCsvFileWriter(filename);

writer.writeRecord([
    'nombre', 
    'asientos', 
    'total', 
    'si', 
    'no', 
    'abs', 
    'novota', 
    'dias_no_vota', 
    'dias_vota_algo', 
    'dias_vota_todo'
]);

var diputado = {};

var Latinise = {};
Latinise.latin_map = { 
    'Á':'A', 'À':'A', 'Â':'A', 'Ã':'A', 'Å':'A', 'Ä':'A', 'Æ':'AE', 'Ç':'C',
    'É':'E', 'È':'E', 'Ê':'E', 'Ë':'E', 'Í':'I', 'Ì':'I', 'Î':'I', 'Ï':'I', 'Ð':'Eth',
    'Ñ':'N', 'Ó':'O', 'Ò':'O', 'Ô':'O', 'Õ':'O', 'Ö':'O', 'Ø':'O',
    'Ú':'U', 'Ù':'U', 'Û':'U', 'Ü':'U', 'Ý':'Y',
    'á':'a', 'à':'a', 'â':'a', 'ã':'a', 'å':'a', 'ä':'a', 'æ':'ae', 'ç':'c',
    'é':'e', 'è':'e', 'ê':'e', 'ë':'e', 'í':'i', 'ì':'i', 'î':'i', 'ï':'i', 'ð':'eth',
    'ñ':'n', 'ó':'o', 'ò':'o', 'ô':'o', 'õ':'o', 'ö':'o', 'ø':'o',
    'ú':'u', 'ù':'u', 'û':'u', 'ü':'u', 'ý':'y',
    'ß':'sz', 'þ':'thorn', 'ÿ':'y',
    ',':'.'
};
String.prototype.latinise=function() {
    return this.replace(/[^A-Za-z0-9\[\] ]/g, function(a){ return Latinise.latin_map[a]||a }).split(' ').join('');
};

exports.options = {
    wait: 0
}
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
                saveFilesToDisk(cb);
                //cb(null, false);
        })
    }
}
exports.run = function(item) {
    var res = item.xml.resultado;
    if(res.totales.asentimiento == 'No') {
        for(var v in res.votaciones.votacion) {
            var vot = res.votaciones.votacion[v];
            var nom = vot.diputado;
            var voto = vot.voto;
            var asiento = vot.asiento;

            if(diputado[nom] == undefined) {
                diputado[nom] = {
                    'asientos': {},
                    'total': 0,
                    'Sí':0,
                    'No':0,
                    'Abstención':0,
                    'No vota':0,
                    'listado':{}
                };
            }
            diputado[nom]['asientos'][asiento] = true;
            diputado[nom]['total']++;
            diputado[nom][voto]++;
            var id = res.informacion.fecha + ';' + res.informacion.sesion + ';' + res.informacion.numerovotacion;
            diputado[nom]['listado'][id] = voto;
        }
    }
    self.emit(["run G complete. Diputado #" + diputado.length]);
}


saveFilesToDisk = function(cb) {
    // sort diputado by name
    var names = Object.keys(diputado);
    var len = names.length;
    names.sort();

    for (var i=0; i<len; i++) {
        var nom = names[i];
        var info = diputado[nom];

        var dias_con_no_voto = {};
        var dias_con_voto = {};

        var writer2 = csv.createCsvFileWriter(filename2.replace('%s', nom.latinise()));
        writer2.writeRecord([
            'fecha', 
            'sesion', 
            'numeroVotacion', 
            'voto'
        ]);
        for(var id in info['listado']) {
            var voto = info['listado'][id];
            var tmp = id.split(";");

            var fecha = tmp[0];
            var sesion = tmp[1];
            var numeroVotacion = tmp[2];
            
            writer2.writeRecord([
                fecha,
                sesion,
                numeroVotacion,
                voto
            ]);
            if(voto == 'No vota') {
                dias_con_no_voto[fecha] = true;
            } else {
                dias_con_voto[fecha] = true;
            }
        }

        // I don't know how to close the csv file...
        // fclose($fp_listado);

        var dias_no_vota = 0;
        var dias_vota_algo = 0;
        var dias_vota_todo = 0;
        for(var fecha in dias_con_no_voto) {
            if(dias_con_voto[fecha] != undefined) {
                dias_vota_algo++;
            } else {
                dias_no_vota++;
            }
        }
        for(var fecha in dias_con_voto) {
            if(dias_con_no_voto[fecha] == undefined) {
                dias_vota_todo++;
            }
        }
        
        writer.writeRecord([
            nom,
            Object.keys(info['asientos']).join(', '),
            info['total'],
            info['Sí'],
            info['No'],
            info['Abstención'],
            info['No vota'],
            dias_no_vota,
            dias_vota_algo,
            dias_vota_todo
        ]);
    }
    cb(null, false);
}



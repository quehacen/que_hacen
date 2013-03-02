#!/usr/bin/env node

var nodeio = require('node.io'),
    qhdb = require('./qhdb.js'),
    util = require('util'),
    async = require('async');

var argv = require('optimist')
    .usage('Download data from congreso.es, store in MongoDB\nUsage: $0 -abc')
    .check(function(a) { if(!(a.a || a.b || a.c)) { throw("a, b or c missing"); }; })
    .describe('a', 'Download links to sessions')
    .describe('b', 'Download session html')
    .describe('c', 'Populate iniciativa & votacion')
    .argv
;


// TAREA 1
// Lee y elimina URLs de pendingURL
// Para cada entrada que encuentra, ejecuta run1()

function input1(pos, limit, cb) {
	qhdb.getPendingURL(function(err, item) {
		if(err || !item)
			cb(null, false);
		else
			cb([item]);
	});
}

// Las páginas "votaciones" incluyen un calendario
// con enlaces al mes siguiente y anterior, y enlaces a las sesiones
// por días. Esta función almacena los enlaces a meses anteriores
// en pendingURL, y los enlaces a sesiones en session.
// Ambas urls deben ser procesadas posteriormente.

var consecutiveEmptyMonths = 0;

function run1(item) { // item = { url:'', _id:'' }
	console.log("run1 " + item.url);

	if(consecutiveEmptyMonths > 2) {
		this.emit(["Found 3 consecutive existing sessions. Won't try further."]);
		return;
	}
	var self = this;
	var insertedNewSession = false;

	this.getHtml(item.url, function(err, $) {
		if(err) gameOver(err);

		var urlArray = [];
		var rx = /fechaSeleccionada=(\d+)\/(\d+)\/(.+)/; 
		$('div.fila_dias a').each('href', function(url) {
            if(!url) return;
			var yymmdd = rx.exec(url); // yymmdd[1] = 2012, [2] = 7, [3] = 'iz';
			switch(yymmdd[3]) {
				// mes siguiente. No borrar!
				case 'de': 
					break; 
				// mes anterior
				case 'iz':
					urlArray.push({url: url, pendingURL: true }); 
					break;
				// enlaces a sesión
				default: 
					var fecha = yymmdd[3] + "/" + yymmdd[2] + "/" + yymmdd[1];
					urlArray.push({ url: url, fecha: fecha });
					break;
			}
		});
		var count = urlArray.length;
		console.log("run1 going to proccess " + count + " urls");
		for(var i in urlArray) {
			var o = urlArray[i];
			if(o.pendingURL) {
				qhdb.insertPendingURL(o.url, function(err, result) { 
					if(err) gameOver(err);
					if(--count == 0) {
						if(!insertedNewSession) consecutiveEmptyMonths++;
						self.emit(["run1 complete"]);
					}
				});
			} else {
				qhdb.insertIntoSession({ fecha: o.fecha, url: o.url }, function(err, result) {
					if(err) gameOver(err);
					if(result !== false) {
						console.log("run1:" + count + ". Found new session.");
						insertedNewSession = true;
						consecutiveEmptyMonths = 0;
					} else {
						console.log("run1:" + count + ". Old session.");
					}
					if(--count == 0) {
						if(!insertedNewSession) consecutiveEmptyMonths++;
						self.emit(["run1 complete"]);
					}
				});
			}
		}
	});
}


// TAREA 2
// busca documentos en session con html:null

function input2(pos, limit, cb) {
	qhdb.getSessionWithNoHtml(function(err, item) {
		if(err || !item) {
			console.log("No sessions with empty html field");
			cb(null, false);
		} else
			cb([item]);
	});
}

// Descarga el html de cada enlace (uno por fecha) encontrado por input2

function run2(item) { // item = { url:'', fecha:'', _id:'', html:'' }
	var self = this;
	this.get(item.url, function(err, data) {
		if(err) gameOver(err);
		qhdb.setSessionHtml(item._id, data, function(err, result) {
			if(err) gameOver(err);
			self.emit(["run2 complete: " + item._id + " html=" + data.length + " bytes."]);
		});
	});
}

// TAREA 3
// Analiza los html descargados por tarea 2 y extrae información

function input3(pos, limit, cb) {
    // TEMP TEST LIMIT N
    if(pos > 0) { cb(null, false); return; }
    qhdb.getSessionWithNoNum(function(err, item) {
        if(err || !item) {
            console.log("No sessions with empty num field");
            cb(null, false);
        } else
            cb([item]);
    });
}

function run3(item) { // item = { url:'', fecha:'', _id:'', html:'' }
    console.log("run3 " + item.fecha + " url: " + item.url );
    this.parseHtml(item.html, function(err, $) {
        if(err) gameOver(err);

        var numExpediente = '',
            numSesion = '';

        var rxIniciativa = /QUERY=%28(\d+)%2F([0-9.]+)\*\.NDOC\.%29/,
            rxXML = /sesion=(\d+)&votacion=(\d+)&legislatura=(\d+)/;

        $('a').each('href', function(url) {
            if(!url) return;

            if(url.indexOf('Congreso/Congreso/Iniciativas?') >= 0) {
                var urlIniciativa = url;
			    var p = rxIniciativa.exec(urlIniciativa);
                if(p) {
                    numExpediente = p[1] + '/' + p[2];
                    console.log('iniciativa', numExpediente);
                    // insert into iniciativa numExpediente, urlIniciativa
                } else {
                    console.log("Err: rx=null. Can't find numExpediente in ", urlIniciativa);
                }
            }

            if(numExpediente != '' && url.indexOf('votaciones/OpenData') >= 0) {
                var urlVotosXML = url;
                var p = rxXML.exec(urlVotosXML);
                if(urlVotosXML.charAt(0) == '/')
                    urlVotosXML = 'http://www.congreso.es' + urlVotosXML;
                if(p) {
                    numSesion = p[1];
                    console.log('xml', p[1], p[2], p[3]);
                    // insert into votacion urlVotosXML, legislatura, num, numExp
                } else {
                    console.log('Err: rx=null. ses vot leg?');
                }
            }
        });
        // update session set num=numSesion where id=item._id
    });
    // find all href="(.*)" in item.html
    emit(["Test task 3 done: " + item]);
}


// GESTION DE TAREAS

function jobMaker(inputf, runf) {
    return function(cb) { 
        nodeio.start(new nodeio.Job({ input: inputf, run: runf }), 
            { timeout: 15, debug: true }, cb); 
    };
}

function jobSequence() {
    var s = [];

    if(argv.a) s.push(jobMaker(input1, run1));
    if(argv.b) s.push(jobMaker(input2, run2));
    if(argv.c) s.push(jobMaker(input3, run3));
    
    async.series(s, function(err, result) {
		if(err) console.log("JobSequence error: " + err);
	});
}

// Inicio. Conexión a la base de datos, lanzar tareas

qhdb.connect(function() {
	qhdb.cleanPendingURL(function(err, result) {
		if(err)	gameOver("Error cleaning pendingURL: " + err);
		qhdb.insertPendingURL(null, jobSequence);
	});
});

// FIN (ERROR)

function gameOver(err) {
	console.log(err);
	process.exit(code=1);
}

#!/usr/bin/env node

var nodeio = require('node.io'),
    qhdb = require('./qhdb.js'),
    async = require('async');

var tareaA = require('./tareaA.js'),
    tareaB = require('./tareaB.js'),
    tareaC = require('./tareaC.js');

// Linea de comandos

var argv = require('optimist')
    .usage('Download data from congreso.es, store in MongoDB\nUsage: $0 -abc')
    .check(function(a) { if(!(a.a || a.b || a.c || a.i)) { throw("a, b, c or i missing"); }; })
    .describe('a', 'Download links to sessions')
    .describe('b', 'Download session html')
    .describe('c', 'Populate iniciativa & votacion')
    .describe('i', 'Database Info')
    .argv
;

// Gestión de tareas

function jobMaker(inputfunc, runfunc) {
    return function(cb) { 
        nodeio.start(new nodeio.Job({ input: inputfunc, run: runfunc }), 
            { timeout: 15, debug: true }, cb); 
    };
}

function jobSequence() {
    var s = [];

    // -a = busca enlaces a sesiones y votaciones
    if(argv.a) s.push(function(cb) { 
	    qhdb.cleanPendingURL(function(err, result) {
		    if(err)	gameOver(err);
    		qhdb.insertPendingURL(null, function(err, result) {
                jobMaker(tareaA.input, tareaA.run)(cb);
            });
    	});
    });

    // -b = descarga archivos html y los guarda en la db
    if(argv.b) s.push(jobMaker(tareaB.input, tareaB.run));

    // -c = extrae datos sobre iniciativas y votaciones del html
    if(argv.c) s.push(jobMaker(tareaC.input, tareaC.run));
    
    // ejecuta las tareas una tras otra
    async.series(s, function(err, result) {
		gameOver(err);
	});
    
    // -i = info
    if(argv.i) qhdb.info();
}

// Inicio. Conexión a la base de datos, lanzar tareas

qhdb.connect(jobSequence);

// Fin

function gameOver(err) {
    // TODO: disconnect from DB.
    if(err) {
    	console.log(err);
	    process.exit(code=1);
    } // else process.exit(code=0);
}

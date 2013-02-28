var nodeio = require('node.io'),
    qhdb = require('./qhdb.js'),
    util = require('util'),
    async = require('async');

var consecutiveEmptyMonths = 0;

// Lee y elimina URLs de pendingURL
// Para cada entrada que encuentra, ejecuta run()
function input1(pos, limit, cb) {
	qhdb.pendingURL().findAndRemove({}, function(err, item) {
		if(err || !item)
			cb(null, false);
		else
			cb([item]);
	});
}
// busca documentos en sessios donde html=""
function input2(pos, limit, cb) {
	qhdb.session().findOne({ html:null }, function(err, item) {
		if(err || !item) {
			console.log("No sessions with empty html field");
			cb(null, false);
		} else
			cb([item]);
	});
}

// runN() es ejecutado por input() para cada URL que encuentra.
// runN() descarga esas URLs, y extrae información

// run1 entiende la página "votaciones" que incluye un calendario
// con enlaces al mes siguiente y anterior, y enlaces a las sesiones
// por días. Esta función almacena los enlaces a meses anteriores
// en db.pendingURL, y los enlaces a sesiones en db.session.
// Ambos tipos de URLs deben ser procesados posteriormente.
function run1(item) {
	console.log("run1 " + item.url);
	// item.url, item._id
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
			var parts = rx.exec(url); // parts[1] = 2012, [2] = 7, [3] = 'iz';
			switch(parts[3]) {
				// mes siguiente. No borrar!
				case 'de': 
					break; 
				// mes anterior
				case 'iz':
					urlArray.push({url: url, pendingURL: true }); 
					break;
				// enlaces a sesión
				default: 
					var fecha = parts[3] + "/" + parts[2] + "/" + parts[1];
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

// run2 descarga el html de cada enlace (uno por fecha) encontrado por run1
function run2(item) {
	// item.url, item.fecha, item._id
	var self = this;
	this.get(item.url, function(err, data) {
		if(err) gameOver(err);
		qhdb.setSessionHtml(item._id, data, function(err, result) {
			if(err) gameOver(err);
			self.emit(["run2 complete: " + item._id + " html=" + data.length + " bytes."]);
		});
	});
}

// Tareas de node.io que realizan el loop de descarga y proceso.
var job1 = new nodeio.Job({ input: input1, run: run1 });
var job2 = new nodeio.Job({ input: input2, run: run2 });

// Secuencia de tareas
function jobSequence() {
	async.series({
		job1: function(cb) { nodeio.start(job1, { timeout: 15 }, cb); },
		job2: function(cb) { nodeio.start(job2, { timeout: 15 }, cb); }
	},
	function(err, result) {
		if(err)
			console.log("JobSequence error: " + err);
	});
}

// Error. Salir.
function gameOver(err) {
	console.log(err);
	process.exit(code=1);
}

// Conexión a la base de datos.
qhdb.connect(function() {
	qhdb.cleanPendingURL(function(err, result) {
		if(err)	gameOver("Error cleaning pendingURL: " + err);
		qhdb.insertPendingURL(null, jobSequence);
	});
});


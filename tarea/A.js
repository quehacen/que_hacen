var db = require('../db.js');
var async = require('async');

// Lee y elimina URLs de pendingURL
// Para cada entrada que encuentra, ejecuta run()

exports.input = function(pos, limit, cb) {
	db.getPendingURL(function(err, item) {
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

exports.run = function(item) { // item = { url:'', _id:'' }
	console.log("run1 " + item.url);

	if(consecutiveEmptyMonths > 3) {
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

        async.eachSeries(urlArray, function(o, cb) {
			if(o.pendingURL) {
				db.insertPendingURL(o.url, function(err, result) { 
					cb(err);
				});
			} else {
				db.insertIntoSession({ fecha: o.fecha, url: o.url }, function(err, result) {
					if(err == null) {
                        if(result !== false) {
                            console.log("run1: Found new session.");
                            insertedNewSession = true;
                            consecutiveEmptyMonths = 0;
                        } else {
                            console.log("run A: Old session.");
                        }
                    }
                    cb(err);
				});
			}
        }, function(err) {
            if(err) gameOver(err);
			if(!insertedNewSession) consecutiveEmptyMonths++;
			self.emit(["run A complete"]);        
        });
	});
}

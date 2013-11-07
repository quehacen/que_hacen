var db = require('../db.js'),
    async = require('async');

// Analiza los html descargados por tarea 2 y extrae información

exports.input = function(pos, limit, cb) {
    db.getSessionWithNoNum(function(err, item) {
        if(err || !item) {
            console.log("No sessions with empty num field");
            cb(null, false);
        } else
            cb([item]);
    });
}

exports.run = function(item) { // item = { url:'', fecha:'', _id:'', html:'' }
	var self = this;
    console.log("run3 " + item.fecha + " url: " + item.url );
    this.parseHtml(item.html, function(err, $) {
        if(err) gameOver(err);

        var numExpediente = '',
            numSesion = '';

        var rxIniciativa = /QUERY=%28(\d+)%2F([0-9.]+)\*\.NDOC\.%29/,
            rxXML = /sesion=(\d+)&votacion=(\d+)&legislatura=(\d+)/;

		var urlArray = [];
        $('a').each('href', function(url) {
            if(url) urlArray.push(url);
        });

        async.eachSeries(urlArray, function(url, cb) {
            if(url.indexOf('Congreso/Congreso/Iniciativas?') >= 0) {
			    var p = rxIniciativa.exec(url);
                if(p) {
                    numExpediente = p[1] + '/' + p[2];
                    db.insertIniciativa(numExpediente, url, function(err, result) {
                        cb(err);
                    });
                } else {
                    cb("Err: rxIniciativa=null in " + url);
                }
            } else if(numExpediente != '' && url.indexOf('votaciones/OpenData') >= 0) {
                var p = rxXML.exec(url);
                if(url.charAt(0) == '/')
                    url = 'http://www.congreso.es' + url;
                if(p) {
                    numSesion = p[1];
                    db.insertVotacion(url, p[3], p[2], numExpediente, function(err, result) {
                        cb(err);
                    });
                } else {
                    // La URL puede contener "completa", y la rx falla. Me los salto.
                    // http://www.congreso.es/votaciones/OpenData?sesion=74&completa=1&legislatura=10
                    console.log('Warning: rxXML=null in ' + url);
                    cb();
                }
            } else {
                cb();
            }
        }, function(err) {
            if(err) gameOver(err);
            db.setSessionNum(item._id, numSesion, function(err, result) {
                self.emit(["run C complete: " + item]);
            });
        });
    });
}

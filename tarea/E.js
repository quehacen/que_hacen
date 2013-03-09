var qhdb = require('../qhdb.js');

// busca documentos en iniciativa con html:null

exports.input = function(pos, limit, cb) {
	qhdb.getIniciativaWithNoHtml(function(err, item) {
		if(err || !item) {
			console.log("No iniciativa with empty html field");
			cb(null, false);
		} else
			cb([item]);
	});
}

// Descarga el html de cada enlace (uno por fecha) encontrado por input

exports.run = function(item) { // item = { numExpediente:'', url:'', _id:'', html:'' }
	var self = this;
	this.get(item.url, function(err, data) {
		if(err) gameOver(err);
		qhdb.setIniciativaHtml(item._id, data, function(err, result) {
			if(err) gameOver(err);
			self.emit(["run E complete: " + item._id + " html=" + data.length + " bytes."]);
		});
	});
}


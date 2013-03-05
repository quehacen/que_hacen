var qhdb = require('./qhdb.js');

// busca documentos en session con html:null

exports.input = function(pos, limit, cb) {
	qhdb.getSessionWithNoHtml(function(err, item) {
		if(err || !item) {
			console.log("No sessions with empty html field");
			cb(null, false);
		} else
			cb([item]);
	});
}

// Descarga el html de cada enlace (uno por fecha) encontrado por inputB

exports.run = function(item) { // item = { url:'', fecha:'', _id:'', html:'' }
	var self = this;
	this.get(item.url, function(err, data) {
		if(err) gameOver(err);
		qhdb.setSessionHtml(item._id, data, function(err, result) {
			if(err) gameOver(err);
			self.emit(["run2 complete: " + item._id + " html=" + data.length + " bytes."]);
		});
	});
}


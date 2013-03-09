var qhdb = require('../qhdb.js');
var xml2js = require('xml2js');
var Iconv = require('iconv').Iconv;

// busca documentos en votacion con xml:null

exports.input = function(pos, limit, cb) {
	qhdb.getVotacionWithNoXML(function(err, item) {
		if(err || !item) { //  || pos > 0
			console.log("votacion with no xml not found");
			cb(null, false);
		} else
			cb([item]);
	});
}

function xml2json(xml, cb) {
    // I tried using this to fix encoding but it did not work,
    // so I changed jobMaker and set encoding to binary.

    //var iconv = new Iconv('iso-8859-1', 'utf-8');
    //var xml = iconv.convert(xml).toString('utf8');

    var parser = new xml2js.Parser({ 
        explicitArray:false, 
        normalizeTags: true 
    });
    parser.parseString(xml, cb);
}

// Descarga el xml de cada enlace encontrado por input

exports.run = function(item) { // item = { numExpediente:'', num:'', _id:'', xmlUrl:'' }
	var self = this;
	this.get(item.url, function(err, xml) {
		if(err) gameOver(err);
        xml2json(xml, function(err, json) {
            //console.log(JSON.stringify(json, null, 4));
            qhdb.setVotacionXML(item._id, json, function(err, result) {
                if(err) gameOver(err);
                self.emit(["run D complete: " + item._id + 
                        " xml=" + xml.length + " bytes."]);
            });
        });
	});
}

// Consulta de ejemplo aplicable una vez almacenado en formato json:
// db.votacion.find({"xml":{$exists:true}}, { 
//        "xml.resultado.informacion.titulo":1 });


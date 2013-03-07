var fs = require('fs'),
    Iconv = require('iconv').Iconv,
    xml2js = require('xml2js');

function readFileSync_encoding(filename, encoding) {
    var content = fs.readFileSync(filename);
    var iconv = new Iconv(encoding, 'UTF-8');
    var buffer = iconv.convert(content);
    return buffer; //.toString('utf8');

}
var parser = new xml2js.Parser({ explicitArray:false, normalizeTags: true });
var data = readFileSync_encoding(__dirname + '/votacion.xml', 'iso-8859-1');
parser.parseString(data, function (err, result) {
    //console.log(JSON.stringify(result, null, 4));
    console.log(result.resultado.informacion.sesion,
                result.resultado.informacion.titulo,
                result.resultado.informacion.textosubgrupo);
    console.log(result.resultado.totales.afavor);

    console.log(result.resultado.votaciones.votacion[3].diputado,
                result.resultado.votaciones.votacion[3].voto,
                result.resultado.votaciones.votacion[3].asiento);
    console.log('Done');
});

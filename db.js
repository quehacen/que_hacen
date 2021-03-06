var client = require('mongodb').MongoClient,
    ObjectID = require('mongodb').ObjectID,
    async = require('async');

var cPendingURL,
    cSession,
    cIniciativa,
    cVotacion;

var cfgMongoURL = 'mongodb://localhost:27017/que_hacen',
    cfgStartURL = 'http://www.congreso.es/portal/page/portal/Congreso/Congreso/Actualidad/Votaciones';

// Connect

exports.connect = function(cb) {
    client.connect(cfgMongoURL, function (err, db) {
        if(err) {
            console.log("Error connecting to db: " + err);
            process.exit(code=1);
        }
        cPendingURL = db.collection("pendingURL");
        cSession    = db.collection("session");
        cIniciativa = db.collection("iniciativa"); 
        cVotacion   = db.collection("votacion");
        console.log("Connected to db.");

        cVotacion.ensureIndex({ fecha:1 }, {w:1}, cb);
    });
}

// Collection: pendingURL

exports.cleanPendingURL = function(cb) {
    cPendingURL.remove(null, {w:1}, cb);
}
exports.insertPendingURL = function(url, cb) {
    if(url == null)	url = cfgStartURL; 
    cPendingURL.insert({ url: url }, {w:1}, cb);
}
exports.getPendingURL = function(cb) {
    cPendingURL.findAndRemove({}, cb);
}

// Collection: session

exports.insertIntoSession = function(data, cb) {
    cSession.findOne({ fecha: data.fecha}, function(err, item) {
        if(item)
            cb(null, false);
        else
            cSession.insert({ fecha: data.fecha, url: data.url }, {w:1}, cb);
    });
}
exports.setSessionHtml = function(id, html, cb) {
    cSession.update({ _id: id }, {$set:{html: html}}, {w:1}, cb);
}
exports.setSessionNum = function(id, num, cb) {
    cSession.update({ _id: id }, {$set:{num: num}}, {w:1}, cb);
}
exports.getSessionWithNoHtml = function(cb) {
    cSession.findOne({ html:null }, cb);
}
exports.getSessionWithNoNum = function(cb) {
    cSession.findOne({ num:null}, cb);
}

// Collection: iniciativa

exports.insertIniciativa = function(numExp, url, cb) {
    cIniciativa.insert({ numExpediente: numExp, url: url }, {w:1}, cb);
}
exports.getIniciativaWithNoHtml = function(cb) {
    cIniciativa.findOne({ html:null }, cb);
}
exports.setIniciativaHtml = function(id, html, cb) {
    cIniciativa.update({ _id: id }, {$set:{html: html}}, {w:1}, cb);
}

// Collection: votacion

exports.insertVotacion = function(url, legis, num, numExp, cb) {
    cVotacion.insert({ numExpediente: numExp, url:url, legislatura:legis, num:num }, {w:1}, cb);
}
exports.getVotacionWithNoXML = function(cb) {
    cVotacion.findOne({ xml:null }, cb);
}
exports.getVotacionAll = function(cb) {
    cVotacion.find().sort({fecha:1}, cb);
}
exports.setVotacionXML = function(id, xml, cb) {
    // TODO: insertion of fecha is not yet tested.
    // next time there is new data, TEST.
    var dte = xml.resultado.informacion.fecha.split("/"); 
    var f = new Date(dte[2], dte[1]-1, dte[0], 10, 0, 0); 
    cVotacion.update({ _id: id }, {$set:{xml: xml, fecha: f}}, {w:1}, cb);
}

// Info

function ccount(collection) {
    return function(cb) {
        collection.count(function(err, cnt) { 
            console.log(collection.db.databaseName + "." + 
                        collection.collectionName + ".count() = ", cnt); 
            cb(err); 
        }); 
    };
}
exports.info = function() {
    async.series([
        ccount(cPendingURL),
        ccount(cSession),
        ccount(cIniciativa),
        ccount(cVotacion)
    ], function(err, result) {
        console.log(err ? err : 'Done.');
    });
}

// Test

exports.test = function() {
    cSession.findOne({}, function(err, item) {
        console.log(item);
        cPendingURL.findOne({}, function(err, item) {
            console.log(item);
            process.exit(code=0);
        });
    });
}


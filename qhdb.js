var client = require('mongodb').MongoClient,
    ObjectID = require('mongodb').ObjectID,
    pendingURL,
    session;

var cfgMongoURL = 'mongodb://localhost:27017/votaciones',
    cfgStartURL = 'http://www.congreso.es/portal/page/portal/Congreso/Congreso/Actualidad/Votaciones';

// Connect

exports.connect = function(cb) {
	client.connect(cfgMongoURL, function (err, db) {
		if(err) {
			console.log("Error connecting to db: " + err);
			process.exit(code=1);
		}
		console.log("Connected to db.");
		pendingURL = db.collection("pendingURL");
		session = db.collection("session");
		cb();
	});
}

// Collection: pendingURL

exports.cleanPendingURL = function(cb) {
	pendingURL.remove(null, {w:1}, cb);
}
exports.insertPendingURL = function(url, cb) {
	if(url == null)	url = cfgStartURL; 
	pendingURL.insert({ url: url }, {w:1}, cb);
}
exports.getPendingURL = function(cb) {
    pendingURL.findAndRemove({}, cb);
}

// Collection: session

exports.insertIntoSession = function(data, cb) {
	session.findOne({ fecha: data.fecha}, function(err, item) {
		if(item)
			cb(null, false);
		else
			session.insert({ fecha: data.fecha, url: data.url }, {w:1}, cb);
	});
}
exports.setSessionHtml = function(id, html, cb) {
	session.update({ _id: id }, {$set:{html: html}}, {w:1}, cb);
}
exports.getSessionWithNoHtml = function(cb) {
    session.findOne({ html:null }, cb);
}
exports.getSessionWithNoNum = function(cb) {
    session.findOne({ num:null}, cb);
}

// Collection: iniciativa

// Collection: votacion

// Test

exports.test = function() {
	session.findOne({}, function(err, item) {
		console.log(item);
		pendingURL.findOne({}, function(err, item) {
			console.log(item);
			process.exit(code=0);
		});
	});
}


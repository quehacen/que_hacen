var client = require('mongodb').MongoClient,
    ObjectID = require('mongodb').ObjectID,
    pendingURL,
    session;

exports.pendingURL = function() { 
	return pendingURL;
}
exports.session = function() { 
	return session;
}
exports.connect = function(callback) {
	client.connect("mongodb://localhost:27017/votaciones", function (err, db) {
		if(err) {
			console.log("Error connecting to db: " + err);
			process.exit(code=1);
		}
		console.log("Connected to db.");
		pendingURL = db.collection("pendingURL");
		session = db.collection("session");
		callback();
	});
}
exports.cleanPendingURL = function(cb) {
	pendingURL.remove(null, {w:1}, cb);
}
exports.insertPendingURL = function(url, cb) {
	if(url == null)	url = 'http://www.congreso.es/portal/page/portal/Congreso/Congreso/Actualidad/Votaciones'; 
	pendingURL.insert({ url: url }, {w:1}, cb);
}
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
exports.test = function() {
	session.findOne({}, function(err, item) {
		console.log(item);
		pendingURL.findOne({}, function(err, item) {
			console.log(item);
			process.exit(code=0);
		});
	});
}


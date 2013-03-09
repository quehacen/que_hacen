// http://mongodb.github.com/node-mongodb-native/api-articles/nodekoarticle1.html
// http://docs.mongodb.org/manual/reference/javascript/
var client = require('mongodb').MongoClient;
var util = require('util');
var w1 = { w:1 };

function onInsert(err, result) {
	console.log("onInsert: " + err + ":" + result);
}

function onConnect(err, db) {
	if(err) {
		return console.dir(err);
	}
	console.log("Connected.");

	var pendingURL = db.collection('pendingURL');

	var doc1 = { 'name': 'hasbeenchanged' };
	var doc2 = { 'name': 'toprotecttheinnocent' };
	var docs = [ { 'name': 'a' }, { 'name': 'b' } ];

	// insert
	pendingURL.insert(doc1, w1, onInsert);
	pendingURL.insert(doc2, w1, onInsert);
	pendingURL.insert(docs, w1, onInsert);

	// update where name=b set newfield=bbb
	//pendingURL.update({name:'a'}, {$set:{bla:"bla."}}, {w:1,multi:true}, onInsert);

	// delete 1
	//pendingURL.remove({name:'b'}, w1, onInsert);

	// remove all
	//pendingURL.remove();

	//pendingURL.findAndRemove({}, function(err, item) {
	//	console.log("findAndRemove " + util.inspect(item));
	//});

	// findOne
	pendingURL.findOne({ impossible:1 }, function(err, item) {
		if(item) {
			console.log("found one " + util.inspect(item));
		} else {
			console.log("not found");
		}
	});

	console.log(pendingURL.count(function(err, count) {
		console.log("count:" + count);
	}));
}

client.connect("mongodb://localhost:27017/votaciones", onConnect);


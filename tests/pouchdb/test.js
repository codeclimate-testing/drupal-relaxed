var baseUrl = 'http://replicator:replicator@localhost:8080';
//PouchDB.debug.enable('*');

var getFixtures = function(url, successHandler, errorHandler) {
  var xhr = new XMLHttpRequest();
  xhr.open('get', url, true);
  xhr.responseType = 'text';
  xhr.onload = function() {
    if (xhr.status == 200) {
      // Parse the response into an array of JSON fixtures.
      var fixtures = [];
      var lines = xhr.response.split(/\r\n|\n/);
      for(var i = 0; i < lines.length; i++) {
        if (lines[i]) {
          fixtures.push(JSON.parse(lines[i]));
        }
      }
      successHandler && successHandler(fixtures);
    } else {
      errorHandler && errorHandler(xhr.status);
    }
  };
  xhr.send();
};

describe('Test replication', function () {
  it('Test basic push replication', function (done) {
    var db = new PouchDB('pouch_to_drupal');
    var remote = new PouchDB(baseUrl + '/relaxed/live');
    getFixtures(baseUrl + '/documents.txt', function(docs) {
      db.bulkDocs({ docs: docs }, {}, function (err, results) {
        db.replicate.to(remote, function (err, result) {
          result.ok.should.equal(true);
          result.doc_write_failures.should.equal(0);
          result.docs_written.should.equal(docs.length);
          done();
        });
      });
    });
  });

  it('Test basic pull replication', function (done) {
    var db = new PouchDB('drupal_to_pouch');
    var remote = new PouchDB(baseUrl + '/relaxed/live');
    getFixtures(baseUrl + '/documents.txt', function(docs) {
      db.replicate.from(remote, {}, function (err, result) {
        result.ok.should.equal(true);
        result.doc_write_failures.should.equal(0);
        // Add the 3 users that already exist in the Drupal site.
        result.docs_written.should.equal(docs.length + 3);
        done();
      });
    });
  });
});

var ConnectionManager = function (url) {
    var self = this;

    this.url = url;
    this.requests = {};

    this.onopen = function(e) {
        console.log('open', e);
        self.openDefer.resolve(self);
    };
    this.onmessage = function(e) {
//            console.log('message', e);

        var m = JSON.parse(e.data);
        if (m && m.requestId && self.requests.hasOwnProperty(m.requestId)) {
            if (m.command == 'task-status' || m.command == 'task-created' || m.command == 'task-data' || m.command == 'task-complete') {
                self.requests[m.requestId].notify(m.workload, m.command);
            } else {
                self.requests[m.requestId].resolve(m.workload);
                delete self.requests[m.requestId];
            }
        }
    };
    this.onclose = function(e) {
        console.log('close', e);
        self.openDefer.reject(self);

        Object.keys(self.requests).forEach(function(key) {
            self.requests[key].reject(self);
            delete self.requests[key];
        });
    };
    this.onerror = function(e) {
        console.log('error', e);
        self.openDefer.reject(self);
    };
};

ConnectionManager.prototype.open = function(url) {
    url = url || this.url;

    this.openDefer = new $.Deferred;

    this.ws = new WebSocket(url);
    this.ws.onopen = this.onopen;
    this.ws.onmessage = this.onmessage;
    this.ws.onclose = this.onclose;
    this.ws.onerror = this.onerror;

    return this.openDefer.promise();
};
ConnectionManager.prototype.close = function(reason) {
    this.ws.close(reason);
};
ConnectionManager.prototype.send = function(command) {
    var key = (new Date()).getTime();

    var defer = new $.Deferred;

    this.requests[key] = defer;
    command.requestId = key;

    this.ws.send(command);

    return defer.promise();
};
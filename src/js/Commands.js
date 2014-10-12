var Commands = {};

Commands.Ping = function () { };
Commands.Ping.prototype.toString = function() {
    return JSON.stringify({
        command: 'ping',
        workload: (new Date).getTime() / 1000,
        requestId: this.requestId
    });
};

Commands.Fetch = function(url) {
    this.url = url;
};
Commands.Fetch.prototype.toString = function() {
    return JSON.stringify({
        command: 'fetch',
        workload: this.url,
        requestId: this.requestId
    });
};

Commands.DoBackground = function(name, data, priority) {
    this.name = name;
    this.data = data;
    this.priority = priority;
};
Commands.DoBackground.prototype.toString = function() {
    return JSON.stringify({
        command: 'do-background',
        workload: {
            name: this.name,
            data: this.data,
            priority: this.priority
        },
        requestId: this.requestId
    });
};

Commands.JobStatus = function(handle) {
    this.handle = handle;
};
Commands.JobStatus.prototype.toString = function() {
    return JSON.stringify({
        command: 'job-status',
        workload: this.handle,
        requestId: this.requestId
    });
};

Commands.RunTasks = function(tasks) {
    this.tasks = tasks;
};
Commands.RunTasks.prototype.toString = function() {
    return JSON.stringify({
        command: 'run-tasks',
        workload: this.tasks,
        requestId: this.requestId
    });
};
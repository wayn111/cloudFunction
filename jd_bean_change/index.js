module.exports = (event, context, callback) => {
    var cp = require("child_process");

    cp.execFile("node", ["./" + event["Message"] + ".js"], function (err, stdout, stderr) {
        if (err) {
            console.error(err);
        }
    });
}

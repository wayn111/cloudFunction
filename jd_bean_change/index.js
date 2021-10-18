module.exports = (event, context, callback) => {
    console.log(event);
    callback(null, {code: 0});
}

//'use strict';
exports.main_handler = async (event, context, callback) => {
// 云函数执行入口
    require('./jd_bean_change.js');
}

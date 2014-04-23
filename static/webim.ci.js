//custom
(function(webim) {
	var path = _IMC.path;
	webim.extend(webim.setting.defaults.data, _IMC.setting);
    var cookie_key = "_webim_cookie_";
	if( _IMC.is_visitor ) { cookie_key = "_webim_v_cookie_"; }
    if( _IMC.user != "" ) { cookie_key = cookie_key + _IMC.user.id; }
    webim.status.defaults.key = cookie_key;
	webim.route( {
		online: path + "/index.php/webim/online",
		offline: path + "/index.php/webim/offline",
		deactivate: path + "/index.php/webim/refresh",
		message: path + "/index.php/webim/message",
		presence: path + "/index.php/webim/presence",
		status: path + "/index.php/webim/status",
		setting: path + "/index.php/webim/setting",
		history: path + "/index.php/webim/history",
		clear: path + "/index.php/webim/clear_history",
		download: path + "/index.php/webim/download_history",
		buddies: path + "/index.php/webim/buddies",
        //room actions
		invite: path + "/index.php/webim/invite",
		join: path + "/index.php/webim/join",
		leave: path + "/index.php/webim/leave",
		block: path + "/index.php/webim/block",
		unblock: path + "/index.php/webim/unblock",
		members: path + "/index.php/webim/members",
        //notifications
		upload: path + "/static/images/upload.php",
		notifications: path + "index.php/webim/notifications"
	} );

	webim.ui.emot.init({"dir": path + "/static/images/emot/default"});
	var soundUrls = {
		lib: path + "/static/assets/sound.swf",
		msg: path + "/static/assets/sound/msg.mp3"
	};
	var ui = new webim.ui(document.body, {
		imOptions: {
			jsonp: _IMC.jsonp
		},
		soundUrls: soundUrls,
		//layout: "layout.popup",
        layoutOptions: {
            unscalable: _IMC.is_visitor
        },
		buddyChatOptions: {
            downloadHistory: !_IMC.is_visitor,
			//simple: _IMC.is_visitor,
			upload: _IMC.upload && !_IMC.is_visitor
		},
		roomChatOptions: {
            downloadHistory: !_IMC.is_visitor,
			upload: _IMC.upload
		}
	}), im = ui.im;
    //全局化
    window.webimUI = ui;

	if( _IMC.user ) im.setUser( _IMC.user );
	if( _IMC.menu ) ui.addApp("menu", { "data": _IMC.menu } );
	if( _IMC.enable_shortcut ) ui.layout.addShortcut( _IMC.menu );

	ui.addApp("buddy", {
		showUnavailable: _IMC.show_unavailable,
		is_login: _IMC['is_login'],
		disable_login: true,
		collapse: false,
		//disable_user: _IMC.is_visitor,
        //simple: _IMC.is_visitor,
		loginOptions: _IMC['login_options']
	});
    if(!_IMC.is_visitor) {
        if( _IMC.enable_room )ui.addApp("room", { discussion: (_IMC.discussion && !_IMC.is_visitor) });
        if(_IMC.enable_noti )ui.addApp("notification");
    }
    if(_IMC.enable_chatlink) ui.addApp("chatbtn");
    ui.addApp("setting", {"data": webim.setting.defaults.data, "copyright": true});
	ui.render();
	_IMC['is_login'] && im.autoOnline() && im.online();
})(webim);

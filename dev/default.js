(function($){
	c4d_social_login.nonce = null;
	c4d_social_login.fb = {
		checkLogin: function(callback) {
			alert(1);
			FB.getLoginStatus(function(response) {
				callback(response);
			});
		},
		statusCallback: function(response) {
			if (response.status === 'connected') {
		      	// Logged into your app and Facebook.
		      	c4d_social_login.fb.validateUser(response);
		    } else if (response.status === 'not_authorized') {
		      	// The person is logged into Facebook, but not your app.
		    	c4d_social_login.fb.login();
		    } else {
				// The person is not logged into Facebook, so we're not sure if
				// they are logged into this app or not.
				c4d_social_login.fb.login();
		    }
		},
		validateUser: function(res) {
			$.get(c4d_social_login.ajax_url, { 
				'action': 'c4d_social_login_validate', 
				'model': 'fb',
				'data': res,
				'security': c4d_social_login.nonce
				}, function(res){
					if (res != 'false') {
						var url = window.location.href,
						blankIndex = url.indexOf('#');
						if (blankIndex == (url.length - 1)) {
							url = url.substring(0, blankIndex);
						}
						window.location.href = url;	
					}
				}
			);
		},
		login: function() {
			FB.login(function(response) {
			   c4d_social_login[c4d_social_login.type].validateUser(response);
			}, {
				scope: 'public_profile,email',
				return_scopes: true,
                auth_type: 'rerequest'
			}); 
		}
	};

	c4d_social_login.google = {
		button: '.c4d-social-login.google > a',
		init: function() {
			$.getScript( "https://apis.google.com/js/platform.js", function( data, textStatus, jqxhr ) {
				gapi.load('auth2', function(){
					// Retrieve the singleton for the GoogleAuth library and set up the client.
					gapi.auth2.init({
						client_id: '236292181654-1p6hm55bcu6apgj5acleuaa19mfe213o.apps.googleusercontent.com',
						cookiepolicy: 'single_host_origin',
						// Request scopes in addition to 'profile' and 'email'
						//scope: 'additional_scope'
					}).then(function(){
						var auth2 = gapi.auth2.getAuthInstance();
    					
						$(c4d_social_login.google.button).each(function(index, el){
							auth2.attachClickHandler(
								el, 
								{}, 
								function(googleUser) { // login success
						        	c4d_social_login.google.validateUser(googleUser);
						     	}, 
						     	function(error) { // login fail
						     	}
							);
						});
    					
					});
					// bind click event to login button
					
				});	  
			});
		},
		checkLogin: function(callback) {
			// do nothing
		},
		validateUser: function(res) {
			res = res.getAuthResponse();
			$.get(c4d_social_login.ajax_url, { 
				'action': 'c4d_social_login_validate', 
				'model': 'google',
				'data': res,
				'security': c4d_social_login.nonce
				}, function(res){
					if (res != 'false') {
						var url = window.location.href,
						blankIndex = url.indexOf('#');
						if (blankIndex == (url.length - 1)) {
							url = url.substring(0, blankIndex);
						}
						window.location.href = url;
					}
				}
			);
		}
	};

	c4d_social_login.checkLogin = function(el, type) {
		c4d_social_login.button = el;
		c4d_social_login.nonce = $(el).attr('data-nonce');
		c4d_social_login.type = type;
		c4d_social_login[c4d_social_login.type].checkLogin(c4d_social_login.statusCallback);
	};
	c4d_social_login.statusCallback = function(response) {
		c4d_social_login[c4d_social_login.type].statusCallback(response);
	};
	c4d_social_login.validateUser = function(res) {
		// nothing
	};
	c4d_social_login.login = function() {
		// nothing
	};
	window.fbAsyncInit = function() {
		FB.init({
			appId      : '1247267475358846',
			cookie     : true,  // enable cookies to allow the server to access 
			                    // the session
			xfbml      : true,  // parse social plugins on this page
			version    : 'v2.8' // use graph api version 2.8
		});
	};
	// // Load the SDK asynchronously
	// (function(d, s, id) {
	// 	var js, fjs = d.getElementsByTagName(s)[0];
	// 	if (d.getElementById(id)) return;
	// 	js = d.createElement(s); js.id = id;
	// 	js.src = "https://connect.facebook.net/en_US/sdk.js";
	// 	fjs.parentNode.insertBefore(js, fjs);
	// }(document, 'script', 'facebook-jssdk'));

	// // Load the SDK asynchronously
	// (function(d, s, id) {
	// 	var js, fjs = d.getElementsByTagName(s)[0];
	// 	if (d.getElementById(id)) return;
	// 	js = d.createElement(s); js.id = id;
	// 	js.src = "https://apis.google.com/js/platform.js";
	// 	fjs.parentNode.insertBefore(js, fjs);
	// }(document, 'script', 'google-api'));
	
	$(document).ready(function(){
		// start google
		c4d_social_login.google.init();
		// start facebook
		$.getScript( "//connect.facebook.net/en_US/sdk.js", function( data, textStatus, jqxhr ) {
		});
	});
})(jQuery);
/**
  * @package iPublikuj:cms!
  * @copyright	Copyright (C) 2007. All rights reserved.
  * @license http://www.studio81.cz
  * @author Adam Kadlec http://www.studio81.cz
*/

/**
 * iPublikuj 4.0 mod_cck comments js functions
 *
 * @author		Adam Kadlec http://www.datahousing.eu
 * @package		iPublikuj:cms!
 * @since		4.0
 * @version  	1.0
 */

/**
 * Item comments form
 */

(function ($) {
	var plg = function () {};

	$.extend(plg.prototype, {
		name: "Comment",

		options: {
			cookiePrefix	: "ipub-comment_",
			cookieLifetime	: 15552E3
		},

		initialize: function (element, options) {
			this.options = $.extend({}, this.options, options);

			var obj		= this,
				form	= $("#respond");

			form.find(".actions .cancel").bind("click", function (e) {
				e.preventDefault();

				form.appendTo($("#comments").find(".comments"));
				form.find("input[name=parent]").val(0)
			});

			form.find("form").bind("submit", function (e) {
				// Show waiting message
				$(".submit-message", form).addClass("submitting");
			});

			form.find("a.facebook-connect").bind("click", function () {
				obj.setLoginCookie("facebook")
			});

			form.find("a.facebook-logout").bind("click", function () {
				obj.setLoginCookie("")
			});

			form.find("a.twitter-connect").bind("click", function () {
				obj.setLoginCookie("twitter")
			});

			form.find("a.twitter-logout").bind("click", function () {
				obj.setLoginCookie("")
			});

			$("#comments .comment").each(function () {
				var elm = $(this);
				elm.find(".reply a").bind("click", function (event) {
					event.preventDefault();

					form.appendTo(elm);
					form.find("input[name=parent]").val(elm.attr("id").replace(/comment-/i, ""))
				})
			})
		},

		setLoginCookie: function (value) {
			$.cookie(this.options.cookiePrefix + "login", value, {
				expires: this.options.cookieLifetime / 86400,
				path: "/"
			})
		}
	});

	$.fn[plg.prototype.name] = function () {
		var args = arguments,
			method = args[0] ? args[0] : null;

		return this.each(function () {
			var obj = $(this);

			if ( plg.prototype[method] && obj.data(plg.prototype.name) && method != "initialize" ) {
				obj.data(plg.prototype.name)[method].apply(obj.data(plg.prototype.name), Array.prototype.slice.call(args, 1));

			} else if (!method || $.isPlainObject(method)) {
				var elm = new plg;

				plg.prototype.initialize && elm.initialize.apply(elm, $.merge([obj], args));
				obj.data(plg.prototype.name, elm);

			} else {
				$.error("Method " + method + " does not exist on jQuery." + plg.name)
			}
		});
	}

})(jQuery);

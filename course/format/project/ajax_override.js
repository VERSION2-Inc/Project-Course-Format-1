/**
 * AJAXコース編集機能オーバーライド
 * 
 * $Id: ajax_override.js 333 2009-07-03 09:26:40Z malu $
 */

(function ()
{
	if (typeof section_class != "undefined") {
		
		// セクション編集ボタン群に削除ボタンを残すためのオーバーライド
		var original_init_buttons = section_class.prototype.init_buttons;
		section_class.prototype.init_buttons = function ()
		{
			var commandContainer = this.getEl().childNodes[2];
			
			// 削除ボタンを退避
			var deleteButton = null;
			var buttons = commandContainer.getElementsByTagName("a");
			for (var i = 0; i < buttons.length; i++) {
				if (buttons[i].href && buttons[i].href.indexOf("/deletesection.php") >= 0) {
					deleteButton = buttons[i];
					break;
				}
			}
			
			// オリジナルのメソッドを呼ぶ
			original_init_buttons.call(this);
			
			// 退避した削除ボタンを戻す
			if (deleteButton) {
				commandContainer.appendChild(deleteButton);
			}
		};
	}
	
})();

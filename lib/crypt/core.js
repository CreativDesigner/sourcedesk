$(document).ready(function () {
	$("#modal-login-do").bind('click', function (e) {
		e.preventDefault();
		$("#modal-login-hashed").val(hashPassword($("#modal-login-password").val()).toString());
		$("#modal-login-form").unbind('submit');
		$("#modal-login-form").append('<input type="hidden" name="login" value="do" />');
		$("#modal-login-form").submit();
	});

	$("#login-do").bind('click', function (e) {
		e.preventDefault();
		$("#login-hashed").val(hashPassword($("#login-password").val()).toString());
		$("#login-password").removeAttr('name');
		$("#login-hashed").attr('name', 'password');
		$("#login-form").unbind('submit');
		$("#login-form").append('<input type="hidden" name="login" value="do" />');
		$("#login-form").submit();
	});

	$("#reset-password-form").bind('submit', function (e) {
		e.preventDefault();
		var failed = false;

		if($("#reset-password-newpw").val().trim().length < 8){
			$("#reset-password-newpw-hashed").val($("#reset-password-newpw").val() == $("#reset-password-newpw2").val() ? "x" : "y");
			failed = true;
		} else {
			$("#reset-password-newpw-hashed").val(hashPassword($("#reset-password-newpw").val()).toString());
		}

		$("#reset-password-newpw").removeAttr('name');
			$("#reset-password-newpw-hashed").attr('name', 'newpw');
		
		if($("#reset-password-newpw2").val().trim().length < 8){
			$("#reset-password-newpw2-hashed").val("x");
			failed = true;	
		} else {
			$("#reset-password-newpw2-hashed").val(hashPassword($("#reset-password-newpw2").val()).toString());
		}

		$("#reset-password-newpw2").removeAttr('name');
		$("#reset-password-newpw2-hashed").attr('name', 'newpw2');

		if(!failed)
			$("#password_type").val("hashed");
		$("#reset-password-form").unbind('submit');
		$("#reset-password-form").append('<input type="hidden" name="change" value="do" />');
		$("#reset-password-form").submit();
	});

	$("#set-password-form").bind('submit', function (e) {
		e.preventDefault();
		var failed = false;

		if($("#pwd").val().trim().length < 8) {
			$("#set-password-pwd-hashed").val($("#pwd").val() == $("#pwd2").val() ? "x" : "y");
			failed = true;
		} else {
			$("#set-password-pwd-hashed").val(hashPassword($("#pwd").val()).toString());
		}

		$("#pwd").removeAttr('name');
		$("#set-password-pwd-hashed").attr('name', 'pwd');
		
		if($("#pwd2").val().trim().length < 8){
			$("#set-password-pwd2-hashed").val("x");
			failed = true;
		} else {
			$("#set-password-pwd2-hashed").val(hashPassword($("#pwd2").val()).toString());
		}

		$("#pwd2").removeAttr('name');
		$("#set-password-pwd2-hashed").attr('name', 'pwd2');

		if(!failed)
			$("#password_type").val("hashed");
		
		$("#set-password-form").unbind('submit');
		$("#set-password-form").append('<input type="hidden" name="setpw" value="do" />');
		$("#set-password-form").submit();
	});

	$("#profile-form").bind('submit', function (e) {
		e.preventDefault();

		if($("#profile-pwd").val().trim().length > 0){
			if($("#profile-pwd").val().trim().length < 8){
				$("#profile-pwd-hashed").val("x");
			} else {
				$("#profile-pwd-hashed").val(hashPassword($("#profile-pwd").val()).toString());
				$("#password_type").val("hashed");
			}
			
			$("#profile-pwd").removeAttr('name');
			$("#profile-pwd-hashed").attr('name', 'p_pwd');
		}

		$("#profile-form").unbind('submit');
		$("#profile-form").append('<input type="hidden" name="p_submit" value="do" />');
		$("#profile-form").submit();
	});

	$("#order-form").bind('submit', function (e) {
		e.preventDefault();

		if($("#custh").val() == "new"){
			$("#pwl").val($("#reg_pw1").val().length);
			$("#reg_pw1").val(hashPassword($("#reg_pw1").val()).toString());
			$("#reg_pw2").val(hashPassword($("#reg_pw2").val()).toString());
		} else if($("#custh").val() == "login"){
			$("#login_password").val(hashPassword($("#login_password").val()).toString());
		}

		$("#order-form").unbind('submit');
		$("#order-form").submit();
	});

	function hashPassword(password) {
		switch(hash_method) {
			case "md5":
				return CryptoJS.MD5(password);
		        break;
		    case "sha1":
		    	return CryptoJS.SHA1(password);
		        break;
		    case "sha256":
		    	return CryptoJS.SHA256(password);
		   		break;
		   	case "sha512":
		   	case "sha512salt":
		   		return CryptoJS.SHA512(password);
		   		break;
		    default:
		        return password;
		}
	}
});
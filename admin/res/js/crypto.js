$(document).ready(function () {
    // Method for hashing
	function hashPassword(password) {
        if (hash_method === "md5") {
            return CryptoJS.MD5(password);
        } else if (hash_method === "sha1") {
            return CryptoJS.SHA1(password);
        } else if (hash_method === "sha256") {
            return CryptoJS.SHA256(password);
        } else if (hash_method === "sha512" || hash_method === "sha512salt") {
            return CryptoJS.SHA512(password);
        } else {
            return password;
        }
	}

    // Admin login
    doing = 0;

	$("#login-button").click(function (e) {
        e.preventDefault();

        if (doing) {
            return;
        }

        doing = 1;

        $("#loginForm").slideUp(function() {
            $("#doingLogin").slideDown();
            $("#login_error").hide();

            $.post("./", {
                "login": "do",
                "user": $("[name=user]").val(),
                "hashed": hashPassword($("#password").val()) + "",
                "cookie": $("[name=cookie]").is(":checked") ? "1" : "0",
                "ajax": "1",
            }, function(r) {
                doing = 0;
    
                if (r == "ok") {
                    window.location = "./index.php";
                } else if (r == "fail") {
                    $("#doingLogin").slideUp(function() {
                        $("#login_error").show();
                        $("#loginForm").slideDown();
                    });
                } else if (r == "blocked") {
                    window.location = "../locked";
                } else if (r == "tfa") {
                    $("#doingLogin").slideUp(function() {
                        $("#tfaForm").slideDown();

                        $("#tfa-button").unbind("click").click(function(e) {
                            e.preventDefault();

                            if (doing) {
                                return;
                            }

                            doing = 1;

                            $("#tfaForm").slideUp(function() {
                                $("#doingLogin").slideDown();

                                $.post("./", {
                                    "login": "do",
                                    "user": $("[name=user]").val(),
                                    "hashed": hashPassword($("#password").val()) + "",
                                    "cookie": $("[name=cookie]").is(":checked") ? "1" : "0",
                                    "ajax": "1",
                                    "2fa": $("[name=2fa]").val(),
                                }, function(r) {
                                    doing = 0;

                                    if (r == "ok"){
                                        window.location = "./index.php";
                                    } else if (r == "fail") {
                                        $("#tfaError").show();

                                        $("#doingLogin").slideUp(function() {
                                            $("#tfaForm").slideDown();
                                        });
                                    } else if (r == "blocked") {
                                        window.location = "../locked";
                                    }
                                });
                            });
                        });
                    });
                }            
            });
        });
	});

    // Admin password lost
    $("#password_reset").submit(function () {
        if ($("#new").val().trim().length < 8) {
            $("#new2").val($("#new").val() == $("#new2").val() ? "x" : "y");
            $("#new").val("x");
        } else {
            $("#new_hashed").val(hashPassword($("#new").removeAttr("name").val())).attr("name", "new");
            $("#new2_hashed").val(hashPassword($("#new2").removeAttr("name").val())).attr("name", "new2");
            $("#pw_type").val("hashed");
        }
    });

    // Admin password change
    $("#change_password").submit(function () {
        var fields = ["old_pwd", "pwd", "pwd2"];
        fields.forEach(function (field) {
            var pwd = $("#" + field).val();
            if (field == "pwd" && (pwd.length < 8 || pwd.replace(/\D/g, '').length < 1)) {
                $("#pwd").val("x");
            } else {
                $("#" + field + "_hashed").val(hashPassword(pwd)).attr("name", field);
                $("#" + field).removeAttr("name");
            }
        });
        $("#pw_type").val("hashed");
    });

    // Add / edit admin form
    $("#edit_admin").submit(function () {
        if ($("#pwd").val().trim().length < 8) {
            $("#pwd").val("x");
        } else if ($("#pwd").val().trim().length > 0) {
            $("#pwd_hashed").val(hashPassword($("#pwd").remoteAttr("name").val())).attr("name", "password");
            $("#pw_type").val("hashed");
        }
    });
});
<?php
global $var, $captcha, $session, $val, $CFG, $lang, $db;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$title = $lang['CONTACT']['TITLE'];
$tpl = "contact";

$var['step'] = 1;

// Contact form sent
if (isset($_POST['name'])) {
    try {
        // Check token
        if (empty($_POST['token']) || $_POST['token'] != $session->get("token")) {
            throw new Exception($lang['CONTACT']['TOKEN']);
        }

        // Check if the captcha is correct
        if (!$var['logged_in'] && !$captcha->verify() && $session->get('captcha_solved_reg') != 1) {
            throw new Exception($lang['CONTACT']['CAPTCHA']);
        }

        // The client only have to solve one captcha within one form submission
        $session->set('captcha_solved_reg', 1);

        if (empty($_POST['name'])) {
            throw new Exception($lang['CONTACT']['NAME_MISSING']);
        }

        if (empty($_POST['email'])) {
            throw new Exception($lang['CONTACT']['MAIL_MISSING']);
        }

        if (!$val->email($_POST['email'])) {
            throw new Exception($lang['CONTACT']['MAIL_FAILED']);
        }

        if (empty($_POST['message'])) {
            throw new Exception($lang['CONTACT']['MESSAGE_MISSING']);
        }

        // Check if ticket system receives email
        $sql = $db->query("SELECT dept FROM support_email WHERE email LIKE '" . $db->real_escape_string($CFG['PAGEMAIL']) . "' ORDER BY pop3 DESC LIMIT 1");
        if ($sql->num_rows == 1) {
            // Send via ticket system
            $dept = $sql->fetch_object()->dept;

            $customer = 0;
            $sql = $db->query("SELECT ID FROM clients WHERE mail = '" . $db->real_escape_string($_POST['email']) . "'");
            if ($sql->num_rows == 1) {
                $customer = $sql->fetch_object()->ID;
            }

            if (!$customer) {
                $sql = $db->query("SELECT client FROM client_contacts WHERE mail = '" . $db->real_escape_string($_POST['email']) . "'");
                if ($sql->num_rows == 1) {
                    $customer = $sql->fetch_object()->client;
                }

            }

            $sender = $_POST['name'] . " <{$_POST['email']}>";

            $db->query("INSERT INTO support_tickets (subject, dept, created, updated, priority, sender, customer, cc, status) VALUES ('Kontaktformular', {$dept}, '" . date("Y-m-d H:i:s") . "', '" . date("Y-m-d H:i:s") . "', 3, '" . $db->real_escape_string($sender) . "', $customer, '', 0)");
            $id = $db->insert_id;
            $db->query("INSERT INTO support_ticket_answers (ticket, subject, message, priority, sender, time) VALUES ($id, 'Kontaktformular', '" . $db->real_escape_string($_POST['message']) . "', 3, '" . $db->real_escape_string($sender) . " (via Kontaktformular, " . $db->real_escape_string(ip()) . ")', '" . date("Y-m-d H:i:s") . "')");
            $t = new Ticket($id);
            $t->notify();
        } else {
            // Send mail
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->SetFrom($CFG['MAIL_SENDER'], $CFG['PAGENAME']);
            $mail->AddReplyTo($_POST['email'], $_POST['name']);
            $mail->AddAddress($CFG['MAIL_SENDER']);
            $mail->Subject = str_replace("%p", $CFG['PAGENAME'], $lang['CONTACT']['SUBJECT']);
            $mail->Body = $_POST['message'];

            // If mails should be send through SMTP, set SMTP data to object
            if ($CFG['MAIL_TYPE'] == "smtp") {
                $mail->IsSMTP();
                $mail->Host = $CFG['SMTP_HOST'];

                // Specify credentials if needed
                if (!empty($CFG['SMTP_USER']) || !empty($CFG['SMTP_PASSWORD'])) {
                    $mail->SMTPAuth = true;
                    if ($CFG['SMTP_SECURITY'] == "tls" || $CFG['SMTP_SECURITY'] == "ssl") {
                        $mail->SMTPSecure = $CFG['SMTP_SECURITY'];
                    }

                    $mail->Username = $CFG['SMTP_USER'];
                    $mail->Password = $CFG['SMTP_PASSWORD'];
                }
            }

            // Try to send mail via PHPmailer
            if (!$mail->Send()) {
                throw new Exception($lang['CONTACT']['TEMP']);
            }

        }

        // Reset captcha flag and show success information
        $session->set('captcha_solved_reg', 0);
        $var['step'] = 2;
    } catch (Exception $ex) {
        $var['error'] = $ex->getMessage();
    }
}

// Generate captcha and save it into session
$myCaptcha = $captcha->get();
if ($session->get('captcha_solved_reg') == 1) {
    unset($myCaptcha);
}

if (is_array($myCaptcha) && isset($myCaptcha['type']) && $myCaptcha['type'] == "text") {
    $var['captchaText'] = $myCaptcha['value'];
} else if (isset($myCaptcha['type']) && $myCaptcha['type'] == "modal") {
    $var['captchaModal'] = $myCaptcha['value'];
} else if (isset($myCaptcha['type']) && $myCaptcha['type'] == "code") {
    $var['captchaCode'] = $myCaptcha['value'];
}

if (isset($myCaptcha['exec'])) {
    $var['captchaExec'] = $myCaptcha['exec'];
}

$session->set("token", $var['token'] = md5(rand(10000000, 99999999)));

// Set JavaScript for validation
$var['additionalJS'] .= "$('#contact-form').bootstrapValidator({
        message: '',
        feedbackIcons: {
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            name: {
                validators: {
                    notEmpty: {
                        message: '{$lang['CONTACT']['NAME_MISSING']}'
                    }
                }
            },
            email: {
                validators: {
                    notEmpty: {
                        message: '{$lang['CONTACT']['MAIL_MISSING']}'
                    },
                    emailAddress: {
                        message: '{$lang['CONTACT']['MAIL_FAILED']}'
                    }
                }
            },
            message: {
                validators: {
                    notEmpty: {
                        message: '{$lang['CONTACT']['MESSAGE_MISSING']}'
                    }
                }
            }
        }
    });

function form_validate(attr_id){
    var result = true;
    $('#'+attr_id).bootstrapValidator('validate');
    $('#'+attr_id+' .form-group').each(function(){
        if($(this).hasClass('has-error')){
            result = false;
            return false;
        }
    });
    return result;
}

function openCaptchaModal() {
    $('#captchaModal').modal({
        keyboard: false,
        backdrop: 'static',
        show: false,
    });

    $('#captchaModal').modal('show');
    $('#captchaSubmit').prop('disabled', false);
}

$('.enter-disallow').bind('keypress', function(e)
{
   if(e.keyCode == 13)
   {
      return false;
   }
});";

$var['paymentJS'] .= '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.bootstrapvalidator/0.5.2/js/bootstrapValidator.min.js"></script>';

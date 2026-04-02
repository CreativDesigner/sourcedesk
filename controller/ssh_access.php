<?php
global $db, $CFG, $pars;

$serverId = intval($pars[0] ?? 0);
$sshHash = $db->real_escape_string($pars[1] ?? "");

$serverHost = $db->real_escape_string($pars[2] ?? "");
$serverPort = intval($pars[3] ?? 22);
$operatingSystem = $db->real_escape_string($pars[4] ?? "");

$sql = $db->query("SELECT * FROM monitoring_server WHERE ID = $serverId AND ssh_hash = '$sshHash' AND ssh_valid >= " . time() . " AND ssh_host = ''");

if ($serverHost) {
    if (!$sql->num_rows) {
        die("Unfortunately, the link specified is not valid\nPlease refresh server page in sourceDESK and get new link");
    }
    $server = $sql->fetch_object();

    if (empty($serverHost)) {
        die("Invalid SSH connection target");
    }

    if ($serverPort > 65535) {
        die("Invalid SSH port");
    }

    if (!in_array($operatingSystem, ["U", "D", "R"])) {
        die("Invalid operating system");
    }

    if (filter_var($serverHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $serverHost = "[" . $serverHost . "]";
    }

    $ssh = new phpseclib\Net\SSH2($serverHost, $serverPort);

    try {
        $hostKey = $ssh->getServerPublicHostKey();
        if (!$hostKey) {
            die("SSH connection failed");
        }

        $key = new phpseclib\Crypt\RSA;
        $key->load(decrypt($server->ssh_key));

        if (!$ssh->login("root", $key)) {
            die("SSH login failed");
        }
    } catch (RuntimeException $ex) {
        die($ex->getMessage());
    }

    $db->query("UPDATE monitoring_server SET operating_system = '$operatingSystem', ssh_host = '$serverHost', ssh_port = $serverPort, ssh_fingerprint_last = '" . $db->real_escape_string($hostKey) . "', ssh_last = " . time() . " WHERE ID = $serverId");

    die("ok");
}

if (!$sql->num_rows) {
    header("Content-Type: application/x-sh");
    echo "#!/bin/bash\n";
    echo "echo ''\n";
    echo "echo -e \"\e[1;31mUnfortunately, the link specified is not valid\e[0m\" 1>&2\n";
    echo "echo \"Please refresh server page in sourceDESK and get new link\"\n";
    echo 'echo ""';
    exit;
}
$server = $sql->fetch_object();
$rsa = new phpseclib\Crypt\RSA;
$rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_OPENSSH);
$result = $rsa->createKey(2048);

$prvkey = $result['privatekey']->getPrivateKey();
$pubkey = trim(str_replace('phpseclib-generated-key', 'sourceDESK', $result['publickey']->getPublicKey('OpenSSH')));

$db->query("UPDATE monitoring_server SET ssh_key = '" . $db->real_escape_string(encrypt($prvkey)) . "' WHERE ID = $serverId");

header("Content-Type: application/x-sh");
?>
#!/bin/bash
# Do not change anything in this script
echo ""
echo -e "\e[1;32mWelcome to sourceDESK!\e[0m"
echo "We are now connecting your server with sourceDESK, please be patient."
echo ""

echo "Checking prerequirements...";

if [ "$(id -u)" != "0" ]; then
    echo -e "\e[1;31mThis script must be run as root" 1>&2
    echo -e "\e[0m"
    exit 1
fi
echo -e "\e[0;32mRoot access granted\e[0m"

if [[ $(awk -F '=' '/DISTRIB_ID/ { print $2 }' /etc/*-release 2>/dev/null) == "Ubuntu" ]]; then
    BACKEND="sudo apt-get"
    SYSTEM="U"
    echo -e "\e[0;32mUbuntu system detected\e[0m"
elif [ -f /etc/debian_version ]; then
    BACKEND="apt-get"
    SYSTEM="D"
    echo -e "\e[0;32mDebian system detected\e[0m"
elif [ -f /etc/redhat-release ]; then
    BACKEND="yum"
    SYSTEM="R"
    echo -e "\e[0;32mRedhat/CentOS system detected\e[0m"
else
    echo -e "\e[1;31mThis opering system is not supported" 1>&2
    echo -e "\e[0m"
    exit 1
fi

HOSTNAME=$(wget -q -O - <?=$CFG['PAGEURL'];?>ip)
read -p "Please enter SSH host [$HOSTNAME]: " ui

if ! [[ -z "$ui" ]]
then
    HOSTNAME=$ui
fi

xargs -n 1 -0 < /proc/${$}/environ | sed -n 's/^ENV_VAR_NAME=\(.*\)/\1/p'

SSHPORT=${SSH_CONNECTION##* }

read -p "Please enter SSH port [$SSHPORT]: " ui

if ! [[ -z "$ui" ]]
then
    SSHPORT=$ui
fi

if ! hash wget 2>/dev/null; then
    echo "Dependency Wget will be installed"
    ${BACKEND} -y install wget &> /dev/null

    if ! hash wget 2>/dev/null; then
        echo -e "\e[1;31mInstallation of Wget failed" 1>&2
        echo -e "\e[0m"
        exit 1
    fi

    echo -e "\e[0;32mWget installed successfully\e[0m"
else
    echo -e "\e[0;32mDependency wget found\e[0m"
fi

echo -e "\e[1;32mPrerequirements are met\e[0m"
echo ""
echo "Activating sourceDESK access...";

rm /usr/local/bin/sourcedesk-rsh &> /dev/null
sed -i '/sourceDESK/d' ~/.ssh/authorized_keys &> /dev/null
sed -i '/sourceDESK/d'/etc/ssh/sshd_config &> /dev/null
rm /usr/local/bin/sourcedesk-remove &> /dev/null

echo -e "\e[0;32mPrevious sourceDESK connections removed (if any)\e[0m"

cat <<\EOT >> /usr/local/bin/sourcedesk-rsh
#!/bin/bash
# Restricted shell of sourceDESK

if [[ -z $SSH_ORIGINAL_COMMAND ]]
then
    echo "sourceDESK restricted shell: interactive shell not allowed"
    exit 1
fi

case $SSH_ORIGINAL_COMMAND in
    "apt-get autoclean -y" | "sudo apt-get autoclean -y" | \
    "apt-get update -qq" | "sudo apt-get update -qq" | \
    "apt-get upgrade -s" | "sudo apt-get upgrade -s" | \
    "dpkg --configure -a" | "yum check-update -q" | "/usr/local/bin/sourcedesk-remove")
        bash -c "$SSH_ORIGINAL_COMMAND"
        ;;
    "yum update "*)
        SUBSTRING=$(echo $SSH_ORIGINAL_COMMAND | cut -c -12)
        if [[ "$SUBSTRING" =~ [^a-zA-Z0-9\-\ ] ]]; then
            echo "sourceDESK restricted shell: command not allowed: $SSH_ORIGINAL_COMMAND"
            exit 2
        fi
        bash -c "$SSH_ORIGINAL_COMMAND"
        ;;
    "apt-get install --only-upgrade "*)
        SUBSTRING=$(echo $SSH_ORIGINAL_COMMAND | cut -c -32)
        if [[ "$SUBSTRING" =~ [^a-zA-Z0-9\-\ ] ]]; then
            echo "sourceDESK restricted shell: command not allowed: $SSH_ORIGINAL_COMMAND"
            exit 2
        fi
        bash -c "$SSH_ORIGINAL_COMMAND"
        ;;
    "sudo apt-get install --only-upgrade "*)
        SUBSTRING=$(echo $SSH_ORIGINAL_COMMAND | cut -c -37)
        if [[ "$SUBSTRING" =~ [^a-zA-Z0-9\-\ ] ]]; then
            echo "sourceDESK restricted shell: command not allowed: $SSH_ORIGINAL_COMMAND"
            exit 2
        fi
        bash -c "$SSH_ORIGINAL_COMMAND"
        ;;
    *)
        echo "sourceDESK restricted shell: command not allowed: $SSH_ORIGINAL_COMMAND"
        exit 2
        ;;
esac
EOT
chmod +x /usr/local/bin/sourcedesk-rsh
echo -e "\e[0;32mRestricted shell installed\e[0m"

cat <<\EOT >> /usr/local/bin/sourcedesk-remove
#!/bin/bash
# Deinstallation script of sourceDESK

# Enforce root
if [ "$(id -u)" != "0" ]; then
    echo "This script must be run as root"
    exit 1
fi

# Deinstall routine
rm /usr/local/bin/sourcedesk-rsh &> /dev/null
sed -i '/sourceDESK/d' ~/.ssh/authorized_keys &> /dev/null
sed -i '/sourceDESK/d'/etc/ssh/sshd_config &> /dev/null
rm /usr/local/bin/sourcedesk-remove &> /dev/null

# Success
echo "sourceDESK removed"
exit 1
EOT
chmod +x /usr/local/bin/sourcedesk-remove
echo -e "\e[0;32mUninstall routine copied\e[0m"

if ! [ -d "~/.ssh" ]; then
    mkdir -p ~/.ssh
    if [ $? -ne 0 ] ; then
        echo -e "\e[1;31mSSH user directory could not be created\e[0m" 1>&2
        echo ""
        exit 1
    else
        echo -e "\e[0;32mSSH user directory created\e[0m"
    fi
else
    echo -e "\e[0;32mSSH user directory found\e[0m"
fi

if ! [ -a "~/.ssh/authorized_keys" ]; then
    touch ~/.ssh/authorized_keys
    if [ $? -ne 0 ] ; then
        echo -e "\e[1;31mSSH authorized_keys file could not be created\e[0m" 1>&2
        echo ""
        exit 1
    else
        echo -e "\e[0;32mSSH authorized_keys file created\e[0m"
    fi
else
    echo -e "\e[0;32mSSH authorized_keys file found\e[0m"
fi

echo "command=\"/usr/local/bin/sourcedesk-rsh\" <?=$pubkey;?>" >> ~/.ssh/authorized_keys
echo -e "\e[0;32mSSH key added (restricted access)\e[0m"

chmod 700 ~/.ssh && chmod 600 ~/.ssh/*
echo -e "\e[0;32mSSH user directory mode set\e[0m"

if ! [ -a "/etc/ssh/sshd_config" ]; then
    echo -e "\e[31mSSH daemon config file not found" 1>&2
    echo -e "\e[31mPlease ensure that public key authorization is allowed" 1>&2
else
    echo -e "\e[0;32mSSH daemon config file found\e[0m"
    echo "" >> /etc/ssh/sshd_config
    echo "# sourceDESK config (activate public key authorization)" >> /etc/ssh/sshd_config
    echo "RSAAuthentication yes # sourceDESK" >> /etc/ssh/sshd_config
    echo "PubkeyAuthentication yes # sourceDESK" >> /etc/ssh/sshd_config
    echo "PermitRootLogin yes # sourceDESK" >> /etc/ssh/sshd_config
    echo -e "\e[0;32mSSH public key authorization enabled\e[0m"
fi

echo ""
echo "Calling sourceDESK to finish...";

URL="<?=$CFG['PAGEURL'];?>ssh_access/<?=$serverId;?>/<?=$sshHash;?>/${HOSTNAME}/${SSHPORT}/${SYSTEM}"
OUTPUT=$(wget -q -O - ${URL})
if ! [ "$OUTPUT" == "ok" ]; then
    echo -e "\e[1;31msourceDESK have no access to this server" 1>&2
    echo -e "\e[1;31mPlease check the error message\e[0m" 1>&2
    echo ""

    if [ "$OUTPUT" == "" ]; then
        echo "Unable to call sourceDESK, please call URL manually (expected response: ok)"
        echo ${URL}
    else
        echo -e "\e[0m${OUTPUT}"
    fi
else
    echo -e "\e[1;32msourceDESK access activated\e[0m"
fi

<?php

echo 'echo ""';
echo '';
exit;

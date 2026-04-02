<?php
// This is the configuration file

// Database credentials
$CFG['DB']['HOST'] = "%host%"; // In most cases localhost/127.0.0.1
$CFG['DB']['USER'] = "%user%"; // Your database user
$CFG['DB']['PASSWORD'] = "%pw%"; // Your database password
$CFG['DB']['DATABASE'] = "%db%"; // Your database name

// Hash for security reasons (should be AT LEAST 16 characters long)
$CFG['HASH'] = "%gen%";

// Path to wkhtmltoimage (optional)
$CFG['WKHTMLTOIMAGE'] = "/usr/bin/wkhtmltoimage";

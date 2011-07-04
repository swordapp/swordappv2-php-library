<?php

    // Load the PHP library
    include_once('../../../../swordappclient.php');
    include_once('../../utils.php');

    // Store the values
    session_start();

    // Try and deposit the multipart package
    $client = new SWORDAPPClient();
    $response = $client->depositMultipart($_SESSION['durl'], $_SESSION['u'], $_SESSION['p'], $_SESSION['obo'], $_SESSION['filename'], 'http://purl.org/net/sword/package/SimpleZip', 'application/zip', false);
    if ($response->sac_status != 201) {
        $error = 'Unable to deposit package. HTTP response code: ' .
                 $response->sac_status . ' - ' . $response->sac_statusmessage;
        $_SESSION['error'] = $error;
    } else {
        $_SESSION['error'] = '';
    }

?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>SWORD v2 exerciser - POST an atom multipart package</title>
        <link rel='stylesheet' type='text/css' media='all' href='../../css/style.css' />
    </head>
    <body>

        <div id="header">
            <h1>SWORD v2 exerciser</h1>
        </div>

        <p>
            Options:
        </p>

        <div class="section">


        </div>

        <div class="section">
            <h2>Response:</h2>
            <pre>Status code: <?php echo $response->sac_status; ?></pre>
            <pre><?php echo xmlpp($response->sac_xml, true); ?></pre>
        </div>

        <div id="footer">
                <a href='../../'>Home</a> | Based on the <a href="http://github.com/stuartlewis/swordappv2-php-library/">swordappv2-php-library</a>
        </div>
    </body>
</html>
<?php

session_start();
$_SESSION = array();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="nl">
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        <title>Google Analytics with Fusion Charts</title>
        <style type="text/css">
            body{font: 12px Verdana,Arial, Helvetica, sans-serif;color:#333;}
            h1,h2{color:#516082;font-weight:bold}
            h1{font-size: 16px;}
            h2{font-size:14px;}
            form{overflow:hidden;border:1px solid #ccc;padding:10px;width:300px;}
            label{display:block;float:left;clear:left;width: 100px;margin: 3px;}
            input{display:block;float:left;width:200px;margin: 3px;}
            #submit{clear:left;width:100px;margin: 5px 0 0 106px;}
        </style>
    </head>
    <body>
    
        <h1>Google Analytics with Fusion Charts</h1>
        
        <p>Use your Google Analytics account credentials.Your login credentials are not stored anywhere but directly send to Google.</p>
        <form method="post" action="analytics_data.php">
            <label for="username">E-mail</label><input type="text" name="username" id="username">
            <label for="password">Password</label><input type="password" name="password" id="password">
            <input type="submit" id="submit" value="Log in">
        </form>
        <p>Linux Mint 10 x86_64; Linux 2.6.35; Lighttpd 1.4.28; PHP 5.3.5; PostgreSQL 9.0.2</p>
    </body>
</html>
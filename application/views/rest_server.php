<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>REST Server Tests</title>

    <style type="text/css">

    ::selection { background-color: #E13300; color: white; }
    ::-moz-selection { background-color: #E13300; color: white; }

    body {
        background-color: #fff;
        margin: 40px;
        font: 16px/20px normal Helvetica, Arial, sans-serif;
        color: #4F5155;
    }

    a {
        color: #003399;
        background-color: transparent;
        font-weight: normal;
    }

    h1 {
        color: #444;
        background-color: transparent;
        border-bottom: 1px solid #D0D0D0;
        font-size: 24px;
        font-weight: normal;
        margin: 0 0 14px 0;
        padding: 14px 15px 10px 15px;
    }

    code {
        font-family: Consolas, Monaco, Courier New, Courier, monospace;
        font-size: 16px;
        background-color: #f9f9f9;
        border: 1px solid #D0D0D0;
        color: #002166;
        display: block;
        margin: 14px 0 14px 0;
        padding: 12px 10px 12px 10px;
    }

    #body {
        margin: 0 15px 0 15px;
    }

    p.footer {
        text-align: right;
        font-size: 16px;
        border-top: 1px solid #D0D0D0;
        line-height: 32px;
        padding: 0 10px 0 10px;
        margin: 20px 0 0 0;
    }

    #container {
        margin: 10px;
        border: 1px solid #D0D0D0;
        box-shadow: 0 0 8px #D0D0D0;
    }
    </style>
</head>
<body>

<div id="container">
    <h1>REST Server Tests</h1>

    <div id="body">

        <h2><a href="<?php echo site_url(); ?>">Home</a></h2>

        <p>
            See the article
            <a href="http://net.tutsplus.com/tutorials/php/working-with-restful-services-in-codeigniter-2/" target="_blank">
                http://net.tutsplus.com/tutorials/php/working-with-restful-services-in-codeigniter-2/
            </a>
        </p>

        <p>
            The master project repository is
            <a href="https://github.com/chriskacerguis/codeigniter-restserver" target="_blank">
                https://github.com/chriskacerguis/codeigniter-restserver
            </a>
        </p>

        <p>
            Click on the links to check whether the REST server is working.
        </p>

        <ol>
            <li><a href="<?php echo site_url('api/example/users'); ?>">Users</a> - defaulting to JSON</li>
            <li><a href="<?php echo site_url('api/example/users/format/csv'); ?>">Users</a> - get it in CSV</li>
            <li><a href="<?php echo site_url('api/example/users/id/1'); ?>">User #1</a> - defaulting to JSON  (users/id/1)</li>
            <li><a href="<?php echo site_url('api/example/users/1'); ?>">User #1</a> - defaulting to JSON  (users/1)</li>
            <li><a href="<?php echo site_url('api/example/users/id/1.xml'); ?>">User #1</a> - get it in XML (users/id/1.xml)</li>
            <li><a href="<?php echo site_url('api/example/users/id/1/format/xml'); ?>">User #1</a> - get it in XML (users/id/1/format/xml)</li>
            <li><a href="<?php echo site_url('api/example/users/id/1?format=xml'); ?>">User #1</a> - get it in XML (users/id/1?format=xml)</li>
            <li><a href="<?php echo site_url('api/example/users/1.xml'); ?>">User #1</a> - get it in XML (users/1.xml)</li>
            <li><a id="ajax" href="<?php echo site_url('api/example/users/format/json'); ?>">Users</a> - get it in JSON (AJAX request)</li>
            <li><a href="<?php echo site_url('api/example/users.html'); ?>">Users</a> - get it in HTML (users.html)</li>
            <li><a href="<?php echo site_url('api/example/users/format/html'); ?>">Users</a> - get it in HTML (users/format/html)</li>
            <li><a href="<?php echo site_url('api/example/users?format=html'); ?>">Users</a> - get it in HTML (users?format=html)</li>
        </ol>

    </div>

    <p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds. <?php echo  (ENVIRONMENT === 'development') ?  'CodeIgniter Version <strong>' . CI_VERSION . '</strong>' : '' ?></p>
</div>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

<script type="text/javascript">
//<![CDATA[

    $(function() {

        $("#ajax").on("click", function(evt) {

            evt.preventDefault();

            $.ajax({

                url: $(this).attr("href"), // URL from the link that was clicked on.

            }).done(function (data) {

                // The 'data' parameter is an array of objects that can be looped over.

                alert(window.JSON.stringify(data));

            }).fail(function () {

                alert('Oh no! A problem with the AJAX request!');
            });
        });
    });

//]]>
</script>

</body>
</html>
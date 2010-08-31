<html>
<head>
<title>Welcome to CodeIgniter REST Server</title>

<style type="text/css">

body {
 background-color: #fff;
 margin: 40px;
 font-family: Lucida Grande, Verdana, Sans-serif;
 font-size: 14px;
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
 font-size: 16px;
 font-weight: bold;
 margin: 24px 0 2px 0;
 padding: 5px 0 6px 0;
}

code {
 font-family: Monaco, Verdana, Sans-serif;
 font-size: 12px;
 background-color: #f9f9f9;
 border: 1px solid #D0D0D0;
 color: #002166;
 display: block;
 margin: 14px 0 14px 0;
 padding: 12px 10px 12px 10px;
}

</style>
</head>
<body>

<h1>Welcome to CodeIgniter REST Server!</h1>

<p>The page you are looking at is being generated dynamically by CodeIgniter with <a href="http://philsturgeon.co.uk/" target="_blank">Phil Sturgeon</a>'s REST server included.</p>

<p>Below are a few examples of the REST server library in use. Remember, we will only see GET methods looking through a browser.</p>

<ul>
	<li><a href="<?php echo site_url('api/example/users');?>">Users</a> - defaulting to XML</li>
	<li><a href="<?php echo site_url('api/example/users/format/csv');?>">Users</a> - get it in CSV</li>
	<li><a href="<?php echo site_url('api/example/user/id/1');?>">User #1</a> - defaulting to XML</li>
	<li><a href="<?php echo site_url('api/example/user/id/1/format/json');?>">User #1</a> - get it in JSON</li>
</ul>

<p>If you are exploring CodeIgniter for the very first time, you should start by reading the <a href="http://codeigniter.com/user_guide/">User Guide</a>.</p>


<p><br />Page rendered in {elapsed_time} seconds</p>

</body>
</html>
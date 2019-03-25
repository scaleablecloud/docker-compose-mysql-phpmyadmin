<html>
<head>
<script src="http://keycloak.accounts.dina-web.local:8083/auth/js/keycloak.js"></script>
<script>

    // See https://github.com/ahus1/keycloak-dropwizard-integration/blob/master/keycloak-dropwizard-bearer/src/main/resources/assets/ajax/app.js

    var keycloak = Keycloak();
    keycloak.init({ onLoad: 'login-required' })
        .success(function(authenticated) {
            console.log(authenticated ? 'authenticated' : 'not authenticated');
            console.log(keycloak);
            document.getElementById('user').innerHTML = "Logged in as " + keycloak.idTokenParsed.name + ", " + keycloak.idTokenParsed.email;
            document.getElementById('bearer').innerHTML = "Bearer token: " + keycloak.token;
        })
        .error(function() {
            console.log('failed to initialize');
        });
</script>
</head>
<body>

<h1>Hello Cloudreach!</h1>
<h4>Attempting MySQL connection from php...</h4>
<?php
$host = 'mysql';
$user = 'root';
$pass = 'rootpassword';
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected to MySQL successfully!";
}

?>

        <h1>Login test implementation</h1> 
        <p id="user"></p>
        <p id="bearer"></p>
        
</body>
</html>






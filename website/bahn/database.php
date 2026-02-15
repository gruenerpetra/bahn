<?php
echo "Hello<br>";
$servername = "localhost";
$username = "root";
$dbName = "bahn";

// Create connection
$conn = new mysqli($servername, $username,"" ,$dbName);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";

$sql = "INSERT INTO fahrten(FahrtNr, VZN, Zugart, TZN, Startbahnhof, Zielbahnhof, Klasse)
VALUES ('2', '200', 'ICE', '9500', 'Leipzig Hbf', 'Nuernberg Hbf', '2')";

if ($conn->query($sql) === TRUE) {
  echo "New record created successfully";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();

?>
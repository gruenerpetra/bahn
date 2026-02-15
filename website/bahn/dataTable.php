<?php
echo "Hello<br>";

$fFahrten = 1;
$fMFahrtNr = $_GET["fMFahrtNr"];
$fDatum = $_GET["fDatum"];
$fVZN = $_GET["fVZN"];
$fZugart= $_GET["fZugart"];
$fTZN = $_GET["fTZN"];
$fAbbhf = $_GET["fAbbhf"];
$fAnbhf = $_GET["fAnbhf"];
$fAbZ = $_GET["fAbZ"];
$fAnZ = $_GET["fAnZ"];
$fKlasse = $_GET["fKlasse"];


$servername = "localhost";
$username = "root";
$dbName = "bahn";

// Create connection
$conn = new mysqli($servername, $username,"" ,$dbName);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully<br>";


$sql = "SELECT FahrtNr, VZN FROM fahrten";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
  // output data of each row
  while($row = mysqli_fetch_assoc($result)) {
    echo "FahrtNr: " . $row["FahrtNr"]. " - VZN: " . $row["VZN"].  "<br>";
    $fFahrten++;
  }
} else {
  echo "0 results";
}






$sql = "INSERT INTO fahrten(FahrtNr, FahrtNrM, Datum, VZN, Zugart, TZN, Startbahnhof, Zielbahnhof, AbfZ, AnkZ, Klasse)
VALUES ('$fFahrten', '$fMFahrtNr', '$fDatum', '$fVZN', '$fZugart', '$fTZN', '$fAbbhf', '$fAnbhf', '$fAbZ', '$fAnZ' ,'$fKlasse')";

if ($conn->query($sql) === TRUE) {
  echo "New record created successfully";
  
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();

header("Location: index.html");
exit;

?>
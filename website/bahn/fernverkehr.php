<?php
echo "Hello<br>";

$fFahrten = 1;
echo "Fahrt-Nr: ";
echo $fFahrten;
echo "<br>";

$fVZN = $_GET["fVZN"];
echo "VZN: ";
echo $fVZN;
echo "<br>";

$fZugart= $_GET["fZugart"];
echo "Zugart: ";
echo $fZugart;
echo "<br>";

$fTZN = $_GET["fTZN"];
echo "TZN: ";
echo $fTZN;
echo "<br>";

$fAbbhf = $_GET["fAbbhf"];
echo "Abfahrtsbahnhof: ";
echo $fAbbhf;
echo "<br>";

$fAnbhf = $_GET["fAnbhf"];
echo "Ankunftsbahnhof: ";
echo $fAnbhf;
echo "<br>";

$fKlasse = $_GET["fKlasse"];
echo "Klasse: ";
echo $fKlasse;
echo "<br>";
?>
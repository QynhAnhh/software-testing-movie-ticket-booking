<?php
$c = mysqli_connect("localhost", "root", "", "movie_ticket_booking", "3308");
$r = mysqli_query($c, "SELECT title FROM movies LIMIT 10");
while ($row = mysqli_fetch_array($r)) {
    echo $row[0] . PHP_EOL;
}
?>

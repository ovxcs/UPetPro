<?php

require_once "stocks.php"; rand_stocks_main();
require_once "clients.php"; rand_clients_main();
require_once "visits.php"; generate_visits();
require_once "radio.php"; generate_radio();

?>
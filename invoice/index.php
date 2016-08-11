<?php
# start session
session_start();
# deny access if not logged in
if(!isset($_SESSION['user'])){
  header("Location: ../login/");
  exit();
}
# require database connection
require("../db/pdo.inc.php");
# include <head>
include("../include/head.php");

# check if invoice id was submitted
if(empty($_GET["id"])){
  exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Bitte rufe einen gültigen Rechnungslink auf.</div>");
}
# check if invoice id is integer
if(!filter_var($_GET["id"], FILTER_VALIDATE_INT)){
  exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Unerwartete Rechnungs-ID.</div>");
}
# get invoice
$getInvoice = $db->prepare("SELECT r_id, r_user, r_receiver, r_amount, r_info, r_created WHERE r_id=:r_id AND r_used=0");
$getInvoice->bindValue(":r_id", $_GET["id"], PDO::PARAM_INT);
$getInvoice->execute();
$invoiceExists = ($getInvoice->rowCount() > 0) ? true : false;
if(!$invoiceExists){
  # invoice doesn't exist
  exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Die Rechnungs-ID existiert nicht oder ist nicht mehr gültig.</div>");
}
foreach($getInvoice as $invoice){
  # fetch information
  $rID = htmlspecialchars($invoice["r_id"], ENT_QUOTES);
  $rUser = htmlspecialchars($invoice["r_user"], ENT_QUOTES);
  $rReceiver = htmlspecialchars($invoice["r_receiver"], ENT_QUOTES);
  $rAmount = htmlspecialchars($invoice["r_amount"], ENT_QUOTES);
  $rInfo =  htmlspecialchars($invoice["r_info"], ENT_QUOTES);
  $rCreated = htmlspecialchars($invoice["r_created"], ENT_QUOTES);
}
# check invoice creator
if($rCreated == $_SESSION["user"]){
  # own invoice
  exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Du kannst keine eigenen Rechnungen begleichen.</div>");
}
# check invoice receiver
if($rReceiver != $_SESSION["user"] || $rReceiver != "*"){
  # wrong receiver
  exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Die Rechnung ist nicht für Dich bestimmt.</div>");
}
?>
<form method="post">
  <div class="alert alert-info" style="text-align: center; font-weight: bold">
    Du hast auf einen Rechnungslink geklickt. Bestätige die Rechnung, um sie zu begleichen.
    Hier unten findest Du nähere Angaben zur Rechnung. Überprüfe sie auf ihre Richtigkeit!
  </div>
  <p style="text-align: center">Rechnungsersteller: <b><?php echo $rCreated; ?></b></p>
  <p style="text-align: center">Rechnungsbetrag: <b><?php echo $rAmount; ?> Kadis</b></p>
  <p style="text-align: center">Verwendungszweck/Info: <b><?php echo $rInfo; ?></b></p>
  <button type="submit" class="btn btn-block btn-primary">Rechnung begleichen</button>
</form>

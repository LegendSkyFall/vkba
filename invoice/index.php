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
$getInvoice = $db->prepare("SELECT r_id, r_user, r_receiver, r_amount, r_info, r_created FROM Invoices WHERE r_id=:r_id AND r_used=0");
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
if($rReceiver != $_SESSION["user"] && $rReceiver != "77777777"){
  # wrong receiver
  exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Die Rechnung ist nicht für Dich bestimmt.</div>");
}
# handle pay invoice request
if(isset($_POST["payInvoice"])){
  # CSRF-Protection
  if($_POST["token"] != $_SESSION["csrf_token"]){
    exit("Illegaler Zugriffsversuch.");
  }
  # check if invoice id was submitted
  if(empty($_GET["id"])){
    exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Bitte rufe einen gültigen Rechnungslink auf.</div>");
  }
  # check if invoice id is integer
  if(!filter_var($_GET["id"], FILTER_VALIDATE_INT)){
    exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Unerwartete Rechnungs-ID.</div>");
  }
  # get invoice
  $getInvoice = $db->prepare("SELECT r_id, r_user, r_receiver, r_amount, r_info, r_created, r_maxUsages FROM Invoices WHERE r_id=:r_id AND r_used=0");
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
    $rMaxUsages = htmlspecialchars($invoice["r_maxUsages"], ENT_QUOTES);
  }
  # check invoice creator
  if($rCreated == $_SESSION["user"]){
    # own invoice
    exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Du kannst keine eigenen Rechnungen begleichen.</div>");
  }
  # check invoice receiver
  if($rReceiver != $_SESSION["user"] && $rReceiver != "77777777"){
    # wrong receiver
    exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Die Rechnung ist nicht für Dich bestimmt.</div>");
  }
  # handle max usages
  if($rMaxUsages > 0){
    $newMaxUsages = --$rMaxUsages;
  }else{
    exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Die Rechnung kann nicht mehr eingelöst werden.</div>");
  }
  # get user balance
  $getUserBalance = $db->prepare("SELECT balance FROM Accounts WHERE username=:username");
  $getUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
  $getUserBalance->execute();
  foreach($getUserBalance as $balance){
    $userBalance = $balance["balance"];
  }
  # check amount with available money
  if($rAmount > $userBalance){
    # not enough money
    exit("<div class='alert alert-danger' style='font-weight: bold; text-align: center'>Du hast nicht genügend Geld, um diese Rechnung zu begleichen.</div>");
  }else{
    # get receiver balance
    $getReceiverBalance = $db->prepare("SELECT balance FROM Accounts WHERE ktn_nr=:ktn_nr");
    $getReceiverBalance->bindValue(":ktn_nr", $rReceiver, PDO::PARAM_STR);
    $getReceiverBalance->execute();
    foreach($getReceiverBalance as $balance){
      $receiverBalance = $balance["balance"];
    }
    # calculate new balances
    $newUserBalance = $userBalance - $rAmount;
    $newReceiverBalance = $receiverBalance + $rAmount;
    # update receiver balance
    $updateReceiverBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
    $updateReceiverBalance->bindValue(":balance", $newReceiverBalance, PDO::PARAM_STR);
    $updateReceiverBalance->bindValue(":username", $rReceiver, PDO::PARAM_STR);
    $updateReceiverBalance->execute();
    # create system message for receiver
    $createSysMessage = $db->prepare("INSERT INTO SysMessage (sys_user, message, sys_type) VALUES (:sys_user, :message, 1)");
    $createSysMessage->bindValue(":sys_user", $rReceiver, PDO::PARAM_STR);
    $createSysMessage->bindValue(":message", "Rechnung (" . $rID . ") in Höhe von " . $rAmount . " Kadis (Info: " . $rInfo . ") wurde von " . $_SESSION["user"] . " beglichen.", PDO::PARAM_STR);
    $createSysMessage->execute();
    # generate random transaction id and check whether transaction id already exists
    $randTransactionID = mt_rand(100000000, 999999999);
    $checkTransactionID = $db->prepare("SELECT t_id FROM Transactions WHERE t_id=:t_id");
    $checkTransactionID->bindValue(":t_id", $randTransactionID, PDO::PARAM_INT);
    $checkTransactionID->execute();
    $transactionIDExists = ($checkTransactionID->rowCount() > 0) ? true : false;
    foreach($checkTransactionID as $transaction){
      $transactionID = $transaction["t_id"];
    }
    if($transactionIDExists){
      # generate new random id
      while($randTransactionID == $transactionID){
        $randTransactionID = mt_rand(100000000, 999999999);
        $checkTransactionID = $db->prepare("SELECT t_id FROM Transactions WHERE t_id=:t_id");
        $checkTransactionID->bindValue(":t_id", $randTransactionID, PDO::PARAM_INT);
        $checkTransactionID->execute();
        foreach($checkTransactionID as $transaction){
          $transactionID = $transaction["t_id"];
        }
      }
    }
    # log transaction
    $logTransaction = $db->prepare("INSERT INTO Transactions (t_id, t_description, t_adress, t_sender, t_amount, t_type, t_date, t_state) VALUES(:t_id, :t_description, :t_adress, :t_sender, :t_amount, 0, :t_date, 1)");
    $logTransaction->bindValue(":t_id", $randTransactionID, PDO::PARAM_INT);
    $logTransaction->bindValue(":t_description", "Rechnung", PDO::PARAM_STR);
    $logTransaction->bindValue(":t_adress", $rReceiver, PDO::PARAM_STR);
    $logTransaction->bindValue(":t_sender", $_SESSION["user"], PDO::PARAM_STR);
    $logTransaction->bindValue(":t_amount", $rAmount, PDO::PARAM_STR);
    $logTransaction->bindValue(":t_date", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $logTransaction->execute();
    # update user balance
    $updateUserBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
    $updateUserBalance->bindValue(":balance", $newUserBalance, PDO::PARAM_STR);
    $updateUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
    $updateUserBalance->execute();
    # update max usages
    if($newMaxUsages == 0){
      # close invoice
      $closeInvoice = $db->prepare("UPDATE Invoices SET r_maxUsages=0 AND r_used=1 WHERE r_id=:r_id");
      $closeInvoice->bindValue(":r_id", $rID, PDO::PARAM_INT);
      $closeInvoice->execute();
      if($closeInvoice){
        # successfull
        exit("<div class='alert alert-success' style='font-weight: bold; text-align: center'>Rechnung erfolgreich beglichen. Der Ersteller wurde darüber benachrichtigt.</div>");
      }
    }else{
      # update max usages
      $updateInvoice = $db->prepare("UPDATE Invoices SET r_maxUsages=:r_MaxUsages AND r_used=1 WHERE r_id=:r_id");
      $updateInvoice->bindValue(":r_MaxUsages", $newMaxUsages, PDO::PARAM_INT);
      $updateInvoice->bindValue(":r_id", $rID, PDO::PARAM_INT);
      $updateInvoice->execute();
      if($updateInvoice){
        # successfull
        exit("<div class='alert alert-success' style='font-weight: bold; text-align: center'>Rechnung erfolgreich beglichen. Der Ersteller wurde darüber benachrichtigt.</div>");
      }
    }
  }
}
?>
<form method="post">
  <div class="alert alert-info" style="text-align: center; font-weight: bold">
    Du hast auf einen Rechnungslink geklickt. Bestätige die Rechnung, um sie zu begleichen.
    Hier unten findest Du nähere Angaben zur Rechnung. Überprüfe sie auf ihre Richtigkeit!
  </div>
  <p style="text-align: center">Rechnungsersteller-KtnNr.: <b><?php echo $rUser; ?></b></p>
  <p style="text-align: center">Rechnungsbetrag: <b><?php echo $rAmount; ?> Kadis</b></p>
  <p style="text-align: center">Verwendungszweck/Info: <b><?php echo $rInfo; ?></b></p>
  <p style="text-align: center">Erstelldatum: <b><?php echo date("d.m.Y H:i:s", strtotime($rCreated)); ?></b></p>
  <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
  <button type="submit" name="payInvoice" class="btn btn-block btn-primary">Rechnung begleichen</button>
</form>

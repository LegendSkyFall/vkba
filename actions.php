<?php
# start session
session_start();
# deny access if not logged in
if(!isset($_SESSION['user'])){
  header("Location: login/");
  exit();
}
# conntect to database
require("db/pdo.inc.php");
?>
<!DOCTYPE html>
<html>
  <?php
  include('include/head.php');
  ?>
  <body class="skin-black">
    <?php
    include('include/header.php');
    include('include/usernav.php');
    ?>
    <aside class="right-side">
      <!-- content -->
      <section class="content">
        <?php
        # handle payment submit
        if(isset($_POST["submitPayment"])){
          # CSRF-Protection
          if($_POST["token"] != $_SESSION["csrf_token"]){
            exit("Illegaler Zugriffsversuch!");
          }
          # error handling variable
          $error = false;
          # check if requested amount is a number
          if(!is_numeric($_POST["amount"])){
            # unexpected value
            $error = true;
            $errorMessage = "Der Betrag muss eine Zahl sein.";
          }
          # check if amount is negative
          if($_POST["amount"] <= 0){
            # negative amount
            $error = true;
            $errorMessage = "Der Betrag darf nicht negativ sein.";
          }
          # check users balance
          $getUserBalance = $db->prepare("SELECT balance FROM Accounts WHERE username=:username");
          $getUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
          $getUserBalance->execute();
          foreach($getUserBalance as $balance){
            $userBalance = $balance["balance"];
          }
          # check if requested amount is higher than available money
          if($_POST["amount"] > $userBalance){
            # not enough money
            $error = true;
            $errorMessage = "Du hast nicht genügend Geld.";
          }
          # check if requested amount is higher than allowed amount
          if($_POST["amount"] > 9999){
            # amount to high
            $error = true;
            $errorMessage = "Überweisungen sind nur bis 9999 Kadis erlaubt.";
          }
          # check if usage was submitted and if is valid
          if(strlen($_POST["usage"]) == 0 || strlen($_POST["usage"]) > 255){
            # no usage or too long
            $error = true;
            $errorMessage = "Bitte gib einen Verwendungszweck an und achte darauf, dass dieser nicht länger als 255 Zeichen lang sein darf.";
          }
          # check if ktnNr exists and get receiver balance
          $getReceiver = $db->prepare("SELECT balance, username FROM Accounts WHERE ktn_nr=:ktn_nr");
          $getReceiver->bindValue(":ktn_nr", $_POST["ktnNr"], PDO::PARAM_STR);
          $getReceiver->execute();
          $receiverExists = ($getReceiver->rowCount() > 0) ? true : false;
          if(!$receiverExists){
            # receiver doesn't exist
            $error = true;
            $errorMessage = "Die angegebene Empfänger-Kontonummer konnte nicht gefunden werden.";
          }
          foreach($getReceiver as $receiver){
            $receiverUsername = $receiver["username"];
            $receiverBalance = $receiver["balance"];
          }
          # check if receiver is valid
          if($receiverUsername == $_SESSION["user"]){
            # payments to yourself is not allowed
            $error = true;
            $errorMessage = "Überweisungen an sich selbst sind nicht zulässig";
          }
          # calculate new balances
          $newUserBalance = round($userBalance - $_POST["amount"], 2);
          $newReceiverBalance = round($receiverBalance + $_POST["amount"], 2);
          if($_POST["paymentSelection"] == 1){
            # default payment
            $paymentSelection = 1;
          }elseif($_POST["paymentSelection"] == 2){
            # date payment
            $paymentSelection = 2;
            # check date input //TODO
          }elseif($_POST["paymentSelection"] == 3){
            # permanent transfer
            $paymentSelection = 3;
            # check date input //TODO
            # check interval //TODO
          }else{
            # invalid payment selection
            $error = true;
            $errorMessage = "Ungültige Überweisungsvariante ausgewählt.";
          }
          # if no error, make payment
          if(!$error){
            if($paymentSelection == 1){
              # update users balance
              $updateUserBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
              $updateUserBalance->bindValue(":balance", $newUserBalance, PDO::PARAM_STR);
              $updateUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
              $updateUserBalance->execute();
              # update receivers balance
              $updateReceiverBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
              $updateReceiverBalance->bindValue(":balance", $newReceiverBalance, PDO::PARAM_STR);
              $updateReceiverBalance->bindValue(":username", $receiverUsername, PDO::PARAM_STR);
              $updateReceiverBalance->execute();
              # create system message for receiver
              $createSysMessage = $db->prepare("INSERT INTO SysMessage (sys_user, message, sys_type) VALUES (:sys_user, :message, 1)");
              $createSysMessage->bindValue(":sys_user", $receiverUsername, PDO::PARAM_STR);
              $createSysMessage->bindValue(":message", "Überweisung von " . $_SESSION["user"] . " in Höhe von " . round($_POST["amount"], 2) . " Kadis eingegangen. Verwendungszweck: " . $_POST["usage"], PDO::PARAM_STR);
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
              $logTransaction->bindValue(":t_description", "Standardüberweisung", PDO::PARAM_STR);
              $logTransaction->bindValue(":t_adress", $receiverUsername, PDO::PARAM_STR);
              $logTransaction->bindValue(":t_sender", $_SESSION["user"], PDO::PARAM_STR);
              $logTransaction->bindValue(":t_amount", round($_POST["amount"], 2), PDO::PARAM_STR);
              $logTransaction->bindValue(":t_date", date("Y-m-d H:i:s"), PDO::PARAM_STR);
              $logTransaction->execute();
              if($logTransaction){
                # successfull
                $successMessage = "Standardüberweisung erfolgreich ausgeführt.";
              }
            }else{
              $errorMessage = "Derzeit ist nur die Standardüberweisung aktiviert.";
            }
          }

        }
        # handle code submit
        if(isset($_POST["submitCode"])){
          # CSRF-Protection
          if($_POST["token"] != $_SESSION["csrf_token"]){
            exit("Illegaler Zugriffsversuch!");
          }
          # check code
          $checkCode = $db->prepare("SELECT code, c_value FROM Code WHERE redeemed=0 AND code=:code");
          $checkCode->bindValue(":code", $_POST["code"], PDO::PARAM_STR);
          $checkCode->execute();
          $codeExists = ($checkCode->rowCount() > 0) ? true : false;
          if($codeExists){
            foreach($checkCode as $code){
              $codeNo = $code["code"];
              $codeValue = $code["c_value"];
            }
            # get user balance
            $getUserBalance = $db->prepare("SELECT balance FROM Accounts WHERE username=:username");
            $getUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
            $getUserBalance->execute();
            foreach($getUserBalance as $userBalance){
              $newBalance = $userBalance["balance"] + $codeValue;
            }
            # update user balance
            $updateUserBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
            $updateUserBalance->bindValue(":balance", $newBalance, PDO::PARAM_STR);
            $updateUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
            $updateUserBalance->execute();
            # mark code as redeemed
            $markAsRedeemed = $db->prepare("UPDATE Code SET redeemed=1 WHERE code=:code");
            $markAsRedeemed->bindValue(":code", $codeNo, PDO::PARAM_STR);
            $markAsRedeemed->execute();
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
            $logTransaction = $db->prepare("INSERT INTO Transactions (t_id, t_description, t_adress, t_sender, t_amount, t_type, t_date, t_state) VALUES(:t_id, :t_description, :t_adress, :t_sender, :t_amount, 1, :t_date, 1)");
            $logTransaction->bindValue(":t_id", $randTransactionID, PDO::PARAM_INT);
            $logTransaction->bindValue(":t_description", "Guthabenaufladung (Code)", PDO::PARAM_STR);
            $logTransaction->bindValue(":t_adress", "VKBA-Bot", PDO::PARAM_STR);
            $logTransaction->bindValue(":t_sender", $_SESSION["user"], PDO::PARAM_STR);
            $logTransaction->bindValue(":t_amount", $codeValue, PDO::PARAM_STR);
            $logTransaction->bindValue(":t_date", date("Y-m-d H:i:s"), PDO::PARAM_STR);
            $logTransaction->execute();
            if($logTransaction){
              # successfull
              $successMessage = "Die Aufladung per Code war erfolgreich. Das Geld wurde Dir gutgeschrieben.";
            }
          }else{
            $errorMessage = "Aufladung per Code fehlgeschlagen. Code existiert nicht.";
          }
        }
        # handle auto submit
        if(isset($_POST["submitAuto"])){
          # CSRF-Protection
          if($_POST["token"] != $_SESSION["csrf_token"]){
            exit("Illegaler Zugriffsversuch!");
          }
          echo "<meta http-equiv='refresh' content='0; /readout'>";
          exit();
        }
        # handle report submit
        if(isset($_POST["submitReport"])){
          # CSRF-Protection
          if($_POST["token"] != $_SESSION["csrf_token"]){
            exit("Illegaler Zugriffsversuch!");
          }
          # error handling variable
          $error = false;
          if(empty($_POST["reportDescription"])){
            # no description
            $error = true;
            $errorMessage = "Bitte teile uns nähere Informationen zu dem Betrugsfall mit.";
          }
          if(strlen($_POST["reportDescription"]) > 255){
            # description too long
            $error = true;
            $errorMessage = "Die Beschreibung des Betrugsfall ist zu lang.";
          }
          $checkTransactionID = $db->prepare("SELECT t_state FROM Transactions WHERE (t_adress=:t_adress OR t_sender=:t_sender) AND t_id =:t_id");
          $checkTransactionID->bindValue(":t_adress", $_SESSION["user"], PDO::PARAM_STR);
          $checkTransactionID->bindValue(":t_sender", $_SESSION["user"], PDO::PARAM_STR);
          $checkTransactionID->bindValue(":t_id", $_POST["reportID"], PDO::PARAM_STR);
          $checkTransactionID->execute();
          $transactionIDValid = ($checkTransactionID->rowCount() > 0) ? true : false;
          if(!$transactionIDValid){
            $error = true;
            $errorMessage = "Ungültige Transaktions-ID oder Dir nicht zugeordnet.";
          }else{
            foreach($checkTransactionID as $transaction){
              $transactionState = $transaction["t_state"];
            }
            if($transactionState == 2){
              # already reported
              $error = true;
              $errorMessage = "Die Transaktion wurde bereits von Dir gemeldet. Der Fall wird noch genauer geprüft.";
            }
          }
          if(!$error){
            # log report
            $logReport = $db->prepare("INSERT INTO adminLog (username, logType, logInfo) VALUES (:username, :logType, :logInfo)");
            $logReport->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
            $logReport->bindValue(":logType", "Käuferschutz (#" . $_POST["reportID"] . ")", PDO::PARAM_STR);
            $logReport->bindValue(":logInfo", $_POST["reportDescription"], PDO::PARAM_STR);
            $logReport->execute();
            # mark transaction
            $reportTransaction = $db->prepare("UPDATE Transactions SET t_state=2 WHERE t_id=:t_id");
            $reportTransaction->bindValue(":t_id", $_POST["reportID"], PDO::PARAM_INT);
            $reportTransaction->execute();
            if($reportTransaction){
              # successfull
              $successMessage = "Der Fall wurde gemeldet und nun näher untersucht. Du wirst benachrichtigt, sobald der Fall bearbeitet wurde. Wir hoffen, Du hast weiterhin Spaß bei VKBA und bedanken uns für das Melden.";
            }
          }
        }
        # handle payout submit
        if(isset($_POST["submitPayout"])){
          # CSRF-Protection
          if($_POST["token"] != $_SESSION["csrf_token"]){
            exit("Illegaler Zugriffsversuch!");
          }
          # get actual balance
          $getUserBalance = $db->prepare("SELECT balance FROM Accounts WHERE username=:username");
          $getUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
          $getUserBalance->execute();
          foreach($getUserBalance as $balance){
            $userBalance = $balance["balance"];
          }
          if($userBalance <= 0){
            # not enough money
            $errorMessage = "Du hast kein Geld, welches Du Dir auszahlen lassen kannst.";
          }else{
            # calculate fees
            if($_SESSION["ktype"] == 0){
              # giro account, 5% fees
              $payoutBalance = round($userBalance - (($userBalance/100) * 5), 2);
            }elseif($_SESSION["ktype"] == 1){
              # merchant account, 1% fees
              $payoutBalance = round($userBalance - (($userBalance/100) * 1), 2);
            }
            # log transaction
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
            $logTransaction = $db->prepare("INSERT INTO Transactions (t_id, t_description, t_adress, t_sender, t_amount, t_type, t_date, t_state) VALUES(:t_id, :t_description, :t_adress, :t_sender, :t_amount, 1, :t_date, 0)");
            $logTransaction->bindValue(":t_id", $randTransactionID, PDO::PARAM_INT);
            $logTransaction->bindValue(":t_description", "Auszahlung", PDO::PARAM_STR);
            $logTransaction->bindValue(":t_adress", $_SESSION["user"], PDO::PARAM_STR);
            $logTransaction->bindValue(":t_sender", "VKBA-Bot", PDO::PARAM_STR);
            $logTransaction->bindValue(":t_amount", $payoutBalance, PDO::PARAM_STR);
            $logTransaction->bindValue(":t_date", date("Y-m-d H:i:s"), PDO::PARAM_STR);
            $logTransaction->execute();
            # log payout
            $logReport = $db->prepare("INSERT INTO adminLog (username, logType, logInfo) VALUES (:username, :logType, :logInfo)");
            $logReport->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
            $logReport->bindValue(":logType", "Auszahlungsantrag", PDO::PARAM_STR);
            $logReport->bindValue(":logInfo", $_SESSION["user"] . " - " . $payoutBalance . " Kadis", PDO::PARAM_STR);
            $logReport->execute();
            # reset balance
            $resetBalance = $db->prepare("UPDATE Accounts SET balance=0 WHERE username=:username");
            $resetBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
            $resetBalance->execute();
            if($resetBalance){
              # successfull
              $successMessage = "Auszahlungsantrag erfolgreich gestellt. Es dauert ca. 1-2 Tage, bis das Geld ingame bei Dir eingetroffen ist. Wir hoffen, Du bleibst uns weiterhin bei VKBA erhalten!";
            }
          }
        }
        # error message
        if(!empty($errorMessage)){
          echo "<div class='alert alert-danger' style='text-align: center; font-weight: bold'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" . $errorMessage . "</div>";
        }
        # success message
        if(!empty($successMessage)){
          echo "<div class='alert alert-success' style='text-align: center; font-weight: bold'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" . $successMessage . "</div>";
        }
        ?>
        <div class="row">
          <div class="col-md-12">
            <div class="panel">
              <header class="panel-heading">
                Aktionen
              </header>
              <div class="panel-body">
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#modalPayment"><i class="fa fa-ticket fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Überweisung tätigen</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#myModal-2"><i class="fa fa-pencil-square-o fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Rechnung schreiben</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#modalCode"><i class="fa fa-unlock fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Kontoaufladung per Code</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#modalAuto"><i class="fa fa-check-circle-o fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Kontoaufladung automatisch*</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#modalReport"><i class="fa fa-shield fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Käuferschutz beanspruchen</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#modalPayout"><i class="fa fa-money fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Auszahlung beantragen</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#myModal-1"><i class="fa fa-bullhorn fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Werbung schalten**</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#myModal-1"><i class="fa fa-trophy fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">QuickPoints-Prämien</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#myModal-1"><i class="fa fa-percent fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Sparbuch anlegen</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
              </div><!-- end panel-body -->
              <p style="text-align: center; font-style: italic" class="help-block">
                * Der gewünschte Betrag muss vorher am /w Legend auf Server 1 (geradeaus im Gebäude) am entsprechenden Bankautomaten aufgeladen werden.
                Nach dem Klicken auf das Schild, ca. 3 Minuten warten. Anschließend die erscheinende Schaltfläche hier betätigen, welcher beim Klicken auf die Schaltfläche erscheint.
                Das Geld wird anschließend gutgeschrieben. Die Gutschrift erfolgt global, sprich alle offenen Eingänge, auch die von anderen Spielern, werden ebenfalls bearbeitet.
              </p>
              <p style="text-align: center; font-style: italic" class="help-block">
                ** Werbung kann für eine Woche geschaltet werden. Diese erscheint zufällig auf QuickBuy. Anschließend kann diese reaktiviert oder eine neue angelegt werden.
              </p>
            </div><!-- end panel -->
          </div><!-- end col-md-12 -->
        </div><!-- end row -->
      </section><!-- end section -->
      <div class="footer-main">
          &copy; LEGEND-BANK 2016 - Virtual Kadcon Bank Accounts
      </div>
    </aside><!-- end aside -->
    <!-- Modal payment -->
    <div class="modal fade" id="modalPayment" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
            <h4 class="modal-title">Überweisung tätigen</h4>
          </div>
          <div class="modal-body">
            Um Geld anderen Spielern überweisen zu können, muss das nachfolgende Formular ausgefüllt werden.
            <form method="post">
              <label>Empfänger*</label>
              <input type="number" class="form-control" placeholder="Kontonummer des Empfängers angeben" name="ktnNr" required="required" min="10000000" max="99999999" />
              <label>Verwendungszweck</label>
              <input type="text" class="form-control" placeholder="Verwendungszweck angeben" name="usage" required="required" />
              <label>Betrag</label>
              <input type="number" class="form-control" placeholder="Betrag angeben" name="amount" required="required" min="0.01" max="9999" step="0.01"/>
              <label>Überweisungsvariante wählen</label>
              <select class="form-control" id="paymentSelection" name="paymentSelection">
                <option value="1">Standardüberweisung</option>
                <option value="2">Terminüberweisung</option>
                <option value="3">Dauerüberweisung</option>
              </select>
              <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <br>
              <button type="submit" class="btn btn-block btn-primary" name="submitPayment">Überweisung tätigen</button>
              <span class="help-block">
                Es gibt drei verschiedene Überweisungstypen. Standardüberweisungen werden unmittelbar nach Absenden des Formulars dem Empfänger gutgeschrieben,
                während Terminüberweisungen erst an dem angegebenem, gewünschten Datum ausgeführt werden.
                Dauerüberweisungen sind Überweisungen, die automatisch in selbst definierten Intervallen an den Empfänger ausgezahlt werden.
                Abhängig des ausgewählten Typs müssen unter Umständen weitere Informationen angegeben werden.
                <br>*Die Kontonummer des Empfängers kann unter 'Kontakte', wenn auf den Loginnamen geklickt wird, eingesehen werden.
              </span>
            </form>
          </div>
        </div><!-- end modal-content -->
      </div><!-- end modal-dialog -->
    </div><!-- end modal -->
    <!-- Modal code -->
    <div class="modal fade" id="modalCode" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
            <h4 class="modal-title">Kontoaufladung per Code</h4>
          </div>
          <div class="modal-body">
            Bitte gib im nachfolgendem Formular Deinen Guthaben-Code ein, um Dein VKBA-Konto manuell aufzuladen. Die Gutschrift erfolgt sofort.
            <form method="post">
              <input type="text" class="form-control" placeholder="Code eingeben" name="code" required="required"/>
              <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <button type="submit" class="btn btn-block btn-primary" name="submitCode">Code einlösen</button>
              <span class="help-block">
                Guthabencodes können am /w Legend auf Server 1 erworben werden. Dieses Verfahren eignet sich, wenn besonders schnell Guthaben benötigt wird.
                Jeder Code kann nur einmal eingelöst werden. Die Gutschrift erfolgt unmittelbar nach Eingabe.
                Alternativ gibt es auch die automatische Kontoaufladung, welche für erhöhten Komfort sorgt.
              </span>
            </form>
          </div>
        </div><!-- end modal-content -->
      </div><!-- end modal-dialog -->
    </div><!-- end modal -->
    <!-- Modal auto -->
    <div class="modal fade" id="modalAuto" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
            <h4 class="modal-title">Automatische Kontoaufladung</h4>
          </div>
          <div class="modal-body">
            Die automatische Kontoaufladung ist die bequemste und einfachste Art, sein Konto aufzuladen. Betätige die nachfolgende Schaltfläche, wenn Du zuvor Geld an einem VKBA-Geldautomaten eingezahlt hast.
            <form method="post">
              <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <button type="submit" class="btn btn-block btn-primary" name="submitAuto">Automatisch Konto aufladen</button>
              <span class="help-block">
                Alle VKBA-Akzeptazstellen sind dem Foren-Thread zu entnehmen. Hauptstandort ist der /w Legend auf Server 1.
                Das automatische Aufladen geht frühestens nach 5 Minuten wieder. Das Aufladen ist global, alle offenen Eingänge werden bearbeitet.
                Nach dem Einzahlen an einem VKBA-Geldautomaten bitte vor Betätigung der Schaltfläche einige Minuten warten.
              </span>
            </form>
          </div>
        </div><!-- end modal-content -->
      </div><!-- end modal-dialog -->
    </div><!-- end modal -->
    <!-- Modal report -->
    <div class="modal fade" id="modalReport" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
            <h4 class="modal-title">Käuferschutzbeantragung</h4>
          </div>
          <div class="modal-body">
            Wir bemühen uns stets, Betrüger von unserem Dienst fernzuhalten. Sollte es doch einmal zu einem Betrugsfall kommen oder der Verdacht auf Betrug besteht, kann dies hier gemeldet werden.<br>
            Wir werden den Fall intern prüfen und entsprechende Maßnahmen ergreifen. Sollte sich der Fall bestätigen, erhältst Du Dein Geld selbstverständlich erstattet.
            <form method="post">
              <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <br>
              <label>Betroffene Transaktion</label>
              <input type="number" name="reportID" class="form-control" min="100000000" max="999999999" required="required" placeholder="Transaktions-ID angeben"/>
              <label>Nähere Informationen zum Betrugsfall</label>
              <textarea class="form-control" maxlength="255" rows="3" name="reportDescription" required="required"></textarea>
              <button type="submit" class="btn btn-block btn-primary" name="submitReport">Käuferschutz beanspruchen</button>
              <span class="help-block">
                Um den Käuferschutz beanspruchen zu können, ist die Angabe der betroffenen Transaktions-ID notwendig, damit wir den Fall genauer prüfen könnnen.
                Die Transaktions-ID ist unter "Transakionen" im Menü auf der rechten Seite aufgelistet.
                Nach Überprüfung des Falls wirst Du benachrichtigt.
              </span>
            </form>
          </div>
        </div><!-- end modal-content -->
      </div><!-- end modal-dialog -->
    </div><!-- end modal -->
    <!-- Modal payout -->
    <div class="modal fade" id="modalPayout" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
            <h4 class="modal-title">Auszahlungsantrag</h4>
          </div>
          <div class="modal-body">
            VKBA ist dazu gedacht, online mit anderen Spielern zu handeln, eine weitere Zahlungsmethode einzuführen, die bequem verwaltbar und von überall aus erreichbar ist.
            Aus diesem Grund erheben wir für das Auszahlen geringe Gebühren, die abhängig vom Kontotypen, unterschiedlich hoch ausfallen können.<br>
            Es wird automatisch das <b>gesamte</b> Guthaben, abzüglich der Gebühren, ausgezahlt. Teilauszahlungen sind <b>nicht</b> möglich.
            <form method="post">
              <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <button type="submit" class="btn btn-block btn-primary" name="submitPayout">Auszahlung beantragen</button>
              <span class="help-block">
                Nach Auszahlungsantrag dauert es etwa 1-2 Tage, bis das Geld ingame überwiesen wurde. Das gesamte Geld wird Dir allerdings sofort abgebucht.
              </span>
            </form>
          </div>
        </div><!-- end modal-content -->
      </div><!-- end modal-dialog -->
    </div><!-- end modal -->
  </body><!-- end body -->
</html><!-- end html -->

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

# get user information
$getUserInformation = $db->prepare("SELECT a_type, balance, qp FROM Accounts WHERE username=:username");
$getUserInformation->bindValue(":username", $_SESSION['user'], PDO::PARAM_STR);
$getUserInformation->execute();
foreach($getUserInformation as $userInformation){
  $accountType = $userInformation['a_type'];
  $userBalance = $userInformation['balance'];
  $quickPoints = $userInformation['qp'];
}

# get total number of transactions
$getTransactions = $db->prepare("SELECT t_id FROM Transactions WHERE t_adress=:userAdress OR t_sender=:userSender");
$getTransactions->bindValue('userAdress', $_SESSION['user'], PDO::PARAM_STR);
$getTransactions->bindValue('userSender', $_SESSION['user'], PDO::PARAM_STR);
$getTransactions->execute();
$numTransactions = $getTransactions->rowCount();
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
    <script src="js/chart.js"></script>
    <script>
    /* will be used later so that page doesn't have to reload */
    /* function for deleting servermessages */
    function delMessage() {
      $.post("include/backend/sysMessage.php", $("#sysMessageForm").serialize())
      .done(
        function(data){
          $("#delMessage").html(data);
        }
      );
    }
    $(function() {
      "use strict";
      // line chart
      var data = {
        labels: [
          <?php
          for($x=6; $x>=0; $x--){
            echo "'" . date('d.m',strtotime(date('Y-m-d') . '-' . $x . 'days')) . "',";
          }
          ?>
        ],
        datasets: [
          {
            label: "Kontostand",
            fillColor: "rgba(151,187,205,0.2)",
            strokeColor: "rgba(151,187,205,1)",
            pointColor: "rgba(151,187,205,1)",
            pointStrokeColor: "#fff",
            pointHighlightFill: "#fff",
            pointHighlightStroke: "rgba(151,187,205,1)",
            data: [
              <?php
              $getBalanceHistory = $db->prepare("SELECT balance FROM History WHERE username=:user AND h_date BETWEEN :last AND :today ORDER BY h_date ASC LIMIT 6");
              $getBalanceHistory->bindValue(":user", $_SESSION["user"], PDO::PARAM_STR);
              $getBalanceHistory->bindValue(":last", date('Y-m-d',strtotime(date('Y-m-d') . '-6 days')), PDO::PARAM_STR);
              $getBalanceHistory->bindValue(":today", date('Y-m-d'), PDO::PARAM_STR);
              $getBalanceHistory->execute();
              # need for fill up
              $counter = 0;
              $historyData = $getBalanceHistory->rowCount();
              foreach($getBalanceHistory as $balanceHistory){
                $counter++;
                echo $balanceHistory["balance"] . ",";
              }
              while($counter<6){
                # fill empty data with zero
                echo "0,";
                $counter++;
              }
              echo $userBalance . ",";
              ?>
            ]
          }
        ]
      };
      new Chart(document.getElementById("balanceChart").getContext("2d")).Line(data,{
        responsive : true,
        bezierCurve: false,
        maintainAspectRatio: false
      });
    });
    </script>
    <aside class="right-side">
      <!-- content -->
      <section class="content">
        <?php
        # handle read sysMessage request
        if(isset($_POST["readSysMessage"])){
          # CSRF-Protection
          if($_POST["token"] != $_SESSION["csrf_token"]){
            exit("Illegaler Zugriffsversuch!");
          }
          # error handling variable
          $error = false;
          # check sysMessage ID
          $checkSysMessage = $db->prepare("SELECT id FROM SysMessage WHERE id=:id AND sys_user=:sys_user AND has_read=0");
          $checkSysMessage->bindValue(":id", $_POST["sysID"], PDO::PARAM_INT);
          $checkSysMessage->bindValue(":sys_user", $_SESSION["user"], PDO::PARAM_STR);
          $checkSysMessage->execute();
          $sysMessageExists = ($checkSysMessage->rowCount() > 0) ? true : false;
          if(!$sysMessageExists){
            # message doesn't exist or is not a user message
            $error = true;
            $errorMessage = "Die Meldung existiert nicht, wurde bereits als gelesen markiert oder sie ist dir nicht zugeordnet. Systemmeldungen können nicht als gelesen markiert werden.";
          }
          if(!$error){
            # mark as read
            $markAsRead = $db->prepare("UPDATE SysMessage SET has_read=1 WHERE id=:id");
            $markAsRead->bindValue(":id", $_POST["sysID"], PDO::PARAM_INT);
            $markAsRead->execute();
            if($markAsRead){
              # successfull
              $successMessage = "Meldung erfolgreich als gelesen markiert.";
            }
          }

        }

        if(isset($_POST["readAllSysMessages"])){
          # CSRF-Protection
          if($_POST["token"] != $_SESSION["csrf_token"]){
            exit("Illegaler Zugriffsversuch!");
          }
          # error handling variable
          $error = false;
          # mark all unread messages as read
          $updateSysMessages = $db->prepare("UPDATE SysMessage SET has_read=1 WHERE AND sys_user=:sys_user AND has_read=0");
          $updateSysMessages->bindValue(":sys_user", $_SESSION["user"], PDO::PARAM_STR);
          $updateSysMessages->execute();
          $sysMessagesUpdated = ($updateSysMessages->rowCount() > 0) ? true : false;
          if(!$sysMessagesUpdated){
            # no messages were updated
            $error = true;
            $errorMessage = "Du hast keine ungelesenen Meldungen.";
          }
          if(!$error){
            $successMessage = "Alle Meldungen wurden erfolgreich als gelesen markiert.";
          }

        }

        if(!empty($errorMessage)){
          # ouput errorMessage
          echo "<div class='alert alert-danger' style='font-weight: bold; text-align: center'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" . $errorMessage . "</div>";
        }
        if(!empty($successMessage)){
          # output successMessage
          echo "<div class='alert alert-success' style='font-weight: bold; text-align: center'><a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>" . $successMessage . "</div>";
        }
        ?>
        <span id="delMessage"></span> <!-- sysMessage alert will appear here -->
        <div class="alert alert-warning" style="text-align: center; font-weight: bold">
          VKBA befindet sich derzeit noch in der First-Access-Phase. Fehler könnten noch auftreten!
        </div>
        <!-- user tiles -->
        <div class="row" style="margin-bottom: 5px">
          <!-- user balance -->
          <div class="col-md-3">
            <div class="sm-st clearfix">
              <span class="sm-st-icon st-blue"><i class="fa fa-dollar"></i></span>
              <div class="sm-st-info">
                <span>
                  <?php
                  echo $userBalance;
                  ?>
                </span>
                Kontostand in Kadis
              </div><!-- sm-st-info -->
            </div><!-- sm-st clearfix -->
          </div><!-- col-md-3 -->
          <!-- user transaction number -->
          <div class="col-md-3">
            <div class="sm-st clearfix">
              <span class="sm-st-icon st-red"><i class="fa fa-exchange"></i></span>
              <div class="sm-st-info">
                <span>
                  <?php
                  echo $numTransactions;
                  ?>
                </span>
                Anzahl der Transaktionen
              </div><!-- sm-st-info -->
            </div><!-- sm-st clearfix -->
          </div><!-- col-md-3 -->
          <!-- user QuickPoints -->
          <div class="col-md-3">
            <div class="sm-st clearfix">
              <span class="sm-st-icon st-violet"><i class="fa fa-money"></i></span>
              <div class="sm-st-info">
                <span>
                  <?php
                  echo $quickPoints;
                  ?>
                </span>
                QuickPoints
              </div><!-- sm-st-info -->
            </div><!-- sm-st clearfix -->
          </div><!-- col-md-3 -->
        <!-- user Account type -->
        <div class="col-md-3">
          <div class="sm-st clearfix">
            <span class="sm-st-icon st-green"><i class="fa fa-credit-card"></i></span>
            <div class="sm-st-info">
              <span>
                <?php
                if($accountType == 0){
                  echo "Girokonto";
                }elseif($accountType == 1){
                  echo "Händlerkonto";
                }
                ?>
              </span>
              Kontotyp
            </div><!-- sm-st-info -->
          </div><!-- sm-st clearfix -->
        </div><!-- col-md-3 -->
      </div><!-- row user tiles -->
      <!-- main content row -->
      <div class="row">
        <div class="col-md-8">
          <!-- balance history chart -->
          <section class="panel">
            <header class="panel-heading">
              Kontostandsverlauf
            </header>
            <div class="panel-body">
              <canvas id="balanceChart" width="600" height="330"></canvas>
            </div>
          </section><!-- panel balance chart -->
        </div><!-- col-md-8 -->
        <!-- notification box -->
        <div class="col-lg-4">
          <section class="panel">
            <header class="panel-heading">
              <form method='post' action='index.php' id='readAllSysMessagesForm'>
                <input type='hidden' name='token' value='<?php echo htmlspecialchars($systemMessage["id"], ENT_QUOTES) ?>' />
                <button name='readAllSysMessages' type='submit' class='close close-sm'><i class='fa fa-times'></i></button>
              </form>
              Systemmeldungen
            </header>
            <div class="panel-box" id="noti-box">
              <?php
              $getSystemMessages = $db->prepare("SELECT id, message, sys_type FROM SysMessage WHERE (sys_user=:user AND has_read=0) OR sys_user='*' ORDER BY id DESC");
              $getSystemMessages->bindValue(":user", $_SESSION["user"], PDO::PARAM_STR);
              $getSystemMessages->execute();
              $messagesExists = ($getSystemMessages->rowCount() > 0) ? true : false;

              if(!$messagesExists){
                echo "<div class='alert alert-block alert-warning'><button data-dismiss='alert' class='close close-sm' type='button'><i class='fa fa-times'></i></button>Keine ungelesenen Meldungen</div>";
              }else{
                foreach($getSystemMessages as $systemMessage){
                  echo "<form method='post' action='index.php' id='sysmessageform'>";
                    if($systemMessage["sys_type"] == 0){
                      echo "<div class='alert alert-block alert-info'>";
                    }elseif($systemMessage["sys_type"] == 1){
                      echo "<div class='alert alert-block alert-success'>";
                    }elseif($systemMessage["sys_type"] == 2){
                      echo "<div class='alert alert-block alert-warning'>";
                    }elseif($systemMessage["sys_type"] == 3){
                      echo "<div class='alert alert-block alert-danger'>";
                    }
                      echo "<input type='hidden' name='sysID' value='" . htmlspecialchars($systemMessage["id"], ENT_QUOTES) . "'>";
                      echo "<input type='hidden' name='token' value='" . $_SESSION["csrf_token"] . "'>";
                      echo "<button name='readSysMessage' type='submit' class='close close-sm'><i class='fa fa-times'></i></button>";
                      echo htmlspecialchars($systemMessage["message"], ENT_QUOTES);
                    echo "</form>";
                  echo "</div>";
                }
              }
              ?>
            </div><!-- end panel-box -->
          </section><!-- end panel section -->
        </div><!-- end col-lg-4 -->
      </div><!-- main content row -->
    </section><!-- content -->
    <div class="footer-main">
        &copy; LEGEND-BANK 2016 - Virtual Kadcon Bank Accounts
    </div>
    </aside>
  </body>
</html>

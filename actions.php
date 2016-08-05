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
        # QuickBuy confirmation message
        if(!empty($qbConfirmMessage)){
          echo $qbConfirmMessage;
        }
        # AddOn message
        if(!empty($addonMessage)){
          echo $addonMessage;
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
            echo "<div class='alert alert-success' style='text-align: center; font-weight: bold'><a href='#' class='lose' data-dismiss='alert' aria-label='close'>&times;</a>Die Aufladung per Code war erfolgreich. Das Geld wurde Dir gutgeschrieben.</div>";
          }else{
            echo "<div class='alert alert-danger' style='text-align: center; font-weight: bold'><a href='#' class='lose' data-dismiss='alert' aria-label='close'>&times;</a>Aufladung per fehlgeschlagen. Code existiert nicht.</div>";
          }
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
                      <a data-toggle="modal" href="#myModal-1"><i class="fa fa-ticket fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
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
                      <a data-toggle="modal" href="#myModal-4"><i class="fa fa-check-circle-o fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Kontoaufladung automatisch*</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#myModal-6"><i class="fa fa-shield fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
                    </div>
                    <h5 class="stat-info" style="background-color: #FAFAFA">Käuferschutz beanspruchen</h5>
                  </div><!-- end stat -->
                </div><!-- end col-md-2 -->
                <div class="col-md-2">
                  <div class="stat">
                    <div class="stat-icon" style="color: #fa8564">
                      <a data-toggle="modal" href="#myModal-1"><i class="fa fa-money fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
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
                      <a data-toggle="modal" href="#myModal-1"><i class="fa fa-star-o fa-3x stat-elem" style="background-color: #FAFAFA"></i></a>
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
          &copy LEGEND-BANK 2016 - Virtual Kadcon Bank Accounts
      </div>
    </aside><!-- end aside -->
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
              <button type="submit" class="btn btn-block btn-primary" name="submitCode">Code einlösen</button>
              <span class="help-block">
                Guthabencodes können am /w Legend auf Server 1 erworben werden. Dieses Verfahren eignet sich, wenn besonders schnell Guthaben benötigt wird.
                Jeder Code kann nur einmal eingelöst werden. Die Gutschrift erfolgt unmittelbar nach Eingabe.
                Alternativ gibt es auch die automatische Kontoaufladung, welche für erhöhten Komfort sorgt.
            </span>
          </div>
        </div><!-- end modal-content -->
      </div><!-- end modal-dialog -->
    </div><!-- end modal -->
  </body><!-- end body -->
</html><!-- end html -->

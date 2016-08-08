<?php
# handle buyAddOn
if(isset($_POST["buyAddOn"])){
  # CSRF-Protection
  if($_POST["token"] != $_SESSION["csrf_token"]){
    exit("Illegaler Zugriffsversuch!");
  }
  # error handling variable
  $error = false;
  # check if submitted addOnID is valid
  $availableAddOns = array(1, 2);
  if(!in_array($_POST["addOnID"], $availableAddOns)){
    # unknown addOn
    $error = true;
    $errorMessage = "Dieses AddOn existiert nicht.";
  }
  # check if user already has AddOn
  $checkAddOn = $db->prepare("SELECT add_id FROM AddOns WHERE username=:username AND add_id=:add_id");
  $checkAddOn->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
  $checkAddOn->bindValue(":add_id", $_POST["addOnID"], PDO::PARAM_INT);
  $checkAddOn->execute();
  $hasAddOn = ($checkAddOn->rowCount() > 0) ? true : false;
  if($hasAddOn){
    # user has AddOn
    $error = true;
    $errorMessage = "Du besitzt dieses AddOn bereits.";
  }
  # check if AddOn is for specified account type
  if($_POST["addOnID"] == 2){
    if($_SESSION["ktype"] != 0){
      # not for this type
      $error = true;
      $errorMessage = "Dieses AddOn ist nicht für Deinen Kontotypen verfügbar.";
    }
  }
  if(!$error){
    # buy AddOn
    $buyAddOn = $db->prepare("INSERT INTO AddOns (add_id, username) VALUES (:add_id, :username)");
    $buyAddOn->bindValue(":add_id", $_POST["addOnID"], PDO::PARAM_INT);
    $buyAddOn->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
    $buyAddOn->execute();
    if($buyAddOn){
      # successfull
      $successMessage = "AddOn-Kauf war erfolgreich. Viel Spaß!";
    }
  }
}
# handle deleteAddOn
if(isset($_POST["deleteAddOn"])){
  # CSRF-Protection
  if($_POST["token"] != $_SESSION["csrf_token"]){
    exit("Illegaler Zugriffsversuch!");
  }
  # error handling variable
  $error = false;
  # check if submitted addOnID is valid
  $availableAddOns = array(1, 2);
  if(!in_array($_POST["addOnID"], $availableAddOns)){
    # unknown addOn
    $error = true;
    $errorMessage = "Dieses AddOn existiert nicht.";
  }
  # check if user really has AddOn
  $checkAddOn = $db->prepare("SELECT add_id FROM AddOns WHERE username=:username AND add_id=:add_id");
  $checkAddOn->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
  $checkAddOn->bindValue(":add_id", $_POST["addOnID"], PDO::PARAM_INT);
  $checkAddOn->execute();
  $hasAddOn = ($checkAddOn->rowCount() > 0) ? true : false;
  if(!$hasAddOn){
    # user doesn't have AddOn
    $error = true;
    $errorMessage = "Du besitzt dieses AddOn gar nicht.";
  }
  # check if AddOn is for specified account type
  if($_POST["addOnID"] == 2){
    if($_SESSION["ktype"] != 0){
      # not for this type
      $error = true;
      $errorMessage = "Dieses AddOn ist nicht für Deinen Kontotypen verfügbar.";
    }
  }
  # calculate termination fees
  if($_POST["addOnID"] == 1){
    $terminationFees = (30/100) * 75;
  }elseif($_POST["addOnID"] == 2){
    $terminationFees = (65/100) * 75;
  }
  # get actual balance
  $getUserBalance = $db->prepare("SELECT balance FROM Accounts WHERE username=:username");
  $getUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
  $getUserBalance->execute();
  foreach($getUserBalance as $balance){
    $userBalance = $balance["balance"];
  }
  if(empty($terminationFees)){
    # wrong AddOn
    $error = true;
  }else{
    if($userBalance < $terminationFees){
      # not enough money for termination
      $error = true;
      $errorMessage = "Du hast nicht genügend Geld, um dieses AddOn zu kündigen.";
    }else{
      # calculate new balance
      $newUserBalance = round($userBalance - $terminationFees, 2);
    }
  }
  # check if user has enough money for termination
  if(!$error){
    # update user balance
    $updateUserBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
    $updateUserBalance->bindValue(":balance", $newUserBalance, PDO::PARAM_STR);
    $updateUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
    $updateUserBalance->execute();
    # delete AddOn
    $deleteAddOn = $db->prepare("DELETE FROM AddOns WHERE username=:username AND add_id=:add_id");
    $deleteAddOn->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
    $deleteAddOn->bindValue(":add_id", $_POST["addOnID"], PDO::PARAM_INT);
    $deleteAddOn->execute();
    if($deleteAddOn){
      # successfull
      $successMessage = "AddOn-Kündigung war erfolgreich. Du kannst es jederzeit wieder aktivieren!";
    }
  }
}
?>
<header class="header">
  <a href="index.php" class="logo">
    VKBA Rewrite
  </a>
  <nav class="navbar navbar-static-top" role="navigation">
    <!-- sidebar toggle button -->
    <a href="#" class="navbar-btn sidebar-toggle" data-toggle="offcanvas" role="button">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </a>
    <div class="navbar-right">
      <ul class="nav navbar-nav">
        <li class="dropdown messages-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <i class="fa fa-inbox"></i>
            <span class="label label-primary">0</span>
          </a>
          <ul class="dropdown-menu">
            <li class="header">0 neue Transaktionen</li>
              <li>
                <!-- will contain unread transactions in the future -->
                <ul class="menu">
                  <li>
                    <a href="#">
                      <div class="pull-left">
                        <img src="" class="img-circle" alt="user image" />
                      </div>
                      <h4>Benutzer</h4>
                      <p>Derzeit noch nicht verfügbar.</p>
                      <small class="pull-right"><i class="fa fa-clock-o"></i> 00:00</small>
                    </a>
                  </li>
                </ul><!-- end menu -->
              </li>
              <li class="footer"><a href="#">Alle Transaktionen einsehen</a></li>
            </ul><!-- end dropdown-menu -->
          </li><!-- end dropdown messages-menu -->
          <!-- user account menu -->
          <li class="dropdown user user-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-user"></i>
              <span><?php echo $_SESSION['user']; ?><i class="caret"></i></span>
            </a>
            <ul class="dropdown-menu dropdown-custom dropdown-menu-right">
              <li class="dropdown-header text-center">VKBA-Account</li>
              <li>
                <a data-toggle="modal" href="#modalAddOn">
                  <i class="fa fa-plus fa-fw pull-right"></i>
                  <span class="badge badge-success pull-right">2</span> Add-Ons
                </a>
                <a data-toggle="modal" href="#modalContacts">
                  <i class="fa fa-users fa-fw pull-right"></i>
                  <span class="badge badge-danger pull-right">0</span> Kontakte
                </a>
                <a data-toggle="modal" href="#modalSettings">
                  <i class="fa fa-cog fa-fw pull-right"></i>
                  VKBA-Einstellungen
                </a>
              </li>
              <li class="divider"></li>
              <li>
                <a href="logout/"><i class="fa fa-sign-out fa-fw pull-right"></i> Logout</a>
              </li>
            </ul>
          </li><!-- end dropdown user user-menu -->
        </ul><!-- end nav navbar-nav -->
      </div><!-- end navbar-right -->
    </nav><!-- end navbar navbar-static-top -->
</header>

<!-- Modal AddOns-->
<div class="modal fade" id="modalAddOn" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
        <h4 class="modal-title">Add-Ons</h4>
      </div>
      <div class="modal-body">
        <form method="post" action="">
          <div class="col-md-12">
            <div class="sm-st clearfix">
              <div class="sm-st-info">
                <?php
                # get AddOn1
                $addOnONE = $db->prepare("SELECT id FROM AddOns WHERE username=:username AND add_id=1");
                $addOnONE->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
                $addOnONE->execute();
                $hasAddOnONE = ($addOnONE->rowCount() > 0) ? true : false;
                # check if user has AddOn1
                if($hasAddOnONE){
                  # user has AddOn1
                  echo "<input type='hidden' name='token' value='" . $_SESSION["csrf_token"] . "'>";
                  echo "<input type='hidden' name='addOnID' value=1>";
                  echo "<button type='submit' name='deleteAddOn' class='btn btn-default btn-sm pull-right'>";
                    echo "<span style='color: #DC2E31' class='glyphicon glyphicon-remove' aria-hidden='true'></span>";
                  echo "</button>";
                }else{
                  # user has not AddOn1
                  echo "<input type='hidden' name='token' value='" . $_SESSION["csrf_token"] . "'>";
                  echo "<input type='hidden' name='addOnID' value=1>";
                  echo "<button type='submit' name='buyAddOn' class='btn btn-default btn-sm pull-right'>";
                    echo "<span style='color: #088A08' class='glyphicon glyphicon-plus' aria-hidden='true'></span>";
                  echo "</button>";
                }
                ?>
                <span>Werbe-AddOn</span>
                Das AddOn ermöglicht Dir das Schalten eigener Werbung, bspw. für Deinen Warp auf Kadcon oder aber auch für eigene QuickBuy-Inserate. Die Werbung wird zufällig auf QuickBuy geschaltet.<br>
                Sie bleibt eine Woche lang aktiv. Danach kannst Du sie unter Aktionen 'Werbung schalten' wieder reaktivieren oder den Text ändern. Du kannst bis zu drei Werbungen pro Woche schalten.<br>
                Die erste Werbung pro Woche ist immer gratis, jede weitere kostet Dich einmalig 25 Kadis. Den AddOn-Preis zahlst Du wöchentlich immer gleich, unabhängig davon, wie viele aktiv sind.<br>
                <br>
                <b>Kosten:</b> 30 Kadis/Woche
              </div><!-- end sm-st-info -->
            </div><!-- end sm-st clearfix -->
          </div><!-- end col-md-12 -->
        </form><!-- end form -->
        <form method="post" action="">
          <div class="col-md-12">
            <div class="sm-st clearfix">
              <div class="sm-st-info">
                <?php
                # get AddOn1
                $addOnTWO = $db->prepare("SELECT id FROM AddOns WHERE username=:username AND add_id=2");
                $addOnTWO->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
                $addOnTWO->execute();
                $hasAddOnTWO = ($addOnTWO->rowCount() > 0) ? true : false;
                # check if user has AddOn1
                if($hasAddOnTWO){
                  # user has AddOn1
                  echo "<input type='hidden' name='token' value='" . $_SESSION["csrf_token"] . "'>";
                  echo "<input type='hidden' name='addOnID' value=2>";
                  echo "<button type='submit' name='deleteAddOn' class='btn btn-default btn-sm pull-right'>";
                    echo "<span style='color: #DC2E31' class='glyphicon glyphicon-remove' aria-hidden='true'></span>";
                  echo "</button>";
                }else{
                  # user has not AddOn1
                  echo "<input type='hidden' name='token' value='" . $_SESSION["csrf_token"] . "'>";
                  echo "<input type='hidden' name='addOnID' value=2>";
                  echo "<button type='submit' name='buyAddOn' class='btn btn-default btn-sm pull-right'>";
                    echo "<span style='color: #088A08' class='glyphicon glyphicon-plus' aria-hidden='true'></span>";
                  echo "</button>";
                }
                ?>
                <span>Händler-AddOn</span>
                Dieses AddOn ermöglicht das Erstellen eigener Inserate in QuickBuy für <b>Girokonten</b>.
                <br>
                <b>Kosten:</b> 65 Kadis/Woche
              </div><!-- end sm-st-info -->
            </div><!-- end sm-st clearfix -->
          </div><!-- end col-md-12 -->
        </form><!-- end form -->
        <br><br>
        <span class="help-block">
          AddOns können jederzeit gekündigt werden.
          Um Missbrauch zu vermeiden, wird bei der Kündigung ein einmaliger Betrag in Höhe der wöchentlichen Kosten zuzüglich einer kleinen Gebühr in Rechnung gestellt.
          Die wöchentlichen AddOn-Kosten werden - unabhängig vom Kaufdatum - immer sonntags automatisch abgebucht. AddOns können jederzeit wieder gekauft werden.
          Hat der Spieler nicht genügend Geld, die laufenden AddOn-Kosten zu tragen, so wird das AddOn automatisch gekündigt und eine Gebühr in Rechnung gestellt, welche automatisch abgebucht wird.
        </span>
      </div><!-- end modal-body -->
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</div><!-- end modal -->

<!-- Modal contacts -->
<div class="modal fade" id="modalContacts" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
        <h4 class="modal-title">Kontakte</h4>
      </div>
      <div class="modal-body">
        Kontakte werden in naher Zukunft freigeschaltet.
      </div>
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</div><!-- end modal -->

<!-- Modal VKBA settings -->
<div class="modal fade" id="modalSettings" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
        <h4 class="modal-title">VKBA-Einstellungen</h4>
      </div>
      <div class="modal-body">
        <form method="post" action="">
          <?php
          //TODO
          ?>
          <label>Username</label>
          <p><?php echo $_SESSION["user"]; ?></p>
          <label>Kontonummer</label>
          <p><?php echo $_SESSION["ktn_nr"]; ?></p>
          <label>Kontotyp</label>
          <p><?php if($_SESSION["ktype"] == 0){ echo "Girokonto";}else{ echo "Händlerkonto"; } ?></p>
          <label>SKey (<u>niemals</u> an Dritte weitergeben!)</label>
          <p><?php echo $_SESSION["skey"]; ?></p>
          <label>AKey (<u>niemals</u> an Dritte weitergeben!)</label>
          <p><?php echo $_SESSION["akey"]; ?></p>
          <label>QuickBuy-Kaufbestätigung</label>
          <select class="form-control" style="width: 33%">
            <?php
            if($quickBuyConfirmation){
              echo "<option selected value=1>Ja</option>";
              echo "<option value=0>Nein</option>";
            }else{
              echo "<option value=1>Ja</option>";
              echo "<option selected value=0>Nein</option>";
            }
            ?>
          </select>
        </form>
      </div><!-- end modal-body -->
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</div><!-- end modal -->

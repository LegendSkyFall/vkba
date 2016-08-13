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
# handle donation
if(isset($_POST["donate"])){
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
  # check if user has enough money for donation
  if($userBalance < 100){
    $errorMessage = "Du hast leider nicht genügend Geld, um eine kleine Spende in Höhe von 100 Kadis zu hinterlassen.";
  }else{
    # calculate new balance
    $newUserBalance = $userBalance - 100;
    # update user balance
    $updateUserBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
    $updateUserBalance->bindValue(":balance", $newUserBalance, PDO::PARAM_STR);
    $updateUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
    $updateUserBalance->execute();
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
    $logTransaction->bindValue(":t_description", "Spende", PDO::PARAM_STR);
    $logTransaction->bindValue(":t_adress", "VKBA-Bot", PDO::PARAM_STR);
    $logTransaction->bindValue(":t_sender", $_SESSION["user"], PDO::PARAM_STR);
    $logTransaction->bindValue(":t_amount", 100, PDO::PARAM_STR);
    $logTransaction->bindValue(":t_date", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $logTransaction->execute();
    if($logTransaction){
      # successfull
      $successMessage = "Vielen Dank für Deine Spende. Der VKBA-Bot arbeitet immer fleißig im Hintergrund, damit alles einwandfrei funktioniert. Jetzt arbeitet er noch motivierter ;)";
    }
  }
}
# handle add contact
if(isset($_POST["addContact"])){
  # CSRF-Protection
  if($_POST["token"] != $_SESSION["csrf_token"]){
    exit("Illegaler Zugriffsversuch!");
  }
  # error handling variable
  $error = false;
  # check if posted ktnNr is an integer
  if(!filter_var($_POST["contactKtnNr"], FILTER_VALIDATE_INT)){
    # unexpected value
    $error = true;
    $errorMessage = "Die Kontonummer muss eine Zahl sein.";
  }else{
    # check if ktnNr exists
    $checkKtnNr = $db->prepare("SELECT username FROM Accounts WHERE ktn_nr=:ktn_nr");
    $checkKtnNr->bindValue(":ktn_nr", $_POST["contactKtnNr"], PDO::PARAM_INT);
    $checkKtnNr->execute();
    $ktnNrExists = ($checkKtnNr->rowCount() > 0) ? true : false;
    if(!$ktnNrExists){
      # ktnNr doesn't exist
      $error = true;
      $errorMessage = "Die angegebene Kontonummer ist dem System nicht bekannt.";
    }else{
      # get username
      foreach($checkKtnNr as $ktnNr){
        $contactUsername = $ktnNr["username"];
      }
      if($contactUsername == $_SESSION["user"]){
        # own
        $error = true;
        $errorMessage = "Du kannst Dich nicht selbst als Kontakt einspeichern.";
      }
    }
  }
  if(!$error){
    # special bonus
    if(date("Y-m-d") == "2016-08-13"){
      $checkBonus = $db->prepare("SELECT counter FROM BonusCounter WHERE username=:username AND type=0");
      $checkBonus->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
      $checkBonus->execute();
      $hasBonus = ($checkBonus->rowCount() > 0) ? true : false;
      if(!$hasBonus){
        # insert bonus
        $insertBonus = $db->prepare("INSERT INTO BonusCounter (username, type, counter) VALUES (:username, 1, 1)");
        $insertBonus->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
        $insertBonus->execute();
        # special credit
        $getBalance = $db->prepare("SELECT balance FROM Accounts WHERE username=:username");
        $getBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
        $getBalance->execute();
        foreach($getBalance as $balance){
          # get actual balance
          $actualBalance = $balance["balance"];
        }
        # calculate new balance
        $newBalance = $actualBalance + 250;
        # update balance
        $updateBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
        $updateBalance->bindValue(":balance", $newBalance, PDO::PARAM_STR);
        $updateBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
        $updateBalance->execute();
        # create system message for user
        $createSysMessage = $db->prepare("INSERT INTO SysMessage (sys_user, message, sys_type) VALUES (:sys_user, :message, 1)");
        $createSysMessage->bindValue(":sys_user", $_SESSION["user"], PDO::PARAM_STR);
        $createSysMessage->bindValue(":message", "Du hast heute einen Bonus gutgeschrieben bekommen, weil Du die Bedingungen für die Bonusaktion erfüllt hast!", PDO::PARAM_STR);
        $createSysMessage->execute();
        if($createSysMessage){
          # successfull
          $bonusMessage = "Bonusaktion erfüllt! Dir wurden dafür 250 Kadis gutgeschrieben.";
        }
      }
    }
    # send info to added contact
    $createSysMessage = $db->prepare("INSERT INTO SysMessage (sys_user, message, sys_type) VALUES (:sys_user, :message, 1)");
    $createSysMessage->bindValue(":sys_user", $contactUsername, PDO::PARAM_STR);
    $createSysMessage->bindValue(":message", "Du wurdest von " . $_SESSION["user"] . " als Kontakt hinzugefügt.", PDO::PARAM_STR);
    $createSysMessage->execute();
    # insert contact
    $insertContact = $db->prepare("INSERT INTO Contacts (contact_ktnNr, contact_username, contact_user) VALUES (:contact_ktnNr, :contact_username, :contact_user)");
    $insertContact->bindValue(":contact_ktnNr", $_POST["contactKtnNr"], PDO::PARAM_INT);
    $insertContact->bindValue(":contact_username", $contactUsername, PDO::PARAM_STR);
    $insertContact->bindValue(":contact_user", $_SESSION["user"], PDO::PARAM_STR);
    $insertContact->execute();
    if($insertContact){
      # successfull
      $successMessage = "Der Kontakt wurde erfolgreich hinzugefügt. Zukünftig kannst Du einfach auf die entsprechende Schaltfläche beim Kontakt betätigen, um die Kontonummer direkt kopieren zu können.";
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
<script>
$(function() {
    "use strict";

    //Enable sidebar toggle
    $("[data-toggle='offcanvas']").click(function(e) {
        e.preventDefault();

        //If window is small enough, enable sidebar push menu
        if ($(window).width() <= 992) {
            $('.row-offcanvas').toggleClass('active');
            $('.left-side').removeClass("collapse-left");
            $(".right-side").removeClass("strech");
            $('.row-offcanvas').toggleClass("relative");
        } else {
            //Else, enable content streching
            $('.left-side').toggleClass("collapse-left");
            $(".right-side").toggleClass("strech");
        }
    });

    //Add hover support for touch devices
    $('.btn').bind('touchstart', function() {
        $(this).addClass('hover');
    }).bind('touchend', function() {
        $(this).removeClass('hover');
    });

    //Activate tooltips
    $("[data-toggle='tooltip']").tooltip();

    /*
     * Add collapse and remove events to boxes
     */
    $("[data-widget='collapse']").click(function() {
        //Find the box parent
        var box = $(this).parents(".box").first();
        //Find the body and the footer
        var bf = box.find(".box-body, .box-footer");
        if (!box.hasClass("collapsed-box")) {
            box.addClass("collapsed-box");
            //Convert minus into plus
            $(this).children(".fa-minus").removeClass("fa-minus").addClass("fa-plus");
            bf.slideUp();
        } else {
            box.removeClass("collapsed-box");
            //Convert plus into minus
            $(this).children(".fa-plus").removeClass("fa-plus").addClass("fa-minus");
            bf.slideDown();
        }
    });
  });
</script>
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
        <div class="row">
          <div class="col-lg-12">
            <form method="post">
              <div class="input-group">
                <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="number" class="form-control" required="required" min="10000000" max="99999999" name="contactKtnNr"placeholder="Kontonummer">
                <span class="input-group-btn">
                  <button class="btn btn-default" type="submit" name="addContact">Als Kontakt speichern</button>
                </span>
              </div><!--  end input-group -->
            </form><!-- end form -->
            <br>
            <p style="text-align: center; font-style: italic">Spenden für den VKBA-Bot betragen immer 100 Kadis</p>
          </div><!-- end col-lg-6 -->
        </div><!-- end row -->
        <div class="form-group">
          <div class="user-panel">
            <div class="pull-left image">
              <?php
              $getAvatar = "https://cravatar.eu/avatar/" . "VKBABot";
              echo "<img src='$getAvatar' class='img-circle' alt='Avatar'/>";
              ?>
            </div>
            <div class="pull-left info" style="color: black">
              <?php
              echo "<p>VKBA-Bot</p>";
              ?>
              <form method="post">
                <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" class="btn btn-danger" name="donate"><span class="glyphicon glyphicon-heart" aria-hidden="true"></span> Spenden</button>
              </form>
            </div>
          </div><!-- end user-panel -->
          <hr>
        </div>
        <?php
        $getContacts = $db->prepare("SELECT contact_ktnNr, contact_username FROM Contacts WHERE contact_user=:contact_user");
        $getContacts->bindValue(":contact_user", $_SESSION["user"], PDO::PARAM_STR);
        $getContacts->execute();
        foreach($getContacts as $contact){
          echo "<div class='form-group'>";
            echo "<div class='user-panel'>";
              echo "<div class='pull-left image'>";
                $getAvatar = "https://cravatar.eu/avatar/" . htmlspecialchars($contact["contact_username"], ENT_QUOTES);
                echo "<img src='$getAvatar' class='img-circle' alt='Avatar' />";
              echo "</div>";
              echo "<div class='pull-left info' style='color: black'>";
                echo "<p>" . htmlspecialchars($contact["contact_username"], ENT_QUOTES) . "</p>";
                echo "<button onclick='copyToClipboard(this.innerText)' type='button' class='btn btn-primary'><span class='glyphicon glyphicon-copy' aria-hidden='true'></span> " . htmlspecialchars($contact["contact_ktnNr"], ENT_QUOTES) . "</button>";
              echo "</div>";
            echo "</div>";
            echo "<hr>";
          echo "</div>";
        }
        ?>
      </div><!-- end modal-body -->
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</div><!-- end modal -->
<script>
// copy function for contacts
function copyToClipboard(text) {
  window.prompt("Zum Kopieren STRG+C, Enter drücken", text);
}
</script>

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
          # get confirmation settings for QuickBuy adverts
          $getConfirmation = $db->prepare("SELECT qb_confirm FROM Accounts WHERE username=:username");
          $getConfirmation->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
          $getConfirmation->execute();
          foreach($getConfirmation as $confirmationState){
            $quickBuyConfirmation = $confirmationState["qb_confirm"];
          }
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
          <button type="submit" class="btn btn-success" name="saveSettings">Einstellungen speichern</button>
        </form>
      </div><!-- end modal-body -->
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</div><!-- end modal -->

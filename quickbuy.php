<?php
# start session
session_start();
# deny access if not logged in
if(!isset($_SESSION['user'])){
  header("Location: login/");
  exit();
}
# require db
require("db/pdo.inc.php");

# get and count active products
$getActiveProducts = $db->prepare("SELECT qb_id FROM QuickBuy WHERE bought=0");
$getActiveProducts->execute();
$countActiveProducts = $getActiveProducts->rowCount();

# get and count bought products
$getBoughtProducts = $db->prepare("SELECT qb_id, qb_creator, qb_product, qb_short, qb_price FROM QuickBuy WHERE bought=1 AND bought_by=:user ORDER BY time_update DESC");
$getBoughtProducts->bindValue(":user", $_SESSION["user"], PDO::PARAM_STR);
$getBoughtProducts->execute();
$countBoughtProducts = $getBoughtProducts->rowCount();

# get and count sold products
$getSoldProducts = $db->prepare("SELECT qb_id, qb_creator, qb_product, qb_short, qb_price, bought_by FROM QuickBuy WHERE bought=1 AND qb_creator=:user ORDER BY time_update DESC");
$getSoldProducts->bindValue(":user", $_SESSION["user"]. PDO::PARAM_STR);
$getSoldProducts->execute();
$countSoldProducts = $getSoldProducts->rowCount();

# handle buy request
if(isset($_POST["buyAdvert"])){
  # CSRF-Protection
  if($_POST["token"] != $_SESSION["csrf_token"]){
    exit("Illegaler Zugriffsversuch!");
  }
  # error handling variable
  $error = false;
  # get QuickBuy Advert
  $getQuickBuyAdvert = $db->prepare("SELECT qb_creator, qb_price FROM QuickBuy WHERE qb_id=:qb_id AND bought=0");
  $getQuickBuyAdvert->bindValue(":qb_id", $_POST["qbID"], PDO::PARAM_INT);
  $getQuickBuyAdvert->execute();
  $advertExists = ($getQuickBuyAdvert->rowCount() > 0) ? true : false;
  if(!$advertExists){
    # advert doesn't exists or was already bought
    $error = true;
    $errorMessage = "Das Inserat existiert nicht oder wurde bereits von einem anderen Spieler gekauft.";
  }else{
    foreach($getQuickBuyAdvert as $quickBuyAdvert){
      $qbCreator = $quickBuyAdvert["qb_creator"];
      $qbPrice = $quickBuyAdvert["qb_price"];
    }
    # check creator
    if($qbCreator == $_SESSION["user"]){
      # buying own adverts is not allowed
      $error = true;
      $errorMessage = "Du kannst nicht Deine eigenen Inserate kaufen.";
    }
    # fetch users balance
    $getBalance = $db->prepare("SELECT balance FROM Accounts WHERE username=:username");
    $getBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
    $getBalance->execute();
    foreach($getBalance as $balance){
      $userBalance = $balance["balance"];
    }
    # check price
    if($userBalance < $qbPrice){
      # not enough money
      $error = true;
      $errorMessage = "Du hast nicht genügend Geld, um das Inserat zu kaufen.";
    }
    # get creators balance
    $getOtherBalance = $db->prepare("SELECT balance FROM Accounts WHERE username=:username");
    $getOtherBalance->bindValue(":username", $qbCreator, PDO::PARAM_STR);
    $getOtherBalance->execute();
    foreach($getOtherBalance as $otherBalance){
      $creatorBalance = $otherBalance["balance"];
    }
    # calculate new balances
    $newUserBalance = $userBalance - $qbPrice;
    $newCreatorBalance = $creatorBalance + $qbPrice;
  }
  if(!$error){
    # buy QuickBuy advert
    $buyAdvert = $db->prepare("UPDATE QuickBuy SET bought=1, bought_by=:bought_by WHERE qb_id=:qb_id");
    $buyAdvert->bindValue(":bought_by", $_SESSION["user"], PDO::PARAM_STR);
    $buyAdvert->bindValue(":qb_id", $_POST["qbID"], PDO::PARAM_INT);
    $buyAdvert->execute();
    # update users balance
    $updateUserBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
    $updateUserBalance->bindValue(":balance", $newUserBalance, PDO::PARAM_STR);
    $updateUserBalance->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
    $updateUserBalance->execute();
    # update creators balance
    $updateCreatorBalance = $db->prepare("UPDATE Accounts SET balance=:balance WHERE username=:username");
    $updateCreatorBalance->bindValue(":balance", $newCreatorBalance, PDO::PARAM_STR);
    $updateCreatorBalance->bindValue(":username", $qbCreator, PDO::PARAM_STR);
    $updateCreatorBalance->execute();
    # create system message for creator
    $createSysMessage = $db->prepare("INSERT INTO SysMessage (sys_user, message, sys_type) VALUES (:sys_user, :message, 0)");
    $createSysMessage->bindValue(":sys_user", $qbCreator, PDO::PARAM_STR);
    $createSysMessage->bindValue(":message", "Dein QuickBuy Inserat #" . $_POST["qbID"] . " wurde von " . $_SESSION["user"] . " gekauft.", PDO::PARAM_STR);
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
    $logTransaction->bindValue(":t_description", "QuickBuy (#" . $_POST["qbID"] . ")", PDO::PARAM_STR);
    $logTransaction->bindValue(":t_adress", $qbCreator, PDO::PARAM_STR);
    $logTransaction->bindValue(":t_sender", $_SESSION["user"], PDO::PARAM_STR);
    $logTransaction->bindValue(":t_amount", $qbPrice, PDO::PARAM_STR);
    $logTransaction->bindValue(":t_date", date("Y-m-d H:i:s"), PDO::PARAM_STR);
    $logTransaction->execute();
    # buy successfull
    $successMessage = "Kauf des Inserats erfolgreich. Der Käufer wurde darüber benachrichtigt.";
  }
}
# handle create request
if(isset($_POST["createAdvert"])){
  # CSRF-Protection
  if($_POST["token"] != $_SESSION["csrf_token"]){
    exit("Illegaler Zugriffsversuch!");
  }
  # error handling variable
  $error = false;
  # check if product name is valid
  if(strlen($_POST["productName"]) == 0){
    # no product name submitted
    $error = true;
    $errorMessage = "Du musst einen Produktnamen angeben.";
  }
  # check if product name is too long
  if(strlen($_POST["productName"]) > 50){
    # product name too long
    $error = true;
    $errorMessage = "Der Produktname ist zu lang.";
  }
  # check if product description is valid
  if(strlen($_POST["productDescription"]) == 0){
    # no product description submitted
    $error = true;
    $errorMessage = "Du musst eine Kurzbeschreibung angeben.";
  }
  # check if product description is too long
  if(strlen($_POST["productDescription"]) > 75){
    # product description is too long
    $error = true;
    $errorMessage = "Die Kurzbeschreibung ist zu lang.";
  }
  # check if product price is a number
  if(!is_numeric($_POST["productPrice"])){
    # unexpected value
    $error = true;
    $errorMessage = "Der Preis ist ungültig.";
  }
  # check if product price is negative
  if($_POST["productPrice"] <= 0){
    # negative product price
    $error = true;
    $errorMessage = "Der Preis darf nicht negativ sein.";
  }
  # check if product price is too high
  if($_POST["productPrice"] > 9999){
    # product price is too high
    $error = true;
    $errorMessage = "Der Preis darf 9999 Kadis nicht übersteigen.";
  }
  # check account type
  if($_SESSION["ktype"] != 1){
    # no trader account, check if trader addon is active
    $checkTraderAddon = $db->prepare("SELECT username FROM AddOns WHERE add_id=2 AND username=:username");
    $checkTraderAddon->bindValue(":username", $_SESSION["user"], PDO::PARAM_STR);
    $checkTraderAddon->execute();
    $hasTraderAddon = ($checkTraderAddon->rowCount() > 0) ? true : false;
    if(!$hasTraderAddon){
      # trader addon not activated
      $error = true;
      $errorMessage = "Du benötigst ein Händler-Konto bzw. das Werbe-Addon, um ein Inserat erstellen zu können.";
    }
  }
  if(!$error){
    # generate random quickbuy id and check whether quickbuy id already exists
    $randQuickBuyID = mt_rand(10000000, 99999999);
    $checkQuickBuyID = $db->prepare("SELECT qb_id FROM QuickBuy WHERE qb_id=:qb_id");
    $checkQuickBuyID->bindValue(":qb_id", $randQuickBuyID, PDO::PARAM_INT);
    $checkQuickBuyID->execute();
    $quickBuyIDExists = ($checkQuickBuyID->rowCount() > 0) ? true : false;
    foreach($checkQuickBuyID as $quickBuy){
      $quickBuyID = $quickBuy["qb_id"];
    }
    if($quickBuyIDExists){
      # generate new random id
      while($randQuickBuyID == $quickBuyID){
        $randQuickBuyID = mt_rand(100000000, 999999999);
        $checkQuickBuyID = $db->prepare("SELECT qb_id FROM QuickBuy WHERE qb_id=:qb_id");
        $checkQuickBuyID->bindValue(":qb_id", $randQuickBuyID, PDO::PARAM_INT);
        $checkQuickBuyID->execute();
        foreach($checkQuickBuyID as $quickBuy){
          $quickBuyID = $quickBuy["qb_id"];
        }
      }
    }
    $insertAdvert = $db->prepare("INSERT INTO QuickBuy (qb_id, qb_creator, qb_product, qb_short, qb_price) VALUES (:qb_id, :qb_creator, :qb_product, :qb_short, :qb_price)");
    $insertAdvert->bindValue(":qb_id", $randQuickBuyID, PDO::PARAM_INT);
    $insertAdvert->bindValue(":qb_creator", $_SESSION["user"], PDO::PARAM_STR);
    $insertAdvert->bindValue(":qb_product", $_POST["productName"], PDO::PARAM_STR);
    $insertAdvert->bindValue(":qb_short", $_POST["productDescription"], PDO::PARAM_STR);
    $insertAdvert->bindValue(":qb_price", $_POST["productPrice"], PDO::PARAM_STR);
    $insertAdvert->execute();
    if($insertAdvert){
      # advert creation successfull
      $successMessage = "Die Erstellung des Inserats war erfolgreich. Du wirst benachrichtigt, wenn es von einem Spieler gekauft wird.";
    }else{
      # an error occured
      $errorMessage = "Ein unbekannter Fehler beim Erstellen des Inserats ist aufgetreten.";
    }
  }
}
?>
<!DOCTYPE html>
<html>
<?php
include("include/head.php");
?>
<body class="skin-black">
  <?php
  include("include/header.php");
  include("include/usernav.php");
  ?>
  <aside class="right-side">
    <section class="content">
      <div class="row">
        <?php
        if(!empty($errorMessage)){
          # ouput errorMessage
          echo "<div class='alert alert-danger' style='font-weight: bold; text-align: center'>" . $errorMessage . "</div>";
        }
        if(!empty($successMessage)){
          # output successMessage
          echo "<div class='alert alert-success' style='font-weight: bold; text-align: center'>" . $successMessage . "</div>";
        }
        ?>
        <!-- row for overview -->
        <div class="col-md-2">
          <div class="stat">
            <div class="stat-icon" style="color: darkblue">
              <i class="fa fa-exchange fa-3x stat-elem"></i>
            </div>
            <h5 class="stat-info">Anzahl aller aktiven Produkte: <?php echo $countActiveProducts; ?></h5>
          </div><!-- end stat -->
        </div><!-- end col-md-2 -->
        <div class="col-md-2">
          <div class="stat">
            <div class="stat-icon" style="color: darkblue">
              <a data-toggle="modal" href="#myModalQBbought"><i class="fa fa-shopping-cart fa-3x stat-elem"></i></a>
            </div>
            <h5 class="stat-info">Anzahl gekaufter Produkte: <?php echo $countBoughtProducts; ?></h5>
          </div><!-- end stat -->
        </div><!-- end col-md-2 -->
        <div class="col-md-2">
          <div class="stat">
            <div class="stat-icon" style="color: darkblue">
              <a data-toggle="modal" href="#myModalQBsold"><i class="fa fa-money fa-3x stat-elem"></i></a>
            </div>
            <h5 class="stat-info">Anzahl verkaufter Produkte: <?php echo $countSoldProducts; ?></h5>
          </div><!-- end stat -->
        </div><!-- end col-md-2 -->
        <div class="col-md-2">
          <div class="stat">
            <div class="stat-icon" style="color: darkblue">
              <a data-toggle="modal" href="#myModalQB"><i class="fa fa-plus fa-3x stat-elem"></i></a>
            </div>
            <h5 class="stat-info">Eigenes Inserat erstellen</h5>
          </div><!-- end stat -->
        </div><!-- end col-md-2 -->
      </div><!-- end row -->
      <br>
      <!-- row for quickbuy products -->
      <div class="row" style="margin-bottom: 5px">
        <?php
        # check confirm setting
        $getConfirmState = $db->prepare("SELECT qb_confirm FROM Accounts WHERE username=:user");
        $getConfirmState->bindValue(":user", $_SESSION["user"], PDO::PARAM_STR);
        $getConfirmState->execute();
        foreach($getConfirmState as $confirmState){
          $confirmation = $confirmState["qb_confirm"];
        }
        # fetch available products
        $getProducts = $db->query("SELECT qb_id, qb_creator, qb_product, qb_short, qb_price FROM QuickBuy WHERE bought=0")->fetchAll();
        foreach($getProducts as $product){
          echo "<form method='post' action='quickbuy.php'>";
            echo "<div class='col-lg-3 col-md-4' style='max-height: 175px; height: 175px; overflow-y: hidden'>";
              echo "<div class='sm-st clearfix'>";
                echo "<input type='hidden' name='qbID' value='" . htmlspecialchars($product["qb_id"], ENT_QUOTES) . "'>";
                echo "<input type='hidden' name='token' value='" . htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES) . "'>";
                if($confirmation == 0){
                  echo "<button type='submit' name='buyAdvert' class='sm-st-icon st-blue'><i class='fa fa-shopping-cart'></i></button>";
                }else{
                  ?>
                  <button onclick="return confirm('Produkt wirklich kaufen?');" type="submit" name="buyAdvert" class="sm-st-icon st-blue"><i class="fa fa-shopping-cart"></i></button>
                  <?php
                }
                echo "<div class='sm-st-info'>";
                  echo "<span>" . htmlspecialchars($product["qb_product"], ENT_QUOTES) . "</span>";
                  echo htmlspecialchars($product["qb_short"], ENT_QUOTES);
                  echo "<br><b>" . htmlspecialchars($product["qb_price"], ENT_QUOTES) . "</b><br>";
                  echo "<i>" . htmlspecialchars($product["qb_creator"], ENT_QUOTES) . "</i>";
                echo "</div>";
              echo "</div>";
            echo "</div>";
          echo "</form>";
        }
        ?>
      </div><!-- end row -->
      <!-- modal advert creation -->
      <div aria-hidden="true" role="dialog" tabindex="-1" id="myModalQB" class="modal fade">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button aria-hidden="true" data-dismiss="modal" class="close" type="button">x</button>
              <h4 class="modal-title">Eigenes Inserat erstellen</h4>
            </div><!-- end modal-header -->
            <div class="modal-body">
              <form method="post">
                <label>Produktname</label>
                <input type="text" class="form-control" name="productName" placeholder="Welches Produkt möchtest Du anbieten?" required="required" />
                <label>Kurzbeschreibung</label>
                <input type="text" class="form-control" name="productDescription" placeholder="z.B. weitere Informationen oder Mengenangabe" required="required" />
                <label>Preis</label>
                <input type="number" class="form-control" name="productPrice" min="1" max="9999" step="0.01" required="required" />
                <div class="checkbox disabled">
                  <label><input type="checkbox" disabled>Premiumversand anbieten (derzeit noch nicht verfügbar)</label>
                </div>
                <input type="hidden" value="<?php echo $_SESSION['csrf_token']; ?>" name="token">
                <button type="submit" class="btn btn-block btn-primary" name="createAdvert">Inserat erstellen</button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- modal bought products -->
      <div aria-hidden="true" role="dialog" tabindex="-1" id="myModalQBbought" class="modal fade">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button aria-hidden="true" data-dismiss="modal" class="close" type="button">x</button>
              <h4 class="modal-title">Inserate, welche Du von anderen Spielern gekauft hast</h4>
            </div><!-- end modal-header -->
            <div class="modal-body">
              <?php
              if($countBoughtProducts == 0){
                echo "Du hast noch keine Inserate von anderen Spielern gekauft.";
              }else{
                foreach($getBoughtProducts as $boughtProduct){
                  echo "<div class='sm-st clearfix'>";
                    echo "<input type='hidden' name='token' value='" . htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES) . "'>";
                    echo "<span class='sm-st-icon st-blue'><i class='fa fa-shopping-cart'></i></span>";
                    echo "<div class='sm-st-info'>";
                      echo "<span>" . htmlspecialchars($boughtProduct["qb_product"], ENT_QUOTES) . "</span>";
                      echo htmlspecialchars($boughtProduct["qb_short"], ENT_QUOTES);
                      echo "<br><b>" . htmlspecialchars($boughtProduct["qb_price"], ENT_QUOTES) . " Kadis</b><br>";
                      echo "<i>QuickBuy-ID: <b>" . htmlspecialchars($boughtProduct["qb_id"], ENT_QUOTES) . "</b></i><br>";
                      echo "Verkäufer: <b>" . htmlspecialchars($boughtProduct["qb_creator"], ENT_QUOTES) . "</b><br>";
                    echo "</div>";
                  echo "</div>";
                }
              }
              ?>
            </div><!-- end modal-body -->
          </div><!-- end modal-content -->
        </div><!-- end modal-dialog -->
      </div><!-- end modal fade -->
      <!-- modal sold products -->
      <div aria-hidden="true" role="dialog" tabindex="-1" id="myModalQBsold" class="modal fade">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button aria-hidden="true" data-dismiss="modal" class="close" type="button">x</button>
              <h4 class="modal-title">Eigene Inserate, die von anderen Spielern gekauft wurden</h4>
            </div><!-- end modal-header -->
            <div class="modal-body">
              <?php
              if($countSoldProducts == 0){
                echo "Bisher wurde noch kein Inserat von Dir gekauft.";
              }else{
                foreach($getSoldProducts as $soldProduct){
                  echo "<div class='sm-st clearfix'>";
                    echo "<input type='hidden' name='token' value='" . htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES) . "'>";
                    echo "<span class='sm-st-icon st-blue'><i class='fa fa-shopping-cart'></i></span>";
                    echo "<div class='sm-st-info'>";
                      echo "<span>" . htmlspecialchars($soldProduct["qb_product"], ENT_QUOTES) . "</span>";
                      echo htmlspecialchars($soldProduct["qb_short"], ENT_QUOTES);
                      echo "<br><b>" . htmlspecialchars($soldProduct["qb_price"], ENT_QUOTES) . " Kadis</b><br>";
                      echo "<i>QuickBuy-ID: <b>" . htmlspecialchars($soldProduct["qb_id"], ENT_QUOTES) . "</b></i><br>";
                      echo "Gekauft von: <b>" . htmlspecialchars($soldProduct["bought_by"], ENT_QUOTES) . "</b>";
                    echo "</div>";
                  echo "</div>";
                }
              }
              ?>
            </div><!-- end modal-body -->
          </div><!-- end modal-content -->
        </div><!-- end modal-dialog -->
      </div><!-- end modal fade -->
    </section><!-- end section -->
  </aside><!-- end aside -->
</body><!-- end body -->
</html><!-- end html -->

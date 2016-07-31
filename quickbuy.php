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
$getBoughtProducts = $db->prepare("SELECT qb_id FROM QuickBuy WHERE bought=1 AND bought_by=:user");
$getBoughtProducts->bindValue(":user", $_SESSION["user"], PDO::PARAM_STR);
$getBoughtProducts->execute();
$countBoughtProducts = $getBoughtProducts->rowCount();

# get and count sold products
$getSoldProducts = $db->prepare("SELECT qb_id FROM QuickBuy WHERE bought=1 AND qb_creator=:user");
$getSoldProducts->bindValue(":user", $_SESSION["user"]. PDO::PARAM_STR);
$getSoldProducts->execute();
$countSoldProducts = $getSoldProducts->rowCount();
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
            echo "<div class='col-md-3'>";
              echo "<div class='sm-st clearfix'>";
                echo "<input type='hidden' name='qbId' value='" . htmlspecialchars($product["qb_id"], ENT_QUOTES) . "'>";
                echo "<input type='hidden' name='token' value='" . htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES) . "'>";
                if($confirmation == 0){
                  echo "<button type='submit' name='buyInserat' class='sm-st-icon st-blue'><i class='fa fa-shopping-cart'></i></button>";
                }else{
                  ?>
                  <button onclick="return confirm('Produkt wirklich kaufen?');" type="submit" name="buyInserat" class="sm-st-icon st-blue"><i class="fa fa-shopping-cart"></i></button>
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
    </section><!-- end section -->
  </aside><!-- end aside -->
</body><!-- end body -->
</html><!-- end html -->

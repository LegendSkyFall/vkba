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
              <a data-toggle="modal" href="#myModalQBbought"><i class="fa fa-shopping-cart fa-3x stat-elem"></i>
            </div>
            <h5 class="stat-info">Anzahl gekaufter Produkte: <?php echo $countBoughtProducts; ?></h5>
          </div><!-- end stat -->
        </div><!-- end col-md-2 -->
        <div class="col-md-2">
          <div class="stat">
            <div class="stat-icon" style="color: darkblue">
              <a data-toggle="modal" href="#myModalQBsold"><i class="fa fa-money fa-3x stat-elem"></i>
            </div>
            <h5 class="stat-info">Anzahl verkaufter Produkte: <?php echo $countSoldProducts; ?></h5>
          </div><!-- end stat -->
        </div><!-- end col-md-2 -->
        <div class="col-md-2">
          <div class="stat">
            <div class="stat-icon" style="color: darkblue">
              <a data-toggle="modal" href="#myModalQB"><i class="fa fa-plus fa-3x stat-elem"></i>
            </div>
            <h5 class="stat-info">Eigenes Inserat erstellen</h5>
          </div><!-- end stat -->
        </div><!-- end col-md-2 -->
      </div><!-- end row -->
      <br>
      <!-- row for quickbuy products -->
      <div class="row" style="margin-bottom: 5px">
        <?php
        //TODO
        ?>
      </div><!-- end row -->
    </section><!-- end section -->
  </aside><!-- end aside -->
</body><!-- end body -->
</html><!-- end html -->

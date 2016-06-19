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
        <div class="col-md-12">
          <div class="panel">
            <header class="panel-heading">
              Transaktionsübersicht
            </header>
            <div class="panel-body">
              <table class="table table-bordered">
                <tr>
                  <th>Transaktions-ID</th>
                  <th>Beschreibung</th>
                  <th>Empfänger</th>
                  <th>Absender</th>
                  <th>Betrag</th>
                  <th>Typ</th>
                  <th>Vorgangsdatum</th>
                  <th>Status</th>
                </tr>
                <?php
                //TODO
                ?>
              </table><!-- end table -->
            </div><!-- end panel-body -->
          </div><!-- end panel -->
        </div><!-- end col-md-12 -->
      </div><!-- end row -->
    </section><!-- end section -->
  </aside><!-- end aside -->
</body><!-- end body -->
</html><!-- end html -->

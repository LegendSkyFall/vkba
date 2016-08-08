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
                # get transactions and list them
                $getTransactions = $db->prepare("SELECT t_id, t_description, t_adress, t_sender, t_amount, t_type, t_date, t_state FROM Transactions WHERE t_adress=:adress OR t_sender=:sender ORDER BY t_date DESC");
                $getTransactions->bindValue(":adress", $_SESSION['user'], PDO::PARAM_STR);
                $getTransactions->bindValue(":sender", $_SESSION['user'], PDO::PARAM_STR);
                $getTransactions->execute();
                foreach($getTransactions as $Transaction){
                  echo "<tr>";
                    echo "<td>" . htmlspecialchars($Transaction['t_id'], ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars($Transaction['t_description'], ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars($Transaction['t_adress'], ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars($Transaction['t_sender'], ENT_QUOTES) . "</td>";
                    echo "<td>" . htmlspecialchars($Transaction['t_amount'], ENT_QUOTES) . "</td>";
                    # check type of transaction
                    switch(true){
                      case($Transaction['t_type'] == 0):
                        echo "<td>Privat</td>";
                        break;
                      case($Transaction['t_type'] == 1):
                        echo "<td>System</td>";
                        break;
                    }
                    echo "<td>" . htmlspecialchars($Transaction['t_date'], ENT_QUOTES) . "</td>";
                    # check state of Transaction
                    switch(true){
                      case($Transaction['t_state'] == 0):
                        echo "<td class='warning'>Ausstehend</td>";
                        break;
                      case($Transaction['t_state'] == 1):
                        echo "<td class='success'>Abgeschlossen</td>";
                        break;
                      case($Transaction['t_state'] == 2):
                        echo "<td class='danger'>Käuferschutz - Warte auf Rückbuchung</td>";
                        break;
                      case($Transaction['t_state'] ==3):
                        echo "<td style='background-color: #d9edf7'>Rückbuchung erfolgreich</td>";
                        break;
                    }
                  echo "</tr>";
                }
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

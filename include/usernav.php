<div class="wrapper row-offcanvas row-offcanvas-left">
  <aside style="z-index: 100" class="left-side sidebar-offcanvas">
    <section class="sidebar">
      <div class="user-panel">
        <div class="pull-left image">
          <?php
          $getAvatar = "https://cravatar.eu/avatar/" . $_SESSION['user'];
          echo "<img src='$getAvatar' class='img-circle' alt='Avatar'/>";
          ?>
        </div>
        <div class="pull-left info">
          <?php
          echo "<p>" . $_SESSION['user'] . "</p>";
          ?>
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div><!-- end user-panel -->
      <ul class="sidebar-menu">
        <?php
        // get active page for highlighting
        switch(basename($_SERVER["REQUEST_URI"])){
          case basename($_SERVER["REQUEST_URI"]) == "index.php":
            $active = "index";
            break;
          case basename($_SERVER["REQUEST_URI"]) == "transactions.php":
            $active = "transactions";
            break;
          case basename($_SERVER["REQUEST_URI"]) == "actions.php":
            $active = "actions";
            break;
          case basename($_SERVER["REQUEST_URI"]) == "quickbuy.php":
            $active = "quickbuy";
            break;
          default:
            $active = "index";
            break;
        }
        ?>
        <?php
        if($active == "index"){
          echo "<li class='active'>";
        }else{
          echo "<li>";
        }
        ?>
          <a href="index.php">
            <i class="fa fa-eye"></i> <span>Übersicht</span>
          </a>
        </li>
        <?php
        if($active == "transactions"){
          echo "<li class='active'>";
        }else{
          echo "<li>";
        }
        ?>
          <a href="transactions.php">
            <i class="fa fa-table"></i> <span>Transaktionen</span>
          </a>
        </li>
        <?php
        if($active == "actions"){
          echo "<li class='active'>";
        }else{
          echo "<li>";
        }
        ?>
          <a href="actions.php">
            <i class="fa fa-plus-square"></i> <span>Aktionen</span>
          </a>
        </li>
        <?php
        if($active == "quickbuy"){
          echo "<li class='active'>";
        }else{
          echo "<li>";
        }
        ?>
          <a href="quickbuy.php">
            <i class="fa fa-shopping-cart"></i> <span>QuickBuy</span>
          </a>
        </li>
        <li>
          <a data-toggle="modal" href="#modalSystem">
            <i class="fa fa-tasks"></i> <span>Systemstatus</span>
          </a>
        </li>
      </ul>
    </section><!-- end sidebar -->
  </aside><!-- end left-side sidebar-offcanvas -->
</div><!-- end wrapper row-offcanvas row-offcanvas-left -->
<!-- Modal system state -->
<div class="modal fade" id="modalSystem" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
        <h4 class="modal-title">VKBA-Systemstatus</h4>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger" style="font-weight: bold; text-align: center">Aufgrund von Domainproblemen wird der Status unter Umständen falsch angezeigt.</div>
        <?php
        # get last system states
        $getSystemState = $db->query("SELECT system, api, bot_backend, bot_push FROM SystemState")->fetch();
        # main system check
        if($getSystemState["system"] == 1){
          echo "<i class='fa fa-circle text-success'></i>&nbsp;<label>Hauptsystem (Online)</label>";
        }elseif($getSystemState["system"] == 2){
          echo "<i class='fa fa-circle text-warning'></i>&nbsp;<label>Hauptsystem (Langsam)</label>";
        }elseif($getSystemState["system"] == 0){
          echo "<i class='fa fa-circle text-danger'></i>&nbsp;<label>Hauptsystem (Offline/Probleme)</label>";
        }
        echo "<hr>";
        # api check
        if($getSystemState["api"] == 1){
          echo "<i class='fa fa-circle text-success'></i>&nbsp;<label>API (Online)</label>";
        }elseif($getSystemState["api"] == 2){
          echo "<i class='fa fa-circle text-warning'></i>&nbsp;<label>API (Langsam)</label>";
        }elseif($getSystemState["api"] == 0){
          echo "<i class='fa fa-circle text-danger'></i>&nbsp;<label>API (Offline/Probleme)</label>";
        }
        echo "<hr>";
        # bot_backend check
        if($getSystemState["bot_backend"] == 1){
          echo "<i class='fa fa-circle text-success'></i>&nbsp;<label>VKBA-Bot[Backend] (Online)</label>";
        }elseif($getSystemState["bot_backend"] == 2){
          echo "<i class='fa fa-circle text-warning'></i>&nbsp;<label>VKBA-Bot[Backend] (Langsam)</label>";
        }elseif($getSystemState["bot_backend"] == 0){
          echo "<i class='fa fa-circle text-danger'></i>&nbsp;<label>VKBA-Bot[Backend] (Offline/Probleme)</label>";
        }
        echo "<hr>";
        # bot_push check
        if($getSystemState["bot_push"] == 1){
          echo "<i class='fa fa-circle text-success'></i>&nbsp;<label>VKBA-Bot[Push] (Online)</label>";
        }elseif($getSystemState["bot_push"] == 2){
          echo "<i class='fa fa-circle text-warning'></i>&nbsp;<label>VKBA-Bot[Push] (Langsam)</label>";
        }elseif($getSystemState["bot_push"] == 0){
          echo "<i class='fa fa-circle text-danger'></i>&nbsp;<label>VKBA-Bot[Push] (Offline/Probleme)</label>";
        }
        echo "<hr>";
        $load = sys_getloadavg();
        echo "Derzeitige Serverauslastung: <b>" . $load[0] . "%</b><br>";
        $count = $db->query("SELECT COUNT(username) AS totalAccounts FROM Accounts")->fetch();
        echo "Anzahl angelegter Accounts: <b>" . $count["totalAccounts"] . "</b><br>";
        $count = $db->query("SELECT COUNT(t_id) AS totalTransactions FROM Transactions")->fetch();
        echo "Anzahl getätigter Transaktionen: <b>" . $count["totalTransactions"] . "</b><br>";
        $count = $db->query("SELECT COUNT(ID) AS totalIngameTransactions FROM Kontoauszug")->fetch();
        echo "Anzahl erfasster Ingame-Interaktionen: <b>" . $count["totalIngameTransactions"] . "</b><br>";

        ?>
      </div><!-- end modal-body -->
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</div><!-- end modal -->

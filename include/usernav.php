<div class="wrapper row-offcanvas row-offcanvas-left">
  <aside class="left-side sidebar-offcanvas">
    <section class="sidebar">
      <div class="user-panel">
        <div class="pull-left image">
          <?php
          $getAvatar = "https://cravatar.eu/avatar" . $_SESSION['user'];
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
            <i class="fa fa-eye"></i> <span>Transaktionen</span>
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
            <i class="fa fa-eye"></i> <span>Aktionen</span>
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
            <i class="fa fa-eye"></i> <span>QuickBuy</span>
          </a>
        </li>
        <li>
          <a data-toggle="modal" href="#myModal">
            <i class="fa fa-tasks"></i> <span>Systemstatus</span>
          </a>
        </li>
      </ul>
    </section><!-- end sidebar -->
  </aside><!-- end left-side sidebar-offcanvas -->
</div><!-- end wrapper row-offcanvas row-offcanvas-left -->

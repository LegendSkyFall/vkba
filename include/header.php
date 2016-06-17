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
                  <a data-toggle="modal" href="#myModal2">
                    <i class="fa fa-plus fa-fw pull-right"></i>
                    <span class="badge badge-success pull-right">2</span> Add-Ons</a>
                  <a data-toggle="modal" href="#myModal3">
                    <i class="fa fa-users fa-fw pull-right"></i>
                    <span class="badge badge-danger pull-right">0</span> Kontakte</a>
                  </a>
                </li>
                <li class="divider"></li>

</header>

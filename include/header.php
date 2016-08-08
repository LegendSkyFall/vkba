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
                  <span class="badge badge-success pull-right">2</span> Add-Ons
                </a>
                <a data-toggle="modal" href="#myModal3">
                  <i class="fa fa-users fa-fw pull-right"></i>
                  <span class="badge badge-danger pull-right">0</span> Kontakte
                </a>
                <a data-toggle="modal" href="#myModal3">
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
<div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
              <?php
              //TODO
              ?>
              <div class="sm-st-info">
                <?php
                //TODO
                ?>
                <button type="submit" name="deleteAddon" class="btn btn-default btn-sm pull-right">
                  <span style="color: #DC2E31" class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                </button>
                <span>Werbe-AddOn</span>
                Mit diesem AddOn bist Du berechtigt, Werbung beispielsweise für Deinen Warp auf Kadcon, oder aber auch für VKBA-Announcen zu schalten. Werbung erscheint zufällig bei QuickBuy.
                <br>
                <b>Kosten:</b> 50 Kadis/Woche
              </div><!-- end sm-st-info -->
            </div><!-- end sm-st clearfix -->
          </div><!-- end col-md-12 -->
        </form><!-- end form -->
        <form method="post" action="">
          <div class="col-md-12">
            <div class="sm-st clearfix">
              <?php
              //TODO
              ?>
              <div class="sm-st-info">
                <?php
                //TODO
                ?>
                <button type="submit" name="deleteAddon" class="btn btn-default btn-sm pull-right">
                  <span style="color: #DC2E31" class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                </button>
                <span>Händler-AddOn</span>
                Mit diesem AddOn bist Du berechtigt, eigene Inserate bei QuickBuy schalten zu können, auch wenn Du kein Händlerkonto besitzt.<b>AddOn nur für Girkonten verfügbar</b>
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
        </span>
      </div><!-- end modal-body -->
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</div><!-- end modal -->

<!-- Modal contacts -->
<div class="modal fade" id="myModal3" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
<div class="modal fade" id="myModal4" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
        </form>
      </div><!-- end modal-body -->
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</div><!-- end modal -->

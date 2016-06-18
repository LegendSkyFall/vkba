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
        //TODO
        ?>
      </ul>
    </section><!-- end sidebar -->
  </aside><!-- end left-side sidebar-offcanvas -->
</div><!-- end wrapper row-offcanvas row-offcanvas-left -->

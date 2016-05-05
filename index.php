<html>
  <?php
  include('include/head.php');
  ?>
  <body class="skin-black">
    <?php
    include('include/header.php');
    include('include/usernav.php');
    ?>
    <script>
    /* will be used later so that page doesn't have to reload */
    /* function for deleting servermessages */
    function delMessage() {
      $.post("include/backend/sysMessage.php", $("#sysMessageForm").serialize())
      .done(
        function(data){
          $("#delMessage").html(data);
        }
      );
    }
    </script>
    <aside class="right-side">
      <!-- content -->
      <section class="content">
        <?php
        # QuickBuy confirmation message
        if(!empty($qbConfirmMessage)){
          echo $qbConfirmMessage;
        }
        # AddOn message
        if(!empty($addonMessage)){
          echo $addonMessage;
        }
        # system message deletion
        if(!empty($sysMessage)){
          echo $sysMessage;
        }
        ?>
        <span id="delMessage"></span> <!-- sysMessage alert will appear here -->
        <div id="alert alert-warning" style="text-align: center; font-weight: bold">
          VKBA befindet sich derzeit noch in der First-Access-Phase. Fehler könnten noch auftreten!
        </div>
        <?php
        include('include/accountInformation.php');
        ?>
        <!-- user tiles -->
        <div class="row" style="margin-bottom: 5px">
          <!-- user balance -->
          <div class="col-md-3">
            <div class="sm-st clearfix">
              <span class="sm-st-icon st-blue"><i class="fa fa-dollar"></i></span>
              <div class="sm-st-info">
                <span>
                  <?php
                  echo $getUserBalance;
                  ?>
                </span>
                Kontostand in Kadis
              </div><!-- sm-st-info -->
            </div><!-- sm-st clearfix -->
          </div><!-- col-md-3 -->
          <!-- user transaction number -->
          <div class="col-md-3">
            <div class="sm-st clearfix">
              <span class="sm-st-icon st-red"><i class="fa fa-exchange"></i></span>
              <div class="sm-st-info">
                <span>
                  <?php
                  echo $getNumTransactions;
                  ?>
                </span>
                Anzahl der Transaktionen
              </div><!-- sm-st-info -->
            </div><!-- sm-st clearfix -->
          </div><!-- col-md-3 -->
          <!-- user QuickPoints -->
          <div class="col-md-3">
            <div class="sm-st clearfix">
              <span class="sm-st-icon st-violet"><i class="fa fa-money"></i></span>
              <div class="sm-st-info">
                <span>
                  <?php
                  echo $getQuickPoints;
                  ?>
                </span>
                QuickPoints
              </div><!-- sm-st-info -->
            </div><!-- sm-st clearfix -->
          </div><!-- col-md-3 -->
        <!-- user Account type -->
        <div class="col-md-3">
          <div class="sm-st clearfix">
            <span class="sm-st-icon st-green"><i class="fa fa-credit-card"></i></span>
            <div class="sm-st-info">
              <span>
                <?php
                if($getAccountType == 0){
                  echo "Girokonto";
                }elseif($getAccountType == 1){
                  echo "Händlerkonto";
                }
                ?>
              </span>
              QuickPoints
            </div><!-- sm-st-info -->
          </div><!-- sm-st clearfix -->
        </div><!-- col-md-3 -->
      </div><!-- row user tiles -->
      <!-- main content row -->
      <div class="row">
        <div class="col-md-8">
          <!-- balance history chart -->
          <section class="panel">
            <header class="panel-heading">
              Kontostandsverlauf
            </header>
            <div class="panel-body">
              <canvas id="balanceChart" width="600" height="330"></canvas>
            </div>
          </section><!-- panel balance chart -->
        </div><!-- col-md-8 -->
      </div><!-- main content row -->
    </section><!-- content -->
    </aside>
  </body>
</html>

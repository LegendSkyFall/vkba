<?php
# start session
session_start();
# require db connection
require('../db/pdo.inc.php');

if(isset($_POST['submit'])){
  $error = false;
  $postedPassword= $_POST['password'];

  # check login credentials
  $getAccountInfo = $db->prepare("SELECT akey, skey, username, pw_hash, salt, activated, a_type, ktn_nr FROM Accounts WHERE username=:username AND banned='0'");
  $getAccountInfo->bindValue(":username", $_POST["username"], PDO::PARAM_STR);
  $getAccountInfo->execute();
  $accountExists = ($getAccountInfo->rowCount() > 0) ? true : false;

  if($accountExists){
    foreach($getAccountInfo as $accountInfo){
      $getUsername = $accountInfo["username"];
      $getPassword = $accountInfo["pw_hash"];
      $getSaltKey = $accountInfo["salt"];
      $getActivatedState = $accountInfo["activated"];
      $getAType = $accountInfo["a_type"];
      $getKtnNr = $accountInfo["ktn_nr"];
      $getAKey = $accountInfo["akey"];
      $getSKey = $accountInfo["skey"];
    }
  }else{
    $error = true;
  }

  if(!$error){
    # create hash
    function hashPassword($postedPassword, $getSaltKey, $iterations){
      for($x=0; $x<$iterations; $x++){
        $postedPassword = hash("sha512", $postedPassword . $getSaltKey);
      }
      return $postedPassword;
    }
    $passwordHash = hashPassword($postedPassword, utf8_decode($getSaltKey), 10000);

    if($getPassword != $passwordHash){
      # wrong password
      $error = true;
    }

    if($getActivatedState == 0){
      # not verified
      $error = true;
  		$activatedMessage = "Dein Account ist nicht verifiziert! Eine Anleitung gibt es <a href='http://tinyurl.com/verify-vkba'>hier</a>. Bitte anschließend <a href='../verify'>hier</a> Deinen Account aktivieren</a>.";
  	}

  }

  # if no error, try to login
  if(!$error){
    if(empty($_SESSION["user"])){
      session_regenerate_id();
      $_SESSION["user"] = $getUsername;
      $_SESSION["csrf_token"] = uniqid('',true); # protection against CSRF
			$_SESSION["ktn_nr"] = $getKtnNr;
			$_SESSION["ktype"] = $getAType;
      $_SESSION["akey"] = $getAKey;
      $_SESSION["skey"] = $getSKey;
      # login successfull
			header("Location: ../");
    }else{
      # already logged in
      header("Location: ../");
    }
  }else{
      # login not successfull
      $errorMessage = "Zugangsdaten falsch oder Account nicht verifiziert (Anleitung gibt es <a href='http://tinyurl.com/verify'>hier</a>).Bitte erneut versuchen! Unter Umständen kann auch eine Sperrung des Accounts vorliegen.";
	  	session_destroy();
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>VKBA Rewrite Login</title>
  <!-- optimization for mobile phones and small screens -->
  <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
  <meta name="description" content="Developed By LegendSkyFall">
  <meta name="keywords" content="Virtual Kadcon Bank Accounts, LEGEND-BANK, LegendSkyFall, VKBA, Kadcon">
  <!-- jQuery -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  <!-- bootstrap JS -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
  <!-- bootstrap CDN -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <!-- font Awesome CDN-->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <!-- Lato font CDN -->
  <link href='https://fonts.googleapis.com/css?family=Lato' rel='stylesheet' type='text/css'>
  <!-- theme style -->
  <link href="../style/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
  <div id="login-overlay" class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="myModalLabel">VKBA Rewrite Login</h4>
      </div>
      <div class="modal-body">
        <?php
        if(!empty($activatedMessage)){
          echo "<div class='alert alert-danger' style='font-weight: bold; text-align: center'>" . $activatedMessage . "</div>";
        }
        if(!empty($errorMessage)){
          echo "<div class='alert alert-danger' style='font-weight: bold; text-align: center'>" . $errorMessage . "</div>";
        }
        ?>
        <div class="row">
          <div class="col-xs-12">
            <div class="well">
              <form id="loginForm" method="POST" name="login" action="index.php">
                <div class="form-group">
                  <label for="username" class="control-label">Username</label>
                  <input type="text" class="form-control" id="username" name="username" value="" required="" placeholder="Benutzername">
                  <span class="help-block"></span>
                </div>
                <div class="form-group">
                  <label for="password" class="control-label">Passwort</label>
                  <input type="password" class="form-control" id="password" name="password" value="" required="">
                  <span class="help-block"></span>
                </div>
                <button type="submit" class="btn btn-success btn-block" name="submit">Login</button>
                <a href="http://bank.legendskyfall.de#contact" class="btn btn-default btn-block">Probleme beim Einloggen?</a>
                <div class="alert alert-warning" style="font-weight: bold; text-align: center">Um VKBARewrite nutzen zu können, müssen bestehende Accounts <a href="../migrate">migriert</a> werden.</div>
              </form>
            </div><!-- end well -->
          </div><!-- end col-xs-12 -->
          <div class="col-xs-12">
            <p class="lead">Noch kein Account? <span class="text-success">Kostenlos</span> anlegen!</p>
            <ul class="list-unstyled" style="line-height: 2">
              <li><span class="fa fa-check text-success"></span> individuelle Anpassung, mehrere Kontotypen</li>
              <li><span class="fa fa-check text-success"></span> von überall aus verwaltbar</li>
              <li><span class="fa fa-check text-success"></span> Termin-, Dauerüberweisung, Guthabenaufladung, QuickBuy u.v.m.</li>
              <li><span class="fa fa-check text-success"></span> Käuferschutz</li>
              <li><span class="fa fa-check text-success"></span> 100 Kadis Startguthaben sichern <small>(nur für kurze Zeit)</small></li>
              <li><a href="http://t37618.kshor.de"><u>Mehr lesen</u></a></li>
            </ul>
            <p><a href="../register" class="btn btn-info btn-block">Jetzt Konto anlegen!</a></p>
          </div><!-- end col-xs-12 -->
        </div><!-- end row -->
      </div><!-- end modal-body -->
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</body>
</html>

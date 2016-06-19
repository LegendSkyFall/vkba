<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>VKBA Rewrite Registrierung</title>
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
        <h4 class="modal-title" id="myModalLabel">VKBA Rewrite Registrierung</h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-xs-12">
            <div clas="well">
              <form id="registerForm" method="post" name="register" action="">
                <div class="form-group">
                  <label for="username" class="control-label">Minecraft-Name</label>
                  <input type="text" class="form-control" id="username" name="username" value="" required="" placeholder="Ingame-Name">
                  <span class="help-block">Der Name muss Deinem Kadcon-Namen entsprechen</span>
                </div>
                <div class="form-group">
                  <label for "password" class="control-label">Passwort</label>
                  <input type="password" class="form-control" id="password" name="password" value="" required="">
                  <span class="help-block">Muss aus mindestens 6 Zeichen bestehen</span>
                </div>
                <div class="form-group">
                  <label for="password2" class="control-label">Passwort wiederholen</label>
                  <input type="password" class="form-control" id="password2" name="password2" value="" required="">
                  <span class="help-block"></span>
                </div>
                <div class="form-group">
                  <label for="a_type" class="control-label">Kontotyp auswählen</label>
                  <select class="form-control m-b-10" name="a_type">
                    <option value="0">Girokonto</option>
                    <option value="1">Händlerkonto</option>
                  </select>
                  <span class="help-block">
                    <ul>
                      <li>Girokonto: kostenlos. Fehlende Features können per AddOn nachgekauft werden. Auszahlung 5% Abzug</li>
                      <li>Händlerkonto: 50 Kadis pro Woche. Auszahlung 1% Abzug. Kostenlos eigene QuickBuy Inserate erstellen</li>
                      <li>Weiteres folgt..</li>
                    </ul>
                  </span>
                </div>
                <button type="submit" class="btn btn-success btn-block" name="submit">Jetzt Account erstellen</button>
                <span style="text-align: center; font-weight: bold" class="help-block">100 Kadis Startguthaben direkt mitsichern - nur für kurze Zeit</span>
                <br>
                <span class="help-block">
                  Bei Fragen bitte an den Support wenden oder im entsprechenden Thread nachfragen.
                  Mit der Nutzung von VKBA erklärst Du Dich mit unseren AGB einverstanden.
                  Nach der Registrierung erhälst Du Deine Kontonummer. Verteile sie an Freunde, damit Du mit ihnen handeln kannst.
                  Deinen SKey und AKey niemals an Dritte weitergeben!
                  VKBA befindet sich noch in der Entwicklungsphase und wird stets aktualisiert. Unter Umständen können noch Fehler auftauchen.
                </span>
              </form><!-- end form -->
            </div><!-- end well -->
          </div><!-- end col-xs-12 -->
        </div><!-- end row -->
      </div><!-- end modal-body -->
    </div><!-- end modal-content -->
  </div><!-- end modal-dialog -->
</body><!-- end body -->
</html><!-- end html -->
